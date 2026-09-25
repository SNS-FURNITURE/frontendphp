<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\BillOfMaterial;
use App\Models\BomLine;
use App\Models\ProductionOrder;
use App\Models\SalesOrder;
use App\Services\AuditService;
use App\Services\InventoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ProductionOrderWebController extends Controller
{
    public function __construct(
        private AuditService $audit,
        private InventoryService $inventory,
    ) {}

    public function index(Request $request): View
    {
        abort_unless(auth()->user()?->canViewProduction(), 403);

        $status = $request->query('status');

        $orders = ProductionOrder::query()
            ->with(['bom.finishedItem', 'salesOrder'])
            ->when($status, fn ($q) => $q->where('status', $status))
            ->latestFirst()
            ->get();

        return view('production.index', [
            'orders' => $orders,
            'status' => $status,
            'statuses' => ProductionOrder::STATUSES,
            'boms' => BillOfMaterial::query()->orderBy('name')->get(),
            'salesOrders' => SalesOrder::query()->latestFirst()->limit(100)->get(),
            'canCreate' => auth()->user()->canCreateProduction(),
            'openCreate' => $request->boolean('create'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()?->canCreateProduction(), 403);

        $validated = $request->validate([
            'bom_id' => ['required', 'integer'],
            'quantity' => ['required', 'numeric', 'min:0.01'],
            'sales_order_id' => ['nullable', 'integer'],
        ]);

        $po = ProductionOrder::query()->create([
            'bom_id' => $validated['bom_id'],
            'quantity' => $validated['quantity'],
            'sales_order_id' => $validated['sales_order_id'] ?? null,
            'status' => 'planned',
            'assigned_to' => auth()->id(),
        ]);

        $this->audit->log(auth()->user(), 'production_order', (int) $po->id, 'CREATE_PRODUCTION_ORDER', [
            'bom_id' => $validated['bom_id'],
            'quantity' => $validated['quantity'],
        ], $request);

        return redirect()
            ->route('production.index')
            ->with('status', 'Production order launched successfully');
    }

    public function updateStatus(Request $request, ProductionOrder $order): RedirectResponse
    {
        abort_unless(auth()->user()?->canCreateProduction(), 403);

        $status = (string) $request->input('status');
        if (! in_array($status, ProductionOrder::STATUSES, true)) {
            return back()->withErrors(['status' => 'Invalid production order status']);
        }

        $prevStatus = $order->status;
        $user = auth()->user();

        DB::transaction(function () use ($order, $status, $prevStatus, $user) {
            if ($status === 'in_progress' && ! $order->started_at) {
                $order->started_at = now();
            }

            if ($status === 'completed' && $prevStatus !== 'completed') {
                $order->completed_at = now();
                $this->consumeBomAndReceiptFinished($order, $user);
            }

            $order->status = $status;
            $order->save();
        });

        $this->audit->log($user, 'production_order', (int) $order->id, 'UPDATE_PRODUCTION_STATUS', [
            'prev_status' => $prevStatus,
            'new_status' => $status,
        ], $request);

        return back()->with('status', 'Production status updated');
    }

    private function consumeBomAndReceiptFinished(ProductionOrder $po, $user): void
    {
        $bom = BillOfMaterial::query()->find($po->bom_id);
        if (! $bom) {
            return;
        }

        foreach (BomLine::query()->where('bom_id', $bom->id)->get() as $line) {
            $this->inventory->applyMovementUnsafe([
                'item_id' => $line->component_item_id,
                'movement_type' => 'out',
                'quantity' => (float) $line->quantity_required * (float) $po->quantity,
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
