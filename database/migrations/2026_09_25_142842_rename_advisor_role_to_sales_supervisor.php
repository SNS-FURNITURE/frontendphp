<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('roles')) {
            DB::table('roles')
                ->where('name', 'advisor')
                ->update([
                    'name' => 'sales_supervisor',
                    'description' => 'SALES SUPERVISOR organizational role scope',
                ]);
        }

        if (Schema::hasTable('commercial_tasks')) {
            DB::table('commercial_tasks')
                ->where('assignee_role', 'advisor')
                ->update(['assignee_role' => 'sales_supervisor']);
        }

        if (Schema::hasTable('users')) {
            DB::table('users')
                ->where('email', 'advisor@sns.com')
                ->update(['full_name' => 'Alex Supervisor']);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('roles')) {
            DB::table('roles')
                ->where('name', 'sales_supervisor')
                ->update([
                    'name' => 'advisor',
                    'description' => 'ADVISOR organizational role scope',
                ]);
        }

        if (Schema::hasTable('commercial_tasks')) {
            DB::table('commercial_tasks')
                ->where('assignee_role', 'sales_supervisor')
                ->update(['assignee_role' => 'advisor']);
        }
    }
};
