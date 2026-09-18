@extends('layouts.app')
@section('title', 'Report library')
@section('content')
<div class="page-head">
    <div><h1>All reports</h1><p class="muted" style="margin:0.35rem 0 0">Admin / Company Manager library.</p></div>
    <a class="btn ghost" href="{{ route('reports.index') }}">Post report</a>
</div>
<div class="card">
@if ($reports->isEmpty())
<p class="muted" style="margin:0">No reports</p>
@else
<table class="data"><thead><tr><th>Title</th><th>Author</th><th>Type</th><th>Posted</th></tr></thead><tbody>
@foreach ($reports as $r)
<tr>
<td>{{ $r->title }}</td>
<td>{{ $r->author?->full_name ?? $r->author_name ?? '—' }}</td>
<td>{{ $r->report_type }}</td>
<td>{{ $r->posted_at }}</td>
</tr>
@endforeach
</tbody></table>
@endif
</div>
@endsection
