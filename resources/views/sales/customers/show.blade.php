@extends('layouts.app')

@section('title', 'Customer: ' . $party->name)

@section('content')
<div class="page-head">
    <div>
        <h1>{{ $party->name }}</h1>
        <p class="muted" style="margin:.35rem 0 0">
            {{ $party->phone ?? 'No phone' }} · {{ $party->address ?? 'No address' }} · Status: {{ ucfirst($party->approval_status) }}
        </p>
    </div>
</div>

<div class="card">
    <h2 style="margin:0 0 1rem;font-size:1.1rem">Orders</h2>
    
    @if ($invoices->isEmpty())
        <p class="muted" style="margin:0;text-align:center;padding:2rem">No orders found for this customer.</p>
    @else
        <table class="data">
            <thead>
            <tr>
                <th>Order Number</th>
                <th>Status</th>
                <th>Date Issued</th>
                <th>Amount</th>
            </tr>
            </thead>
            <tbody>
            @foreach ($invoices as $invoice)
                <tr style="cursor: pointer;" onclick="window.location.href='{{ route('invoices.show', $invoice) }}'">
                    <td><strong>{{ $invoice->invoice_number }}</strong></td>
                    <td>{{ ucfirst($invoice->status) }}</td>
                    <td>{{ $invoice->issued_at ? $invoice->issued_at->format('M d, Y') : '—' }}</td>
                    <td>{{ number_format($invoice->amount, 2) }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif
</div>
@endsection
