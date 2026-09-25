<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Deal;
use App\Models\User;
use App\Services\AuditService;
use App\Services\NotifyService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DealController extends Controller
{
    private const PATCH_FIELDS = [
        'title', 'customer_name', 'status', 'owner_id', 'lead_id',
        'product_category', 'deal_value', 'notes',
    ];

    public function __construct(
        private AuditService $audit,
        private NotifyService $notify,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = Deal::query()->with('owner')->latestFirst();

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }
        if ($request->filled('owner_id')) {
            $query->where('owner_id', $request->query('owner_id'));
        }

        $rows = $query->get()->map(fn (Deal $d) => $d->toApiArray())->values()->all();

        return ApiResponse::success($rows);
    }

    public function show(int $id): JsonResponse
    {
        $deal = Deal::query()->with('owner')->find($id);
        if (! $deal) {
            return ApiResponse::error('Deal not found', 'NOT_FOUND', 404);
        }

        return ApiResponse::success($deal->toApiArray());
    }

    public function store(Request $request): JsonResponse
    {
        $title = $request->input('title');
        $customerName = $request->input('customer_name');

        if (! $title || ! $customerName) {
            return ApiResponse::error('Title and customer name are required', 'INVALID_INPUT', 400);
        }

        /** @var User|null $user */
        $user = $request->attributes->get('auth_user') ?? $request->user();

        $deal = Deal::query()->create([
            'title' => $title,
            'customer_name' => $customerName,
            'status' => $request->input('status', 'in_progress'),
            'owner_id' => $request->input('owner_id') ?: $user?->id,
            'lead_id' => $request->input('lead_id'),
            'product_category' => $request->input('product_category'),
            'deal_value' => $request->input('deal_value', 0),
            'notes' => $request->input('notes'),
        ]);

        $this->audit->log($user, 'deal', (int) $deal->id, 'CREATE_DEAL', [
            'title' => $title,
            'customer_name' => $customerName,
        ], $request);

        return ApiResponse::success($deal->fresh('owner')?->toApiArray(), null, 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $deal = Deal::query()->find($id);
        if (! $deal) {
            return ApiResponse::error('Deal not found', 'NOT_FOUND', 404);
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

        $deal->fill($updates);
        $deal->save();

        /** @var User|null $user */
        $user = $request->attributes->get('auth_user') ?? $request->user();
        $this->audit->log($user, 'deal', (int) $deal->id, 'UPDATE_DEAL', $body, $request);

        return ApiResponse::success($deal->fresh('owner')?->toApiArray());
    }

    public function salesReview(Request $request, int $id): JsonResponse
    {
        /** @var User|null $user */
        $user = $request->attributes->get('auth_user') ?? $request->user();

        if (! $user || ! $this->canSalesReview($user)) {
            return ApiResponse::error('Only the Sales Lead can approve or lose this deal', 'FORBIDDEN', 403);
        }

        $action = $request->input('action');
        if (! in_array($action, ['approve', 'lost'], true)) {
            return ApiResponse::error('Action must be approve or lost', 'INVALID_INPUT', 400);
        }

        $deal = Deal::query()->with('owner')->find($id);
        if (! $deal) {
            return ApiResponse::error('Deal not found', 'NOT_FOUND', 404);
        }

        $nextStatus = $action === 'approve' ? 'sales_approved' : 'lost';
        $deal->status = $nextStatus;
        if ($request->filled('notes')) {
            $deal->notes = $request->input('notes');
        }
        $deal->sales_reviewed_by = $user->id;
        $deal->sales_reviewed_at = now();
        $deal->save();

        $this->audit->log($user, 'deal', (int) $deal->id, 'SALES_REVIEW_DEAL', [
            'action' => $action,
            'status' => $nextStatus,
        ], $request);

        if ($action === 'approve') {
            foreach ($this->notify->activeUserIdsWithRolesRaw(['company_manager', 'manager']) as $managerId) {
                $this->notify->notifyUser($managerId, [
                    'type' => 'deal_manager_review',
                    'title' => 'Deal ready for manager review: '.$deal->title,
                    'message' => ($user->full_name ?: 'Sales Lead').' approved this deal.',
                    'entityType' => 'deal',
                    'entityId' => (int) $deal->id,
                ]);
            }
        }

        return ApiResponse::success($deal->fresh('owner')?->toApiArray());
    }

    public function managerReview(Request $request, int $id): JsonResponse
    {
        /** @var User|null $user */
        $user = $request->attributes->get('auth_user') ?? $request->user();

        if (! $user || ! $this->canManagerReview($user)) {
            return ApiResponse::error('Only the Company Manager can complete this review', 'FORBIDDEN', 403);
        }

        $action = $request->input('action');
        if (! in_array($action, ['approve', 'reject'], true)) {
            return ApiResponse::error('Action must be approve or reject', 'INVALID_INPUT', 400);
        }

        $deal = Deal::query()->with('owner')->find($id);
        if (! $deal) {
            return ApiResponse::error('Deal not found', 'NOT_FOUND', 404);
        }

        if ($deal->status !== 'sales_approved') {
            return ApiResponse::error('Deal must be approved by Sales Lead first', 'INVALID_STATE', 409);
        }

        $nextStatus = $action === 'approve' ? 'won' : 'in_progress';
        $deal->status = $nextStatus;
        if ($request->filled('notes')) {
            $deal->notes = $request->input('notes');
        }
        $deal->manager_reviewed_by = $user->id;
        $deal->manager_reviewed_at = now();
        $deal->save();

        $this->audit->log($user, 'deal', (int) $deal->id, 'MANAGER_REVIEW_DEAL', [
            'action' => $action,
            'status' => $nextStatus,
        ], $request);

        return ApiResponse::success($deal->fresh('owner')?->toApiArray());
    }

    private function canSalesReview(User $user): bool
    {
        foreach (['admin', 'supervisor', 'sales_supervisor', 'advisor'] as $role) {
            if ($user->hasRole($role)) {
                return true;
            }
        }

        return false;
    }

    private function canManagerReview(User $user): bool
    {
        return $user->hasRole('company_manager') || $user->hasRole('manager');
    }
}
