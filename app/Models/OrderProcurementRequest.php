<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrderProcurementRequest extends Model
{
    protected $table = 'order_procurement_requests';

    protected $fillable = [
        'order_intake_id',
        'order_material_line_id',
        'status',
        'proposed_quote_id',
        'approved_by',
        'approved_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'approved_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function intake(): BelongsTo
    {
        return $this->belongsTo(OrderIntake::class, 'order_intake_id');
    }

    public function materialLine(): BelongsTo
    {
        return $this->belongsTo(OrderMaterialLine::class, 'order_material_line_id');
    }

    public function quotes(): HasMany
    {
        return $this->hasMany(OrderSupplierQuote::class, 'order_procurement_request_id');
    }

    public function proposedQuote(): BelongsTo
    {
        return $this->belongsTo(OrderSupplierQuote::class, 'proposed_quote_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
