<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Report;
use App\Models\User;
use App\Services\AuditService;
use App\Services\DirectedReportService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class ReportController extends Controller
{
    public function __construct(
        private AuditService $audit,
        private DirectedReportService $reports,
    ) {}

    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->attributes->get('auth_user') ?? $request->user();
        $isMaster = $user->canViewAllReports();

        $query = Report::query()->with('author')->orderByDesc('posted_at');

        if (! $isMaster) {
            $query->where(function ($q) use ($user) {
                $q->where('posted_by_user_id', $user->id);
                if (Schema::hasColumn('reports', 'sent_to_user_id')) {
                    $q->orWhere('sent_to_user_id', $user->id);
                }
            });
        }

        return ApiResponse::success(
            $query->get()->map(fn (Report $r) => $r->toApiArray())->values()->all()
        );
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $detail = $this->reports->loadReportDetail($id);
        if (! $detail) {
            return ApiResponse::error('Report not found', 'NOT_FOUND', 404);
        }

        /** @var User $user */
        $user = $request->attributes->get('auth_user') ?? $request->user();
        $uid = (int) $user->id;

        if (
            ! $user->canViewAllReports()
            && (int) ($detail['posted_by_user_id'] ?? 0) !== $uid
            && (int) ($detail['sent_to_user_id'] ?? 0) !== $uid
        ) {
            return ApiResponse::error('You are not a recipient of this report', 'FORBIDDEN', 403);
        }

        return ApiResponse::success($detail);
    }

    public function store(Request $request): JsonResponse
    {
        $title = $request->input('title');
        if (! $title) {
            return ApiResponse::error('Report title is required', 'INVALID_INPUT', 400);
        }

        $recipientId = $request->input('recipient_user_id');
        if (! $recipientId) {
            return ApiResponse::error('Please choose who this report is sent to', 'INVALID_INPUT', 400);
        }

        $recipient = User::query()->where('id', $recipientId)->where('is_active', true)->first();
        if (! $recipient) {
            return ApiResponse::error('Recipient not found', 'NOT_FOUND', 404);
        }

        /** @var User $user */
        $user = $request->attributes->get('auth_user') ?? $request->user();
        $roleName = $request->input('role_name')
            ?: $request->input('report_type')
            ?: ($user->roles->first()?->name ?: 'general');

        $parents = $request->input('parent_report_ids', []);
        if (! is_array($parents)) {
            $parents = [];
        }

        $created = $this->reports->insertDirectedReport(
            $title,
            (string) ($request->input('body') ?: ''),
            (string) $roleName,
            $user,
            (int) $recipient->id,
            (string) $recipient->full_name,
            $request->input('entity_type'),
            $request->input('entity_id') ? (int) $request->input('entity_id') : null,
            array_map('intval', $parents),
        );

        $this->audit->log($user, 'report', (int) $created['id'], 'SEND_REPORT', [
            'title' => $title,
            'recipient_user_id' => (int) $recipient->id,
            'parent_report_ids' => $created['parent_report_ids'] ?? [],
        ], $request);

        return ApiResponse::success($created, null, 201);
    }
}
