<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceSubmission extends Model
{
    public $timestamps = false;

    protected $table = 'attendance_submissions';

    protected $fillable = [
        'period',
        'status',
        'compiled_by',
        'approved_by',
        'summary_json',
        'payroll_run_id',
        'submitted_at',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'summary_json' => 'array',
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    public function compiler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'compiled_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function payrollRun(): BelongsTo
    {
        return $this->belongsTo(PayrollRun::class, 'payroll_run_id');
    }

    /**
     * @return array<string, mixed>
     */
    public function toApiArray(?array $payrollRun = null): array
    {
        $data = [
            'id' => (int) $this->id,
            'period' => $this->period,
            'status' => $this->status,
            'compiled_by' => $this->compiled_by,
            'approved_by' => $this->approved_by,
            'summary_json' => $this->summary_json,
            'payroll_run_id' => $this->payroll_run_id,
            'submitted_at' => $this->submitted_at,
            'reviewed_at' => $this->reviewed_at,
            'compiled_by_name' => $this->compiler?->full_name,
            'approved_by_name' => $this->approver?->full_name,
        ];

        if ($payrollRun !== null) {
            $data['payroll_run'] = $payrollRun;
        }

        return $data;
    }
}
