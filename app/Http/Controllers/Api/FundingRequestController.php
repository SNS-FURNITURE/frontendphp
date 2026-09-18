<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FundingRequest;
use App\Models\User;
use App\Services\AuditService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FundingRequestController extends Controller
{
    public function __construct(private AuditService $audit) {}

    public function index(Request $request): JsonResponse
    {
        $query = FundingRequest::query()
            ->with(['requester', 'approver'])
            ->orderByDesc('created_at');

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        $rows = $query->get()->map(fn (FundingRequest $f) => $f->toApiArray())->values()->all();

        return ApiResponse::success($rows);
    }

    public function store(Request $request): JsonResponse
    {
        $title = $request->input('title');
        $amount = $request->input('amount');
        $purpose = $request->input('purpose');

        if (! $title || ! $amount) {
            return ApiResponse::error('Title and amount are required', 'INVALID_INPUT', 400);
        }

        /** @var User|null $user */
        $user = $request->attributes->get('auth_user') ?? $request->user();

        $funding = FundingRequest::query()->create([
            'title' => $title,
            'amount' => $amount,
            'purpose' => $purpose ?: $title,
            'status' => 'draft',
            'requested_by' => $user?->id,
        ]);

        $this->audit->log($user, 'funding_request', (int) $funding->id, 'CREATE_FUNDING_REQUEST', [
            'title' => $title,
            'amount' => $amount,
        ], $request);

        $funding->load(['requester', 'approver']);

        return ApiResponse::success($funding->toApiArray(), null, 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $funding = FundingRequest::query()->find($id);
        if (! $funding) {
            return ApiResponse::error('Funding request not found', 'NOT_FOUND', 404);
        }

        $body = $request->all();
        $allowed = ['title', 'amount', 'purpose', 'status', 'approved_by'];
        $updates = [];

        foreach ($allowed as $field) {
            if (array_key_exists($field, $body)) {
                $updates[$field] = $body[$field];
            }
        }

        /** @var User|null $user */
        $user = $request->attributes->get('auth_user') ?? $request->user();

        if (
            isset($body['status'])
            && in_array($body['status'], ['approved', 'routed', 'received'], true)
            && ! array_key_exists('approved_by', $body)
        ) {
            $updates['approved_by'] = $user?->id;
        }

        if ($updates === []) {
            return ApiResponse::error('No fields to update', 'INVALID_INPUT', 400);
        }

        $funding->fill($updates);
        $funding->save();

        $this->audit->log($user, 'funding_request', (int) $funding->id, 'UPDATE_FUNDING_REQUEST', $body, $request);

        $funding->load(['requester', 'approver']);

        return ApiResponse::success($funding->toApiArray());
    }
}
