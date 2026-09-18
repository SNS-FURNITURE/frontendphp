@extends('layouts.app')

@section('title', $detail['party']['name'] ?? 'Employee')

@section('content')
<div class="page-head">
    <div>
        <h1>{{ $detail['party']['name'] ?? 'Employee' }}</h1>
        <p class="muted" style="margin:0.35rem 0 0">{{ $detail['employee_number'] }} · {{ $detail['job_title'] ?: '—' }}</p>
    </div>
    <a class="btn ghost" href="{{ route('hr.employees') }}">Back</a>
</div>

<div class="card">
    <div class="grid-2">
        <div><span class="muted">Department</span><div>{{ $detail['department'] ?: '—' }}</div></div>
        <div><span class="muted">Hire date</span><div>{{ $detail['hire_date'] ?: '—' }}</div></div>
        <div><span class="muted">Phone</span><div>{{ $detail['party']['phone'] ?: '—' }}</div></div>
        <div><span class="muted">Email</span><div>{{ $detail['party']['email'] ?: '—' }}</div></div>
        <div><span class="muted">City / Country</span><div>{{ $detail['city'] }}, {{ $detail['country'] }}</div></div>
        <div><span class="muted">Status</span><div>{{ $detail['employment_status'] }}</div></div>
        <div><span class="muted">Bank</span><div>{{ $detail['bank_name'] }} · {{ $detail['bank_account_number'] ?: '—' }}</div></div>
        <div><span class="muted">Salary</span><div>{{ $detail['monthly_salary'] !== null ? number_format($detail['monthly_salary'], 2).' ETB' : '—' }}</div></div>
        <div><span class="muted">Emergency</span><div>{{ $detail['emergency_contact_name'] ?: '—' }} {{ $detail['emergency_contact_phone'] ? '· '.$detail['emergency_contact_phone'] : '' }}</div></div>
    </div>
</div>
@endsection
