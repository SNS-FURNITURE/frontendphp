<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('order_intakes')) {
            Schema::table('order_intakes', function (Blueprint $table): void {
                if (! Schema::hasColumn('order_intakes', 'issued_snapshot_json')) {
                    $table->json('issued_snapshot_json')->nullable()->after('snapshot_json');
                }
                if (! Schema::hasColumn('order_intakes', 'approved_snapshot_json')) {
                    $table->json('approved_snapshot_json')->nullable()->after('issued_snapshot_json');
                }
            });

            if (Schema::hasColumn('order_intakes', 'issued_snapshot_json')) {
                DB::table('order_intakes')
                    ->where('source_type', 'sales_supervisor_issue')
                    ->whereNull('issued_snapshot_json')
                    ->whereNotNull('snapshot_json')
                    ->orderBy('id')
                    ->chunkById(100, function ($rows): void {
                        foreach ($rows as $row) {
                            DB::table('order_intakes')->where('id', $row->id)->update([
                                'issued_snapshot_json' => $row->snapshot_json,
                            ]);
                        }
                    });

                DB::table('order_intakes')
                    ->where('source_type', 'approved_invoice')
                    ->whereNull('approved_snapshot_json')
                    ->whereNotNull('snapshot_json')
                    ->orderBy('id')
                    ->chunkById(100, function ($rows): void {
                        foreach ($rows as $row) {
                            DB::table('order_intakes')->where('id', $row->id)->update([
                                'approved_snapshot_json' => $row->snapshot_json,
                            ]);
                        }
                    });
            }
        }

        if (Schema::hasTable('order_phases')) {
            Schema::table('order_phases', function (Blueprint $table): void {
                if (! Schema::hasColumn('order_phases', 'label')) {
                    $table->string('label')->nullable()->after('phase_key');
                }
                if (! Schema::hasColumn('order_phases', 'sort_order')) {
                    $table->unsignedInteger('sort_order')->default(0)->after('label');
                }
            });

            if (Schema::hasColumn('order_phases', 'label')) {
                $defaults = [
                    'design' => 'Designing',
                    'factory_coloring' => 'Factory coloring',
                    'materials' => 'Materials',
                    'assembly' => 'Assembly',
                    'delivery' => 'Delivery',
                ];
                foreach ($defaults as $key => $label) {
                    DB::table('order_phases')->where('phase_key', $key)->whereNull('label')->update(['label' => $label]);
                }
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('order_phases')) {
            Schema::table('order_phases', function (Blueprint $table): void {
                if (Schema::hasColumn('order_phases', 'sort_order')) {
                    $table->dropColumn('sort_order');
                }
                if (Schema::hasColumn('order_phases', 'label')) {
                    $table->dropColumn('label');
                }
            });
        }

        if (Schema::hasTable('order_intakes')) {
            Schema::table('order_intakes', function (Blueprint $table): void {
                if (Schema::hasColumn('order_intakes', 'approved_snapshot_json')) {
                    $table->dropColumn('approved_snapshot_json');
                }
                if (Schema::hasColumn('order_intakes', 'issued_snapshot_json')) {
                    $table->dropColumn('issued_snapshot_json');
                }
            });
        }
    }
};
