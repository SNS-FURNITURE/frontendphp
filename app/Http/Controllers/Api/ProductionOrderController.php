<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BillOfMaterial;
use App\Models\BomLine;
use App\Models\ProductionOrder;
use App\Models\SalesOrder;
use App\Models\User;
use App\Services\AuditService;
use App\Services\InventoryService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ProductionOrderController extends Controller
{
    public function __construct(
        private AuditService $audit,
        private InventoryService $inventory,
    ) {}

    public function index(Request $request): JsonResponse
    {
        if (! Schema::hasTable('production_orders')) {
            return ApiResponse::success([]);
        }

        $query = ProductionOrder::query()
            ->with(['bom', 'salesOrder'])
            ->orderByDesc('created_at');

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        $rows = $query->get()
            ->map(fn (ProductionOrder $po) => $po->toApiArray())
            ->values()
            ->all();

        return ApiResponse::success($rows);
    }

    public function store(Request $request): JsonResponse
    {
        $bomId = $request->input('bom_id');
        $quantity = $request->input('quantity');
        $salesOrderId = $request->input('sales_order_id');

        if (! $bomId || ! $quantity) {
            return ApiResponse::error('BOM ID and quantity are required', 'INVALID_INPUT', 400);
        }

        /** @var User|null $user */
        $user = $request->attributes->get('auth_user') ?? $request->user();

        $po = ProductionOrder::query()->create([
            'sales_order_id' => $salesOrderId ?: null,
            'bom_id' => $bomId,
            'quantity' => $quantity,
            'status' => 'planned',
            'assigned_to' => $request->input('assigned_to') ?: $user?->id,
        ]);

        $this->audit->log($user, 'production_order', (int) $po->id, 'CREATE_PRODUCTION_ORDER', [
            'bom_id' => $bomId,
            'quantity' => $quantity,
        ], $request);

        return ApiResponse::success([
            'id' => (int) $po->id,
            'bom_id' => (int) $bomId,
            'quantity' => $quantity,
            'status' => 'planned',
        ], null, 201);
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $status = $request->input('status');

        if (! in_array($status, ProductionOrder::STATUSES, true)) {
            return ApiResponse::error('Invalid production order status', 'INVALID_INPUT', 400);
        }

        $po = ProductionOrder::query()->find($id);
        if (! $po) {
            return ApiResponse::error('Production order not found', 'NOT_FOUND', 404);
        }

        /** @var User|null $user */
        $user = $request->attributes->get('auth_user') ?? $request->user();
        $prevStatus = $po->status;

        DB::transaction(function () use ($po, $status, $prevStatus, $user) {
            if ($status === 'in_progress' && ! $po->started_at) {
                $po->started_at = now();
            }

            if ($status === 'completed' && $prevStatus !== 'completed') {
                $po->completed_at = now();
                $this->consumeBomAndReceiptFinished($po, $user);
            }

            $po->status = $status;
            $po->save();
        });

        $this->audit->log($user, 'production_order', (int) $po->id, 'UPDATE_PRODUCTION_STATUS', [
            'prev_status' => $prevStatus,
            'new_status' => $status,
        ], $request);

        return ApiResponse::success([
            'id' => (int) $po->id,
            'status' => $status,
        ]);
    }

    private function consumeBomAndReceiptFinished(ProductionOrder $po, ?User $user): void
    {
        $bom = BillOfMaterial::query()->find($po->bom_id);
        if (! $bom) {
            return;
        }

        $lines = BomLine::query()->where('bom_id', $bom->id)->get();

        foreach ($lines as $line) {
            $consumedQty = (float) $line->quantity_required * (float) $po->quantity;
            $this->inventory->applyMovementUnsafe([
                'item_id' => $line->component_item_id,
                'movement_type' => 'out',
                'quantity' => $consumedQty,
                'reference_type' => 'production_order',
                'reference_id' => $po->id,
            ], $user);
        }

        $this->inventory->applyMovementUnsafe([
            'item_id' => $bom->finished_item_id,
            'movement_type' => 'in',
            'quantity' => $po->quantity,
            'reference_type' => 'production_order',
            'reference_id' => $po->id,
        ], $user);

        if ($po->sales_order_id) {
            SalesOrder::query()
                ->where('id', $po->sales_order_id)
                ->where('status', 'confirmed')
                ->update(['status' => 'in_production']);
        }
    }
}
