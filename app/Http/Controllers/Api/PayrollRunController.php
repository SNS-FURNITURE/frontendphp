<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AttendanceSubmission;
use App\Models\PayrollLine;
use App\Models\PayrollRun;
use App\Models\User;
use App\Services\AuditService;
use App\Services\NotifyService;
use App\Services\Payroll\EthiopiaPayrollCalculator;
use App\Services\Payroll\PayrollGenerationService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class PayrollRunController extends Controller
{
    public function __construct(
        private AuditService $audit,
        private NotifyService $notify,
        private PayrollGenerationService $payroll,
    ) {}

    public function index(): JsonResponse
    {
        $rows = PayrollRun::query()
            ->latestFirst('period')
            ->get()
            ->map(fn (PayrollRun $r) => $r->toApiArray())
            ->values()
            ->all();

        return ApiResponse::success($rows);
    }

    public function store(Request $request): JsonResponse
    {
        $period = (string) ($request->input('period') ?: now()->format('Y-m'));
        if (! preg_match('/^\d{4}-\d{2}$/', $period)) {
            return ApiResponse::error('Period must be in YYYY-MM format', 'INVALID_INPUT', 400);
        }

        $submission = AttendanceSubmission::query()
            ->where('period', $period)
            ->where('status', 'approved')
            ->first();

        if (! $submission) {
            return ApiResponse::error(
                "Company Manager must approve this month's attendance calendar before payroll can be generated",
                'ATTENDANCE_NOT_APPROVED',
                409,
            );
        }

        try {
            $factors = $this->payroll->attendanceFactorsForPeriod($period);
            $created = $this->payroll->generatePayrollRunForPeriod(
                $period,
                $factors,
                (int) $submission->id,
            );
        } catch (RuntimeException $e) {
            return ApiResponse::error($e->getMessage(), 'NOT_FOUND', 400);
        } catch (Throwable $e) {
            report($e);

            return ApiResponse::error('Payroll generation failed', 'REQUEST_FAILED', 500);
        }

        if (! ($created['reused'] ?? false)) {
            $submission->payroll_run_id = $created['id'];
            $submission->save();
        }

        /** @var User|null $user */
        $user = $request->attributes->get('auth_user') ?? $request->user();
        $this->audit->log($user, 'payroll_run', $created['id'], 'CREATE_PAYROLL_RUN', [
            'period' => $period,
        ], $request);

        return ApiResponse::success($created, null, $created['reused'] ? 200 : 201);
    }

    public function show(int $id): JsonResponse
    {
        $run = PayrollRun::query()->with('lines')->find($id);
        if (! $run) {
            return ApiResponse::error('Payroll run not found', 'NOT_FOUND', 404);
        }

        return ApiResponse::success($run->toApiArray(true));
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $status = $request->input('status');
        if (! in_array($status, PayrollRun::STATUSES, true)) {
            return ApiResponse::error('Invalid payroll status', 'INVALID_INPUT', 400);
        }

        $run = PayrollRun::query()->find($id);
        if (! $run) {
            return ApiResponse::error('Payroll run not found', 'NOT_FOUND', 404);
        }

        /** @var User|null $user */
        $user = $request->attributes->get('auth_user') ?? $request->user();

        $from = (string) $run->status;
        $allowed = $this->allowedStatusTransition($user, $from, $status);
        if ($allowed !== true) {
            return ApiResponse::error($allowed, 'FORBIDDEN', 403);
        }

        $run->status = $status;
        if ($status === PayrollRun::STATUS_PROCESSED) {
            $run->processed_at = now();
        }
        if ($status === PayrollRun::STATUS_PAID) {
            $run->paid_at = now();
        }
        $run->save();

        if ($status === PayrollRun::STATUS_PENDING_MANAGER) {
            foreach ($this->notify->activeUserIdsWithRolesRaw(['company_manager']) as $managerId) {
                $this->notify->notifyUser($managerId, [
                    'type' => 'payroll_final_review',
                    'title' => "Payroll ready for {$run->period}",
                    'message' => ($user?->full_name ?: 'Finance')." submitted {$run->period} payroll for final decision.",
                    'entityType' => 'payroll_run',
                    'entityId' => (int) $run->id,
                ]);
            }
        }

        if (in_array($status, [PayrollRun::STATUS_PROCESSED, PayrollRun::STATUS_REJECTED], true)) {
            foreach ($this->notify->activeUserIdsWithRolesRaw(['finance']) as $financeId) {
                $this->notify->notifyUser($financeId, [
                    'type' => 'payroll_final_decision',
                    'title' => $status === PayrollRun::STATUS_PROCESSED
                        ? "Payroll approved for {$run->period}"
                        : "Payroll rejected for {$run->period}",
                    'message' => $status === PayrollRun::STATUS_PROCESSED
                        ? "Company Manager approved {$run->period} payroll."
                        : "Company Manager rejected {$run->period} payroll. Revise and resubmit.",
                    'entityType' => 'payroll_run',
                    'entityId' => (int) $run->id,
                ]);
            }
        }

        $this->audit->log($user, 'payroll_run', (int) $run->id, 'UPDATE_PAYROLL_STATUS', [
            'from' => $from,
            'status' => $status,
        ], $request);

        return ApiResponse::success(['id' => (int) $run->id, 'status' => $status]);
    }

    /**
     * @return true|string
     */
    private function allowedStatusTransition(?User $user, string $from, string $to): bool|string
    {
        if (! $user) {
            return 'Authentication required';
        }

        if ($user->isAdmin()) {
            return true;
        }

        $financeTransitions = [
            PayrollRun::STATUS_DRAFT => [PayrollRun::STATUS_PENDING_MANAGER],
            PayrollRun::STATUS_REJECTED => [PayrollRun::STATUS_PENDING_MANAGER, PayrollRun::STATUS_DRAFT],
            PayrollRun::STATUS_PROCESSED => [PayrollRun::STATUS_PAID],
        ];

        $managerTransitions = [
            PayrollRun::STATUS_PENDING_MANAGER => [PayrollRun::STATUS_PROCESSED, PayrollRun::STATUS_REJECTED],
        ];

        if ($user->canEditPayroll() && in_array($to, $financeTransitions[$from] ?? [], true)) {
            return true;
        }

        if ($user->canFinalizePayroll() && in_array($to, $managerTransitions[$from] ?? [], true)) {
            return true;
        }

        return 'You cannot move this payroll to that status';
    }

    public function updateLine(Request $request, int $id, int $lineId): JsonResponse
    {
        $line = PayrollLine::query()
            ->where('id', $lineId)
            ->where('payroll_run_id', $id)
            ->first();

        if (! $line) {
            return ApiResponse::error('Payroll line not found', 'NOT_FOUND', 404);
        }

        $payload = $request->all();
        $computed = EthiopiaPayrollCalculator::computePayrollLine([
            'basic_salary' => (float) $line->basic_salary,
            'daily_rate' => array_key_exists('daily_rate', $payload) ? $payload['daily_rate'] : ($line->daily_rate ?: null),
            'days_worked' => array_key_exists('days_worked', $payload) ? $payload['days_worked'] : ($line->days_worked ?: null),
            'taxable_allowance' => array_key_exists('taxable_allowance', $payload) ? $payload['taxable_allowance'] : ($line->taxable_allowance ?: null),
            'transport_allowance' => array_key_exists('transport_allowance', $payload) ? $payload['transport_allowance'] : $line->transport_allowance,
            'housing_allowance' => array_key_exists('housing_allowance', $payload) ? $payload['housing_allowance'] : $line->housing_allowance,
            'other_allowance' => array_key_exists('other_allowance', $payload) ? $payload['other_allowance'] : $line->other_allowance,
            'overtime_hours' => array_key_exists('overtime_hours', $payload) ? $payload['overtime_hours'] : $line->overtime_hours,
            'overtime_multiplier' => array_key_exists('overtime_multiplier', $payload) ? $payload['overtime_multiplier'] : $line->overtime_multiplier,
            'overtime_pay' => array_key_exists('overtime_pay', $payload)
                ? $payload['overtime_pay']
                : (array_key_exists('overtime_hours', $payload) ? null : $line->overtime_pay),
            'unpaid_days' => array_key_exists('unpaid_days', $payload) ? $payload['unpaid_days'] : $line->unpaid_days,
            'other_deductions' => array_key_exists('other_deductions', $payload) ? $payload['other_deductions'] : $line->other_deductions,
            'advance' => array_key_exists('advance', $payload) ? $payload['advance'] : ($line->advance ?? 0),
            'long_term_loan' => array_key_exists('long_term_loan', $payload) ? $payload['long_term_loan'] : ($line->long_term_loan ?? 0),
            'role_multiplier' => array_key_exists('role_multiplier', $payload) ? $payload['role_multiplier'] : ($line->role_multiplier ?? 1),
            'attendance_factor' => array_key_exists('attendance_factor', $payload) ? $payload['attendance_factor'] : ($line->attendance_factor ?? 1),
            'performance_factor' => array_key_exists('performance_factor', $payload) ? $payload['performance_factor'] : ($line->performance_factor ?? 1),
        ]);
        $formulaText = EthiopiaPayrollCalculator::formatPayrollFormula($computed);

        return DB::transaction(function () use ($line, $computed, $formulaText, $id) {
            $updates = [
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
                'role_multiplier', 'attendance_factor', 'performance_factor',
            ] as $col) {
                if (Schema::hasColumn('payroll_lines', $col)) {
                    $updates[$col] = $computed[$col];
                }
            }
            if (Schema::hasColumn('payroll_lines', 'formula_text')) {
                $updates['formula_text'] = $formulaText;
            }

            $line->fill($updates);
            $line->save();

            $sums = PayrollLine::query()
                ->where('payroll_run_id', $id)
                ->selectRaw('SUM(gross) as total_gross, SUM(paye) as total_paye, SUM(employee_pension) as total_emp_pension, SUM(employer_pension) as total_employer_pension, SUM(net) as total_net, SUM(employer_cost) as total_employer_cost')
                ->first();

            $run = PayrollRun::query()->findOrFail($id);
            $run->fill([
                'total_gross' => EthiopiaPayrollCalculator::roundMoney((float) ($sums->total_gross ?? 0)),
                'total_paye' => EthiopiaPayrollCalculator::roundMoney((float) ($sums->total_paye ?? 0)),
                'total_employee_pension' => EthiopiaPayrollCalculator::roundMoney((float) ($sums->total_emp_pension ?? 0)),
                'total_employer_pension' => EthiopiaPayrollCalculator::roundMoney((float) ($sums->total_employer_pension ?? 0)),
                'total_net' => EthiopiaPayrollCalculator::roundMoney((float) ($sums->total_net ?? 0)),
                'total_employer_cost' => EthiopiaPayrollCalculator::roundMoney((float) ($sums->total_employer_cost ?? 0)),
            ]);
            $run->save();

            return ApiResponse::success($run->fresh('lines')?->toApiArray(true));
        });
    }

    public function csv(int $id): StreamedResponse|JsonResponse
    {
        $run = PayrollRun::query()->with('lines')->find($id);
        if (! $run) {
            return ApiResponse::error('Payroll run not found', 'NOT_FOUND', 404);
        }

        $filename = 'payroll-'.$run->period.'.csv';

        return response()->streamDownload(function () use ($run) {
            $out = fopen('php://output', 'w');
            fputcsv($out, [
                'Employee Number', 'Employee Name', 'Department', 'Job Title',
                'Bank Name', 'Bank Account', 'Basic Salary', 'Gross', 'PAYE',
                'Employee Pension', 'Net', 'Employer Cost',
            ]);
            foreach ($run->lines as $line) {
                fputcsv($out, [
                    $line->employee_number,
                    $line->employee_name,
                    $line->department,
                    $line->job_title,
                    $line->bank_name,
                    $line->bank_account_number,
                    $line->basic_salary,
                    $line->gross,
                    $line->paye,
                    $line->employee_pension,
                    $line->net,
                    $line->employer_cost,
                ]);
            }
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }
}
