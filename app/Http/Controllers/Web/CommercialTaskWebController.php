<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\CommercialTask;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CommercialTaskWebController extends Controller
{
    public function index(): View
    {
        abort_unless(auth()->user()?->canViewCommercialTasks(), 403);

        $user = auth()->user();
        $query = CommercialTask::query()->with(['assignee', 'assigner'])->latestFirst();

        if (! $user->canManageCommercialTasks()) {
            $query->where('assigned_to_user_id', $user->id);
        }

        $assignees = User::query()
            ->with('roles')
            ->where('is_active', true)
            ->whereHas('roles', fn ($q) => $q->whereIn('name', ['sales', 'sales_supervisor']))
            ->orderBy('full_name')
            ->get();

        return view('commercial.tasks.index', [
            'tasks' => $query->limit(200)->get(),
            'assignees' => $assignees,
            'canManage' => $user->canManageCommercialTasks(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()?->canManageCommercialTasks(), 403);

        $validated = $request->validate([
            'title' => ['required', 'string', 'min:2', 'max:255'],
            'notes' => ['nullable', 'string'],
            'assigned_to_user_id' => ['required', 'integer'],
            'due_date' => ['nullable', 'date'],
        ]);

        $assignee = User::query()
            ->where('id', $validated['assigned_to_user_id'])
            ->where('is_active', true)
            ->whereHas('roles', fn ($q) => $q->whereIn('name', ['sales', 'sales_supervisor']))
            ->first();

        if (! $assignee) {
            return back()->withErrors(['assigned_to_user_id' => 'Choose a sales rep or supervisor.'])->withInput();
        }

        $role = $assignee->hasRole('sales_supervisor') ? 'sales_supervisor' : 'sales';

        CommercialTask::query()->create([
            'title' => $validated['title'],
            'notes' => $validated['notes'] ?? null,
            'assigned_to_user_id' => $assignee->id,
            'assigned_by_user_id' => auth()->id(),
            'assignee_role' => $role,
            'status' => 'todo',
            'due_date' => $validated['due_date'] ?? null,
        ]);

        return redirect()
            ->route('commercial.tasks.index')
            ->with('status', 'Task assigned to '.$assignee->full_name.'.');
    }

    public function update(Request $request, CommercialTask $task): RedirectResponse
    {
        $user = auth()->user();
        abort_unless($user?->canViewCommercialTasks(), 403);

        if (! $user->canManageCommercialTasks() && (int) $task->assigned_to_user_id !== (int) $user->id) {
            abort(403);
        }

        $validated = $request->validate([
            'status' => ['required', 'in:todo,in_progress,done,cancelled'],
        ]);

        $task->status = $validated['status'];
        $task->save();

        return back()->with('status', 'Task updated.');
    }
}
