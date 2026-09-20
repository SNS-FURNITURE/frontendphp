<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parties', function (Blueprint $table) {
            $table->id();
            $table->string('party_type', 50);
            $table->string('name');
            $table->string('company_name')->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->text('notes')->nullable();
            $table->string('approval_status', 50)->default('pending');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('party_id')->constrained('parties')->cascadeOnDelete();
            $table->string('supplier_code', 100)->unique();
            $table->string('status', 50)->default('ACTIVE');
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('party_id')->constrained('parties')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('employee_no', 100)->nullable()->unique();
            $table->string('employee_number', 100)->nullable();
            $table->string('national_id_number', 100)->nullable();
            $table->string('employee_account')->nullable();
            $table->string('department', 100)->nullable();
            $table->string('job_title', 100)->nullable();
            $table->date('hire_date')->nullable();
            $table->date('termination_date')->nullable();
            $table->string('employment_status', 50)->default('ACTIVE');
            $table->foreignId('hr_manager_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_relationship', 100)->nullable();
            $table->string('emergency_contact_phone', 100)->nullable();
            $table->text('photo_url')->nullable();
            $table->text('id_image_url')->nullable();
            $table->text('cv_url')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('bank_account_number', 100)->nullable();
            $table->decimal('monthly_salary', 12, 2)->nullable();
            $table->timestamps();
        });

        Schema::create('attendance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->date('date');
            $table->string('session', 20)->nullable();
            $table->time('check_in')->nullable();
            $table->time('check_out')->nullable();
            $table->string('status', 50)->default('present');
            $table->unique(['employee_id', 'date', 'session'], 'uniq_attendance_emp_date_session');
            $table->index('date', 'idx_attendance_date');
        });

        Schema::create('attendance_submissions', function (Blueprint $table) {
            $table->id();
            $table->string('period', 7)->unique();
            $table->string('status', 50)->default('pending_manager');
            $table->foreignId('compiled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('summary_json')->nullable();
            $table->unsignedBigInteger('payroll_run_id')->nullable();
            $table->timestamp('submitted_at')->useCurrent();
            $table->timestamp('reviewed_at')->nullable();
        });

        Schema::create('leave_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->string('employee_name');
            $table->date('start_date');
            $table->date('end_date');
            $table->string('reason');
            $table->string('status', 50)->default('pending');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_requests');
        Schema::dropIfExists('attendance_submissions');
        Schema::dropIfExists('attendance');
        Schema::dropIfExists('employees');
        Schema::dropIfExists('suppliers');
        Schema::dropIfExists('parties');
    }
};
