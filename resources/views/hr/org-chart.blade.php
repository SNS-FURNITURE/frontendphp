@extends('layouts.app')

@section('title', 'Org chart')

@section('content')
<div class="page-head">
    <div>
        <h1>Organization chart</h1>
        <p class="muted" style="margin:0.35rem 0 0">Static divisions — no API</p>
    </div>
</div>

@foreach ($divisions as $division)
    <div class="card" style="margin-bottom:1rem">
        <div style="display:flex;justify-content:space-between;gap:1rem;flex-wrap:wrap">
            <div>
                <h2 style="margin:0;font-size:1.15rem">{{ $division['name'] }}</h2>
                <p class="muted" style="margin:0.35rem 0 0">{{ $division['code'] }} · {{ $division['description'] }}</p>
            </div>
            <div class="badge">{{ $division['headTitle'] }}</div>
        </div>
        <div style="margin-top:1rem;display:grid;gap:0.75rem">
            @foreach ($division['units'] as $unit)
                <div style="border-top:1px solid var(--border);padding-top:0.75rem">
                    <strong>{{ $unit['name'] }}</strong>
                    <div class="muted">{{ $unit['description'] }}</div>
                    @if (!empty($unit['mergedNote']))
                        <div class="muted" style="margin-top:0.25rem">{{ $unit['mergedNote'] }}</div>
                    @endif
                    @if (!empty($unit['subPositions']))
                        <div style="margin-top:0.35rem">{{ implode(' · ', $unit['subPositions']) }}</div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
@endforeach
@endsection
