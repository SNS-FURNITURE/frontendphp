<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\AttendanceSubmission;
use App\Models\Employee;
use App\Models\Party;
use App\Models\PayrollRun;
use Tests\FeatureTestCase;

class AttendancePayrollApprovalFlowTest extends FeatureTestCase
{
    public function test_monthly_attendance_flows_cm_to_finance_to_cm_final(): void
    {
        $period = now()->format('Y-m');

        $hr = $this->createUserWithRole('hr');
        $cm = $this->createUserWithRole('company_manager');
        $finance = $this->createUserWithRole('finance');

        $party = Party::query()->create([
            'party_type' => 'employee',
            'name' => 'Worker One',
            'approval_status' => 'approved',
        ]);

        $employee = Employee::query()->create([
            'party_id' => $party->id,
            'employee_number' => 'E-001',
            'job_title' => 'Assembler',
            'department' => 'Factory',
            'employment_status' => 'ACTIVE',
            'monthly_salary' => 20000,
        ]);

        Attendance::query()->create([
            'employee_id' => $employee->id,
            'date' => $period.'-02',
            'session' => 'morning',
            'status' => 'present',
        ]);
        Attendance::query()->create([
            'employee_id' => $employee->id,
            'date' => $period.'-02',
            'session' => 'afternoon',
            'status' => 'present',
        ]);

        $this->actingAs($hr)
            ->post(route('hr.attendance.compile'), ['period' => $period])
            ->assertRedirect();

        $submission = AttendanceSubmission::query()->where('period', $period)->first();
        $this->assertNotNull($submission);
        $this->assertSame('pending_manager', $submission->status);
        $this->assertDatabaseHas('notifications', [
            'type' => 'attendance_payroll_review',
        ]);

        $this->actingAs($cm)
            ->patch(route('hr.attendance.review', $submission->id), ['status' => 'approved'])
            ->assertRedirect();

        $submission->refresh();
        $this->assertSame('approved', $submission->status);
        $this->assertNull($submission->payroll_run_id);
        $this->assertSame(0, PayrollRun::query()->where('period', $period)->count());
        $this->assertDatabaseHas('notifications', [
            'type' => 'attendance_calendar_approved',
        ]);

        $this->actingAs($finance)
            ->post(route('finance.payroll.generate'), ['period' => $period])
            ->assertRedirect();

        $run = PayrollRun::query()->where('period', $period)->first();
        $this->assertNotNull($run);
        $this->assertSame(PayrollRun::STATUS_DRAFT, $run->status);
        $submission->refresh();
        $this->assertSame($run->id, $submission->payroll_run_id);

        $this->actingAs($finance)
            ->patch(route('finance.payroll.status', $run->id), ['status' => PayrollRun::STATUS_PENDING_MANAGER])
            ->assertRedirect();

        $run->refresh();
        $this->assertSame(PayrollRun::STATUS_PENDING_MANAGER, $run->status);
        $this->assertDatabaseHas('notifications', [
            'type' => 'payroll_final_review',
        ]);

        $this->actingAs($cm)
            ->patch(route('finance.payroll.status', $run->id), ['status' => PayrollRun::STATUS_PROCESSED])
            ->assertRedirect();

        $run->refresh();
        $this->assertSame(PayrollRun::STATUS_PROCESSED, $run->status);
        $this->assertNotNull($run->processed_at);
        $this->assertDatabaseHas('notifications', [
            'type' => 'payroll_final_decision',
        ]);
    }

    public function test_finance_cannot_generate_before_cm_approves_calendar(): void
    {
        $period = now()->format('Y-m');
        $finance = $this->createUserWithRole('finance');

        AttendanceSubmission::query()->create([
            'period' => $period,
            'status' => 'pending_manager',
            'compiled_by' => $finance->id,
            'summary_json' => ['period' => $period, 'employees' => []],
            'submitted_at' => now(),
        ]);

        $this->actingAs($finance)
            ->post(route('finance.payroll.generate'), ['period' => $period])
            ->assertRedirect()
            ->assertSessionHasErrors('period');

        $this->assertSame(0, PayrollRun::query()->where('period', $period)->count());
    }

    public function test_finance_cannot_finalize_payroll(): void
    {
        $finance = $this->createUserWithRole('finance');
        $run = PayrollRun::query()->create([
            'period' => '2026-01',
            'status' => PayrollRun::STATUS_PENDING_MANAGER,
            'employee_count' => 1,
            'total_gross' => 100,
            'total_paye' => 10,
            'total_employee_pension' => 7,
            'total_employer_pension' => 11,
            'total_net' => 83,
            'total_employer_cost' => 111,
        ]);

        $this->actingAs($finance)
            ->patch(route('finance.payroll.status', $run->id), ['status' => PayrollRun::STATUS_PROCESSED])
            ->assertRedirect()
            ->assertSessionHasErrors('status');

        $this->assertSame(PayrollRun::STATUS_PENDING_MANAGER, $run->fresh()->status);
    }
}
