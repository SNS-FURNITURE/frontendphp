@extends('layouts.app')

@section('title', $detail['party']['name'] ?? 'Employee')

@section('content')
@php
    $name = $detail['party']['name'] ?? 'Employee';
    $initials = collect(preg_split('/\s+/', trim($name)))->filter()->take(2)->map(fn ($p) => strtoupper(substr($p, 0, 1)))->implode('');
    $status = strtoupper((string) ($detail['employment_status'] ?? 'ACTIVE'));
    $gross = $salary['gross'] ?? null;
    $paye = $salary['paye'] ?? null;
    $net = $salary['net'] ?? null;
@endphp

<div class="page-head no-print">
    <div>
        <h1 class="display-font">Employee profile</h1>
        <p class="muted" style="margin:0.35rem 0 0">{{ $detail['employee_number'] }}</p>
    </div>
    <div class="toolbar">
        <a class="btn ghost" href="{{ route('hr.employees') }}">Back</a>
        <a class="btn lime" href="{{ route('hr.employees.pdf', $employee->id) }}" target="_blank">Download PDF</a>
    </div>
</div>

<div class="profile-shell">
    <aside class="profile-side">
        @if (! empty($detail['photo_url']))
            <img class="avatar lg" src="{{ $detail['photo_url'] }}" alt="">
        @else
            <span class="avatar lg">{{ $initials ?: 'E' }}</span>
        @endif
        <div>
            <h2>{{ $name }}</h2>
            <div class="profile-role">{{ $detail['job_title'] ?: 'Staff' }}</div>
            <div style="margin-top:0.5rem"><span class="badge {{ $status === 'ACTIVE' ? 'badge-ok' : 'badge-warn' }}">{{ $status }}</span></div>
        </div>
        <div class="profile-contact">
            <div>{{ $detail['party']['email'] ?: '—' }}</div>
            <div>{{ $detail['party']['phone'] ?: '—' }}</div>
            <div>{{ ($detail['city'] ?? 'Addis Ababa').', '.($detail['country'] ?? 'Ethiopia') }}</div>
        </div>
        <a class="btn lime no-print" href="{{ route('hr.employees.pdf', $employee->id) }}" target="_blank">Download PDF</a>
    </aside>

    <div>
        <div class="profile-section">
            <h3><span class="ico">P</span> Professional details</h3>
            <div class="grid-2">
                <div><span class="muted">Department</span><div>{{ $detail['department'] ?: '—' }}</div></div>
                <div><span class="muted">Employment type</span><div>{{ $detail['employment_type'] ?: 'Full-Time' }}</div></div>
                <div><span class="muted">Employee ID</span><div>{{ $detail['employee_number'] }}</div></div>
                <div><span class="muted">Date joined</span><div>{{ $detail['hire_date'] ?: '—' }}</div></div>
            </div>
        </div>

        <div class="profile-section">
            <h3><span class="ico">$</span> Salary &amp; benefits</h3>
            <div class="salary-box">
                <div>
                    <span class="muted">Gross salary</span>
                    <div class="val-lime">{{ $gross !== null ? 'ETB '.number_format($gross, 0) : '—' }}</div>
                </div>
                <div>
                    <span class="muted">Income tax</span>
                    <div class="val-red">{{ $paye !== null ? 'ETB '.number_format($paye, 0) : '—' }}</div>
                </div>
                <div>
                    <span class="muted">Net salary</span>
                    <div class="val-lime">{{ $net !== null ? 'ETB '.number_format($net, 0) : '—' }}</div>
                </div>
            </div>
            <p class="muted" style="margin:0.55rem 0 0">Monthly estimate · Ethiopia PAYE · pension 7% deducted from net formula on payroll runs</p>
        </div>

        <div class="profile-section">
            <h3><span class="ico">I</span> Personal information</h3>
            <div class="grid-2">
                <div><span class="muted">National ID</span><div>{{ $detail['national_id_number'] ?: '—' }}</div></div>
                <div><span class="muted">Gender</span><div>{{ $detail['gender'] ?: '—' }}</div></div>
                <div><span class="muted">City</span><div>{{ $detail['city'] ?: '—' }}</div></div>
                <div><span class="muted">Country</span><div>{{ $detail['country'] ?: '—' }}</div></div>
                <div><span class="muted">Address</span><div>{{ $detail['address'] ?: '—' }}</div></div>
                <div><span class="muted">Employee account</span><div>{{ $detail['employee_account'] ?: '—' }}</div></div>
            </div>
        </div>

        <div class="profile-section">
            <h3><span class="ico">B</span> Banking details</h3>
            <div class="grid-2">
                <div><span class="muted">Bank name</span><div>{{ $detail['bank_name'] ?: '—' }}</div></div>
                <div><span class="muted">Account number</span><div>{{ $detail['bank_account_number'] ?: '—' }}</div></div>
            </div>
        </div>

        <div class="profile-section">
            <h3><span class="ico">E</span> Emergency contact</h3>
            <div class="grid-2">
                <div><span class="muted">Name</span><div>{{ $detail['emergency_contact_name'] ?: '—' }}</div></div>
                <div><span class="muted">Relationship</span><div>{{ $detail['emergency_contact_relationship'] ?: '—' }}</div></div>
                <div><span class="muted">Phone</span><div>{{ $detail['emergency_contact_phone'] ?: '—' }}</div></div>
            </div>
        </div>

        <div class="profile-section">
            <h3><span class="ico">D</span> Documents</h3>
            <div class="doc-grid">
                <div class="doc-card">
                    <div><strong>ID card</strong> <span class="ok-dot"></span></div>
                    <div class="muted">{{ $detail['id_image_url'] ? 'On file' : 'Not uploaded' }}</div>
                    @if (! empty($detail['id_image_url']))
                        <a href="{{ $detail['id_image_url'] }}" target="_blank">View</a>
                    @endif
                </div>
                <div class="doc-card">
                    <div><strong>CV / Resume</strong> <span class="ok-dot"></span></div>
                    <div class="muted">{{ $detail['cv_url'] ? 'On file' : 'Not uploaded' }}</div>
                    @if (! empty($detail['cv_url']))
                        <a href="{{ $detail['cv_url'] }}" target="_blank">View</a>
                    @endif
                </div>
                <div class="doc-card">
                    <div><strong>Photo</strong> <span class="ok-dot"></span></div>
                    <div class="muted">{{ $detail['photo_url'] ? 'On file' : 'Not uploaded' }}</div>
                </div>
            </div>
            @if ($canEdit ?? false)
                <form method="POST" action="{{ route('hr.employees.documents', $employee->id) }}" enctype="multipart/form-data" style="margin-top:1rem">
                    @csrf
                    @method('PATCH')
                    <div class="grid-3">
                        <div>
                            <label>Upload photo</label>
                            <input type="file" name="photo" accept="image/*">
                        </div>
                        <div>
                            <label>Upload ID card</label>
                            <input type="file" name="id_image" accept="image/*,application/pdf">
                        </div>
                        <div>
                            <label>Upload CV</label>
                            <input type="file" name="cv" accept=".pdf,.doc,.docx,image/*">
                        </div>
                    </div>
                    <button class="btn lime" type="submit">Upload from device</button>
                </form>
            @endif
        </div>
    </div>
</div>
@endsection
