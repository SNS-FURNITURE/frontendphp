<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\DesignRecord;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class DesignWebController extends Controller
{
    public function __construct(private AuditService $audit) {}

    public function index(): View
    {
        abort_unless(auth()->user()?->canViewDesigns(), 403);

        $designs = DesignRecord::query()->with('designer')->latestFirst()->get();

        $statuses = Schema::hasTable('design_records')
            ? ['concept', 'in_progress', 'ready', 'revision_needed']
            : ['concept', 'in_progress', 'review', 'ready'];

        return view('designs.index', [
            'designs' => $designs,
            'statuses' => $statuses,
            'canCreate' => auth()->user()->canCreateDesigns(),
            'canEdit' => auth()->user()->canEditDesigns(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()?->canCreateDesigns(), 403);

        $validated = $request->validate([
            'title' => ['required', 'string', 'min:2'],
            'kind' => ['required', 'in:original,custom'],
            'design_source' => ['required', 'string', 'min:1'],
            'status' => ['required', 'string'],
            'notes' => ['nullable', 'string'],
            'file_url' => ['nullable', 'string'],
        ]);

        $design = DesignRecord::query()->create([
            'title' => $validated['title'],
            'kind' => $validated['kind'],
            'design_source' => $validated['design_source'],
            'status' => $validated['status'],
            'notes' => $validated['notes'] ?? null,
            'file_url' => $validated['file_url'] ?? null,
            'designer_id' => auth()->id(),
        ]);

        $this->audit->log(auth()->user(), 'design_record', (int) $design->id, 'CREATE_DESIGN', [
            'title' => $validated['title'],
            'kind' => $validated['kind'],
        ], $request);

        return redirect()
            ->route('designs.index')
            ->with('status', 'Design "'.$validated['title'].'" created successfully');
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        abort_unless(auth()->user()?->canEditDesigns(), 403);

        $design = DesignRecord::query()->findOrFail($id);
        $validated = $request->validate([
            'title' => ['sometimes', 'string', 'min:2'],
            'kind' => ['sometimes', 'in:original,custom'],
            'status' => ['sometimes', 'string'],
            'design_source' => ['sometimes', 'nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'file_url' => ['nullable', 'string'],
        ]);

        $design->fill($validated);
        $design->save();

        $this->audit->log(auth()->user(), 'design_record', (int) $design->id, 'UPDATE_DESIGN', $validated, $request);

        return back()->with('status', 'Design updated');
    }
}
