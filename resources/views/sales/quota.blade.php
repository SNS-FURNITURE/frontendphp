@extends('layouts.app')

@section('title', 'Sales quota')

@section('content')
<div class="page-head">
    <div>
        <h1>Sales quota</h1>
        <p class="muted" style="margin:0.35rem 0 0">Period {{ $quota['period'] }}</p>
    </div>
</div>

<div class="card">
    @if (! $quota['from_db'] && $quota['actual'] == 0)
        <p class="muted" style="margin:0 0 1rem">No quota data available. Showing default target.</p>
    @endif
    <div class="grid-2">
        <div>
            <div class="muted" style="font-size:0.72rem;text-transform:uppercase;letter-spacing:0.04em">Quota</div>
            <div style="font-size:1.75rem;font-weight:700;margin-top:0.25rem">{{ number_format($quota['quota'], 2) }} ETB</div>
        </div>
        <div>
            <div class="muted" style="font-size:0.72rem;text-transform:uppercase;letter-spacing:0.04em">Actual</div>
            <div style="font-size:1.75rem;font-weight:700;margin-top:0.25rem">{{ number_format($quota['actual'], 2) }} ETB</div>
        </div>
    </div>
    <div style="margin-top:1.25rem">
        <div class="muted" style="margin-bottom:0.4rem">{{ $pct }}% of target</div>
        <div style="height:12px;border-radius:999px;background:#2a2550;overflow:hidden">
            <div style="height:100%;width:{{ $pct }}%;background:var(--accent);border-radius:999px"></div>
        </div>
    </div>
</div>
@endsection
