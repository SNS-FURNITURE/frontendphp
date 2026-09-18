<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollLine extends Model
{
    protected $table = 'payroll_lines';

    protected $fillable = [
        'payroll_run_id',
        'employee_id',
        'employee_number',
        'employee_name',
        'department',
        'job_title',
        'bank_name',
        'bank_account_number',
        'basic_salary',
        'daily_rate',
        'days_worked',
        'salary_month',
        'taxable_allowance',
        'transport_allowance',
        'housing_allowance',
        'other_allowance',
        'overtime_hours',
        'overtime_multiplier',
        'overtime_pay',
        'unpaid_days',
        'unpaid_absence',
        'other_deductions',
        'advance',
        'long_term_loan',
        'total_deduction',
        'role_multiplier',
        'attendance_factor',
        'performance_factor',
        'formula_text',
        'gross',
        'employee_pension',
        'employer_pension',
        'taxable_income',
        'paye',
        'net',
        'employer_cost',
    ];

    protected function casts(): array
    {
        return [
            'basic_salary' => 'decimal:2',
            'daily_rate' => 'decimal:2',
            'days_worked' => 'decimal:2',
            'salary_month' => 'decimal:2',
            'taxable_allowance' => 'decimal:2',
            'transport_allowance' => 'decimal:2',
            'housing_allowance' => 'decimal:2',
            'other_allowance' => 'decimal:2',
            'overtime_hours' => 'decimal:2',
            'overtime_multiplier' => 'decimal:2',
            'overtime_pay' => 'decimal:2',
            'unpaid_days' => 'decimal:2',
            'unpaid_absence' => 'decimal:2',
            'other_deductions' => 'decimal:2',
            'advance' => 'decimal:2',
            'long_term_loan' => 'decimal:2',
            'total_deduction' => 'decimal:2',
            'role_multiplier' => 'decimal:3',
            'attendance_factor' => 'decimal:3',
            'performance_factor' => 'decimal:3',
            'gross' => 'decimal:2',
            'employee_pension' => 'decimal:2',
            'employer_pension' => 'decimal:2',
            'taxable_income' => 'decimal:2',
            'paye' => 'decimal:2',
            'net' => 'decimal:2',
            'employer_cost' => 'decimal:2',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function run(): BelongsTo
    {
        return $this->belongsTo(PayrollRun::class, 'payroll_run_id');
    }

    /**
     * @return array<string, mixed>
     */
    public function toApiArray(): array
    {
        return $this->attributesToArray();
    }
}
