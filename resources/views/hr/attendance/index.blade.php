@extends('layouts.app')

@section('title', 'Attendance')

@section('content')
@php
    $prev = \DateTimeImmutable::createFromFormat('Y-m', $period)?->modify('-1 month')->format('Y-m') ?: $period;
    $next = \DateTimeImmutable::createFromFormat('Y-m', $period)?->modify('+1 month')->format('Y-m') ?: $period;
@endphp

<div class="page-head">
    <div>
        <h1 class="display-font">Attendance Management</h1>
        <p class="muted" style="margin:0.35rem 0 0">Mark today · Sundays are OFF · feeds payroll present days</p>
    </div>
    <div class="toolbar">
        @if ($canEdit)
            <form method="POST" action="{{ route('hr.attendance.holiday') }}">@csrf
                <input type="hidden" name="date" value="{{ $today }}">
                <button class="btn purple-outline" type="submit">Mark Holiday (All)</button>
            </form>
            <form method="POST" action="{{ route('hr.attendance.compile') }}">@csrf
                <input type="hidden" name="period" value="{{ $period }}">
                <button class="btn ghost" type="submit">Compile for manager</button>
            </form>
            <a class="btn lime" href="{{ route('finance.payroll') }}">Calculate Salary</a>
        @endif
    </div>
</div>

<div class="summary-strip">
    <div class="metric"><strong>{{ $todaySummary['total'] }}</strong><span>Total</span></div>
    <div class="metric"><strong>{{ $todaySummary['present'] }}</strong><span>Present</span></div>
    <div class="metric"><strong>{{ $todaySummary['absent'] }}</strong><span>Absent</span></div>
    <div class="metric"><strong>{{ $todaySummary['late'] }}</strong><span>Late</span></div>
    <div class="metric"><strong>{{ $todaySummary['half_day'] }}</strong><span>Half-Day</span></div>
    <div class="metric"><strong>{{ $todaySummary['leave_holiday'] }}</strong><span>Leave/Holiday</span></div>
    <div class="metric"><strong>{{ $todaySummary['not_marked'] }}</strong><span>Not Marked</span></div>
</div>

<div class="note-box">
    Note: You can only mark attendance for TODAY ({{ \Illuminate\Support\Carbon::parse($today)->format('n/j/Y') }}). Sundays are non-working days.
</div>

<div class="filter-bar">
    <a class="btn ghost" href="{{ route('hr.attendance', ['period' => $prev]) }}">‹</a>
    <strong>{{ $periodLabel }}</strong>
    <a class="btn ghost" href="{{ route('hr.attendance', ['period' => $next]) }}">›</a>
    <form method="GET" style="margin:0">
        <input type="month" name="period" value="{{ $period }}" onchange="this.form.submit()" style="margin:0">
    </form>
</div>

<div class="legend">
    <span><i class="dot" style="background:#86efac"></i> Present</span>
    <span><i class="dot" style="background:#fca5a5"></i> Absent</span>
    <span><i class="dot" style="background:#fdba74"></i> Late</span>
    <span><i class="dot" style="background:#fde047"></i> Half-Day</span>
    <span><i class="dot" style="background:#93c5fd"></i> Leave</span>
    <span><i class="dot" style="background:#c4b5fd"></i> Holiday</span>
    <span><i class="dot" style="background:#9ca3af"></i> Sunday (Off)</span>
</div>

<div class="att-scroll" style="margin-bottom:1rem">
    <table class="data att-grid">
        <thead>
        <tr>
            <th style="min-width:180px">Employee</th>
            @for ($d = 1; $d <= $daysInMonth; $d++)
                @php
                    $date = sprintf('%04d-%02d-%02d', $year, $month, $d);
                    $dow = (int) (new DateTimeImmutable($date))->format('w');
                    $isToday = $date === $today;
                @endphp
                <th class="{{ $isToday ? 'today' : '' }}">{{ $d }}{{ $isToday ? ' Today' : ($dow === 0 ? ' OFF' : '') }}</th>
            @endfor
        </tr>
        </thead>
        <tbody>
        @foreach ($employees as $emp)
            @php
                $name = $emp->party?->name ?: 'Employee';
                $initials = collect(preg_split('/\s+/', trim($name)))->filter()->take(2)->map(fn ($p) => strtoupper(substr($p, 0, 1)))->implode('');
            @endphp
            <tr>
                <td>
                    <div class="emp-row">
                        @if ($emp->resolvePublicUrl($emp->photo_url))
                            <img class="avatar" src="{{ $emp->resolvePublicUrl($emp->photo_url) }}" alt="">
                        @else
                            <span class="avatar">{{ $initials ?: 'E' }}</span>
                        @endif
                        <div>
                            <strong>{{ $name }}</strong>
                            <div class="muted">{{ $emp->job_title ?: '—' }}</div>
                        </div>
                    </div>
                </td>
                @for ($d = 1; $d <= $daysInMonth; $d++)
                    @php
                        $date = sprintf('%04d-%02d-%02d', $year, $month, $d);
                        $dow = (int) (new DateTimeImmutable($date))->format('w');
                        $am = $marks->get($emp->id.'|'.$date.'|morning')?->first();
                        $pm = $marks->get($emp->id.'|'.$date.'|afternoon')?->first();
                        $status = $am?->status ?: $pm?->status;
                    @endphp
                    <td style="font-size:0.72rem;min-width:72px">
                        @if ($dow === 0)
                            <span class="muted">OFF</span>
                        @elseif ($canEdit && $date === $today)
                            <form method="POST" action="{{ route('hr.attendance.mark') }}" style="margin:0">
                                @csrf
                                <input type="hidden" name="employee_id" value="{{ $emp->id }}">
                                <input type="hidden" name="date" value="{{ $date }}">
                                <input type="hidden" name="session" value="morning">
                                <select name="status" onchange="this.form.submit()" style="margin:0;padding:0.25rem;font-size:0.7rem">
                                    <option value="clear">Not marked</option>
                                    @foreach (['present','late','half_day','absent','leave','holiday'] as $st)
                                        <option value="{{ $st }}" @selected($status === $st)>{{ str_replace('_', '-', $st) }}</option>
                                    @endforeach
                                </select>
                            </form>
                        @elseif ($status)
                            <span class="status-{{ $status }}">{{ str_replace('_', '-', $status) }}</span>
                        @else
                            <span class="muted">Not marked</span>
                        @endif
                    </td>
                @endfor
            </tr>
        @endforeach
        </tbody>
    </table>
</div>

<div class="stat-cards">
    <div class="stat-card"><strong>{{ $daysInMonth }}</strong><span class="muted">Total Days</span></div>
    <div class="stat-card"><strong>{{ $sundays }}</strong><span class="muted">Sundays (Off)</span></div>
    <div class="stat-card"><strong>{{ $workingDays }}</strong><span class="muted">Working Days</span></div>
    <div class="stat-card"><strong>{{ $employees->count() }}</strong><span class="muted">Employees</span></div>
</div>

<div class="card" style="margin-top:1rem">
    <h2 style="margin:0 0 0.75rem;font-size:1.05rem">Submissions</h2>
    @if ($submissions->isEmpty())
        <p class="muted" style="margin:0">No submissions yet</p>
    @else
        <table class="data">
            <thead><tr><th>Period</th><th>Status</th><th>Compiled by</th><th></th></tr></thead>
            <tbody>
            @foreach ($submissions as $sub)
                <tr>
                    <td>{{ $sub->period }}</td>
                    <td><span class="badge">{{ $sub->status }}</span></td>
                    <td>{{ $sub->compiler?->full_name ?: '—' }}</td>
                    <td>
                        @if ($canApprove && $sub->status === 'pending_manager')
                            <form method="POST" action="{{ route('hr.attendance.review', $sub->id) }}" style="display:inline">@csrf @method('PATCH')
                                <input type="hidden" name="status" value="approved">
                                <button class="btn lime" type="submit">Approve</button>
                            </form>
                            <form method="POST" action="{{ route('hr.attendance.review', $sub->id) }}" style="display:inline">@csrf @method('PATCH')
                                <input type="hidden" name="status" value="rejected">
                                <button class="btn ghost" type="submit">Reject</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif
</div>
@endsection
