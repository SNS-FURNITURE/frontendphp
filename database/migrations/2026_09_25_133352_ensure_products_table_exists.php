<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('products')) {
            Schema::create('products', function (Blueprint $table) {
                $table->id();
                $table->string('category', 100);
                $table->string('name');
                $table->text('description')->nullable();
                $table->string('unit', 50)->default('pieces');
                $table->decimal('unit_price', 12, 2)->default(0);
                $table->index('category');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
