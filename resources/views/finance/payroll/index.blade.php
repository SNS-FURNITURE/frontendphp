@extends('layouts.app')

@section('title', 'Pay Cycles')

@section('content')
<div class="page-head">
    <div>
        <h1 class="display-font">Pay Cycles</h1>
        <p class="muted" style="margin:0.35rem 0 0">Ethiopia PAYE · attendance present days · Late counts present · half-day = 0.5</p>
    </div>
</div>

@if ($canGenerate)
<div class="card" style="margin-bottom:1.25rem">
    <form method="POST" action="{{ route('finance.payroll.generate') }}" class="toolbar">
        @csrf
        <input type="month" name="period" value="{{ now()->format('Y-m') }}" style="margin:0;width:auto">
        <button class="btn lime" type="submit">Process Payroll</button>
        <a class="btn ghost" href="{{ route('hr.attendance') }}">Open attendance</a>
    </form>
</div>
@endif

<div class="card">
    @if ($runs->isEmpty())
        <p class="muted" style="margin:0">No historical runs found</p>
    @else
        <table class="data">
            <thead><tr><th>Period</th><th>Status</th><th>Employees</th><th>Net payroll</th><th>Actions</th></tr></thead>
            <tbody>
            @foreach ($runs as $run)
                <tr>
                    <td>{{ $run->period }}</td>
                    <td><span class="badge {{ $run->status === 'paid' ? 'badge-ok' : '' }}">{{ $run->status }}</span></td>
                    <td>{{ $run->employee_count }}</td>
                    <td>ETB {{ number_format((float) $run->total_net, 2) }}</td>
                    <td class="toolbar">
                        <a class="btn ghost" href="{{ route('finance.payroll.show', $run->id) }}">Open</a>
                        <a class="btn ghost" href="{{ route('finance.payroll.csv', $run->id) }}">Export</a>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif
</div>
@endsection
