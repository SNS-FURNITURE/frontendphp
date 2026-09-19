@extends('layouts.app')

@section('title', 'Material requests')

@section('content')
<div class="page-head">
    <div>
        <h1>Material requests</h1>
        <p class="muted" style="margin:0.35rem 0 0">Request raw materials and track fulfillment.</p>
    </div>
    <div class="toolbar">
        <form method="GET" action="{{ route('material-requests.index') }}" style="display:flex;gap:0.5rem;align-items:center;margin:0">
            <select name="status" onchange="this.form.submit()" style="margin:0;width:auto;min-width:9rem">
                <option value="">All statuses</option>
                @foreach ($statuses as $s)
                    <option value="{{ $s }}" @selected($status === $s)>{{ $s }}</option>
                @endforeach
            </select>
        </form>
        @if ($canCreate)
            <button class="btn" type="button" onclick="document.getElementById('create-mr').hidden=false">New request</button>
        @endif
    </div>
</div>

@if ($canCreate)
<div class="card" id="create-mr" style="margin-bottom:1.25rem" @if(!$errors->any() || !old('title')) hidden @endif>
    <h2 style="margin:0 0 1rem;font-size:1.1rem">Submit material request</h2>
    <form method="POST" action="{{ route('material-requests.store') }}">
        @csrf
        <div class="grid-2">
            <div>
                <label for="title">Title</label>
                <input id="title" name="title" value="{{ old('title') }}" required minlength="2">
            </div>
            <div>
                <label for="item_name">Item name</label>
                <input id="item_name" name="item_name" value="{{ old('item_name') }}" required minlength="2">
            </div>
            <div>
                <label for="quantity">Quantity</label>
                <input id="quantity" name="quantity" type="number" min="1" value="{{ old('quantity', 1) }}" required>
            </div>
            <div>
                <label for="unit">Unit</label>
                <input id="unit" name="unit" value="{{ old('unit', 'pcs') }}">
            </div>
            <div>
                <label for="urgency">Urgency</label>
                <select id="urgency" name="urgency">
                    @foreach (['standard','high','urgent'] as $u)
                        <option value="{{ $u }}" @selected(old('urgency', 'standard') === $u)>{{ $u }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <label for="notes">Notes</label>
        <textarea id="notes" name="notes" rows="2">{{ old('notes') }}</textarea>
        <div class="toolbar">
            <button class="btn" type="submit">Submit</button>
            <button class="btn ghost" type="button" onclick="document.getElementById('create-mr').hidden=true">Cancel</button>
        </div>
    </form>
</div>
@endif

<div class="card">
    @if ($requests->isEmpty())
        <p class="muted" style="margin:0">No material requests</p>
    @else
        <table class="data">
            <thead>
            <tr>
                <th>Title</th>
                <th>Item</th>
                <th>Qty</th>
                <th>Requested by</th>
                <th>Status</th>
                <th>Proof / notes</th>
            </tr>
            </thead>
            <tbody>
            @foreach ($requests as $row)
                <tr>
                    <td>{{ $row->title }}</td>
                    <td>{{ $row->item_name }}</td>
                    <td>{{ $row->quantity }}</td>
                    <td>{{ $row->requester?->full_name ?? '—' }}</td>
                    <td>
                        @if ($canEdit)
                            <form method="POST" action="{{ route('material-requests.update', $row) }}" style="display:flex;gap:0.35rem;align-items:center;margin:0">
                                @csrf
                                @method('PATCH')
                                <select name="status" style="margin:0;width:auto">
                                    @foreach ($statuses as $s)
                                        <option value="{{ $s }}" @selected($row->status === $s)>{{ $s }}</option>
                                    @endforeach
                                </select>
                                <input name="proof_note" placeholder="Proof note" value="{{ $row->proof_note }}" style="margin:0;min-width:8rem">
                                <button class="btn ghost" type="submit">Update</button>
                            </form>
                        @else
                            <span class="badge">{{ $row->status }}</span>
                        @endif
                    </td>
                    <td>{{ $row->proof_note ?: '—' }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif
</div>
@endsection
