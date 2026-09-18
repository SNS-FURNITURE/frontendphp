<?php

namespace App\Services\Payroll;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\PayrollLine;
use App\Models\PayrollRun;
use App\Services\AttendanceSessionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class PayrollGenerationService
{
    /**
     * @return array{unpaid_days: float, attendance_factor: float, overtime_hours: float, days_worked: float}
     */
    public function attendanceFactorsForPeriod(string $period): array
    {
        [$year, $month] = array_map('intval', explode('-', $period));
        $totalDays = (int) (new \DateTimeImmutable(sprintf('%04d-%02d-01', $year, $month)))->format('t');
        $sundays = 0;
        for ($d = 1; $d <= $totalDays; $d++) {
            if ((int) (new \DateTimeImmutable(sprintf('%04d-%02d-%02d', $year, $month, $d)))->format('w') === 0) {
                $sundays++;
            }
        }
        $workingDays = max(1, $totalDays - $sundays);
        $from = sprintf('%s-01', $period);
        $to = sprintf('%s-%02d', $period, $totalDays);

        app(AttendanceSessionService::class)->ensureSaturdayAfternoonsPresent($from, $to);

        $rows = Attendance::query()
            ->whereDate('date', '>=', $from)
            ->whereDate('date', '<=', $to)
            ->get(['employee_id', 'status']);

        $sessionsByEmp = [];
        foreach ($rows as $row) {
            $eid = (int) $row->employee_id;
            $current = $sessionsByEmp[$eid] ?? ['present' => 0.0, 'unpaid' => 0.0];
            $status = (string) $row->status;
            if (in_array($status, ['present', 'late', 'holiday', 'leave'], true)) {
                $current['present'] += 0.5;
            } elseif ($status === 'half_day') {
                $current['present'] += 0.25;
            } elseif ($status === 'absent') {
                $current['unpaid'] += 0.5;
            }
            $sessionsByEmp[$eid] = $current;
        }

        $map = [];
        foreach ($sessionsByEmp as $employeeId => $counts) {
            $attendanceFactor = min(1.0, max(0.0, $counts['present'] / $workingDays));
            $map[$employeeId] = [
                'unpaid_days' => $counts['unpaid'],
                'attendance_factor' => $attendanceFactor ?: 1.0,
                'overtime_hours' => 0.0,
                'days_worked' => $counts['present'] ?: 30.0,
            ];
        }

        return $map;
    }

    /**
     * @param  array<int, array{unpaid_days: float, attendance_factor: float, overtime_hours: float, days_worked: float}>|null  $factorsByEmployee
     * @return array{id: int, period: string, status: string, reused: bool}
     */
    public function generatePayrollRunForPeriod(
        string $period,
        ?array $factorsByEmployee = null,
        ?int $attendanceSubmissionId = null,
    ): array {
        $existing = PayrollRun::query()->where('period', $period)->first();
        if ($existing) {
            return [
                'id' => (int) $existing->id,
                'period' => $period,
                'status' => 'draft',
                'reused' => true,
            ];
        }

        $employees = Employee::query()
            ->active()
            ->with('party')
            ->get();

        if ($employees->isEmpty()) {
            throw new RuntimeException('No employees found to generate payroll', 400);
        }

        return DB::transaction(function () use ($period, $factorsByEmployee, $attendanceSubmissionId, $employees) {
            $runAttrs = [
                'period' => $period,
                'status' => 'draft',
                'employee_count' => $employees->count(),
                'total_gross' => 0,
                'total_paye' => 0,
                'total_employee_pension' => 0,
                'total_employer_pension' => 0,
                'total_net' => 0,
                'total_employer_cost' => 0,
            ];
            if (Schema::hasColumn('payroll_runs', 'attendance_submission_id')) {
                $runAttrs['attendance_submission_id'] = $attendanceSubmissionId;
            }

            $run = PayrollRun::query()->create($runAttrs);

            $totalGross = 0.0;
            $totalPaye = 0.0;
            $totalEmpPension = 0.0;
            $totalEmployerPension = 0.0;
            $totalNet = 0.0;
            $totalEmployerCost = 0.0;

            foreach ($employees as $emp) {
                $salary = (float) ($emp->monthly_salary ?: 25000);
                $factor = $factorsByEmployee[(int) $emp->id] ?? null;
                $roleMultiplier = EthiopiaPayrollCalculator::roleMultiplierFor($emp->job_title);
                $computed = EthiopiaPayrollCalculator::computePayrollLine([
                    'basic_salary' => $salary,
                    'days_worked' => $factor['days_worked'] ?? 30,
                    'role_multiplier' => $roleMultiplier,
                    'attendance_factor' => $factor['attendance_factor'] ?? 1,
                    'overtime_hours' => $factor['overtime_hours'] ?? 0,
                    'unpaid_days' => $factor['unpaid_days'] ?? 0,
                ]);
                $formulaText = EthiopiaPayrollCalculator::formatPayrollFormula($computed);

                $totalGross += $computed['gross'];
                $totalPaye += $computed['paye'];
                $totalEmpPension += $computed['employee_pension'];
                $totalEmployerPension += $computed['employer_pension'];
                $totalNet += $computed['net'];
                $totalEmployerCost += $computed['employer_cost'];

                $lineAttrs = [
                    'payroll_run_id' => $run->id,
                    'employee_id' => $emp->id,
                    'employee_number' => $emp->employee_no ?: ($emp->employee_number ?: 'EMP-'.$emp->id),
                    'employee_name' => $emp->party?->name ?? 'Employee',
                    'department' => $emp->department,
                    'job_title' => $emp->job_title,
                    'bank_name' => $emp->bank_name ?: 'Commercial Bank of Ethiopia',
                    'bank_account_number' => $emp->bank_account_number ?: '1000'.$emp->id.'09283',
                    'basic_salary' => $computed['basic_salary'],
                    'transport_allowance' => $computed['transport_allowance'],
                    'housing_allowance' => $computed['housing_allowance'],
                    'other_allowance' => $computed['other_allowance'],
                    'overtime_hours' => $computed['overtime_hours'],
                    'overtime_multiplier' => $computed['overtime_multiplier'],
                    'overtime_pay' => $computed['overtime_pay'],
                    'unpaid_days' => $computed['unpaid_days'],
                    'unpaid_absence' => $computed['unpaid_absence'],
                    'other_deductions' => $computed['other_deductions'],
                    'gross' => $computed['gross'],
                    'employee_pension' => $computed['employee_pension'],
                    'employer_pension' => $computed['employer_pension'],
                    'taxable_income' => $computed['taxable_income'],
                    'paye' => $computed['paye'],
                    'net' => $computed['net'],
                    'employer_cost' => $computed['employer_cost'],
                ];

                foreach ([
                    'daily_rate', 'days_worked', 'salary_month', 'taxable_allowance',
                    'advance', 'long_term_loan', 'total_deduction',
                    'role_multiplier', 'attendance_factor', 'performance_factor', 'formula_text',
                ] as $col) {
                    if (Schema::hasColumn('payroll_lines', $col)) {
                        $lineAttrs[$col] = $col === 'formula_text' ? $formulaText : $computed[$col];
                    }
                }

                PayrollLine::query()->create($lineAttrs);
            }

            $run->fill([
                'total_gross' => EthiopiaPayrollCalculator::roundMoney($totalGross),
                'total_paye' => EthiopiaPayrollCalculator::roundMoney($totalPaye),
                'total_employee_pension' => EthiopiaPayrollCalculator::roundMoney($totalEmpPension),
                'total_employer_pension' => EthiopiaPayrollCalculator::roundMoney($totalEmployerPension),
                'total_net' => EthiopiaPayrollCalculator::roundMoney($totalNet),
                'total_employer_cost' => EthiopiaPayrollCalculator::roundMoney($totalEmployerCost),
            ]);
            $run->save();

            return [
                'id' => (int) $run->id,
                'period' => $period,
                'status' => 'draft',
                'reused' => false,
            ];
        });
    }

    /**
     * @return array{totalDays: int, workingDays: int}
     */
    public function workingDaysInPeriod(string $period): array
    {
        [$year, $month] = array_map('intval', explode('-', $period));
        $totalDays = (int) (new \DateTimeImmutable(sprintf('%04d-%02d-01', $year, $month)))->format('t');
        $sundays = 0;
        for ($d = 1; $d <= $totalDays; $d++) {
            if ((int) (new \DateTimeImmutable(sprintf('%04d-%02d-%02d', $year, $month, $d)))->format('w') === 0) {
                $sundays++;
            }
        }

        return [
            'totalDays' => $totalDays,
            'workingDays' => max(1, $totalDays - $sundays),
        ];
    }
}
