<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MaterialRequest;
use App\Models\User;
use App\Services\AuditService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class MaterialRequestController extends Controller
{
    public function __construct(private AuditService $audit) {}

    public function index(Request $request): JsonResponse
    {
        if (! Schema::hasTable('material_requests')) {
            return ApiResponse::success([]);
        }

        $query = MaterialRequest::query()->latestFirst();

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        $rows = $query->get()
            ->map(fn (MaterialRequest $mr) => $mr->toApiArray())
            ->values()
            ->all();

        return ApiResponse::success($rows);
    }

    public function store(Request $request): JsonResponse
    {
        $title = $request->input('title');
        $itemName = $request->input('item_name');
        $quantity = $request->input('quantity');

        if (! $title || ! $itemName || ! $quantity) {
            return ApiResponse::error(
                'Title, item_name, and quantity are required',
                'INVALID_INPUT',
                400,
            );
        }

        /** @var User|null $user */
        $user = $request->attributes->get('auth_user') ?? $request->user();

        $payload = [
            'title' => $title,
            'item_name' => $itemName,
            'quantity' => $quantity,
            'status' => 'pending',
            'requested_by' => $user?->id,
        ];

        if (Schema::hasColumn('material_requests', 'item_id') && $request->exists('item_id')) {
            $payload['item_id'] = $request->input('item_id');
        }

        $mr = MaterialRequest::query()->create($payload);

        $this->audit->log($user, 'material_request', (int) $mr->id, 'CREATE_MATERIAL_REQUEST', [
            'title' => $title,
            'item_name' => $itemName,
            'quantity' => $quantity,
        ], $request);

        return ApiResponse::success($mr->toApiArray(), null, 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $mr = MaterialRequest::query()->find($id);
        if (! $mr) {
            return ApiResponse::error('Not found', 'NOT_FOUND', 404);
        }

        $body = $request->all();
        $allowed = ['title', 'item_name', 'quantity', 'status', 'fulfilled_by', 'proof_note'];
        if (Schema::hasColumn('material_requests', 'item_id')) {
            $allowed[] = 'item_id';
        }

        $updates = [];
        foreach ($allowed as $field) {
            if (array_key_exists($field, $body)) {
                $updates[$field] = $body[$field];
            }
        }

        if ($updates === []) {
            return ApiResponse::error('No fields to update', 'INVALID_INPUT', 400);
        }

        $mr->fill($updates);
        $mr->save();

        /** @var User|null $user */
        $user = $request->attributes->get('auth_user') ?? $request->user();
        $this->audit->log($user, 'material_request', (int) $mr->id, 'UPDATE_MATERIAL_REQUEST', $body, $request);

        return ApiResponse::success($mr->fresh()->toApiArray());
    }
}
