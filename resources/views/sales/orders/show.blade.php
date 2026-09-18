@extends('layouts.app')

@section('title', 'Sales order #'.$order->id)

@section('content')
<div class="page-head">
    <div>
        <h1>Sales order #{{ $order->id }}</h1>
        <p class="muted" style="margin:0.35rem 0 0">
            {{ $order->customer?->name ?? 'Customer' }}
            · <span class="badge">{{ $order->status }}</span>
            · {{ number_format((float) $order->total_amount, 2) }} ETB
        </p>
    </div>
    <a class="btn ghost" href="{{ route('orders.requests') }}">Back to queue</a>
</div>

@if ($canApprove)
<div class="card" style="margin-bottom:1.25rem">
    <form method="POST" action="{{ route('sales.orders.status', $order) }}" class="toolbar" style="margin:0">
        @csrf
        @method('PATCH')
        <label for="status" style="margin:0">Status</label>
        <select id="status" name="status" style="margin:0;width:auto;min-width:10rem" onchange="this.form.submit()">
            @foreach ($statuses as $st)
                <option value="{{ $st }}" @selected($order->status === $st)>{{ $st }}</option>
            @endforeach
        </select>
    </form>
</div>
@endif

<div class="card" style="margin-bottom:1.25rem">
    <h2 style="margin:0 0 1rem;font-size:1.1rem">Lines</h2>
    @if ($order->lines->isEmpty())
        <p class="muted">No lines yet</p>
    @else
        <table class="data">
            <thead>
            <tr>
                <th>Item</th>
                <th>Qty</th>
                <th>Unit price</th>
                <th>Line total</th>
                <th>Specs</th>
            </tr>
            </thead>
            <tbody>
            @foreach ($order->lines as $line)
                <tr>
                    <td>{{ $line->item?->name ?? ('#'.$line->item_id) }}
                        @if($line->item?->sku)<div class="muted">{{ $line->item->sku }}</div>@endif
                    </td>
                    <td>{{ $line->quantity }}</td>
                    <td>{{ number_format((float) $line->unit_price, 2) }}</td>
                    <td>{{ number_format((float) $line->quantity * (float) $line->unit_price, 2) }}</td>
                    <td class="muted" style="font-size:0.8rem">
                        @if (is_array($line->custom_specs))
                            {{ collect($line->custom_specs)->map(fn ($v, $k) => $k.': '.$v)->implode(' · ') }}
                        @else
                            —
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif
</div>

@if ($canCreate && $items->isNotEmpty())
<div class="card">
    <h2 style="margin:0 0 1rem;font-size:1.1rem">Add line</h2>
    <form method="POST" action="{{ route('sales.orders.lines', $order) }}">
        @csrf
        <div class="grid-2">
            <div>
                <label for="item_id">Item</label>
                <select id="item_id" name="item_id" required>
                    <option value="">Select…</option>
                    @foreach ($items as $item)
                        <option value="{{ $item->id }}">{{ $item->sku }} — {{ $item->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="quantity">Quantity</label>
                <input id="quantity" name="quantity" type="number" step="0.01" min="0.01" value="1" required>
            </div>
            <div>
                <label for="unit_price">Unit price</label>
                <input id="unit_price" name="unit_price" type="number" step="0.01" min="0" required>
            </div>
            <div>
                <label for="wood_type">Wood type</label>
                <input id="wood_type" name="wood_type">
            </div>
            <div>
                <label for="finish">Finish</label>
                <input id="finish" name="finish">
            </div>
            <div>
                <label for="dimensions">Dimensions</label>
                <input id="dimensions" name="dimensions">
            </div>
        </div>
        <label for="notes">Notes</label>
        <textarea id="notes" name="notes" rows="2"></textarea>
        <button class="btn" type="submit">Add line</button>
    </form>
</div>
@endif
@endsection
