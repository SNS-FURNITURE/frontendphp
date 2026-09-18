@extends('layouts.app')

@section('title', 'Employees')

@section('content')
@php
    $statusFilter = request('status', 'ACTIVE');
    $q = strtolower(trim((string) request('q', '')));
    $dept = (string) request('department', '');
    $departments = $employees->pluck('department')->filter()->unique()->sort()->values();
    $filtered = $employees->filter(function ($emp) use ($q, $dept, $statusFilter) {
        $status = strtoupper((string) ($emp->employment_status ?: 'ACTIVE'));
        if ($statusFilter !== 'ALL' && $status !== strtoupper($statusFilter)) {
            return false;
        }
        if ($dept !== '' && strcasecmp((string) $emp->department, $dept) !== 0) {
            return false;
        }
        if ($q === '') {
            return true;
        }
        $hay = strtolower(implode(' ', [
            $emp->party?->name,
            $emp->party?->email,
            $emp->job_title,
            $emp->department,
        ]));

        return str_contains($hay, $q);
    });
@endphp

<div class="page-head">
    <div>
        <h1 class="display-font">Employee Ledger</h1>
        <p class="muted" style="margin:0.35rem 0 0">Roster, salaries, and profile cards for payroll.</p>
    </div>
    @if ($canCreate)
        <button class="btn lime" type="button" onclick="document.getElementById('create-emp').hidden=false">+ Add Employee</button>
    @endif
</div>

@if ($canCreate)
<div class="card" id="create-emp" style="margin-bottom:1.25rem" @if(!$errors->any()) hidden @endif>
    <h2 style="margin:0 0 1rem;font-size:1.1rem">Register employee</h2>
    <form method="POST" action="{{ route('hr.employees.store') }}" enctype="multipart/form-data">
        @csrf
        <div class="grid-2">
            <div><label>Full name</label><input name="name" value="{{ old('name') }}" required minlength="2"></div>
            <div><label>Job title / position</label><input name="job_title" value="{{ old('job_title') }}"></div>
            <div><label>Department</label><input name="department" value="{{ old('department') }}"></div>
            <div><label>Phone</label><input name="phone" value="{{ old('phone') }}"></div>
            <div><label>Email</label><input name="email" type="email" value="{{ old('email') }}"></div>
            <div><label>Hire date</label><input name="hire_date" type="date" value="{{ old('hire_date') }}"></div>
            <div><label>National ID</label><input name="national_id_number" value="{{ old('national_id_number') }}"></div>
            <div><label>Monthly salary (ETB)</label><input name="monthly_salary" type="number" step="0.01" min="0" value="{{ old('monthly_salary') }}"></div>
            <div><label>Bank name</label><input name="bank_name" value="{{ old('bank_name', 'Commercial Bank of Ethiopia') }}"></div>
            <div><label>Bank account</label><input name="bank_account_number" value="{{ old('bank_account_number') }}"></div>
            <div><label>Employee account</label><input name="employee_account" value="{{ old('employee_account') }}"></div>
            <div><label>Address</label><input name="address" value="{{ old('address') }}"></div>
            <div><label>Emergency contact name</label><input name="emergency_contact_name" value="{{ old('emergency_contact_name') }}"></div>
            <div><label>Emergency relationship</label><input name="emergency_contact_relationship" value="{{ old('emergency_contact_relationship') }}"></div>
            <div><label>Emergency phone</label><input name="emergency_contact_phone" value="{{ old('emergency_contact_phone') }}"></div>
            <div>
                <label>Photo (device)</label>
                <input type="file" name="photo" accept="image/*">
            </div>
            <div>
                <label>ID card / scan (device)</label>
                <input type="file" name="id_image" accept="image/*,application/pdf">
            </div>
            <div>
                <label>CV / Resume (device)</label>
                <input type="file" name="cv" accept=".pdf,.doc,.docx,image/*">
            </div>
        </div>
        <div class="toolbar">
            <button class="btn lime" type="submit">Save employee</button>
            <button class="btn ghost" type="button" onclick="document.getElementById('create-emp').hidden=true">Cancel</button>
        </div>
    </form>
</div>
@endif

<form method="GET" class="filter-bar no-print">
    <input type="search" name="q" value="{{ request('q') }}" placeholder="Search by name, position or department...">
    <select name="department" onchange="this.form.submit()">
        <option value="">All departments</option>
        @foreach ($departments as $d)
            <option value="{{ $d }}" @selected($dept === $d)>{{ $d }}</option>
        @endforeach
    </select>
    <input type="hidden" name="status" value="{{ $statusFilter }}">
    <button class="btn ghost" type="submit">Search</button>
</form>

<div class="chip-row no-print" style="margin-bottom:1rem">
    @foreach (['ACTIVE' => 'Active', 'ON_LEAVE' => 'On Leave', 'PROBATION' => 'Probation', 'TERMINATED' => 'Terminated', 'ALL' => 'All Statuses'] as $key => $label)
        <a class="chip {{ $statusFilter === $key ? 'active' : '' }}" href="{{ request()->fullUrlWithQuery(['status' => $key]) }}">{{ $label }}</a>
    @endforeach
</div>

<div class="card" style="overflow-x:auto">
    @if ($filtered->isEmpty())
        <p class="muted" style="margin:0">No employees found</p>
    @else
        <table class="data">
            <thead>
            <tr>
                <th>Employee</th>
                <th>Position</th>
                <th>Department</th>
                <th>Salary</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
            </thead>
            <tbody>
            @foreach ($filtered as $emp)
                @php
                    $name = $emp->party?->name ?: 'Employee';
                    $initials = collect(preg_split('/\s+/', trim($name)))->filter()->take(2)->map(fn ($p) => strtoupper(substr($p, 0, 1)))->implode('');
                    $status = strtoupper((string) ($emp->employment_status ?: 'ACTIVE'));
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
                                <div class="muted">{{ $emp->party?->email ?: '—' }}</div>
                            </div>
                        </div>
                    </td>
                    <td>{{ $emp->job_title ?: '—' }}</td>
                    <td>{{ $emp->department ?: '—' }}</td>
                    <td>{{ $emp->monthly_salary !== null ? 'ETB '.number_format((float) $emp->monthly_salary, 0) : '—' }}</td>
                    <td><span class="badge {{ $status === 'ACTIVE' ? 'badge-ok' : ($status === 'TERMINATED' ? 'badge-danger' : 'badge-warn') }}">{{ $status }}</span></td>
                    <td class="toolbar">
                        <a class="btn ghost" href="{{ route('hr.employees.show', $emp->id) }}">View</a>
                        <a class="btn ghost" href="{{ route('hr.employees.pdf', $emp->id) }}" target="_blank">PDF</a>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif
</div>
@endsection
