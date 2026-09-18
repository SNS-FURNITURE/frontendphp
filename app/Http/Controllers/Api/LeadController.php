<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\User;
use App\Services\AuditService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeadController extends Controller
{
    private const PUBLIC_SOURCES = ['quote_form', 'contact_form', 'website'];

    private const PATCH_FIELDS = [
        'name', 'phone', 'email', 'source', 'notes', 'product_interest',
        'room_details', 'material_preference', 'design_source', 'status', 'verified_by',
    ];

    public function __construct(private AuditService $audit) {}

    public function index(Request $request): JsonResponse
    {
        $query = Lead::query()->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }
        if ($request->filled('created_by')) {
            $query->where('created_by', $request->query('created_by'));
        }

        $rawLimit = (int) $request->query('limit');
        $rawPage = (int) $request->query('page');
        $limit = $rawLimit > 0 ? min($rawLimit, 500) : null;
        $page = $rawPage > 0 ? $rawPage : 1;

        if ($limit !== null) {
            $total = (clone $query)->count();
            $rows = $query->forPage($page, $limit)->get()
                ->map(fn (Lead $l) => $l->toApiArray())
                ->values()
                ->all();

            return ApiResponse::success($rows, [
                'page' => $page,
                'per_page' => $limit,
                'total' => $total,
            ]);
        }

        $rows = $query->get()->map(fn (Lead $l) => $l->toApiArray())->values()->all();

        return ApiResponse::success($rows);
    }

    public function show(int $id): JsonResponse
    {
        $lead = Lead::query()->find($id);
        if (! $lead) {
            return ApiResponse::error('Lead not found', 'NOT_FOUND', 404);
        }

        return ApiResponse::success($lead->toApiArray());
    }

    public function storePublic(Request $request): JsonResponse
    {
        $name = $request->input('name');
        $phone = $request->input('phone');

        if (! $name || ! $phone) {
            return ApiResponse::error('Name and phone are required', 'INVALID_INPUT', 400);
        }

        $source = $request->input('source');
        $leadSource = in_array($source, self::PUBLIC_SOURCES, true) ? $source : 'website';

        $lead = Lead::query()->create([
            'name' => trim((string) $name),
            'phone' => trim((string) $phone),
            'email' => $request->input('email'),
            'source' => $leadSource,
            'notes' => $request->input('notes'),
            'product_interest' => $request->input('product_interest'),
            'room_details' => $request->input('room_details'),
            'material_preference' => $request->input('material_preference'),
            'design_source' => $request->input('design_source'),
            'status' => 'pending',
            'created_by' => null,
        ]);

        return ApiResponse::success($lead->fresh()?->toApiArray(), null, 201);
    }

    public function store(Request $request): JsonResponse
    {
        $name = $request->input('name');
        $phone = $request->input('phone');

        if (! $name || ! $phone) {
            return ApiResponse::error('Name and phone are required', 'INVALID_INPUT', 400);
        }

        /** @var User|null $user */
        $user = $request->attributes->get('auth_user') ?? $request->user();

        $lead = Lead::query()->create([
            'name' => $name,
            'phone' => $phone,
            'email' => $request->input('email'),
            'source' => $request->input('source'),
            'notes' => $request->input('notes'),
            'product_interest' => $request->input('product_interest'),
            'room_details' => $request->input('room_details'),
            'material_preference' => $request->input('material_preference'),
            'design_source' => $request->input('design_source'),
            'status' => 'pending',
            'created_by' => $user?->id,
        ]);

        $this->audit->log($user, 'lead', (int) $lead->id, 'CREATE_LEAD', [
            'name' => $name,
            'phone' => $phone,
            'source' => $lead->source,
        ], $request);

        return ApiResponse::success($lead->fresh()?->toApiArray(), null, 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $lead = Lead::query()->find($id);
        if (! $lead) {
            return ApiResponse::error('Lead not found', 'NOT_FOUND', 404);
        }

        $body = $request->all();
        $updates = [];
        foreach (self::PATCH_FIELDS as $field) {
            if (array_key_exists($field, $body)) {
                $updates[$field] = $body[$field];
            }
        }

        if ($updates === []) {
            return ApiResponse::error('No fields to update', 'INVALID_INPUT', 400);
        }

        $lead->fill($updates);
        $lead->save();

        /** @var User|null $user */
        $user = $request->attributes->get('auth_user') ?? $request->user();
        $this->audit->log($user, 'lead', (int) $lead->id, 'UPDATE_LEAD', $body, $request);

        return ApiResponse::success($lead->fresh()?->toApiArray());
    }
}
