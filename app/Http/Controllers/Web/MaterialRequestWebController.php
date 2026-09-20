<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\MaterialRequest;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class MaterialRequestWebController extends Controller
{
    public function __construct(private AuditService $audit) {}

    public function index(Request $request): View
    {
        abort_unless(auth()->user()?->canViewInventory(), 403);

        $status = $request->query('status');

        $requests = MaterialRequest::query()
            ->with(['requester', 'fulfiller'])
            ->when($status, fn ($q) => $q->where('status', $status))
            ->orderByDesc('created_at')
            ->get();

        return view('inventory.material-requests', [
            'requests' => $requests,
            'status' => $status,
            'statuses' => MaterialRequest::STATUSES,
            'canCreate' => auth()->user()->canCreateInventory(),
            'canEdit' => auth()->user()->canCreateInventory(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()?->canCreateInventory(), 403);

        $validated = $request->validate([
            'title' => ['required', 'string', 'min:2'],
            'item_name' => ['required', 'string', 'min:2'],
            'quantity' => ['required', 'integer', 'min:1'],
            'unit' => ['nullable', 'string'],
            'urgency' => ['nullable', 'in:standard,high,urgent'],
            'notes' => ['nullable', 'string'],
            'item_id' => ['nullable', 'integer'],
        ]);

        $proofNote = null;
        $extras = [];
        if (! empty($validated['unit'])) {
            $extras[] = 'Unit: '.$validated['unit'];
        }
        if (! empty($validated['urgency'])) {
            $extras[] = 'Urgency: '.$validated['urgency'];
        }
        if (! empty($validated['notes'])) {
            $extras[] = $validated['notes'];
        }
        if ($extras !== []) {
            $proofNote = implode('. ', $extras);
        }

        $payload = [
            'title' => $validated['title'],
            'item_name' => $validated['item_name'],
            'quantity' => $validated['quantity'],
            'status' => 'pending',
            'requested_by' => auth()->id(),
            'proof_note' => $proofNote,
        ];

        if (Schema::hasColumn('material_requests', 'item_id') && ! empty($validated['item_id'])) {
            $payload['item_id'] = $validated['item_id'];
        }

        $mr = MaterialRequest::query()->create($payload);

        $this->audit->log(auth()->user(), 'material_request', (int) $mr->id, 'CREATE_MATERIAL_REQUEST', [
            'title' => $validated['title'],
            'item_name' => $validated['item_name'],
            'quantity' => $validated['quantity'],
        ], $request);

        return redirect()
            ->route('material-requests.index')
            ->with('status', 'Material request "'.$validated['title'].'" submitted');
    }

    public function update(Request $request, MaterialRequest $materialRequest): RedirectResponse
    {
        abort_unless(auth()->user()?->canCreateInventory(), 403);

        $validated = $request->validate([
            'status' => ['nullable', 'in:'.implode(',', MaterialRequest::STATUSES)],
            'proof_note' => ['nullable', 'string'],
            'title' => ['nullable', 'string', 'min:2'],
            'item_name' => ['nullable', 'string', 'min:2'],
            'quantity' => ['nullable', 'integer', 'min:1'],
        ]);

        $updates = array_filter(
            $validated,
            fn ($v) => $v !== null && $v !== '',
        );

        if ($updates === []) {
            return back()->withErrors(['status' => 'No fields to update']);
        }

        if (($updates['status'] ?? null) === 'fulfilled') {
            $updates['fulfilled_by'] = auth()->id();
        }

        $materialRequest->fill($updates);
        $materialRequest->save();

        $this->audit->log(auth()->user(), 'material_request', (int) $materialRequest->id, 'UPDATE_MATERIAL_REQUEST', $updates, $request);

        return back()->with('status', 'Material request status updated');
    }
}
