<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FundingRequest extends Model
{
    protected $table = 'funding_requests';

    public const STATUSES = [
        'draft',
        'submitted',
        'approved',
        'rejected',
        'received',
        'routed',
    ];

    public const ALLOCATION_STATUSES = [
        'approved',
        'received',
        'routed',
    ];

    protected $fillable = [
        'title',
        'amount',
        'purpose',
        'status',
        'requested_by',
        'approved_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * @return array<string, mixed>
     */
    public function toApiArray(): array
    {
        return [
            'id' => (int) $this->id,
            'title' => $this->title,
            'amount' => $this->amount,
            'purpose' => $this->purpose,
            'status' => $this->status,
            'requested_by' => $this->requested_by,
            'approved_by' => $this->approved_by,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'requested_by_name' => $this->requester?->full_name,
            'approved_by_name' => $this->approver?->full_name,
        ];
    }
}
