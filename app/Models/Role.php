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
            'marketing_manager' => 'MARKETING MANAGER',
            'sales_supervisor' => 'SALES SUPERVISOR',
            'sales' => 'SALES',
            'operations_manager_showroom' => 'OMS — SHOWROOM',
            'operations_manager_factory' => 'OMF — FACTORY',
            'operations_customer' => 'OPERATIONS CUSTOMER',
            'operations_factory' => 'OPERATIONS FACTORY',
            'procurement' => 'PROCUREMENT',
            'procurement_operations' => 'PROCUREMENT OPERATIONS',
            'assembler' => 'ASSEMBLER',
            'inventory' => 'INVENTORY',
            'product_manager' => 'PRODUCT MANAGER',
            'designer' => 'DESIGNER',
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
