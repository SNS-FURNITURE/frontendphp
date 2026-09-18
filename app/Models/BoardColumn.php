<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BoardColumn extends Model
{
    protected $table = 'board_columns';

    public const UPDATED_AT = null;

    protected $fillable = [
        'board_id',
        'name',
        'key_name',
        'column_type',
        'settings_json',
        'is_required',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'settings_json' => 'array',
            'is_required' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    public function board(): BelongsTo
    {
        return $this->belongsTo(Board::class, 'board_id');
    }
}
