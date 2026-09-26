@extends('layouts.app')

@section('title', 'Order procurement')

@section('content')
<div class="page-head">
    <div>
        <h1>Open procurement requests</h1>
        <p class="muted" style="margin:0.35rem 0 0">Source suppliers and recommend quotes for company manager approval.</p>
    </div>
</div>

<div class="card">
    @if ($requests->isEmpty())
        <p class="muted" style="margin:0">No open procurement requests</p>
    @else
        <table class="data">
            <thead>
            <tr>
                <th>Invoice</th>
                <th>Material</th>
                <th>Status</th>
                <th>Quotes</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            @foreach ($requests as $row)
                <tr>
                    <td>{{ $row->intake?->invoice_number ?? '—' }}</td>
                    <td>{{ $row->materialLine?->item_name ?? '—' }}</td>
                    <td><span class="badge">{{ $row->status }}</span></td>
                    <td>{{ $row->quotes->count() }}</td>
                    <td>
                        @if ($row->intake)
                            <a class="btn ghost" href="{{ route('operations.orders.show', $row->intake) }}">Open</a>
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif
</div>
@endsection
