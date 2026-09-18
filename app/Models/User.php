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

    /**
     * Launch roles that may open sales boards (customers, orders, quota).
     * Admin is observer (view); finance is launch-allowed but not sales.
     */
    public function canViewSales(): bool
    {
        if (! $this->hasInvoiceLaunchRole()) {
            return false;
        }

        return $this->isAdmin()
            || $this->hasRole('company_manager')
            || $this->hasRole('marketing_manager')
            || $this->hasRole('advisor')
            || $this->hasRole('supervisor')
            || $this->hasRole('sales_supervisor');
    }

    public function canCreateSales(): bool
    {
        return $this->canViewSales() && ! $this->isAdmin();
    }

    /** Express party approve: supervisor, sales_supervisor, company_manager (+ advisor alias). */
    public function canApproveParty(): bool
    {
        return $this->hasRole('company_manager')
            || $this->hasRole('supervisor')
            || $this->hasRole('sales_supervisor')
            || $this->hasRole('advisor');
    }

    /** Funding board: company manager requests; finance/admin can observe. */
    public function canViewFunding(): bool
    {
        if (! $this->hasInvoiceLaunchRole()) {
            return false;
        }

        return $this->hasPermission('funding', 'view')
            || $this->isAdmin()
            || $this->hasRole('company_manager')
            || $this->hasRole('finance');
    }

    public function canCreateFunding(): bool
    {
        if (! $this->canViewFunding() || $this->isAdmin()) {
            return false;
        }

        return $this->hasPermission('funding', 'create')
            || $this->hasRole('company_manager');
    }

    public function canApproveFunding(): bool
    {
        return $this->hasPermission('funding', 'approve')
            || $this->hasRole('finance');
    }

    public function canEditFunding(): bool
    {
        return $this->hasPermission('funding', 'edit')
            || $this->hasRole('company_manager');
    }

    /** Allocations read-only: finance:view (Express RoleGuard). */
    public function canViewAllocations(): bool
    {
        return $this->hasInvoiceLaunchRole() && $this->hasPermission('finance', 'view');
    }

    /** Inventory boards: Express API is auth+launch; UI uses inventory:*. */
    public function canViewInventory(): bool
    {
        if (! $this->hasInvoiceLaunchRole()) {
            return false;
        }

        return $this->hasPermission('inventory', 'view')
            || $this->isAdmin()
            || $this->hasRole('company_manager')
            || $this->hasRole('finance')
            || $this->hasRole('marketing_manager')
            || $this->hasRole('advisor')
            || $this->hasRole('supervisor')
            || $this->hasRole('sales_supervisor');
    }

    public function canCreateInventory(): bool
    {
        if (! $this->canViewInventory() || $this->isAdmin()) {
            return false;
        }

        return $this->hasPermission('inventory', 'create')
            || $this->hasRole('company_manager')
            || $this->hasRole('marketing_manager')
            || $this->hasRole('advisor')
            || $this->hasRole('supervisor')
            || $this->hasRole('sales_supervisor');
    }

    /** Production / manufacturing boards: production:* or inventory create roles. */
    public function canViewProduction(): bool
    {
        if (! $this->hasInvoiceLaunchRole()) {
            return false;
        }

        return $this->hasPermission('production', 'view')
            || $this->hasPermission('manufacturing', 'view')
            || $this->canViewInventory();
    }

    public function canCreateProduction(): bool
    {
        if (! $this->canViewProduction() || $this->isAdmin()) {
            return false;
        }

        return $this->hasPermission('production', 'create')
            || $this->hasPermission('manufacturing', 'create')
            || $this->canCreateInventory();
    }

    /** Deliveries / outbound: deliveries:*. */
    public function canViewDeliveries(): bool
    {
        if (! $this->hasInvoiceLaunchRole()) {
            return false;
        }

        return $this->hasPermission('deliveries', 'view')
            || $this->isAdmin()
            || $this->hasRole('company_manager')
            || $this->hasRole('finance')
            || $this->hasRole('marketing_manager')
            || $this->hasRole('advisor')
            || $this->hasRole('supervisor')
            || $this->hasRole('sales_supervisor');
    }

    public function canCreateDeliveries(): bool
    {
        if (! $this->canViewDeliveries() || $this->isAdmin()) {
            return false;
        }

        return $this->hasPermission('deliveries', 'create')
            || $this->hasRole('company_manager')
            || $this->hasRole('marketing_manager')
            || $this->hasRole('advisor')
            || $this->hasRole('supervisor')
            || $this->hasRole('sales_supervisor');
    }

    /** Inventory outbound count / dispatch — deliveries:approve. PM cannot dispatch. */
    public function canDispatchDeliveries(): bool
    {
        if (! $this->canViewDeliveries() || $this->isAdmin()) {
            return false;
        }

        if ($this->hasRole('project_manager')) {
            return false;
        }

        return $this->hasPermission('deliveries', 'approve')
            || $this->hasPermission('deliveries', 'edit')
            || $this->hasRole('company_manager')
            || $this->hasRole('finance')
            || $this->hasRole('supervisor')
            || $this->hasRole('sales_supervisor');
    }

    public function canViewDesigns(): bool
    {
        return $this->hasInvoiceLaunchRole()
            && ($this->hasPermission('designs', 'view') || $this->isAdmin() || $this->hasRole('company_manager'));
    }

    public function canCreateDesigns(): bool
    {
        return $this->canViewDesigns() && ! $this->isAdmin()
            && ($this->hasPermission('designs', 'create') || $this->hasRole('designer'));
    }

    public function canEditDesigns(): bool
    {
        return $this->canViewDesigns() && ! $this->isAdmin()
            && ($this->hasPermission('designs', 'edit') || $this->hasRole('designer'));
    }

    public function canViewMachinery(): bool
    {
        return $this->hasInvoiceLaunchRole()
            && ($this->hasPermission('machinery', 'view') || $this->isAdmin() || $this->hasRole('company_manager'));
    }

    public function canCreateMachinery(): bool
    {
        return $this->canViewMachinery() && ! $this->isAdmin()
            && ($this->hasPermission('machinery', 'create') || $this->hasRole('product_manager'));
    }

    public function canEditMachinery(): bool
    {
        return $this->canViewMachinery() && ! $this->isAdmin()
            && ($this->hasPermission('machinery', 'edit')
                || $this->hasRole('product_manager')
                || $this->hasRole('inventory'));
    }

    public function canViewProcurement(): bool
    {
        return $this->hasInvoiceLaunchRole()
            && ($this->hasPermission('procurement', 'view') || $this->isAdmin() || $this->hasRole('company_manager'));
    }

    public function canCreateProcurement(): bool
    {
        return $this->canViewProcurement() && ! $this->isAdmin()
            && ($this->hasPermission('procurement', 'create') || $this->hasRole('procurement_operations'));
    }

    public function canApproveProcurement(): bool
    {
        return $this->hasPermission('procurement', 'approve') || $this->hasRole('company_manager');
    }

    public function canEditProcurement(): bool
    {
        return $this->hasPermission('procurement', 'edit')
            || $this->hasRole('company_manager')
            || $this->hasRole('procurement_operations');
    }

    public function canViewInstallation(): bool
    {
        return $this->hasInvoiceLaunchRole()
            && ($this->hasPermission('installation', 'view')
                || $this->canViewProcurement()
                || $this->hasRole('procurement_operations'));
    }

    public function canEditInstallation(): bool
    {
        return $this->hasPermission('installation', 'edit')
            || $this->hasRole('procurement_operations')
            || $this->hasRole('company_manager');
    }

    public function canViewProjects(): bool
    {
        return $this->hasInvoiceLaunchRole()
            && ($this->hasPermission('projects', 'view')
                || $this->hasPermission('production', 'view')
                || $this->isAdmin()
                || $this->hasRole('company_manager')
                || $this->hasRole('product_manager'));
    }

    public function canViewTasks(): bool
    {
        return $this->hasInvoiceLaunchRole()
            && ($this->hasPermission('tasks', 'view') || $this->isAdmin() || $this->hasRole('company_manager'));
    }

    public function canEditTasks(): bool
    {
        return $this->hasPermission('tasks', 'edit');
    }

    public function canPostReport(): bool
    {
        return $this->hasInvoiceLaunchRole()
            && ($this->hasPermission('reports', 'post_report') || $this->isAdmin() || $this->hasRole('company_manager'));
    }

    public function canViewAllReports(): bool
    {
        return $this->isAdmin() || $this->hasRole('company_manager');
    }

    public function canViewBoards(): bool
    {
        return $this->hasInvoiceLaunchRole()
            && ($this->hasPermission('boards', 'view')
                || $this->hasPermission('reports', 'post_report')
                || $this->isAdmin()
                || $this->hasRole('company_manager'));
    }

    public function canCreateBoards(): bool
    {
        return $this->canViewBoards() && ! $this->isAdmin()
            && ($this->hasPermission('boards', 'create') || $this->hasRole('company_manager'));
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
