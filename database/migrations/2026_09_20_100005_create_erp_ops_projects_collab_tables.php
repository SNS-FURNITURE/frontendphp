<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
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
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('dispatched_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('dispatched_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'destination'], 'idx_deliveries_status');
        });

        Schema::create('outbound_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_id')->nullable()->constrained('deliveries')->nullOnDelete();
            $table->string('product_name');
            $table->integer('quantity_out');
            $table->string('destination', 50)->default('customer');
            $table->string('recipient_name');
            $table->foreignId('counted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('counted_at')->nullable();
            $table->string('reference', 100)->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('procurement_market_research', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('requisition_id')->nullable();
            $table->string('item_name');
            $table->string('category', 100)->nullable();
            $table->text('specifications')->nullable();
            $table->decimal('quantity', 12, 2);
            $table->string('unit_of_measure', 50)->default('pcs');
            $table->json('supplier_options_json')->nullable();
            $table->string('selected_supplier_name')->nullable();
            $table->decimal('selected_unit_price', 12, 2)->nullable();
            $table->decimal('selected_total_price', 12, 2)->nullable();
            $table->text('research_notes')->nullable();
            $table->string('quality_grade', 50)->nullable();
            $table->string('status', 50)->default('draft');
            $table->foreignId('conducted_by_user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('approved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

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
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('site_installation_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->nullable()->constrained('sales_orders')->nullOnDelete();
            $table->foreignId('delivery_id')->nullable()->constrained('deliveries')->nullOnDelete();
            $table->string('customer_name');
            $table->string('customer_phone', 50)->nullable();
            $table->text('site_address');
            $table->string('status', 50)->default('not_assigned');
            $table->foreignId('assigned_laborer_id')->nullable()->constrained('external_laborers')->nullOnDelete();
            $table->date('scheduled_start_date')->nullable();
            $table->integer('estimated_duration_days')->default(1);
            $table->date('actual_completion_date')->nullable();
            $table->decimal('agreed_total_payment', 12, 2)->default(0);
            $table->decimal('advance_payment_amount', 12, 2)->default(0);
            $table->decimal('balance_payment_amount', 12, 2)->default(0);
            $table->foreignId('funding_request_id')->nullable()->constrained('funding_requests')->nullOnDelete();
            $table->text('setup_notes')->nullable();
            $table->text('client_sign_off_notes')->nullable();
            $table->timestamps();
        });

        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('pm_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 50)->default('planned');
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('project_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->string('title');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 50)->default('todo');
            $table->date('due_date')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('workspaces', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('visibility', 50)->default('PRIVATE');
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('boards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('workspaces')->cascadeOnDelete();
            $table->string('name');
            $table->string('board_type', 50)->default('main');
            $table->string('module_key', 100)->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('board_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('board_id')->constrained('boards')->cascadeOnDelete();
            $table->string('name');
            $table->integer('sort_order')->default(0);
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('board_columns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('board_id')->constrained('boards')->cascadeOnDelete();
            $table->string('name');
            $table->string('key_name', 100);
            $table->string('column_type', 50);
            $table->json('settings_json')->nullable();
            $table->boolean('is_required')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamp('created_at')->useCurrent();
            $table->unique(['board_id', 'key_name']);
        });

        Schema::create('board_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('board_id')->constrained('boards')->cascadeOnDelete();
            $table->foreignId('group_id')->nullable()->constrained('board_groups')->nullOnDelete();
            $table->string('title');
            $table->string('entity_type', 100)->nullable();
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->foreignId('owner_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 50)->nullable();
            $table->date('due_date')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('board_item_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('board_item_id')->constrained('board_items')->cascadeOnDelete();
            $table->foreignId('column_id')->constrained('board_columns')->cascadeOnDelete();
            $table->text('value_text')->nullable();
            $table->json('value_json')->nullable();
            $table->unique(['board_item_id', 'column_id']);
        });

        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->string('entity_type', 100);
            $table->unsignedBigInteger('entity_id');
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->text('body');
            $table->unsignedBigInteger('parent_comment_id')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('file_attachments', function (Blueprint $table) {
            $table->id();
            $table->string('entity_type', 100);
            $table->unsignedBigInteger('entity_id');
            $table->string('file_name');
            $table->text('file_url');
            $table->string('file_mime', 100)->nullable();
            $table->foreignId('uploaded_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('type', 100);
            $table->string('title');
            $table->text('message')->nullable();
            $table->string('entity_type', 100)->nullable();
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->string('report_type', 100);
            $table->string('title');
            $table->text('body')->nullable();
            $table->string('role_name', 100)->nullable();
            $table->foreignId('posted_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('author_name')->nullable();
            $table->foreignId('role_id')->nullable()->constrained('roles')->nullOnDelete();
            $table->string('entity_type', 100)->nullable();
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->boolean('is_locked')->default(true);
            $table->boolean('immutable')->default(true);
            $table->timestamp('posted_at')->useCurrent();
            $table->unsignedBigInteger('sent_to_user_id')->nullable();
            $table->string('sent_to_name')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->index(['report_type', 'posted_at'], 'idx_reports_type_posted');
        });

        Schema::create('report_sources', function (Blueprint $table) {
            $table->unsignedBigInteger('report_id');
            $table->unsignedBigInteger('parent_report_id');
            $table->primary(['report_id', 'parent_report_id']);
            $table->foreign('report_id')->references('id')->on('reports')->cascadeOnDelete();
            $table->foreign('parent_report_id')->references('id')->on('reports')->cascadeOnDelete();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('entity_type', 100);
            $table->unsignedBigInteger('entity_id');
            $table->string('action', 100);
            $table->json('changes_json')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('report_sources');
        Schema::dropIfExists('reports');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('file_attachments');
        Schema::dropIfExists('comments');
        Schema::dropIfExists('board_item_values');
        Schema::dropIfExists('board_items');
        Schema::dropIfExists('board_columns');
        Schema::dropIfExists('board_groups');
        Schema::dropIfExists('boards');
        Schema::dropIfExists('workspaces');
        Schema::dropIfExists('project_tasks');
        Schema::dropIfExists('projects');
        Schema::dropIfExists('site_installation_jobs');
        Schema::dropIfExists('external_laborers');
        Schema::dropIfExists('procurement_market_research');
        Schema::dropIfExists('outbound_records');
        Schema::dropIfExists('deliveries');
    }
};
