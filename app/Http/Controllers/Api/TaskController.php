<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProjectTask;
use App\Models\User;
use App\Services\AuditService;
use App\Services\DirectedReportService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function __construct(
        private AuditService $audit,
        private DirectedReportService $reports,
    ) {}

    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->attributes->get('auth_user') ?? $request->user();
        $names = $user->roles->pluck('name')->map(fn ($n) => strtolower((string) $n))->all();
        $isBroad = in_array('admin', $names, true)
            || in_array('company_manager', $names, true)
            || in_array('product_manager', $names, true);

        $query = ProjectTask::query()
            ->with(['project', 'assignee']);

        if (! $isBroad) {
            $query->where(function ($q) use ($user) {
                $q->where('assigned_to', $user->id)->orWhereNull('assigned_to');
            });
        }

        $rows = $query->latestFirst()
            ->get()
            ->map(fn (ProjectTask $t) => $t->toApiArray())
            ->values()
            ->all();

        return ApiResponse::success($rows);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        /** @var User $user */
        $user = $request->attributes->get('auth_user') ?? $request->user();

        if (! $user->canEditTasks()) {
            return ApiResponse::error('You cannot update tasks', 'FORBIDDEN', 403);
        }

        $task = ProjectTask::query()->with(['project', 'assignee'])->find($id);
        if (! $task) {
            return ApiResponse::error('Task not found', 'NOT_FOUND', 404);
        }

        $existingStatus = $task->status;
        $status = $request->input('status');
        $assignedTo = $request->input('assigned_to', '__omit__');
        $dueDate = $request->input('due_date', '__omit__');

        $updates = [];
        if ($status) {
            $updates['status'] = $status;
        }
        if ($assignedTo !== '__omit__') {
            $updates['assigned_to'] = $assignedTo;
        }
        if ($dueDate !== '__omit__') {
            $updates['due_date'] = $dueDate;
        }

        if ($updates === []) {
            return ApiResponse::error('No fields to update', 'INVALID_INPUT', 400);
        }

        $task->fill($updates);
        $task->save();
        $task->load(['project', 'assignee']);

        $report = null;
        if ($status === 'done' && $existingStatus !== 'done') {
            $report = $this->reports->maybeGenerateTaskReport(
                $user,
                $task,
                $request->input('recipient_user_id') ? (int) $request->input('recipient_user_id') : null,
            );
            $this->audit->log($user, 'task', $id, 'COMPLETE_TASK', [
                'title' => $task->title,
                'report_id' => $report['id'] ?? null,
                'recipient_user_id' => $report['sent_to_user_id'] ?? null,
            ], $request);
        }

        return ApiResponse::success(array_merge($task->toApiArray(), [
            'generated_report' => $report,
        ]));
    }
}
