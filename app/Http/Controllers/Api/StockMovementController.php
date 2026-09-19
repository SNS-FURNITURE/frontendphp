<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditService;
use App\Services\InventoryService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class StockMovementController extends Controller
{
    public function __construct(
        private AuditService $audit,
        private InventoryService $inventory,
    ) {}

    public function index(Request $request): JsonResponse
    {
        if (! Schema::hasTable('stock_movements')) {
            return ApiResponse::success([]);
        }

        $rawLimit = (float) $request->query('limit');
        $rawPage = (float) $request->query('page');
        $limit = is_finite($rawLimit) && $rawLimit > 0 ? (int) min($rawLimit, 500) : null;
        $page = is_finite($rawPage) && $rawPage > 0 ? (int) floor($rawPage) : 1;

        $meta = null;
        if ($limit !== null) {
            $total = (int) DB::table('stock_movements')->count();
            $meta = [
                'page' => $page,
                'per_page' => $limit,
                'total' => $total,
            ];
        }

        $sql = 'SELECT sm.*, i.name as item_name, i.sku as item_sku
                FROM stock_movements sm
                JOIN items i ON sm.item_id = i.id
                ORDER BY sm.created_at DESC, sm.id DESC';
        $bindings = [];

        if ($limit !== null) {
            $sql .= ' LIMIT ? OFFSET ?';
            $bindings = [$limit, ($page - 1) * $limit];
        }

        $rows = DB::select($sql, $bindings);

        $data = array_map(fn ($r) => [
            'id' => (int) $r->id,
            'item_id' => (int) $r->item_id,
            'movement_type' => $r->movement_type,
            'quantity' => $r->quantity,
            'reference_type' => $r->reference_type,
            'reference_id' => $r->reference_id,
            'created_by' => $r->created_by,
            'created_at' => $r->created_at,
            'item' => [
                'id' => (int) $r->item_id,
                'sku' => $r->item_sku,
                'name' => $r->item_name,
            ],
        ], $rows);

        return ApiResponse::success($data, $meta);
    }

    public function store(Request $request): JsonResponse
    {
        if (! Schema::hasTable('stock_movements') || ! Schema::hasTable('stock_levels')) {
            return ApiResponse::error('Stock tables are not available', 'NOT_FOUND', 404);
        }

        $itemId = $request->input('item_id');
        $movementType = $request->input('movement_type');
        $quantity = $request->input('quantity');
        $warehouse = $request->input('warehouse', 'Main Warehouse');
        $referenceType = $request->input('reference_type');
        $referenceId = $request->input('reference_id');

        if (! $itemId || ! $movementType || ! $quantity) {
            return ApiResponse::error(
                'item_id, movement_type, and quantity are required',
                'INVALID_INPUT',
                400,
            );
        }

        /** @var User|null $user */
        $user = $request->attributes->get('auth_user') ?? $request->user();

        $this->inventory->applyMovement([
            'item_id' => $itemId,
            'movement_type' => $movementType,
            'quantity' => $quantity,
            'warehouse' => $warehouse,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
        ], $user);

        $this->audit->log($user, 'stock_movement', (int) $itemId, 'STOCK_ADJUSTMENT', [
            'movement_type' => $movementType,
            'quantity' => $quantity,
            'warehouse' => $warehouse,
        ], $request);

        return ApiResponse::success(
            ['message' => 'Stock movement recorded successfully'],
            null,
            201,
        );
    }
}
