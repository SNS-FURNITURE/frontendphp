@extends('layouts.app')

@section('title', 'Leave')

@section('content')
<div class="page-head">
    <div>
        <h1>Leave</h1>
        <p class="muted" style="margin:0.35rem 0 0">Leave board at /leave</p>
    </div>
    @if ($canCreate)
        <button class="btn" type="button" onclick="document.getElementById('create-leave').hidden=false">New request</button>
    @endif
</div>

@if ($canCreate)
<div class="card" id="create-leave" style="margin-bottom:1.25rem" @if(!$errors->any()) hidden @endif>
    <form method="POST" action="{{ route('leave.store') }}">
        @csrf
        <div class="grid-2">
            <div>
                <label>Employee</label>
                <select name="employee_id" onchange="const o=this.options[this.selectedIndex]; document.getElementById('ename').value=o.dataset.name||''">
                    <option value="">—</option>
                    @foreach ($employees as $emp)
                        <option value="{{ $emp->id }}" data-name="{{ $emp->party?->name }}">{{ $emp->party?->name }}</option>
                    @endforeach
                </select>
            </div>
            <div><label>Employee name</label><input id="ename" name="employee_name" value="{{ old('employee_name') }}" required minlength="2"></div>
            <div>
                <label>Leave type</label>
                <select name="leave_type" required>
                    @foreach (['Annual','Sick','Unpaid','Other'] as $t)
                        <option value="{{ $t }}" @selected(old('leave_type')===$t)>{{ $t }}</option>
                    @endforeach
                </select>
            </div>
            <div><label>Start</label><input type="date" name="start_date" value="{{ old('start_date') }}" required></div>
            <div><label>End</label><input type="date" name="end_date" value="{{ old('end_date') }}" required></div>
        </div>
        <label>Reason</label>
        <textarea name="reason" rows="2" required minlength="3">{{ old('reason') }}</textarea>
        <button class="btn" type="submit">Submit</button>
    </form>
</div>
@endif

<div class="card">
    @if ($leaves->isEmpty())
        <p class="muted" style="margin:0">No leave requests</p>
    @else
        <table class="data">
            <thead><tr><th>Employee</th><th>Dates</th><th>Reason</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @foreach ($leaves as $row)
                <tr>
                    <td>{{ $row->employee_name }}</td>
                    <td>{{ $row->start_date?->format('Y-m-d') }} → {{ $row->end_date?->format('Y-m-d') }}</td>
                    <td>{{ $row->reason }}</td>
                    <td><span class="badge">{{ $row->status }}</span></td>
                    <td>
                        @if ($canApprove && $row->status === 'pending')
                            <form method="POST" action="{{ route('leave.update', $row->id) }}" style="display:inline">@csrf @method('PATCH')
                                <input type="hidden" name="status" value="approved">
                                <button class="btn" type="submit">Approve</button>
                            </form>
                            <form method="POST" action="{{ route('leave.update', $row->id) }}" style="display:inline">@csrf @method('PATCH')
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
