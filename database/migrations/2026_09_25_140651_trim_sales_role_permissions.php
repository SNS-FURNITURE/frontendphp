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

        $roleId = DB::table('roles')->where('name', 'sales')->value('id');

        if ($roleId === null) {
            return;
        }

        $salesRepKeys = ['sales.view'];

        DB::table('role_permissions')->where('role_id', $roleId)->delete();

        foreach ($salesRepKeys as $key) {
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
        // Sales permissions are managed by ErpDemoSeeder; do not restore legacy grants.
    }
};
