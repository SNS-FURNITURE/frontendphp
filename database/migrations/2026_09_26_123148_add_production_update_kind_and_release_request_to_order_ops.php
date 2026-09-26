<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('order_messages') && ! Schema::hasColumn('order_messages', 'message_kind')) {
            Schema::table('order_messages', function (Blueprint $table): void {
                $table->string('message_kind', 32)->default('ops')->after('body');
            });
        }

        if (Schema::hasTable('order_intakes')) {
            Schema::table('order_intakes', function (Blueprint $table): void {
                if (! Schema::hasColumn('order_intakes', 'materials_release_requested_at')) {
                    $table->timestamp('materials_release_requested_at')->nullable()->after('cm_overdue_sent_at');
                }
                if (! Schema::hasColumn('order_intakes', 'materials_release_requested_by')) {
                    $table->unsignedInteger('materials_release_requested_by')->nullable()->after('materials_release_requested_at');
                }
                if (! Schema::hasColumn('order_intakes', 'materials_release_note')) {
                    $table->text('materials_release_note')->nullable()->after('materials_release_requested_by');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('order_messages') && Schema::hasColumn('order_messages', 'message_kind')) {
            Schema::table('order_messages', function (Blueprint $table): void {
                $table->dropColumn('message_kind');
            });
        }

        if (Schema::hasTable('order_intakes')) {
            Schema::table('order_intakes', function (Blueprint $table): void {
                foreach (['materials_release_note', 'materials_release_requested_by', 'materials_release_requested_at'] as $column) {
                    if (Schema::hasColumn('order_intakes', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
