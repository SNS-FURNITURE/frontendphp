<?php

namespace App\Services\Payroll;

class EthiopiaPayrollCalculator
{
    public const EMPLOYEE_PENSION_RATE = 0.07;

    public const EMPLOYER_PENSION_RATE = 0.11;

    public const STANDARD_MONTHLY_HOURS = 208;

    public const STANDARD_MONTHLY_DAYS = 30;

    /** @var list<array{max: float, rate: float, deduction: float}> */
    public const PAYE_BRACKETS = [
        ['max' => 2000.0, 'rate' => 0.0, 'deduction' => 0.0],
        ['max' => 4000.0, 'rate' => 0.15, 'deduction' => 300.0],
        ['max' => 7000.0, 'rate' => 0.20, 'deduction' => 500.0],
        ['max' => 10000.0, 'rate' => 0.25, 'deduction' => 850.0],
        ['max' => 14000.0, 'rate' => 0.30, 'deduction' => 1350.0],
        ['max' => INF, 'rate' => 0.35, 'deduction' => 2050.0],
    ];

    /** @var array<string, float> */
    public const ROLE_MULTIPLIERS = [
        'General Manager' => 1.35,
        'Manager (Operations)' => 1.25,
        'Lead Carpenter' => 1.15,
        'Sales Unit Supervisor' => 1.12,
        'default' => 1.0,
    ];

    public static function roleMultiplierFor(?string $jobTitle): float
    {
        if ($jobTitle === null || $jobTitle === '') {
            return self::ROLE_MULTIPLIERS['default'];
        }

        return self::ROLE_MULTIPLIERS[$jobTitle] ?? self::ROLE_MULTIPLIERS['default'];
    }

    public static function roundMoney(float $value): float
    {
        return round(($value + PHP_FLOAT_EPSILON) * 100) / 100;
    }

    public static function computePaye(float $taxable): float
    {
        if ($taxable <= 0) {
            return 0.0;
        }

        $bracket = self::PAYE_BRACKETS[array_key_last(self::PAYE_BRACKETS)];
        foreach (self::PAYE_BRACKETS as $item) {
            if ($taxable <= $item['max']) {
                $bracket = $item;
                break;
            }
        }

        return max(0.0, self::roundMoney($taxable * $bracket['rate'] - $bracket['deduction']));
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, float|int>
     */
    public static function computePayrollLine(array $input): array
    {
        $basic = max(0.0, (float) ($input['basic_salary'] ?? 0));
        $unpaidDays = max(0.0, (float) ($input['unpaid_days'] ?? 0));
        $daysWorked = array_key_exists('days_worked', $input) && $input['days_worked'] !== null
            ? max(0.0, (float) $input['days_worked'])
            : max(0.0, self::STANDARD_MONTHLY_DAYS - $unpaidDays);

        $dailyRateProvided = array_key_exists('daily_rate', $input) && $input['daily_rate'] !== null;
        $dailyRate = $dailyRateProvided
            ? max(0.0, (float) $input['daily_rate'])
            : self::roundMoney($basic / self::STANDARD_MONTHLY_DAYS);

        $salaryMonth = $dailyRateProvided
            ? self::roundMoney($dailyRate * $daysWorked)
            : self::roundMoney(($basic * $daysWorked) / self::STANDARD_MONTHLY_DAYS);

        $housing = max(0.0, (float) ($input['housing_allowance'] ?? 0));
        $other = max(0.0, (float) ($input['other_allowance'] ?? 0));
        $taxableAllowance = array_key_exists('taxable_allowance', $input) && $input['taxable_allowance'] !== null
            ? max(0.0, (float) $input['taxable_allowance'])
            : self::roundMoney($housing + $other);
        $transport = max(0.0, (float) ($input['transport_allowance'] ?? 0));

        $overtimeHours = max(0.0, (float) ($input['overtime_hours'] ?? 0));
        $overtimeMultiplier = (float) ($input['overtime_multiplier'] ?? 0) ?: 1.25;
        $overtimeFromHours = self::roundMoney(
            $overtimeHours * ($basic / self::STANDARD_MONTHLY_HOURS) * $overtimeMultiplier
        );
        $overtimePay = array_key_exists('overtime_pay', $input) && $input['overtime_pay'] !== null
            ? max(0.0, self::roundMoney((float) $input['overtime_pay']))
            : $overtimeFromHours;

        $advance = max(0.0, (float) ($input['advance'] ?? 0));
        $longTermLoan = max(0.0, (float) ($input['long_term_loan'] ?? 0));
        $otherDeductions = max(0.0, (float) ($input['other_deductions'] ?? 0));

        $roleMultiplier = max(0.0, (float) ($input['role_multiplier'] ?? 1) ?: 1);
        $attendanceFactor = min(1.5, max(0.0, (float) ($input['attendance_factor'] ?? 1) ?: 1));
        $performanceFactor = min(1.5, max(0.0, (float) ($input['performance_factor'] ?? 1) ?: 1));

        $gross = self::roundMoney($salaryMonth + $taxableAllowance + $overtimePay);
        $employeePension = self::roundMoney($salaryMonth * self::EMPLOYEE_PENSION_RATE);
        $employerPension = self::roundMoney($salaryMonth * self::EMPLOYER_PENSION_RATE);
        $taxableIncome = max(0.0, $gross);
        $paye = self::computePaye($taxableIncome);
        $totalDeduction = self::roundMoney($paye + $employeePension + $advance + $longTermLoan + $otherDeductions);
        $net = max(0.0, self::roundMoney($gross - $totalDeduction + $transport));
        $employerCost = self::roundMoney($gross + $employerPension);
        $unpaidAbsence = self::roundMoney(($basic / self::STANDARD_MONTHLY_DAYS) * $unpaidDays);

        return [
            'basic_salary' => self::roundMoney($basic),
            'daily_rate' => $dailyRate,
            'days_worked' => $daysWorked,
            'salary_month' => $salaryMonth,
            'taxable_allowance' => self::roundMoney($taxableAllowance),
            'transport_allowance' => self::roundMoney($transport),
            'housing_allowance' => self::roundMoney($housing),
            'other_allowance' => self::roundMoney($other),
            'overtime_hours' => $overtimeHours,
            'overtime_multiplier' => $overtimeMultiplier,
            'overtime_pay' => $overtimePay,
            'unpaid_days' => $unpaidDays,
            'unpaid_absence' => $unpaidAbsence,
            'other_deductions' => self::roundMoney($otherDeductions),
            'advance' => self::roundMoney($advance),
            'long_term_loan' => self::roundMoney($longTermLoan),
            'total_deduction' => $totalDeduction,
            'role_multiplier' => $roleMultiplier,
            'attendance_factor' => $attendanceFactor,
            'performance_factor' => $performanceFactor,
            'scaled_base' => $salaryMonth,
            'gross' => $gross,
            'employee_pension' => $employeePension,
            'employer_pension' => $employerPension,
            'taxable_income' => $taxableIncome,
            'paye' => $paye,
            'net' => $net,
            'employer_cost' => $employerCost,
        ];
    }

    /**
     * @param  array<string, float|int>  $line
     */
    public static function formatPayrollFormula(array $line): string
    {
        return implode(' ', [
            'Salary/Month = Sal/day '.number_format((float) $line['daily_rate'], 2, '.', '').' × Day/wor '.$line['days_worked'],
            '= '.number_format((float) $line['salary_month'], 2, '.', ''),
            'Gross = Salary/Month + Taxable Allow '.number_format((float) $line['taxable_allowance'], 2, '.', '').' + OT '.number_format((float) $line['overtime_pay'], 2, '.', ''),
            '= '.number_format((float) $line['gross'], 2, '.', ''),
            'Total Deduction = Tax '.number_format((float) $line['paye'], 2, '.', '').' + Pension 7% '.number_format((float) $line['employee_pension'], 2, '.', ''),
            '+ Advance '.number_format((float) $line['advance'], 2, '.', '').' + Loan '.number_format((float) $line['long_term_loan'], 2, '.', ''),
            '= '.number_format((float) $line['total_deduction'], 2, '.', ''),
            'Net = Gross − Total Deduction + Transp/Allow '.number_format((float) $line['transport_allowance'], 2, '.', ''),
            '= '.number_format((float) $line['net'], 2, '.', '').' ETB',
        ]);
    }
}
