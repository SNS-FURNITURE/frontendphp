@php($activeTab = $tab ?? 'check')
<div class="toolbar" style="margin-bottom:1rem;flex-wrap:wrap">
    <a class="btn {{ $activeTab === 'check' || $activeTab === 'queue' ? '' : 'ghost' }}" href="{{ route('operations.oms.check-invoice') }}">Check invoice</a>
    <a class="btn {{ $activeTab === 'schedule' ? '' : 'ghost' }}" href="{{ route('operations.oms.schedule') }}">Schedule production</a>
    <a class="btn {{ $activeTab === 'pipeline' ? '' : 'ghost' }}" href="{{ route('operations.oms.pipeline') }}">Live pipeline</a>
</div>
