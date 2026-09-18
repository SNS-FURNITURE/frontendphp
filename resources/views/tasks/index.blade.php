@extends('layouts.app')
@section('title', 'Tasks')
@section('content')
<div class="page-head"><div><h1>Priority tasks</h1><p class="muted" style="margin:0.35rem 0 0">Assigned and open tasks. Completing creates a directed report.</p></div></div>
<div class="card">
@if ($tasks->isEmpty())
<p class="muted" style="margin:0">No tasks</p>
@else
<table class="data"><thead><tr><th>Project</th><th>Task</th><th>Assignee</th><th>Status</th></tr></thead><tbody>
@foreach ($tasks as $t)
<tr>
<td>{{ $t->project?->name ?? '—' }}</td>
<td>{{ $t->title }}</td>
<td>{{ $t->assignee?->full_name ?? 'Unassigned' }}</td>
<td>
@if ($canEdit)
<form method="POST" action="{{ route('tasks.update', $t) }}" style="display:flex;gap:0.4rem;margin:0;align-items:center">
@csrf @method('PATCH')
<select name="status" style="margin:0;width:auto">
@foreach (['todo','in_progress','done'] as $st)<option value="{{ $st }}" @selected($t->status===$st)>{{ $st }}</option>@endforeach
</select>
<select name="recipient_user_id" style="margin:0;width:auto"><option value="">CM default</option>
@foreach ($users as $u)<option value="{{ $u->id }}">{{ $u->full_name }}</option>@endforeach
</select>
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
