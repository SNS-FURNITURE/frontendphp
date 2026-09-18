<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Employee Profile Card — {{ $detail['party']['name'] ?? 'Employee' }}</title>
    <style>
        @page { margin: 18mm; }
        body { font-family: Arial, Helvetica, sans-serif; color: #1a1a1a; margin: 0; }
        .header { background: #6B6D46; color: #fff; padding: 16px 18px; display: flex; justify-content: space-between; align-items: center; }
        .header h1 { margin: 0; font-size: 18px; letter-spacing: 0.04em; }
        .header .co { font-size: 13px; opacity: 0.95; }
        .bar { background: #4f5134; color: #f5f5e8; font-size: 11px; padding: 8px 18px; }
        .wrap { padding: 18px; }
        .hero { display: flex; gap: 18px; align-items: center; margin-bottom: 16px; }
        .photo { width: 96px; height: 96px; border-radius: 12px; object-fit: cover; background: #ece8d8; display: flex; align-items: center; justify-content: center; font-size: 28px; font-weight: 700; }
        .name { font-size: 22px; font-weight: 700; margin: 0 0 4px; }
        .meta { color: #555; font-size: 13px; }
        .badge { display: inline-block; background: #e8f0d8; color: #3f5d1a; border-radius: 999px; padding: 2px 10px; font-size: 11px; font-weight: 700; }
        .section { margin-top: 14px; }
        .section h2 { margin: 0; background: #6B6D46; color: #fff; font-size: 12px; letter-spacing: 0.06em; padding: 7px 10px; }
        .box { background: #f7f4ea; border: 1px solid #e4dfc8; padding: 10px; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px 18px; }
        .label { font-size: 10px; color: #666; text-transform: uppercase; letter-spacing: 0.04em; }
        .val { font-size: 13px; margin-top: 2px; }
        .salary .val.gross, .salary .val.net { color: #3f5d1a; font-weight: 700; }
        .salary .val.tax { color: #9b1c1c; font-weight: 700; }
        @media print { .no-print { display: none !important; } }
    </style>
</head>
<body>
@php
    $name = $detail['party']['name'] ?? 'Employee';
    $initials = collect(preg_split('/\s+/', trim($name)))->filter()->take(2)->map(fn ($p) => strtoupper(substr($p, 0, 1)))->implode('');
@endphp
<div class="no-print" style="padding:12px 18px;display:flex;gap:8px">
    <button onclick="window.print()">Print / Save PDF</button>
    <a href="{{ route('hr.employees.show', $employee->id) }}">Back to profile</a>
</div>
<div class="header">
    <div>
        <div class="co">SNS Furniture</div>
        <h1>EMPLOYEE PROFILE CARD</h1>
    </div>
    <div style="text-align:right;font-size:12px">{{ now()->format('M j, Y') }}</div>
</div>
<div class="bar">Ethiopia · HR &amp; Payroll · Tel / Email on file with SNS Furniture</div>
<div class="wrap">
    <div class="hero">
        @if (! empty($detail['photo_url']))
            <img class="photo" src="{{ $detail['photo_url'] }}" alt="">
        @else
            <div class="photo">{{ $initials ?: 'E' }}</div>
        @endif
        <div>
            <div class="name">{{ $name }}</div>
            <div class="meta">{{ ($detail['job_title'] ?: 'Staff').' | '.($detail['department'] ?: '—') }}</div>
            <div style="margin-top:6px"><span class="badge">{{ strtoupper((string) ($detail['employment_status'] ?? 'ACTIVE')) }}</span></div>
            <div class="meta" style="margin-top:8px">{{ $detail['party']['email'] ?: '—' }} · {{ $detail['party']['phone'] ?: '—' }}</div>
            <div class="meta">Joined {{ $detail['hire_date'] ?: '—' }}</div>
        </div>
    </div>

    <div class="grid">
        <div class="section">
            <h2>PERSONAL INFORMATION</h2>
            <div class="box grid">
                <div><div class="label">National ID</div><div class="val">{{ $detail['national_id_number'] ?: '—' }}</div></div>
                <div><div class="label">Gender</div><div class="val">{{ $detail['gender'] ?: '—' }}</div></div>
                <div><div class="label">City / Country</div><div class="val">{{ ($detail['city'] ?? '—').' / '.($detail['country'] ?? '—') }}</div></div>
                <div><div class="label">Address</div><div class="val">{{ $detail['address'] ?: '—' }}</div></div>
            </div>
        </div>
        <div class="section">
            <h2>PROFESSIONAL INFORMATION</h2>
            <div class="box grid">
                <div><div class="label">Employment type</div><div class="val">{{ $detail['employment_type'] ?: 'Full-Time' }}</div></div>
                <div><div class="label">Employee ID</div><div class="val">{{ $detail['employee_number'] }}</div></div>
                <div><div class="label">Bank</div><div class="val">{{ $detail['bank_name'] ?: '—' }}</div></div>
                <div><div class="label">Account</div><div class="val">{{ $detail['bank_account_number'] ?: '—' }}</div></div>
            </div>
        </div>
    </div>

    <div class="section salary">
        <h2>SALARY INFORMATION</h2>
        <div class="box grid">
            <div><div class="label">Gross salary</div><div class="val gross">{{ isset($salary['gross']) ? 'ETB '.number_format($salary['gross'], 2) : '—' }}</div></div>
            <div><div class="label">Income tax</div><div class="val tax">{{ isset($salary['paye']) ? 'ETB '.number_format($salary['paye'], 2) : '—' }}</div></div>
            <div><div class="label">Net salary</div><div class="val net">{{ isset($salary['net']) ? 'ETB '.number_format($salary['net'], 2) : '—' }}</div></div>
            <div><div class="label">Pay cycle</div><div class="val">Monthly</div></div>
        </div>
    </div>

    <div class="section">
        <h2>EMERGENCY CONTACT</h2>
        <div class="box">
            <div class="val">
                {{ $detail['emergency_contact_name'] ?: '—' }}
                @if (! empty($detail['emergency_contact_relationship']))
                    ({{ $detail['emergency_contact_relationship'] }})
                @endif
                @if (! empty($detail['emergency_contact_phone']))
                    — {{ $detail['emergency_contact_phone'] }}
                @endif
            </div>
        </div>
    </div>

    @if (! empty($detail['id_image_url']))
        <div class="section">
            <h2>GOVERNMENT IDENTIFICATION</h2>
            <div class="box">
                <div class="label">ID number</div>
                <div class="val">{{ $detail['national_id_number'] ?: '—' }}</div>
                <img src="{{ $detail['id_image_url'] }}" alt="ID" style="max-width:100%;margin-top:10px;border:1px solid #ddd">
            </div>
        </div>
    @endif
</div>
<script>window.addEventListener('load', function () { /* ready for print */ });</script>
</body>
</html>
