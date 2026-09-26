<?php

namespace Database\Seeders;

use App\Support\ErpRoles;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Seeds roles, permissions, and demo users to match sns-erp-backend init.ts.
 * Safe to re-run: uses insert-or-skip by unique keys.
 */
class ErpDemoSeeder extends Seeder
{
    public function run(): void
    {
        ErpRoles::purgeRemovedRoles();

        $roles = array_merge(['admin'], ErpRoles::catalogNames());

        $roleIds = [];
        foreach ($roles as $name) {
            $existing = DB::table('roles')->where('name', $name)->first();
            if ($existing) {
                $roleIds[$name] = $existing->id;

                continue;
            }
            $roleIds[$name] = DB::table('roles')->insertGetId([
                'name' => $name,
                'description' => strtoupper($name).' organizational role scope',
                'created_at' => now(),
            ]);
        }

        $modules = [
            'admin', 'leads', 'deals', 'order_requests', 'funding', 'production',
            'inventory', 'finance', 'hr', 'designs', 'reports', 'boards',
            'deliveries', 'machinery', 'procurement', 'installation', 'sales',
            'manufacturing', 'projects', 'tasks', 'order_operations',
        ];
        $actions = ['view', 'create', 'edit', 'delete', 'approve', 'verify', 'post_report'];

        $permissionIds = [];
        foreach ($modules as $module) {
            foreach ($actions as $action) {
                $key = "{$module}.{$action}";
                $existing = DB::table('permissions')
                    ->where('module_key', $module)
                    ->where('action_key', $action)
                    ->first();
                if ($existing) {
                    $permissionIds[$key] = $existing->id;

                    continue;
                }
                $permissionIds[$key] = DB::table('permissions')->insertGetId([
                    'module_key' => $module,
                    'action_key' => $action,
                    'description' => "{$action} {$module}",
                ]);
            }
        }

        // Admin gets view on every module + admin create
        $adminPermKeys = [];
        foreach ($modules as $module) {
            $adminPermKeys[] = "{$module}.view";
        }
        $adminPermKeys[] = 'admin.create';
        $adminPermKeys[] = 'funding.approve';
        $adminPermKeys[] = 'reports.post_report';

        foreach (array_unique($adminPermKeys) as $key) {
            $this->grantRolePermission($roleIds['admin'], $key, $permissionIds);
        }

        $financeKeys = [
            'finance.view', 'finance.create', 'finance.edit', 'finance.delete', 'finance.approve',
        ];
        foreach ($financeKeys as $key) {
            $this->grantRolePermission($roleIds['finance'], $key, $permissionIds);
        }

        $marketingKeys = [
            'finance.approve',
            'leads.view', 'leads.create', 'leads.edit', 'leads.verify',
            'deals.view', 'deals.create',
            'sales.view',
            'inventory.view',
        ];
        foreach ($marketingKeys as $key) {
            $this->grantRolePermission($roleIds['marketing_manager'], $key, $permissionIds);
        }

        $salesSupervisorKeys = [
            'finance.view', 'finance.create',
            'leads.view', 'leads.create', 'leads.verify',
            'deals.view', 'deals.create',
            'sales.view',
        ];
        $this->syncRolePermissions($roleIds['sales_supervisor'], $salesSupervisorKeys, $permissionIds);

        $salesRepKeys = [
            'sales.view',
        ];
        $this->syncRolePermissions($roleIds['sales'], $salesRepKeys, $permissionIds);

        $omsKeys = [
            'finance.view',
            'order_requests.view', 'order_requests.create',
            'order_operations.view', 'order_operations.create', 'order_operations.edit', 'order_operations.approve',
            'leads.view',
            'deals.view',
            'sales.view',
            'deliveries.view', 'deliveries.create', 'deliveries.edit', 'deliveries.approve',
            'installation.view', 'installation.edit',
            'inventory.view',
            'designs.view',
            'procurement.view',
        ];
        foreach ($omsKeys as $key) {
            $this->grantRolePermission($roleIds['operations_manager_showroom'], $key, $permissionIds);
        }

        $omfKeys = [
            'production.view', 'production.create', 'production.edit',
            'manufacturing.view', 'manufacturing.create', 'manufacturing.edit',
            'inventory.view', 'inventory.create', 'inventory.edit',
            'order_requests.view',
            'order_operations.view', 'order_operations.create', 'order_operations.edit',
            'machinery.view',
            'deliveries.view',
        ];
        foreach ($omfKeys as $key) {
            $this->grantRolePermission($roleIds['operations_manager_factory'], $key, $permissionIds);
        }

        $procurementKeys = [
            'procurement.view', 'procurement.create', 'procurement.edit',
            'order_operations.view', 'order_operations.create', 'order_operations.edit',
            'inventory.view',
        ];
        foreach ($procurementKeys as $key) {
            $this->grantRolePermission($roleIds['procurement'], $key, $permissionIds);
        }

        $designerKeys = [
            'designs.view', 'designs.create', 'designs.edit',
            'order_operations.view', 'order_operations.edit',
        ];
        foreach ($designerKeys as $key) {
            $this->grantRolePermission($roleIds['designer'], $key, $permissionIds);
        }

        $assemblerKeys = [
            'order_operations.view', 'order_operations.edit',
            'production.view',
        ];
        foreach ($assemblerKeys as $key) {
            $this->grantRolePermission($roleIds['assembler'], $key, $permissionIds);
        }

        $managerViewKeys = array_map(fn (string $module) => "{$module}.view", $modules);
        $managerViewKeys[] = 'reports.post_report';
        $managerViewKeys[] = 'funding.create';
        $managerViewKeys[] = 'funding.approve';
        foreach (array_unique($managerViewKeys) as $key) {
            $this->grantRolePermission($roleIds['company_manager'], $key, $permissionIds);
        }

        $passwordHash = Hash::make('password123');
        $users = [
            ['full_name' => 'General Manager', 'email' => 'admin@sns.com', 'role' => 'admin', 'phone' => '+251911000001'],
            ['full_name' => 'Maya Manager', 'email' => 'manager@sns.com', 'role' => 'company_manager', 'phone' => '+251911000004'],
            ['full_name' => 'Hana HR & PR Officer', 'email' => 'hr@sns.com', 'role' => 'hr', 'phone' => '+251911000008'],
            ['full_name' => 'Fiona Finance', 'email' => 'finance@sns.com', 'role' => 'finance', 'phone' => '+251911000006'],
            ['full_name' => 'Marta Marketing', 'email' => 'mktmanager@sns.com', 'role' => 'marketing_manager', 'phone' => '+251911000003'],
            ['full_name' => 'Alex Supervisor', 'email' => 'advisor@sns.com', 'role' => 'sales_supervisor', 'phone' => '+251911000012'],
            ['full_name' => 'Sam Sales', 'email' => 'sales@sns.com', 'role' => 'sales', 'phone' => '+251911000013'],
            ['full_name' => 'Olivia OMS', 'email' => 'opscustomer@sns.com', 'role' => 'operations_manager_showroom', 'phone' => '+251911000014'],
            ['full_name' => 'Frank OMF', 'email' => 'opsfactory@sns.com', 'role' => 'operations_manager_factory', 'phone' => '+251911000015'],
            ['full_name' => 'Paul Procurement', 'email' => 'procurement@sns.com', 'role' => 'procurement', 'phone' => '+251911000011'],
            ['full_name' => 'Ivan Inventory', 'email' => 'inventory@sns.com', 'role' => 'inventory', 'phone' => '+251911000007'],
            ['full_name' => 'Pete Products', 'email' => 'pm@sns.com', 'role' => 'product_manager', 'phone' => '+251911000005'],
            ['full_name' => 'Daniel Designer', 'email' => 'designer@sns.com', 'role' => 'designer', 'phone' => '+251911000010'],
            ['full_name' => 'Amy Assembler', 'email' => 'assembler@sns.com', 'role' => 'assembler', 'phone' => '+251911000016'],
        ];

        foreach ($users as $u) {
            $user = DB::table('users')->where('email', $u['email'])->first();
            if (! $user) {
                $userId = DB::table('users')->insertGetId([
                    'full_name' => $u['full_name'],
                    'email' => $u['email'],
                    'phone' => $u['phone'],
                    'password_hash' => $passwordHash,
                    'status' => 'ACTIVE',
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                $userId = $user->id;
            }

            $roleId = $roleIds[$u['role']];
            $linked = DB::table('user_roles')
                ->where('user_id', $userId)
                ->where('role_id', $roleId)
                ->exists();
            if (! $linked) {
                DB::table('user_roles')->insert([
                    'user_id' => $userId,
                    'role_id' => $roleId,
                    'assigned_at' => now(),
                ]);
            }
        }

        $salesUser = DB::table('users')->where('email', 'sales@sns.com')->first();
        if ($salesUser) {
            $period = now()->format('Y-m');
            $exists = DB::table('sales_quotas')
                ->where('user_id', $salesUser->id)
                ->where('period', $period)
                ->exists();
            if (! $exists) {
                DB::table('sales_quotas')->insert([
                    'user_id' => $salesUser->id,
                    'quota' => 25,
                    'actual' => 0,
                    'period' => $period,
                    'period_type' => 'monthly',
                    'metric' => 'contacts',
                ]);
            }
        }

        $this->command?->info('ERP demo roles/users seeded (password for all: password123).');
    }

    /**
     * @param  list<string>  $keys
     * @param  array<string, int>  $permissionIds
     */
    private function syncRolePermissions(int $roleId, array $keys, array $permissionIds): void
    {
        DB::table('role_permissions')->where('role_id', $roleId)->delete();

        foreach ($keys as $key) {
            $this->grantRolePermission($roleId, $key, $permissionIds);
        }
    }

    /**
     * @param  array<string, int>  $permissionIds
     */
    private function grantRolePermission(int $roleId, string $key, array $permissionIds): void
    {
        if (! isset($permissionIds[$key])) {
            return;
        }

        $exists = DB::table('role_permissions')
            ->where('role_id', $roleId)
            ->where('permission_id', $permissionIds[$key])
            ->exists();

        if ($exists) {
            return;
        }

        DB::table('role_permissions')->insert([
            'role_id' => $roleId,
            'permission_id' => $permissionIds[$key],
            'allowed' => true,
        ]);
    }
}
