@extends('layouts.app')
@section('title', 'Boards')
@section('content')
<div class="page-head"><div><h1>Boards</h1><p class="muted" style="margin:0.35rem 0 0">Monday-style workspaces and boards.</p></div></div>
@if ($canCreate)
<div class="card" style="margin-bottom:1.25rem">
    <div class="grid-2">
        <form method="POST" action="{{ route('boards.workspaces.store') }}">@csrf
            <label>New workspace</label>
            <input name="name" required minlength="2">
            <button class="btn" type="submit" style="margin-top:0.75rem">Create workspace</button>
        </form>
        <form method="POST" action="{{ route('boards.store') }}">@csrf
            <label>Workspace</label>
            <select name="workspace_id" required>
                @foreach ($workspaces as $ws)<option value="{{ $ws->id }}">{{ $ws->name }}</option>@endforeach
            </select>
            <label>Board name</label>
            <input name="name" required minlength="2">
            <label>Type</label>
            <select name="board_type">
                @foreach (['table','kanban','timeline','custom'] as $t)<option value="{{ $t }}">{{ $t }}</option>@endforeach
            </select>
            <button class="btn" type="submit" style="margin-top:0.75rem">Create board</button>
        </form>
    </div>
</div>
@endif
<div class="card">
@if ($boards->isEmpty())
<p class="muted" style="margin:0">No boards</p>
@else
<table class="data"><thead><tr><th>Board</th><th>Workspace</th><th>Type</th></tr></thead><tbody>
@foreach ($boards as $b)
<tr><td><a href="{{ route('boards.show', $b) }}">{{ $b->name }}</a></td><td>#{{ $b->workspace_id }}</td><td>{{ $b->board_type }}</td></tr>
@endforeach
</tbody></table>
@endif
</div>
@endsection
