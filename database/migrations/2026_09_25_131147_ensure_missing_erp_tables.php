<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('material_requests')) {
            Schema::create('material_requests', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->unsignedBigInteger('item_id')->nullable();
                $table->string('item_name');
                $table->decimal('quantity', 12, 2);
                $table->string('status', 50)->default('pending');
                $table->unsignedInteger('requested_by')->nullable();
                $table->unsignedInteger('fulfilled_by')->nullable();
                $table->text('proof_note')->nullable();
                $table->timestamps();

                $table->index('item_id');
                $table->index('requested_by');
                $table->index('fulfilled_by');
            });
        }

        if (! Schema::hasTable('deals')) {
            Schema::create('deals', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->string('customer_name');
                $table->string('status', 50)->default('in_progress');
                $table->unsignedInteger('owner_id')->nullable();
                $table->unsignedBigInteger('lead_id')->nullable();
                $table->string('product_category', 100)->nullable();
                $table->decimal('deal_value', 12, 2)->default(0);
                $table->text('notes')->nullable();
                $table->unsignedInteger('sales_reviewed_by')->nullable();
                $table->timestamp('sales_reviewed_at')->nullable();
                $table->unsignedInteger('manager_reviewed_by')->nullable();
                $table->timestamp('manager_reviewed_at')->nullable();
                $table->timestamps();

                $table->index('owner_id');
                $table->index('lead_id');
                $table->index('status', 'idx_deals_status');
            });
        }

        if (! Schema::hasTable('attendance_submissions')) {
            Schema::create('attendance_submissions', function (Blueprint $table) {
                $table->id();
                $table->string('period', 7)->unique();
                $table->string('status', 50)->default('pending_manager');
                $table->unsignedInteger('compiled_by')->nullable();
                $table->unsignedInteger('approved_by')->nullable();
                $table->json('summary_json')->nullable();
                $table->unsignedBigInteger('payroll_run_id')->nullable();
                $table->timestamp('submitted_at')->useCurrent();
                $table->timestamp('reviewed_at')->nullable();

                $table->index('compiled_by');
                $table->index('approved_by');
            });
        }

        if (! Schema::hasTable('leave_requests')) {
            Schema::create('leave_requests', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('employee_id')->nullable();
                $table->string('employee_name');
                $table->date('start_date');
                $table->date('end_date');
                $table->string('reason');
                $table->string('status', 50)->default('pending');
                $table->unsignedInteger('approved_by')->nullable();
                $table->timestamp('created_at')->useCurrent();

                $table->index('employee_id');
                $table->index('approved_by');
            });
        }

        if (! Schema::hasTable('deliveries')) {
            Schema::create('deliveries', function (Blueprint $table) {
                $table->id();
                $table->string('product_name');
                $table->integer('quantity');
                $table->integer('quantity_dispatched')->nullable();
                $table->string('destination', 50)->default('customer');
                $table->string('recipient_name')->nullable();
                $table->string('status', 50)->default('planned');
                $table->text('notes')->nullable();
                $table->boolean('needs_installation')->default(false);
                $table->unsignedInteger('created_by')->nullable();
                $table->unsignedInteger('dispatched_by')->nullable();
                $table->timestamp('dispatched_at')->nullable();
                $table->timestamps();

                $table->index(['status', 'destination'], 'idx_deliveries_status');
                $table->index('created_by');
                $table->index('dispatched_by');
            });
        }

        if (! Schema::hasTable('outbound_records')) {
            Schema::create('outbound_records', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('delivery_id')->nullable();
                $table->string('product_name');
                $table->integer('quantity_out');
                $table->string('destination', 50)->default('customer');
                $table->string('recipient_name');
                $table->unsignedInteger('counted_by')->nullable();
                $table->timestamp('counted_at')->nullable();
                $table->string('reference', 100)->nullable();
                $table->timestamp('created_at')->useCurrent();

                $table->index('delivery_id');
                $table->index('counted_by');
            });
        }

        if (! Schema::hasTable('external_laborers')) {
            Schema::create('external_laborers', function (Blueprint $table) {
                $table->id();
                $table->string('full_name');
                $table->string('phone', 50);
                $table->string('national_id', 100)->nullable();
                $table->text('specialty_skills')->nullable();
                $table->integer('experience_years')->default(0);
                $table->decimal('daily_rate', 12, 2)->default(0);
                $table->string('status', 50)->default('free');
                $table->text('notes')->nullable();
                $table->unsignedInteger('created_by_user_id')->nullable();
                $table->timestamps();

                $table->index('created_by_user_id');
            });
        }

        if (! Schema::hasTable('procurement_market_research')) {
            Schema::create('procurement_market_research', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('requisition_id')->nullable();
                $table->string('item_name');
                $table->string('category', 100)->nullable();
                $table->text('specifications')->nullable();
                $table->decimal('quantity', 12, 2);
                $table->string('unit_of_measure', 50)->default('pieces');
                $table->json('supplier_options_json')->nullable();
                $table->string('selected_supplier_name')->nullable();
                $table->decimal('selected_unit_price', 12, 2)->nullable();
                $table->decimal('selected_total_price', 12, 2)->nullable();
                $table->text('research_notes')->nullable();
                $table->string('quality_grade', 50)->nullable();
                $table->string('status', 50)->default('draft');
                $table->unsignedInteger('conducted_by_user_id');
                $table->unsignedInteger('approved_by_user_id')->nullable();
                $table->timestamps();

                $table->index('conducted_by_user_id');
                $table->index('approved_by_user_id');
            });
        }

        if (! Schema::hasTable('site_installation_jobs')) {
            Schema::create('site_installation_jobs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('order_id')->nullable();
                $table->unsignedBigInteger('delivery_id')->nullable();
                $table->string('customer_name');
                $table->string('customer_phone', 50)->nullable();
                $table->text('site_address');
                $table->string('status', 50)->default('not_assigned');
                $table->unsignedBigInteger('assigned_laborer_id')->nullable();
                $table->date('scheduled_start_date')->nullable();
                $table->integer('estimated_duration_days')->default(1);
                $table->date('actual_completion_date')->nullable();
                $table->decimal('agreed_total_payment', 12, 2)->default(0);
                $table->decimal('advance_payment_amount', 12, 2)->default(0);
                $table->decimal('balance_payment_amount', 12, 2)->default(0);
                $table->unsignedBigInteger('funding_request_id')->nullable();
                $table->text('setup_notes')->nullable();
                $table->text('client_sign_off_notes')->nullable();
                $table->timestamps();

                $table->index('order_id');
                $table->index('delivery_id');
                $table->index('assigned_laborer_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('site_installation_jobs');
        Schema::dropIfExists('procurement_market_research');
        Schema::dropIfExists('external_laborers');
        Schema::dropIfExists('outbound_records');
        Schema::dropIfExists('deliveries');
        Schema::dropIfExists('leave_requests');
        Schema::dropIfExists('attendance_submissions');
        Schema::dropIfExists('deals');
        Schema::dropIfExists('material_requests');
    }
};
