<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\OrderMaterialUsageLog;
use App\Services\OrderIntakeService;
use App\Services\OrderMaterialUsageService;
use App\Services\OrderProcurementService;
use App\Services\OrderScheduleService;
use App\Services\OrderWorkflowService;
use App\Support\OrderOperations;
use Illuminate\Support\Carbon;
use Tests\FeatureTestCase;

class OrderMaterialUsageReportTest extends FeatureTestCase
{
    public function test_releasing_materials_writes_usage_log(): void
    {
        $oms = $this->createUserWithRole(OrderOperations::ROLE_OMS);
        $omf = $this->createUserWithRole(OrderOperations::ROLE_OMF);
        $designer = $this->createUserWithRole(OrderOperations::ROLE_DESIGNER);
        $cm = $this->createUserWithRole(OrderOperations::ROLE_COMPANY_MANAGER);
        $sales = $this->createUserWithRole('sales');
        $pm = $this->createUserWithRole(OrderOperations::ROLE_PRODUCT_MANAGER);
        $this->createUserWithRole('admin');

        $invoice = Invoice::query()->create([
            'invoice_number' => 'INV-MAT-001',
            'amount' => 100,
            'status' => 'approved',
            'snapshot_json' => ['totals' => ['grand_total' => 100]],
            'created_by' => $sales->id,
        ]);

        $intake = app(OrderIntakeService::class)->enqueueFromApproval($invoice, $oms);
        $intake = app(OrderIntakeService::class)->sendToCompanyManager($intake, $oms, null, Carbon::now()->addDay());
        $intake = app(OrderIntakeService::class)->approveByCompanyManager($intake, $cm);

        app(OrderScheduleService::class)->setPhaseDeadline(
            $intake->phase(OrderOperations::PHASE_DESIGN),
            $oms,
            Carbon::now()->addDays(2),
        );
        app(OrderScheduleService::class)->setPhaseDeadline(
            $intake->fresh(['phases'])->phase(OrderOperations::PHASE_FACTORY_COLORING),
            $oms,
            Carbon::now()->addDays(6),
        );
        app(OrderScheduleService::class)->assignUser($intake->fresh(['phases']), $oms, $designer, OrderOperations::ROLE_DESIGNER);
        app(OrderScheduleService::class)->assignUser($intake->fresh(['phases']), $oms, $pm, OrderOperations::ROLE_PRODUCT_MANAGER);

        $design = $intake->fresh(['phases.checkpoints'])->phase(OrderOperations::PHASE_DESIGN);
        foreach ($design->checkpoints as $checkpoint) {
            app(OrderWorkflowService::class)->toggleCheckpoint($checkpoint, $designer, true);
        }

        $lines = app(OrderProcurementService::class)->submitMaterials($intake->fresh(), $designer, [
            ['item_name' => 'Oak plank', 'quantity' => 12, 'unit' => 'pcs'],
        ]);
        app(OrderProcurementService::class)->verifyStockAvailable($lines[0], $cm);
        app(OrderProcurementService::class)->requestMaterialRelease($intake->fresh(), $pm);
        app(OrderProcurementService::class)->releaseMaterials($intake->fresh(), $cm);

        $this->assertDatabaseHas('order_material_usage_logs', [
            'order_intake_id' => $intake->id,
            'item_name' => 'Oak plank',
            'source_user_id' => $sales->id,
            'released_by' => $cm->id,
        ]);

        $this->assertSame(1, OrderMaterialUsageLog::query()->where('order_intake_id', $intake->id)->count());
        $this->assertDatabaseHas('notifications', [
            'type' => 'material_usage_logged',
        ]);
    }

    public function test_admin_commercial_reports_show_material_usage(): void
    {
        $admin = $this->createUserWithRole('admin');
        $sales = $this->createUserWithRole('sales');

        OrderMaterialUsageLog::query()->create([
            'order_intake_id' => $this->seedAcceptedIntake($sales)->id,
            'order_material_line_id' => null,
            'invoice_id' => null,
            'invoice_number' => 'INV-MAT-REP',
            'item_id' => null,
            'item_name' => 'Steel bolt',
            'quantity' => 40,
            'unit' => 'pcs',
            'source_user_id' => $sales->id,
            'released_by' => $admin->id,
            'used_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('commercial.reports.index', ['period' => 'weekly']))
            ->assertOk()
            ->assertSee('Material usage')
            ->assertSee('Steel bolt')
            ->assertSee('Sales analytics');

        $report = app(OrderMaterialUsageService::class)->usageReport('weekly');
        $this->assertGreaterThanOrEqual(1, $report['totals']['lines']);
        $this->assertSame('Steel bolt', $report['by_item'][0]['item_name'] ?? null);
    }

    public function test_sales_dashboard_shows_material_usage_for_source_rep(): void
    {
        $sales = $this->createUserWithRole('sales');

        OrderMaterialUsageLog::query()->create([
            'order_intake_id' => $this->seedAcceptedIntake($sales)->id,
            'order_material_line_id' => null,
            'invoice_id' => null,
            'invoice_number' => 'INV-MAT-SALES',
            'item_id' => null,
            'item_name' => 'Walnut veneer',
            'quantity' => 5,
            'unit' => 'sheets',
            'source_user_id' => $sales->id,
            'released_by' => $sales->id,
            'used_at' => now(),
        ]);

        $this->actingAs($sales)
            ->get(route('sales.dashboard'))
            ->assertOk()
            ->assertSee('Material usage (your orders)')
            ->assertSee('Walnut veneer');
    }

    private function seedAcceptedIntake($sales)
    {
        $oms = $this->createUserWithRole(OrderOperations::ROLE_OMS);
        $cm = $this->createUserWithRole(OrderOperations::ROLE_COMPANY_MANAGER);

        $invoice = Invoice::query()->create([
            'invoice_number' => 'INV-MAT-'.uniqid(),
            'amount' => 50,
            'status' => 'approved',
            'snapshot_json' => ['totals' => ['grand_total' => 50]],
            'created_by' => $sales->id,
        ]);

        $intake = app(OrderIntakeService::class)->enqueueFromApproval($invoice, $oms);
        $intake = app(OrderIntakeService::class)->sendToCompanyManager($intake, $oms, null, Carbon::now()->addDay());

        return app(OrderIntakeService::class)->approveByCompanyManager($intake, $cm);
    }
}
