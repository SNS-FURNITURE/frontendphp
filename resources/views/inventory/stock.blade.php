@extends('layouts.app')

@section('title', 'Stock levels')

@section('content')
<div class="page-head">
    <div>
        <h1>Stock levels</h1>
        <p class="muted" style="margin:0.35rem 0 0">On-hand quantities by warehouse.</p>
    </div>
</div>

<div class="card">
    @if (empty($levels))
        <p class="muted" style="margin:0">No stock records found</p>
    @else
        <table class="data">
            <thead>
            <tr>
                <th>SKU</th>
                <th>Item</th>
                <th>Warehouse</th>
                <th>Qty on hand</th>
                <th>Reorder</th>
                <th>Updated</th>
            </tr>
            </thead>
            <tbody>
            @foreach ($levels as $row)
                <tr>
                    <td>{{ $row->sku }}</td>
                    <td>{{ $row->name }}</td>
                    <td>{{ $row->warehouse }}</td>
                    <td>{{ number_format((float) $row->quantity_on_hand, 2) }} {{ $row->unit_of_measure }}</td>
                    <td>{{ number_format((float) $row->reorder_level, 2) }}</td>
                    <td>{{ $row->updated_at }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif
</div>
@endsection
