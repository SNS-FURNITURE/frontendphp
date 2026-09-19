@extends('layouts.app')

@section('title', 'Outbound')

@section('content')
<div class="page-head">
    <div>
        <h1>Outbound</h1>
        <p class="muted" style="margin:0.35rem 0 0">Dispatch counts and outbound records.</p>
    </div>
    <div class="toolbar">
        <a class="btn ghost" href="{{ route('production.deliveries') }}">Deliveries</a>
    </div>
</div>

@if ($pending->isNotEmpty())
<div class="card" style="margin-bottom:1.25rem">
    <h2 style="margin:0 0 0.75rem;font-size:1.05rem">Ready for dispatch</h2>
    <table class="data">
        <thead>
        <tr>
            <th>ID</th>
            <th>Product</th>
            <th>Qty</th>
            <th>Recipient</th>
            @if ($canDispatch)<th></th>@endif
        </tr>
        </thead>
        <tbody>
        @foreach ($pending as $d)
            <tr>
                <td>#{{ $d->id }}</td>
                <td>{{ $d->product_name }}</td>
                <td>{{ $d->quantity }}</td>
                <td>{{ $d->recipient_name }}</td>
                @if ($canDispatch)
                    <td>
                        <form method="POST" action="{{ route('production.deliveries.status', $d) }}" style="display:flex;gap:0.35rem;margin:0">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="status" value="dispatched">
                            <input name="quantity_dispatched" type="number" min="1" value="{{ $d->quantity }}" style="width:5rem;margin:0" required>
                            <button class="btn" type="submit">Count &amp; dispatch</button>
                        </form>
                    </td>
                @endif
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endif

<div class="card">
    @if ($records->isEmpty())
        <p class="muted" style="margin:0">No outbound records</p>
    @else
        <table class="data">
            <thead>
            <tr>
                <th>Reference</th>
                <th>Delivery</th>
                <th>Product</th>
                <th>Qty out</th>
                <th>Destination</th>
                <th>Recipient</th>
                <th>Counted by</th>
                <th>Counted at</th>
            </tr>
            </thead>
            <tbody>
            @foreach ($records as $r)
                <tr>
                    <td>{{ $r->reference ?: '—' }}</td>
                    <td>#{{ $r->delivery_id }}</td>
                    <td>{{ $r->product_name }}</td>
                    <td>{{ $r->quantity_out }}</td>
                    <td>{{ $r->destination }}</td>
                    <td>{{ $r->recipient_name }}</td>
                    <td>{{ $r->counter?->full_name ?? '—' }}</td>
                    <td>{{ optional($r->counted_at)->format('Y-m-d H:i') }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif
</div>
@endsection
