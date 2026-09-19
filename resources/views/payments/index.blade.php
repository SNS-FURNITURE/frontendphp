@extends('layouts.app')

@section('title', 'Payments')

@section('content')
<div class="page-head">
    <div>
        <h1>Payments</h1>
        <p class="muted" style="margin:0.35rem 0 0">Record invoice payments. An invoice becomes paid when total payments cover its amount.</p>
    </div>
</div>

@can('recordPayment', App\Models\Invoice::class)
<div class="card" style="margin-bottom:1rem">
    <h3 style="margin-top:0">Record payment</h3>
    <form method="POST" action="{{ route('payments.store') }}">
        @csrf
        <div class="grid-2">
            <div>
                <label>Invoice</label>
                <select name="invoice_id" required>
                    <option value="">Select…</option>
                    @foreach ($invoices as $invoice)
                        <option value="{{ $invoice->id }}" @selected((string)request('invoice') === (string)$invoice->id)>
                            {{ $invoice->invoice_number }} · {{ number_format((float) $invoice->amount, 2) }} ETB ({{ $invoice->status }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label>Amount</label>
                <input type="number" step="0.01" name="amount" required>
            </div>
            <div>
                <label>Method</label>
                <input type="text" name="method" value="bank_transfer">
            </div>
        </div>
        <button class="btn" type="submit">Record payment</button>
    </form>
</div>
@endcan

<div class="card">
    @if ($payments->isEmpty())
        <p class="muted" style="margin:0">No payments recorded</p>
    @else
        <table class="data">
            <thead>
            <tr>
                <th>ID</th>
                <th>Invoice</th>
                <th>Amount</th>
                <th>Method</th>
                <th>Paid at</th>
            </tr>
            </thead>
            <tbody>
            @foreach ($payments as $payment)
                <tr>
                    <td>{{ $payment->id }}</td>
                    <td>{{ $payment->invoice?->invoice_number ?? $payment->invoice_id }}</td>
                    <td>{{ number_format((float) $payment->amount, 2) }} ETB</td>
                    <td>{{ $payment->method }}</td>
                    <td>{{ optional($payment->paid_at)->format('Y-m-d H:i') }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
        <div style="margin-top:1rem">{{ $payments->links() }}</div>
    @endif
</div>
@endsection
