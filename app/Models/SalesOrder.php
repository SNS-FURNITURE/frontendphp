<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalesOrder extends Model
{
    protected $table = 'sales_orders';

    protected $fillable = [
        'customer_id',
        'sales_rep_id',
        'status',
        'total_amount',
    ];

    protected function casts(): array
    {
        return [
            'total_amount' => 'decimal:2',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Party::class, 'customer_id');
    }

    public function salesRep(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sales_rep_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(SalesOrderLine::class, 'sales_order_id');
    }

    public function recalculateTotal(): float
    {
        $total = (float) SalesOrderLine::query()
            ->where('sales_order_id', $this->id)
            ->selectRaw('COALESCE(SUM(quantity * unit_price), 0) as grand_total')
            ->value('grand_total');

        $this->total_amount = $total;
        $this->save();

        return $total;
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'sales_order_id');
    }
}
