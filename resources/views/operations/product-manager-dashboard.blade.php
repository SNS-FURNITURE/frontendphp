@extends('layouts.app')

@section('title', 'Product manager tasks')

@section('content')
<div class="page-head">
    <div>
        <h1>Product manager assignments</h1>
        <p class="muted" style="margin:0.35rem 0 0">Production work under OMF supervision. Schedule is set by OMS.</p>
    </div>
</div>

<div class="card">
    @if ($intakes->isEmpty())
        <p class="muted" style="margin:0">No product manager assignments</p>
    @else
        <table class="data">
            <thead>
            <tr>
                <th>Invoice</th>
                <th>Factory coloring</th>
                <th>Due</th>
                <th>Days left</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            @foreach ($intakes as $intake)
                @php
                    $factory = $intake->phase(\App\Support\OrderOperations::PHASE_FACTORY_COLORING);
                    $daysLeft = $factory?->due_at
                        ? (int) now()->startOfDay()->diffInDays($factory->due_at->copy()->startOfDay(), false)
                        : null;
                @endphp
                <tr>
                    <td>{{ $intake->invoice_number }}</td>
                    <td><span class="badge">{{ $factory?->status ?? '—' }}</span></td>
                    <td>{{ optional($factory?->due_at)->format('Y-m-d H:i') ?? '—' }}</td>
                    <td>{{ $daysLeft === null ? '—' : $daysLeft }}</td>
                    <td><a class="btn ghost" href="{{ route('operations.orders.show', $intake) }}">Open</a></td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif
</div>
@endsection
