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
        $this->assertCount(5, $accepted->phases);
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
        $this->assertCount(3, $design->checkpoints);

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

    public function test_approval_captures_pre_approval_as_issued_snapshot(): void
    {
        $supervisor = $this->createUserWithRole('sales_supervisor');
        $approver = $this->createUserWithRole('marketing_manager');

        $invoice = Invoice::query()->create([
            'invoice_number' => 'INV-2026-100',
            'amount' => 300,
            'status' => 'issued',
            'snapshot_json' => [
                'doc_number' => 'INV-2026-100',
                'totals' => ['grand_total' => 300],
                'customer' => ['name' => 'Before Approve'],
                'lines' => [['description' => 'Chair', 'qty' => 1]],
            ],
            'created_by' => $supervisor->id,
            'issued_at' => now(),
        ]);

        $issuedBefore = $invoice->snapshot_json;
        $approved = [
            'doc_number' => 'INV-2026-100',
            'totals' => ['grand_total' => 350],
            'customer' => ['name' => 'After Approve'],
            'lines' => [['description' => 'Chair', 'qty' => 1]],
            'approval' => ['status' => 'approved'],
        ];

        $invoice->status = 'approved';
        $invoice->snapshot_json = $approved;
        $invoice->amount = 350;
        $invoice->save();

        $intake = app(OrderIntakeService::class)->enqueueFromApproval(
            $invoice->fresh(),
            $approver,
            null,
            $issuedBefore
        );

        $this->assertNotNull($intake);
        $this->assertSame('Before Approve', $intake->issued_snapshot_json['customer']['name'] ?? null);
        $this->assertSame('After Approve', $intake->approved_snapshot_json['customer']['name'] ?? null);
    }

    public function test_company_manager_sees_invoice_when_awaiting_approval(): void
    {
        $oms = $this->createUserWithRole(OrderOperations::ROLE_OMS);
        $cm = $this->createUserWithRole(OrderOperations::ROLE_COMPANY_MANAGER);
        $supervisor = $this->createUserWithRole('sales_supervisor');

        $invoice = Invoice::query()->create([
            'invoice_number' => 'INV-2026-110',
            'amount' => 450,
            'status' => 'approved',
            'snapshot_json' => [
                'doc_number' => 'INV-2026-110',
                'doc_type' => 'PROFORMA',
                'totals' => [
                    'subtotal' => 450,
                    'discount_amount' => 0,
                    'taxable' => 450,
                    'tax_amount' => 0,
                    'grand_total' => 450,
                ],
                'customer' => ['name' => 'CM Visible Co'],
                'lines' => [
                    ['description' => 'Oak desk', 'qty' => 1, 'unit_price' => 450, 'line_total' => 450],
                ],
            ],
            'created_by' => $supervisor->id,
        ]);

        $intake = app(OrderIntakeService::class)->enqueueFromApproval(
            $invoice,
            $oms,
            null,
            $invoice->snapshot_json
        );
        $intake = app(OrderIntakeService::class)->sendToCompanyManager(
            $intake,
            $oms,
            null,
            Carbon::now()->addDays(2)
        );

        $this->actingAs($cm)
            ->get(route('operations.orders.show', $intake))
            ->assertOk()
            ->assertSee('Company manager approval')
            ->assertSee('CM Visible Co')
            ->assertSee('Oak desk')
            ->assertSee('Approve for production');

        $this->actingAs($cm)
            ->get(route('operations.manager.dashboard'))
            ->assertOk()
            ->assertSee('Review invoice')
            ->assertSee('INV-2026-110');
    }

    public function test_oms_can_open_dashboard(): void
    {
        $oms = $this->createUserWithRole(OrderOperations::ROLE_OMS);

        $this->actingAs($oms)
            ->get(route('operations.oms.dashboard'))
            ->assertOk();
    }

    public function test_dual_snapshots_preserve_issued_when_approved(): void
    {
        $supervisor = $this->createUserWithRole('sales_supervisor');
        $oms = $this->createUserWithRole(OrderOperations::ROLE_OMS);

        $invoice = Invoice::query()->create([
            'invoice_number' => 'INV-2026-050',
            'amount' => 300,
            'status' => 'issued',
            'snapshot_json' => [
                'doc_number' => 'INV-2026-050',
                'totals' => ['grand_total' => 300],
                'customer' => ['name' => 'Issued Co'],
            ],
            'created_by' => $supervisor->id,
        ]);

        $intake = app(OrderIntakeService::class)->enqueueFromSalesSupervisorIssue($invoice, $supervisor);
        $this->assertNotNull($intake);
        $this->assertSame(300.0, (float) ($intake->issued_snapshot_json['totals']['grand_total'] ?? 0));
        $this->assertNull($intake->approved_snapshot_json);

        $invoice->status = 'approved';
        $invoice->snapshot_json = [
            'doc_number' => 'INV-2026-050',
            'totals' => ['grand_total' => 350],
            'customer' => ['name' => 'Approved Co'],
        ];
        $invoice->save();

        $updated = app(OrderIntakeService::class)->enqueueFromApproval($invoice->fresh(), $oms);
        $this->assertSame(300.0, (float) ($updated->issued_snapshot_json['totals']['grand_total'] ?? 0));
        $this->assertSame(350.0, (float) ($updated->approved_snapshot_json['totals']['grand_total'] ?? 0));
    }

    public function test_oms_assigns_designer_and_product_manager_omf_cannot(): void
    {
        $oms = $this->createUserWithRole(OrderOperations::ROLE_OMS);
        $omf = $this->createUserWithRole(OrderOperations::ROLE_OMF);
        $cm = $this->createUserWithRole(OrderOperations::ROLE_COMPANY_MANAGER);
        $designer = $this->createUserWithRole(OrderOperations::ROLE_DESIGNER);
        $pm = $this->createUserWithRole(OrderOperations::ROLE_PRODUCT_MANAGER);
        $supervisor = $this->createUserWithRole('sales_supervisor');

        $invoice = Invoice::query()->create([
            'invoice_number' => 'INV-2026-060',
            'amount' => 400,
            'status' => 'approved',
            'snapshot_json' => ['totals' => ['grand_total' => 400]],
            'created_by' => $supervisor->id,
        ]);

        $intake = app(OrderIntakeService::class)->enqueueFromApproval($invoice, $oms);
        $intake = app(OrderIntakeService::class)->sendToCompanyManager($intake, $oms, null, Carbon::now()->addDays(1));
        $intake = app(OrderIntakeService::class)->approveByCompanyManager($intake, $cm);

        app(OrderScheduleService::class)->setPhaseDeadline(
            $intake->phase(OrderOperations::PHASE_DESIGN),
            $oms,
            Carbon::now()->addDays(3),
        );
        app(OrderScheduleService::class)->setPhaseDeadline(
            $intake->fresh(['phases'])->phase(OrderOperations::PHASE_FACTORY_COLORING),
            $oms,
            Carbon::now()->addDays(7),
        );

        app(OrderScheduleService::class)->assignUser(
            $intake->fresh(['phases']),
            $oms,
            $designer,
            OrderOperations::ROLE_DESIGNER
        );
        app(OrderScheduleService::class)->assignUser(
            $intake->fresh(['phases']),
            $oms,
            $pm,
            OrderOperations::ROLE_PRODUCT_MANAGER
        );

        $this->assertDatabaseHas('order_assignments', [
            'order_intake_id' => $intake->id,
            'role_key' => OrderOperations::ROLE_DESIGNER,
            'user_id' => $designer->id,
        ]);
        $this->assertDatabaseHas('order_assignments', [
            'order_intake_id' => $intake->id,
            'role_key' => OrderOperations::ROLE_PRODUCT_MANAGER,
            'user_id' => $pm->id,
        ]);

        $this->expectException(\InvalidArgumentException::class);
        app(OrderScheduleService::class)->assignUser(
            $intake->fresh(['phases']),
            $omf,
            $pm,
            OrderOperations::ROLE_PRODUCT_MANAGER
        );
    }

    public function test_oms_can_add_custom_phase_omf_cannot(): void
    {
        $oms = $this->createUserWithRole(OrderOperations::ROLE_OMS);
        $omf = $this->createUserWithRole(OrderOperations::ROLE_OMF);
        $cm = $this->createUserWithRole(OrderOperations::ROLE_COMPANY_MANAGER);
        $supervisor = $this->createUserWithRole('sales_supervisor');

        $invoice = Invoice::query()->create([
            'invoice_number' => 'INV-2026-070',
            'amount' => 120,
            'status' => 'approved',
            'snapshot_json' => ['totals' => ['grand_total' => 120]],
            'created_by' => $supervisor->id,
        ]);

        $intake = app(OrderIntakeService::class)->enqueueFromApproval($invoice, $oms);
        $intake = app(OrderIntakeService::class)->sendToCompanyManager($intake, $oms, null, Carbon::now()->addDays(1));
        $intake = app(OrderIntakeService::class)->approveByCompanyManager($intake, $cm);

        $custom = app(OrderScheduleService::class)->addCustomPhase($intake, $oms, 'Quality check', Carbon::now()->addDays(10));
        $this->assertSame('Quality check', $custom->label);
        $this->assertSame('quality_check', $custom->phase_key);

        $this->expectException(\InvalidArgumentException::class);
        app(OrderScheduleService::class)->addCustomPhase($intake->fresh(), $omf, 'Should fail');
    }

    public function test_designer_invoice_document_hides_prices(): void
    {
        $oms = $this->createUserWithRole(OrderOperations::ROLE_OMS);
        $cm = $this->createUserWithRole(OrderOperations::ROLE_COMPANY_MANAGER);
        $designer = $this->createUserWithRole(OrderOperations::ROLE_DESIGNER);
        $supervisor = $this->createUserWithRole('sales_supervisor');

        $invoice = Invoice::query()->create([
            'invoice_number' => 'INV-2026-080',
            'amount' => 999,
            'status' => 'approved',
            'snapshot_json' => [
                'doc_number' => 'INV-2026-080',
                'doc_type' => 'PROFORMA',
                'totals' => [
                    'subtotal' => 999,
                    'discount_amount' => 0,
                    'taxable' => 999,
                    'tax_amount' => 0,
                    'grand_total' => 999,
                ],
                'customer' => ['name' => 'Hide Prices Co'],
                'lines' => [
                    ['description' => 'Table', 'qty' => 1, 'unit_price' => 999, 'line_total' => 999],
                ],
            ],
            'created_by' => $supervisor->id,
        ]);

        $intake = app(OrderIntakeService::class)->enqueueFromApproval($invoice, $oms);
        $intake = app(OrderIntakeService::class)->sendToCompanyManager($intake, $oms, null, Carbon::now()->addDays(1));
        $intake = app(OrderIntakeService::class)->approveByCompanyManager($intake, $cm);

        $html = $this->actingAs($designer)
            ->get(route('operations.orders.invoice-document', $intake))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('999.00', $html);
        $this->assertStringContainsString('Table', $html);
    }

    public function test_checkpoint_notifies_sales_source_user(): void
    {
        $oms = $this->createUserWithRole(OrderOperations::ROLE_OMS);
        $cm = $this->createUserWithRole(OrderOperations::ROLE_COMPANY_MANAGER);
        $designer = $this->createUserWithRole(OrderOperations::ROLE_DESIGNER);
        $sales = $this->createUserWithRole('sales');
        $this->createUserWithRole(OrderOperations::ROLE_OMF);
        $this->createUserWithRole('admin');
        $this->createUserWithRole('sales_supervisor');

        $invoice = Invoice::query()->create([
            'invoice_number' => 'INV-2026-090',
            'amount' => 80,
            'status' => 'approved',
            'snapshot_json' => ['totals' => ['grand_total' => 80]],
            'created_by' => $sales->id,
        ]);

        $intake = app(OrderIntakeService::class)->enqueueFromApproval($invoice, $oms);
        $intake = app(OrderIntakeService::class)->sendToCompanyManager($intake, $oms, null, Carbon::now()->addDays(1));
        $intake = app(OrderIntakeService::class)->approveByCompanyManager($intake, $cm);

        app(OrderScheduleService::class)->setPhaseDeadline(
            $intake->phase(OrderOperations::PHASE_DESIGN),
            $oms,
            Carbon::now()->addDays(2),
        );
        app(OrderScheduleService::class)->assignUser(
            $intake->fresh(['phases']),
            $oms,
            $designer,
            OrderOperations::ROLE_DESIGNER
        );

        $checkpoint = $intake->fresh(['phases.checkpoints'])
            ->phase(OrderOperations::PHASE_DESIGN)
            ->checkpoints
            ->firstWhere('checkpoint_key', OrderOperations::CHECKPOINT_MEASUREMENT);

        $this->assertNotNull($checkpoint);
        app(OrderWorkflowService::class)->toggleCheckpoint($checkpoint, $designer, true);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $sales->id,
            'type' => 'order_checkpoint',
            'entity_type' => 'order_intake',
            'entity_id' => $intake->id,
        ]);
    }
}
