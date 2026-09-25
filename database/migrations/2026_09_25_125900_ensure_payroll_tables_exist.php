<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('payroll_runs')) {
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

                $table->index('attendance_submission_id');
            });
        }

        if (! Schema::hasTable('payroll_lines')) {
            Schema::create('payroll_lines', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('payroll_run_id');
                $table->unsignedInteger('employee_id')->nullable();
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

                $table->index('payroll_run_id');
                $table->index('employee_id');
                $table->unique(['payroll_run_id', 'employee_id'], 'uniq_run_employee');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_lines');
        Schema::dropIfExists('payroll_runs');
    }
};
