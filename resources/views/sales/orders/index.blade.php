@extends('layouts.app')

@section('title', 'Order requests')

@section('content')
<div class="page-head">
    <div>
        <h1>Order requests</h1>
        <p class="muted" style="margin:0.35rem 0 0">Sales order queue</p>
    </div>
    <div class="toolbar">
        <form method="GET" action="{{ route('orders.requests') }}" style="display:flex;gap:0.5rem;align-items:center;margin:0">
            <select name="status" onchange="this.form.submit()" style="margin:0;width:auto;min-width:8rem">
                <option value="">All statuses</option>
                @foreach (['draft','quoted','confirmed','in_production','delivered','cancelled'] as $st)
                    <option value="{{ $st }}" @selected($status === $st)>{{ $st }}</option>
                @endforeach
            </select>
        </form>
        @if ($canCreate)
            <button class="btn" type="button" onclick="document.getElementById('create-order').hidden=false">New order</button>
        @endif
    </div>
</div>

@if ($canCreate)
<div class="card" id="create-order" style="margin-bottom:1.25rem" @if(!$errors->any()) hidden @endif>
    <h2 style="margin:0 0 1rem;font-size:1.1rem">Create order request</h2>
    <form method="POST" action="{{ route('orders.requests.store') }}">
        @csrf
        <label for="customer_id">Customer</label>
        <select id="customer_id" name="customer_id" required>
            <option value="">Select approved customer…</option>
            @foreach ($customers as $c)
                <option value="{{ $c->id }}" @selected((string) old('customer_id') === (string) $c->id)>{{ $c->name }}</option>
            @endforeach
        </select>
        @if ($items->isNotEmpty())
            <div class="grid-2">
                <div>
                    <label for="item_id">Item (optional first line)</label>
                    <select id="item_id" name="item_id">
                        <option value="">—</option>
                        @foreach ($items as $item)
                            <option value="{{ $item->id }}" @selected((string) old('item_id') === (string) $item->id)>{{ $item->sku }} — {{ $item->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="quantity">Quantity</label>
                    <input id="quantity" name="quantity" type="number" step="0.01" min="0.01" value="{{ old('quantity', 1) }}">
                </div>
                <div>
                    <label for="unit_price">Unit price</label>
                    <input id="unit_price" name="unit_price" type="number" step="0.01" min="0" value="{{ old('unit_price') }}">
                </div>
                <div>
                    <label for="wood_type">Wood type</label>
                    <input id="wood_type" name="wood_type" value="{{ old('wood_type') }}">
                </div>
                <div>
                    <label for="finish">Finish</label>
                    <input id="finish" name="finish" value="{{ old('finish') }}">
                </div>
                <div>
                    <label for="dimensions">Dimensions</label>
                    <input id="dimensions" name="dimensions" value="{{ old('dimensions') }}">
                </div>
            </div>
            <label for="notes">Notes</label>
            <textarea id="notes" name="notes" rows="2">{{ old('notes') }}</textarea>
        @endif
        <div class="toolbar">
            <button class="btn" type="submit">Create</button>
            <button class="btn ghost" type="button" onclick="document.getElementById('create-order').hidden=true">Cancel</button>
        </div>
    </form>
</div>
@endif

<div class="card">
    @if ($orders->isEmpty())
        <p class="muted" style="margin:0;text-align:center;padding:2rem">No order requests</p>
    @else
        <table class="data">
            <thead>
            <tr>
                <th>#</th>
                <th>Customer</th>
                <th>Status</th>
                <th>Total</th>
                <th>Created</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            @foreach ($orders as $order)
                <tr>
                    <td>{{ $order->id }}</td>
                    <td>{{ $order->customer?->name ?? '—' }}</td>
                    <td><span class="badge">{{ $order->status }}</span></td>
                    <td>{{ number_format((float) $order->total_amount, 2) }} ETB</td>
                    <td>{{ optional($order->created_at)->format('Y-m-d H:i') }}</td>
                    <td><a href="{{ route('sales.orders.show', $order) }}">View</a></td>
                </tr>
            @endforeach
            </tbody>
        </table>
        <div style="margin-top:1rem">{{ $orders->links() }}</div>
    @endif
</div>
@endsection
