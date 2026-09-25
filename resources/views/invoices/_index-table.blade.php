@php
    $openRoute = $openRoute ?? 'show';
    $showStatusControl = (bool) ($showStatusControl ?? false);
    $showUpdatedColumn = (bool) ($showUpdatedColumn ?? false);
    $showDeleteDraft = (bool) ($showDeleteDraft ?? false);
@endphp
<div class="table-wrap">
    <table class="data">
        <thead>
        <tr>
            <th>Number</th>
            <th>Customer</th>
            @if ($canViewPrices)
                <th>Amount</th>
            @endif
            @if ($showUpdatedColumn)
                <th>Updated</th>
            @else
                <th>Status</th>
            @endif
            <th></th>
        </tr>
        </thead>
        <tbody>
        @foreach ($rows as $invoice)
            @php
                $snap = is_array($invoice->snapshot_json) ? $invoice->snapshot_json : [];
                $customer = $snap['customer']['name'] ?? '—';
                $href = $openRoute === 'edit'
                    ? route('invoices.edit', $invoice)
                    : route('invoices.show', $invoice);
            @endphp
            <tr class="clickable-row" onclick="if(!event.target.closest('a, button, select, form')) window.location.href='{{ $href }}'">
                <td>{{ $invoice->invoice_number }}</td>
                <td>{{ $customer }}</td>
                @if ($canViewPrices)
                    <td>{{ number_format((float) $invoice->amount, 2) }} ETB</td>
                @endif
                @if ($showUpdatedColumn)
                    <td>{{ optional($invoice->updated_at)->format('Y-m-d H:i') ?? '—' }}</td>
                @else
                    <td>
                        @if ($showStatusControl && auth()->user()->can('update', $invoice) && !in_array($invoice->status, ['approved', 'paid', 'cancelled'], true))
                            <form method="POST" action="{{ route('invoices.status', $invoice) }}" style="display:inline">
                                @csrf
                                @method('PATCH')
                                <select name="status" onchange="this.form.submit()" style="margin:0;width:auto;min-width:7rem">
                                    @foreach (['issued', 'overdue', 'cancelled'] as $st)
                                        <option value="{{ $st }}" @selected($invoice->status === $st)>{{ $st }}</option>
                                    @endforeach
                                </select>
                            </form>
                        @else
                            <span class="badge" @if($invoice->status === 'approved') style="background:#166534;color:#bbf7d0" @endif>{{ $invoice->status }}</span>
                        @endif
                    </td>
                @endif
                <td style="white-space:nowrap">
                    @if ($showDeleteDraft)
                        @can('delete', $invoice)
                            <form method="POST" action="{{ route('invoices.destroy', $invoice) }}" style="display:inline;margin:0"
                                  data-erp-confirm="Delete this draft order? This cannot be undone."
                                  data-erp-confirm-danger
                                  data-erp-confirm-ok="Delete">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn ghost" style="padding:0.15rem 0.5rem;font-size:0.8rem;color:#fca5a5;border-color:#7f1d1d">Delete</button>
                            </form>
                        @endcan
                    @elseif (auth()->user()->can('update', $invoice) && !in_array($invoice->status, ['approved', 'paid', 'cancelled'], true))
                        <a href="{{ route('invoices.edit', $invoice) }}">Edit</a>
                    @endif
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
