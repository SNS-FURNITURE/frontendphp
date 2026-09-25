@extends('layouts.app')

@section('title', 'Items')

@section('content')
<div class="page-head">
    <div>
        <h1>Items</h1>
        <p class="muted" style="margin:0.35rem 0 0">Inventory catalog — finished goods, raw materials, components.</p>
    </div>
    <div class="toolbar">
        <form method="GET" action="{{ route('inventory.items') }}" style="display:flex;gap:0.5rem;align-items:center;margin:0">
            <select name="item_type" onchange="this.form.submit()" style="margin:0;width:auto;min-width:10rem">
                <option value="">All types</option>
                @foreach ($types as $type)
                    <option value="{{ $type }}" @selected($itemType === $type)>{{ $type }}</option>
                @endforeach
            </select>
        </form>
        @if ($canCreate)
            <button class="btn" type="button" onclick="document.getElementById('create-item').hidden=false">New item</button>
        @endif
    </div>
</div>

@if ($canCreate)
<div class="card" id="create-item" style="margin-bottom:1.25rem" @if(!$errors->any() || !old('sku')) hidden @endif>
    <h2 style="margin:0 0 1rem;font-size:1.1rem">Create item</h2>
    <form method="POST" action="{{ route('inventory.items.store') }}">
        @csrf
        <div class="grid-2">
            <div>
                <label for="sku">SKU</label>
                <input id="sku" name="sku" value="{{ old('sku') }}" required>
            </div>
            <div>
                <label for="name">Name</label>
                <input id="name" name="name" value="{{ old('name') }}" required minlength="2">
            </div>
            <div>
                <label for="item_type">Type</label>
                <select id="item_type" name="item_type" required>
                    @foreach ($types as $type)
                        <option value="{{ $type }}" @selected(old('item_type') === $type)>{{ $type }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="unit_of_measure">UOM</label>
                <input id="unit_of_measure" name="unit_of_measure" value="{{ old('unit_of_measure', \App\Support\UnitOfMeasure::DEFAULT) }}" required>
            </div>
            <div>
                <label for="reorder_level">Reorder level</label>
                <input id="reorder_level" name="reorder_level" type="number" step="0.01" min="0" value="{{ old('reorder_level', 0) }}">
            </div>
            <div>
                <label for="initial_stock">Initial stock</label>
                <input id="initial_stock" name="initial_stock" type="number" step="0.01" min="0" value="{{ old('initial_stock', 0) }}">
            </div>
        </div>
        <div class="toolbar">
            <button class="btn" type="submit">Save item</button>
            <button class="btn ghost" type="button" onclick="document.getElementById('create-item').hidden=true">Cancel</button>
        </div>
    </form>
</div>
@endif

<div class="card">
    @if ($items->isEmpty())
        <p class="muted" style="margin:0">No items found</p>
    @else
        <table class="data">
            <thead>
            <tr>
                <th>SKU</th>
                <th>Name</th>
                <th>Type</th>
                <th>UOM</th>
                <th>Reorder</th>
            </tr>
            </thead>
            <tbody>
            @foreach ($items as $item)
                <tr>
                    <td>{{ $item->sku }}</td>
                    <td>{{ $item->name }}</td>
                    <td><span class="badge">{{ $item->item_type }}</span></td>
                    <td>{{ $item->unit_of_measure }}</td>
                    <td>{{ number_format((float) $item->reorder_level, 2) }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif
</div>
@endsection
