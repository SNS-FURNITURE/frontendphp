<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('order_material_usage_logs');

        Schema::create('order_material_usage_logs', function (Blueprint $table) {
            // Live MySQL order_* / users ids are INT UNSIGNED (not BIGINT), so avoid foreignId().
            $table->increments('id');
            $table->unsignedInteger('order_intake_id');
            $table->unsignedInteger('order_material_line_id')->nullable();
            $table->unsignedInteger('invoice_id')->nullable();
            $table->string('invoice_number', 64)->nullable();
            $table->unsignedInteger('item_id')->nullable();
            $table->string('item_name');
            $table->decimal('quantity', 12, 2);
            $table->string('unit', 32)->nullable();
            $table->unsignedInteger('source_user_id')->nullable();
            $table->unsignedInteger('released_by')->nullable();
            $table->timestamp('used_at');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('used_at');
            $table->index('invoice_number');
            $table->index(['item_name', 'used_at']);
            $table->index(['source_user_id', 'used_at']);
            $table->index('order_intake_id');
            $table->index('order_material_line_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_material_usage_logs');
    }
};
