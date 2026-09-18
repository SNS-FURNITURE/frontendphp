@extends('layouts.app')

@section('title', 'Customers')

@section('content')
<div class="page-head">
    <div>
        <h1>Customers</h1>
        <p class="muted" style="margin:0.35rem 0 0">Parties merged with leads and deal counts.</p>
    </div>
    <div class="toolbar">
        <form method="GET" action="{{ route('sales.customers') }}" style="display:flex;gap:0.5rem;align-items:center;margin:0">
            <select name="approval_status" onchange="this.form.submit()" style="margin:0;width:auto;min-width:8rem">
                <option value="">All statuses</option>
                @foreach (['pending','approved','rejected'] as $st)
                    <option value="{{ $st }}" @selected($approval === $st)>{{ $st }}</option>
                @endforeach
            </select>
        </form>
        @if ($canCreate)
            <button class="btn" type="button" onclick="document.getElementById('create-customer').hidden=false">New customer</button>
        @endif
    </div>
</div>

@if ($canCreate)
<div class="card" id="create-customer" style="margin-bottom:1.25rem" @if(!$errors->any() || !old('name')) hidden @endif>
    <h2 style="margin:0 0 1rem;font-size:1.1rem">Create customer</h2>
    <form method="POST" action="{{ route('sales.customers.store') }}">
        @csrf
        <div class="grid-2">
            <div>
                <label for="name">Name</label>
                <input id="name" name="name" value="{{ old('name') }}" required minlength="2">
            </div>
            <div>
                <label for="phone">Phone</label>
                <input id="phone" name="phone" value="{{ old('phone') }}" required minlength="8">
            </div>
            <div>
                <label for="email">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}">
            </div>
            <div>
                <label for="company_name">Company</label>
                <input id="company_name" name="company_name" value="{{ old('company_name') }}">
            </div>
        </div>
        <label for="address">Address</label>
        <input id="address" name="address" value="{{ old('address') }}">
        <label for="notes">Notes</label>
        <textarea id="notes" name="notes" rows="2">{{ old('notes') }}</textarea>
        <p class="muted" style="margin:0 0 0.75rem">New customers always start as <strong>pending</strong> until an advisor approves.</p>
        <div class="toolbar">
            <button class="btn" type="submit">Save</button>
            <button class="btn ghost" type="button" onclick="document.getElementById('create-customer').hidden=true">Cancel</button>
        </div>
    </form>
</div>
@endif

<div class="card">
    @if ($customers->isEmpty() && empty($orphanLeads))
        <p class="muted" style="margin:0;text-align:center;padding:2rem">No customers yet</p>
    @else
        <table class="data">
            <thead>
            <tr>
                <th>Name</th>
                <th>Phone</th>
                <th>Email</th>
                <th>Status</th>
                <th>Deals</th>
                <th>Lead</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            @foreach ($customers as $row)
                @php $party = $row['party']; @endphp
                <tr>
                    <td>{{ $party->name }}@if($party->company_name)<div class="muted">{{ $party->company_name }}</div>@endif</td>
                    <td>{{ $party->phone ?? '—' }}</td>
                    <td>{{ $party->email ?? '—' }}</td>
                    <td><span class="badge">{{ $party->approval_status }}</span></td>
                    <td>{{ $row['deal_count'] }}</td>
                    <td>
                        @if ($row['lead'])
                            <span class="badge">{{ $row['lead']->status }}</span>
                        @else
                            —
                        @endif
                    </td>
                    <td style="white-space:nowrap">
                        @if ($canApprove && $party->approval_status === 'pending')
                            <form method="POST" action="{{ route('sales.customers.approve', $party) }}" style="display:inline">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="approval_status" value="approved">
                                <button class="btn" type="submit" style="padding:0.3rem 0.55rem;font-size:0.75rem">Approve</button>
                            </form>
                            <form method="POST" action="{{ route('sales.customers.approve', $party) }}" style="display:inline">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="approval_status" value="rejected">
                                <button class="btn danger" type="submit" style="padding:0.3rem 0.55rem;font-size:0.75rem">Reject</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @endforeach
            @foreach ($orphanLeads as $lead)
                <tr>
                    <td>{{ $lead->name }} <span class="muted">(lead only)</span></td>
                    <td>{{ $lead->phone ?? '—' }}</td>
                    <td>{{ $lead->email ?? '—' }}</td>
                    <td><span class="badge">lead</span></td>
                    <td>—</td>
                    <td><span class="badge">{{ $lead->status }}</span></td>
                    <td></td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif
</div>
@endsection
