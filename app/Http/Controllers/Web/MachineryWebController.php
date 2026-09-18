<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Machinery;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MachineryWebController extends Controller
{
    public function __construct(private AuditService $audit) {}

    public function index(): View
    {
        abort_unless(auth()->user()?->canViewMachinery(), 403);

        return view('machinery.index', [
            'machines' => Machinery::query()->orderBy('name')->get(),
            'statuses' => Machinery::STATUSES,
            'canCreate' => auth()->user()->canCreateMachinery(),
            'canEdit' => auth()->user()->canEditMachinery(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()?->canCreateMachinery(), 403);

        $validated = $request->validate([
            'machine_code' => ['required', 'string', 'min:2'],
            'name' => ['required', 'string', 'min:2'],
            'category' => ['required', 'string', 'min:1'],
            'workshop_location' => ['required', 'string', 'min:1'],
            'existing_qty' => ['required', 'integer', 'min:1'],
        ], [
            'machine_code.min' => 'Machine code is required (e.g. MAC-008)',
        ]);

        $machine = Machinery::query()->create([
            ...$validated,
            'status' => 'operational',
        ]);

        $this->audit->log(auth()->user(), 'machinery', (int) $machine->id, 'CREATE_MACHINERY', [
            'machine_code' => $validated['machine_code'],
            'name' => $validated['name'],
        ], $request);

        return redirect()
            ->route('machinery.index')
            ->with('status', 'New machinery added to equipment catalog');
    }

    public function updateStatus(Request $request, Machinery $machinery): RedirectResponse
    {
        abort_unless(auth()->user()?->canEditMachinery(), 403);

        $validated = $request->validate([
            'status' => ['required', 'in:operational,under_maintenance,idle'],
            'workshop_location' => ['nullable', 'string'],
        ]);

        $machinery->status = $validated['status'];
        if (! empty($validated['workshop_location'])) {
            $machinery->workshop_location = $validated['workshop_location'];
        }
        $machinery->save();

        $this->audit->log(auth()->user(), 'machinery', (int) $machinery->id, 'UPDATE_MACHINERY_STATUS', $validated, $request);

        return back()->with('status', 'Machinery status updated');
    }
}
