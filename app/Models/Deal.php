<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Deal extends Model
{
    protected $table = 'deals';

    protected $fillable = [
        'title',
        'customer_name',
        'status',
        'owner_id',
        'lead_id',
        'product_category',
        'deal_value',
        'notes',
        'sales_reviewed_by',
        'sales_reviewed_at',
        'manager_reviewed_by',
        'manager_reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'deal_value' => 'decimal:2',
            'sales_reviewed_at' => 'datetime',
            'manager_reviewed_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class, 'lead_id');
    }

    /**
     * @return array<string, mixed>
     */
    public function toApiArray(): array
    {
        return [
            'id' => (int) $this->id,
            'title' => $this->title,
            'customer_name' => $this->customer_name,
            'status' => $this->status,
            'owner_id' => $this->owner_id,
            'lead_id' => $this->lead_id,
            'product_category' => $this->product_category,
            'deal_value' => $this->deal_value,
            'notes' => $this->notes,
            'sales_reviewed_by' => $this->sales_reviewed_by ?? null,
            'sales_reviewed_at' => $this->sales_reviewed_at ?? null,
            'manager_reviewed_by' => $this->manager_reviewed_by ?? null,
            'manager_reviewed_at' => $this->manager_reviewed_at ?? null,
            'owner_name' => $this->owner?->full_name,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
