@extends('layouts.app')

@section('title', 'Leads')

@section('content')
<div class="page-head">
    <div>
        <h1>Leads</h1>
        <p class="muted" style="margin:0.35rem 0 0">Own-only without verify · convert when verified</p>
    </div>
    <div class="toolbar">
        <a class="btn ghost" href="{{ route('leads.index', ['source' => 'all']) }}">All</a>
        <a class="btn ghost" href="{{ route('leads.index', ['source' => 'website']) }}">Website</a>
        <a class="btn ghost" href="{{ route('leads.index', ['source' => 'manual']) }}">Manual</a>
        @if ($canCreate)
            <button class="btn" type="button" onclick="document.getElementById('create-lead').hidden=false">Add lead</button>
        @endif
    </div>
</div>

@if ($canCreate)
<div class="card" id="create-lead" style="margin-bottom:1.25rem" @if(!$errors->any()) hidden @endif>
    <form method="POST" action="{{ route('leads.store') }}">
        @csrf
        <div class="grid-2">
            <div><label>Name</label><input name="name" value="{{ old('name') }}" required minlength="2"></div>
            <div><label>Phone</label><input name="phone" value="{{ old('phone') }}" required minlength="8"></div>
            <div><label>Email</label><input name="email" type="email" value="{{ old('email') }}"></div>
            <div><label>Source</label><input name="source" value="{{ old('source', 'manual') }}" required></div>
            <div><label>Product interest</label><input name="product_interest" value="{{ old('product_interest') }}" required></div>
            <div><label>Design source</label><input name="design_source" value="{{ old('design_source', 'showroom') }}" required></div>
        </div>
        <label>Notes</label>
        <textarea name="notes" rows="2">{{ old('notes') }}</textarea>
        <button class="btn" type="submit">Save</button>
    </form>
</div>
@endif

<div class="card">
    @if ($leads->isEmpty())
        <p class="muted" style="margin:0">No leads</p>
    @else
        <table class="data">
            <thead><tr><th>Name</th><th>Phone</th><th>Source</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @foreach ($leads as $lead)
                <tr>
                    <td>{{ $lead->name }}</td>
                    <td>{{ $lead->phone }}</td>
                    <td>{{ $lead->source ?: '—' }}</td>
                    <td>
                        @if ($canVerify)
                            <form method="POST" action="{{ route('leads.update', $lead->id) }}" style="margin:0">
                                @csrf @method('PATCH')
                                <select name="status" onchange="this.form.submit()">
                                    @foreach (['pending','verified','rejected','contacted','showroom_visit','not_interested'] as $st)
                                        <option value="{{ $st }}" @selected($lead->status === $st)>{{ $st }}</option>
                                    @endforeach
                                </select>
                                @if ($lead->status === 'verified' || true)
                                    <input type="hidden" name="verified_by" value="{{ auth()->id() }}">
                                @endif
                            </form>
                        @else
                            <span class="badge">{{ $lead->status }}</span>
                        @endif
                    </td>
                    <td>
                        @if ($canConvert)
                            <form method="POST" action="{{ route('leads.convert', $lead->id) }}">@csrf
                                <button class="btn ghost" type="submit">Convert</button>
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
