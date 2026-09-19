@extends('layouts.app')

@section('title', 'Machinery')

@section('content')
<div class="page-head">
    <div>
        <h1>Machinery</h1>
        <p class="muted" style="margin:0.35rem 0 0">Workshop equipment catalog and status.</p>
    </div>
    @if ($canCreate)
        <div class="toolbar">
            <button class="btn" type="button" onclick="document.getElementById('create-machine').hidden=false">Register machine</button>
        </div>
    @endif
</div>

@if ($canCreate)
<div class="card" id="create-machine" style="margin-bottom:1.25rem" @if(!$errors->any() || !old('machine_code')) hidden @endif>
    <h2 style="margin:0 0 1rem;font-size:1.1rem">Register machine</h2>
    <form method="POST" action="{{ route('machinery.store') }}">
        @csrf
        <div class="grid-2">
            <div>
                <label for="machine_code">Machine code</label>
                <input id="machine_code" name="machine_code" value="{{ old('machine_code') }}" required minlength="2" placeholder="MAC-008">
            </div>
            <div>
                <label for="name">Name</label>
                <input id="name" name="name" value="{{ old('name') }}" required minlength="2">
            </div>
            <div>
                <label for="category">Category</label>
                <input id="category" name="category" value="{{ old('category', 'woodwork') }}" required>
            </div>
            <div>
                <label for="workshop_location">Workshop</label>
                <input id="workshop_location" name="workshop_location" value="{{ old('workshop_location', 'Wood Work Shop') }}" required>
            </div>
            <div>
                <label for="existing_qty">Quantity</label>
                <input id="existing_qty" name="existing_qty" type="number" min="1" value="{{ old('existing_qty', 1) }}" required>
            </div>
        </div>
        <div class="toolbar">
            <button class="btn" type="submit">Add machinery</button>
            <button class="btn ghost" type="button" onclick="document.getElementById('create-machine').hidden=true">Cancel</button>
        </div>
    </form>
</div>
@endif

<div class="card">
    @if ($machines->isEmpty())
        <p class="muted" style="margin:0">No machinery registered</p>
    @else
        <table class="data">
            <thead>
            <tr>
                <th>Code</th>
                <th>Name</th>
                <th>Category</th>
                <th>Workshop</th>
                <th>Qty</th>
                <th>Status</th>
            </tr>
            </thead>
            <tbody>
            @foreach ($machines as $row)
                <tr>
                    <td>{{ $row->machine_code }}</td>
                    <td>{{ $row->name }}</td>
                    <td>{{ $row->category }}</td>
                    <td>{{ $row->workshop_location }}</td>
                    <td>{{ $row->existing_qty }}</td>
                    <td>
                        @if ($canEdit)
                            <form method="POST" action="{{ route('machinery.status', $row) }}" style="margin:0">
                                @csrf
                                @method('PATCH')
                                <select name="status" onchange="this.form.submit()" style="margin:0;width:auto;min-width:10rem">
                                    @foreach ($statuses as $st)
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
