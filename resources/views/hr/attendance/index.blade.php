@extends('layouts.app')

@section('title', 'Attendance')

@section('content')
<div class="page-head">
    <div>
        <h1>Attendance</h1>
        <p class="muted" style="margin:0.35rem 0 0">AM/PM marks · compile for Company Manager · period {{ $period }}</p>
    </div>
    <form method="GET" class="toolbar">
        <input type="month" name="period" value="{{ $period }}" onchange="this.form.submit()">
    </form>
</div>

@if ($canEdit)
<div class="card" style="margin-bottom:1rem">
    <div class="toolbar">
        <form method="POST" action="{{ route('hr.attendance.holiday') }}">@csrf
            <input type="hidden" name="date" value="{{ $today }}">
            <button class="btn secondary" type="submit">Holiday all (today)</button>
        </form>
        <form method="POST" action="{{ route('hr.attendance.compile') }}">@csrf
            <input type="hidden" name="period" value="{{ $period }}">
            <button class="btn" type="submit">Compile for manager</button>
        </form>
    </div>
</div>
@endif

<div class="card" style="overflow-x:auto;margin-bottom:1rem">
    <table class="data">
        <thead>
        <tr>
            <th>Employee</th>
            @for ($d = 1; $d <= min($daysInMonth, 14); $d++)
                @php $dow = (int) (new DateTimeImmutable(sprintf('%04d-%02d-%02d', $year, $month, $d)))->format('w'); @endphp
                <th>{{ $d }}{{ $dow === 0 ? ' OFF' : '' }}</th>
            @endfor
        </tr>
        </thead>
        <tbody>
        @foreach ($employees as $emp)
            <tr>
                <td>{{ $emp->party?->name }}</td>
                @for ($d = 1; $d <= min($daysInMonth, 14); $d++)
                    @php
                        $date = sprintf('%04d-%02d-%02d', $year, $month, $d);
                        $dow = (int) (new DateTimeImmutable($date))->format('w');
                        $am = $marks->get($emp->id.'|'.$date.'|morning')?->first();
                        $pm = $marks->get($emp->id.'|'.$date.'|afternoon')?->first();
                    @endphp
                    <td style="font-size:0.75rem">
                        @if ($dow === 0)
                            <span class="muted">OFF</span>
                        @elseif ($canEdit && $date === $today)
                            <form method="POST" action="{{ route('hr.attendance.mark') }}" style="margin:0">
                                @csrf
                                <input type="hidden" name="employee_id" value="{{ $emp->id }}">
                                <input type="hidden" name="date" value="{{ $date }}">
                                <input type="hidden" name="session" value="morning">
                                <select name="status" onchange="this.form.submit()" style="margin:0;padding:0.2rem;font-size:0.7rem">
                                    <option value="clear">—</option>
                                    @foreach (['present','late','half_day','absent','leave','holiday'] as $st)
                                        <option value="{{ $st }}" @selected($am?->status === $st)>AM {{ $st }}</option>
                                    @endforeach
                                </select>
                            </form>
                            <form method="POST" action="{{ route('hr.attendance.mark') }}" style="margin:0.2rem 0 0">
                                @csrf
                                <input type="hidden" name="employee_id" value="{{ $emp->id }}">
                                <input type="hidden" name="date" value="{{ $date }}">
                                <input type="hidden" name="session" value="afternoon">
                                <select name="status" onchange="this.form.submit()" style="margin:0;padding:0.2rem;font-size:0.7rem">
                                    <option value="clear">—</option>
                                    @foreach (['present','late','half_day','absent','leave','holiday'] as $st)
                                        <option value="{{ $st }}" @selected($pm?->status === $st)>PM {{ $st }}</option>
                                    @endforeach
                                </select>
                            </form>
                        @else
                            {{ $am?->status ? 'AM:'.$am->status : '' }} {{ $pm?->status ? 'PM:'.$pm->status : '' }}
                        @endif
                    </td>
                @endfor
            </tr>
        @endforeach
        </tbody>
    </table>
</div>

<div class="card">
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
                                <button class="btn" type="submit">Approve</button>
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
