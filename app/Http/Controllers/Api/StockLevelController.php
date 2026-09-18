<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class StockLevelController extends Controller
{
    public function index(): JsonResponse
    {
        if (! Schema::hasTable('stock_levels')) {
            return ApiResponse::success([]);
        }

        $rows = DB::select('
            SELECT sl.id, sl.item_id, sl.warehouse, sl.quantity_on_hand, sl.updated_at,
                   i.sku, i.name, i.item_type, i.unit_of_measure, i.reorder_level, i.created_at as item_created_at
            FROM stock_levels sl
            JOIN items i ON sl.item_id = i.id
            ORDER BY i.name ASC, sl.warehouse ASC
        ');

        $levels = array_map(fn ($row) => [
            'id' => (int) $row->id,
            'item_id' => (int) $row->item_id,
            'warehouse' => $row->warehouse,
            'quantity_on_hand' => $row->quantity_on_hand,
            'updated_at' => $row->updated_at,
            'item' => [
                'id' => (int) $row->item_id,
                'sku' => $row->sku,
                'name' => $row->name,
                'item_type' => $row->item_type,
                'unit_of_measure' => $row->unit_of_measure,
                'reorder_level' => $row->reorder_level,
                'created_at' => $row->item_created_at,
            ],
        ], $rows);

        return ApiResponse::success($levels);
    }
}
