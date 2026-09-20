<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BoardItemValue extends Model
{
    protected $table = 'board_item_values';

    public $timestamps = false;

    protected $fillable = [
        'board_item_id',
        'column_id',
        'value_text',
        'value_json',
    ];

    protected function casts(): array
    {
        return [
            'value_json' => 'array',
        ];
    }
}
