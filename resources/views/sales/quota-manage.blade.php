@extends('layouts.app')

@section('title', 'Sales Quotas')

@section('content')
<div class="page-head">
    <div>
        <h1>Sales quotas</h1>
        <p class="muted" style="margin:.35rem 0 0">Set monthly sales targets for each sales rep — {{ $period['label'] }}</p>
    </div>
</div>

<form method="POST" action="{{ route('sales.quota.store') }}">
    @csrf
    <div class="card">
        <table class="data">
            <thead>
            <tr>
                <th>Sales rep</th>
                <th>Monthly quota (contacts)</th>
                <th>Submitted</th>
                <th>Approved</th>
                <th>Pending</th>
                <th>Rejected</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            @foreach ($quotas as $row)
                <tr>
                    <td><a href="{{ route('sales.reps.report', $row['user']) }}">{{ $row['user']->full_name }}</a></td>
                    <td>
                        <input type="hidden" name="quotas[{{ $loop->index }}][user_id]" value="{{ $row['user']->id }}">
                        <input type="number" name="quotas[{{ $loop->index }}][quota]" value="{{ (int) $row['row']->quota }}" min="0" max="10000" required style="max-width:8rem;margin:0">
                    </td>
                    <td>{{ $row['counts']['total'] }}</td>
                    <td>{{ $row['counts']['approved'] }}</td>
                    <td>{{ $row['counts']['pending'] }}</td>
                    <td>{{ $row['counts']['rejected'] }}</td>
                    <td><a class="btn ghost" style="padding:.25rem .55rem;font-size:.78rem" href="{{ route('sales.reps.report', $row['user']) }}">View report</a></td>
                </tr>
            @endforeach
            </tbody>
        </table>
        <div class="toolbar" style="margin-top:1rem">
            <button type="submit" class="btn">Save quotas</button>
        </div>
    </div>
</form>
@endsection
