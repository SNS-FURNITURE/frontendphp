@extends('layouts.app')

@section('title', 'BOMs')

@section('content')
<div class="page-head">
    <div>
        <h1>Bills of materials</h1>
        <p class="muted" style="margin:0.35rem 0 0">Finished goods and component recipes for production.</p>
    </div>
    @if ($canCreate)
        <div class="toolbar">
            <button class="btn" type="button" onclick="document.getElementById('create-bom').hidden=false">New BOM</button>
        </div>
    @endif
</div>

@if ($canCreate)
<div class="card" id="create-bom" style="margin-bottom:1.25rem" @if(!$errors->any() || !old('name')) hidden @endif>
    <h2 style="margin:0 0 1rem;font-size:1.1rem">Create BOM</h2>
    <form method="POST" action="{{ route('manufacturing.boms.store') }}" id="bom-form">
        @csrf
        <div class="grid-2">
            <div>
                <label for="finished_item_id">Finished item</label>
                <select id="finished_item_id" name="finished_item_id" required>
                    <option value="">Select finished good</option>
                    @foreach ($finishedItems as $item)
                        <option value="{{ $item->id }}" @selected(old('finished_item_id') == $item->id)>{{ $item->sku }} — {{ $item->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="name">BOM name</label>
                <input id="name" name="name" value="{{ old('name') }}" required>
            </div>
            <div>
                <label for="version">Version</label>
                <input id="version" name="version" type="number" min="1" value="{{ old('version', 1) }}">
            </div>
        </div>
        <h3 style="margin:1rem 0 0.5rem;font-size:0.95rem">Lines</h3>
        <div id="bom-lines">
            <div class="grid-2" style="margin-bottom:0.5rem">
                <div>
                    <label>Component</label>
                    <select name="component_item_id[]" required>
                        <option value="">Select component</option>
                        @foreach ($components as $item)
                            <option value="{{ $item->id }}">{{ $item->sku }} — {{ $item->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label>Qty required</label>
                    <input name="quantity_required[]" type="number" step="0.01" min="0.01" value="1" required>
                </div>
            </div>
        </div>
        <div class="toolbar">
            <button class="btn ghost" type="button" onclick="addBomLine()">Add line</button>
            <button class="btn" type="submit">Save BOM</button>
            <button class="btn ghost" type="button" onclick="document.getElementById('create-bom').hidden=true">Cancel</button>
        </div>
    </form>
</div>
<script>
function addBomLine() {
    const wrap = document.getElementById('bom-lines');
    const row = wrap.firstElementChild.cloneNode(true);
    row.querySelectorAll('select,input').forEach(el => { if (el.tagName === 'SELECT') el.selectedIndex = 0; else el.value = '1'; });
    wrap.appendChild(row);
}
</script>
@endif

@forelse ($boms as $bom)
    <div class="card" style="margin-bottom:1rem">
        <div class="page-head" style="margin-bottom:0.75rem">
            <div>
                <h2 style="margin:0;font-size:1.05rem">{{ $bom->name }} <span class="badge">v{{ $bom->version }}</span></h2>
                <p class="muted" style="margin:0.25rem 0 0">FG: {{ $bom->finishedItem?->sku }} — {{ $bom->finishedItem?->name }}</p>
            </div>
            @if ($canCreate)
                <div class="toolbar">
                    <form method="POST" action="{{ route('manufacturing.boms.destroy', $bom) }}"
                          data-erp-confirm="Delete this BOM?"
                          data-erp-confirm-danger
                          data-erp-confirm-ok="Delete"
                          data-erp-remove="closest:.card">
                        @csrf
                        @method('DELETE')
                        <button class="btn ghost" type="submit">Delete</button>
                    </form>
                </div>
            @endif
        </div>

        @if ($canCreate)
            <form method="POST" action="{{ route('manufacturing.boms.update', $bom) }}" style="margin-bottom:1rem" class="grid-2">
                @csrf
                @method('PATCH')
                <div>
                    <label>Name</label>
                    <input name="name" value="{{ $bom->name }}" required>
                </div>
                <div>
                    <label>Version</label>
                    <input name="version" type="number" min="1" value="{{ $bom->version }}">
                </div>
                <div class="toolbar" style="grid-column:1/-1">
                    <button class="btn" type="submit">Update BOM</button>
                </div>
            </form>
        @endif

        <table class="data">
            <thead>
            <tr>
                <th>Component</th>
                <th>SKU</th>
                <th>Qty</th>
                <th>UOM</th>
                @if ($canCreate)<th></th>@endif
            </tr>
            </thead>
            <tbody data-erp-empty-message="No lines">
            @forelse ($bom->lines as $line)
                <tr>
                    <td>{{ $line->componentItem?->name }}</td>
                    <td>{{ $line->componentItem?->sku }}</td>
                    <td>
                        @if ($canCreate)
                            <form method="POST" action="{{ route('manufacturing.boms.lines.update', [$bom, $line]) }}" style="display:flex;gap:0.35rem;margin:0">
                                @csrf
                                @method('PATCH')
                                <input name="quantity_required" type="number" step="0.01" min="0.01" value="{{ $line->quantity_required }}" style="width:5rem;margin:0">
                                <button class="btn ghost" type="submit">Save</button>
                            </form>
                        @else
                            {{ number_format((float) $line->quantity_required, 2) }}
                        @endif
                    </td>
                    <td>{{ $line->componentItem?->unit_of_measure }}</td>
                    @if ($canCreate)
                        <td>
                            <form method="POST" action="{{ route('manufacturing.boms.lines.destroy', [$bom, $line]) }}"
                                  data-erp-confirm="Remove this line?"
                                  data-erp-confirm-danger
                                  data-erp-confirm-ok="Remove"
                                  style="margin:0">
                                @csrf
                                @method('DELETE')
                                <button class="btn ghost" type="submit">Remove</button>
                            </form>
                        </td>
                    @endif
                </tr>
            @empty
                <tr><td colspan="5" class="muted">No lines</td></tr>
            @endforelse
            </tbody>
        </table>

        @if ($canCreate)
            <form method="POST" action="{{ route('manufacturing.boms.lines.store', $bom) }}" class="grid-2" style="margin-top:0.75rem">
                @csrf
                <div>
                    <label>Add component</label>
                    <select name="component_item_id" required>
                        <option value="">Select</option>
                        @foreach ($components as $item)
                            <option value="{{ $item->id }}">{{ $item->sku }} — {{ $item->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label>Qty</label>
                    <input name="quantity_required" type="number" step="0.01" min="0.01" value="1" required>
                </div>
                <div class="toolbar" style="grid-column:1/-1">
                    <button class="btn" type="submit">Add line</button>
                </div>
            </form>
        @endif
    </div>
@empty
    <div class="card"><p class="muted" style="margin:0">No BOMs yet</p></div>
@endforelse
@endsection
