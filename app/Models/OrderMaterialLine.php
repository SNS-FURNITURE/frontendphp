<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrderMaterialLine extends Model
{
    protected $table = 'order_material_lines';

    protected $fillable = [
        'order_intake_id',
        'item_name',
        'item_id',
        'quantity',
        'unit',
        'stock_status',
        'notes',
        'submitted_by',
        'reviewed_by',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function intake(): BelongsTo
    {
        return $this->belongsTo(OrderIntake::class, 'order_intake_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'item_id');
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function procurementRequests(): HasMany
    {
        return $this->hasMany(OrderProcurementRequest::class, 'order_material_line_id');
    }
}
