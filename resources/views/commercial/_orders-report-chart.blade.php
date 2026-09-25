@php
    $counts = $orderReport['counts'];
    $percentages = $orderReport['percentages'];
    $segments = [
        ['key' => 'draft', 'label' => 'Draft', 'class' => 'draft', 'color' => '#64748b'],
        ['key' => 'pending', 'label' => 'Pending review', 'class' => 'pending', 'color' => '#d97706'],
        ['key' => 'approved', 'label' => 'Approved', 'class' => 'approved', 'color' => '#16a34a'],
        ['key' => 'paid', 'label' => 'Paid', 'class' => 'paid', 'color' => '#2563eb'],
        ['key' => 'cancelled', 'label' => 'Cancelled', 'class' => 'cancelled', 'color' => '#dc2626'],
    ];
    $offset = 25;
    $segmentOffsets = [];
    foreach ($segments as $segment) {
        $segmentOffsets[$segment['key']] = $offset;
        $offset -= $percentages[$segment['key']];
    }
@endphp

@push('styles')
<style>
    .orders-report-card { margin-bottom: 1.25rem; }
    .orders-period-tabs {
        display: flex;
        flex-wrap: wrap;
        gap: 0.4rem;
        margin-bottom: 1rem;
    }
    .orders-period-tab {
        display: inline-flex;
        align-items: center;
        padding: 0.38rem 0.75rem;
        border-radius: 999px;
        border: 1px solid var(--border);
        background: transparent;
        color: var(--text);
        font-size: 0.84rem;
        font-weight: 500;
        text-decoration: none;
    }
    .orders-period-tab.is-active {
        background: var(--purple);
        border-color: var(--purple);
        color: #fff;
        font-weight: 600;
    }
    .orders-period-tab:not(.is-active):hover { background: var(--nav-hover); }
    .orders-report-layout {
        display: grid;
        grid-template-columns: minmax(160px, 220px) 1fr;
        gap: 1.25rem;
        align-items: center;
    }
    .orders-donut-wrap {
        position: relative;
        width: 100%;
        max-width: 210px;
        margin: 0 auto;
    }
    .orders-donut-center {
        position: absolute;
        inset: 0;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
        pointer-events: none;
    }
    .orders-donut-total {
        font-size: 1.65rem;
        font-weight: 700;
        line-height: 1;
        color: var(--text);
    }
    .orders-donut-label {
        margin-top: 0.25rem;
        font-size: 0.72rem;
        color: var(--muted);
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }
    .orders-donut-chart {
        width: 100%;
        transform: rotate(-90deg);
    }
    .orders-donut-track {
        fill: none;
        stroke: var(--border);
        stroke-width: 3.2;
    }
    .orders-donut-segment {
        fill: none;
        stroke-width: 3.2;
        stroke-linecap: butt;
    }
    .orders-report-legend {
        display: grid;
        gap: 0.75rem;
    }
    .orders-report-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        padding: 0.55rem 0.75rem;
        border: 1px solid var(--border);
        border-radius: 10px;
        background: var(--panel);
    }
    .orders-report-row-main {
        display: flex;
        align-items: center;
        gap: 0.55rem;
        min-width: 0;
    }
    .orders-report-dot {
        width: 0.72rem;
        height: 0.72rem;
        border-radius: 999px;
        flex-shrink: 0;
    }
    .orders-report-name {
        font-size: 0.92rem;
        font-weight: 600;
        color: var(--text);
    }
    .orders-report-meta {
        text-align: right;
        white-space: nowrap;
        font-size: 0.88rem;
        color: var(--text);
        font-weight: 600;
    }
    .orders-report-meta span {
        display: block;
        font-size: 0.72rem;
        color: var(--muted);
        font-weight: 500;
    }
    @media (max-width: 760px) {
        .orders-report-layout { grid-template-columns: 1fr; }
    }
</style>
@endpush

<div class="card orders-report-card">
    <div>
        <h2 style="margin:0 0 .35rem;font-size:1.05rem">Orders report</h2>
        <p class="muted" style="margin:0">{{ $orderReport['period']['label'] }}</p>
    </div>

    <div class="orders-period-tabs" style="margin-top:1rem">
        @foreach (['weekly' => 'Weekly', 'monthly' => 'Monthly', 'yearly' => 'Yearly'] as $value => $label)
            <a
                class="orders-period-tab {{ $periodType === $value ? 'is-active' : '' }}"
                href="{{ route('commercial.reports.index', ['period' => $value]) }}"
            >{{ $label }}</a>
        @endforeach
    </div>

    <div class="orders-report-layout">
        <div class="orders-donut-wrap">
            <svg class="orders-donut-chart" viewBox="0 0 36 36" aria-hidden="true">
                <path class="orders-donut-track" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"></path>
                @foreach ($segments as $segment)
                    @if ($counts[$segment['key']] > 0)
                        <path
                            class="orders-donut-segment {{ $segment['class'] }}"
                            style="stroke: {{ $segment['color'] }}"
                            stroke-dasharray="{{ $percentages[$segment['key']] }} {{ 100 - $percentages[$segment['key']] }}"
                            stroke-dashoffset="{{ $segmentOffsets[$segment['key']] }}"
                            d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                        ></path>
                    @endif
                @endforeach
            </svg>
            <div class="orders-donut-center">
                <div class="orders-donut-total">{{ $counts['total'] }}</div>
                <div class="orders-donut-label">Orders</div>
            </div>
        </div>

        <div class="orders-report-legend">
            @foreach ($segments as $segment)
                <div class="orders-report-row">
                    <div class="orders-report-row-main">
                        <span class="orders-report-dot" style="background: {{ $segment['color'] }}"></span>
                        <span class="orders-report-name">{{ $segment['label'] }}</span>
                    </div>
                    <div class="orders-report-meta">
                        {{ $counts[$segment['key']] }}
                        <span>{{ number_format($percentages[$segment['key']], 1) }}%</span>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
