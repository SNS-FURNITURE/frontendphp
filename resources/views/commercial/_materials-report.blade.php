@php
    $totals = $materialReport['totals'];
    $byItem = $materialReport['by_item'];
    $bySales = $materialReport['by_sales'];
    $recent = $materialReport['recent'];
@endphp

<div class="card orders-report-card">
    <div style="display:flex;justify-content:space-between;align-items:start;gap:1rem;flex-wrap:wrap;margin-bottom:1rem">
        <div>
            <h2 style="margin:0;font-size:1.1rem">Material usage</h2>
            <p class="muted" style="margin:0.35rem 0 0">Materials released to production — {{ $materialReport['period']['label'] }}</p>
        </div>
        <div class="orders-period-tabs" style="margin:0">
            @foreach (['weekly' => 'Weekly', 'monthly' => 'Monthly', 'yearly' => 'Yearly'] as $value => $label)
                <a
                    class="orders-period-tab {{ $materialReport['period_type'] === $value ? 'is-active' : '' }}"
                    href="{{ route('commercial.reports.index', ['period' => $value]) }}"
                >{{ $label }}</a>
            @endforeach
        </div>
    </div>

    <div class="grid-2" style="gap:1rem;margin-bottom:1.25rem">
        <div style="padding:0.85rem;border:1px solid var(--border);border-radius:10px">
            <div class="muted" style="font-size:0.75rem;text-transform:uppercase;letter-spacing:0.04em">Lines used</div>
            <div style="font-size:1.6rem;font-weight:700">{{ $totals['lines'] }}</div>
        </div>
        <div style="padding:0.85rem;border:1px solid var(--border);border-radius:10px">
            <div class="muted" style="font-size:0.75rem;text-transform:uppercase;letter-spacing:0.04em">Total quantity</div>
            <div style="font-size:1.6rem;font-weight:700">{{ number_format($totals['quantity'], 2) }}</div>
        </div>
        <div style="padding:0.85rem;border:1px solid var(--border);border-radius:10px">
            <div class="muted" style="font-size:0.75rem;text-transform:uppercase;letter-spacing:0.04em">Orders</div>
            <div style="font-size:1.6rem;font-weight:700">{{ $totals['orders'] }}</div>
        </div>
        <div style="padding:0.85rem;border:1px solid var(--border);border-radius:10px">
            <div class="muted" style="font-size:0.75rem;text-transform:uppercase;letter-spacing:0.04em">Distinct items</div>
            <div style="font-size:1.6rem;font-weight:700">{{ $totals['items'] }}</div>
        </div>
    </div>

    <div class="grid-2" style="gap:1rem;margin-bottom:1.25rem">
        <div>
            <h3 style="margin:0 0 0.75rem;font-size:1rem">By material</h3>
            @if ($byItem === [])
                <p class="muted" style="margin:0">No material usage in this period</p>
            @else
                <table class="data">
                    <thead><tr><th>Item</th><th>Qty</th><th>Lines</th></tr></thead>
                    <tbody>
                    @foreach ($byItem as $row)
                        <tr>
                            <td>{{ $row['item_name'] }}</td>
                            <td>{{ number_format($row['quantity'], 2) }} {{ $row['unit'] ?? 'pcs' }}</td>
                            <td>{{ $row['lines'] }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            @endif
        </div>
        <div>
            <h3 style="margin:0 0 0.75rem;font-size:1rem">Sales analytics (source rep)</h3>
            @if ($bySales === [])
                <p class="muted" style="margin:0">No sales-linked usage in this period</p>
            @else
                <table class="data">
                    <thead><tr><th>Sales</th><th>Qty</th><th>Orders</th></tr></thead>
                    <tbody>
                    @foreach ($bySales as $row)
                        <tr>
                            <td>{{ $row['full_name'] }}</td>
                            <td>{{ number_format($row['quantity'], 2) }}</td>
                            <td>{{ $row['orders'] }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>

    <h3 style="margin:0 0 0.75rem;font-size:1rem">Usage log</h3>
    @if ($recent->isEmpty())
        <p class="muted" style="margin:0">No usage log entries this period</p>
    @else
        <table class="data">
            <thead>
            <tr>
                <th>When</th>
                <th>Invoice</th>
                <th>Item</th>
                <th>Qty</th>
                <th>Sales source</th>
                <th>Released by</th>
            </tr>
            </thead>
            <tbody>
            @foreach ($recent as $log)
                <tr>
                    <td>{{ optional($log->used_at)->format('Y-m-d H:i') }}</td>
                    <td>{{ $log->invoice_number }}</td>
                    <td>{{ $log->item_name }}</td>
                    <td>{{ number_format((float) $log->quantity, 2) }} {{ $log->unit ?? 'pcs' }}</td>
                    <td>{{ $log->sourceUser?->full_name ?? '—' }}</td>
                    <td>{{ $log->releasedByUser?->full_name ?? '—' }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif
</div>
