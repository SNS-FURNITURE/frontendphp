@extends('layouts.app')
@section('title', 'Reports')
@section('content')
<div class="page-head">
    <div><h1>Post report</h1><p class="muted" style="margin:0.35rem 0 0">Directed reports notify the recipient in-app.</p></div>
    @if (auth()->user()->canViewAllReports())
        <a class="btn ghost" href="{{ route('reports.library') }}">Library</a>
    @endif
</div>
@if ($canPost)
<div class="card" style="margin-bottom:1.25rem">
    <form method="POST" action="{{ route('reports.store') }}">@csrf
        <div class="grid-2">
            <div><label>Title</label><input name="title" required minlength="2" value="{{ old('title') }}"></div>
            <div><label>Recipient</label>
                <select name="recipient_user_id" required>
                    <option value="">Choose…</option>
                    @foreach ($users as $u)<option value="{{ $u->id }}" @selected((string)old('recipient_user_id')===(string)$u->id)>{{ $u->full_name }}</option>@endforeach
                </select>
            </div>
            <div><label>Period</label><input name="period" value="{{ old('period') }}"></div>
        </div>
        <label>Highlights</label><textarea name="highlights" rows="2">{{ old('highlights') }}</textarea>
        <label>Challenges</label><textarea name="challenges" rows="2">{{ old('challenges') }}</textarea>
        <label>Next steps</label><textarea name="next_steps" rows="2">{{ old('next_steps') }}</textarea>
        <div class="toolbar"><button class="btn" type="submit">Send report</button></div>
    </form>
</div>
@endif
<div class="card">
@if ($reports->isEmpty())
<p class="muted" style="margin:0">No reports</p>
@else
<table class="data"><thead><tr><th>Title</th><th>Author</th><th>Posted</th></tr></thead><tbody>
@foreach ($reports as $r)
<tr><td>{{ $r->title }}</td><td>{{ $r->author?->full_name ?? $r->author_name ?? '—' }}</td><td>{{ $r->posted_at }}</td></tr>
@endforeach
</tbody></table>
@endif
</div>
@endsection
