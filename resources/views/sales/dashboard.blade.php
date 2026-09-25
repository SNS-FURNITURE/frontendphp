@extends('layouts.app')

@section('title', $viewingAsManager ? $subject->full_name.' — Sales report' : 'Sales Dashboard')

@section('content')
<div class="page-head">
    <div>
        <h1>@if ($viewingAsManager) {{ $subject->full_name }} @else Sales dashboard @endif</h1>
        <p class="muted" style="margin:.35rem 0 0">
            @if ($viewingAsManager)
                Contact approval report for {{ $report['period']['label'] }}
            @else
                Your customer contacts vs quota for {{ $stats['period_label'] }}
            @endif
        </p>
    </div>
    <div class="toolbar">
        @if ($viewingAsManager)
            <a class="btn ghost" href="{{ route('sales.quota.manage') }}">Back to sales quotas</a>
        @else
            <a class="btn" href="{{ route('sales.customers') }}">Add contact</a>
        @endif
    </div>
</div>

@include('sales._contact-report-chart')

@if (! $viewingAsManager)
<div class="grid-2" style="margin-bottom:1.25rem">
    <div class="card">
        <h2 style="margin:0 0 .75rem;font-size:1rem">Quota progress</h2>
        <p style="font-size:2rem;font-weight:700;margin:0">{{ $stats['approved'] }} <span class="muted" style="font-size:1rem;font-weight:400">/ {{ (int) $stats['quota'] }} approved contacts</span></p>
        <div style="margin-top:1rem;height:10px;background:var(--border);border-radius:999px;overflow:hidden">
            <div style="height:100%;width:{{ $stats['pct'] }}%;background:var(--accent)"></div>
        </div>
        <p class="muted" style="margin:.75rem 0 0">{{ $stats['pct'] }}% of {{ ucfirst($periodType) }} sales quota</p>
    </div>
    <div class="card">
        <h2 style="margin:0 0 .75rem;font-size:1rem">{{ ucfirst($periodType) }} summary</h2>
        <ul style="margin:0;padding-left:1.1rem;line-height:1.8">
            <li><strong>{{ $stats['total'] }}</strong> submitted</li>
            <li><strong>{{ $stats['pending'] }}</strong> pending supervisor review</li>
            <li><strong>{{ $stats['approved'] }}</strong> approved</li>
            <li><strong>{{ $stats['rejected'] }}</strong> rejected</li>
        </ul>
    </div>
</div>
@endif

<div class="card">
    <h2 style="margin:0 0 1rem;font-size:1.1rem">Recent contacts</h2>
    @if ($recent->isEmpty())
        <p class="muted" style="margin:0">No contacts in this period.</p>
    @else
        <table class="data">
            <thead>
            <tr>
                <th>Name</th>
                <th>Phone</th>
                <th>Status</th>
                <th>Submitted</th>
            </tr>
            </thead>
            <tbody>
            @foreach ($recent as $party)
                <tr>
                    <td>{{ $party->name }}</td>
                    <td>{{ $party->phone ?: '—' }}</td>
                    <td>{{ ucfirst($party->approval_status) }}</td>
                    <td>{{ $party->created_at?->format('M j, Y') }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif
</div>
@endsection
