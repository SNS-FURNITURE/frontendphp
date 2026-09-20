@extends('layouts.app')

@section('title', 'Audit log')

@section('content')
<div class="page-head">
    <div>
        <h1>Audit log</h1>
        <p class="muted" style="margin:.35rem 0 0">Latest 100 entries.</p>
    </div>
</div>

<div class="card" style="margin-bottom:1rem">
    <form method="GET" action="{{ route('admin.audit') }}" style="display:flex;gap:.75rem;align-items:end;flex-wrap:wrap">
        <div style="min-width:200px">
            <label for="entity_type">Entity type</label>
            <select id="entity_type" name="entity_type">
                <option value="">All</option>
                @foreach ($types as $type)
                    <option value="{{ $type }}" @selected($entityType === $type)>{{ $type }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="btn ghost">Filter</button>
    </form>
</div>

<div class="card">
    <table class="data">
        <thead>
        <tr>
            <th>When</th>
            <th>User</th>
            <th>Action</th>
            <th>Entity</th>
            <th>Changes</th>
        </tr>
        </thead>
        <tbody>
        @forelse ($entries as $entry)
            <tr>
                <td>{{ optional($entry->created_at)->format('Y-m-d H:i') }}</td>
                <td>{{ $entry->user?->full_name ?? '—' }}</td>
                <td><span class="badge">{{ $entry->action }}</span></td>
                <td>{{ $entry->entity_type }} #{{ $entry->entity_id }}</td>
                <td>
                    @if ($entry->changes_json)
                        <details>
                            <summary style="cursor:pointer;color:var(--accent)">View changes</summary>
                            <pre style="white-space:pre-wrap;font-size:.75rem;margin:.5rem 0 0;color:var(--muted)">{{ json_encode($entry->changes_json, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES) }}</pre>
                        </details>
                    @else
                        —
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="5" class="muted">No audit entries matching filter</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@endsection
