<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    protected $table = 'users';

    protected $fillable = [
        'full_name',
        'username',
        'email',
        'phone',
        'password_hash',
        'status',
        'is_active',
        'last_login_at',
    ];

    protected $hidden = [
        'password_hash',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function getAuthPassword(): string
    {
        return (string) $this->password_hash;
    }

    public function getAuthIdentifierName(): string
    {
        return 'id';
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_roles', 'user_id', 'role_id')
            ->withPivot('assigned_at');
    }

    public function hasRole(string $roleName): bool
    {
        return $this->roles->contains(
            fn (Role $role) => strtolower($role->name) === strtolower($roleName)
        );
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    public function hasInvoiceLaunchRole(): bool
    {
        if (! config('erp.invoice_launch_enabled', true)) {
            return true;
        }

        $allowed = array_map('strtolower', config('invoice-launch.roles', []));

        return $this->roles->contains(
            fn (Role $role) => in_array(strtolower($role->name), $allowed, true)
        );
    }

    public function hasPermission(string $module, string $action): bool
    {
        foreach ($this->permissionPairs() as $pair) {
            if ($pair['module'] === $module && $pair['action'] === $action) {
                return true;
            }
        }

        return false;
    }

    /**
     * Same shape as Express loadUserRolesAndPermissions permissions array.
     *
     * @return array<int, array{id:int, role_id:int, module:string, action:string}>
     */
    public function permissionPairs(): array
    {
        $rows = [];
        $seen = [];

        $roles = $this->roles->loadMissing('permissions');
        // Phase 1: advisor / sales_supervisor are supervisor-equivalent (Express rename).
        $isSupervisorFamily = $this->hasRole('advisor')
            || $this->hasRole('supervisor')
            || $this->hasRole('sales_supervisor');

        if ($isSupervisorFamily && ! $this->hasRole('supervisor')) {
            $supervisor = Role::query()->with('permissions')->where('name', 'supervisor')->first();
            if ($supervisor) {
                $roles = $roles->concat([$supervisor]);
            }
        }

        foreach ($roles as $role) {
            foreach ($role->permissions as $permission) {
                if (! (bool) ($permission->pivot->allowed ?? true)) {
                    continue;
                }
                $key = $permission->module_key.':'.$permission->action_key;
                if (isset($seen[$key])) {
                    continue;
                }
                $seen[$key] = true;
                $rows[] = [
                    'id' => (int) ($permission->pivot->id ?? 0),
                    'role_id' => (int) $role->id,
                    'module' => (string) $permission->module_key,
                    'action' => (string) $permission->action_key,
                ];
            }
        }

        // Local DBs may still have sales_supervisor without finance grants; match Express supervisor map.
        if ($isSupervisorFamily) {
            foreach (['view', 'create'] as $action) {
                $key = 'finance:'.$action;
                if (isset($seen[$key])) {
                    continue;
                }
                $seen[$key] = true;
                $perm = Permission::query()
                    ->where('module_key', 'finance')
                    ->where('action_key', $action)
                    ->first();
                $rows[] = [
                    'id' => (int) ($perm?->id ?? 0),
                    'role_id' => 0,
                    'module' => 'finance',
                    'action' => $action,
                ];
            }
        }

        // Admin is observer: can view finance surfaces without create/edit grants.
        if ($this->isAdmin() && ! isset($seen['finance:view'])) {
            $seen['finance:view'] = true;
            $perm = Permission::query()
                ->where('module_key', 'finance')
                ->where('action_key', 'view')
                ->first();
            $rows[] = [
                'id' => (int) ($perm?->id ?? 0),
                'role_id' => 0,
                'module' => 'finance',
                'action' => 'view',
            ];
        }

        return $rows;
    }

    /**
     * @return array{id:int, username:?string, full_name:string, email:string, phone:?string, is_active:bool}
     */
    public function toAuthUserArray(): array
    {
        return [
            'id' => (int) $this->id,
            'username' => $this->getAttribute('username'),
            'full_name' => (string) $this->full_name,
            'email' => (string) $this->email,
            'phone' => $this->phone,
            'is_active' => (bool) $this->is_active,
        ];
    }

    /**
     * @return array<int, array{id:int, name:string}>
     */
    public function rolesArray(): array
    {
        return $this->roles->map(fn (Role $role) => [
            'id' => (int) $role->id,
            'name' => (string) $role->name,
        ])->values()->all();
    }
}
