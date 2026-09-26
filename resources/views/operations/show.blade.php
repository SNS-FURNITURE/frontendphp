@extends('layouts.app')

@section('title', 'Order '.$intake->invoice_number)

@section('content')
@php
    $user = auth()->user();
    $design = $intake->phase(\App\Support\OrderOperations::PHASE_DESIGN);
    $materialsPhase = $intake->phase(\App\Support\OrderOperations::PHASE_MATERIALS);
    $assembly = $intake->phase(\App\Support\OrderOperations::PHASE_ASSEMBLY);
    $deliveryPhase = $intake->phase(\App\Support\OrderOperations::PHASE_DELIVERY);
@endphp

<div class="page-head">
    <div>
        <h1>Order {{ $intake->invoice_number }}</h1>
        <p class="muted" style="margin:0.35rem 0 0">
            Status <span class="badge">{{ $intake->status }}</span>
            · Source {{ $intake->source_type }}
            @if ($intake->sourceUser) · {{ $intake->sourceUser->full_name }} @endif
            @if (($hidePrices ?? false) && $designDaysLeft !== null)
                · <strong>{{ $designDaysLeft }} day{{ $designDaysLeft === 1 ? '' : 's' }} left</strong> on design deadline
            @endif
        </p>
    </div>
    <div class="toolbar">
        @if ($user->canReviewOrderIntake())
            <a class="btn ghost" href="{{ route('operations.oms.check-invoice') }}">Check invoice</a>
            <a class="btn ghost" href="{{ route('operations.oms.schedule') }}">Schedule</a>
        @elseif ($user->isOmf() || $user->isAssembler())
            <a class="btn ghost" href="{{ route('operations.omf.dashboard') }}">OMF board</a>
        @elseif ($user->isDesigner())
            <a class="btn ghost" href="{{ route('operations.designer.dashboard') }}">Design board</a>
        @elseif ($user->isProductManager())
            <a class="btn ghost" href="{{ route('operations.product-manager.dashboard') }}">PM board</a>
        @elseif ($user->isProcurement())
            <a class="btn ghost" href="{{ route('operations.procurement.dashboard') }}">Procurement</a>
        @elseif ($user->hasRole('company_manager'))
            <a class="btn ghost" href="{{ route('operations.manager.dashboard') }}">Manager board</a>
        @endif
        <a class="btn ghost" href="{{ route('operations.orders.invoice-document', $intake) }}" target="_blank" rel="noopener">Invoice document</a>
    </div>
</div>

@if ($user->canReviewOrderIntake() && in_array($intake->status, [\App\Support\OrderOperations::INTAKE_PENDING, \App\Support\OrderOperations::INTAKE_UNDER_REVIEW, \App\Support\OrderOperations::INTAKE_RESUBMITTED], true))
<div class="card" style="margin-bottom:1.25rem">
    <h2 style="margin:0 0 1rem;font-size:1.1rem">Check invoice — side by side</h2>
    <p class="muted" style="margin:0 0 1rem">Sales supervisor issued document vs marketing/admin approved document.</p>
    <div class="grid-2" style="gap:1rem;margin-bottom:1rem">
        <div style="border:1px solid var(--border, #333);padding:0.75rem;border-radius:8px;max-height:28rem;overflow:auto">
            <h3 style="margin:0 0 0.5rem;font-size:0.95rem">Sales supervisor — issued / saved</h3>
            @if (! empty($issuedHtml))
                {!! $issuedHtml !!}
            @else
                <p class="muted" style="margin:0">No sales-supervisor issued/saved document was captured before approval. New approvals keep both copies automatically.</p>
            @endif
        </div>
        <div style="border:1px solid var(--border, #333);padding:0.75rem;border-radius:8px;max-height:28rem;overflow:auto">
            <h3 style="margin:0 0 0.5rem;font-size:0.95rem">Marketing / admin — approved</h3>
            @if (! empty($approvedHtml))
                {!! $approvedHtml !!}
            @else
                <p class="muted" style="margin:0">No approved snapshot on file</p>
            @endif
        </div>
    </div>
    @if ($intake->rejection_reason)
        <p class="muted" style="margin:0 0 1rem">Last return note: {{ $intake->rejection_reason }}</p>
    @endif
    <div class="toolbar" style="flex-wrap:wrap">
        @if (in_array($intake->status, [\App\Support\OrderOperations::INTAKE_PENDING, \App\Support\OrderOperations::INTAKE_RESUBMITTED], true))
            <form method="POST" action="{{ route('operations.orders.start-review', $intake) }}">@csrf
                <button class="btn ghost" type="submit">Start review</button>
            </form>
        @endif
        <form method="POST" action="{{ route('operations.orders.send-to-cm', $intake) }}" style="display:flex;gap:0.5rem;align-items:end;margin:0;flex-wrap:wrap">
            @csrf
            <div>
                <label for="cm_due_at">Company manager deadline *</label>
                <input id="cm_due_at" type="datetime-local" name="cm_due_at" required value="{{ old('cm_due_at') }}">
            </div>
            <div>
                <label for="cm_reminder_hours_before">Remind (hours before)</label>
                <input id="cm_reminder_hours_before" type="number" min="1" name="cm_reminder_hours_before" value="{{ old('cm_reminder_hours_before', 24) }}">
            </div>
            <button class="btn" type="submit">Send to company manager</button>
        </form>
        <form method="POST" action="{{ route('operations.orders.reject', $intake) }}" style="display:flex;gap:0.5rem;align-items:end;margin:0">
            @csrf
            <div>
                <label for="rejection_reason">Reject reason</label>
                <input id="rejection_reason" name="rejection_reason" required minlength="3" value="{{ old('rejection_reason') }}">
            </div>
            <button class="btn ghost" type="submit">Reject</button>
        </form>
    </div>
</div>
@endif

@if (($user->hasRole('company_manager') || $user->isAdmin()) && $intake->status === \App\Support\OrderOperations::INTAKE_AWAITING_CM)
<div class="card" style="margin-bottom:1.25rem">
    <h2 style="margin:0 0 1rem;font-size:1.1rem">Company manager approval</h2>
    <p class="muted" style="margin:0 0 1rem">
        OMS checked this invoice and set deadline
        <strong>{{ optional($intake->cm_due_at)->format('Y-m-d H:i') ?? '—' }}</strong>.
        Review the document below, then approve for production or return to OMS.
    </p>
    <div style="border:1px solid var(--border, #333);padding:0.75rem;border-radius:8px;max-height:36rem;overflow:auto;margin-bottom:1rem;background:var(--panel, #111)">
        @if (! empty($cmInvoiceHtml))
            {!! $cmInvoiceHtml !!}
        @elseif (! empty($approvedHtml))
            {!! $approvedHtml !!}
        @elseif (! empty($issuedHtml))
            {!! $issuedHtml !!}
        @else
            <p class="muted" style="margin:0">Invoice document not available.
                <a href="{{ route('operations.orders.invoice-document', $intake) }}" target="_blank" rel="noopener">Open invoice document</a>
            </p>
        @endif
    </div>
    <div class="toolbar" style="flex-wrap:wrap">
        <a class="btn ghost" href="{{ route('operations.orders.invoice-document', $intake) }}" target="_blank" rel="noopener">Open full invoice</a>
        <form method="POST" action="{{ route('operations.orders.cm-approve', $intake) }}">@csrf
            <button class="btn" type="submit">Approve for production</button>
        </form>
        <form method="POST" action="{{ route('operations.orders.cm-reject', $intake) }}" style="display:flex;gap:0.5rem;align-items:end;margin:0">
            @csrf
            <div>
                <label for="cm_rejection_reason">Return reason</label>
                <input id="cm_rejection_reason" name="rejection_reason" required minlength="3" value="{{ old('rejection_reason') }}">
            </div>
            <button class="btn ghost" type="submit">Return to OMS</button>
        </form>
    </div>
</div>
@endif

@if ($user->canReviewOrderIntake() && $intake->status === \App\Support\OrderOperations::INTAKE_AWAITING_CM)
<div class="card" style="margin-bottom:1.25rem">
    <h2 style="margin:0 0 0.5rem;font-size:1.1rem">Waiting on company manager</h2>
    <p class="muted" style="margin:0">
        Approval deadline you set:
        <strong>{{ optional($intake->cm_due_at)->format('Y-m-d H:i') ?? '—' }}</strong>.
        Designer assignment unlocks after approval.
    </p>
</div>
@endif

@if ($intake->status === \App\Support\OrderOperations::INTAKE_REJECTED && ((int) $intake->source_user_id === (int) $user->id || $user->isAdmin() || $user->isMarketingManager() || $user->isSalesSupervisor()))
<div class="card" style="margin-bottom:1.25rem">
    <h2 style="margin:0 0 1rem;font-size:1.1rem">Resubmit</h2>
    <p class="muted">Rejection: {{ $intake->rejection_reason }}</p>
    <form method="POST" action="{{ route('operations.orders.resubmit', $intake) }}">
        @csrf
        <label for="resubmit_comment">Comment</label>
        <textarea id="resubmit_comment" name="resubmit_comment" rows="2">{{ old('resubmit_comment') }}</textarea>
        <button class="btn" type="submit">Resubmit to OMS</button>
    </form>
</div>
@endif

@if ($intake->status === \App\Support\OrderOperations::INTAKE_ACCEPTED)
@php
    $factoryPhase = $intake->phase(\App\Support\OrderOperations::PHASE_FACTORY_COLORING);
    $canSupervise = $user->canSuperviseProductManager() || $user->isProductManager() || $user->isOms() || $user->isAdmin();
    $currentDesigner = $intake->assignments->firstWhere('role_key', \App\Support\OrderOperations::ROLE_DESIGNER);
    $currentPm = $intake->assignments->firstWhere('role_key', \App\Support\OrderOperations::ROLE_PRODUCT_MANAGER);
@endphp

@if ($user->canManageOrderSchedule())
<div class="card" style="margin-bottom:1.25rem" id="oms-schedule">
    <div style="display:flex;justify-content:space-between;align-items:baseline;gap:1rem;flex-wrap:wrap;margin-bottom:1rem">
        <div>
            <h2 style="margin:0;font-size:1.15rem">Schedule production</h2>
            <p class="muted" style="margin:0.35rem 0 0">Set people, phase names, and deadlines — then save once.</p>
        </div>
    </div>

    <form method="POST" action="{{ route('operations.orders.schedule.save', $intake) }}" id="oms-schedule-form">
        @csrf

        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(14rem,1fr));gap:1rem;margin-bottom:1.25rem;padding:1rem;border:1px solid var(--border,#333);border-radius:10px">
            <div>
                <label for="designer_user_id" style="font-weight:600">Designer</label>
                <select id="designer_user_id" name="designer_user_id" style="width:100%">
                    <option value="">Select designer</option>
                    @foreach ($designers as $designer)
                        <option value="{{ $designer->id }}" @selected((string) old('designer_user_id', $currentDesigner?->user_id) === (string) $designer->id)>{{ $designer->full_name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label style="font-weight:600">Product manager</label>
                @php $solePm = $soleProductManager ?? null; @endphp
                @if ($solePm)
                    <input type="hidden" name="product_manager_user_id" value="{{ $solePm->id }}">
                    <p style="margin:0.4rem 0 0"><strong>{{ $solePm->full_name }}</strong></p>
                    <p class="muted" style="margin:0.25rem 0 0;font-size:0.85rem">Single product manager — owns production and material release requests.</p>
                @else
                    <p class="muted" style="margin:0.4rem 0 0">No active product manager user found. Create one product_manager role user.</p>
                @endif
            </div>
        </div>

        <div style="overflow:auto">
            <table class="data" id="oms-phase-table" style="margin:0">
                <thead>
                <tr>
                    <th style="width:42%">Phase name</th>
                    <th style="width:28%">Deadline</th>
                    <th style="width:18%">Status</th>
                    <th style="width:12%"></th>
                </tr>
                </thead>
                <tbody id="oms-phase-body">
                @foreach ($intake->phases as $index => $phase)
                    <tr>
                        <td>
                            <input type="hidden" name="phases[{{ $index }}][id]" value="{{ $phase->id }}">
                            <input type="hidden" name="phases[{{ $index }}][reminder_hours_before]" value="{{ $phase->reminder_hours_before ?? 24 }}">
                            <input type="text" name="phases[{{ $index }}][label]" required maxlength="120" value="{{ old('phases.'.$index.'.label', $phase->displayLabel()) }}" style="width:100%">
                        </td>
                        <td>
                            <input type="datetime-local" name="phases[{{ $index }}][due_at]" required value="{{ old('phases.'.$index.'.due_at', optional($phase->due_at)->format('Y-m-d\TH:i')) }}" style="width:100%">
                        </td>
                        <td><span class="badge">{{ $phase->status }}</span></td>
                        <td class="muted" style="font-size:0.85rem">seeded</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

        <div class="toolbar" style="margin-top:1rem;flex-wrap:wrap;justify-content:space-between;gap:0.75rem">
            <button class="btn ghost" type="button" id="oms-add-phase">+ Add phase</button>
            <button class="btn" type="submit" style="min-width:10rem">Save schedule</button>
        </div>
    </form>
</div>

<template id="oms-new-phase-row">
    <tr class="oms-new-phase">
        <td>
            <input type="hidden" data-name="reminder_hours_before" value="24">
            <input type="text" data-name="label" maxlength="120" placeholder="New phase name" style="width:100%">
        </td>
        <td>
            <input type="datetime-local" data-name="due_at" style="width:100%">
        </td>
        <td><span class="badge">new</span></td>
        <td>
            <button class="btn ghost oms-remove-phase" type="button" style="padding:0.35rem 0.6rem">Remove</button>
        </td>
    </tr>
</template>

<script>
(function () {
    const body = document.getElementById('oms-phase-body');
    const addBtn = document.getElementById('oms-add-phase');
    const tpl = document.getElementById('oms-new-phase-row');
    const form = document.getElementById('oms-schedule-form');
    if (!body || !addBtn || !tpl || !form) return;

    function reindexNewPhases() {
        body.querySelectorAll('tr.oms-new-phase').forEach(function (row, i) {
            row.querySelectorAll('[data-name]').forEach(function (input) {
                input.name = 'new_phases[' + i + '][' + input.getAttribute('data-name') + ']';
            });
        });
    }

    addBtn.addEventListener('click', function () {
        body.appendChild(tpl.content.cloneNode(true));
        reindexNewPhases();
        const last = body.querySelector('tr.oms-new-phase:last-child input[data-name="label"]');
        if (last) last.focus();
    });

    body.addEventListener('click', function (e) {
        const btn = e.target.closest('.oms-remove-phase');
        if (!btn) return;
        const row = btn.closest('tr');
        if (row) row.remove();
        reindexNewPhases();
    });

    form.addEventListener('submit', reindexNewPhases);
})();
</script>
@else
<div class="card" style="margin-bottom:1.25rem">
    <h2 style="margin:0 0 1rem;font-size:1.1rem">Schedule (OMS-owned, read-only)</h2>
    <table class="data">
        <thead>
        <tr>
            <th>Phase</th>
            <th>Status</th>
            <th>Due</th>
            @if ($canSupervise)
                <th></th>
            @endif
        </tr>
        </thead>
        <tbody>
        @foreach ($intake->phases as $phase)
            <tr>
                <td>{{ $phase->displayLabel() }}</td>
                <td><span class="badge">{{ $phase->status }}</span></td>
                <td>{{ optional($phase->due_at)->format('Y-m-d H:i') ?? '—' }}</td>
                @if ($canSupervise && $phase->phase_key !== \App\Support\OrderOperations::PHASE_DESIGN && $phase->status !== \App\Support\OrderOperations::PHASE_COMPLETED)
                    <td>
                        <form method="POST" action="{{ route('operations.orders.phases.complete', [$intake, $phase]) }}" style="margin:0">@csrf
                            <button class="btn ghost" type="submit" style="padding:0.35rem 0.6rem">Mark complete</button>
                        </form>
                    </td>
                @elseif ($canSupervise)
                    <td class="muted">—</td>
                @endif
            </tr>
        @endforeach
        </tbody>
    </table>
    @if ($currentDesigner || $currentPm)
        <p class="muted" style="margin:1rem 0 0">
            @if ($currentDesigner) Designer: <strong>{{ $currentDesigner->user?->full_name }}</strong> @endif
            @if ($currentDesigner && $currentPm) · @endif
            @if ($currentPm) Product manager: <strong>{{ $currentPm->user?->full_name }}</strong> @endif
        </p>
    @endif
</div>
@endif

@if ($user->canAssignAssembler())
<div class="card" style="margin-bottom:1.25rem">
    <h2 style="margin:0 0 1rem;font-size:1.1rem">Assembler</h2>
    <form method="POST" action="{{ route('operations.orders.assign-assembler', $intake) }}" style="display:flex;gap:0.5rem;align-items:end;margin:0;flex-wrap:wrap">
        @csrf
        <div>
            <label>Assembler</label>
            <select name="user_id" required>
                <option value="">Select</option>
                @foreach ($assemblers as $assembler)
                    <option value="{{ $assembler->id }}">{{ $assembler->full_name }}</option>
                @endforeach
            </select>
        </div>
        <button class="btn" type="submit">Assign assembler</button>
    </form>
</div>
@endif

@if ($design)
<div class="card" style="margin-bottom:1.25rem">
    <h2 style="margin:0 0 1rem;font-size:1.1rem">Design checkpoints</h2>
    <table class="data">
        <thead><tr><th>Checkpoint</th><th>Status</th><th>Completed by</th><th></th></tr></thead>
        <tbody>
        @foreach ($design->checkpoints as $checkpoint)
            <tr>
                <td>{{ $checkpoint->label }}</td>
                <td><span class="badge">{{ $checkpoint->is_completed ? 'done' : 'open' }}</span></td>
                <td>{{ $checkpoint->completedByUser?->full_name ?? '—' }}</td>
                <td>
                    <form method="POST" action="{{ route('operations.orders.checkpoints.toggle', [$intake, $checkpoint]) }}" style="margin:0">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="completed" value="{{ $checkpoint->is_completed ? 0 : 1 }}">
                        <button class="btn ghost" type="submit" style="padding:0.35rem 0.6rem">
                            {{ $checkpoint->is_completed ? 'Reopen' : 'Complete' }}
                        </button>
                    </form>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endif

<div class="card" style="margin-bottom:1.25rem">
    <h2 style="margin:0 0 1rem;font-size:1.1rem">Materials</h2>
    @if ($materialsPhase)
        <p class="muted">Materials phase: <span class="badge">{{ $materialsPhase->status }}</span></p>
    @endif
    <table class="data" style="margin-bottom:1rem">
        <thead><tr><th>Item</th><th>Qty</th><th>Unit</th><th>Stock</th><th>Actions</th></tr></thead>
        <tbody>
        @forelse ($intake->materialLines as $line)
            <tr>
                <td>{{ $line->item_name }}</td>
                <td>{{ number_format((float) $line->quantity, 2) }}</td>
                <td>{{ $line->unit }}</td>
                <td><span class="badge">{{ $line->stock_status }}</span></td>
                <td>
                    <div class="toolbar" style="gap:0.35rem;margin:0">
                        @if ($line->stock_status === \App\Support\OrderOperations::STOCK_PENDING && ($user->canApproveOrderProcurement() || $user->hasRole('company_manager')))
                            <form method="POST" action="{{ route('operations.orders.materials.verify', [$intake, $line]) }}" style="margin:0">@csrf
                                <button class="btn ghost" type="submit" style="padding:0.35rem 0.6rem">In stock</button>
                            </form>
                            @if ($user->hasRole('company_manager'))
                                <form method="POST" action="{{ route('operations.orders.materials.procure', [$intake, $line]) }}" style="margin:0">@csrf
                                    <button class="btn ghost" type="submit" style="padding:0.35rem 0.6rem">Procure</button>
                                </form>
                            @endif
                        @endif
                    </div>
                </td>
            </tr>
        @empty
            <tr><td colspan="5" class="muted">No material lines</td></tr>
        @endforelse
        </tbody>
    </table>

    @if ($user->isDesigner() || $user->isOms() || $intake->assignments->where('role_key', \App\Support\OrderOperations::ROLE_DESIGNER)->where('user_id', $user->id)->isNotEmpty())
        <h3 style="margin:0 0 0.75rem;font-size:1rem">Submit materials</h3>
        <form method="POST" action="{{ route('operations.orders.materials.submit', $intake) }}">
            @csrf
            <div class="grid-2">
                <div>
                    <label>Item name</label>
                    <input name="lines[0][item_name]" required value="{{ old('lines.0.item_name') }}">
                </div>
                <div>
                    <label>Quantity</label>
                    <input name="lines[0][quantity]" type="number" step="0.01" min="0.01" required value="{{ old('lines.0.quantity', 1) }}">
                </div>
                <div>
                    <label>Unit</label>
                    <input name="lines[0][unit]" value="{{ old('lines.0.unit', 'pcs') }}">
                </div>
                <div>
                    <label>Notes</label>
                    <input name="lines[0][notes]" value="{{ old('lines.0.notes') }}">
                </div>
            </div>
            <button class="btn" type="submit">Submit materials</button>
        </form>
    @endif

    @if (($user->isProductManager() || $user->isAdmin()) && $intake->materialLines->isNotEmpty() && $intake->materialLines->contains(fn ($l) => in_array($l->stock_status, [\App\Support\OrderOperations::STOCK_AVAILABLE, \App\Support\OrderOperations::STOCK_RELEASE_REQUESTED], true)))
        <form method="POST" action="{{ route('operations.orders.materials.request-release', $intake) }}" style="margin-top:1rem">
            @csrf
            <label for="release_note">Request note (optional)</label>
            <input id="release_note" name="note" value="{{ old('note') }}" placeholder="Why inventory should release these materials">
            <button class="btn" type="submit" style="margin-top:0.5rem">Request release from inventory</button>
        </form>
        @if ($intake->materials_release_requested_at)
            <p class="muted" style="margin:0.75rem 0 0">
                Release requested {{ $intake->materials_release_requested_at->format('Y-m-d H:i') }}
                @if ($intake->materials_release_note) — {{ $intake->materials_release_note }} @endif
            </p>
        @endif
    @endif

    @if ($user->canApproveMaterialRelease() && $intake->materials_release_requested_at && $intake->materialLines->isNotEmpty())
        <form method="POST" action="{{ route('operations.orders.materials.release', $intake) }}" style="margin-top:1rem">@csrf
            <button class="btn" type="submit">Release materials from inventory</button>
        </form>
        <p class="muted" style="margin:0.5rem 0 0">Requested by product manager. Confirm stock leave before releasing.</p>
    @endif
</div>

<div class="card" style="margin-bottom:1.25rem">
    <h2 style="margin:0 0 1rem;font-size:1.1rem">Procurement</h2>
    @forelse ($intake->procurementRequests as $procurement)
        <div style="margin-bottom:1.25rem;padding-bottom:1rem;border-bottom:1px solid var(--border, #e2e8f0)">
            <p style="margin:0 0 0.5rem">
                <strong>{{ $procurement->materialLine?->item_name ?? 'Material' }}</strong>
                <span class="badge">{{ $procurement->status }}</span>
            </p>
            <table class="data" style="margin-bottom:0.75rem">
                <thead><tr><th>Supplier</th><th>Unit</th><th>Total</th><th>Selected</th><th></th></tr></thead>
                <tbody>
                @forelse ($procurement->quotes as $quote)
                    <tr>
                        <td>{{ $quote->supplier_name }}</td>
                        <td>{{ number_format((float) $quote->unit_price, 2) }}</td>
                        <td>{{ number_format((float) $quote->total_price, 2) }}</td>
                        <td>{{ $quote->is_selected ? 'yes' : '—' }}</td>
                        <td>
                            @if ($user->isProcurement() || $user->isAdmin())
                                <form method="POST" action="{{ route('operations.orders.quotes.recommend', [$intake, $procurement, $quote]) }}" style="display:flex;gap:0.35rem;align-items:end;margin:0">
                                    @csrf
                                    <input name="justification" placeholder="Justification" required style="min-width:10rem">
                                    <button class="btn ghost" type="submit" style="padding:0.35rem 0.6rem">Recommend</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="muted">No quotes yet</td></tr>
                @endforelse
                </tbody>
            </table>

            @if ($user->isProcurement() || $user->isAdmin())
                <form method="POST" action="{{ route('operations.orders.quotes.add', [$intake, $procurement]) }}" style="margin-bottom:0.75rem">
                    @csrf
                    <div class="grid-2">
                        <div>
                            <label>Supplier</label>
                            <input name="supplier_name" required>
                        </div>
                        <div>
                            <label>Unit price</label>
                            <input name="unit_price" type="number" step="0.01" min="0" required>
                        </div>
                        <div>
                            <label>Quality</label>
                            <input name="quality_grade">
                        </div>
                        <div>
                            <label>Lead time (days)</label>
                            <input name="lead_time_days" type="number" min="0">
                        </div>
                    </div>
                    <button class="btn ghost" type="submit">Add quote</button>
                </form>
            @endif

            @if ($procurement->status === \App\Support\OrderOperations::PROCUREMENT_PENDING_APPROVAL && $user->canApproveOrderProcurement())
                <div class="toolbar">
                    <form method="POST" action="{{ route('operations.orders.procurement.decide', [$intake, $procurement]) }}" style="margin:0">@csrf
                        <input type="hidden" name="approve" value="1">
                        <button class="btn" type="submit">Approve recommendation</button>
                    </form>
                    <form method="POST" action="{{ route('operations.orders.procurement.decide', [$intake, $procurement]) }}" style="display:flex;gap:0.4rem;align-items:end;margin:0">
                        @csrf
                        <input type="hidden" name="approve" value="0">
                        <input name="comment" placeholder="Reject comment">
                        <button class="btn ghost" type="submit">Reject</button>
                    </form>
                </div>
            @endif
        </div>
    @empty
        <p class="muted" style="margin:0">No procurement requests</p>
    @endforelse
</div>

<div class="card" style="margin-bottom:1.25rem">
    <h2 style="margin:0 0 1rem;font-size:1.1rem">Assembly &amp; delivery</h2>
    <p class="muted">
        Assembly <span class="badge">{{ $assembly?->status ?? '—' }}</span>
        · Delivery phase <span class="badge">{{ $deliveryPhase?->status ?? '—' }}</span>
    </p>
    @if ($user->canMonitorProductionDelivery())
        <div class="toolbar" style="margin-bottom:1rem">
            <form method="POST" action="{{ route('operations.orders.assembly.complete', $intake) }}">@csrf
                <button class="btn" type="submit">Complete assembly</button>
            </form>
            <form method="POST" action="{{ route('operations.orders.delivery.complete', $intake) }}">@csrf
                <button class="btn" type="submit">Complete delivery</button>
            </form>
        </div>
    @endif

    <h3 style="margin:0 0 0.75rem;font-size:1rem">Linked deliveries</h3>
    <table class="data" style="margin-bottom:1rem">
        <thead><tr><th>Product</th><th>Qty</th><th>Destination</th><th>Recipient</th><th>Status</th></tr></thead>
        <tbody>
        @forelse ($intake->deliveries as $delivery)
            <tr>
                <td>{{ $delivery->product_name }}</td>
                <td>{{ $delivery->quantity }}</td>
                <td>{{ $delivery->destination }}</td>
                <td>{{ $delivery->recipient_name }}</td>
                <td><span class="badge">{{ $delivery->status }}</span></td>
            </tr>
        @empty
            <tr><td colspan="5" class="muted">No deliveries scheduled</td></tr>
        @endforelse
        </tbody>
    </table>

    @if ($user->canMonitorProductionDelivery() || $user->canCreateDeliveries())
        <form method="POST" action="{{ route('operations.orders.delivery.schedule', $intake) }}">
            @csrf
            <div class="grid-2">
                <div>
                    <label>Product</label>
                    <input name="product_name" required value="{{ old('product_name', $intake->invoice_number) }}">
                </div>
                <div>
                    <label>Quantity</label>
                    <input name="quantity" type="number" min="1" required value="{{ old('quantity', 1) }}">
                </div>
                <div>
                    <label>Destination</label>
                    <select name="destination" required>
                        @foreach ($destinations as $dest)
                            <option value="{{ $dest }}" @selected(old('destination', 'customer') === $dest)>{{ $dest }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label>Recipient</label>
                    <input name="recipient_name" required value="{{ old('recipient_name') }}">
                </div>
            </div>
            <label>Notes</label>
            <textarea name="notes" rows="2">{{ old('notes') }}</textarea>
            <button class="btn" type="submit">Schedule delivery</button>
        </form>
    @endif
</div>
@endif

@if ($intake->status === \App\Support\OrderOperations::INTAKE_ACCEPTED)
<div class="card" style="margin-bottom:1.25rem">
    <h2 style="margin:0 0 0.5rem;font-size:1.1rem">Production updates</h2>
    <p class="muted" style="margin:0 0 1rem">Product manager posts progress here — OMS and OMF are notified.</p>
    <div style="margin-bottom:1rem;max-height:16rem;overflow:auto">
        @php
            $productionUpdates = $intake->messages->where('message_kind', \App\Support\OrderOperations::MESSAGE_PRODUCTION_UPDATE);
        @endphp
        @forelse ($productionUpdates as $message)
            <p style="margin:0 0 0.65rem">
                <strong>{{ $message->sender?->full_name ?? 'PM' }}</strong>
                <span class="muted">{{ optional($message->created_at)->format('Y-m-d H:i') }}</span><br>
                {{ $message->body }}
            </p>
        @empty
            <p class="muted">No production updates yet</p>
        @endforelse
    </div>
    @if ($user->canPostProductionUpdate() && ($user->isAdmin() || $intake->assignments->where('role_key', \App\Support\OrderOperations::ROLE_PRODUCT_MANAGER)->where('user_id', $user->id)->isNotEmpty()))
        <form method="POST" action="{{ route('operations.orders.production-updates', $intake) }}">
            @csrf
            <label for="production_update_body">Update for OMS &amp; OMF</label>
            <textarea id="production_update_body" name="body" rows="2" required>{{ old('body') }}</textarea>
            <button class="btn" type="submit">Post production update</button>
        </form>
    @endif
</div>
@endif

@if ($user->canMessageOpsPeer())
<div class="card" style="margin-bottom:1.25rem">
    <h2 style="margin:0 0 1rem;font-size:1.1rem">OMS ↔ OMF messages</h2>
    <div style="margin-bottom:1rem;max-height:16rem;overflow:auto">
        @forelse ($intake->messages->where('message_kind', \App\Support\OrderOperations::MESSAGE_OPS) as $message)
            <p style="margin:0 0 0.65rem">
                <strong>{{ $message->sender?->full_name ?? 'User' }}</strong>
                <span class="muted">{{ optional($message->created_at)->format('Y-m-d H:i') }}</span><br>
                {{ $message->body }}
            </p>
        @empty
            <p class="muted">No messages yet</p>
        @endforelse
    </div>
    <form method="POST" action="{{ route('operations.orders.messages.send', $intake) }}">
        @csrf
        <label for="body">Message</label>
        <textarea id="body" name="body" rows="2" required>{{ old('body') }}</textarea>
        <button class="btn" type="submit">Send</button>
    </form>
</div>
@endif

@if ($intake->reviews->isNotEmpty())
<div class="card">
    <h2 style="margin:0 0 1rem;font-size:1.1rem">Review history</h2>
    <table class="data">
        <thead><tr><th>Action</th><th>From</th><th>To</th><th>Actor</th><th>When</th></tr></thead>
        <tbody>
        @foreach ($intake->reviews->sortByDesc('id') as $review)
            <tr>
                <td>{{ $review->action }}</td>
                <td>{{ $review->from_status ?? '—' }}</td>
                <td>{{ $review->to_status ?? '—' }}</td>
                <td>{{ $review->actor?->full_name ?? $review->actor_role ?? '—' }}</td>
                <td>{{ optional($review->created_at)->format('Y-m-d H:i') }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endif
@endsection
