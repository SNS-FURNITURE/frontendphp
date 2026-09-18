<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\User;
use App\Services\AuditService;
use App\Services\DirectedReportService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function __construct(
        private AuditService $audit,
        private DirectedReportService $reports,
    ) {}

    public function index(): JsonResponse
    {
        return ApiResponse::success(
            Project::query()->orderByDesc('created_at')->get()->values()->all()
        );
    }

    public function store(Request $request): JsonResponse
    {
        $name = $request->input('name');
        if (! $name) {
            return ApiResponse::error('Project name is required', 'INVALID_INPUT', 400);
        }

        /** @var User|null $user */
        $user = $request->attributes->get('auth_user') ?? $request->user();

        $project = Project::query()->create([
            'name' => $name,
            'pm_user_id' => $request->input('pm_user_id') ?: $user?->id,
            'status' => $request->input('status') ?: 'planned',
        ]);

        $this->audit->log($user, 'project', (int) $project->id, 'CREATE_PROJECT', [
            'name' => $name,
            'status' => $project->status,
        ], $request);

        return ApiResponse::success([
            'id' => (int) $project->id,
            'name' => $project->name,
            'status' => $project->status,
        ], null, 201);
    }

    public function tasks(int $id): JsonResponse
    {
        $rows = ProjectTask::query()
            ->where('project_id', $id)
            ->orderByDesc('created_at')
            ->get();

        return ApiResponse::success($rows->values()->all());
    }

    public function storeTask(Request $request, int $id): JsonResponse
    {
        $title = $request->input('title');
        if (! $title) {
            return ApiResponse::error('Task title is required', 'INVALID_INPUT', 400);
        }

        $task = ProjectTask::query()->create([
            'project_id' => $id,
            'title' => $title,
            'assigned_to' => $request->input('assigned_to'),
            'status' => $request->input('status') ?: 'todo',
            'due_date' => $request->input('due_date'),
        ]);

        return ApiResponse::success([
            'id' => (int) $task->id,
            'project_id' => $id,
            'title' => $task->title,
            'status' => $task->status,
        ], null, 201);
    }

    public function updateTask(Request $request, int $id, int $taskId): JsonResponse
    {
        $task = ProjectTask::query()->with(['project', 'assignee'])->find($taskId);
        if (! $task || (int) $task->project_id !== $id) {
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
            /** @var User $user */
            $user = $request->attributes->get('auth_user') ?? $request->user();
            $report = $this->reports->maybeGenerateTaskReport(
                $user,
                $task,
                $request->input('recipient_user_id') ? (int) $request->input('recipient_user_id') : null,
            );
        }

        return ApiResponse::success([
            'id' => (int) $taskId,
            'updated' => true,
            'generated_report' => $report,
        ]);
    }
}
