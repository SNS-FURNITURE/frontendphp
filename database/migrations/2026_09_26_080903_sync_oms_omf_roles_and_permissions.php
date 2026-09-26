<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * @var array<string, string>
     */
    private array $roleMap = [
        'operations_customer' => 'operations_manager_showroom',
        'operations_factory' => 'operations_manager_factory',
        'procurement_operations' => 'procurement',
    ];

    public function up(): void
    {
        if (! Schema::hasTable('roles')) {
            return;
        }

        foreach ([
            'operations_manager_showroom' => 'Operation Manager Showroom (OMS) organizational role scope',
            'operations_manager_factory' => 'Operation Manager Factory (OMF) organizational role scope',
            'assembler' => 'Assembler organizational role scope',
            'procurement' => 'Procurement organizational role scope',
            'designer' => 'Designer organizational role scope',
        ] as $name => $description) {
            $exists = DB::table('roles')->where('name', $name)->exists();
            if (! $exists) {
                DB::table('roles')->insert([
                    'name' => $name,
                    'description' => $description,
                    'created_at' => now(),
                ]);
            }
        }

        foreach ($this->roleMap as $from => $to) {
            $fromRole = DB::table('roles')->where('name', $from)->first();
            $toRole = DB::table('roles')->where('name', $to)->first();
            if (! $fromRole || ! $toRole) {
                continue;
            }

            $assignments = DB::table('user_roles')->where('role_id', $fromRole->id)->get();
            foreach ($assignments as $assignment) {
                $already = DB::table('user_roles')
                    ->where('user_id', $assignment->user_id)
                    ->where('role_id', $toRole->id)
                    ->exists();

                if ($already) {
                    DB::table('user_roles')
                        ->where('user_id', $assignment->user_id)
                        ->where('role_id', $fromRole->id)
                        ->delete();
                } else {
                    DB::table('user_roles')
                        ->where('user_id', $assignment->user_id)
                        ->where('role_id', $fromRole->id)
                        ->update(['role_id' => $toRole->id]);
                }
            }

            $perms = DB::table('role_permissions')->where('role_id', $fromRole->id)->get();
            foreach ($perms as $perm) {
                $exists = DB::table('role_permissions')
                    ->where('role_id', $toRole->id)
                    ->where('permission_id', $perm->permission_id)
                    ->exists();
                if (! $exists) {
                    DB::table('role_permissions')->insert([
                        'role_id' => $toRole->id,
                        'permission_id' => $perm->permission_id,
                        'allowed' => $perm->allowed,
                    ]);
                }
            }

            DB::table('role_permissions')->where('role_id', $fromRole->id)->delete();
            DB::table('roles')->where('id', $fromRole->id)->delete();
        }

        if (! Schema::hasTable('permissions')) {
            return;
        }

        $actions = ['view', 'create', 'edit', 'delete', 'approve'];
        $permissionIds = [];
        foreach ($actions as $action) {
            $existing = DB::table('permissions')
                ->where('module_key', 'order_operations')
                ->where('action_key', $action)
                ->first();
            if ($existing) {
                $permissionIds[$action] = $existing->id;
            } else {
                $permissionIds[$action] = DB::table('permissions')->insertGetId([
                    'module_key' => 'order_operations',
                    'action_key' => $action,
                    'description' => "{$action} order_operations",
                ]);
            }
        }

        $grants = [
            'operations_manager_showroom' => ['view', 'create', 'edit', 'approve'],
            'operations_manager_factory' => ['view', 'create', 'edit'],
            'company_manager' => ['view', 'approve'],
            'designer' => ['view', 'edit'],
            'procurement' => ['view', 'create', 'edit'],
            'assembler' => ['view', 'edit'],
            'admin' => ['view'],
            'sales_supervisor' => ['view'],
            'marketing_manager' => ['view'],
        ];

        foreach ($grants as $roleName => $actionsForRole) {
            $roleId = DB::table('roles')->where('name', $roleName)->value('id');
            if (! $roleId) {
                continue;
            }
            foreach ($actionsForRole as $action) {
                $permissionId = $permissionIds[$action] ?? null;
                if (! $permissionId) {
                    continue;
                }
                $exists = DB::table('role_permissions')
                    ->where('role_id', $roleId)
                    ->where('permission_id', $permissionId)
                    ->exists();
                if (! $exists) {
                    DB::table('role_permissions')->insert([
                        'role_id' => $roleId,
                        'permission_id' => $permissionId,
                        'allowed' => true,
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        // Irreversible data migration — roles are remapped forward only.
    }
};
