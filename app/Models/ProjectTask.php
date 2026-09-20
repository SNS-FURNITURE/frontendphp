<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectTask extends Model
{
    protected $table = 'project_tasks';

    public const UPDATED_AT = null;

    public const STATUSES = ['todo', 'in_progress', 'done'];

    protected $fillable = [
        'project_id',
        'title',
        'assigned_to',
        'status',
        'due_date',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'created_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * @return array<string, mixed>
     */
    public function toApiArray(): array
    {
        return [
            'id' => (int) $this->id,
            'project_id' => (int) $this->project_id,
            'title' => $this->title,
            'assigned_to' => $this->assigned_to,
            'status' => $this->status,
            'due_date' => $this->due_date,
            'created_at' => $this->created_at,
            'project_name' => $this->project?->name,
            'assigned_name' => $this->assignee?->full_name,
        ];
    }
}
