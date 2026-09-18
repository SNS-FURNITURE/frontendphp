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
        <button class="btn lime" type="button" onclick="document.getElementById('create-emp').hidden=false; window.scrollTo({top:0,behavior:'smooth'})">+ Add Employee</button>
    @endif
</div>

@if ($canCreate)
<div class="emp-form-panel" id="create-emp" @if(!$errors->any()) hidden @endif x-data="employeeForm()">
    <div class="emp-form-head">
        <div>
            <h2>Add employee</h2>
            <p class="muted" style="margin:0.35rem 0 0">Create a full profile for payroll, attendance, and documents.</p>
        </div>
        <button class="btn ghost" type="button" onclick="document.getElementById('create-emp').hidden=true">Close</button>
    </div>
    <div class="emp-form-body">
        <form method="POST" action="{{ route('hr.employees.store') }}" enctype="multipart/form-data">
            @csrf
            <div class="emp-form-layout">
                <aside class="emp-form-photo">
                    <div class="preview">
                        <template x-if="photoUrl">
                            <img :src="photoUrl" alt="Photo preview">
                        </template>
                        <template x-if="!photoUrl">
                            <span x-text="initials">+</span>
                        </template>
                    </div>
                    <label>Profile photo</label>
                    <input type="file" name="photo" accept="image/*" @change="onPhoto($event)">
                    <p class="hint muted" style="margin:0.45rem 0 0;font-size:0.75rem">JPG/PNG up to 5MB</p>
                </aside>

                <div>
                    <div class="emp-form-section">
                        <h3><span class="ico">1</span> Personal information</h3>
                        <div class="grid-2">
                            <div><label>Full name *</label><input name="name" value="{{ old('name') }}" required minlength="2" x-model="name" placeholder="e.g. Abrham Wendesen"></div>
                            <div><label>National ID</label><input name="national_id_number" value="{{ old('national_id_number') }}" placeholder="Government ID number"></div>
                            <div><label>Phone</label><input name="phone" value="{{ old('phone') }}" placeholder="09…"></div>
                            <div><label>Email</label><input name="email" type="email" value="{{ old('email') }}" placeholder="name@company.com"></div>
                            <div style="grid-column:1 / -1"><label>Address</label><input name="address" value="{{ old('address') }}" placeholder="City, subcity, woreda…"></div>
                        </div>
                    </div>

                    <div class="emp-form-section">
                        <h3><span class="ico">2</span> Professional details</h3>
                        <div class="grid-2">
                            <div><label>Position / job title</label><input name="job_title" value="{{ old('job_title') }}" placeholder="Worker, Cleaner…"></div>
                            <div><label>Department</label><input name="department" value="{{ old('department') }}" placeholder="Engineering"></div>
                            <div><label>Date joined</label><input name="hire_date" type="date" value="{{ old('hire_date', now()->toDateString()) }}"></div>
                            <div><label>Employee account</label><input name="employee_account" value="{{ old('employee_account') }}" placeholder="Internal account code"></div>
                        </div>
                    </div>

                    <div class="emp-form-section">
                        <h3><span class="ico">$</span> Salary &amp; banking</h3>
                        <div class="grid-2">
                            <div><label>Gross monthly salary (ETB)</label><input name="monthly_salary" type="number" step="0.01" min="0" value="{{ old('monthly_salary') }}" placeholder="40000"></div>
                            <div><label>Bank name</label><input name="bank_name" value="{{ old('bank_name', 'Commercial Bank of Ethiopia') }}"></div>
                            <div style="grid-column:1 / -1"><label>Bank account number</label><input name="bank_account_number" value="{{ old('bank_account_number') }}" placeholder="Account number"></div>
                        </div>
                        <p class="muted" style="margin:0.35rem 0 0;font-size:0.78rem">Tax and net are calculated on payroll using Ethiopia PAYE.</p>
                    </div>

                    <div class="emp-form-section">
                        <h3><span class="ico">E</span> Emergency contact</h3>
                        <div class="grid-2">
                            <div><label>Contact name</label><input name="emergency_contact_name" value="{{ old('emergency_contact_name') }}"></div>
                            <div><label>Relationship</label><input name="emergency_contact_relationship" value="{{ old('emergency_contact_relationship') }}" placeholder="Mother, spouse…"></div>
                            <div><label>Phone</label><input name="emergency_contact_phone" value="{{ old('emergency_contact_phone') }}"></div>
                        </div>
                    </div>

                    <div class="emp-form-section">
                        <h3><span class="ico">D</span> Documents</h3>
                        <div class="grid-2">
                            <div class="file-tile">
                                <strong>ID card / scan</strong>
                                <span class="hint">Image or PDF · up to 8MB</span>
                                <input type="file" name="id_image" accept="image/*,application/pdf">
                            </div>
                            <div class="file-tile">
                                <strong>CV / Resume</strong>
                                <span class="hint">PDF, DOC, DOCX · up to 10MB</span>
                                <input type="file" name="cv" accept=".pdf,.doc,.docx,image/*">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="emp-form-actions">
                <button class="btn ghost" type="button" onclick="document.getElementById('create-emp').hidden=true">Cancel</button>
                <button class="btn lime" type="submit">Save employee</button>
            </div>
        </form>
    </div>
</div>
@endif

@push('scripts')
<script>
function employeeForm() {
    return {
        name: @json(old('name', '')),
        photoUrl: null,
        get initials() {
            const parts = String(this.name || '').trim().split(/\s+/).filter(Boolean).slice(0, 2);
            if (!parts.length) return '+';
            return parts.map(p => p.charAt(0).toUpperCase()).join('');
        },
        onPhoto(e) {
            const file = e.target.files && e.target.files[0];
            if (!file) { this.photoUrl = null; return; }
            if (this.photoUrl) URL.revokeObjectURL(this.photoUrl);
            this.photoUrl = URL.createObjectURL(file);
        }
    };
}
</script>
@endpush

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
