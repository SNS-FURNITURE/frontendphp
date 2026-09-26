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
        $this->addSalesQuotaColumns();
        $this->ensureCommercialTasksTable();
        $this->ensureProductsTable();
        $this->addPartyIdentityColumns();
        $this->addReportColumns();
        $this->ensureReportSourcesTable();
        $this->expandMysqlEnum('deals', 'status', ['sales_approved']);
        $this->expandMysqlEnum('leads', 'status', ['rejected', 'not_interested']);
        $this->expandMysqlEnum('material_requests', 'status', ['approved']);
    }

    public function down(): void
    {
        // Additive reconcile migration — leave widened enums and backfilled keys in place.
    }

    private function addSalesQuotaColumns(): void
    {
        if (! Schema::hasTable('sales_quotas')) {
            return;
        }

        Schema::table('sales_quotas', function (Blueprint $table): void {
            if (! Schema::hasColumn('sales_quotas', 'period_type')) {
                $table->string('period_type', 20)->default('monthly')->after('period');
            }
            if (! Schema::hasColumn('sales_quotas', 'metric')) {
                $table->string('metric', 20)->default('contacts')->after('period_type');
            }
        });
    }

    private function ensureCommercialTasksTable(): void
    {
        if (Schema::hasTable('commercial_tasks')) {
            return;
        }

        Schema::create('commercial_tasks', function (Blueprint $table): void {
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

    private function ensureProductsTable(): void
    {
        if (Schema::hasTable('products')) {
            return;
        }

        Schema::create('products', function (Blueprint $table): void {
            $table->id();
            $table->string('category', 100);
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('unit', 50)->default('pieces');
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->index('category');
        });
    }

    private function addPartyIdentityColumns(): void
    {
        if (! Schema::hasTable('parties')) {
            return;
        }

        if (! Schema::hasColumn('parties', 'name_key')) {
            Schema::table('parties', function (Blueprint $table): void {
                $table->string('name_key', 255)->nullable()->after('name');
                $table->string('address_key', 255)->nullable()->after('address');
            });
        }

        DB::table('parties')
            ->orderBy('id')
            ->chunkById(200, function ($rows): void {
                foreach ($rows as $row) {
                    $isCustomer = ($row->party_type ?? null) === 'customer';

                    DB::table('parties')
                        ->where('id', $row->id)
                        ->update([
                            'name_key' => $isCustomer ? CustomerIdentity::normalize($row->name ?? '') : null,
                            'address_key' => $isCustomer ? CustomerIdentity::normalize($row->address ?? '') : null,
                        ]);
                }
            });

        $duplicateCount = (int) DB::table('parties')
            ->where('party_type', 'customer')
            ->whereNotNull('name_key')
            ->select('name_key', 'address_key', DB::raw('COUNT(*) as total'))
            ->groupBy('name_key', 'address_key')
            ->having('total', '>', 1)
            ->get()
            ->count();

        if ($duplicateCount === 0 && ! $this->indexExists('parties', 'uniq_parties_customer_identity')) {
            Schema::table('parties', function (Blueprint $table): void {
                $table->unique(['name_key', 'address_key'], 'uniq_parties_customer_identity');
            });
        }
    }

    private function addReportColumns(): void
    {
        if (! Schema::hasTable('reports')) {
            return;
        }

        Schema::table('reports', function (Blueprint $table): void {
            if (! Schema::hasColumn('reports', 'role_name')) {
                $table->string('role_name', 100)->nullable()->after('body');
            }
            if (! Schema::hasColumn('reports', 'author_id')) {
                $table->unsignedInteger('author_id')->nullable()->after('posted_by_user_id');
            }
            if (! Schema::hasColumn('reports', 'author_name')) {
                $table->string('author_name')->nullable()->after('author_id');
            }
            if (! Schema::hasColumn('reports', 'immutable')) {
                $table->boolean('immutable')->default(true)->after('is_locked');
            }
            if (! Schema::hasColumn('reports', 'sent_to_user_id')) {
                $table->unsignedInteger('sent_to_user_id')->nullable()->after('posted_at');
            }
            if (! Schema::hasColumn('reports', 'sent_to_name')) {
                $table->string('sent_to_name')->nullable()->after('sent_to_user_id');
            }
            if (! Schema::hasColumn('reports', 'delivered_at')) {
                $table->timestamp('delivered_at')->nullable()->after('sent_to_name');
            }
        });
    }

    private function ensureReportSourcesTable(): void
    {
        if (Schema::hasTable('report_sources') || ! Schema::hasTable('reports')) {
            return;
        }

        Schema::create('report_sources', function (Blueprint $table): void {
            $table->unsignedInteger('report_id');
            $table->unsignedInteger('parent_report_id');
            $table->primary(['report_id', 'parent_report_id']);
        });
    }

    /**
     * @param  list<string>  $extraValues
     */
    private function expandMysqlEnum(string $table, string $column, array $extraValues): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
            return;
        }

        $info = DB::selectOne('SHOW COLUMNS FROM `'.$table.'` WHERE Field = ?', [$column]);
        if ($info === null) {
            return;
        }

        $type = (string) ($info->Type ?? '');
        if (! str_starts_with(strtolower($type), 'enum(')) {
            return;
        }

        preg_match_all("/'((?:[^'\\\\]|\\\\.)*)'/", $type, $matches);
        $existing = $matches[1] ?? [];
        $merged = array_values(array_unique(array_merge($existing, $extraValues)));

        if ($merged === $existing) {
            return;
        }

        $enumList = implode(',', array_map(
            static fn (string $value): string => "'".str_replace("'", "''", $value)."'",
            $merged,
        ));

        $nullSql = strtoupper((string) ($info->Null ?? 'YES')) === 'NO' ? 'NOT NULL' : 'NULL';
        $default = $info->Default;
        $defaultSql = $default === null
            ? ''
            : ' DEFAULT '.DB::getPdo()->quote((string) $default);

        DB::statement("ALTER TABLE `{$table}` MODIFY COLUMN `{$column}` ENUM({$enumList}) {$nullSql}{$defaultSql}");
    }

    private function indexExists(string $table, string $indexName): bool
    {
        try {
            return Schema::hasIndex($table, $indexName);
        } catch (Throwable) {
            $connection = Schema::getConnection();
            $driver = $connection->getDriverName();

            if ($driver === 'sqlite') {
                $rows = $connection->select("PRAGMA index_list('{$table}')");
                foreach ($rows as $row) {
                    $name = is_object($row) ? ($row->name ?? null) : ($row['name'] ?? null);
                    if ($name === $indexName) {
                        return true;
                    }
                }

                return false;
            }

            $database = $connection->getDatabaseName();
            $result = $connection->select(
                'SELECT 1 FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ? LIMIT 1',
                [$database, $table, $indexName],
            );

            return $result !== [];
        }
    }
};
