<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExternalLaborer extends Model
{
    protected $table = 'external_laborers';

    public const STATUSES = ['free', 'assigned', 'unavailable'];

    protected $fillable = [
        'full_name',
        'phone',
        'national_id',
        'specialty_skills',
        'experience_years',
        'daily_rate',
        'status',
        'notes',
        'created_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'experience_years' => 'integer',
            'daily_rate' => 'decimal:2',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /**
     * @return array<string, mixed>
     */
    public function toApiArray(): array
    {
        return [
            'id' => (int) $this->id,
            'full_name' => $this->full_name,
            'phone' => $this->phone,
            'national_id' => $this->national_id,
            'specialty_skills' => $this->specialty_skills,
            'experience_years' => (int) $this->experience_years,
            'daily_rate' => $this->daily_rate,
            'status' => $this->status,
            'notes' => $this->notes,
            'created_by_user_id' => $this->created_by_user_id,
            'created_by_name' => $this->createdBy?->full_name,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
