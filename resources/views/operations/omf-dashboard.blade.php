@extends('layouts.app')

@section('title', 'OMF production')

@section('content')
<div class="page-head">
    <div>
        <h1>OMF assembly &amp; delivery</h1>
        <p class="muted" style="margin:0.35rem 0 0">Monitor factory assembly and delivery progress for accepted orders.</p>
    </div>
</div>

<div class="card">
    @if ($intakes->isEmpty())
        <p class="muted" style="margin:0">No assembly or delivery orders</p>
    @else
        <table class="data">
            <thead>
            <tr>
                <th>Invoice</th>
                <th>Assembly</th>
                <th>Delivery</th>
                <th>Assemblers</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            @foreach ($intakes as $intake)
                @php
                    $assembly = $intake->phase(\App\Support\OrderOperations::PHASE_ASSEMBLY);
                    $delivery = $intake->phase(\App\Support\OrderOperations::PHASE_DELIVERY);
                    $assemblers = $intake->assignments->where('role_key', \App\Support\OrderOperations::ROLE_ASSEMBLER);
                @endphp
                <tr>
                    <td>{{ $intake->invoice_number }}</td>
                    <td><span class="badge">{{ $assembly?->status ?? '—' }}</span></td>
                    <td><span class="badge">{{ $delivery?->status ?? '—' }}</span></td>
                    <td>{{ $assemblers->map(fn ($a) => $a->user?->full_name)->filter()->implode(', ') ?: '—' }}</td>
                    <td><a class="btn ghost" href="{{ route('operations.orders.show', $intake) }}">Open</a></td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif
</div>
@endsection
