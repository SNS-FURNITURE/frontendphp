<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('parties')->cascadeOnDelete();
            $table->foreignId('sales_rep_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 50)->default('draft');
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('sales_order_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_order_id')->constrained('sales_orders')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $table->decimal('quantity', 12, 2);
            $table->decimal('unit_price', 12, 2);
            $table->json('custom_specs')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('sales_quotas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('quota', 12, 2)->default(100000);
            $table->decimal('actual', 12, 2)->default(0);
            $table->string('period', 50)->default('2026-Q3');
        });

        Schema::create('production_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_order_id')->nullable()->constrained('sales_orders')->nullOnDelete();
            $table->foreignId('bom_id')->constrained('bill_of_materials')->cascadeOnDelete();
            $table->decimal('quantity', 12, 2);
            $table->string('status', 50)->default('planned');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone', 50);
            $table->string('email')->nullable();
            $table->string('source', 100)->default('outreach');
            $table->string('product_interest')->nullable();
            $table->text('room_details')->nullable();
            $table->text('material_preference')->nullable();
            $table->string('design_source', 100)->nullable();
            $table->text('notes')->nullable();
            $table->string('status', 50)->default('pending');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['status', 'created_at'], 'idx_leads_status_created');
        });

        Schema::create('deals', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('customer_name');
            $table->string('status', 50)->default('in_progress');
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('lead_id')->nullable()->constrained('leads')->nullOnDelete();
            $table->string('product_category', 100)->nullable();
            $table->decimal('deal_value', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('sales_reviewed_by')->nullable();
            $table->timestamp('sales_reviewed_at')->nullable();
            $table->unsignedBigInteger('manager_reviewed_by')->nullable();
            $table->timestamp('manager_reviewed_at')->nullable();
            $table->timestamps();
            $table->index('status', 'idx_deals_status');
        });

        Schema::create('designs', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('kind', 50)->default('original');
            $table->string('status', 50)->default('concept');
            $table->foreignId('sales_order_id')->nullable()->constrained('sales_orders')->nullOnDelete();
            $table->foreignId('deal_id')->nullable()->constrained('deals')->nullOnDelete();
            $table->foreignId('designer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('file_url')->nullable();
            $table->text('description')->nullable();
            $table->text('specs')->nullable();
            $table->string('room_type', 100)->nullable();
            $table->string('finish', 100)->nullable();
            $table->string('source', 100)->nullable();
            $table->string('dimensions', 100)->nullable();
            $table->decimal('estimated_hours', 6, 2)->nullable();
            $table->string('design_source', 100)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_order_id')->nullable()->constrained('sales_orders')->nullOnDelete();
            $table->string('invoice_number', 100)->unique();
            $table->decimal('amount', 12, 2);
            $table->string('status', 50)->default('draft');
            $table->timestamp('issued_at')->nullable();
            $table->date('due_date')->nullable();
            $table->json('snapshot_json')->nullable();
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('method', 50)->default('bank_transfer');
            $table->timestamp('paid_at')->useCurrent();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('funding_requests', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->decimal('amount', 12, 2);
            $table->text('purpose');
            $table->string('category', 100)->default('operational');
            $table->string('status', 50)->default('draft');
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('payroll_runs', function (Blueprint $table) {
            $table->id();
            $table->string('period', 7)->unique();
            $table->string('status', 50)->default('draft');
            $table->integer('employee_count')->default(0);
            $table->decimal('total_gross', 14, 2)->default(0);
            $table->decimal('total_paye', 14, 2)->default(0);
            $table->decimal('total_employee_pension', 14, 2)->default(0);
            $table->decimal('total_employer_pension', 14, 2)->default(0);
            $table->decimal('total_net', 14, 2)->default(0);
            $table->decimal('total_employer_cost', 14, 2)->default(0);
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->unsignedBigInteger('attendance_submission_id')->nullable();
            $table->timestamps();
        });

        Schema::create('payroll_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_run_id')->constrained('payroll_runs')->cascadeOnDelete();
            $table->foreignId('employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->string('employee_number', 100)->nullable();
            $table->string('employee_name');
            $table->string('department', 100)->nullable();
            $table->string('job_title', 100)->nullable();
            $table->string('bank_name')->nullable();
            $table->string('bank_account_number', 100)->nullable();
            $table->decimal('basic_salary', 12, 2)->default(0);
            $table->decimal('transport_allowance', 12, 2)->default(0);
            $table->decimal('housing_allowance', 12, 2)->default(0);
            $table->decimal('other_allowance', 12, 2)->default(0);
            $table->decimal('overtime_hours', 6, 2)->default(0);
            $table->decimal('overtime_multiplier', 4, 2)->default(1.25);
            $table->decimal('overtime_pay', 12, 2)->default(0);
            $table->decimal('unpaid_days', 5, 2)->default(0);
            $table->decimal('unpaid_absence', 12, 2)->default(0);
            $table->decimal('other_deductions', 12, 2)->default(0);
            $table->decimal('daily_rate', 12, 2)->default(0);
            $table->decimal('days_worked', 5, 2)->default(30);
            $table->decimal('salary_month', 12, 2)->default(0);
            $table->decimal('taxable_allowance', 12, 2)->default(0);
            $table->decimal('advance', 12, 2)->default(0);
            $table->decimal('long_term_loan', 12, 2)->default(0);
            $table->decimal('total_deduction', 12, 2)->default(0);
            $table->decimal('gross', 12, 2)->default(0);
            $table->decimal('employee_pension', 12, 2)->default(0);
            $table->decimal('employer_pension', 12, 2)->default(0);
            $table->decimal('taxable_income', 12, 2)->default(0);
            $table->decimal('paye', 12, 2)->default(0);
            $table->decimal('net', 12, 2)->default(0);
            $table->decimal('employer_cost', 12, 2)->default(0);
            $table->decimal('role_multiplier', 6, 3)->default(1);
            $table->decimal('attendance_factor', 6, 3)->default(1);
            $table->decimal('performance_factor', 6, 3)->default(1);
            $table->text('formula_text')->nullable();
            $table->timestamps();
            $table->unique(['payroll_run_id', 'employee_id'], 'uniq_run_employee');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_lines');
        Schema::dropIfExists('payroll_runs');
        Schema::dropIfExists('funding_requests');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('designs');
        Schema::dropIfExists('deals');
        Schema::dropIfExists('leads');
        Schema::dropIfExists('production_orders');
        Schema::dropIfExists('sales_quotas');
        Schema::dropIfExists('sales_order_lines');
        Schema::dropIfExists('sales_orders');
    }
};
