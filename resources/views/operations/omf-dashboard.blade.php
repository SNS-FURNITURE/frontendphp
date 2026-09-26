@extends('layouts.app')

@section('title', 'OMF supervision')

@section('content')
<div class="page-head">
    <div>
        <h1>OMF — supervise product manager</h1>
        <p class="muted" style="margin:0.35rem 0 0">OMS owns the schedule. Monitor PM progress and complete factory/production phases as supervisor.</p>
    </div>
</div>

<div class="card">
    @if ($intakes->isEmpty())
        <p class="muted" style="margin:0">No production orders to supervise</p>
    @else
        <table class="data">
            <thead>
            <tr>
                <th>Invoice</th>
                <th>Product manager</th>
                <th>Factory coloring</th>
                <th>Assembly</th>
                <th>Delivery</th>
                <th>Schedule</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            @foreach ($intakes as $intake)
                @php
                    $factory = $intake->phase(\App\Support\OrderOperations::PHASE_FACTORY_COLORING);
                    $assembly = $intake->phase(\App\Support\OrderOperations::PHASE_ASSEMBLY);
                    $delivery = $intake->phase(\App\Support\OrderOperations::PHASE_DELIVERY);
                    $pm = $intake->assignments->firstWhere('role_key', \App\Support\OrderOperations::ROLE_PRODUCT_MANAGER);
                    $schedule = $intake->phases->sortBy('sort_order')->map(fn ($p) => $p->displayLabel().' ('.($p->status ?? '—').')')->implode(' → ');
                @endphp
                <tr>
                    <td>{{ $intake->invoice_number }}</td>
                    <td>{{ $pm?->user?->full_name ?? '—' }}</td>
                    <td>
                        <span class="badge">{{ $factory?->status ?? '—' }}</span>
                        @if ($factory?->due_at)
                            <span class="muted" style="display:block;font-size:0.85rem">due {{ $factory->due_at->format('Y-m-d') }}</span>
                        @endif
                    </td>
                    <td><span class="badge">{{ $assembly?->status ?? '—' }}</span></td>
                    <td><span class="badge">{{ $delivery?->status ?? '—' }}</span></td>
                    <td class="muted" style="max-width:18rem;font-size:0.85rem">{{ $schedule ?: '—' }}</td>
                    <td><a class="btn ghost" href="{{ route('operations.orders.show', $intake) }}">Supervise</a></td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif
</div>
@endsection
