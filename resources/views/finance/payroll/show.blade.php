@extends('layouts.app')

@section('title', 'Payroll '.$run->period)

@section('content')
@php
    $periodLabel = \DateTimeImmutable::createFromFormat('Y-m', $run->period)?->format('F Y') ?: $run->period;
    $fullSalaries = $run->lines->sum(fn ($l) => (float) $l->basic_salary);
    $proratedGross = $run->lines->sum(fn ($l) => (float) $l->gross);
    $incomeTax = $run->lines->sum(fn ($l) => (float) $l->paye);
    $netPayroll = (float) $run->total_net;
    $missingAttendance = $run->lines->filter(fn ($l) => (float) $l->days_worked <= 0)->count();
@endphp

<div class="page-head">
    <div>
        <h1 class="display-font">Pay Cycle · {{ $periodLabel }}</h1>
        <p class="muted" style="margin:0.35rem 0 0">Status {{ $run->status }} · based on attendance present units</p>
    </div>
    <div class="toolbar">
        <a class="btn ghost" href="{{ route('finance.payroll.csv', $run->id) }}">Export</a>
        <a class="btn ghost" href="{{ route('finance.payroll') }}">Back</a>
        @if ($canEdit && $run->status === 'draft')
            <form method="POST" action="{{ route('finance.payroll.status', $run->id) }}">@csrf @method('PATCH')
                <input type="hidden" name="status" value="processed">
                <button class="btn lime" type="submit">Process Payroll</button>
            </form>
        @elseif (auth()->user()->canEditPayroll() && $run->status === 'processed')
            <form method="POST" action="{{ route('finance.payroll.status', $run->id) }}">@csrf @method('PATCH')
                <input type="hidden" name="status" value="paid">
                <button class="btn lime" type="submit">Mark paid</button>
            </form>
        @endif
    </div>
</div>

<div class="pay-hero">
    <div class="pay-summary">
        <strong style="font-size:1.05rem">Payroll summary</strong>
        <div class="pay-metrics">
            <div><span class="muted">Full salaries</span><strong>ETB {{ number_format($fullSalaries, 0) }}</strong></div>
            <div><span class="muted">Prorated gross</span><strong>ETB {{ number_format($proratedGross, 0) }}</strong></div>
            <div><span class="muted">Income tax</span><strong style="color:#f87171">ETB {{ number_format($incomeTax, 0) }}</strong></div>
            <div><span class="muted">Net payroll</span><strong style="color:var(--lime)">ETB {{ number_format($netPayroll, 0) }}</strong></div>
        </div>
    </div>
    <div class="pay-checklist">
        <strong>Payroll checklist</strong>
        <ul>
            <li>✓ Employees verified ({{ $run->employee_count }})</li>
            <li>✓ Attendance loaded for {{ $periodLabel }}</li>
            <li>{{ $run->status === 'paid' ? '✓' : '○' }} Payout status: {{ $run->status }}</li>
        </ul>
    </div>
</div>

@if ($missingAttendance > 0)
    <div class="errors">{{ $missingAttendance }} employee{{ $missingAttendance === 1 ? '' : 's' }} have missing / zero attendance days.</div>
@endif

<h2 class="display-font" style="font-size:1.35rem;margin:0 0 0.85rem">Employee payout breakdown</h2>

@foreach ($run->lines as $line)
    <div class="pay-line">
        <div class="pay-line-grid">
            <div>
                <strong>{{ $line->employee_name }}</strong>
                <div class="muted">{{ ($line->job_title ?: 'Staff').' · '.($line->department ?: '—') }}</div>
            </div>
            <div><span class="muted">Present units</span><div>{{ rtrim(rtrim(number_format((float) $line->days_worked, 2), '0'), '.') }}/22</div></div>
            <div><span class="muted">Daily rate</span><div>ETB {{ number_format((float) $line->daily_rate, 0) }}</div></div>
            <div><span class="muted">Gross</span><div>ETB {{ number_format((float) $line->gross, 0) }}</div></div>
            <div><span class="muted">Tax</span><div style="color:#f87171">ETB {{ number_format((float) $line->paye, 0) }}</div></div>
            <div><span class="muted">Net</span><div style="color:var(--lime);font-weight:700">ETB {{ number_format((float) $line->net, 0) }}</div></div>
        </div>
        @if ($line->formula_text)
            <div class="muted" style="margin-top:0.55rem;font-size:0.75rem">{{ $line->formula_text }}</div>
        @endif
        @if ($canEdit)
            <form method="POST" action="{{ route('finance.payroll.line', [$run->id, $line->id]) }}" class="toolbar" style="margin-top:0.65rem">
                @csrf @method('PATCH')
                <input type="number" step="0.01" name="overtime_hours" value="{{ $line->overtime_hours }}" style="width:5rem;margin:0" placeholder="OT hrs">
                <select name="overtime_multiplier" style="width:5rem;margin:0">
                    @foreach ([1.25, 1.5, 2.0] as $m)
                        <option value="{{ $m }}" @selected((float)$line->overtime_multiplier === (float)$m)>{{ $m }}</option>
                    @endforeach
                </select>
                <button class="btn ghost" type="submit">Save OT</button>
            </form>
        @endif
    </div>
@endforeach
@endsection
