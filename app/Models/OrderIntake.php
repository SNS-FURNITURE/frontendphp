<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrderIntake extends Model
{
    protected $table = 'order_intakes';

    protected $fillable = [
        'invoice_id',
        'invoice_number',
        'source_type',
        'source_user_id',
        'status',
        'rejection_reason',
        'resubmit_comment',
        'snapshot_json',
        'issued_snapshot_json',
        'approved_snapshot_json',
        'reviewed_by',
        'reviewed_at',
        'accepted_at',
        'company_manager_notified_at',
        'cm_due_at',
        'cm_reminder_hours_before',
        'cm_reminder_sent_at',
        'cm_overdue_sent_at',
    ];

    protected function casts(): array
    {
        return [
            'snapshot_json' => 'array',
            'issued_snapshot_json' => 'array',
            'approved_snapshot_json' => 'array',
            'reviewed_at' => 'datetime',
            'accepted_at' => 'datetime',
            'company_manager_notified_at' => 'datetime',
            'cm_due_at' => 'datetime',
            'cm_reminder_sent_at' => 'datetime',
            'cm_overdue_sent_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }

    public function sourceUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'source_user_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function phases(): HasMany
    {
        return $this->hasMany(OrderPhase::class, 'order_intake_id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(OrderAssignment::class, 'order_intake_id');
    }

    public function materialLines(): HasMany
    {
        return $this->hasMany(OrderMaterialLine::class, 'order_intake_id');
    }

    public function procurementRequests(): HasMany
    {
        return $this->hasMany(OrderProcurementRequest::class, 'order_intake_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(OrderMessage::class, 'order_intake_id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(OrderIntakeReview::class, 'order_intake_id');
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(Delivery::class, 'order_intake_id');
    }

    public function materialUsageLogs(): HasMany
    {
        return $this->hasMany(OrderMaterialUsageLog::class, 'order_intake_id');
    }

    public function phase(string $phaseKey): ?OrderPhase
    {
        return $this->phases->firstWhere('phase_key', $phaseKey);
    }
}
