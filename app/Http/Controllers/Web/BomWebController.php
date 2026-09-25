<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\BillOfMaterial;
use App\Models\BomLine;
use App\Models\Item;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class BomWebController extends Controller
{
    public function __construct(private AuditService $audit) {}

    public function index(): View
    {
        abort_unless(auth()->user()?->canViewProduction(), 403);

        $boms = BillOfMaterial::query()
            ->with(['finishedItem', 'lines.componentItem'])
            ->latestFirst()
            ->get();

        $finishedItems = Item::query()
            ->where('item_type', 'finished_good')
            ->orderBy('name')
            ->get();

        $components = Item::query()
            ->whereIn('item_type', ['raw_material', 'component'])
            ->orderBy('name')
            ->get();

        return view('manufacturing.boms', [
            'boms' => $boms,
            'finishedItems' => $finishedItems,
            'components' => $components,
            'canCreate' => auth()->user()->canCreateProduction(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()?->canCreateProduction(), 403);

        $validated = $request->validate([
            'finished_item_id' => ['required', 'integer'],
            'name' => ['required', 'string', 'min:1'],
            'version' => ['nullable', 'integer', 'min:1'],
            'component_item_id' => ['required', 'array', 'min:1'],
            'component_item_id.*' => ['required', 'integer'],
            'quantity_required' => ['required', 'array', 'min:1'],
            'quantity_required.*' => ['required', 'numeric', 'min:0.01'],
        ]);

        $bomId = null;

        DB::transaction(function () use ($request, $validated, &$bomId) {
            $bom = BillOfMaterial::query()->create([
                'finished_item_id' => $validated['finished_item_id'],
                'name' => $validated['name'],
                'version' => $validated['version'] ?? 1,
            ]);
            $bomId = $bom->id;

            foreach ($validated['component_item_id'] as $i => $componentId) {
                BomLine::query()->create([
                    'bom_id' => $bom->id,
                    'component_item_id' => $componentId,
                    'quantity_required' => $validated['quantity_required'][$i],
                ]);
            }

            $this->audit->log(auth()->user(), 'bom', (int) $bom->id, 'CREATE_BOM', [
                'name' => $validated['name'],
                'finished_item_id' => $validated['finished_item_id'],
            ], $request);
        });

        return redirect()->route('manufacturing.boms')->with('status', 'BOM created');
    }

    public function update(Request $request, BillOfMaterial $bom): RedirectResponse
    {
        abort_unless(auth()->user()?->canCreateProduction(), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'min:1'],
            'version' => ['nullable', 'integer', 'min:1'],
        ]);

        $bom->fill([
            'name' => $validated['name'],
            'version' => $validated['version'] ?? $bom->version,
        ]);
        $bom->save();

        $this->audit->log(auth()->user(), 'bom', (int) $bom->id, 'UPDATE_BOM', $validated, $request);

        return back()->with('status', 'BOM updated');
    }

    public function destroy(Request $request, BillOfMaterial $bom): RedirectResponse|JsonResponse
    {
        abort_unless(auth()->user()?->canCreateProduction(), 403);

        $id = (int) $bom->id;
        $bom->delete();

        $this->audit->log(auth()->user(), 'bom', $id, 'DELETE_BOM', null, $request);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'BOM deleted',
                'id' => $id,
            ]);
        }

        return redirect()->route('manufacturing.boms')->with('status', 'BOM deleted');
    }

    public function storeLine(Request $request, BillOfMaterial $bom): RedirectResponse
    {
        abort_unless(auth()->user()?->canCreateProduction(), 403);

        $validated = $request->validate([
            'component_item_id' => ['required', 'integer'],
            'quantity_required' => ['required', 'numeric', 'min:0.01'],
        ]);

        $line = BomLine::query()->create([
            'bom_id' => $bom->id,
            'component_item_id' => $validated['component_item_id'],
            'quantity_required' => $validated['quantity_required'],
        ]);

        $this->audit->log(auth()->user(), 'bom_line', (int) $line->id, 'CREATE_BOM_LINE', [
            'bom_id' => $bom->id,
        ], $request);

        return back()->with('status', 'BOM line added');
    }

    public function updateLine(Request $request, BillOfMaterial $bom, BomLine $line): RedirectResponse
    {
        abort_unless(auth()->user()?->canCreateProduction(), 403);
        abort_unless((int) $line->bom_id === (int) $bom->id, 404);

        $validated = $request->validate([
            'quantity_required' => ['required', 'numeric', 'min:0.01'],
        ]);

        $line->quantity_required = $validated['quantity_required'];
        $line->save();

        $this->audit->log(auth()->user(), 'bom_line', (int) $line->id, 'UPDATE_BOM_LINE', $validated, $request);

        return back()->with('status', 'BOM line updated');
    }

    public function destroyLine(Request $request, BillOfMaterial $bom, BomLine $line): RedirectResponse|JsonResponse
    {
        abort_unless(auth()->user()?->canCreateProduction(), 403);
        abort_unless((int) $line->bom_id === (int) $bom->id, 404);

        $id = (int) $line->id;
        $line->delete();

        $this->audit->log(auth()->user(), 'bom_line', $id, 'DELETE_BOM_LINE', [
            'bom_id' => $bom->id,
        ], $request);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'BOM line removed',
                'id' => $id,
            ]);
        }

        return back()->with('status', 'BOM line removed');
    }
}
