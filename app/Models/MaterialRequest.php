<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Schema;

class MaterialRequest extends Model
{
    protected $table = 'material_requests';

    public const STATUSES = [
        'pending',
        'approved',
        'rejected',
        'fulfilled',
    ];

    protected $fillable = [
        'title',
        'item_id',
        'item_name',
        'quantity',
        'status',
        'requested_by',
        'fulfilled_by',
        'proof_note',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function fulfiller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'fulfilled_by');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'item_id');
    }

    /**
     * @return array<string, mixed>
     */
    public function toApiArray(): array
    {
        $data = [
            'id' => (int) $this->id,
            'title' => $this->title,
            'item_name' => $this->item_name,
            'quantity' => (int) $this->quantity,
            'status' => $this->status,
            'requested_by' => $this->requested_by,
            'fulfilled_by' => $this->fulfilled_by,
            'proof_note' => $this->proof_note,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];

        if (Schema::hasColumn('material_requests', 'item_id')) {
            $data['item_id'] = $this->item_id;
        }

        return $data;
    }
}
