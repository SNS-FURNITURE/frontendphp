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
                <th>Reviewed by</th>
                <th>Sent</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            @foreach ($awaitingCm as $intake)
                <tr>
                    <td>{{ $intake->invoice_number }}</td>
                    <td>{{ $intake->source_type }}</td>
                    <td>{{ $intake->reviewer?->full_name ?? '—' }}</td>
                    <td>{{ optional($intake->company_manager_notified_at ?? $intake->reviewed_at)->format('Y-m-d H:i') ?? '—' }}</td>
                    <td class="toolbar" style="justify-content:flex-end;margin:0;flex-wrap:wrap">
                        <a class="btn ghost" href="{{ route('operations.orders.show', $intake) }}">Open</a>
                        <form method="POST" action="{{ route('operations.orders.cm-approve', $intake) }}" style="margin:0">@csrf
                            <button class="btn" type="submit">Approve</button>
                        </form>
                        <form method="POST" action="{{ route('operations.orders.cm-reject', $intake) }}" style="display:flex;gap:0.35rem;align-items:center;margin:0">
                            @csrf
                            <input name="rejection_reason" required minlength="3" placeholder="Return reason" style="margin:0;max-width:12rem">
                            <button class="btn ghost" type="submit">Return</button>
                        </form>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
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
