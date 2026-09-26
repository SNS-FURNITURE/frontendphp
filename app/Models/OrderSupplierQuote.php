<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderSupplierQuote extends Model
{
    protected $table = 'order_supplier_quotes';

    protected $fillable = [
        'order_procurement_request_id',
        'supplier_name',
        'unit_price',
        'total_price',
        'quality_grade',
        'availability',
        'lead_time_days',
        'delivery_terms',
        'notes',
        'is_selected',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:2',
            'total_price' => 'decimal:2',
            'lead_time_days' => 'integer',
            'is_selected' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function procurementRequest(): BelongsTo
    {
        return $this->belongsTo(OrderProcurementRequest::class, 'order_procurement_request_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
