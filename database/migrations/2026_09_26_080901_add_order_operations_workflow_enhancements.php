<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('order_intakes') && ! Schema::hasTable('order_intake_reviews')) {
            Schema::create('order_intake_reviews', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('order_intake_id');
                $table->string('action', 32);
                $table->string('from_status', 32)->nullable();
                $table->string('to_status', 32)->nullable();
                $table->text('comment')->nullable();
                $table->integer('actor_user_id')->nullable();
                $table->string('actor_role', 64)->nullable();
                $table->timestamps();

                $table->index(['order_intake_id', 'created_at']);
                $table->foreign('order_intake_id')->references('id')->on('order_intakes')->cascadeOnDelete();
                $table->foreign('actor_user_id')->references('id')->on('users')->nullOnDelete();
            });
        }

        if (Schema::hasTable('order_messages')) {
            Schema::table('order_messages', function (Blueprint $table) {
                if (! Schema::hasColumn('order_messages', 'is_read')) {
                    $table->boolean('is_read')->default(false)->after('body');
                }
                if (! Schema::hasColumn('order_messages', 'read_at')) {
                    $table->timestamp('read_at')->nullable()->after('is_read');
                }
            });
        }

        if (Schema::hasTable('order_supplier_quotes')) {
            Schema::table('order_supplier_quotes', function (Blueprint $table) {
                if (! Schema::hasColumn('order_supplier_quotes', 'availability')) {
                    $table->string('availability', 64)->nullable()->after('quality_grade');
                }
                if (! Schema::hasColumn('order_supplier_quotes', 'lead_time_days')) {
                    $table->unsignedInteger('lead_time_days')->nullable()->after('availability');
                }
                if (! Schema::hasColumn('order_supplier_quotes', 'delivery_terms')) {
                    $table->text('delivery_terms')->nullable()->after('lead_time_days');
                }
            });
        }

        if (Schema::hasTable('deliveries') && ! Schema::hasColumn('deliveries', 'order_intake_id')) {
            Schema::table('deliveries', function (Blueprint $table) {
                $table->unsignedInteger('order_intake_id')->nullable()->after('id');
                $table->foreign('order_intake_id')->references('id')->on('order_intakes')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('deliveries') && Schema::hasColumn('deliveries', 'order_intake_id')) {
            Schema::table('deliveries', function (Blueprint $table) {
                $table->dropForeign(['order_intake_id']);
                $table->dropColumn('order_intake_id');
            });
        }

        if (Schema::hasTable('order_supplier_quotes')) {
            Schema::table('order_supplier_quotes', function (Blueprint $table) {
                foreach (['delivery_terms', 'lead_time_days', 'availability'] as $column) {
                    if (Schema::hasColumn('order_supplier_quotes', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('order_messages')) {
            Schema::table('order_messages', function (Blueprint $table) {
                foreach (['read_at', 'is_read'] as $column) {
                    if (Schema::hasColumn('order_messages', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        Schema::dropIfExists('order_intake_reviews');
    }
};
