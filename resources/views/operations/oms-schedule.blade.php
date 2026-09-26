@extends('layouts.app')

@section('title', 'OMS Schedule production')

@section('content')
@include('operations._oms-tabs', ['tab' => 'schedule'])

<div class="page-head">
    <div>
        <h1>Schedule production</h1>
        <p class="muted" style="margin:0.35rem 0 0">Open an order, fill people + phase deadlines, then Save schedule once.</p>
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
                <th>Ready?</th>
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
                    $allDue = $intake->phases->isNotEmpty() && $intake->phases->every(fn ($p) => $p->due_at !== null);
                    $ready = $designer && $pm && $allDue;
                @endphp
                <tr>
                    <td>{{ $intake->invoice_number }}</td>
                    <td>
                        @if ($ready)
                            <span class="badge">scheduled</span>
                        @else
                            <span class="badge">needs schedule</span>
                        @endif
                    </td>
                    <td>{{ $designer?->user?->full_name ?? '—' }}</td>
                    <td>{{ $pm?->user?->full_name ?? '—' }}</td>
                    <td>
                        <a class="btn {{ $ready ? 'ghost' : '' }}" href="{{ route('operations.orders.show', $intake) }}#oms-schedule">
                            {{ $ready ? 'Edit' : 'Schedule' }}
                        </a>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif
</div>
@endsection
