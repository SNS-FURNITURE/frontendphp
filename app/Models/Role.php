<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    protected $table = 'roles';

    public const UPDATED_AT = null;

    protected $fillable = [
        'name',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function getFormattedNameAttribute(): string
    {
        $map = [
            'admin' => 'ADMIN',
            'company_manager' => 'COMPANY MANAGER',
            'hr' => 'HUMAN RESOURCE',
            'finance' => 'FINANCE',
            'procurement_operations' => 'PROCUREMENT OPERATIONS',
            'inventory' => 'INVENTORY',
            'product_manager' => 'PRODUCT MANAGER',
            'designer' => 'DESIGNER',
            'sales' => 'SALES',
            'advisor' => 'SALES ADVISOR',
            'marketing_manager' => 'MARKETING MANAGER',
        ];

        $name = (string) $this->name;
        
        return $map[$name] ?? strtoupper(str_replace('_', ' ', $name));
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_roles', 'role_id', 'user_id')
            ->withPivot('assigned_at');
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_permissions', 'role_id', 'permission_id')
            ->withPivot(['id', 'allowed']);
    }
}
