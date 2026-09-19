<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Delivery extends Model
{
    protected $table = 'deliveries';

    public const STATUSES = [
        'planned',
        'ready_for_dispatch',
        'dispatched',
        'delivered',
        'cancelled',
    ];

    public const DESTINATIONS = [
        'customer',
        'showroom',
    ];

    protected $fillable = [
        'product_name',
        'quantity',
        'quantity_dispatched',
        'destination',
        'recipient_name',
        'status',
        'notes',
        'created_by',
        'dispatched_by',
        'dispatched_at',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'quantity_dispatched' => 'integer',
            'dispatched_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function dispatcher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dispatched_by');
    }

    public function outboundRecords(): HasMany
    {
        return $this->hasMany(OutboundRecord::class, 'delivery_id');
    }

    /**
     * @return array<string, mixed>
     */
    public function toApiArray(): array
    {
        return [
            'id' => (int) $this->id,
            'product_name' => $this->product_name,
            'quantity' => (int) $this->quantity,
            'quantity_dispatched' => $this->quantity_dispatched,
            'destination' => $this->destination,
            'recipient_name' => $this->recipient_name,
            'status' => $this->status,
            'notes' => $this->notes,
            'created_by' => $this->created_by,
            'dispatched_by' => $this->dispatched_by,
            'dispatched_at' => $this->dispatched_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'created_by_name' => $this->creator?->full_name,
            'dispatched_by_name' => $this->dispatcher?->full_name,
        ];
    }
}
