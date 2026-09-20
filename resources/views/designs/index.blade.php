@extends('layouts.app')

@section('title', 'Designs')

@section('content')
<div class="page-head">
    <div>
        <h1>Designs</h1>
        <p class="muted" style="margin:0.35rem 0 0">Original and custom design records linked to deals.</p>
    </div>
    @if ($canCreate)
        <div class="toolbar">
            <button class="btn" type="button" onclick="document.getElementById('create-design').hidden=false">New design</button>
        </div>
    @endif
</div>

@if ($canCreate)
<div class="card" id="create-design" style="margin-bottom:1.25rem" @if(!$errors->any() || !old('title')) hidden @endif>
    <h2 style="margin:0 0 1rem;font-size:1.1rem">Create design</h2>
    <form method="POST" action="{{ route('designs.store') }}">
        @csrf
        <div class="grid-2">
            <div>
                <label for="title">Title</label>
                <input id="title" name="title" value="{{ old('title') }}" required minlength="2">
            </div>
            <div>
                <label for="kind">Kind</label>
                <select id="kind" name="kind" required>
                    <option value="original" @selected(old('kind', 'original') === 'original')>original</option>
                    <option value="custom" @selected(old('kind') === 'custom')>custom</option>
                </select>
            </div>
            <div>
                <label for="design_source">Design source</label>
                <select id="design_source" name="design_source" required>
                    @foreach (['client','catalogue','sns_recommendation'] as $src)
                        <option value="{{ $src }}" @selected(old('design_source', 'client') === $src)>{{ $src }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="status">Status</label>
                <select id="status" name="status" required>
                    @foreach ($statuses as $st)
                        <option value="{{ $st }}" @selected(old('status', 'concept') === $st)>{{ $st }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <label for="notes">Notes</label>
        <textarea id="notes" name="notes" rows="2">{{ old('notes') }}</textarea>
        <label for="file_url">File URL</label>
        <input id="file_url" name="file_url" value="{{ old('file_url') }}">
        <div class="toolbar">
            <button class="btn" type="submit">Create design</button>
            <button class="btn ghost" type="button" onclick="document.getElementById('create-design').hidden=true">Cancel</button>
        </div>
    </form>
</div>
@endif

<div class="card">
    @if ($designs->isEmpty())
        <p class="muted" style="margin:0">No designs yet</p>
    @else
        <table class="data">
            <thead>
            <tr>
                <th>Title</th>
                <th>Kind</th>
                <th>Source</th>
                <th>Designer</th>
                <th>Status</th>
            </tr>
            </thead>
            <tbody>
            @foreach ($designs as $row)
                <tr>
                    <td>{{ $row->title }}</td>
                    <td>{{ $row->kind }}</td>
                    <td>{{ $row->design_source ?: '—' }}</td>
                    <td>{{ $row->designer?->full_name ?? '—' }}</td>
                    <td>
                        @if ($canEdit)
                            <form method="POST" action="{{ route('designs.update', $row->id) }}" style="margin:0">
                                @csrf
                                @method('PATCH')
                                <select name="status" onchange="this.form.submit()" style="margin:0;width:auto;min-width:9rem">
                                    @foreach ($statuses as $st)
                                        <option value="{{ $st }}" @selected($row->status === $st)>{{ $st }}</option>
                                    @endforeach
                                </select>
                            </form>
                        @else
                            <span class="badge">{{ $row->status }}</span>
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif
</div>
@endsection
