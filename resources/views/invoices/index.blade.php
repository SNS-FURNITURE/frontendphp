@extends('layouts.app')

@section('title', 'Invoice log')

@section('content')
@php
    $drafts = $drafts ?? collect();
    $tab = request('tab') === 'drafts' ? 'drafts' : 'saved';
@endphp

<div class="page-head">
    <div>
        <h1>Invoice log</h1>
        <p class="muted" style="margin:0.35rem 0 0">
            Drafts autosave while you work. Click Save to move an invoice into Saved.
        </p>
    </div>
    @can('create', App\Models\Invoice::class)
        <a class="btn" href="{{ route('invoices.create') }}">Create invoice</a>
    @endcan
</div>

<div
    class="card"
    x-data="{ tab: @js($tab) }"
    x-cloak
>
    <div class="inv-tabs" role="tablist" aria-label="Invoice lists">
        <button
            type="button"
            class="inv-tab"
            role="tab"
            :class="{ 'is-active': tab === 'saved' }"
            :aria-selected="tab === 'saved'"
            @click="tab = 'saved'; history.replaceState(null, '', '?tab=saved')"
        >
            Saved
        </button>
        <button
            type="button"
            class="inv-tab"
            role="tab"
            :class="{ 'is-active': tab === 'drafts' }"
            :aria-selected="tab === 'drafts'"
            @click="tab = 'drafts'; history.replaceState(null, '', '?tab=drafts')"
        >
            Drafts
        </button>
    </div>

    <div x-show="tab === 'saved'" role="tabpanel">
        @if ($invoices->isEmpty())
            <div style="min-height:160px;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:0.75rem">
                <p class="muted" style="margin:0">No saved invoices yet</p>
                @can('create', App\Models\Invoice::class)
                    <a class="btn" href="{{ route('invoices.create') }}">Create invoice</a>
                @endcan
            </div>
        @else
            <div class="table-wrap">
                <table class="data">
                    <thead>
                    <tr>
                        <th>Number</th>
                        <th>Customer</th>
                        <th>Amount</th>
                        <th>Status</th>
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
                                @if (auth()->user()->can('update', $invoice) && !in_array($invoice->status, ['approved','paid','cancelled'], true))
                                    <form method="POST" action="{{ route('invoices.status', $invoice) }}" style="display:inline">
                                        @csrf
                                        @method('PATCH')
                                        <select name="status" onchange="this.form.submit()" style="margin:0;width:auto;min-width:7rem">
                                            @foreach (['issued','overdue','cancelled'] as $st)
                                                <option value="{{ $st }}" @selected($invoice->status === $st)>{{ $st }}</option>
                                            @endforeach
                                        </select>
                                    </form>
                                @else
                                    <span class="badge" @if($invoice->status === 'approved') style="background:#166534;color:#bbf7d0" @endif>{{ $invoice->status }}</span>
                                @endif
                            </td>
                            <td style="white-space:nowrap">
                                <a href="{{ route('invoices.show', $invoice) }}">View</a>
                                @can('update', $invoice)
                                    @if (!in_array($invoice->status, ['approved','paid','cancelled'], true))
                                        · <a href="{{ route('invoices.edit', $invoice) }}">Edit</a>
                                    @endif
                                @endcan
                                @can('approve', $invoice)
                                    · <form method="POST" action="{{ route('invoices.approve', $invoice) }}" style="display:inline;margin:0"
                                            onsubmit="return confirm('Approve this invoice? Your name will appear as Approved By.');">
                                        @csrf
                                        <button type="submit" class="btn" style="padding:0.2rem 0.55rem;font-size:0.8rem">Approve</button>
                                    </form>
                                @endcan
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <div style="margin-top:1rem">{{ $invoices->appends(['tab' => 'saved'])->links() }}</div>
        @endif
    </div>

    <div x-show="tab === 'drafts'" role="tabpanel">
        @if ($drafts->isEmpty())
            <div style="min-height:160px;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:0.75rem">
                <p class="muted" style="margin:0">No drafts yet. Start an invoice — it autosaves here until you click Save.</p>
            </div>
        @else
            <div class="table-wrap">
                <table class="data">
                    <thead>
                    <tr>
                        <th>Number</th>
                        <th>Customer</th>
                        <th>Amount</th>
                        <th>Updated</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($drafts as $invoice)
                        @php
                            $snap = is_array($invoice->snapshot_json) ? $invoice->snapshot_json : [];
                            $customer = $snap['customer']['name'] ?? '—';
                        @endphp
                        <tr>
                            <td>{{ $invoice->invoice_number }}</td>
                            <td>{{ $customer }}</td>
                            <td>{{ number_format((float) $invoice->amount, 2) }} ETB</td>
                            <td>{{ optional($invoice->updated_at)->format('Y-m-d H:i') ?? '—' }}</td>
                            <td style="white-space:nowrap">
                                @can('update', $invoice)
                                    <a href="{{ route('invoices.edit', $invoice) }}">Edit</a>
                                @endcan
                                @can('delete', $invoice)
                                    · <form method="POST" action="{{ route('invoices.destroy', $invoice) }}" style="display:inline;margin:0"
                                            onsubmit="return confirm('Delete this draft invoice? This cannot be undone.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn ghost" style="padding:0.15rem 0.5rem;font-size:0.8rem;color:#fca5a5;border-color:#7f1d1d">Delete</button>
                                    </form>
                                @endcan
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>

@push('styles')
<style>
.inv-tabs {
    display: flex;
    gap: 0.35rem;
    margin: 0 0 1.25rem;
    padding: 0.25rem;
    background: #0f0d1f;
    border: 1px solid var(--border);
    border-radius: 12px;
    width: fit-content;
    max-width: 100%;
}
.inv-tab {
    appearance: none;
    border: 0;
    background: transparent;
    color: var(--muted);
    font-weight: 700;
    font-size: 0.9rem;
    padding: 0.55rem 1rem;
    border-radius: 10px;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
}
.inv-tab:hover { color: var(--text); }
.inv-tab.is-active {
    background: var(--panel);
    color: var(--text);
    box-shadow: 0 0 0 1px var(--border);
}
</style>
@endpush
@endsection
