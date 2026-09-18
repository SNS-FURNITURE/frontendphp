<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\User;
use App\Services\AuditService;
use App\Services\DirectedReportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProjectWebController extends Controller
{
    public function __construct(
        private AuditService $audit,
        private DirectedReportService $reports,
    ) {}

    public function index(): View
    {
        abort_unless(auth()->user()?->canViewProjects(), 403);

        return view('projects.index', [
            'projects' => Project::query()->with('pm')->orderByDesc('created_at')->get(),
            'canCreate' => auth()->user()->canViewProjects() && ! auth()->user()->isAdmin(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()?->canViewProjects() && ! auth()->user()?->isAdmin(), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'min:2'],
        ], [
            'name.required' => 'Project name is required',
        ]);

        $project = Project::query()->create([
            'name' => $validated['name'],
            'pm_user_id' => auth()->id(),
            'status' => 'planned',
        ]);

        $this->audit->log(auth()->user(), 'project', (int) $project->id, 'CREATE_PROJECT', [
            'name' => $validated['name'],
            'status' => 'planned',
        ], $request);

        return redirect()->route('projects.show', $project)->with('status', 'Project created');
    }

    public function show(Project $project): View
    {
        abort_unless(auth()->user()?->canViewProjects(), 403);

        return view('projects.show', [
            'project' => $project,
            'tasks' => $project->tasks()->with('assignee')->orderByDesc('created_at')->get(),
            'users' => User::query()->where('is_active', true)->orderBy('full_name')->get(['id', 'full_name']),
            'canEditTasks' => auth()->user()->canEditTasks(),
        ]);
    }

    public function storeTask(Request $request, Project $project): RedirectResponse
    {
        abort_unless(auth()->user()?->canViewProjects(), 403);

        $validated = $request->validate([
            'title' => ['required', 'string', 'min:2'],
            'assigned_to' => ['nullable', 'integer'],
            'due_date' => ['nullable', 'date'],
        ]);

        $project->tasks()->create([
            'title' => $validated['title'],
            'assigned_to' => $validated['assigned_to'] ?? null,
            'due_date' => $validated['due_date'] ?? null,
            'status' => 'todo',
        ]);

        return back()->with('status', 'Task created');
    }

    public function updateTask(Request $request, Project $project, ProjectTask $task): RedirectResponse
    {
        abort_unless((int) $task->project_id === (int) $project->id, 404);

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
