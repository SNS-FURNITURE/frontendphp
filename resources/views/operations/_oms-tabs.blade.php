@php($activeTab = $tab ?? 'queue')
<div class="toolbar" style="margin-bottom:1rem;flex-wrap:wrap">
    <a class="btn {{ $activeTab === 'queue' ? '' : 'ghost' }}" href="{{ route('operations.oms.dashboard') }}">Incoming queue</a>
    <a class="btn {{ $activeTab === 'pipeline' ? '' : 'ghost' }}" href="{{ route('operations.oms.pipeline') }}">Live pipeline</a>
</div>
