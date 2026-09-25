<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('sales_quotas')) {
            Schema::table('sales_quotas', function (Blueprint $table) {
                if (! Schema::hasColumn('sales_quotas', 'period_type')) {
                    $table->string('period_type', 20)->default('monthly')->after('period');
                }
                if (! Schema::hasColumn('sales_quotas', 'metric')) {
                    $table->string('metric', 20)->default('contacts')->after('period_type');
                }
            });
        }

        if (! Schema::hasTable('commercial_tasks')) {
            Schema::create('commercial_tasks', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->text('notes')->nullable();
                $table->unsignedInteger('assigned_to_user_id');
                $table->unsignedInteger('assigned_by_user_id');
                $table->string('assignee_role', 50);
                $table->string('status', 30)->default('todo');
                $table->date('due_date')->nullable();
                $table->timestamps();

                $table->index('assigned_to_user_id');
                $table->index('assigned_by_user_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('commercial_tasks');

        if (Schema::hasTable('sales_quotas')) {
            Schema::table('sales_quotas', function (Blueprint $table) {
                if (Schema::hasColumn('sales_quotas', 'metric')) {
                    $table->dropColumn('metric');
                }
                if (Schema::hasColumn('sales_quotas', 'period_type')) {
                    $table->dropColumn('period_type');
                }
            });
        }
    }
};
