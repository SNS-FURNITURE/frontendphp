<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Employee extends Model
{
    protected $table = 'employees';

    protected $fillable = [
        'party_id',
        'user_id',
        'employee_no',
        'employee_number',
        'national_id_number',
        'employee_account',
        'department',
        'job_title',
        'hire_date',
        'termination_date',
        'employment_status',
        'hr_manager_user_id',
        'emergency_contact_name',
        'emergency_contact_relationship',
        'emergency_contact_phone',
        'photo_url',
        'id_image_url',
        'cv_url',
        'bank_name',
        'bank_account_number',
        'monthly_salary',
    ];

    protected function casts(): array
    {
        return [
            'hire_date' => 'date',
            'termination_date' => 'date',
            'monthly_salary' => 'decimal:2',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function party(): BelongsTo
    {
        return $this->belongsTo(Party::class, 'party_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function attendance(): HasMany
    {
        return $this->hasMany(Attendance::class, 'employee_id');
    }

    public function scopeActive($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('employment_status')
                ->orWhere('employment_status', '<>', 'TERMINATED');
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function toListArray(): array
    {
        $party = $this->party;

        return [
            'id' => (int) $this->id,
            'party_id' => (int) $this->party_id,
            'user_id' => $this->user_id,
            'job_title' => $this->job_title,
            'department' => $this->department,
            'hire_date' => $this->hire_date?->format('Y-m-d'),
            'party' => [
                'id' => (int) $this->party_id,
                'name' => $party?->name,
                'email' => $party?->email,
                'phone' => $party?->phone,
                'approval_status' => $party?->approval_status,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toDetailArray(): array
    {
        $party = $this->party;

        return [
            'id' => (int) $this->id,
            'party_id' => (int) $this->party_id,
            'user_id' => $this->user_id,
            'employee_number' => $this->employee_no ?: ($this->employee_number ?: 'EMP-'.$this->id),
            'national_id_number' => $this->national_id_number,
            'employee_account' => $this->employee_account,
            'job_title' => $this->job_title,
            'department' => $this->department,
            'hire_date' => $this->hire_date?->format('Y-m-d'),
            'date_of_birth' => null,
            'gender' => null,
            'city' => 'Addis Ababa',
            'country' => 'Ethiopia',
            'address' => $party?->address,
            'employment_type' => 'Full-Time',
            'employment_status' => $this->employment_status ?: 'ACTIVE',
            'emergency_contact_name' => $this->emergency_contact_name,
            'emergency_contact_relationship' => $this->emergency_contact_relationship,
            'emergency_contact_phone' => $this->emergency_contact_phone,
            'photo_url' => $this->resolvePublicUrl($this->photo_url),
            'id_image_url' => $this->resolvePublicUrl($this->id_image_url),
            'cv_url' => $this->resolvePublicUrl($this->cv_url),
            'bank_name' => $this->bank_name ?: 'Commercial Bank of Ethiopia',
            'bank_account_number' => $this->bank_account_number,
            'monthly_salary' => $this->monthly_salary !== null ? (float) $this->monthly_salary : null,
            'party' => [
                'id' => (int) $this->party_id,
                'name' => $party?->name,
                'email' => $party?->email,
                'phone' => $party?->phone,
                'approval_status' => $party?->approval_status,
            ],
        ];
    }

    public function resolvePublicUrl(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        if (preg_match('#^https?://#i', $path) === 1) {
            return $path;
        }

        if (str_starts_with($path, '/storage/') || str_starts_with($path, 'storage/')) {
            return asset(ltrim($path, '/'));
        }

        return asset('storage/'.ltrim($path, '/'));
    }
}
