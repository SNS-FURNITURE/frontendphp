<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('order_intakes')) {
            return;
        }

        Schema::table('order_intakes', function (Blueprint $table): void {
            if (! Schema::hasColumn('order_intakes', 'cm_due_at')) {
                $table->timestamp('cm_due_at')->nullable()->after('company_manager_notified_at');
            }
            if (! Schema::hasColumn('order_intakes', 'cm_reminder_hours_before')) {
                $table->unsignedInteger('cm_reminder_hours_before')->nullable()->after('cm_due_at');
            }
            if (! Schema::hasColumn('order_intakes', 'cm_reminder_sent_at')) {
                $table->timestamp('cm_reminder_sent_at')->nullable()->after('cm_reminder_hours_before');
            }
            if (! Schema::hasColumn('order_intakes', 'cm_overdue_sent_at')) {
                $table->timestamp('cm_overdue_sent_at')->nullable()->after('cm_reminder_sent_at');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('order_intakes')) {
            return;
        }

        Schema::table('order_intakes', function (Blueprint $table): void {
            foreach (['cm_overdue_sent_at', 'cm_reminder_sent_at', 'cm_reminder_hours_before', 'cm_due_at'] as $column) {
                if (Schema::hasColumn('order_intakes', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
