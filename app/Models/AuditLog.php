<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    protected $table = 'audit_logs';

    public const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'entity_type',
        'entity_id',
        'action',
        'changes_json',
        'ip_address',
    ];

    protected function casts(): array
    {
        return [
            'changes_json' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
