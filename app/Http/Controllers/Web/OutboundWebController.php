<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Delivery;
use App\Models\OutboundRecord;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OutboundWebController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless(auth()->user()?->canViewDeliveries(), 403);

        $records = OutboundRecord::query()
            ->with(['counter', 'delivery'])
            ->when(
                $request->query('delivery_id'),
                fn ($q, $id) => $q->where('delivery_id', $id),
            )
            ->latestFirst('counted_at')
            ->get();

        $pending = Delivery::query()
            ->where('status', 'ready_for_dispatch')
            ->latestFirst()
            ->get();

        return view('inventory.outbound', [
            'records' => $records,
            'pending' => $pending,
            'canDispatch' => auth()->user()->canDispatchDeliveries(),
        ]);
    }
}
