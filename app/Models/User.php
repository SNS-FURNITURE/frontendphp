<?php

namespace App\Models;

use App\Support\OrderOperations;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Route;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory;

    use Notifiable;

    protected ?array $cachedPermissionPairs = null;

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
        $aliases = OrderOperations::roleAliases($roleName);

        return $this->roles->contains(
            fn (Role $role) => in_array(strtolower($role->name), array_map('strtolower', $aliases), true)
        );
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    public function isOms(): bool
    {
        return $this->hasRole(OrderOperations::ROLE_OMS);
    }

    public function isOmf(): bool
    {
        return $this->hasRole(OrderOperations::ROLE_OMF);
    }

    public function isAssembler(): bool
    {
        return $this->hasRole(OrderOperations::ROLE_ASSEMBLER);
    }

    public function isDesigner(): bool
    {
        return $this->hasRole(OrderOperations::ROLE_DESIGNER);
    }

    public function isProductManager(): bool
    {
        return $this->hasRole(OrderOperations::ROLE_PRODUCT_MANAGER);
    }

    public function isProcurement(): bool
    {
        return $this->hasRole(OrderOperations::ROLE_PROCUREMENT);
    }

    public function canViewOrderOperations(): bool
    {
        return $this->hasPermission('order_operations', 'view')
            || $this->isOms()
            || $this->isOmf()
            || $this->isAssembler()
            || $this->isDesigner()
            || $this->isProductManager()
            || $this->isProcurement()
            || $this->hasRole('company_manager')
            || $this->isAdmin()
            || $this->isSalesSupervisor()
            || $this->isMarketingManager();
    }

    public function canReviewOrderIntake(): bool
    {
        return $this->isOms() || $this->hasPermission('order_operations', 'approve');
    }

    public function canManageOrderSchedule(): bool
    {
        return $this->isOms();
    }

    public function canAssignDesigner(): bool
    {
        return $this->isOms();
    }

    public function canAssignProductManager(): bool
    {
        return $this->isOms();
    }

    public function canAssignAssembler(): bool
    {
        return $this->isOmf();
    }

    public function canSuperviseProductManager(): bool
    {
        return $this->isOmf() || $this->isAdmin();
    }

    public function canMonitorProductionDelivery(): bool
    {
        return $this->isOms() || $this->isOmf() || $this->isAdmin() || $this->hasRole('company_manager');
    }

    public function canMessageOpsPeer(): bool
    {
        return $this->isOms() || $this->isOmf() || $this->isAdmin();
    }

    public function canApproveOrderProcurement(): bool
    {
        return $this->hasRole('company_manager') || $this->hasPermission('order_operations', 'approve');
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
    public function isSalesRep(): bool
    {
        return $this->hasRole('sales');
    }

    public function isSalesSupervisor(): bool
    {
        return $this->hasRole('sales_supervisor');
    }

    /** Sales rep and supervisor — showroom floor roles with a trimmed workspace. */
    public function isLimitedSalesRole(): bool
    {
        return $this->isSalesRep() || $this->isSalesSupervisor();
    }

    public function isMarketingManager(): bool
    {
        return $this->hasRole('marketing_manager');
    }

    public function canCreateCustomerContact(): bool
    {
        return $this->hasInvoiceLaunchRole() && $this->isSalesRep();
    }

    public function canViewSales(): bool
    {
        if (! $this->hasInvoiceLaunchRole()) {
            return false;
        }

        return $this->isAdmin()
            || $this->hasRole('company_manager')
            || $this->hasRole('marketing_manager')
            || $this->isSalesSupervisor()
            || $this->isSalesRep()
            || $this->hasRole('operations_manager_showroom');
    }

    public function canCreateSales(): bool
    {
        if ($this->isAdmin()
            || $this->hasRole('marketing_manager')
            || $this->isSalesRep()
            || $this->hasRole('operations_manager_showroom')
            || $this->hasRole('operations_manager_factory')) {
            return false;
        }

        return $this->canViewSales();
    }

    public function canManageContactQuotas(): bool
    {
        return $this->hasRole('marketing_manager');
    }

    public function canViewProducts(): bool
    {
        return $this->isMarketingManager() || $this->isAdmin();
    }

    public function canManageProducts(): bool
    {
        return $this->isMarketingManager() || $this->isAdmin();
    }

    public function canReviewCustomerContacts(): bool
    {
        return $this->isSalesSupervisor();
    }

    public function canViewCommercialReports(): bool
    {
        return $this->isAdmin()
            || $this->hasRole('marketing_manager')
            || $this->hasRole('company_manager');
    }

    public function canManageCommercialTasks(): bool
    {
        return $this->hasRole('marketing_manager');
    }

    public function canViewCommercialTasks(): bool
    {
        return $this->hasRole('marketing_manager')
            || $this->isSalesRep()
            || $this->isSalesSupervisor();
    }

    public function canViewInvoicePrices(): bool
    {
        return $this->isAdmin() || $this->hasRole('marketing_manager') || $this->isSalesSupervisor();
    }

    public function canEditInvoicePrices(): bool
    {
        return $this->isAdmin() || $this->hasRole('marketing_manager');
    }

    /** Express party approve: sales floor + company manager. */
    public function canApproveParty(): bool
    {
        return $this->hasRole('company_manager')
            || $this->isSalesSupervisor();
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

    public function canViewPayments(): bool
    {
        if ($this->isMarketingManager() || $this->isLimitedSalesRole()) {
            return false;
        }

        return $this->hasInvoiceLaunchRole() && $this->hasPermission('finance', 'view');
    }

    /** Allocations read-only: finance:view (Express RoleGuard). */
    public function canViewAllocations(): bool
    {
        if ($this->isMarketingManager() || $this->isLimitedSalesRole()) {
            return false;
        }

        return $this->hasInvoiceLaunchRole() && $this->hasPermission('finance', 'view');
    }

    /** Inventory boards: Express API is auth+launch; UI uses inventory:*. */
    public function canViewInventory(): bool
    {
        if ($this->isLimitedSalesRole() || ! $this->hasInvoiceLaunchRole() || $this->isOms() || $this->isOmf()) {
            return false;
        }

        return $this->hasPermission('inventory', 'view')
            || $this->isAdmin()
            || $this->hasRole('company_manager')
            || $this->hasRole('finance')
            || $this->isMarketingManager();
    }

    public function canCreateInventory(): bool
    {
        if (! $this->canViewInventory() || $this->isAdmin() || $this->isOms() || $this->isOmf()) {
            return false;
        }

        return $this->hasPermission('inventory', 'create')
            || $this->hasRole('company_manager');
    }

    /** Production / manufacturing boards: production:* or inventory create roles. */
    public function canViewProduction(): bool
    {
        if ($this->isLimitedSalesRole() || ! $this->hasInvoiceLaunchRole() || $this->isMarketingManager() || $this->isAdmin()) {
            return false;
        }

        return $this->hasPermission('production', 'view')
            || $this->hasPermission('manufacturing', 'view')
            || $this->hasRole('operations_manager_factory')
            || $this->canViewInventory();
    }

    public function canCreateProduction(): bool
    {
        if (! $this->canViewProduction() || $this->isAdmin()) {
            return false;
        }

        return $this->hasPermission('production', 'create')
            || $this->hasPermission('manufacturing', 'create')
            || $this->hasRole('operations_manager_factory')
            || $this->canCreateInventory();
    }

    /** Deliveries / outbound: deliveries:*. */
    public function canViewDeliveries(): bool
    {
        if ($this->isLimitedSalesRole() || ! $this->hasInvoiceLaunchRole()) {
            return false;
        }

        return $this->hasPermission('deliveries', 'view')
            || $this->isAdmin()
            || $this->hasRole('company_manager')
            || $this->hasRole('finance')
            || $this->hasRole('operations_manager_showroom')
            || $this->hasRole('operations_manager_factory');
    }

    public function canCreateDeliveries(): bool
    {
        if (! $this->canViewDeliveries() || $this->isAdmin()) {
            return false;
        }

        return $this->hasPermission('deliveries', 'create')
            || $this->hasRole('company_manager')
            || $this->hasRole('operations_manager_showroom');
    }

    /** Inventory outbound count / dispatch — deliveries:approve. PM cannot dispatch. */
    public function canDispatchDeliveries(): bool
    {
        if (! $this->canViewDeliveries() || $this->isAdmin()) {
            return false;
        }

        return $this->hasPermission('deliveries', 'approve')
            || $this->hasPermission('deliveries', 'edit')
            || $this->hasRole('company_manager')
            || $this->hasRole('finance')
            || $this->hasRole('operations_manager_showroom');
    }

    public function canViewDesigns(): bool
    {
        return $this->hasInvoiceLaunchRole()
            && ($this->hasPermission('designs', 'view')
                || $this->isAdmin()
                || $this->hasRole('company_manager')
                || $this->isDesigner()
                || $this->isOms());
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
            && ($this->hasPermission('machinery', 'view')
                || $this->isAdmin()
                || $this->hasRole('company_manager')
                || $this->hasRole('operations_manager_factory'));
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
            && ($this->hasPermission('procurement', 'view')
                || $this->isAdmin()
                || $this->hasRole('company_manager')
                || $this->isProcurement()
                || $this->isOms());
    }

    public function canCreateProcurement(): bool
    {
        return $this->canViewProcurement() && ! $this->isAdmin()
            && ($this->hasPermission('procurement', 'create') || $this->hasRole('procurement'));
    }

    public function canApproveProcurement(): bool
    {
        return $this->hasPermission('procurement', 'approve') || $this->hasRole('company_manager');
    }

    public function canEditProcurement(): bool
    {
        return $this->hasPermission('procurement', 'edit')
            || $this->hasRole('company_manager')
            || $this->hasRole('procurement');
    }

    public function canViewInstallation(): bool
    {
        return $this->hasInvoiceLaunchRole()
            && ($this->hasPermission('installation', 'view')
                || $this->canViewProcurement()
                || $this->hasRole('procurement')
                || $this->hasRole('operations_manager_showroom'));
    }

    public function canEditInstallation(): bool
    {
        return $this->hasPermission('installation', 'edit')
            || $this->hasRole('procurement')
            || $this->hasRole('company_manager')
            || $this->hasRole('operations_manager_showroom');
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
        if ($this->isMarketingManager()) {
            return false;
        }

        return $this->hasInvoiceLaunchRole()
            && ($this->hasPermission('reports', 'post_report')
                || $this->isAdmin()
                || $this->hasRole('company_manager'));
    }

    /** HR boards: Express RoleGuard hr:view. */
    public function canViewHr(): bool
    {
        if (! $this->hasInvoiceLaunchRole()) {
            return false;
        }

        return $this->hasPermission('hr', 'view')
            || $this->isAdmin()
            || $this->hasRole('company_manager')
            || $this->hasRole('finance')
            || $this->hasRole('hr');
    }

    public function canEditHr(): bool
    {
        if (! $this->canViewHr() || $this->isAdmin()) {
            return false;
        }

        return $this->hasPermission('hr', 'edit')
            || $this->hasPermission('hr', 'create')
            || $this->hasRole('hr')
            || $this->hasRole('company_manager');
    }

    public function canApproveHr(): bool
    {
        return $this->hasPermission('hr', 'approve')
            || $this->hasRole('company_manager')
            || $this->hasRole('hr');
    }

    public function canViewPayroll(): bool
    {
        if ($this->isMarketingManager() || $this->isLimitedSalesRole()) {
            return false;
        }

        return $this->hasInvoiceLaunchRole()
            && ($this->hasPermission('finance', 'view')
                || $this->isAdmin()
                || $this->hasRole('finance')
                || $this->hasRole('company_manager'));
    }

    public function canEditPayroll(): bool
    {
        if (! $this->canViewPayroll() || $this->isAdmin()) {
            return false;
        }

        return $this->hasPermission('finance', 'edit')
            || $this->hasPermission('finance', 'create')
            || $this->hasRole('finance');
    }

    /** Final payroll decision after finance generates and submits the run. */
    public function canFinalizePayroll(): bool
    {
        return $this->hasRole('company_manager') || $this->isAdmin();
    }

    public function canViewLeads(): bool
    {
        if (! $this->hasInvoiceLaunchRole() || $this->isSalesRep()) {
            return false;
        }

        return $this->hasPermission('leads', 'view')
            || $this->isAdmin()
            || $this->canViewSales();
    }

    public function canCreateLeads(): bool
    {
        if (! $this->canViewLeads() || $this->isAdmin()) {
            return false;
        }

        return $this->hasPermission('leads', 'create') || $this->canCreateSales();
    }

    public function canVerifyLeads(): bool
    {
        return $this->isAdmin()
            || $this->hasPermission('leads', 'verify')
            || $this->hasRole('company_manager')
            || $this->hasRole('marketing_manager')
            || $this->isSalesSupervisor();
    }

    public function canViewDeals(): bool
    {
        if (! $this->hasInvoiceLaunchRole() || $this->isSalesRep()) {
            return false;
        }

        return $this->hasPermission('deals', 'view')
            || $this->isAdmin()
            || $this->canViewSales();
    }

    public function canCreateDeals(): bool
    {
        if (! $this->canViewDeals() || $this->isAdmin()) {
            return false;
        }

        return $this->hasPermission('deals', 'create') || $this->canCreateSales();
    }

    public function canSalesReviewDeal(): bool
    {
        return $this->hasRole('admin')
            || $this->isSalesSupervisor();
    }

    public function canManagerReviewDeal(): bool
    {
        return $this->hasRole('company_manager');
    }

    public function canViewAllReports(): bool
    {
        if ($this->isMarketingManager()) {
            return false;
        }

        return $this->isAdmin()
            || $this->hasRole('company_manager');
    }

    public function canViewBoards(): bool
    {
        if ($this->isMarketingManager()) {
            return false;
        }

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

    /**
     * First workspace screen after login — avoid dumping every role on invoices.
     */
    public function preferredHomeRouteName(): string
    {
        if ($this->isOms() && Route::has('operations.oms.dashboard')) {
            return 'operations.oms.dashboard';
        }
        if ($this->isOmf() && Route::has('operations.omf.dashboard')) {
            return 'operations.omf.dashboard';
        }
        if ($this->isDesigner() && Route::has('operations.designer.dashboard')) {
            return 'operations.designer.dashboard';
        }
        if ($this->isProductManager() && Route::has('operations.product-manager.dashboard')) {
            return 'operations.product-manager.dashboard';
        }
        if ($this->isProcurement() && Route::has('operations.procurement.dashboard')) {
            return 'operations.procurement.dashboard';
        }
        if ($this->hasRole('company_manager') && Route::has('operations.manager.dashboard')) {
            return 'operations.manager.dashboard';
        }
        if ($this->isAssembler() && Route::has('operations.omf.dashboard')) {
            return 'operations.omf.dashboard';
        }
        if ($this->isSalesRep() && Route::has('sales.dashboard')) {
            return 'sales.dashboard';
        }
        if (($this->isMarketingManager() || $this->isAdmin()) && Route::has('commercial.reports.index')) {
            return 'commercial.reports.index';
        }
        if ($this->isSalesSupervisor() && Route::has('sales.customers')) {
            return 'sales.customers';
        }
        if ($this->hasPermission('finance', 'view') || ($this->canViewSales() && ! $this->isSalesRep())) {
            return 'invoices.index';
        }
        if ($this->canViewHr() && Route::has('hr.employees')) {
            return 'hr.employees';
        }
        if ($this->canViewLeads() && Route::has('leads.index')) {
            return 'leads.index';
        }

        if ($this->canViewInventory() && Route::has('inventory.items')) {
            return 'inventory.items';
        }
        if ($this->canViewProduction() && Route::has('production.index')) {
            return 'production.index';
        }
        if ($this->canViewProjects() && Route::has('projects.index')) {
            return 'projects.index';
        }
        if ($this->canViewBoards() && Route::has('boards.index')) {
            return 'boards.index';
        }
        if ($this->canViewProcurement() && Route::has('procurement.index')) {
            return 'procurement.index';
        }

        return 'profile.edit';
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
        if ($this->cachedPermissionPairs !== null) {
            return $this->cachedPermissionPairs;
        }

        $rows = [];
        $seen = [];

        $roles = $this->roles->loadMissing('permissions');
        $isSalesFloor = $this->isSalesSupervisor();

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

        // Sales floor roles receive finance view/create grants when missing from role_permissions.
        if ($isSalesFloor) {
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

        // Admin is observer for create/edit, but may approve invoices.
        if ($this->isAdmin()) {
            foreach (['view', 'approve'] as $action) {
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

        // Marketing manager reviews issued orders and leads; no order creation.
        if ($this->isMarketingManager()) {
            $marketingGrants = [
                ['module' => 'finance', 'actions' => ['approve']],
                ['module' => 'leads', 'actions' => ['view', 'create', 'edit', 'verify']],
                ['module' => 'deals', 'actions' => ['view', 'create']],
                ['module' => 'sales', 'actions' => ['view']],
                ['module' => 'inventory', 'actions' => ['view']],
            ];

            foreach ($marketingGrants as $grant) {
                foreach ($grant['actions'] as $action) {
                    $key = $grant['module'].':'.$action;
                    if (isset($seen[$key])) {
                        continue;
                    }
                    $seen[$key] = true;
                    $perm = Permission::query()
                        ->where('module_key', $grant['module'])
                        ->where('action_key', $action)
                        ->first();
                    $rows[] = [
                        'id' => (int) ($perm?->id ?? 0),
                        'role_id' => 0,
                        'module' => $grant['module'],
                        'action' => $action,
                    ];
                }
            }
        }

        return $this->cachedPermissionPairs = $rows;
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
