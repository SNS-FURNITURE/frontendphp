@extends('layouts.app')

@section('title', 'Employees')

@section('content')
<div class="page-head">
    <div>
        <h1>Employees</h1>
        <p class="muted" style="margin:0.35rem 0 0">HR roster linked to party type employee.</p>
    </div>
    @if ($canCreate)
        <button class="btn" type="button" onclick="document.getElementById('create-emp').hidden=false">Register</button>
    @endif
</div>

@if ($canCreate)
<div class="card" id="create-emp" style="margin-bottom:1.25rem" @if(!$errors->any()) hidden @endif>
    <h2 style="margin:0 0 1rem;font-size:1.1rem">Register employee</h2>
    <form method="POST" action="{{ route('hr.employees.store') }}">
        @csrf
        <div class="grid-2">
            <div><label>Name</label><input name="name" value="{{ old('name') }}" required minlength="2"></div>
            <div><label>Job title</label><input name="job_title" value="{{ old('job_title') }}"></div>
            <div><label>Department</label><input name="department" value="{{ old('department') }}"></div>
            <div><label>Phone</label><input name="phone" value="{{ old('phone') }}"></div>
            <div><label>Email</label><input name="email" type="email" value="{{ old('email') }}"></div>
            <div><label>Hire date</label><input name="hire_date" type="date" value="{{ old('hire_date') }}"></div>
            <div><label>Monthly salary</label><input name="monthly_salary" type="number" step="0.01" min="0" value="{{ old('monthly_salary') }}"></div>
            <div><label>Bank name</label><input name="bank_name" value="{{ old('bank_name', 'Dashen Bank') }}"></div>
            <div><label>Bank account</label><input name="bank_account_number" value="{{ old('bank_account_number') }}"></div>
            <div><label>Employee account</label><input name="employee_account" value="{{ old('employee_account') }}"></div>
        </div>
        <div class="toolbar">
            <button class="btn" type="submit">Save</button>
            <button class="btn ghost" type="button" onclick="document.getElementById('create-emp').hidden=true">Cancel</button>
        </div>
    </form>
</div>
@endif

<div class="card">
    @if ($employees->isEmpty())
        <p class="muted" style="margin:0">No employees found</p>
    @else
        <table class="data">
            <thead><tr><th>Name</th><th>Title</th><th>Department</th><th>Phone</th><th></th></tr></thead>
            <tbody>
            @foreach ($employees as $emp)
                <tr>
                    <td>{{ $emp->party?->name }}</td>
                    <td>{{ $emp->job_title ?: '—' }}</td>
                    <td>{{ $emp->department ?: '—' }}</td>
                    <td>{{ $emp->party?->phone ?: '—' }}</td>
                    <td><a href="{{ route('hr.employees.show', $emp->id) }}">View</a></td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif
</div>
@endsection
