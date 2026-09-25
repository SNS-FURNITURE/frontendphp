<?php

use App\Support\CustomerIdentity;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('parties')) {
            return;
        }

        if (! Schema::hasColumn('parties', 'name_key')) {
            Schema::table('parties', function (Blueprint $table) {
                $table->string('name_key', 255)->nullable()->after('name');
                $table->string('address_key', 255)->nullable()->after('address');
            });
        }

        DB::table('parties')
            ->orderBy('id')
            ->chunkById(200, function ($rows): void {
                foreach ($rows as $row) {
                    $isCustomer = $row->party_type === 'customer';

                    DB::table('parties')
                        ->where('id', $row->id)
                        ->update([
                            'name_key' => $isCustomer ? CustomerIdentity::normalize($row->name) : null,
                            'address_key' => $isCustomer ? CustomerIdentity::normalize($row->address ?? '') : null,
                        ]);
                }
            });

        $duplicateGroups = DB::table('parties')
            ->where('party_type', 'customer')
            ->select('name_key', 'address_key', DB::raw('COUNT(*) as total'))
            ->groupBy('name_key', 'address_key')
            ->having('total', '>', 1)
            ->get();

        foreach ($duplicateGroups as $group) {
            $ids = DB::table('parties')
                ->where('party_type', 'customer')
                ->where('name_key', $group->name_key)
                ->where('address_key', $group->address_key)
                ->orderBy('id')
                ->pluck('id');

            $keepId = (int) $ids->first();

            foreach ($ids->slice(1) as $duplicateId) {
                $duplicateId = (int) $duplicateId;

                if (Schema::hasTable('sales_orders')) {
                    DB::table('sales_orders')
                        ->where('customer_id', $duplicateId)
                        ->update(['customer_id' => $keepId]);
                }

                DB::table('parties')->where('id', $duplicateId)->delete();
            }
        }

        Schema::table('parties', function (Blueprint $table) {
            if (! $this->indexExists('parties', 'uniq_parties_customer_identity')) {
                $table->unique(['name_key', 'address_key'], 'uniq_parties_customer_identity');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('parties')) {
            return;
        }

        Schema::table('parties', function (Blueprint $table) {
            if ($this->indexExists('parties', 'uniq_parties_customer_identity')) {
                $table->dropUnique('uniq_parties_customer_identity');
            }

            if (Schema::hasColumn('parties', 'name_key')) {
                $table->dropColumn('name_key');
            }

            if (Schema::hasColumn('parties', 'address_key')) {
                $table->dropColumn('address_key');
            }
        });
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $connection = Schema::getConnection();
        $database = $connection->getDatabaseName();

        $result = $connection->select(
            'SELECT 1 FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ? LIMIT 1',
            [$database, $table, $indexName],
        );

        return $result !== [];
    }
};
