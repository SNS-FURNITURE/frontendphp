@php
    $counts = $report['counts'];
    $percentages = $report['percentages'];
    $approvedPct = $percentages['approved'];
    $pendingPct = $percentages['pending'];
    $rejectedPct = $percentages['rejected'];
    $approvedOffset = 25;
    $pendingOffset = $approvedOffset - $approvedPct;
    $rejectedOffset = $pendingOffset - $pendingPct;
@endphp

@push('styles')
<style>
    .contact-report-card { margin-bottom: 1.25rem; }
    .contact-period-tabs {
        display: flex;
        flex-wrap: wrap;
        gap: 0.4rem;
        margin-bottom: 1rem;
    }
    .contact-period-tab {
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
    .contact-period-tab.is-active {
        background: var(--purple);
        border-color: var(--purple);
        color: #fff;
        font-weight: 600;
    }
    .contact-period-tab:not(.is-active):hover { background: var(--nav-hover); }
    .contact-report-layout {
        display: grid;
        grid-template-columns: minmax(160px, 220px) 1fr;
        gap: 1.25rem;
        align-items: center;
    }
    .contact-donut-wrap {
        position: relative;
        width: 100%;
        max-width: 210px;
        margin: 0 auto;
    }
    .contact-donut-center {
        position: absolute;
        inset: 0;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
        pointer-events: none;
    }
    .contact-donut-total {
        font-size: 1.65rem;
        font-weight: 700;
        line-height: 1;
        color: var(--text);
    }
    .contact-donut-label {
        margin-top: 0.25rem;
        font-size: 0.72rem;
        color: var(--muted);
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }
    .contact-donut-chart {
        width: 100%;
        transform: rotate(-90deg);
    }
    .contact-donut-track {
        fill: none;
        stroke: var(--border);
        stroke-width: 3.2;
    }
    .contact-donut-segment {
        fill: none;
        stroke-width: 3.2;
        stroke-linecap: butt;
    }
    .contact-donut-segment.approved { stroke: #16a34a; }
    .contact-donut-segment.pending { stroke: #d97706; }
    .contact-donut-segment.rejected { stroke: #dc2626; }
    .contact-report-legend {
        display: grid;
        gap: 0.75rem;
    }
    .contact-report-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        padding: 0.55rem 0.75rem;
        border: 1px solid var(--border);
        border-radius: 10px;
        background: var(--panel);
    }
    .contact-report-row-main {
        display: flex;
        align-items: center;
        gap: 0.55rem;
        min-width: 0;
    }
    .contact-report-dot {
        width: 0.72rem;
        height: 0.72rem;
        border-radius: 999px;
        flex-shrink: 0;
    }
    .contact-report-dot.approved { background: #16a34a; }
    .contact-report-dot.pending { background: #d97706; }
    .contact-report-dot.rejected { background: #dc2626; }
    .contact-report-name {
        font-size: 0.92rem;
        font-weight: 600;
        color: var(--text);
    }
    .contact-report-meta {
        text-align: right;
        white-space: nowrap;
        font-size: 0.88rem;
        color: var(--text);
        font-weight: 600;
    }
    .contact-report-meta span {
        display: block;
        font-size: 0.72rem;
        color: var(--muted);
        font-weight: 500;
    }
    @media (max-width: 760px) {
        .contact-report-layout { grid-template-columns: 1fr; }
    }
</style>
@endpush

<div class="card contact-report-card">
    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:1rem;flex-wrap:wrap;margin-bottom:.25rem">
        <div>
            <h2 style="margin:0 0 .35rem;font-size:1.05rem">Contact approval report</h2>
            <p class="muted" style="margin:0">{{ $report['period']['label'] }}</p>
        </div>
    </div>

    <div class="contact-period-tabs">
        @foreach (['weekly' => 'Weekly', 'monthly' => 'Monthly', 'yearly' => 'Yearly'] as $value => $label)
            <a
                class="contact-period-tab {{ $periodType === $value ? 'is-active' : '' }}"
                href="{{ $reportRoute.'?period='.$value }}"
            >{{ $label }}</a>
        @endforeach
    </div>

    <div class="contact-report-layout">
        <div class="contact-donut-wrap">
            <svg class="contact-donut-chart" viewBox="0 0 36 36" aria-hidden="true">
                <path class="contact-donut-track" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"></path>
                @if ($counts['approved'] > 0)
                    <path
                        class="contact-donut-segment approved"
                        stroke-dasharray="{{ $approvedPct }} {{ 100 - $approvedPct }}"
                        stroke-dashoffset="{{ $approvedOffset }}"
                        d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                    ></path>
                @endif
                @if ($counts['pending'] > 0)
                    <path
                        class="contact-donut-segment pending"
                        stroke-dasharray="{{ $pendingPct }} {{ 100 - $pendingPct }}"
                        stroke-dashoffset="{{ $pendingOffset }}"
                        d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                    ></path>
                @endif
                @if ($counts['rejected'] > 0)
                    <path
                        class="contact-donut-segment rejected"
                        stroke-dasharray="{{ $rejectedPct }} {{ 100 - $rejectedPct }}"
                        stroke-dashoffset="{{ $rejectedOffset }}"
                        d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                    ></path>
                @endif
            </svg>
            <div class="contact-donut-center">
                <div class="contact-donut-total">{{ $counts['total'] }}</div>
                <div class="contact-donut-label">Submitted</div>
            </div>
        </div>

        <div class="contact-report-legend">
            <div class="contact-report-row">
                <div class="contact-report-row-main">
                    <span class="contact-report-dot approved"></span>
                    <span class="contact-report-name">Approved</span>
                </div>
                <div class="contact-report-meta">
                    {{ $counts['approved'] }}
                    <span>{{ number_format($approvedPct, 1) }}%</span>
                </div>
            </div>
            <div class="contact-report-row">
                <div class="contact-report-row-main">
                    <span class="contact-report-dot pending"></span>
                    <span class="contact-report-name">Pending review</span>
                </div>
                <div class="contact-report-meta">
                    {{ $counts['pending'] }}
                    <span>{{ number_format($pendingPct, 1) }}%</span>
                </div>
            </div>
            <div class="contact-report-row">
                <div class="contact-report-row-main">
                    <span class="contact-report-dot rejected"></span>
                    <span class="contact-report-name">Rejected</span>
                </div>
                <div class="contact-report-meta">
                    {{ $counts['rejected'] }}
                    <span>{{ number_format($rejectedPct, 1) }}%</span>
                </div>
            </div>
        </div>
    </div>
</div>
