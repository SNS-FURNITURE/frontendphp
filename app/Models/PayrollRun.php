<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayrollRun extends Model
{
    protected $table = 'payroll_runs';

    public const STATUSES = ['draft', 'processed', 'paid'];

    protected $fillable = [
        'period',
        'status',
        'employee_count',
        'total_gross',
        'total_paye',
        'total_employee_pension',
        'total_employer_pension',
        'total_net',
        'total_employer_cost',
        'attendance_submission_id',
        'processed_at',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'employee_count' => 'integer',
            'total_gross' => 'decimal:2',
            'total_paye' => 'decimal:2',
            'total_employee_pension' => 'decimal:2',
            'total_employer_pension' => 'decimal:2',
            'total_net' => 'decimal:2',
            'total_employer_cost' => 'decimal:2',
            'processed_at' => 'datetime',
            'paid_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function lines(): HasMany
    {
        return $this->hasMany(PayrollLine::class, 'payroll_run_id');
    }

    /**
     * @return array<string, mixed>
     */
    public function toApiArray(bool $withLines = false): array
    {
        $data = [
            'id' => (int) $this->id,
            'period' => $this->period,
            'status' => $this->status,
            'employee_count' => (int) $this->employee_count,
            'total_gross' => $this->total_gross,
            'total_paye' => $this->total_paye,
            'total_employee_pension' => $this->total_employee_pension,
            'total_employer_pension' => $this->total_employer_pension,
            'total_net' => $this->total_net,
            'total_employer_cost' => $this->total_employer_cost,
            'attendance_submission_id' => $this->attendance_submission_id ?? null,
            'processed_at' => $this->processed_at,
            'paid_at' => $this->paid_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];

        if ($withLines) {
            $data['lines'] = $this->lines->map(fn (PayrollLine $line) => $line->toApiArray())->values()->all();
        }

        return $data;
    }
}
