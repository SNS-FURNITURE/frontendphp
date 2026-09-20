<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductionOrder extends Model
{
    protected $table = 'production_orders';

    public const UPDATED_AT = null;

    public const STATUSES = [
        'planned',
        'in_progress',
        'completed',
        'cancelled',
    ];

    protected $fillable = [
        'sales_order_id',
        'bom_id',
        'quantity',
        'status',
        'assigned_to',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    public function bom(): BelongsTo
    {
        return $this->belongsTo(BillOfMaterial::class, 'bom_id');
    }

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class, 'sales_order_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * @return array<string, mixed>
     */
    public function toApiArray(): array
    {
        return [
            'id' => (int) $this->id,
            'sales_order_id' => $this->sales_order_id,
            'bom_id' => (int) $this->bom_id,
            'quantity' => $this->quantity,
            'status' => $this->status,
            'assigned_to' => $this->assigned_to,
            'started_at' => $this->started_at,
            'completed_at' => $this->completed_at,
            'created_at' => $this->created_at,
            'bom' => $this->bom ? [
                'id' => (int) $this->bom->id,
                'name' => $this->bom->name,
                'finished_item_id' => (int) $this->bom->finished_item_id,
            ] : null,
            'sales_order' => $this->sales_order_id && $this->salesOrder ? [
                'id' => (int) $this->salesOrder->id,
                'total_amount' => $this->salesOrder->total_amount,
            ] : null,
        ];
    }
}
