@extends('layouts.app')

@section('title', 'Users · SNS Furniture')

@section('content')
<div class="page-head">
    <div>
        <h1>Users &amp; employee accounts</h1>
        <p class="muted" style="margin:.35rem 0 0">
            Admin only: hire a staff member by creating their ERP login + employee HR record.
            Default password is <strong>password123</strong>. Email defaults to <code>firstname.lastname@sns.com</code>.
        </p>
    </div>
</div>

@if (session('status'))
    <div class="card" style="margin-bottom:1rem;border-color:#16a34a">
        <p style="margin:0;color:#15803d">{{ session('status') }}</p>
    </div>
@endif

@if ($errors->any())
    <div class="card" style="margin-bottom:1rem;border-color:#dc2626">
        <ul style="margin:0;padding-left:1.2rem;color:#b91c1c">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="card" style="margin-bottom:1.25rem">
    <h2 style="margin-top:0;font-size:1.1rem">Create employee ERP account</h2>
    <form method="POST" action="{{ route('admin.users.store') }}" id="hire-form">
        @csrf

        <h3 style="font-size:.95rem;margin:0 0 .75rem;color:var(--muted, #64748b)">Account &amp; access</h3>
        <div class="grid-2">
            <div>
                <label for="full_name">Full name *</label>
                <input id="full_name" name="full_name" value="{{ old('full_name') }}" required minlength="2"
                       placeholder="e.g. Abebe Kebede" autocomplete="name">
                <p class="muted" style="margin:.35rem 0 0;font-size:.8rem">Used to build login email automatically.</p>
            </div>
            <div>
                <label for="email">Login email (optional override)</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}"
                       placeholder="auto: name@sns.com">
                <p class="muted" style="margin:.35rem 0 0;font-size:.8rem" id="email-preview">Leave blank to auto-generate.</p>
            </div>
            <div>
                <label for="phone">Phone</label>
                <input id="phone" name="phone" value="{{ old('phone') }}" placeholder="+2519…">
            </div>
            <div>
                <label for="role">ERP role *</label>
                <select id="role" name="role" required>
                    @foreach ($roles as $role)
                        <option value="{{ $role->name }}" @selected(old('role') === $role->name)>{{ $role->name }}</option>
                    @endforeach
                </select>
                <p class="muted" style="margin:.35rem 0 0;font-size:.8rem">Admin role cannot be assigned here.</p>
            </div>
            <div>
                <label>Password</label>
                <input type="text" value="password123" disabled>
                <input type="hidden" name="password_locked" value="1">
                <p class="muted" style="margin:.35rem 0 0;font-size:.8rem">Fixed default — employee should change after first login.</p>
            </div>
        </div>

        <h3 style="font-size:.95rem;margin:1.25rem 0 .75rem;color:var(--muted, #64748b)">Employee / HR details</h3>
        <div class="grid-2">
            <div>
                <label for="job_title">Job title</label>
                <input id="job_title" name="job_title" value="{{ old('job_title') }}">
            </div>
            <div>
                <label for="department">Department</label>
                <input id="department" name="department" value="{{ old('department') }}">
            </div>
            <div>
                <label for="hire_date">Hire date</label>
                <input id="hire_date" name="hire_date" type="date" value="{{ old('hire_date', now()->toDateString()) }}">
            </div>
            <div>
                <label for="monthly_salary">Monthly salary (ETB)</label>
                <input id="monthly_salary" name="monthly_salary" type="number" step="0.01" min="0" value="{{ old('monthly_salary') }}">
            </div>
            <div>
                <label for="employee_no">Employee number</label>
                <input id="employee_no" name="employee_no" value="{{ old('employee_no') }}" placeholder="Auto if blank">
            </div>
            <div>
                <label for="national_id_number">National ID</label>
                <input id="national_id_number" name="national_id_number" value="{{ old('national_id_number') }}">
            </div>
            <div>
                <label for="bank_name">Bank name</label>
                <input id="bank_name" name="bank_name" value="{{ old('bank_name', 'Dashen Bank') }}">
            </div>
            <div>
                <label for="bank_account_number">Bank account number</label>
                <input id="bank_account_number" name="bank_account_number" value="{{ old('bank_account_number') }}">
            </div>
            <div>
                <label for="employee_account">Employee account (payroll ref)</label>
                <input id="employee_account" name="employee_account" value="{{ old('employee_account') }}">
            </div>
            <div>
                <label for="emergency_contact_name">Emergency contact name</label>
                <input id="emergency_contact_name" name="emergency_contact_name" value="{{ old('emergency_contact_name') }}">
            </div>
            <div>
                <label for="emergency_contact_relationship">Emergency contact relationship</label>
                <input id="emergency_contact_relationship" name="emergency_contact_relationship" value="{{ old('emergency_contact_relationship') }}">
            </div>
            <div>
                <label for="emergency_contact_phone">Emergency contact phone</label>
                <input id="emergency_contact_phone" name="emergency_contact_phone" value="{{ old('emergency_contact_phone') }}">
            </div>
        </div>

        <div class="toolbar" style="margin-top:1rem">
            <button type="submit" class="btn">Create employee account</button>
        </div>
    </form>
</div>

<div class="card">
    <h2 style="margin-top:0;font-size:1.1rem">Existing accounts</h2>
    <table class="data">
        <thead>
        <tr>
            <th>Name</th>
            <th>Username</th>
            <th>Email</th>
            <th>Roles</th>
            <th>Active</th>
            <th>Created</th>
        </tr>
        </thead>
        <tbody>
        @forelse ($users as $user)
            <tr>
                <td>{{ $user->full_name }}</td>
                <td>{{ '@'.($user->username ?: '—') }}</td>
                <td>{{ $user->email }}</td>
                <td>{{ $user->roles->pluck('name')->join(', ') ?: '—' }}</td>
                <td>{{ $user->is_active ? 'Yes' : 'No' }}</td>
                <td>{{ optional($user->created_at)->format('Y-m-d') }}</td>
            </tr>
        @empty
            <tr><td colspan="6" class="muted">No users found.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>

<script>
(function () {
    const nameInput = document.getElementById('full_name');
    const emailInput = document.getElementById('email');
    const preview = document.getElementById('email-preview');
    if (!nameInput || !preview) return;

    function slugEmail(name) {
        const slug = String(name || '')
            .toLowerCase()
            .trim()
            .replace(/[^a-z0-9]+/g, '.')
            .replace(/^\.+|\.+$/g, '')
            .replace(/\.+/g, '.')
            .slice(0, 64) || 'employee';
        return slug + '@sns.com';
    }

    function updatePreview() {
        if (emailInput && emailInput.value.trim() !== '') {
            preview.textContent = 'Will use: ' + emailInput.value.trim().toLowerCase();
            return;
        }
        preview.textContent = 'Will use: ' + slugEmail(nameInput.value);
    }

    nameInput.addEventListener('input', updatePreview);
    if (emailInput) emailInput.addEventListener('input', updatePreview);
    updatePreview();
})();
</script>
@endsection
