@extends('layouts.app')

@section('title', 'My Quota')

@section('content')
<div class="page-head">
    <div>
        <h1>My sales quota</h1>
        <p class="muted" style="margin:.35rem 0 0">{{ $stats['period_label'] }}</p>
    </div>
    <div class="toolbar">
        <form method="GET" action="{{ route('sales.quota') }}" style="margin:0">
            <select name="period_type" onchange="this.form.submit()" style="margin:0;max-width:120px">
                <option value="daily" {{ $periodType === 'daily' ? 'selected' : '' }}>Daily</option>
                <option value="weekly" {{ $periodType === 'weekly' ? 'selected' : '' }}>Weekly</option>
                <option value="monthly" {{ $periodType === 'monthly' ? 'selected' : '' }}>Monthly</option>
                <option value="yearly" {{ $periodType === 'yearly' ? 'selected' : '' }}>Yearly</option>
            </select>
        </form>
        <a class="btn ghost" href="{{ route('sales.dashboard') }}">Dashboard</a>
    </div>
</div>

<div class="card">
    <p style="font-size:2rem;font-weight:700;margin:0">{{ $stats['approved'] }} <span class="muted" style="font-size:1rem;font-weight:400">/ {{ (int) $stats['quota'] }} approved</span></p>
    <div style="margin:1rem 0;height:10px;background:var(--border);border-radius:999px;overflow:hidden">
        <div style="height:100%;width:{{ min(100, $stats['pct']) }}%;background:var(--accent)"></div>
    </div>
    <p class="muted" style="margin:0 0 .75rem">{{ number_format($stats['pct'], 1) }}% of {{ $periodType }} sales quota</p>
    <p class="muted" style="margin:0">Pending: {{ $stats['pending'] }} · Rejected: {{ $stats['rejected'] }} · Submitted: {{ $stats['total'] }}</p>
</div>
@endsection
