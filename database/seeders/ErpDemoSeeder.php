<?php

namespace Database\Seeders;

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
        $roles = [
            'admin',
            'company_manager',
            'hr',
            'finance',
            'sales_lead_gen',
            'sales_supervisor',
            'procurement_operations',
            'inventory',
            'product_manager',
            'designer',
        ];

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
            'manufacturing', 'projects', 'tasks',
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
            if (! isset($permissionIds[$key])) {
                continue;
            }
            $exists = DB::table('role_permissions')
                ->where('role_id', $roleIds['admin'])
                ->where('permission_id', $permissionIds[$key])
                ->exists();
            if (! $exists) {
                DB::table('role_permissions')->insert([
                    'role_id' => $roleIds['admin'],
                    'permission_id' => $permissionIds[$key],
                    'allowed' => true,
                ]);
            }
        }

        $passwordHash = Hash::make('password123');
        $users = [
            ['full_name' => 'General Manager (Admin)', 'email' => 'admin@sns.com', 'role' => 'admin', 'phone' => '+251911000001'],
            ['full_name' => 'Maya Manager (Operations)', 'email' => 'manager@sns.com', 'role' => 'company_manager', 'phone' => '+251911000004'],
            ['full_name' => 'Hana HR & PR Officer', 'email' => 'hr@sns.com', 'role' => 'hr', 'phone' => '+251911000008'],
            ['full_name' => 'Fiona Finance', 'email' => 'finance@sns.com', 'role' => 'finance', 'phone' => '+251911000006'],
            ['full_name' => 'Lina Lead Gen', 'email' => 'sales@sns.com', 'role' => 'sales_lead_gen', 'phone' => '+251911000002'],
            ['full_name' => 'Sam Supervisor', 'email' => 'advisor@sns.com', 'role' => 'sales_supervisor', 'phone' => '+251911000003'],
            ['full_name' => 'Paul Procurement', 'email' => 'procurement@sns.com', 'role' => 'procurement_operations', 'phone' => '+251911000011'],
            ['full_name' => 'Ivan Inventory', 'email' => 'inventory@sns.com', 'role' => 'inventory', 'phone' => '+251911000007'],
            ['full_name' => 'Pete Products', 'email' => 'pm@sns.com', 'role' => 'product_manager', 'phone' => '+251911000005'],
            ['full_name' => 'Daniel Designer', 'email' => 'designer@sns.com', 'role' => 'designer', 'phone' => '+251911000010'],
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

        $this->command?->info('ERP demo roles/users seeded (password for all: password123).');
    }
}
