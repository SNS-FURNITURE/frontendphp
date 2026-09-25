@php
    $counts = $dealReport['counts'];
    $percentages = $dealReport['percentages'];
    $segments = [
        ['key' => 'in_progress', 'label' => 'In progress', 'class' => 'in-progress', 'color' => '#64748b'],
        ['key' => 'sales_approved', 'label' => 'Sales approved', 'class' => 'sales-approved', 'color' => '#d97706'],
        ['key' => 'won', 'label' => 'Won', 'class' => 'won', 'color' => '#16a34a'],
        ['key' => 'lost', 'label' => 'Lost', 'class' => 'lost', 'color' => '#dc2626'],
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
    .deals-report-card { margin-bottom: 1.25rem; }
    .deals-period-tabs {
        display: flex;
        flex-wrap: wrap;
        gap: 0.4rem;
        margin-bottom: 1rem;
    }
    .deals-period-tab {
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
    .deals-period-tab.is-active {
        background: var(--purple);
        border-color: var(--purple);
        color: #fff;
        font-weight: 600;
    }
    .deals-period-tab:not(.is-active):hover { background: var(--nav-hover); }
    .deals-report-layout {
        display: grid;
        grid-template-columns: minmax(160px, 220px) 1fr;
        gap: 1.25rem;
        align-items: center;
    }
    .deals-donut-wrap {
        position: relative;
        width: 100%;
        max-width: 210px;
        margin: 0 auto;
    }
    .deals-donut-center {
        position: absolute;
        inset: 0;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
        pointer-events: none;
    }
    .deals-donut-total {
        font-size: 1.65rem;
        font-weight: 700;
        line-height: 1;
        color: var(--text);
    }
    .deals-donut-label {
        margin-top: 0.25rem;
        font-size: 0.72rem;
        color: var(--muted);
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }
    .deals-donut-chart {
        width: 100%;
        transform: rotate(-90deg);
    }
    .deals-donut-track {
        fill: none;
        stroke: var(--border);
        stroke-width: 3.2;
    }
    .deals-donut-segment {
        fill: none;
        stroke-width: 3.2;
        stroke-linecap: butt;
    }
    .deals-report-legend {
        display: grid;
        gap: 0.75rem;
    }
    .deals-report-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        padding: 0.55rem 0.75rem;
        border: 1px solid var(--border);
        border-radius: 10px;
        background: var(--panel);
    }
    .deals-report-row-main {
        display: flex;
        align-items: center;
        gap: 0.55rem;
        min-width: 0;
    }
    .deals-report-dot {
        width: 0.72rem;
        height: 0.72rem;
        border-radius: 999px;
        flex-shrink: 0;
    }
    .deals-report-name {
        font-size: 0.92rem;
        font-weight: 600;
        color: var(--text);
    }
    .deals-report-meta {
        text-align: right;
        white-space: nowrap;
        font-size: 0.88rem;
        color: var(--text);
        font-weight: 600;
    }
    .deals-report-meta span {
        display: block;
        font-size: 0.72rem;
        color: var(--muted);
        font-weight: 500;
    }
    @media (max-width: 760px) {
        .deals-report-layout { grid-template-columns: 1fr; }
    }
</style>
@endpush

<div class="card deals-report-card">
    <div>
        <h2 style="margin:0 0 .35rem;font-size:1.05rem">Deals report</h2>
        <p class="muted" style="margin:0">{{ $dealReport['period']['label'] }}</p>
    </div>

    <div class="deals-period-tabs" style="margin-top:1rem">
        @foreach (['weekly' => 'Weekly', 'monthly' => 'Monthly', 'yearly' => 'Yearly'] as $value => $label)
            <a
                class="deals-period-tab {{ $periodType === $value ? 'is-active' : '' }}"
                href="{{ route('commercial.reports.index', ['period' => $value]) }}"
            >{{ $label }}</a>
        @endforeach
    </div>

    <div class="deals-report-layout">
        <div class="deals-donut-wrap">
            <svg class="deals-donut-chart" viewBox="0 0 36 36" aria-hidden="true">
                <path class="deals-donut-track" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"></path>
                @foreach ($segments as $segment)
                    @if ($counts[$segment['key']] > 0)
                        <path
                            class="deals-donut-segment {{ $segment['class'] }}"
                            style="stroke: {{ $segment['color'] }}"
                            stroke-dasharray="{{ $percentages[$segment['key']] }} {{ 100 - $percentages[$segment['key']] }}"
                            stroke-dashoffset="{{ $segmentOffsets[$segment['key']] }}"
                            d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                        ></path>
                    @endif
                @endforeach
            </svg>
            <div class="deals-donut-center">
                <div class="deals-donut-total">{{ $counts['total'] }}</div>
                <div class="deals-donut-label">Deals</div>
            </div>
        </div>

        <div class="deals-report-legend">
            @foreach ($segments as $segment)
                <div class="deals-report-row">
                    <div class="deals-report-row-main">
                        <span class="deals-report-dot" style="background: {{ $segment['color'] }}"></span>
                        <span class="deals-report-name">{{ $segment['label'] }}</span>
                    </div>
                    <div class="deals-report-meta">
                        {{ $counts[$segment['key']] }}
                        <span>{{ number_format($percentages[$segment['key']], 1) }}%</span>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
