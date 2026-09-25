<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BillOfMaterial;
use App\Models\BomLine;
use App\Models\User;
use App\Services\AuditService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class BomController extends Controller
{
    public function __construct(private AuditService $audit) {}

    public function index(): JsonResponse
    {
        if (! Schema::hasTable('bill_of_materials')) {
            return ApiResponse::success([]);
        }

        $boms = BillOfMaterial::query()
            ->with(['finishedItem', 'lines.componentItem'])
            ->latestFirst()
            ->get()
            ->map(fn (BillOfMaterial $bom) => $bom->toApiArray())
            ->values()
            ->all();

        return ApiResponse::success($boms);
    }

    public function show(int $id): JsonResponse
    {
        $bom = BillOfMaterial::query()
            ->with(['finishedItem', 'lines.componentItem'])
            ->find($id);

        if (! $bom) {
            return ApiResponse::error('BOM not found', 'NOT_FOUND', 404);
        }

        return ApiResponse::success($bom->toApiArray());
    }

    public function store(Request $request): JsonResponse
    {
        $finishedItemId = $request->input('finished_item_id');
        $name = $request->input('name');
        $version = $request->input('version', 1);
        $lines = $request->input('lines', []);

        if (! $finishedItemId || ! $name) {
            return ApiResponse::error('finished_item_id and name are required', 'INVALID_INPUT', 400);
        }

        $bomId = null;

        DB::transaction(function () use ($finishedItemId, $name, $version, $lines, &$bomId) {
            $bom = BillOfMaterial::query()->create([
                'finished_item_id' => $finishedItemId,
                'name' => $name,
                'version' => $version,
            ]);
            $bomId = $bom->id;

            foreach ($lines as $line) {
                BomLine::query()->create([
                    'bom_id' => $bomId,
                    'component_item_id' => $line['component_item_id'],
                    'quantity_required' => $line['quantity_required'],
                ]);
            }
        });

        /** @var User|null $user */
        $user = $request->attributes->get('auth_user') ?? $request->user();
        $this->audit->log($user, 'bom', (int) $bomId, 'CREATE_BOM', [
            'name' => $name,
            'finished_item_id' => $finishedItemId,
        ], $request);

        return ApiResponse::success([
            'id' => (int) $bomId,
            'finished_item_id' => (int) $finishedItemId,
            'name' => $name,
            'version' => (int) $version,
        ], null, 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $bom = BillOfMaterial::query()->find($id);
        if (! $bom) {
            return ApiResponse::error('BOM not found', 'NOT_FOUND', 404);
        }

        $updates = [];
        foreach (['name', 'version', 'finished_item_id'] as $field) {
            if ($request->exists($field)) {
                $updates[$field] = $request->input($field);
            }
        }

        if ($updates === []) {
            return ApiResponse::error('No fields to update', 'INVALID_INPUT', 400);
        }

        $bom->fill($updates);
        $bom->save();

        /** @var User|null $user */
        $user = $request->attributes->get('auth_user') ?? $request->user();
        $this->audit->log($user, 'bom', (int) $bom->id, 'UPDATE_BOM', $updates, $request);

        $bom->load(['finishedItem', 'lines.componentItem']);

        return ApiResponse::success($bom->toApiArray());
    }

    public function destroy(int $id): JsonResponse
    {
        $bom = BillOfMaterial::query()->find($id);
        if (! $bom) {
            return ApiResponse::error('BOM not found', 'NOT_FOUND', 404);
        }

        $bomId = (int) $bom->id;
        $bom->delete();

        /** @var User|null $user */
        $user = request()->attributes->get('auth_user') ?? request()->user();
        $this->audit->log($user, 'bom', $bomId, 'DELETE_BOM', null, request());

        return ApiResponse::success(['id' => $bomId, 'deleted' => true]);
    }

    public function storeLine(Request $request, int $id): JsonResponse
    {
        $bom = BillOfMaterial::query()->find($id);
        if (! $bom) {
            return ApiResponse::error('BOM not found', 'NOT_FOUND', 404);
        }

        $componentItemId = $request->input('component_item_id');
        $quantityRequired = $request->input('quantity_required');

        if (! $componentItemId || ! $quantityRequired) {
            return ApiResponse::error(
                'component_item_id and quantity_required are required',
                'INVALID_INPUT',
                400,
            );
        }

        $line = BomLine::query()->create([
            'bom_id' => $bom->id,
            'component_item_id' => $componentItemId,
            'quantity_required' => $quantityRequired,
        ]);
        $line->load('componentItem');

        /** @var User|null $user */
        $user = $request->attributes->get('auth_user') ?? $request->user();
        $this->audit->log($user, 'bom_line', (int) $line->id, 'CREATE_BOM_LINE', [
            'bom_id' => $bom->id,
            'component_item_id' => $componentItemId,
        ], $request);

        return ApiResponse::success($line->toApiArray(), null, 201);
    }

    public function updateLine(Request $request, int $id, int $lineId): JsonResponse
    {
        $line = BomLine::query()->where('bom_id', $id)->where('id', $lineId)->first();
        if (! $line) {
            return ApiResponse::error('BOM line not found', 'NOT_FOUND', 404);
        }

        $updates = [];
        foreach (['component_item_id', 'quantity_required'] as $field) {
            if ($request->exists($field)) {
                $updates[$field] = $request->input($field);
            }
        }

        if ($updates === []) {
            return ApiResponse::error('No fields to update', 'INVALID_INPUT', 400);
        }

        $line->fill($updates);
        $line->save();
        $line->load('componentItem');

        /** @var User|null $user */
        $user = $request->attributes->get('auth_user') ?? $request->user();
        $this->audit->log($user, 'bom_line', (int) $line->id, 'UPDATE_BOM_LINE', $updates, $request);

        return ApiResponse::success($line->toApiArray());
    }

    public function destroyLine(int $id, int $lineId): JsonResponse
    {
        $line = BomLine::query()->where('bom_id', $id)->where('id', $lineId)->first();
        if (! $line) {
            return ApiResponse::error('BOM line not found', 'NOT_FOUND', 404);
        }

        $deletedId = (int) $line->id;
        $line->delete();

        /** @var User|null $user */
        $user = request()->attributes->get('auth_user') ?? request()->user();
        $this->audit->log($user, 'bom_line', $deletedId, 'DELETE_BOM_LINE', [
            'bom_id' => $id,
        ], request());

        return ApiResponse::success(['id' => $deletedId, 'deleted' => true]);
    }
}
