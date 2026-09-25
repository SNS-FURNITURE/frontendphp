<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\StockLevel;
use App\Models\User;
use App\Services\AuditService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ItemController extends Controller
{
    public function __construct(private AuditService $audit) {}

    public function index(Request $request): JsonResponse
    {
        $query = Item::query()->latestFirst();

        if ($itemType = $request->query('item_type')) {
            $query->where('item_type', $itemType);
        }

        return ApiResponse::success($query->get()->values()->all());
    }

    public function store(Request $request): JsonResponse
    {
        $sku = $request->input('sku');
        $name = $request->input('name');
        $itemType = $request->input('item_type');
        $uom = \App\Support\UnitOfMeasure::normalize($request->input('unit_of_measure'));
        $reorder = $request->input('reorder_level', 0);
        $initialStock = $request->input('initial_stock', 0);

        if (! $sku || ! $name || ! $itemType) {
            return ApiResponse::error('SKU, name, and item_type are required', 'INVALID_INPUT', 400);
        }

        $item = null;

        DB::transaction(function () use ($sku, $name, $itemType, $uom, $reorder, $initialStock, &$item) {
            $item = Item::query()->create([
                'sku' => $sku,
                'name' => $name,
                'item_type' => $itemType,
                'unit_of_measure' => $uom,
                'reorder_level' => $reorder,
            ]);

            if (Schema::hasTable('stock_levels')) {
                StockLevel::query()->create([
                    'item_id' => $item->id,
                    'warehouse' => 'Main Warehouse',
                    'quantity_on_hand' => $initialStock,
                ]);
            }
        });

        /** @var User|null $user */
        $user = $request->attributes->get('auth_user') ?? $request->user();
        $this->audit->log($user, 'item', (int) $item->id, 'CREATE_ITEM', [
            'sku' => $sku,
            'name' => $name,
            'item_type' => $itemType,
        ], $request);

        return ApiResponse::success($item->fresh(), null, 201);
    }

    public function lowStock(): JsonResponse
    {
        if (! Schema::hasTable('stock_levels')) {
            return ApiResponse::success([]);
        }

        $rows = DB::select('
            SELECT i.*, SUM(sl.quantity_on_hand) as total_stock
            FROM items i
            LEFT JOIN stock_levels sl ON i.id = sl.item_id
            GROUP BY i.id
            HAVING total_stock <= i.reorder_level OR total_stock IS NULL
        ');

        return ApiResponse::success($rows);
    }

    public function stock(int $id): JsonResponse
    {
        if (! Schema::hasTable('stock_levels')) {
            return ApiResponse::success([]);
        }

        $rows = StockLevel::query()->where('item_id', $id)->get()->values()->all();

        return ApiResponse::success($rows);
    }
}
