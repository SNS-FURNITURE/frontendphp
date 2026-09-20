@extends('layouts.app')

@section('title', 'Invoice log')

@section('content')
<div class="page-head">
    <div>
        <h1>Invoice log</h1>
        <p class="muted" style="margin:0.35rem 0 0">
            Create, edit, and keep every invoice in this register. Saved documents stay here for later view, edit, download, or print.
        </p>
    </div>
    @can('create', App\Models\Invoice::class)
        <a class="btn" href="{{ route('invoices.create') }}">Create invoice</a>
    @endcan
</div>

<div class="card">
    @if ($invoices->isEmpty())
        <div style="min-height:220px;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:0.75rem">
            <span style="width:8px;height:8px;border-radius:50%;background:#f5c542;display:inline-block"></span>
            <p class="muted" style="margin:0">No invoices in the log yet</p>
            @can('create', App\Models\Invoice::class)
                <a class="btn" href="{{ route('invoices.create') }}">Create invoice</a>
            @endcan
        </div>
    @else
        <table class="data">
            <thead>
            <tr>
                <th>Number</th>
                <th>Customer</th>
                <th>Amount</th>
                <th>Status</th>
                <th>Due</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            @foreach ($invoices as $invoice)
                @php
                    $snap = is_array($invoice->snapshot_json) ? $invoice->snapshot_json : [];
                    $customer = $snap['customer']['name'] ?? '—';
                @endphp
                <tr>
                    <td>{{ $invoice->invoice_number }}</td>
                    <td>{{ $customer }}</td>
                    <td>{{ number_format((float) $invoice->amount, 2) }} ETB</td>
                    <td>
                        @can('update', $invoice)
                            @if (!in_array($invoice->status, ['paid','cancelled'], true))
                                <form method="POST" action="{{ route('invoices.status', $invoice) }}" style="display:inline">
                                    @csrf
                                    @method('PATCH')
                                    <select name="status" onchange="this.form.submit()" style="margin:0;width:auto;min-width:7rem">
                                        @foreach (['draft','issued','overdue','cancelled'] as $st)
                                            <option value="{{ $st }}" @selected($invoice->status === $st)>{{ $st }}</option>
                                        @endforeach
                                    </select>
                                </form>
                            @else
                                <span class="badge">{{ $invoice->status }}</span>
                            @endif
                        @else
                            <span class="badge">{{ $invoice->status }}</span>
                        @endcan
                    </td>
                    <td>{{ optional($invoice->due_date)->format('Y-m-d') ?? '—' }}</td>
                    <td style="white-space:nowrap">
                        <a href="{{ route('invoices.show', $invoice) }}">View</a>
                        @can('update', $invoice)
                            @if (!in_array($invoice->status, ['paid','cancelled'], true))
                                · <a href="{{ route('invoices.edit', $invoice) }}">Edit</a>
                            @endif
                        @endcan
                        · <a href="{{ route('invoices.document', [$invoice, 'format' => 'pdf']) }}">PDF</a>
                        · <a href="{{ route('invoices.document', [$invoice, 'format' => 'html']) }}" target="_blank">Print</a>
                        @can('recordPayment', App\Models\Invoice::class)
                            · <a href="{{ route('payments.index', ['invoice' => $invoice->id]) }}">Payment</a>
                        @endcan
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
        <div style="margin-top:1rem">{{ $invoices->links() }}</div>
    @endif
</div>
@endsection
