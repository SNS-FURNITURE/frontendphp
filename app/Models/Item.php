<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Item extends Model
{
    protected $table = 'items';

    public const UPDATED_AT = null;

    public const TYPES = [
        'raw_material',
        'finished_good',
        'component',
    ];

    protected $fillable = [
        'sku',
        'name',
        'item_type',
        'unit_of_measure',
        'reorder_level',
    ];

    protected function casts(): array
    {
        return [
            'reorder_level' => 'decimal:2',
            'created_at' => 'datetime',
        ];
    }

    public function stockLevels(): HasMany
    {
        return $this->hasMany(StockLevel::class, 'item_id');
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'item_id');
    }
}
