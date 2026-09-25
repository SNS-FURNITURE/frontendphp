<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\Party;
use App\Models\SalesOrder;
use App\Models\SalesOrderLine;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SalesOrderWebController extends Controller
{
    private const STATUSES = [
        'draft',
        'quoted',
        'confirmed',
        'in_production',
        'delivered',
        'cancelled',
    ];

    public function __construct(private AuditService $audit) {}

    public function index(Request $request): View
    {
        abort_unless(auth()->user()?->canViewSales(), 403);

        $status = $request->query('status');

        $orders = SalesOrder::query()
            ->with('customer')
            ->when($status, fn ($q) => $q->where('status', $status))
            ->latestFirst()
            ->paginate(25)
            ->withQueryString();

        $customers = Party::query()
            ->where('party_type', 'customer')
            ->orderBy('name')
            ->get();

        $items = Item::query()->orderBy('name')->limit(200)->get();

        return view('sales.orders.index', [
            'orders' => $orders,
            'status' => $status,
            'customers' => $customers,
            'items' => $items,
            'canCreate' => auth()->user()->canCreateSales(),
        ]);
    }

    public function show(SalesOrder $order): View
    {
        abort_unless(auth()->user()?->canViewSales(), 403);

        $order->load(['customer', 'lines.item']);
        $items = Item::query()->orderBy('name')->limit(200)->get();

        return view('sales.orders.show', [
            'order' => $order,
            'items' => $items,
            'statuses' => self::STATUSES,
            'canCreate' => auth()->user()->canCreateSales(),
            'canApprove' => auth()->user()->canCreateSales(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()?->canCreateSales(), 403);

        $validated = $request->validate([
            'customer_id' => ['required', 'integer'],
            'item_id' => ['nullable', 'integer'],
            'quantity' => ['nullable', 'numeric', 'min:0.01'],
            'unit_price' => ['nullable', 'numeric', 'min:0'],
            'wood_type' => ['nullable', 'string'],
            'finish' => ['nullable', 'string'],
            'dimensions' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ], [
            'customer_id.required' => 'Please select a customer',
        ]);

        $customer = Party::query()->find($validated['customer_id']);
        if (! $customer) {
            return back()->withErrors(['customer_id' => 'Customer not found'])->withInput();
        }



        $order = null;

        DB::transaction(function () use ($request, $validated, $customer, &$order) {
            $order = SalesOrder::query()->create([
                'customer_id' => $customer->id,
                'sales_rep_id' => auth()->id(),
                'status' => 'draft',
                'total_amount' => 0,
            ]);

            $this->audit->log(auth()->user(), 'sales_order', (int) $order->id, 'CREATE_SALES_ORDER', [
                'customer_id' => (int) $customer->id,
            ], $request);

            if (! empty($validated['item_id']) && ! empty($validated['quantity']) && isset($validated['unit_price'])) {
                $specs = array_filter([
                    'wood_type' => $validated['wood_type'] ?? null,
                    'finish' => $validated['finish'] ?? null,
                    'dimensions' => $validated['dimensions'] ?? null,
                    'notes' => $validated['notes'] ?? null,
                ], fn ($v) => $v !== null && $v !== '');

                SalesOrderLine::query()->create([
                    'sales_order_id' => $order->id,
                    'item_id' => $validated['item_id'],
                    'quantity' => $validated['quantity'],
                    'unit_price' => $validated['unit_price'],
                    'custom_specs' => $specs ?: null,
                ]);

                $grandTotal = (float) DB::table('sales_order_lines')
                    ->where('sales_order_id', $order->id)
                    ->selectRaw('COALESCE(SUM(quantity * unit_price), 0) as grand_total')
                    ->value('grand_total');

                DB::table('sales_orders')->where('id', $order->id)->update(['total_amount' => $grandTotal]);

                $this->audit->log(auth()->user(), 'sales_order', (int) $order->id, 'ADD_ORDER_LINE', [
                    'item_id' => $validated['item_id'],
                    'quantity' => $validated['quantity'],
                    'unit_price' => $validated['unit_price'],
                ], $request);
            }
        });

        return redirect()
            ->route('sales.orders.show', $order)
            ->with('status', 'Order request created successfully');
    }

    public function updateStatus(Request $request, SalesOrder $order): RedirectResponse
    {
        abort_unless(auth()->user()?->canCreateSales(), 403);

        $validated = $request->validate([
            'status' => ['required', 'in:'.implode(',', self::STATUSES)],
        ], [
            'status.in' => 'Invalid sales order status',
        ]);

        $order->status = $validated['status'];
        $order->save();

        $this->audit->log(auth()->user(), 'sales_order', (int) $order->id, 'UPDATE_ORDER_STATUS', [
            'new_status' => $validated['status'],
        ], $request);

        return redirect()
            ->route('sales.orders.show', $order)
            ->with('status', 'Order status updated');
    }

    public function addLine(Request $request, SalesOrder $order): RedirectResponse
    {
        abort_unless(auth()->user()?->canCreateSales(), 403);

        $validated = $request->validate([
            'item_id' => ['required', 'integer'],
            'quantity' => ['required', 'numeric', 'min:0.01'],
            'unit_price' => ['required', 'numeric', 'min:0'],
            'wood_type' => ['nullable', 'string'],
            'finish' => ['nullable', 'string'],
            'dimensions' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ], [
            'item_id.required' => 'Item, quantity, and unit price are required',
            'quantity.required' => 'Item, quantity, and unit price are required',
            'unit_price.required' => 'Item, quantity, and unit price are required',
        ]);

        $specs = array_filter([
            'wood_type' => $validated['wood_type'] ?? null,
            'finish' => $validated['finish'] ?? null,
            'dimensions' => $validated['dimensions'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ], fn ($v) => $v !== null && $v !== '');

        DB::transaction(function () use ($request, $order, $validated, $specs) {
            SalesOrderLine::query()->create([
                'sales_order_id' => $order->id,
                'item_id' => $validated['item_id'],
                'quantity' => $validated['quantity'],
                'unit_price' => $validated['unit_price'],
                'custom_specs' => $specs ?: null,
            ]);

            $grandTotal = (float) DB::table('sales_order_lines')
                ->where('sales_order_id', $order->id)
                ->selectRaw('COALESCE(SUM(quantity * unit_price), 0) as grand_total')
                ->value('grand_total');

            DB::table('sales_orders')->where('id', $order->id)->update(['total_amount' => $grandTotal]);

            $this->audit->log(auth()->user(), 'sales_order', (int) $order->id, 'ADD_ORDER_LINE', [
                'item_id' => $validated['item_id'],
                'quantity' => $validated['quantity'],
                'unit_price' => $validated['unit_price'],
            ], $request);
        });

        return redirect()
            ->route('sales.orders.show', $order)
            ->with('status', 'Order line added');
    }
}
