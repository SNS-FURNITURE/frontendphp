<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Schema;

class Report extends Model
{
    protected $table = 'reports';

    public const UPDATED_AT = null;

    public const CREATED_AT = 'posted_at';

    protected $fillable = [
        'report_type',
        'title',
        'body',
        'role_name',
        'posted_by_user_id',
        'author_id',
        'author_name',
        'role_id',
        'sent_to_user_id',
        'sent_to_name',
        'delivered_at',
        'entity_type',
        'entity_id',
        'is_locked',
        'immutable',
    ];

    protected function casts(): array
    {
        return [
            'is_locked' => 'boolean',
            'immutable' => 'boolean',
            'posted_at' => 'datetime',
            'delivered_at' => 'datetime',
        ];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by_user_id');
    }

    /**
     * @param  array<string, mixed>  $extras
     * @return array<string, mixed>
     */
    public function toApiArray(array $extras = []): array
    {
        return array_merge([
            'id' => (int) $this->id,
            'title' => $this->title,
            'body' => $this->body,
            'role_name' => $this->getAttribute('role_name') ?: $this->report_type ?: '',
            'author_id' => $this->getAttribute('author_id') ?: $this->posted_by_user_id,
            'author_name' => $this->getAttribute('author_name') ?: $this->author?->full_name ?: '',
            'posted_by_user_id' => $this->posted_by_user_id,
            'sent_to_user_id' => Schema::hasColumn($this->getTable(), 'sent_to_user_id')
                ? $this->getAttribute('sent_to_user_id')
                : null,
            'sent_to_name' => Schema::hasColumn($this->getTable(), 'sent_to_name')
                ? $this->getAttribute('sent_to_name')
                : null,
            'delivered_at' => Schema::hasColumn($this->getTable(), 'delivered_at')
                ? $this->getAttribute('delivered_at')
                : $this->posted_at,
            'entity_type' => $this->entity_type,
            'entity_id' => $this->entity_id,
            'posted_at' => $this->posted_at,
            'immutable' => true,
        ], $extras);
    }
}
