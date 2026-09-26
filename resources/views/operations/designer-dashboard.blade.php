@extends('layouts.app')

@section('title', 'Design tasks')

@section('content')
<div class="page-head">
    <div>
        <h1>Design assignments</h1>
        <p class="muted" style="margin:0.35rem 0 0">Orders assigned for 3D/2D design work.</p>
    </div>
</div>

<div class="card">
    @if ($intakes->isEmpty())
        <p class="muted" style="margin:0">No design assignments</p>
    @else
        <table class="data">
            <thead>
            <tr>
                <th>Invoice</th>
                <th>Design status</th>
                <th>Checkpoints</th>
                <th>Due</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            @foreach ($intakes as $intake)
                @php
                    $design = $intake->phase(\App\Support\OrderOperations::PHASE_DESIGN);
                    $done = $design?->checkpoints->where('is_completed', true)->count() ?? 0;
                    $total = $design?->checkpoints->count() ?? 0;
                @endphp
                <tr>
                    <td>{{ $intake->invoice_number }}</td>
                    <td><span class="badge">{{ $design?->status ?? '—' }}</span></td>
                    <td>{{ $done }}/{{ $total }}</td>
                    <td>{{ optional($design?->due_at)->format('Y-m-d H:i') ?? '—' }}</td>
                    <td><a class="btn ghost" href="{{ route('operations.orders.show', $intake) }}">Open</a></td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif
</div>
@endsection
