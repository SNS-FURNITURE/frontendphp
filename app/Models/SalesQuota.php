<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesQuota extends Model
{
    protected $table = 'sales_quotas';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'quota',
        'actual',
        'period',
    ];

    protected function casts(): array
    {
        return [
            'quota' => 'decimal:2',
            'actual' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
