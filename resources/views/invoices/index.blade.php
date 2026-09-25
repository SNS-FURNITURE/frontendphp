@extends('layouts.app')

@section('title', 'Order log')

@section('content')
<style>
    .clickable-row { cursor: pointer; transition: background-color 0.15s ease; }
    .clickable-row:hover { background-color: #f1f5f9; }
    html[data-theme="dark"] .clickable-row:hover { background-color: #334155; }
</style>
@php
    $drafts = $drafts ?? collect();
    $isOrderReviewer = (bool) ($isOrderReviewer ?? false);
    $canViewPrices = auth()->user()?->canViewInvoicePrices() ?? false;
    $tab = request('tab');
    if ($isOrderReviewer) {
        $tab = in_array($tab, ['issued', 'approved'], true) ? $tab : 'issued';
    } else {
        $tab = $tab === 'drafts' ? 'drafts' : 'saved';
    }
@endphp

<div class="page-head">
    <div>
        <h1>Order log</h1>
        <p class="muted" style="margin:0.35rem 0 0">
            @if ($isOrderReviewer)
                Review issued orders, approve them, then find finished orders under Approved.
            @else
                Drafts autosave while you work. Click Save to move an order into Saved.
            @endif
        </p>
    </div>
    @can('create', App\Models\Invoice::class)
        <a class="btn" href="{{ route('invoices.create') }}">Create order</a>
    @endcan
</div>

<div class="card" x-data="{ tab: @js($tab) }" x-cloak>
    <div class="inv-tabs" role="tablist" aria-label="Invoice lists">
        @if ($isOrderReviewer)
            <button
                type="button"
                class="inv-tab"
                role="tab"
                :class="{ 'is-active': tab === 'issued' }"
                :aria-selected="tab === 'issued'"
                @click="tab = 'issued'; history.replaceState(null, '', '?tab=issued')"
            >
                Issued
            </button>
            <button
                type="button"
                class="inv-tab"
                role="tab"
                :class="{ 'is-active': tab === 'approved' }"
                :aria-selected="tab === 'approved'"
                @click="tab = 'approved'; history.replaceState(null, '', '?tab=approved')"
            >
                Approved
            </button>
        @else
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
        @endif
    </div>

    @if ($isOrderReviewer)
        <div x-show="tab === 'issued'" role="tabpanel">
            @if (($issuedInvoices ?? collect())->isEmpty())
                <div style="min-height:160px;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:0.75rem">
                    <p class="muted" style="margin:0">No issued orders waiting for approval.</p>
                </div>
            @else
                @include('invoices._index-table', [
                    'rows' => $issuedInvoices,
                    'canViewPrices' => $canViewPrices,
                    'openRoute' => 'show',
                    'showStatusControl' => true,
                ])
                <div style="margin-top:1rem">{{ $issuedInvoices->appends(['tab' => 'issued'])->links() }}</div>
            @endif
        </div>

        <div x-show="tab === 'approved'" role="tabpanel">
            @if (($approvedInvoices ?? collect())->isEmpty())
                <div style="min-height:160px;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:0.75rem">
                    <p class="muted" style="margin:0">No approved orders yet.</p>
                </div>
            @else
                @include('invoices._index-table', [
                    'rows' => $approvedInvoices,
                    'canViewPrices' => $canViewPrices,
                    'openRoute' => 'show',
                    'showStatusControl' => false,
                ])
                <div style="margin-top:1rem">{{ $approvedInvoices->appends(['tab' => 'approved'])->links() }}</div>
            @endif
        </div>
    @else
        <div x-show="tab === 'saved'" role="tabpanel">
            @if (($invoices ?? collect())->isEmpty())
                <div style="min-height:160px;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:0.75rem">
                    <p class="muted" style="margin:0">No saved orders yet</p>
                    @can('create', App\Models\Invoice::class)
                        <a class="btn" href="{{ route('invoices.create') }}">Create order</a>
                    @endcan
                </div>
            @else
                @include('invoices._index-table', [
                    'rows' => $invoices,
                    'canViewPrices' => $canViewPrices,
                    'openRoute' => 'show',
                    'showStatusControl' => true,
                ])
                <div style="margin-top:1rem">{{ $invoices->appends(['tab' => 'saved'])->links() }}</div>
            @endif
        </div>

        <div x-show="tab === 'drafts'" role="tabpanel">
            @if ($drafts->isEmpty())
                <div style="min-height:160px;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:0.75rem">
                    <p class="muted" style="margin:0">No drafts yet. Start an order — it autosaves here until you click Save.</p>
                </div>
            @else
                @include('invoices._index-table', [
                    'rows' => $drafts,
                    'canViewPrices' => $canViewPrices,
                    'openRoute' => 'edit',
                    'showUpdatedColumn' => true,
                    'showDeleteDraft' => true,
                ])
            @endif
        </div>
    @endif
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
