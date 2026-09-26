@extends('layouts.app')

@section('title', 'OMS live pipeline')

@section('content')
@include('operations._oms-tabs', ['tab' => 'pipeline'])

<div class="page-head">
    <div>
        <h1>OMS live pipeline</h1>
        <p class="muted" style="margin:0.35rem 0 0">
            Auto-updates every 10 seconds while this tab is visible.
            <span id="pipeline-stamp" class="badge" style="margin-left:0.35rem">loading…</span>
        </p>
    </div>
</div>

<div class="card">
    <div class="table-wrap">
        <table class="data" id="pipeline-table">
            <thead>
            <tr>
                <th>Invoice</th>
                <th>Designer</th>
                <th>PM</th>
                <th>Design</th>
                <th>Factory</th>
                <th>Materials</th>
                <th>Assembly</th>
                <th>Delivery</th>
                <th></th>
            </tr>
            </thead>
            <tbody id="pipeline-body">
            <tr><td colspan="9" class="muted">Loading…</td></tr>
            </tbody>
        </table>
    </div>
</div>

<script>
(function () {
    const url = @json($pipelineUrl);
    const body = document.getElementById('pipeline-body');
    const stamp = document.getElementById('pipeline-stamp');
    let timer = null;

    function findPhase(phases, key) {
        if (!phases) return null;
        if (Array.isArray(phases)) {
            return phases.find(function (p) { return p.key === key; }) || null;
        }
        return phases[key] || null;
    }

    function phaseLabel(phase) {
        if (!phase) return '—';
        let text = phase.status || '—';
        if (phase.checkpoints_total > 0) {
            text += ' (' + phase.checkpoints_done + '/' + phase.checkpoints_total + ')';
        }
        return text;
    }

    function render(rows) {
        if (!rows.length) {
            body.innerHTML = '<tr><td colspan="9" class="muted">No accepted orders in pipeline</td></tr>';
            return;
        }
        body.innerHTML = rows.map(function (row) {
            return '<tr>'
                + '<td>' + row.invoice_number + '</td>'
                + '<td>' + (row.designer || '—') + '</td>'
                + '<td>' + (row.product_manager || '—') + '</td>'
                + '<td><span class="badge">' + phaseLabel(findPhase(row.phases, 'design')) + '</span></td>'
                + '<td><span class="badge">' + phaseLabel(findPhase(row.phases, 'factory_coloring')) + '</span></td>'
                + '<td><span class="badge">' + phaseLabel(findPhase(row.phases, 'materials')) + '</span>'
                + (row.materials_pending ? ' · pend ' + row.materials_pending : '')
                + (row.procurement_open ? ' · proc ' + row.procurement_open : '')
                + '</td>'
                + '<td><span class="badge">' + phaseLabel(findPhase(row.phases, 'assembly')) + '</span></td>'
                + '<td><span class="badge">' + phaseLabel(findPhase(row.phases, 'delivery')) + '</span>'
                + (row.delivery_status ? ' · ' + row.delivery_status : '')
                + '</td>'
                + '<td><a class="btn ghost" href="' + row.url + '">Open</a></td>'
                + '</tr>';
        }).join('');
    }

    function load() {
        if (document.visibilityState === 'hidden') {
            return;
        }
        fetch(url, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                render(data.rows || []);
                stamp.textContent = 'updated ' + new Date(data.generated_at || Date.now()).toLocaleTimeString();
            })
            .catch(function () {
                stamp.textContent = 'update failed';
            });
    }

    function start() {
        load();
        if (timer) clearInterval(timer);
        timer = setInterval(load, 10000);
    }

    document.addEventListener('visibilitychange', function () {
        if (document.visibilityState === 'visible') {
            start();
        } else if (timer) {
            clearInterval(timer);
            timer = null;
        }
    });

    start();
})();
</script>
@endsection
