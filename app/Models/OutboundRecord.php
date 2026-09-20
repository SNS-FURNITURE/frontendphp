<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OutboundRecord extends Model
{
    protected $table = 'outbound_records';

    public const UPDATED_AT = null;

    protected $fillable = [
        'delivery_id',
        'product_name',
        'quantity_out',
        'destination',
        'recipient_name',
        'counted_by',
        'counted_at',
        'reference',
    ];

    protected function casts(): array
    {
        return [
            'quantity_out' => 'integer',
            'counted_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    public function delivery(): BelongsTo
    {
        return $this->belongsTo(Delivery::class, 'delivery_id');
    }

    public function counter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'counted_by');
    }

    /**
     * @return array<string, mixed>
     */
    public function toApiArray(): array
    {
        return [
            'id' => (int) $this->id,
            'delivery_id' => (int) $this->delivery_id,
            'product_name' => $this->product_name,
            'quantity_out' => (int) $this->quantity_out,
            'destination' => $this->destination,
            'recipient_name' => $this->recipient_name,
            'counted_by' => $this->counted_by,
            'counted_at' => $this->counted_at,
            'reference' => $this->reference,
            'created_at' => $this->created_at,
            'counted_by_name' => $this->counter?->full_name,
        ];
    }
}
