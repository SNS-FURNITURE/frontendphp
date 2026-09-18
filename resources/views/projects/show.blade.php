@extends('layouts.app')
@section('title', $project->name)
@section('content')
<div class="page-head">
    <div><h1>{{ $project->name }}</h1><p class="muted" style="margin:0.35rem 0 0">Status: {{ $project->status }}</p></div>
    <a class="btn ghost" href="{{ route('projects.index') }}">Back</a>
</div>
<div class="card" style="margin-bottom:1.25rem">
    <h2 style="margin:0 0 1rem;font-size:1.1rem">Add task</h2>
    <form method="POST" action="{{ route('projects.tasks.store', $project) }}">@csrf
        <div class="grid-2">
            <div><label>Title</label><input name="title" required></div>
            <div><label>Assignee</label>
                <select name="assigned_to"><option value="">Unassigned</option>
                @foreach ($users as $u)<option value="{{ $u->id }}">{{ $u->full_name }}</option>@endforeach
                </select>
            </div>
            <div><label>Due</label><input type="date" name="due_date"></div>
        </div>
        <button class="btn" type="submit">Add task</button>
    </form>
</div>
<div class="card">
@if ($tasks->isEmpty())
<p class="muted" style="margin:0">No tasks</p>
@else
<table class="data"><thead><tr><th>Task</th><th>Assignee</th><th>Due</th><th>Status</th></tr></thead><tbody>
@foreach ($tasks as $t)
<tr>
<td>{{ $t->title }}</td>
<td>{{ $t->assignee?->full_name ?? '—' }}</td>
<td>{{ $t->due_date ?: '—' }}</td>
<td>
@if ($canEditTasks)
<form method="POST" action="{{ route('projects.tasks.update', [$project, $t]) }}" style="display:flex;gap:0.4rem;align-items:center;margin:0">
@csrf @method('PATCH')
<select name="status" style="margin:0;width:auto">
@foreach (['todo','in_progress','done'] as $st)<option value="{{ $st }}" @selected($t->status===$st)>{{ $st }}</option>@endforeach
</select>
@if ($t->status !== 'done')
<select name="recipient_user_id" style="margin:0;width:auto" title="Report recipient when done">
<option value="">CM default</option>
@foreach ($users as $u)<option value="{{ $u->id }}">{{ $u->full_name }}</option>@endforeach
</select>
@endif
<button class="btn ghost" type="submit" style="padding:0.35rem 0.6rem">Save</button>
</form>
@else
<span class="badge">{{ $t->status }}</span>
@endif
</td>
</tr>
@endforeach
</tbody></table>
@endif
</div>
@endsection
