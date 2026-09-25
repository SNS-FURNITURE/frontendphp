@extends('layouts.app')

@section('title', auth()->user()->isSalesRep() ? 'My Customers' : 'Customer Contacts')

@section('content')
<div class="page-head">
    <div>
        <h1>@if (auth()->user()->isSalesRep()) My customers @else Customer contacts @endif</h1>
        @if ($canReview)
            <p class="muted" style="margin:.35rem 0 0">
                <a href="{{ route('sales.customers') }}">All</a> ·
                <a href="{{ route('sales.customers', ['filter' => 'pending']) }}">Pending review</a>
            </p>
        @endif
    </div>
    <div class="toolbar">
        @if ($canCreate)
            <button class="btn" type="button" onclick="document.getElementById('create-customer').hidden=false">Add contact</button>
        @endif
    </div>
</div>

@if ($canCreate)
<div class="card" id="create-customer" style="margin-bottom:1.25rem" @if(!$errors->any() || !old('name')) hidden @endif>
    <h2 style="margin:0 0 1rem;font-size:1.1rem">New customer contact</h2>
    <p class="muted" style="margin:0 0 1rem">Submitted contacts go to a sales supervisor for approval. Each customer name and address pair must be unique.</p>
    <form method="POST" action="{{ route('sales.customers.store') }}">
        @csrf
        <div class="grid-2">
            <div>
                <label for="name">Name *</label>
                <input id="name" name="name" value="{{ old('name') }}" required minlength="2">
            </div>
            <div>
                <label for="phone">Phone *</label>
                <input id="phone" name="phone" value="{{ old('phone') }}" required minlength="8">
            </div>
            <div>
                <label for="address">Address</label>
                <input id="address" name="address" value="{{ old('address') }}">
            </div>
            <div>
                <label for="notes">Notes</label>
                <input id="notes" name="notes" value="{{ old('notes') }}">
            </div>
        </div>
        <div class="toolbar">
            <button class="btn" type="submit">Submit for review</button>
            <button class="btn ghost" type="button" onclick="document.getElementById('create-customer').hidden=true">Cancel</button>
        </div>
    </form>
</div>
@endif

<div class="card">
    @if ($customers->isEmpty())
        <p class="muted" style="margin:0;text-align:center;padding:2rem">No contacts yet</p>
    @else
        <table class="data">
            <thead>
            <tr>
                <th>Name</th>
                <th>Phone</th>
                <th>Address</th>
                <th>Status</th>
                @unless (auth()->user()->isSalesRep())
                    <th>Submitted by</th>
                @endunless
                @if ($canReview)
                    <th></th>
                @endif
            </tr>
            </thead>
            <tbody>
            @foreach ($customers as $party)
                <tr>
                    <td>{{ $party->name }}</td>
                    <td>{{ $party->phone ?? '—' }}</td>
                    <td>{{ $party->address ?? '—' }}</td>
                    <td>{{ ucfirst($party->approval_status) }}</td>
                    @unless (auth()->user()->isSalesRep())
                        <td>{{ $party->creator?->full_name ?? '—' }}</td>
                    @endunless
                    @if ($canReview && $party->approval_status === 'pending')
                        <td>
                            <div style="display:flex;gap:.5rem">
                                <form method="POST" action="{{ route('sales.customers.approve', $party) }}">@csrf @method('PATCH')
                                    <input type="hidden" name="approval_status" value="approved">
                                    <button type="submit" class="btn" style="padding:.25rem .5rem;font-size:.8rem">Approve</button>
                                </form>
                                <form method="POST" action="{{ route('sales.customers.approve', $party) }}">@csrf @method('PATCH')
                                    <input type="hidden" name="approval_status" value="rejected">
                                    <button type="submit" class="btn ghost" style="padding:.25rem .5rem;font-size:.8rem;color:#dc2626">Reject</button>
                                </form>
                            </div>
                        </td>
                    @elseif ($canReview)
                        <td class="muted">—</td>
                    @endif
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif
</div>
@endsection
