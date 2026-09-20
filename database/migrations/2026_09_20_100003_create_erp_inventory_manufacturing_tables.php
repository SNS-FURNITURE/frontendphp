<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->string('sku', 100)->unique();
            $table->string('name');
            $table->string('item_type', 50);
            $table->string('unit_of_measure', 50)->default('pcs');
            $table->decimal('reorder_level', 12, 2)->default(0);
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('stock_levels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $table->string('warehouse', 100)->default('Main Warehouse');
            $table->decimal('quantity_on_hand', 12, 2)->default(0);
            $table->timestamp('updated_at')->useCurrent();
            $table->unique(['item_id', 'warehouse']);
        });

        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $table->string('movement_type', 50);
            $table->decimal('quantity', 12, 2);
            $table->string('reference_type', 100)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
            $table->index('created_at', 'idx_stock_movements_created');
        });

        Schema::create('bill_of_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('finished_item_id')->constrained('items')->cascadeOnDelete();
            $table->string('name');
            $table->integer('version')->default(1);
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('bom_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bom_id')->constrained('bill_of_materials')->cascadeOnDelete();
            $table->foreignId('component_item_id')->constrained('items')->cascadeOnDelete();
            $table->decimal('quantity_required', 12, 2);
        });

        Schema::create('machinery', function (Blueprint $table) {
            $table->id();
            $table->string('machine_code', 100)->unique();
            $table->string('name');
            $table->string('category', 100)->default('woodwork');
            $table->string('status', 50)->default('operational');
            $table->string('workshop_location', 100)->nullable();
            $table->integer('existing_qty')->default(1);
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('material_requests', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->foreignId('item_id')->nullable()->constrained('items')->nullOnDelete();
            $table->string('item_name');
            $table->decimal('quantity', 12, 2);
            $table->string('status', 50)->default('pending');
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('fulfilled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('proof_note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('material_requests');
        Schema::dropIfExists('machinery');
        Schema::dropIfExists('bom_lines');
        Schema::dropIfExists('bill_of_materials');
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('stock_levels');
        Schema::dropIfExists('items');
    }
};
