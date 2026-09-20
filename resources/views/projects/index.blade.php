@extends('layouts.app')
@section('title', 'Projects')
@section('content')
<div class="page-head">
    <div><h1>Projects</h1><p class="muted" style="margin:0.35rem 0 0">Work OS projects and tasks.</p></div>
    @if ($canCreate)<button class="btn" type="button" onclick="document.getElementById('create-project').hidden=false">New project</button>@endif
</div>
@if ($canCreate)
<div class="card" id="create-project" style="margin-bottom:1.25rem" hidden>
    <form method="POST" action="{{ route('projects.store') }}">@csrf
        <label for="name">Name</label>
        <input id="name" name="name" required minlength="2">
        <div class="toolbar"><button class="btn" type="submit">Create</button></div>
    </form>
</div>
@endif
<div class="card">
@if ($projects->isEmpty())
<p class="muted" style="margin:0">No projects</p>
@else
<table class="data"><thead><tr><th>Name</th><th>PM</th><th>Status</th></tr></thead><tbody>
@foreach ($projects as $p)
<tr><td><a href="{{ route('projects.show', $p) }}">{{ $p->name }}</a></td><td>{{ $p->pm?->full_name ?? '—' }}</td><td><span class="badge">{{ $p->status }}</span></td></tr>
@endforeach
</tbody></table>
@endif
</div>
@endsection
