<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BoardItem extends Model
{
    protected $table = 'board_items';

    public const UPDATED_AT = null;

    protected $fillable = [
        'board_id',
        'group_id',
        'title',
        'entity_type',
        'entity_id',
        'owner_user_id',
        'status',
        'due_date',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'created_at' => 'datetime',
        ];
    }

    public function board(): BelongsTo
    {
        return $this->belongsTo(Board::class, 'board_id');
    }

    public function values(): HasMany
    {
        return $this->hasMany(BoardItemValue::class, 'board_item_id');
    }
}
