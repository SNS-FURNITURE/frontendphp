@extends('layouts.app')

@section('title', 'OMS Schedule production')

@section('content')
@include('operations._oms-tabs', ['tab' => 'schedule'])

<div class="page-head">
    <div>
        <h1>Schedule production</h1>
        <p class="muted" style="margin:0.35rem 0 0">After CM approval: set all phase deadlines, add phases with +, assign designer and product manager.</p>
    </div>
</div>

<div class="card">
    @if ($intakes->isEmpty())
        <p class="muted" style="margin:0">No accepted orders to schedule</p>
    @else
        <table class="data">
            <thead>
            <tr>
                <th>Invoice</th>
                <th>Phases</th>
                <th>Designer</th>
                <th>Product manager</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            @foreach ($intakes as $intake)
                @php
                    $designer = $intake->assignments->firstWhere('role_key', \App\Support\OrderOperations::ROLE_DESIGNER);
                    $pm = $intake->assignments->firstWhere('role_key', \App\Support\OrderOperations::ROLE_PRODUCT_MANAGER);
                @endphp
                <tr>
                    <td>{{ $intake->invoice_number }}</td>
                    <td>{{ $intake->phases->count() }}</td>
                    <td>{{ $designer?->user?->full_name ?? '—' }}</td>
                    <td>{{ $pm?->user?->full_name ?? '—' }}</td>
                    <td><a class="btn ghost" href="{{ route('operations.orders.show', $intake) }}">Schedule</a></td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif
</div>
@endsection
