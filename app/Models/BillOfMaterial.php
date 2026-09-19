<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BillOfMaterial extends Model
{
    protected $table = 'bill_of_materials';

    public const UPDATED_AT = null;

    protected $fillable = [
        'finished_item_id',
        'name',
        'version',
    ];

    protected function casts(): array
    {
        return [
            'version' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function finishedItem(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'finished_item_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(BomLine::class, 'bom_id');
    }

    /**
     * @return array<string, mixed>
     */
    public function toApiArray(): array
    {
        return [
            'id' => (int) $this->id,
            'finished_item_id' => (int) $this->finished_item_id,
            'name' => $this->name,
            'version' => (int) $this->version,
            'created_at' => $this->created_at,
            'finished_item' => $this->finishedItem ? [
                'id' => (int) $this->finishedItem->id,
                'name' => $this->finishedItem->name,
                'sku' => $this->finishedItem->sku,
            ] : null,
            'lines' => $this->lines->map(fn (BomLine $line) => $line->toApiArray())->values()->all(),
        ];
    }
}
