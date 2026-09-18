<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DesignRecord;
use App\Models\User;
use App\Services\AuditService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class DesignController extends Controller
{
    public function __construct(private AuditService $audit) {}

    public function index(Request $request): JsonResponse
    {
        $query = DesignRecord::query()->with('designer')->orderByDesc('created_at');

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }
        if ($designerId = $request->query('designer_id')) {
            $query->where('designer_id', $designerId);
        }

        return ApiResponse::success(
            $query->get()->map(fn (DesignRecord $d) => $d->toApiArray())->values()->all()
        );
    }

    public function show(int $id): JsonResponse
    {
        $design = DesignRecord::query()->with('designer')->find($id);
        if (! $design) {
            return ApiResponse::error('Design record not found', 'NOT_FOUND', 404);
        }

        return ApiResponse::success($design->toApiArray());
    }

    public function store(Request $request): JsonResponse
    {
        $title = $request->input('title');
        if (! $title) {
            return ApiResponse::error('Title is required', 'INVALID_INPUT', 400);
        }

        /** @var User|null $user */
        $user = $request->attributes->get('auth_user') ?? $request->user();

        $status = (string) ($request->input('status') ?: 'concept');
        $status = $this->normalizeStatus($status);

        $design = DesignRecord::query()->create([
            'title' => $title,
            'kind' => $request->input('kind') ?: 'original',
            'status' => $status,
            'designer_id' => $request->input('designer_id') ?: $user?->id,
            'deal_id' => $request->input('deal_id'),
            'design_source' => $request->input('design_source'),
            'notes' => $request->input('notes'),
            'file_url' => $request->input('file_url'),
        ]);

        $this->audit->log($user, 'design_record', (int) $design->id, 'CREATE_DESIGN', [
            'title' => $title,
            'kind' => $design->kind,
        ], $request);

        return ApiResponse::success($design->fresh(['designer'])->toApiArray(), null, 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $design = DesignRecord::query()->find($id);
        if (! $design) {
            return ApiResponse::error('Design record not found', 'NOT_FOUND', 404);
        }

        $body = $request->all();
        $allowed = ['title', 'kind', 'status', 'designer_id', 'deal_id', 'design_source', 'notes', 'file_url'];
        $updates = [];

        foreach ($allowed as $field) {
            if (array_key_exists($field, $body)) {
                $updates[$field] = $field === 'status' ? $this->normalizeStatus((string) $body[$field]) : $body[$field];
            }
        }

        if ($updates === []) {
            return ApiResponse::error('No fields to update', 'INVALID_INPUT', 400);
        }

        $design->fill($updates);
        $design->save();

        /** @var User|null $user */
        $user = $request->attributes->get('auth_user') ?? $request->user();
        $this->audit->log($user, 'design_record', (int) $design->id, 'UPDATE_DESIGN', $body, $request);

        return ApiResponse::success($design->fresh(['designer'])->toApiArray());
    }

    private function normalizeStatus(string $status): string
    {
        if ($status === 'revision_needed' && ! Schema::hasTable('design_records')) {
            return 'review';
        }

        if ($status === 'review' && Schema::hasTable('design_records')) {
            return 'revision_needed';
        }

        return $status;
    }
}
