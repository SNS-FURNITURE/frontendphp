@extends('layouts.app')

@section('title', 'Stock movements')

@section('content')
<div class="page-head">
    <div>
        <h1>Stock movements</h1>
        <p class="muted" style="margin:0.35rem 0 0">Inbound, outbound, and adjustments.</p>
    </div>
</div>

@if ($canCreate)
<div class="card" style="margin-bottom:1.25rem">
    <h2 style="margin:0 0 1rem;font-size:1.1rem">Record movement</h2>
    <form method="POST" action="{{ route('inventory.movements.store') }}">
        @csrf
        <div class="grid-2">
            <div>
                <label for="item_id">Item</label>
                <select id="item_id" name="item_id" required>
                    <option value="">Select…</option>
                    @foreach ($items as $item)
                        <option value="{{ $item->id }}" @selected((string) old('item_id') === (string) $item->id)>
                            {{ $item->sku }} — {{ $item->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="movement_type">Type</label>
                <select id="movement_type" name="movement_type" required>
                    @foreach (['in','out','adjustment'] as $type)
                        <option value="{{ $type }}" @selected(old('movement_type') === $type)>{{ $type }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="quantity">Quantity</label>
                <input id="quantity" name="quantity" type="number" step="0.01" min="0.01" value="{{ old('quantity') }}" required>
            </div>
            <div>
                <label for="warehouse">Warehouse</label>
                <input id="warehouse" name="warehouse" value="{{ old('warehouse', 'Main Warehouse') }}">
            </div>
        </div>
        <button class="btn" type="submit">Record movement</button>
    </form>
</div>
@endif

<div class="card">
    @if (empty($movements))
        <p class="muted" style="margin:0">No stock movements yet</p>
    @else
        <table class="data">
            <thead>
            <tr>
                <th>When</th>
                <th>Item</th>
                <th>Type</th>
                <th>Qty</th>
                <th>Reference</th>
            </tr>
            </thead>
            <tbody>
            @foreach ($movements as $row)
                <tr>
                    <td>{{ $row->created_at }}</td>
                    <td>{{ $row->item_sku }} — {{ $row->item_name }}</td>
                    <td><span class="badge">{{ $row->movement_type }}</span></td>
                    <td>{{ number_format((float) $row->quantity, 2) }}</td>
                    <td>
                        @if ($row->reference_type)
                            {{ $row->reference_type }}{{ $row->reference_id ? '#'.$row->reference_id : '' }}
                        @else
                            —
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
        @php $lastPage = max(1, (int) ceil($total / $perPage)); @endphp
        @if ($lastPage > 1)
            <div class="toolbar" style="margin-top:1rem">
                @if ($page > 1)
                    <a class="btn ghost" href="{{ route('inventory.movements', ['page' => $page - 1]) }}">Previous</a>
                @endif
                <span class="muted">Page {{ $page }} / {{ $lastPage }} ({{ $total }} total)</span>
                @if ($page < $lastPage)
                    <a class="btn ghost" href="{{ route('inventory.movements', ['page' => $page + 1]) }}">Next</a>
                @endif
            </div>
        @endif
    @endif
</div>
@endsection
