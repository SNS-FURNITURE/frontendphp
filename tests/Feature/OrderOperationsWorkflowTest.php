<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Services\OrderIntakeService;
use App\Services\OrderProcurementService;
use App\Services\OrderScheduleService;
use App\Services\OrderWorkflowService;
use App\Support\OrderOperations;
use Illuminate\Support\Carbon;
use Tests\FeatureTestCase;

class OrderOperationsWorkflowTest extends FeatureTestCase
{
    public function test_oms_reject_requires_reason_and_resubmit_loop(): void
    {
        $supervisor = $this->createUserWithRole('sales_supervisor');
        $oms = $this->createUserWithRole(OrderOperations::ROLE_OMS);

        $invoice = Invoice::query()->create([
            'invoice_number' => 'INV-2026-010',
            'amount' => 500,
            'status' => 'issued',
            'snapshot_json' => ['totals' => ['grand_total' => 500]],
            'created_by' => $supervisor->id,
        ]);

        $intake = app(OrderIntakeService::class)->enqueueFromSalesSupervisorIssue($invoice, $supervisor);
        $this->assertNotNull($intake);

        try {
            app(OrderIntakeService::class)->reject($intake, $oms, '   ');
            $this->fail('Empty rejection should fail');
        } catch (\InvalidArgumentException $e) {
            $this->assertStringContainsString('required', strtolower($e->getMessage()));
        }

        $rejected = app(OrderIntakeService::class)->reject($intake, $oms, 'Wrong quantities');
        $this->assertSame(OrderOperations::INTAKE_REJECTED, $rejected->status);
        $this->assertDatabaseHas('order_intake_reviews', [
            'order_intake_id' => $rejected->id,
            'action' => 'rejected',
        ]);

        $resubmitted = app(OrderIntakeService::class)->resubmit($rejected->fresh(), $supervisor, 'Fixed quantities');
        $this->assertSame(OrderOperations::INTAKE_RESUBMITTED, $resubmitted->status);

        $awaiting = app(OrderIntakeService::class)->sendToCompanyManager(
            $resubmitted->fresh(),
            $oms,
            null,
            Carbon::now()->addDays(2)
        );
        $this->assertSame(OrderOperations::INTAKE_AWAITING_CM, $awaiting->status);
        $this->assertCount(0, $awaiting->phases);

        $cm = $this->createUserWithRole(OrderOperations::ROLE_COMPANY_MANAGER);
        $accepted = app(OrderIntakeService::class)->approveByCompanyManager($awaiting->fresh(), $cm);
        $this->assertSame(OrderOperations::INTAKE_ACCEPTED, $accepted->status);
        $this->assertCount(4, $accepted->phases);
    }

    public function test_design_checkpoints_and_procurement_approval_gate(): void
    {
        $oms = $this->createUserWithRole(OrderOperations::ROLE_OMS);
        $omf = $this->createUserWithRole(OrderOperations::ROLE_OMF);
        $designer = $this->createUserWithRole(OrderOperations::ROLE_DESIGNER);
        $cm = $this->createUserWithRole(OrderOperations::ROLE_COMPANY_MANAGER);
        $procurement = $this->createUserWithRole(OrderOperations::ROLE_PROCUREMENT);
        $assembler = $this->createUserWithRole(OrderOperations::ROLE_ASSEMBLER);
        $supervisor = $this->createUserWithRole('sales_supervisor');

        $invoice = Invoice::query()->create([
            'invoice_number' => 'INV-2026-020',
            'amount' => 900,
            'status' => 'approved',
            'snapshot_json' => ['totals' => ['grand_total' => 900]],
            'created_by' => $supervisor->id,
        ]);

        $intake = app(OrderIntakeService::class)->enqueueFromApproval($invoice, $oms);
        $intake = app(OrderIntakeService::class)->sendToCompanyManager(
            $intake,
            $oms,
            null,
            Carbon::now()->addDays(1)
        );
        $intake = app(OrderIntakeService::class)->approveByCompanyManager($intake, $cm);

        app(OrderScheduleService::class)->setPhaseDeadline(
            $intake->phase(OrderOperations::PHASE_DESIGN),
            $oms,
            Carbon::now()->addDays(3),
            12
        );

        app(OrderScheduleService::class)->assignUser($intake, $oms, $designer, OrderOperations::ROLE_DESIGNER);
        app(OrderScheduleService::class)->assignUser($intake, $omf, $assembler, OrderOperations::ROLE_ASSEMBLER);

        $design = $intake->fresh(['phases.checkpoints'])->phase(OrderOperations::PHASE_DESIGN);
        $this->assertNotNull($design);
        $this->assertCount(2, $design->checkpoints);

        $workflow = app(OrderWorkflowService::class);
        foreach ($design->checkpoints as $checkpoint) {
            $workflow->toggleCheckpoint($checkpoint, $designer, true);
        }

        $design = $design->fresh();
        $this->assertSame(OrderOperations::PHASE_COMPLETED, $design->status);

        $lines = app(OrderProcurementService::class)->submitMaterials($intake->fresh(), $designer, [
            ['item_name' => 'Walnut plank', 'quantity' => 10, 'unit' => 'pcs'],
        ]);
        $line = $lines[0];

        $proc = app(OrderProcurementService::class)->markUnavailableAndProcure($line, $cm);
        $quote = app(OrderProcurementService::class)->addQuote($proc, $procurement, [
            'supplier_name' => 'Acme Wood',
            'unit_price' => 50,
            'quality_grade' => 'A',
            'availability' => 'in_stock',
            'lead_time_days' => 5,
            'delivery_terms' => 'FOB',
        ]);

        app(OrderProcurementService::class)->recommendQuote($proc->fresh(), $quote, $procurement, 'Best quality/price trade-off');
        app(OrderProcurementService::class)->decideRecommendation($proc->fresh(), $cm, true, 'Approved');

        $released = app(OrderProcurementService::class)->releaseMaterials($intake->fresh(), $cm);
        $this->assertSame(
            OrderOperations::PHASE_COMPLETED,
            $released->phases->firstWhere('phase_key', OrderOperations::PHASE_MATERIALS)?->status
        );

        app(OrderWorkflowService::class)->completeAssembly($intake->fresh(), $omf);
        app(OrderWorkflowService::class)->completeDelivery($intake->fresh(), $oms);

        $this->assertSame(
            OrderOperations::PHASE_COMPLETED,
            $intake->fresh(['phases'])->phase(OrderOperations::PHASE_DELIVERY)?->status
        );
    }

    public function test_omf_cannot_accept_intake(): void
    {
        $omf = $this->createUserWithRole(OrderOperations::ROLE_OMF);
        $supervisor = $this->createUserWithRole('sales_supervisor');

        $invoice = Invoice::query()->create([
            'invoice_number' => 'INV-2026-030',
            'amount' => 100,
            'status' => 'issued',
            'snapshot_json' => ['totals' => ['grand_total' => 100]],
            'created_by' => $supervisor->id,
        ]);

        $intake = app(OrderIntakeService::class)->enqueueFromSalesSupervisorIssue($invoice, $supervisor);

        $this->expectException(\InvalidArgumentException::class);
        app(OrderIntakeService::class)->sendToCompanyManager($intake, $omf);
    }

    public function test_designer_assign_requires_company_manager_approval(): void
    {
        $oms = $this->createUserWithRole(OrderOperations::ROLE_OMS);
        $cm = $this->createUserWithRole(OrderOperations::ROLE_COMPANY_MANAGER);
        $designer = $this->createUserWithRole(OrderOperations::ROLE_DESIGNER);
        $supervisor = $this->createUserWithRole('sales_supervisor');

        $invoice = Invoice::query()->create([
            'invoice_number' => 'INV-2026-040',
            'amount' => 200,
            'status' => 'approved',
            'snapshot_json' => ['totals' => ['grand_total' => 200]],
            'created_by' => $supervisor->id,
        ]);

        $intake = app(OrderIntakeService::class)->enqueueFromApproval($invoice, $oms);
        $intake = app(OrderIntakeService::class)->sendToCompanyManager(
            $intake,
            $oms,
            null,
            Carbon::now()->addDays(1)
        );
        $this->assertSame(OrderOperations::INTAKE_AWAITING_CM, $intake->status);
        $this->assertNotNull($intake->cm_due_at);

        try {
            app(OrderScheduleService::class)->assignUser($intake, $oms, $designer, OrderOperations::ROLE_DESIGNER);
            $this->fail('Designer assign should fail before CM approval');
        } catch (\InvalidArgumentException $e) {
            $this->assertStringContainsString('company manager', strtolower($e->getMessage()));
        }

        $accepted = app(OrderIntakeService::class)->approveByCompanyManager($intake->fresh(), $cm);

        try {
            app(OrderScheduleService::class)->assignUser($accepted, $oms, $designer, OrderOperations::ROLE_DESIGNER);
            $this->fail('Designer assign should fail without deadline');
        } catch (\InvalidArgumentException $e) {
            $this->assertStringContainsString('deadline', strtolower($e->getMessage()));
        }

        app(OrderScheduleService::class)->setPhaseDeadline(
            $accepted->fresh(['phases'])->phase(OrderOperations::PHASE_DESIGN),
            $oms,
            Carbon::now()->addDays(5),
            24
        );

        $assignment = app(OrderScheduleService::class)->assignUser(
            $accepted->fresh(['phases']),
            $oms,
            $designer,
            OrderOperations::ROLE_DESIGNER
        );

        $this->assertSame(OrderOperations::ROLE_DESIGNER, $assignment->role_key);
        $this->assertSame($designer->id, (int) $assignment->user_id);
    }

    public function test_oms_pipeline_json_requires_authorization(): void
    {
        $sales = $this->createUserWithRole('sales');

        $this->actingAs($sales)
            ->get(route('operations.oms.pipeline.json'))
            ->assertRedirect();
    }

    public function test_oms_can_open_pipeline_json(): void
    {
        $oms = $this->createUserWithRole(OrderOperations::ROLE_OMS);

        $this->actingAs($oms)
            ->get(route('operations.oms.pipeline.json'))
            ->assertOk()
            ->assertJsonStructure(['generated_at', 'rows']);
    }

    public function test_oms_dashboard_requires_authorization(): void
    {
        $sales = $this->createUserWithRole('sales');

        $this->actingAs($sales)
            ->get(route('operations.oms.dashboard'))
            ->assertRedirect();
    }

    public function test_oms_can_open_dashboard(): void
    {
        $oms = $this->createUserWithRole(OrderOperations::ROLE_OMS);

        $this->actingAs($oms)
            ->get(route('operations.oms.dashboard'))
            ->assertOk();
    }
}
