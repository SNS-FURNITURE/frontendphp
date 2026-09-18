@extends('layouts.app')

@section('title', 'Funding')

@section('content')
<div class="page-head">
    <div>
        <h1>Funding</h1>
        <p class="muted" style="margin:0.35rem 0 0">Company Manager requests capital. Finance approves. Manager marks received and routes to Finance.</p>
    </div>
    @if ($canCreate)
        <div class="toolbar">
            <button class="btn" type="button" onclick="document.getElementById('create-funding').hidden=false">New request</button>
        </div>
    @endif
</div>

@if ($canCreate)
<div class="card" id="create-funding" style="margin-bottom:1.25rem" @if(!$errors->any() || !old('title')) hidden @endif>
    <h2 style="margin:0 0 1rem;font-size:1.1rem">Create funding request</h2>
    <form method="POST" action="{{ route('funding.store') }}">
        @csrf
        <div class="grid-2">
            <div>
                <label for="title">Title</label>
                <input id="title" name="title" value="{{ old('title') }}" required minlength="2">
            </div>
            <div>
                <label for="amount">Amount (ETB)</label>
                <input id="amount" name="amount" type="number" step="0.01" min="0.01" value="{{ old('amount', 150000) }}" required>
            </div>
            <div>
                <label for="category">Category</label>
                <select id="category" name="category" required>
                    @foreach (['Raw Materials & Timber','Machinery & Equipment','Payroll Bridge','Operations','Other'] as $cat)
                        <option value="{{ $cat }}" @selected(old('category', 'Raw Materials & Timber') === $cat)>{{ $cat }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="deadline">Deadline (optional)</label>
                <input id="deadline" name="deadline" type="date" value="{{ old('deadline') }}">
            </div>
        </div>
        <label for="purpose">Justification</label>
        <textarea id="purpose" name="purpose" rows="3" required minlength="3">{{ old('purpose') }}</textarea>
        <div class="toolbar">
            <button class="btn" type="submit">Submit request</button>
            <button class="btn ghost" type="button" onclick="document.getElementById('create-funding').hidden=true">Cancel</button>
        </div>
    </form>
</div>
@endif

<div class="card">
    @if ($requests->isEmpty())
        <p class="muted" style="margin:0">No funding requests yet</p>
    @else
        <table class="data">
            <thead>
            <tr>
                <th>Request</th>
                <th>Amount</th>
                <th>Purpose / Category</th>
                <th>Requested by</th>
                <th>Status</th>
            </tr>
            </thead>
            <tbody>
            @foreach ($requests as $row)
                <tr>
                    <td>{{ $row->title }}</td>
                    <td>{{ number_format((float) $row->amount, 2) }} ETB</td>
                    <td>{{ $row->purpose ?: '—' }}</td>
                    <td>{{ $row->requester?->full_name ?? '—' }}</td>
                    <td>
                        @if ($canApprove || $canEditFunding)
                            <form method="POST" action="{{ route('funding.status', $row) }}" style="margin:0">
                                @csrf
                                @method('PATCH')
                                <select name="status" onchange="this.form.submit()" style="margin:0;width:auto;min-width:9rem">
                                    @foreach ($statuses as $st)
                                        @if (in_array($st, ['approved','rejected'], true) && !$canApprove)
                                            @continue
                                        @endif
                                        @if (in_array($st, ['received','routed'], true) && !$canEditFunding)
                                            @continue
                                        @endif
                                        <option value="{{ $st }}" @selected($row->status === $st)>{{ $st }}</option>
                                    @endforeach
                                </select>
                            </form>
                        @else
                            <span class="badge">{{ $row->status }}</span>
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif
</div>
@endsection
