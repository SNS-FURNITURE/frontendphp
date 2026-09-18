<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lead extends Model
{
    protected $table = 'leads';

    public const STATUSES = [
        'pending',
        'verified',
        'rejected',
        'contacted',
        'showroom_visit',
        'not_interested',
    ];

    protected $fillable = [
        'name',
        'phone',
        'email',
        'source',
        'product_interest',
        'room_details',
        'material_preference',
        'design_source',
        'notes',
        'status',
        'created_by',
        'verified_by',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function deals(): HasMany
    {
        return $this->hasMany(Deal::class, 'lead_id');
    }

    /**
     * @return array<string, mixed>
     */
    public function toApiArray(): array
    {
        return [
            'id' => (int) $this->id,
            'name' => $this->name,
            'phone' => $this->phone,
            'email' => $this->email,
            'source' => $this->source,
            'product_interest' => $this->product_interest,
            'room_details' => $this->room_details,
            'material_preference' => $this->material_preference,
            'design_source' => $this->design_source,
            'notes' => $this->notes,
            'status' => $this->status,
            'created_by' => $this->created_by,
            'verified_by' => $this->verified_by,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
