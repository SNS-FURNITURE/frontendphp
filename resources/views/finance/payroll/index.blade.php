@extends('layouts.app')

@section('title', 'Payroll')

@section('content')
<div class="page-head">
    <div>
        <h1>Payroll</h1>
        <p class="muted" style="margin:0.35rem 0 0">Ethiopia PAYE · pension 7%/11% of salary_month</p>
    </div>
</div>

@if ($canGenerate)
<div class="card" style="margin-bottom:1.25rem">
    <form method="POST" action="{{ route('finance.payroll.generate') }}" class="toolbar">
        @csrf
        <input type="month" name="period" value="{{ now()->format('Y-m') }}">
        <button class="btn" type="submit">Generate payroll</button>
    </form>
</div>
@endif

<div class="card">
    @if ($runs->isEmpty())
        <p class="muted" style="margin:0">No historical runs found</p>
    @else
        <table class="data">
            <thead><tr><th>Period</th><th>Status</th><th>Employees</th><th>Net</th><th></th></tr></thead>
            <tbody>
            @foreach ($runs as $run)
                <tr>
                    <td>{{ $run->period }}</td>
                    <td><span class="badge">{{ $run->status }}</span></td>
                    <td>{{ $run->employee_count }}</td>
                    <td>{{ number_format((float) $run->total_net, 2) }} ETB</td>
                    <td>
                        <a href="{{ route('finance.payroll.show', $run->id) }}">Open</a>
                        ·
                        <a href="{{ route('finance.payroll.csv', $run->id) }}">CSV</a>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif
</div>
@endsection
