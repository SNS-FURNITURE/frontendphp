@extends('layouts.app')

@section('title', 'Low stock')

@section('content')
<div class="page-head">
    <div>
        <h1>Low stock</h1>
        <p class="muted" style="margin:0.35rem 0 0">Items at or below reorder level (including zero / missing levels).</p>
    </div>
</div>

<div class="card">
    @if (empty($rows))
        <p class="muted" style="margin:0">All items are above reorder level</p>
    @else
        <table class="data">
            <thead>
            <tr>
                <th>SKU</th>
                <th>Name</th>
                <th>Type</th>
                <th>Total stock</th>
                <th>Reorder</th>
            </tr>
            </thead>
            <tbody>
            @foreach ($rows as $row)
                <tr>
                    <td>{{ $row->sku }}</td>
                    <td>{{ $row->name }}</td>
                    <td><span class="badge">{{ $row->item_type }}</span></td>
                    <td>{{ $row->total_stock === null ? '—' : number_format((float) $row->total_stock, 2) }}</td>
                    <td>{{ number_format((float) $row->reorder_level, 2) }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif
</div>
@endsection
