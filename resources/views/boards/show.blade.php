@extends('layouts.app')
@section('title', $board->name)
@section('content')
<div class="page-head">
    <div><h1>{{ $board->name }}</h1><p class="muted" style="margin:0.35rem 0 0">{{ $board->board_type }} board</p></div>
    <a class="btn ghost" href="{{ route('boards.index') }}">Back</a>
</div>
<div class="card" style="margin-bottom:1.25rem">
    <form method="POST" action="{{ route('boards.items.store', $board) }}">@csrf
        <div class="grid-2">
            <div><label>Item title</label><input name="title" required></div>
            <div><label>Group</label>
                <select name="group_id">
                    @foreach ($board->groups as $g)<option value="{{ $g->id }}">{{ $g->name }}</option>@endforeach
                </select>
            </div>
        </div>
        <button class="btn" type="submit">Add item</button>
    </form>
</div>
<div class="card">
@if ($board->items->isEmpty())
<p class="muted" style="margin:0">No items</p>
@else
<table class="data">
<thead><tr><th>Title</th><th>Status</th>
@foreach ($board->columns as $col)<th>{{ $col->name }}</th>@endforeach
</tr></thead>
<tbody>
@foreach ($board->items as $item)
@php $itemValues = $values->get($item->id, collect())->keyBy('column_id'); @endphp
<tr>
<td>{{ $item->title }}</td>
<td><span class="badge">{{ $item->status }}</span></td>
@foreach ($board->columns as $col)
<td>
@if ($canEdit)
<form method="POST" action="{{ route('boards.items.values', $item) }}" style="margin:0;display:flex;gap:0.35rem">
@csrf @method('PATCH')
<input type="hidden" name="column_id" value="{{ $col->id }}">
<input name="value_text" value="{{ $itemValues->get($col->id)?->value_text }}" style="margin:0">
<button class="btn ghost" type="submit" style="padding:0.3rem 0.5rem">Set</button>
</form>
@else
{{ $itemValues->get($col->id)?->value_text ?: '—' }}
@endif
</td>
@endforeach
</tr>
@endforeach
</tbody>
</table>
@endif
</div>
@endsection
