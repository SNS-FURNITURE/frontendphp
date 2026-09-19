<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BoardGroup extends Model
{
    protected $table = 'board_groups';

    public const UPDATED_AT = null;

    protected $fillable = ['board_id', 'name', 'sort_order'];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public function board(): BelongsTo
    {
        return $this->belongsTo(Board::class, 'board_id');
    }
}
