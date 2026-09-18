<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Board extends Model
{
    protected $table = 'boards';

    public const UPDATED_AT = null;

    protected $fillable = [
        'workspace_id',
        'name',
        'board_type',
        'module_key',
        'is_active',
        'created_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class, 'workspace_id');
    }

    public function groups(): HasMany
    {
        return $this->hasMany(BoardGroup::class, 'board_id')->orderBy('sort_order');
    }

    public function columns(): HasMany
    {
        return $this->hasMany(BoardColumn::class, 'board_id')->orderBy('sort_order');
    }

    public function items(): HasMany
    {
        return $this->hasMany(BoardItem::class, 'board_id')->orderBy('sort_order');
    }
}
