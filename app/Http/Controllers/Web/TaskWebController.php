<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\ProjectTask;
use App\Models\User;
use App\Services\AuditService;
use App\Services\DirectedReportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TaskWebController extends Controller
{
    public function __construct(
        private AuditService $audit,
        private DirectedReportService $reports,
    ) {}

    public function index(): View
    {
        abort_unless(auth()->user()?->canViewTasks(), 403);

        $user = auth()->user();
        $names = $user->roles->pluck('name')->map(fn ($n) => strtolower((string) $n))->all();
        $isBroad = in_array('admin', $names, true)
            || in_array('company_manager', $names, true)
            || in_array('product_manager', $names, true);

        $query = ProjectTask::query()->with(['project', 'assignee']);
        if (! $isBroad) {
            $query->where(function ($q) use ($user) {
                $q->where('assigned_to', $user->id)->orWhereNull('assigned_to');
            });
        }

        return view('tasks.index', [
            'tasks' => $query->orderByRaw("(status = 'done')")->orderByDesc('id')->get(),
            'users' => User::query()->where('is_active', true)->orderBy('full_name')->get(['id', 'full_name']),
            'canEdit' => $user->canEditTasks(),
        ]);
    }

    public function update(Request $request, ProjectTask $task): RedirectResponse
    {
        if (! auth()->user()?->canEditTasks()) {
            return back()->withErrors(['status' => 'You cannot update tasks']);
        }

        $validated = $request->validate([
            'status' => ['required', 'in:todo,in_progress,done'],
            'recipient_user_id' => ['nullable', 'integer'],
        ]);

        $existing = $task->status;
        $task->status = $validated['status'];
        $task->save();
        $task->load(['project', 'assignee']);

        if ($validated['status'] === 'done' && $existing !== 'done') {
            $this->reports->maybeGenerateTaskReport(
                auth()->user(),
                $task,
                isset($validated['recipient_user_id']) ? (int) $validated['recipient_user_id'] : null,
            );
            $this->audit->log(auth()->user(), 'task', (int) $task->id, 'COMPLETE_TASK', [
                'title' => $task->title,
            ], $request);
        }

        return back()->with('status', 'Task updated');
    }
}
