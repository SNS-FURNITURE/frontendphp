<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    public $timestamps = false;

    protected $table = 'attendance';

    protected $fillable = [
        'employee_id',
        'date',
        'session',
        'check_in',
        'check_out',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    /**
     * @return array<string, mixed>
     */
    public function toApiArray(): array
    {
        $employee = $this->employee;
        $party = $employee?->party;

        return [
            'id' => (int) $this->id,
            'employee_id' => (int) $this->employee_id,
            'date' => $this->date?->format('Y-m-d'),
            'session' => $this->session,
            'check_in' => $this->check_in,
            'check_out' => $this->check_out,
            'status' => $this->status,
            'employee' => [
                'id' => (int) $this->employee_id,
                'job_title' => $employee?->job_title,
                'party' => [
                    'name' => $party?->name,
                ],
            ],
        ];
    }
}
