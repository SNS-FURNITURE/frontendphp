@extends('layouts.app')

@section('title', 'Allocations')

@section('content')
<div class="page-head">
    <div>
        <h1>Allocations</h1>
        <p class="muted" style="margin:0.35rem 0 0">Approved, received, and routed funding — read only.</p>
    </div>
</div>

<div class="card">
    @if ($requests->isEmpty())
        <p class="muted" style="margin:0">No routed or approved funds yet.</p>
    @else
        <table class="data">
            <thead>
            <tr>
                <th>Request</th>
                <th>Amount</th>
                <th>Purpose</th>
                <th>Status</th>
                <th>Approved by</th>
                <th>Updated</th>
            </tr>
            </thead>
            <tbody>
            @foreach ($requests as $row)
                <tr>
                    <td>{{ $row->title }}</td>
                    <td>{{ number_format((float) $row->amount, 2) }} ETB</td>
                    <td>{{ $row->purpose ?: '—' }}</td>
                    <td><span class="badge">{{ $row->status }}</span></td>
                    <td>{{ $row->approver?->full_name ?? '—' }}</td>
                    <td>{{ optional($row->updated_at)->format('Y-m-d H:i') }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif
</div>
@endsection
