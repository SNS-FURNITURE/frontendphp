<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BomLine extends Model
{
    protected $table = 'bom_lines';

    public $timestamps = false;

    protected $fillable = [
        'bom_id',
        'component_item_id',
        'quantity_required',
    ];

    protected function casts(): array
    {
        return [
            'quantity_required' => 'decimal:2',
        ];
    }

    public function bom(): BelongsTo
    {
        return $this->belongsTo(BillOfMaterial::class, 'bom_id');
    }

    public function componentItem(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'component_item_id');
    }

    /**
     * @return array<string, mixed>
     */
    public function toApiArray(): array
    {
        return [
            'id' => (int) $this->id,
            'bom_id' => (int) $this->bom_id,
            'component_item_id' => (int) $this->component_item_id,
            'quantity_required' => $this->quantity_required,
            'component_item' => $this->componentItem ? [
                'id' => (int) $this->componentItem->id,
                'name' => $this->componentItem->name,
                'sku' => $this->componentItem->sku,
                'unit_of_measure' => $this->componentItem->unit_of_measure,
            ] : null,
        ];
    }
}
