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
            <h3 style="margin:0 0 0.5rem;font-size:0.95rem">Sales supervisor — issued</h3>
            @if (! empty($issuedHtml))
                {!! $issuedHtml !!}
            @else
                <p class="muted" style="margin:0">No issued snapshot on file</p>
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
    <p class="muted">
        OMS deadline:
        <strong>{{ optional($intake->cm_due_at)->format('Y-m-d H:i') ?? '—' }}</strong>
        — approve before this time so design can start.
    </p>
    <div class="toolbar" style="flex-wrap:wrap">
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
@endphp
<div class="card" style="margin-bottom:1.25rem">
    <h2 style="margin:0 0 1rem;font-size:1.1rem">
        @if ($user->canManageOrderSchedule())
            Schedule production
        @else
            Schedule (OMS-owned, read-only)
        @endif
    </h2>
    <table class="data">
        <thead>
        <tr>
            <th>Phase</th>
            <th>Status</th>
            <th>Due</th>
            @if ($user->canManageOrderSchedule())
                <th>Rename / deadline</th>
            @elseif ($canSupervise)
                <th>Supervise</th>
            @endif
        </tr>
        </thead>
        <tbody>
        @foreach ($intake->phases as $phase)
            <tr>
                <td>{{ $phase->displayLabel() }}</td>
                <td><span class="badge">{{ $phase->status }}</span></td>
                <td>{{ optional($phase->due_at)->format('Y-m-d H:i') ?? '—' }}</td>
                @if ($user->canManageOrderSchedule())
                    <td>
                        <form method="POST" action="{{ route('operations.orders.phases.label', [$intake, $phase]) }}" style="display:flex;gap:0.35rem;align-items:end;margin:0 0 0.4rem;flex-wrap:wrap">
                            @csrf
                            @method('PATCH')
                            <input type="text" name="label" required value="{{ $phase->displayLabel() }}" style="min-width:8rem">
                            <button class="btn ghost" type="submit" style="padding:0.35rem 0.6rem">Rename</button>
                        </form>
                        <form method="POST" action="{{ route('operations.orders.deadlines.update', [$intake, $phase]) }}" style="display:flex;gap:0.4rem;align-items:end;margin:0;flex-wrap:wrap">
                            @csrf
                            @method('PATCH')
                            <input type="datetime-local" name="due_at" required value="{{ optional($phase->due_at)->format('Y-m-d\TH:i') }}">
                            <input type="number" name="reminder_hours_before" min="1" value="{{ $phase->reminder_hours_before ?? 24 }}" style="width:5rem" title="Reminder hours">
                            <button class="btn ghost" type="submit" style="padding:0.35rem 0.6rem">Save due</button>
                        </form>
                    </td>
                @elseif ($canSupervise && $phase->phase_key !== \App\Support\OrderOperations::PHASE_DESIGN && $phase->status !== \App\Support\OrderOperations::PHASE_COMPLETED)
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
    @if ($user->canManageOrderSchedule())
        <form method="POST" action="{{ route('operations.orders.phases.add', $intake) }}" style="display:flex;gap:0.5rem;align-items:end;margin-top:1rem;flex-wrap:wrap">
            @csrf
            <div>
                <label>New phase name</label>
                <input type="text" name="label" required maxlength="120" placeholder="Phase name" value="{{ old('label') }}">
            </div>
            <div>
                <label>Deadline</label>
                <input type="datetime-local" name="due_at" value="{{ old('due_at') }}">
            </div>
            <button class="btn" type="submit">+ Add phase</button>
        </form>
    @endif
</div>

<div class="card" style="margin-bottom:1.25rem">
    <h2 style="margin:0 0 1rem;font-size:1.1rem">Assignments</h2>
    <table class="data" style="margin-bottom:1rem">
        <thead><tr><th>Role</th><th>User</th><th>Assigned by</th><th>At</th></tr></thead>
        <tbody>
        @forelse ($intake->assignments as $assignment)
            <tr>
                <td>{{ $assignment->role_key }}</td>
                <td>{{ $assignment->user?->full_name ?? '—' }}</td>
                <td>{{ $assignment->assignedByUser?->full_name ?? '—' }}</td>
                <td>{{ optional($assignment->assigned_at)->format('Y-m-d H:i') }}</td>
            </tr>
        @empty
            <tr><td colspan="4" class="muted">No assignments yet</td></tr>
        @endforelse
        </tbody>
    </table>
    <div class="toolbar" style="flex-wrap:wrap;gap:1rem">
        @if ($user->canAssignDesigner())
            <form method="POST" action="{{ route('operations.orders.assign-designer', $intake) }}" style="display:flex;gap:0.5rem;align-items:end;margin:0;flex-wrap:wrap">
                @csrf
                <div>
                    <label>Designer</label>
                    <select name="user_id" required>
                        <option value="">Select</option>
                        @foreach ($designers as $designer)
                            <option value="{{ $designer->id }}">{{ $designer->full_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label>Designer deadline *</label>
                    <input type="datetime-local" name="due_at" required value="{{ old('due_at', optional($design?->due_at)->format('Y-m-d\TH:i')) }}">
                </div>
                <div>
                    <label>Remind (h before)</label>
                    <input type="number" min="1" name="reminder_hours_before" value="{{ old('reminder_hours_before', $design?->reminder_hours_before ?? 24) }}">
                </div>
                <button class="btn" type="submit">Assign designer</button>
            </form>
        @endif
        @if ($user->canAssignProductManager())
            <form method="POST" action="{{ route('operations.orders.assign-product-manager', $intake) }}" style="display:flex;gap:0.5rem;align-items:end;margin:0;flex-wrap:wrap">
                @csrf
                <div>
                    <label>Product manager</label>
                    <select name="user_id" required>
                        <option value="">Select</option>
                        @foreach ($productManagers as $pm)
                            <option value="{{ $pm->id }}">{{ $pm->full_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label>Factory coloring deadline *</label>
                    <input type="datetime-local" name="due_at" required value="{{ old('pm_due_at', optional($factoryPhase?->due_at)->format('Y-m-d\TH:i')) }}">
                </div>
                <div>
                    <label>Remind (h before)</label>
                    <input type="number" min="1" name="reminder_hours_before" value="{{ old('pm_reminder_hours_before', $factoryPhase?->reminder_hours_before ?? 24) }}">
                </div>
                <button class="btn" type="submit">Assign product manager</button>
            </form>
        @endif
        @if ($user->canAssignAssembler())
            <form method="POST" action="{{ route('operations.orders.assign-assembler', $intake) }}" style="display:flex;gap:0.5rem;align-items:end;margin:0">
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
        @endif
    </div>
</div>

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

    @if (($user->hasRole('company_manager') || $user->isOms()) && $intake->materialLines->isNotEmpty())
        <form method="POST" action="{{ route('operations.orders.materials.release', $intake) }}" style="margin-top:1rem">@csrf
            <button class="btn" type="submit">Release materials to production</button>
        </form>
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

@if ($user->canMessageOpsPeer())
<div class="card" style="margin-bottom:1.25rem">
    <h2 style="margin:0 0 1rem;font-size:1.1rem">OMS ↔ OMF messages</h2>
    <div style="margin-bottom:1rem;max-height:16rem;overflow:auto">
        @forelse ($intake->messages as $message)
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
