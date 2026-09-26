@extends('layouts.app')

@section('title', 'Product manager tasks')

@section('content')
<div class="page-head">
    <div>
        <h1>Product manager — production</h1>
        <p class="muted" style="margin:0.35rem 0 0">You own production for accepted orders. Post updates to OMS and OMF, and request raw material release from inventory.</p>
    </div>
</div>

<div class="card">
    @if ($intakes->isEmpty())
        <p class="muted" style="margin:0">No production orders assigned</p>
    @else
        <table class="data">
            <thead>
            <tr>
                <th>Invoice</th>
                <th>Factory coloring</th>
                <th>Materials</th>
                <th>Due</th>
                <th>Days left</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            @foreach ($intakes as $intake)
                @php
                    $factory = $intake->phase(\App\Support\OrderOperations::PHASE_FACTORY_COLORING);
                    $materials = $intake->phase(\App\Support\OrderOperations::PHASE_MATERIALS);
                    $daysLeft = $factory?->due_at
                        ? (int) now()->startOfDay()->diffInDays($factory->due_at->copy()->startOfDay(), false)
                        : null;
                    $releasePending = (bool) $intake->materials_release_requested_at
                        && $intake->materialLines->contains(fn ($l) => $l->stock_status === \App\Support\OrderOperations::STOCK_RELEASE_REQUESTED);
                @endphp
                <tr>
                    <td>{{ $intake->invoice_number }}</td>
                    <td><span class="badge">{{ $factory?->status ?? '—' }}</span></td>
                    <td>
                        <span class="badge">{{ $materials?->status ?? '—' }}</span>
                        @if ($releasePending)
                            <span class="badge">release requested</span>
                        @endif
                    </td>
                    <td>{{ optional($factory?->due_at)->format('Y-m-d H:i') ?? '—' }}</td>
                    <td>{{ $daysLeft === null ? '—' : $daysLeft }}</td>
                    <td><a class="btn ghost" href="{{ route('operations.orders.show', $intake) }}">Manage</a></td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif
</div>
@endsection
