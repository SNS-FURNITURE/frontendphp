@extends('layouts.app')

@section('title', 'Procurement')

@section('content')
<div class="page-head">
    <div>
        <h1>Procurement hub</h1>
        <p class="muted" style="margin:0.35rem 0 0">Market research, external laborers, and site installations.</p>
    </div>
</div>

<div class="card" style="margin-bottom:1.25rem">
    <div class="page-head" style="margin-bottom:1rem">
        <h2 style="margin:0;font-size:1.1rem">Market research</h2>
        @if ($canCreate)
            <button class="btn" type="button" onclick="document.getElementById('create-research').hidden=false">Submit research</button>
        @endif
    </div>
    @if ($canCreate)
    <div id="create-research" style="margin-bottom:1rem" @if(!$errors->any() || !old('item_name')) hidden @endif>
        <form method="POST" action="{{ route('procurement.research.store') }}">
            @csrf
            <div class="grid-2">
                <div>
                    <label for="item_name">Item name</label>
                    <input id="item_name" name="item_name" value="{{ old('item_name') }}" required minlength="2">
                </div>
                <div>
                    <label for="category">Category</label>
                    <input id="category" name="category" value="{{ old('category', 'Raw Materials') }}" required>
                </div>
                <div>
                    <label for="quantity">Quantity</label>
                    <input id="quantity" name="quantity" type="number" step="0.01" min="0.01" value="{{ old('quantity', 1) }}" required>
                </div>
                <div>
                    <label for="unit_of_measure">UOM</label>
                    <input id="unit_of_measure" name="unit_of_measure" value="{{ old('unit_of_measure', 'pcs') }}" required>
                </div>
                <div>
                    <label for="selected_supplier_name">Supplier</label>
                    <input id="selected_supplier_name" name="selected_supplier_name" value="{{ old('selected_supplier_name') }}" required minlength="2">
                </div>
                <div>
                    <label for="selected_unit_price">Unit price</label>
                    <input id="selected_unit_price" name="selected_unit_price" type="number" step="0.01" min="0.01" value="{{ old('selected_unit_price') }}" required>
                </div>
            </div>
            <label for="specifications">Specs</label>
            <textarea id="specifications" name="specifications" rows="2">{{ old('specifications') }}</textarea>
            <div class="toolbar">
                <button class="btn" type="submit">Submit for approval</button>
                <button class="btn ghost" type="button" onclick="document.getElementById('create-research').hidden=true">Cancel</button>
            </div>
        </form>
    </div>
    @endif
    @if ($research->isEmpty())
        <p class="muted" style="margin:0">No market research entries</p>
    @else
        <table class="data">
            <thead><tr><th>Item</th><th>Qty</th><th>Supplier</th><th>Total</th><th>Status</th></tr></thead>
            <tbody>
            @foreach ($research as $row)
                <tr>
                    <td>{{ $row->item_name }}</td>
                    <td>{{ $row->quantity }} {{ $row->unit_of_measure }}</td>
                    <td>{{ $row->selected_supplier_name ?: '—' }}</td>
                    <td>{{ $row->selected_total_price !== null ? number_format((float)$row->selected_total_price, 2).' ETB' : '—' }}</td>
                    <td>
                        @if ($canApprove)
                            <form method="POST" action="{{ route('procurement.research.update', $row->id) }}" style="margin:0">
                                @csrf
                                @method('PATCH')
                                <select name="status" onchange="this.form.submit()" style="margin:0;width:auto;min-width:10rem">
                                    @foreach ($researchStatuses as $st)
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

@if ($canViewInstallation)
<div class="card" style="margin-bottom:1.25rem">
    <div class="page-head" style="margin-bottom:1rem">
        <h2 style="margin:0;font-size:1.1rem">Laborers</h2>
        <button class="btn" type="button" onclick="document.getElementById('create-laborer').hidden=false">Register laborer</button>
    </div>
    <div id="create-laborer" style="margin-bottom:1rem" @if(!$errors->any() || !old('full_name')) hidden @endif>
        <form method="POST" action="{{ route('procurement.laborers.store') }}">
            @csrf
            <div class="grid-2">
                <div>
                    <label for="full_name">Full name</label>
                    <input id="full_name" name="full_name" value="{{ old('full_name') }}" required minlength="2">
                </div>
                <div>
                    <label for="phone">Phone</label>
                    <input id="phone" name="phone" value="{{ old('phone') }}" required minlength="6">
                </div>
                <div>
                    <label for="specialty_skills">Specialty</label>
                    <input id="specialty_skills" name="specialty_skills" value="{{ old('specialty_skills') }}" required minlength="2">
                </div>
                <div>
                    <label for="daily_rate">Daily rate</label>
                    <input id="daily_rate" name="daily_rate" type="number" step="0.01" min="0" value="{{ old('daily_rate', 0) }}">
                </div>
            </div>
            <div class="toolbar">
                <button class="btn" type="submit">Register</button>
                <button class="btn ghost" type="button" onclick="document.getElementById('create-laborer').hidden=true">Cancel</button>
            </div>
        </form>
    </div>
    @if ($laborers->isEmpty())
        <p class="muted" style="margin:0">No laborers</p>
    @else
        <table class="data">
            <thead><tr><th>Name</th><th>Phone</th><th>Specialty</th><th>Rate</th><th>Status</th></tr></thead>
            <tbody>
            @foreach ($laborers as $row)
                <tr>
                    <td>{{ $row->full_name }}</td>
                    <td>{{ $row->phone }}</td>
                    <td>{{ $row->specialty_skills ?: '—' }}</td>
                    <td>{{ number_format((float)$row->daily_rate, 2) }}</td>
                    <td>
                        @if ($canEditLaborer)
                            <form method="POST" action="{{ route('procurement.laborers.update', $row->id) }}" style="margin:0">
                                @csrf
                                @method('PATCH')
                                <select name="status" onchange="this.form.submit()" style="margin:0;width:auto">
                                    @foreach ($laborerStatuses as $st)
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

<div class="card">
    <div class="page-head" style="margin-bottom:1rem">
        <h2 style="margin:0;font-size:1.1rem">Installations</h2>
        <button class="btn" type="button" onclick="document.getElementById('create-install').hidden=false">New job</button>
    </div>
    <div id="create-install" style="margin-bottom:1rem" @if(!$errors->any() || !old('customer_name')) hidden @endif>
        <form method="POST" action="{{ route('procurement.installations.store') }}">
            @csrf
            <div class="grid-2">
                <div>
                    <label for="customer_name">Customer</label>
                    <input id="customer_name" name="customer_name" value="{{ old('customer_name') }}" required minlength="2">
                </div>
                <div>
                    <label for="customer_phone">Phone</label>
                    <input id="customer_phone" name="customer_phone" value="{{ old('customer_phone') }}">
                </div>
                <div style="grid-column:1/-1">
                    <label for="site_address">Site address</label>
                    <input id="site_address" name="site_address" value="{{ old('site_address') }}" required minlength="3">
                </div>
                <div>
                    <label for="assigned_laborer_id">Laborer</label>
                    <select id="assigned_laborer_id" name="assigned_laborer_id">
                        <option value="">Unassigned</option>
                        @foreach ($laborers as $lab)
                            <option value="{{ $lab->id }}" @selected((string)old('assigned_laborer_id') === (string)$lab->id)>{{ $lab->full_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="agreed_total_payment">Total payment</label>
                    <input id="agreed_total_payment" name="agreed_total_payment" type="number" step="0.01" min="0" value="{{ old('agreed_total_payment', 0) }}">
                </div>
            </div>
            <div class="toolbar">
                <button class="btn" type="submit">Create job</button>
                <button class="btn ghost" type="button" onclick="document.getElementById('create-install').hidden=true">Cancel</button>
            </div>
        </form>
    </div>
    @if ($installations->isEmpty())
        <p class="muted" style="margin:0">No installation jobs</p>
    @else
        <table class="data">
            <thead><tr><th>Customer</th><th>Site</th><th>Laborer</th><th>Balance</th><th>Status</th></tr></thead>
            <tbody>
            @foreach ($installations as $row)
                <tr>
                    <td>{{ $row->customer_name }}</td>
                    <td>{{ $row->site_address }}</td>
                    <td>{{ $row->laborer?->full_name ?? '—' }}</td>
                    <td>{{ number_format((float)$row->balance_payment_amount, 2) }} ETB</td>
                    <td>
                        @if ($canEditInstallation)
                            <form method="POST" action="{{ route('procurement.installations.update', $row->id) }}" style="margin:0">
                                @csrf
                                @method('PATCH')
                                <select name="status" onchange="this.form.submit()" style="margin:0;width:auto;min-width:9rem">
                                    @foreach ($installationStatuses as $st)
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
@endif
@endsection
