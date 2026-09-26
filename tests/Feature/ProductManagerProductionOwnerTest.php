<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\OrderMessage;
use App\Models\User;
use App\Services\OrderIntakeService;
use App\Services\OrderMessageService;
use App\Services\OrderProcurementService;
use App\Services\OrderScheduleService;
use App\Services\OrderWorkflowService;
use App\Support\OrderOperations;
use Illuminate\Support\Carbon;
use Tests\FeatureTestCase;

class ProductManagerProductionOwnerTest extends FeatureTestCase
{
    public function test_sole_product_manager_is_auto_assigned_on_schedule_save(): void
    {
        $oms = $this->createUserWithRole(OrderOperations::ROLE_OMS);
        $cm = $this->createUserWithRole(OrderOperations::ROLE_COMPANY_MANAGER);
        $designer = $this->createUserWithRole(OrderOperations::ROLE_DESIGNER);
        $pm = $this->createUserWithRole(OrderOperations::ROLE_PRODUCT_MANAGER);
        $supervisor = $this->createUserWithRole('sales_supervisor');

        $this->assertSame($pm->id, User::soleProductManager()?->id);

        $intake = $this->acceptedIntake($oms, $cm, $supervisor);

        $phases = $intake->fresh(['phases'])->phases->sortBy('sort_order')->values()->map(fn ($phase) => [
            'id' => $phase->id,
            'label' => $phase->displayLabel(),
            'due_at' => Carbon::now()->addDays(4)->format('Y-m-d H:i:s'),
            'reminder_hours_before' => 24,
        ])->all();

        $saved = app(OrderScheduleService::class)->saveSchedule(
            $intake,
            $oms,
            $phases,
            [],
            $designer->id,
            null,
        );

        $this->assertDatabaseHas('order_assignments', [
            'order_intake_id' => $saved->id,
            'role_key' => OrderOperations::ROLE_PRODUCT_MANAGER,
            'user_id' => $pm->id,
        ]);
    }

    public function test_pm_requests_release_then_cm_releases_from_inventory(): void
    {
        $oms = $this->createUserWithRole(OrderOperations::ROLE_OMS);
        $cm = $this->createUserWithRole(OrderOperations::ROLE_COMPANY_MANAGER);
        $designer = $this->createUserWithRole(OrderOperations::ROLE_DESIGNER);
        $pm = $this->createUserWithRole(OrderOperations::ROLE_PRODUCT_MANAGER);
        $supervisor = $this->createUserWithRole('sales_supervisor');
        $this->createUserWithRole(OrderOperations::ROLE_OMF);

        $intake = $this->acceptedIntake($oms, $cm, $supervisor);
        $this->scheduleWithPeople($intake, $oms, $designer, $pm);
        $this->completeDesign($intake, $designer);

        $lines = app(OrderProcurementService::class)->submitMaterials($intake->fresh(), $designer, [
            ['item_name' => 'Raw timber', 'quantity' => 8, 'unit' => 'pcs'],
        ]);
        app(OrderProcurementService::class)->verifyStockAvailable($lines[0], $cm);

        try {
            app(OrderProcurementService::class)->releaseMaterials($intake->fresh(), $cm);
            $this->fail('CM should not release before PM request');
        } catch (\InvalidArgumentException $e) {
            $this->assertStringContainsString('product manager must request', strtolower($e->getMessage()));
        }

        app(OrderProcurementService::class)->requestMaterialRelease($intake->fresh(), $pm, 'Shop needs timber');

        $this->assertDatabaseHas('order_material_lines', [
            'id' => $lines[0]->id,
            'stock_status' => OrderOperations::STOCK_RELEASE_REQUESTED,
        ]);

        app(OrderProcurementService::class)->releaseMaterials($intake->fresh(), $cm);

        $this->assertDatabaseHas('order_material_lines', [
            'id' => $lines[0]->id,
            'stock_status' => OrderOperations::STOCK_RELEASED,
        ]);
    }

    public function test_pm_production_update_notifies_oms_and_omf(): void
    {
        $oms = $this->createUserWithRole(OrderOperations::ROLE_OMS);
        $omf = $this->createUserWithRole(OrderOperations::ROLE_OMF);
        $cm = $this->createUserWithRole(OrderOperations::ROLE_COMPANY_MANAGER);
        $designer = $this->createUserWithRole(OrderOperations::ROLE_DESIGNER);
        $pm = $this->createUserWithRole(OrderOperations::ROLE_PRODUCT_MANAGER);
        $supervisor = $this->createUserWithRole('sales_supervisor');

        $intake = $this->acceptedIntake($oms, $cm, $supervisor);
        $this->scheduleWithPeople($intake, $oms, $designer, $pm);

        app(OrderMessageService::class)->postProductionUpdate(
            $intake->fresh(),
            $pm,
            'Factory coloring started on frame set.'
        );

        $this->assertDatabaseHas('order_messages', [
            'order_intake_id' => $intake->id,
            'sender_id' => $pm->id,
            'message_kind' => OrderOperations::MESSAGE_PRODUCTION_UPDATE,
        ]);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $oms->id,
            'type' => 'production_update',
        ]);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $omf->id,
            'type' => 'production_update',
        ]);

        $this->assertSame(1, OrderMessage::query()
            ->where('order_intake_id', $intake->id)
            ->where('message_kind', OrderOperations::MESSAGE_PRODUCTION_UPDATE)
            ->count());
    }

    private function acceptedIntake($oms, $cm, $supervisor)
    {
        $invoice = Invoice::query()->create([
            'invoice_number' => 'INV-PM-'.uniqid(),
            'amount' => 100,
            'status' => 'approved',
            'snapshot_json' => ['totals' => ['grand_total' => 100]],
            'created_by' => $supervisor->id,
        ]);

        $intake = app(OrderIntakeService::class)->enqueueFromApproval($invoice, $oms);
        $intake = app(OrderIntakeService::class)->sendToCompanyManager($intake, $oms, null, Carbon::now()->addDay());

        return app(OrderIntakeService::class)->approveByCompanyManager($intake, $cm);
    }

    private function scheduleWithPeople($intake, $oms, $designer, $pm): void
    {
        $phases = $intake->fresh(['phases'])->phases->map(fn ($phase) => [
            'id' => $phase->id,
            'label' => $phase->displayLabel(),
            'due_at' => Carbon::now()->addDays(5)->format('Y-m-d H:i:s'),
            'reminder_hours_before' => 24,
        ])->all();

        app(OrderScheduleService::class)->saveSchedule($intake, $oms, $phases, [], $designer->id, $pm->id);
    }

    private function completeDesign($intake, $designer): void
    {
        $design = $intake->fresh(['phases.checkpoints'])->phase(OrderOperations::PHASE_DESIGN);
        foreach ($design->checkpoints as $checkpoint) {
            app(OrderWorkflowService::class)->toggleCheckpoint($checkpoint, $designer, true);
        }
    }
}
