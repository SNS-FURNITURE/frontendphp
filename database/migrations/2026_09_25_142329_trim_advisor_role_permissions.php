<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('roles') || ! Schema::hasTable('permissions') || ! Schema::hasTable('role_permissions')) {
            return;
        }

        $roleId = DB::table('roles')->where('name', 'sales_supervisor')->value('id');

        if ($roleId === null) {
            $roleId = DB::table('roles')->where('name', 'advisor')->value('id');
        }

        if ($roleId === null) {
            return;
        }

        $salesSupervisorKeys = [
            'finance.view',
            'finance.create',
            'leads.view',
            'leads.create',
            'leads.verify',
            'deals.view',
            'deals.create',
            'sales.view',
        ];

        DB::table('role_permissions')->where('role_id', $roleId)->delete();

        foreach ($salesSupervisorKeys as $key) {
            [$module, $action] = explode('.', $key, 2);
            $permissionId = DB::table('permissions')
                ->where('module_key', $module)
                ->where('action_key', $action)
                ->value('id');

            if ($permissionId === null) {
                continue;
            }

            DB::table('role_permissions')->insert([
                'role_id' => $roleId,
                'permission_id' => $permissionId,
                'allowed' => true,
            ]);
        }
    }

    public function down(): void
    {
        // Advisor permissions are managed by ErpDemoSeeder; do not restore legacy grants.
    }
};
