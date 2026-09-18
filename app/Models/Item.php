<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    protected $table = 'items';

    public const UPDATED_AT = null;

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
}
