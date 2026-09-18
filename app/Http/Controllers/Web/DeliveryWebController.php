<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Delivery;
use App\Models\OutboundRecord;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DeliveryWebController extends Controller
{
    public function __construct(private AuditService $audit) {}

    public function index(Request $request): View
    {
        abort_unless(auth()->user()?->canViewDeliveries(), 403);

        $status = $request->query('status');

        $deliveries = Delivery::query()
            ->with(['creator', 'dispatcher'])
            ->when($status, fn ($q) => $q->where('status', $status))
            ->orderByDesc('created_at')
            ->get();

        return view('production.deliveries', [
            'deliveries' => $deliveries,
            'status' => $status,
            'statuses' => Delivery::STATUSES,
            'destinations' => Delivery::DESTINATIONS,
            'canCreate' => auth()->user()->canCreateDeliveries(),
            'canDispatch' => auth()->user()->canDispatchDeliveries(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()?->canCreateDeliveries(), 403);

        $validated = $request->validate([
            'product_name' => ['required', 'string', 'min:2'],
            'quantity' => ['required', 'integer', 'min:1'],
            'destination' => ['required', 'in:customer,showroom'],
            'recipient_name' => ['required', 'string', 'min:2'],
            'notes' => ['nullable', 'string'],
        ]);

        $delivery = Delivery::query()->create([
            ...$validated,
            'status' => 'planned',
            'created_by' => auth()->id(),
        ]);

        $this->audit->log(auth()->user(), 'delivery', (int) $delivery->id, 'CREATE_DELIVERY', [
            'product_name' => $validated['product_name'],
            'destination' => $validated['destination'],
        ], $request);

        return redirect()
            ->route('production.deliveries')
            ->with('status', 'Delivery for "'.$validated['product_name'].'" planned successfully');
    }

    public function updateStatus(Request $request, Delivery $delivery): RedirectResponse
    {
        abort_unless(auth()->user()?->canViewDeliveries(), 403);

        $status = (string) $request->input('status');
        if (! in_array($status, Delivery::STATUSES, true)) {
            return back()->withErrors(['status' => 'Invalid delivery status']);
        }

        if ($status === 'dispatched' && ! auth()->user()?->canDispatchDeliveries()) {
            return back()->withErrors(['status' => 'You do not have permission to count outbound stock.']);
        }

        $quantityDispatched = $request->input('quantity_dispatched');
        $user = auth()->user();

        DB::transaction(function () use ($delivery, $status, $quantityDispatched, $request, $user) {
            $delivery->status = $status;

            if ($request->filled('quantity_dispatched')) {
                $delivery->quantity_dispatched = (int) $quantityDispatched;
            }

            if ($request->filled('notes')) {
                $delivery->notes = $request->input('notes');
            }

            if ($status === 'dispatched') {
                $delivery->dispatched_by = $user->id;
                $delivery->dispatched_at = now();
            }

            $delivery->save();

            if ($status === 'dispatched') {
                $qtyOut = $delivery->quantity_dispatched ?: $delivery->quantity;
                OutboundRecord::query()->create([
                    'delivery_id' => $delivery->id,
                    'product_name' => $delivery->product_name,
                    'quantity_out' => $qtyOut,
                    'destination' => $delivery->destination,
                    'recipient_name' => $delivery->recipient_name,
                    'counted_by' => $user->id,
                    'counted_at' => now(),
                    'reference' => 'OUT-'.substr((string) (int) (microtime(true) * 1000), -6),
                ]);
            }
        });

        $this->audit->log($user, 'delivery', (int) $delivery->id, 'UPDATE_DELIVERY', [
            'status' => $status,
            'quantity_dispatched' => $quantityDispatched,
        ], $request);

        return back()->with('status', 'Delivery status updated');
    }
}
