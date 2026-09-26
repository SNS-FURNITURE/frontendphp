<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrderPhase extends Model
{
    protected $table = 'order_phases';

    protected $fillable = [
        'order_intake_id',
        'phase_key',
        'status',
        'due_at',
        'reminder_hours_before',
        'reminder_sent_at',
        'overdue_sent_at',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'due_at' => 'datetime',
            'reminder_hours_before' => 'integer',
            'reminder_sent_at' => 'datetime',
            'overdue_sent_at' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function intake(): BelongsTo
    {
        return $this->belongsTo(OrderIntake::class, 'order_intake_id');
    }

    public function checkpoints(): HasMany
    {
        return $this->hasMany(OrderCheckpoint::class, 'order_phase_id');
    }
}
