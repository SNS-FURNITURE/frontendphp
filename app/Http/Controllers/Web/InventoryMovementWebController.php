<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Services\AuditService;
use App\Services\InventoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class InventoryMovementWebController extends Controller
{
    public function __construct(
        private AuditService $audit,
        private InventoryService $inventory,
    ) {}

    public function index(Request $request): View
    {
        abort_unless(auth()->user()?->canViewInventory(), 403);

        $page = max(1, (int) $request->query('page', 1));
        $perPage = 50;
        $movements = [];
        $total = 0;

        if (Schema::hasTable('stock_movements')) {
            $total = (int) DB::table('stock_movements')->count();
            $movements = DB::select(
                'SELECT sm.*, i.name as item_name, i.sku as item_sku
                 FROM stock_movements sm
                 JOIN items i ON sm.item_id = i.id
                 ORDER BY sm.created_at DESC, sm.id DESC
                 LIMIT ? OFFSET ?',
                [$perPage, ($page - 1) * $perPage],
            );
        }

        return view('inventory.movements', [
            'movements' => $movements,
            'items' => Item::query()->orderBy('name')->get(),
            'page' => $page,
            'perPage' => $perPage,
            'total' => $total,
            'canCreate' => auth()->user()->canCreateInventory(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()?->canCreateInventory(), 403);

        if (! Schema::hasTable('stock_movements') || ! Schema::hasTable('stock_levels')) {
            return back()->withErrors(['item_id' => 'Stock tables are not available']);
        }

        $validated = $request->validate([
            'item_id' => ['required', 'integer', 'min:1'],
            'movement_type' => ['required', 'in:in,out,adjustment'],
            'quantity' => ['required', 'numeric', 'min:0.01'],
            'warehouse' => ['nullable', 'string', 'min:1'],
            'reference_type' => ['nullable', 'string'],
            'reference_id' => ['nullable', 'integer'],
        ]);

        $this->inventory->applyMovement([
            'item_id' => $validated['item_id'],
            'movement_type' => $validated['movement_type'],
            'quantity' => $validated['quantity'],
            'warehouse' => $validated['warehouse'] ?? 'Main Warehouse',
            'reference_type' => $validated['reference_type'] ?? null,
            'reference_id' => $validated['reference_id'] ?? null,
        ], auth()->user());

        $this->audit->log(auth()->user(), 'stock_movement', (int) $validated['item_id'], 'STOCK_ADJUSTMENT', [
            'movement_type' => $validated['movement_type'],
            'quantity' => $validated['quantity'],
            'warehouse' => $validated['warehouse'] ?? 'Main Warehouse',
        ], $request);

        return redirect()->route('inventory.movements')->with('status', 'Movement recorded');
    }
}
