<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\StockLevel;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class InventoryItemWebController extends Controller
{
    public function __construct(private AuditService $audit) {}

    public function index(Request $request): View
    {
        abort_unless(auth()->user()?->canViewInventory(), 403);

        $itemType = $request->query('item_type');

        $items = Item::query()
            ->when($itemType, fn ($q) => $q->where('item_type', $itemType))
            ->orderByDesc('created_at')
            ->get();

        return view('inventory.items', [
            'items' => $items,
            'itemType' => $itemType,
            'canCreate' => auth()->user()->canCreateInventory(),
            'types' => Item::TYPES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()?->canCreateInventory(), 403);

        $validated = $request->validate([
            'sku' => ['required', 'string', 'min:1'],
            'name' => ['required', 'string', 'min:2'],
            'item_type' => ['required', 'in:'.implode(',', Item::TYPES)],
            'unit_of_measure' => ['nullable', 'string', 'min:1'],
            'reorder_level' => ['nullable', 'numeric', 'min:0'],
            'initial_stock' => ['nullable', 'numeric', 'min:0'],
        ]);

        DB::transaction(function () use ($request, $validated) {
            $item = Item::query()->create([
                'sku' => $validated['sku'],
                'name' => $validated['name'],
                'item_type' => $validated['item_type'],
                'unit_of_measure' => $validated['unit_of_measure'] ?? 'pcs',
                'reorder_level' => $validated['reorder_level'] ?? 0,
            ]);

            if (Schema::hasTable('stock_levels')) {
                StockLevel::query()->create([
                    'item_id' => $item->id,
                    'warehouse' => 'Main Warehouse',
                    'quantity_on_hand' => $validated['initial_stock'] ?? 0,
                ]);
            }

            $this->audit->log(auth()->user(), 'item', (int) $item->id, 'CREATE_ITEM', [
                'sku' => $validated['sku'],
                'name' => $validated['name'],
                'item_type' => $validated['item_type'],
            ], $request);
        });

        return redirect()->route('inventory.items')->with('status', 'Item created');
    }
}
