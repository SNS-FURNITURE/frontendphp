@extends('layouts.app')

@section('title', 'My Quota')

@section('content')
<div class="page-head">
    <div>
        <h1>My sales quota</h1>
        <p class="muted" style="margin:.35rem 0 0">{{ $stats['period_label'] }}</p>
    </div>
    <a class="btn ghost" href="{{ route('sales.dashboard') }}">Dashboard</a>
</div>

<div class="card">
    <p style="font-size:2rem;font-weight:700;margin:0">{{ $stats['approved'] }} <span class="muted" style="font-size:1rem;font-weight:400">/ {{ (int) $stats['quota'] }} approved</span></p>
    <div style="margin:1rem 0;height:10px;background:var(--border);border-radius:999px;overflow:hidden">
        <div style="height:100%;width:{{ $stats['pct'] }}%;background:var(--accent)"></div>
    </div>
    <p class="muted" style="margin:0 0 .75rem">{{ number_format($stats['pct'], 1) }}% of monthly sales quota</p>
    <p class="muted" style="margin:0">Pending: {{ $stats['pending'] }} · Rejected: {{ $stats['rejected'] }} · Submitted: {{ $stats['total'] }}</p>
</div>
@endsection
