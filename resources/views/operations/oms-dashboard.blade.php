@extends('layouts.app')

@section('title', 'OMS queue')

@section('content')
<div class="page-head">
    <div>
        <h1>OMS incoming queue</h1>
        <p class="muted" style="margin:0.35rem 0 0">Review approved and issued orders before production scheduling.</p>
    </div>
</div>

<div class="card">
    @if ($intakes->isEmpty())
        <p class="muted" style="margin:0">No incoming orders</p>
    @else
        <table class="data">
            <thead>
            <tr>
                <th>Invoice</th>
                <th>Source</th>
                <th>Status</th>
                <th>Submitted</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            @foreach ($intakes as $intake)
                <tr>
                    <td>{{ $intake->invoice_number }}</td>
                    <td>{{ $intake->source_type }}{{ $intake->sourceUser ? ' — '.$intake->sourceUser->full_name : '' }}</td>
                    <td><span class="badge">{{ $intake->status }}</span></td>
                    <td>{{ optional($intake->created_at)->format('Y-m-d H:i') }}</td>
                    <td><a class="btn ghost" href="{{ route('operations.orders.show', $intake) }}">Open</a></td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif
</div>
@endsection
