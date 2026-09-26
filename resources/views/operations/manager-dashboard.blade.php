@extends('layouts.app')

@section('title', 'Manager materials')

@section('content')
<div class="page-head">
    <div>
        <h1>Materials &amp; procurement approvals</h1>
        <p class="muted" style="margin:0.35rem 0 0">Accepted orders waiting for stock verification or supplier approval.</p>
    </div>
</div>

<div class="card">
    @if ($intakes->isEmpty())
        <p class="muted" style="margin:0">No orders need manager action</p>
    @else
        <table class="data">
            <thead>
            <tr>
                <th>Invoice</th>
                <th>Pending materials</th>
                <th>Open procurement</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            @foreach ($intakes as $intake)
                <tr>
                    <td>{{ $intake->invoice_number }}</td>
                    <td>{{ $intake->materialLines->where('stock_status', \App\Support\OrderOperations::STOCK_PENDING)->count() }}</td>
                    <td>{{ $intake->procurementRequests->whereIn('status', [
                        \App\Support\OrderOperations::PROCUREMENT_PENDING_APPROVAL,
                        \App\Support\OrderOperations::PROCUREMENT_OPEN,
                        \App\Support\OrderOperations::PROCUREMENT_QUOTED,
                    ])->count() }}</td>
                    <td><a class="btn ghost" href="{{ route('operations.orders.show', $intake) }}">Open</a></td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif
</div>
@endsection
