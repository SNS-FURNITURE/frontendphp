<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    protected $table = 'projects';

    public const UPDATED_AT = null;

    public const STATUSES = ['planned', 'active', 'on_hold', 'completed'];

    protected $fillable = [
        'name',
        'pm_user_id',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function pm(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pm_user_id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(ProjectTask::class, 'project_id');
    }
}
