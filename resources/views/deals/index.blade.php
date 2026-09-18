@extends('layouts.app')

@section('title', 'Deals')

@section('content')
<div class="page-head">
    <div>
        <h1>Deals</h1>
        <p class="muted" style="margin:0.35rem 0 0">Sales review → manager review</p>
    </div>
    <div class="toolbar">
        <a class="btn ghost" href="{{ route('deals.index') }}">All</a>
        <a class="btn ghost" href="{{ route('deals.index', ['mine' => 1]) }}">My work</a>
        @if ($canCreate)
            <button class="btn" type="button" onclick="document.getElementById('create-deal').hidden=false">New deal</button>
        @endif
    </div>
</div>

@if ($canCreate)
<div class="card" id="create-deal" style="margin-bottom:1.25rem" @if(!$errors->any()) hidden @endif>
    <form method="POST" action="{{ route('deals.store') }}">
        @csrf
        <div class="grid-2">
            <div><label>Title</label><input name="title" value="{{ old('title') }}" required minlength="2"></div>
            <div><label>Customer</label><input name="customer_name" value="{{ old('customer_name') }}" required minlength="2"></div>
            <div><label>Product category</label><input name="product_category" value="{{ old('product_category') }}" required></div>
            <div><label>Deal value</label><input type="number" step="0.01" min="0" name="deal_value" value="{{ old('deal_value', 0) }}"></div>
        </div>
        <label>Notes</label>
        <textarea name="notes" rows="2">{{ old('notes') }}</textarea>
        <button class="btn" type="submit">Create</button>
    </form>
</div>
@endif

<div class="card">
    @if ($deals->isEmpty())
        <p class="muted" style="margin:0">No deals</p>
    @else
        <table class="data">
            <thead><tr><th>Title</th><th>Customer</th><th>Value</th><th>Owner</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @foreach ($deals as $deal)
                <tr>
                    <td>{{ $deal->title }}</td>
                    <td>{{ $deal->customer_name }}</td>
                    <td>{{ number_format((float) $deal->deal_value, 2) }}</td>
                    <td>{{ $deal->owner?->full_name ?: '—' }}</td>
                    <td><span class="badge">{{ $deal->status }}</span></td>
                    <td class="toolbar">
                        @if ($canSalesReview && !in_array($deal->status, ['sales_approved','won','lost'], true))
                            <form method="POST" action="{{ route('deals.sales-review', $deal->id) }}">@csrf
                                <input type="hidden" name="action" value="approve">
                                <button class="btn" type="submit">Sales approve</button>
                            </form>
                            <form method="POST" action="{{ route('deals.sales-review', $deal->id) }}">@csrf
                                <input type="hidden" name="action" value="lost">
                                <button class="btn ghost" type="submit">Lost</button>
                            </form>
                        @endif
                        @if ($canManagerReview && $deal->status === 'sales_approved')
                            <form method="POST" action="{{ route('deals.manager-review', $deal->id) }}">@csrf
                                <input type="hidden" name="action" value="approve">
                                <button class="btn" type="submit">Manager approve</button>
                            </form>
                            <form method="POST" action="{{ route('deals.manager-review', $deal->id) }}">@csrf
                                <input type="hidden" name="action" value="reject">
                                <button class="btn ghost" type="submit">Return</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif
</div>
@endsection
