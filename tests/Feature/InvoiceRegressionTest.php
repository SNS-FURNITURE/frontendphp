<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Services\OrderIntakeService;
use App\Support\OrderOperations;
use Tests\FeatureTestCase;

class InvoiceRegressionTest extends FeatureTestCase
{
    public function test_sales_supervisor_can_create_issued_invoice_and_enqueue_oms_intake(): void
    {
        $supervisor = $this->createUserWithRole('sales_supervisor');

        $invoice = Invoice::query()->create([
            'invoice_number' => 'INV-2026-001',
            'amount' => 1500.00,
            'status' => 'issued',
            'issued_at' => now(),
            'snapshot_json' => [
                'customer' => ['name' => 'Test Customer'],
                'lines' => [['description' => 'Chair', 'qty' => 1, 'unit_price' => 1500]],
                'totals' => ['grand_total' => 1500],
            ],
            'created_by' => $supervisor->id,
        ]);

        $intake = app(OrderIntakeService::class)->enqueueFromSalesSupervisorIssue($invoice, $supervisor);

        $this->assertNotNull($intake);
        $this->assertSame(OrderOperations::INTAKE_PENDING, $intake->status);
        $this->assertSame($invoice->id, $intake->invoice_id);
        $this->assertDatabaseCount('order_intakes', 1);
    }

    public function test_approved_invoice_enqueues_oms_intake_once(): void
    {
        $admin = $this->createUserWithRole('admin');
        $invoice = Invoice::query()->create([
            'invoice_number' => 'INV-2026-002',
            'amount' => 2000,
            'status' => 'approved',
            'issued_at' => now(),
            'snapshot_json' => ['totals' => ['grand_total' => 2000]],
            'created_by' => $admin->id,
        ]);

        $service = app(OrderIntakeService::class);
        $first = $service->enqueueFromApproval($invoice, $admin);
        $second = $service->enqueueFromApproval($invoice, $admin);

        $this->assertSame($first->id, $second->id);
        $this->assertDatabaseCount('order_intakes', 1);
    }

    public function test_approved_invoice_cannot_be_edited_without_rejection(): void
    {
        $supervisor = $this->createUserWithRole('sales_supervisor');
        $invoice = Invoice::query()->create([
            'invoice_number' => 'INV-2026-003',
            'amount' => 100,
            'status' => 'approved',
            'snapshot_json' => ['totals' => ['grand_total' => 100]],
            'created_by' => $supervisor->id,
        ]);

        $this->assertFalse($supervisor->can('update', $invoice));
    }
}
