@extends('layouts.app')

@section('title', 'Production')

@section('content')
<div class="page-head">
    <div>
        <h1>Production orders</h1>
        <p class="muted" style="margin:0.35rem 0 0">Plan and complete manufacturing runs. Completing consumes BOM stock and receipts the finished good.</p>
    </div>
    <div class="toolbar">
        <form method="GET" action="{{ route('production.index') }}" style="display:flex;gap:0.5rem;align-items:center;margin:0">
            <select name="status" onchange="this.form.submit()" style="margin:0;width:auto;min-width:9rem">
                <option value="">All statuses</option>
                @foreach ($statuses as $s)
                    <option value="{{ $s }}" @selected($status === $s)>{{ $s }}</option>
                @endforeach
            </select>
        </form>
        @if ($canCreate)
            <button class="btn" type="button" onclick="document.getElementById('create-po').hidden=false">New order</button>
        @endif
    </div>
</div>

@if ($canCreate)
<div class="card" id="create-po" style="margin-bottom:1.25rem" @if(!$openCreate && (!$errors->any() || !old('bom_id'))) hidden @endif>
    <h2 style="margin:0 0 1rem;font-size:1.1rem">Launch production order</h2>
    <form method="POST" action="{{ route('production.store') }}">
        @csrf
        <div class="grid-2">
            <div>
                <label for="bom_id">BOM</label>
                <select id="bom_id" name="bom_id" required>
                    <option value="">Select BOM</option>
                    @foreach ($boms as $bom)
                        <option value="{{ $bom->id }}" @selected(old('bom_id') == $bom->id)>{{ $bom->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="quantity">Quantity</label>
                <input id="quantity" name="quantity" type="number" step="0.01" min="0.01" value="{{ old('quantity', 1) }}" required>
            </div>
            <div>
                <label for="sales_order_id">Sales order (optional)</label>
                <select id="sales_order_id" name="sales_order_id">
                    <option value="">None</option>
                    @foreach ($salesOrders as $so)
                        <option value="{{ $so->id }}" @selected(old('sales_order_id') == $so->id)>#{{ $so->id }} — {{ $so->status }} — {{ number_format((float) $so->total_amount, 2) }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="toolbar">
            <button class="btn" type="submit">Launch</button>
            <button class="btn ghost" type="button" onclick="document.getElementById('create-po').hidden=true">Cancel</button>
        </div>
    </form>
</div>
@endif

<div class="card">
    @if ($orders->isEmpty())
        <p class="muted" style="margin:0">No production orders</p>
    @else
        <table class="data">
            <thead>
            <tr>
                <th>ID</th>
                <th>BOM</th>
                <th>Qty</th>
                <th>Sales order</th>
                <th>Status</th>
                <th>Created</th>
            </tr>
            </thead>
            <tbody>
            @foreach ($orders as $order)
                <tr>
                    <td>#{{ $order->id }}</td>
                    <td>{{ $order->bom?->name ?? '—' }}</td>
                    <td>{{ number_format((float) $order->quantity, 2) }}</td>
                    <td>{{ $order->sales_order_id ? '#'.$order->sales_order_id : '—' }}</td>
                    <td>
                        @if ($canCreate)
                            <form method="POST" action="{{ route('production.status', $order) }}" style="margin:0">
                                @csrf
                                @method('PATCH')
                                <select name="status" onchange="this.form.submit()" style="margin:0;width:auto">
                                    @foreach ($statuses as $s)
                                        <option value="{{ $s }}" @selected($order->status === $s)>{{ $s }}</option>
                                    @endforeach
                                </select>
                            </form>
                        @else
                            <span class="badge">{{ $order->status }}</span>
                        @endif
                    </td>
                    <td>{{ optional($order->created_at)->format('Y-m-d H:i') }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif
</div>
@endsection
