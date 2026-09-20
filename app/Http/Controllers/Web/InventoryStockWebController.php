<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class InventoryStockWebController extends Controller
{
    public function index(): View
    {
        abort_unless(auth()->user()?->canViewInventory(), 403);

        $levels = [];
        if (Schema::hasTable('stock_levels')) {
            $levels = DB::select('
                SELECT sl.*, i.sku, i.name, i.item_type, i.unit_of_measure, i.reorder_level
                FROM stock_levels sl
                JOIN items i ON sl.item_id = i.id
                ORDER BY i.name ASC, sl.warehouse ASC
            ');
        }

        return view('inventory.stock', ['levels' => $levels]);
    }

    public function lowStock(): View
    {
        abort_unless(auth()->user()?->canViewInventory(), 403);

        $rows = [];
        if (Schema::hasTable('stock_levels')) {
            $rows = DB::select('
                SELECT i.*, SUM(sl.quantity_on_hand) as total_stock
                FROM items i
                LEFT JOIN stock_levels sl ON i.id = sl.item_id
                GROUP BY i.id
                HAVING total_stock <= i.reorder_level OR total_stock IS NULL
            ');
        }

        return view('inventory.low-stock', ['rows' => $rows]);
    }
}
