@extends('layouts.app')

@section('title', 'Commercial Tasks')

@section('content')
<div class="page-head">
    <div>
        <h1>Commercial tasks</h1>
        <p class="muted" style="margin:.35rem 0 0">Tasks for sales reps and sales supervisors</p>
    </div>
</div>

@if ($canManage)
<div class="card" style="margin-bottom:1.25rem">
    <h2 style="margin:0 0 1rem;font-size:1.1rem">Assign task</h2>
    <form method="POST" action="{{ route('commercial.tasks.store') }}">
        @csrf
        <div class="grid-2">
            <div>
                <label for="title">Title *</label>
                <input id="title" name="title" value="{{ old('title') }}" required minlength="2">
            </div>
            <div>
                <label for="assigned_to_user_id">Assign to *</label>
                <select id="assigned_to_user_id" name="assigned_to_user_id" required>
                    @foreach ($assignees as $user)
                        <option value="{{ $user->id }}" @selected(old('assigned_to_user_id') == $user->id)>{{ $user->full_name }} ({{ $user->roles->pluck('formatted_name')->first() }})</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="due_date">Due date</label>
                <input id="due_date" type="date" name="due_date" value="{{ old('due_date') }}">
            </div>
            <div>
                <label for="notes">Notes</label>
                <input id="notes" name="notes" value="{{ old('notes') }}">
            </div>
        </div>
        <div class="toolbar" style="margin-top:1rem">
            <button type="submit" class="btn">Assign task</button>
        </div>
    </form>
</div>
@endif

<div class="card">
    <table class="data">
        <thead>
        <tr>
            <th>Task</th>
            <th>Assignee</th>
            <th>Role</th>
            <th>Due</th>
            <th>Status</th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        @forelse ($tasks as $task)
            <tr>
                <td>
                    <strong>{{ $task->title }}</strong>
                    @if ($task->notes)
                        <div class="muted" style="font-size:.85rem">{{ $task->notes }}</div>
                    @endif
                </td>
                <td>{{ $task->assignee?->full_name ?? '—' }}</td>
                <td>{{ strtoupper(str_replace('_', ' ', $task->assignee_role)) }}</td>
                <td>{{ $task->due_date?->format('M j, Y') ?? '—' }}</td>
                <td>{{ str_replace('_', ' ', $task->status) }}</td>
                <td>
                    <form method="POST" action="{{ route('commercial.tasks.update', $task) }}">@csrf @method('PATCH')
                        <select name="status" onchange="this.form.submit()" style="margin:0">
                            @foreach (['todo', 'in_progress', 'done', 'cancelled'] as $st)
                                <option value="{{ $st }}" @selected($task->status === $st)>{{ str_replace('_', ' ', $st) }}</option>
                            @endforeach
                        </select>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="6" class="muted">No tasks yet.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@endsection
