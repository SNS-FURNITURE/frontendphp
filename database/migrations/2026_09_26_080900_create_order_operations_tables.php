<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('order_intakes')) {
            Schema::create('order_intakes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
                $table->string('invoice_number', 64);
                $table->string('source_type', 64);
                $table->foreignId('source_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('status', 32)->default('pending');
                $table->text('rejection_reason')->nullable();
                $table->text('resubmit_comment')->nullable();
                $table->json('snapshot_json')->nullable();
                $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('reviewed_at')->nullable();
                $table->timestamp('accepted_at')->nullable();
                $table->timestamp('company_manager_notified_at')->nullable();
                $table->timestamps();

                $table->index('invoice_id');
                $table->index('invoice_number');
                $table->index('source_user_id');
                $table->index('status');
            });
        }

        if (! Schema::hasTable('order_phases')) {
            Schema::create('order_phases', function (Blueprint $table) {
                $table->id();
                $table->foreignId('order_intake_id')->constrained('order_intakes')->cascadeOnDelete();
                $table->string('phase_key', 32);
                $table->string('status', 32)->default('pending');
                $table->timestamp('due_at')->nullable();
                $table->unsignedSmallInteger('reminder_hours_before')->default(24);
                $table->timestamp('reminder_sent_at')->nullable();
                $table->timestamp('overdue_sent_at')->nullable();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();

                $table->unique(['order_intake_id', 'phase_key']);
                $table->index('status');
                $table->index('due_at');
            });
        }

        if (! Schema::hasTable('order_checkpoints')) {
            Schema::create('order_checkpoints', function (Blueprint $table) {
                $table->id();
                $table->foreignId('order_phase_id')->constrained('order_phases')->cascadeOnDelete();
                $table->string('checkpoint_key', 64);
                $table->string('label');
                $table->boolean('is_completed')->default(false);
                $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();

                $table->unique(['order_phase_id', 'checkpoint_key']);
            });
        }

        if (! Schema::hasTable('order_assignments')) {
            Schema::create('order_assignments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('order_intake_id')->constrained('order_intakes')->cascadeOnDelete();
                $table->string('role_key', 32);
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('assigned_at')->nullable();
                $table->timestamps();

                $table->index(['order_intake_id', 'role_key']);
                $table->index('user_id');
            });
        }

        if (! Schema::hasTable('order_material_lines')) {
            Schema::create('order_material_lines', function (Blueprint $table) {
                $table->id();
                $table->foreignId('order_intake_id')->constrained('order_intakes')->cascadeOnDelete();
                $table->string('item_name');
                $table->foreignId('item_id')->nullable()->constrained('items')->nullOnDelete();
                $table->decimal('quantity', 12, 2)->default(1);
                $table->string('unit', 32)->nullable();
                $table->string('stock_status', 32)->default('pending');
                $table->text('notes')->nullable();
                $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index('stock_status');
            });
        }

        if (! Schema::hasTable('order_procurement_requests')) {
            Schema::create('order_procurement_requests', function (Blueprint $table) {
                $table->id();
                $table->foreignId('order_intake_id')->constrained('order_intakes')->cascadeOnDelete();
                $table->foreignId('order_material_line_id')->nullable()->constrained('order_material_lines')->nullOnDelete();
                $table->string('status', 32)->default('open');
                $table->unsignedBigInteger('proposed_quote_id')->nullable()->index();
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('approved_at')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index('status');
            });
        }

        if (! Schema::hasTable('order_supplier_quotes')) {
            Schema::create('order_supplier_quotes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('order_procurement_request_id')->constrained('order_procurement_requests')->cascadeOnDelete();
                $table->string('supplier_name');
                $table->decimal('unit_price', 14, 2)->default(0);
                $table->decimal('total_price', 14, 2)->default(0);
                $table->string('quality_grade', 64)->nullable();
                $table->text('notes')->nullable();
                $table->boolean('is_selected')->default(false);
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('order_messages')) {
            Schema::create('order_messages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('order_intake_id')->nullable()->constrained('order_intakes')->cascadeOnDelete();
                $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
                $table->text('body');
                $table->timestamp('created_at')->useCurrent();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('order_messages');
        Schema::dropIfExists('order_supplier_quotes');
        Schema::dropIfExists('order_procurement_requests');
        Schema::dropIfExists('order_material_lines');
        Schema::dropIfExists('order_assignments');
        Schema::dropIfExists('order_checkpoints');
        Schema::dropIfExists('order_phases');
        Schema::dropIfExists('order_intakes');
    }
};
