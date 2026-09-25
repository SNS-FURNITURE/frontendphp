@extends('layouts.app')

@section('title', 'Commercial Reports')

@section('content')
<div class="page-head">
    <div>
        <h1>Commercial reports</h1>
        <p class="muted" style="margin:.35rem 0 0">Orders and deals overview for {{ $orderReport['period']['label'] }}</p>
    </div>
    <div class="toolbar">
        @foreach (['weekly', 'monthly', 'yearly'] as $cadence)
            <form method="POST" action="{{ route('commercial.reports.generate', $cadence) }}" style="margin:0">@csrf
                <button type="submit" class="btn ghost">Generate {{ $cadence }}</button>
            </form>
        @endforeach
    </div>
</div>

@include('commercial._orders-report-chart')
@include('commercial._deals-report-chart')

<div class="card">
    <h2 style="margin:0 0 1rem;font-size:1.1rem">Report log</h2>
    @if ($reports->isEmpty())
        <p class="muted" style="margin:0">No commercial reports yet. Generate weekly, monthly, or yearly reports using the buttons above.</p>
    @else
        <table class="data">
            <thead><tr><th>When</th><th>Type</th><th>Title</th></tr></thead>
            <tbody>
            @foreach ($reports as $report)
                <tr>
                    <td>{{ $report->posted_at?->format('M j, Y H:i') ?? '—' }}</td>
                    <td>{{ str_replace('_', ' ', $report->report_type) }}</td>
                    <td>{{ $report->title }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif
</div>
@endsection
