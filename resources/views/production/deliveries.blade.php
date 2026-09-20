@extends('layouts.app')

@section('title', 'Deliveries')

@section('content')
<div class="page-head">
    <div>
        <h1>Deliveries</h1>
        <p class="muted" style="margin:0.35rem 0 0">Plan shipments. Dispatch creates an outbound record OUT-…</p>
    </div>
    <div class="toolbar">
        <form method="GET" action="{{ route('production.deliveries') }}" style="display:flex;gap:0.5rem;align-items:center;margin:0">
            <select name="status" onchange="this.form.submit()" style="margin:0;width:auto;min-width:10rem">
                <option value="">All statuses</option>
                @foreach ($statuses as $s)
                    <option value="{{ $s }}" @selected($status === $s)>{{ $s }}</option>
                @endforeach
            </select>
        </form>
        @if ($canCreate)
            <button class="btn" type="button" onclick="document.getElementById('create-delivery').hidden=false">New delivery</button>
        @endif
    </div>
</div>

@if ($canCreate)
<div class="card" id="create-delivery" style="margin-bottom:1.25rem" @if(!$errors->any() || !old('product_name')) hidden @endif>
    <h2 style="margin:0 0 1rem;font-size:1.1rem">Plan delivery</h2>
    <form method="POST" action="{{ route('production.deliveries.store') }}">
        @csrf
        <div class="grid-2">
            <div>
                <label for="product_name">Product</label>
                <input id="product_name" name="product_name" value="{{ old('product_name') }}" required minlength="2">
            </div>
            <div>
                <label for="quantity">Quantity</label>
                <input id="quantity" name="quantity" type="number" min="1" value="{{ old('quantity', 1) }}" required>
            </div>
            <div>
                <label for="destination">Destination</label>
                <select id="destination" name="destination" required>
                    @foreach ($destinations as $d)
                        <option value="{{ $d }}" @selected(old('destination', 'customer') === $d)>{{ $d }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="recipient_name">Recipient</label>
                <input id="recipient_name" name="recipient_name" value="{{ old('recipient_name') }}" required minlength="2">
            </div>
        </div>
        <label for="notes">Notes</label>
        <textarea id="notes" name="notes" rows="2">{{ old('notes') }}</textarea>
        <div class="toolbar">
            <button class="btn" type="submit">Plan delivery</button>
            <button class="btn ghost" type="button" onclick="document.getElementById('create-delivery').hidden=true">Cancel</button>
        </div>
    </form>
</div>
@endif

<div class="card">
    @if ($deliveries->isEmpty())
        <p class="muted" style="margin:0">No deliveries yet</p>
    @else
        <table class="data">
            <thead>
            <tr>
                <th>ID</th>
                <th>Product</th>
                <th>Qty</th>
                <th>Destination</th>
                <th>Recipient</th>
                <th>Status</th>
                <th>Dispatch qty</th>
            </tr>
            </thead>
            <tbody>
            @foreach ($deliveries as $row)
                <tr>
                    <td>#{{ $row->id }}</td>
                    <td>{{ $row->product_name }}</td>
                    <td>{{ $row->quantity }}</td>
                    <td>{{ $row->destination }}</td>
                    <td>{{ $row->recipient_name }}</td>
                    <td>
                        <form method="POST" action="{{ route('production.deliveries.status', $row) }}" style="display:flex;flex-wrap:wrap;gap:0.35rem;align-items:center;margin:0">
                            @csrf
                            @method('PATCH')
                            <select name="status" style="margin:0;width:auto">
                                @foreach ($statuses as $s)
                                    @if ($s === 'dispatched' && ! $canDispatch && $row->status !== 'dispatched')
                                        @continue
                                    @endif
                                    <option value="{{ $s }}" @selected($row->status === $s)>{{ $s }}</option>
                                @endforeach
                            </select>
                            <input name="quantity_dispatched" type="number" min="1" placeholder="Qty out" value="{{ $row->quantity_dispatched }}" style="width:5rem;margin:0">
                            <button class="btn ghost" type="submit">Update</button>
                        </form>
                    </td>
                    <td>{{ $row->quantity_dispatched ?? '—' }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif
</div>
@endsection
