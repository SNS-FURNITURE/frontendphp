@extends('layouts.app')

@section('title', 'Manager board')

@section('content')
<div class="page-head">
    <div>
        <h1>Company manager board</h1>
        <p class="muted" style="margin:0.35rem 0 0">Approve OMS-reviewed orders, then verify materials and procurement.</p>
    </div>
</div>

<div class="card" style="margin-bottom:1.25rem">
    <h2 style="margin:0 0 1rem;font-size:1.1rem">Awaiting your approval</h2>
    @if ($awaitingCm->isEmpty())
        <p class="muted" style="margin:0">No orders waiting for company manager approval</p>
    @else
        <table class="data">
            <thead>
            <tr>
                <th>Invoice</th>
                <th>Source</th>
                <th>OMS deadline</th>
                <th>Reviewed by</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            @foreach ($awaitingCm as $intake)
                <tr>
                    <td>{{ $intake->invoice_number }}</td>
                    <td>{{ $intake->source_type }}</td>
                    <td>{{ optional($intake->cm_due_at)->format('Y-m-d H:i') ?? '—' }}</td>
                    <td>{{ $intake->reviewer?->full_name ?? '—' }}</td>
                    <td class="toolbar" style="justify-content:flex-end;margin:0;flex-wrap:wrap">
                        <a class="btn" href="{{ route('operations.orders.show', $intake) }}">Review invoice</a>
                        <a class="btn ghost" href="{{ route('operations.orders.invoice-document', $intake) }}" target="_blank" rel="noopener">Open document</a>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
        <p class="muted" style="margin:1rem 0 0">Open <strong>Review invoice</strong> to see the full document, then approve or return to OMS.</p>
    @endif
</div>

<div class="card">
    <h2 style="margin:0 0 1rem;font-size:1.1rem">Materials &amp; procurement</h2>
    @if ($intakes->isEmpty())
        <p class="muted" style="margin:0">No accepted orders need stock or supplier action</p>
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
