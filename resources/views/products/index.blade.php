@extends('layouts.app')

@section('title', 'Products')

@push('styles')
<style>
    .products-layout { display: flex; gap: 1.25rem; align-items: flex-start; }
    .products-sidebar { width: 168px; flex-shrink: 0; }
    .products-sidebar .nav-section { margin-top: 0; }
    .products-cat-btn {
        display: block; width: 100%; text-align: left;
        padding: 0.42rem 0.65rem; margin-bottom: 0.2rem;
        border-radius: 8px; border: 1px solid var(--border);
        background: transparent; color: var(--text);
        font-size: 0.88rem; font-weight: 500; cursor: pointer;
        text-decoration: none;
    }
    .products-cat-btn.is-active {
        background: var(--purple); color: #fff; border-color: var(--purple); font-weight: 600;
    }
    .products-cat-btn:not(.is-active):hover { background: var(--nav-hover); }
    .products-main { flex: 1; min-width: 0; }
    .products-search { margin-bottom: 1rem; }
    .products-search input { margin: 0; }
    .products-list {
        border: 1px solid var(--border);
        border-radius: 8px;
        overflow: hidden;
        background: var(--panel);
    }
    .products-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        padding: 0.45rem 0.85rem;
        border-bottom: 1px solid var(--border);
        min-height: 2.5rem;
        cursor: pointer;
        transition: background 0.12s ease;
    }
    .products-row:hover { background: var(--nav-hover); }
    .products-row:focus-visible {
        outline: 2px solid var(--purple);
        outline-offset: -2px;
    }
    .products-row.is-hidden { display: none; }
    .products-row:last-child { border-bottom: 0; }
    .products-row-main { min-width: 0; flex: 1; }
    .products-row-name {
        font-size: 0.9rem;
        font-weight: 600;
        color: var(--text);
        line-height: 1.25;
        margin: 0;
    }
    .products-row-desc {
        font-size: 0.72rem;
        color: var(--muted);
        margin: 0.1rem 0 0;
        line-height: 1.25;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .products-row-meta {
        flex-shrink: 0;
        text-align: right;
        font-size: 0.78rem;
        color: var(--muted);
        white-space: nowrap;
    }
    .products-row-price {
        display: block;
        font-weight: 600;
        color: var(--text);
        font-size: 0.85rem;
    }
    .product-detail-grid {
        display: grid;
        gap: 0.85rem;
        margin: 0;
    }
    .product-detail-grid dt {
        font-size: 0.72rem;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: var(--muted);
        margin: 0 0 0.15rem;
    }
    .product-detail-grid dd {
        margin: 0;
        font-size: 0.95rem;
        color: var(--text);
        line-height: 1.4;
    }
    @media (max-width: 900px) {
        .products-layout { flex-direction: column; }
        .products-sidebar { width: 100%; display: flex; flex-wrap: wrap; gap: 0.35rem; }
        .products-sidebar .nav-section { width: 100%; }
        .products-cat-btn { width: auto; margin: 0; }
    }
</style>
@endpush

@section('content')
<div class="page-head">
    <div>
        <h1>Products</h1>
        <p class="muted" style="margin:.35rem 0 0" id="products-count">
            {{ $products->count() }} products{{ $category !== '' ? ' in '.$category : '' }}
        </p>
    </div>
    @if ($canManage)
        <div class="toolbar">
            <button class="btn" type="button" id="toggle-add-product">Add product</button>
        </div>
    @endif
</div>

@if ($canManage)
<div class="card" id="add-product-panel" style="margin-bottom:1.25rem" @if(!$errors->any() || !old('name')) hidden @endif>
    <h2 style="margin:0 0 1rem;font-size:1.1rem">New product</h2>
    <form method="POST" action="{{ route('products.store') }}">
        @csrf
        <div class="grid-2">
            <div>
                <label for="product-category">Category *</label>
                <input id="product-category" name="category" list="product-categories" value="{{ old('category', $category) }}" required maxlength="100" placeholder="e.g. Beds">
                <datalist id="product-categories">
                    @foreach ($categories as $cat)
                        <option value="{{ $cat }}"></option>
                    @endforeach
                </datalist>
            </div>
            <div>
                <label for="product-name">Name *</label>
                <input id="product-name" name="name" value="{{ old('name') }}" required maxlength="255">
            </div>
            <div style="grid-column:1/-1">
                <label for="product-description">Description</label>
                <textarea id="product-description" name="description" rows="2" maxlength="5000" placeholder="Size, variant, notes…">{{ old('description') }}</textarea>
            </div>
            <div>
                <label for="product-unit">Unit *</label>
                <select id="product-unit" name="unit" required>
                    @foreach ($units as $unit)
                        <option value="{{ $unit }}" @selected(old('unit', 'pieces') === $unit)>{{ $unit }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="product-unit-price">Unit price (ETB) *</label>
                <input id="product-unit-price" name="unit_price" type="number" step="0.01" min="0" value="{{ old('unit_price') }}" required>
            </div>
        </div>
        <div class="toolbar" style="margin-top:1rem">
            <button class="btn" type="submit">Save product</button>
            <button class="btn ghost" type="button" id="cancel-add-product">Cancel</button>
        </div>
    </form>
</div>
@endif

<div class="products-search">
    <input type="search" id="products-search" value="{{ $search }}" placeholder="Search by name, description, or category…" aria-label="Search products" autocomplete="off">
</div>

<div class="products-layout">
    <aside class="products-sidebar no-print">
        <div class="nav-section">Categories</div>
        <a class="products-cat-btn {{ $category === '' ? 'is-active' : '' }}" href="{{ route('products.index') }}">All products</a>
        @foreach ($categories as $cat)
            <a class="products-cat-btn {{ $category === $cat ? 'is-active' : '' }}" href="{{ route('products.index', ['category' => $cat]) }}">{{ $cat }}</a>
        @endforeach
    </aside>

    <div class="products-main">
        @if ($products->isEmpty())
            <div class="card" id="products-empty">
                <p class="muted" style="margin:0;text-align:center;padding:1.5rem">No products found.</p>
            </div>
            <div class="products-list" id="products-list" hidden></div>
        @else
            <div class="card" id="products-empty" hidden>
                <p class="muted" style="margin:0;text-align:center;padding:1.5rem">No products match your search.</p>
            </div>
            <div class="products-list" id="products-list">
                @foreach ($products as $product)
                    <div
                        class="products-row"
                        role="button"
                        tabindex="0"
                        data-product-id="{{ $product->id }}"
                        data-search="{{ mb_strtolower(trim($product->name.' '.($product->description ?? '').' '.$product->category)) }}"
                        data-category="{{ $product->category }}"
                        data-name="{{ $product->name }}"
                        data-description="{{ $product->description ?? '' }}"
                        data-unit="{{ $product->unit }}"
                        @if ($canViewPrices)
                            data-price="{{ number_format((float) $product->unit_price, 2) }}"
                            data-unit-price="{{ (float) $product->unit_price }}"
                        @endif
                    >
                        <div class="products-row-main">
                            <p class="products-row-name">{{ $product->name }}</p>
                            @if (filled($product->description))
                                <p class="products-row-desc">{{ $product->description }}</p>
                            @endif
                        </div>
                        <div class="products-row-meta">
                            <span>{{ $product->unit }}</span>
                            @if ($canViewPrices)
                                <span class="products-row-price">{{ number_format((float) $product->unit_price, 2) }} ETB</span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>

<div class="logout-modal no-print" id="product-detail-modal" role="dialog" aria-modal="true" aria-labelledby="product-detail-title" hidden>
    <div class="logout-dialog" style="max-width:520px">
        <h3 id="product-detail-title" style="margin:0 0 1rem">Product details</h3>

        <div id="product-detail-view">
            <dl class="product-detail-grid">
                <div>
                    <dt>Category</dt>
                    <dd id="product-detail-category"></dd>
                </div>
                <div>
                    <dt>Name</dt>
                    <dd id="product-detail-name"></dd>
                </div>
                <div>
                    <dt>Description</dt>
                    <dd id="product-detail-description"></dd>
                </div>
                <div>
                    <dt>Unit</dt>
                    <dd id="product-detail-unit"></dd>
                </div>
                @if ($canViewPrices)
                    <div>
                        <dt>Unit price</dt>
                        <dd id="product-detail-price"></dd>
                    </div>
                @endif
            </dl>
            <div class="logout-actions">
                @if ($canManage)
                    <button type="button" class="btn" id="product-detail-edit-btn">Edit</button>
                @endif
                <button type="button" class="btn ghost" data-product-detail-close>Close</button>
            </div>
        </div>

        @if ($canManage)
        <form id="product-detail-edit" method="POST" action="" hidden>
            @csrf
            @method('PATCH')
            <div class="grid-2" style="gap:.75rem">
                <div style="grid-column:1/-1">
                    <label for="edit-product-category">Category *</label>
                    <input id="edit-product-category" name="category" list="product-categories" required maxlength="100">
                </div>
                <div style="grid-column:1/-1">
                    <label for="edit-product-name">Name *</label>
                    <input id="edit-product-name" name="name" required maxlength="255">
                </div>
                <div style="grid-column:1/-1">
                    <label for="edit-product-description">Description</label>
                    <textarea id="edit-product-description" name="description" rows="3" maxlength="5000"></textarea>
                </div>
                <div>
                    <label for="edit-product-unit">Unit *</label>
                    <select id="edit-product-unit" name="unit" required>
                        @foreach ($units as $unit)
                            <option value="{{ $unit }}">{{ $unit }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="edit-product-unit-price">Unit price (ETB) *</label>
                    <input id="edit-product-unit-price" name="unit_price" type="number" step="0.01" min="0" required>
                </div>
            </div>
            <div class="logout-actions" style="margin-top:1rem">
                <button type="submit" class="btn">Save changes</button>
                <button type="button" class="btn ghost" id="product-detail-cancel-edit">Cancel</button>
            </div>
        </form>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    var searchInput = document.getElementById('products-search');
    var list = document.getElementById('products-list');
    var emptyState = document.getElementById('products-empty');
    var countEl = document.getElementById('products-count');
    var modal = document.getElementById('product-detail-modal');
    var categoryLabel = @json($category !== '' ? ' in '.$category : '');
    var canViewPrices = @json($canViewPrices);
    var canManage = @json($canManage);
    var updateUrlTemplate = @json(route('products.update', ['product' => '__ID__']));
    var rows = list ? Array.prototype.slice.call(list.querySelectorAll('.products-row')) : [];
    var debounceTimer = null;
    var activeRow = null;
    var detailView = document.getElementById('product-detail-view');
    var detailEdit = document.getElementById('product-detail-edit');

    function visibleCount() {
        return rows.filter(function (row) { return !row.classList.contains('is-hidden'); }).length;
    }

    function updateCount() {
        if (!countEl) {
            return;
        }

        var count = visibleCount();
        var suffix = searchInput && searchInput.value.trim() !== '' ? ' matching search' : '';
        countEl.textContent = count + ' product' + (count === 1 ? '' : 's') + categoryLabel + suffix;
    }

    function toggleEmptyState() {
        if (!emptyState || !list) {
            return;
        }

        var hasVisible = visibleCount() > 0;
        emptyState.hidden = hasVisible;
        list.hidden = !hasVisible;
    }

    function applySearch() {
        var query = searchInput ? searchInput.value.trim().toLowerCase() : '';

        rows.forEach(function (row) {
            if (query === '') {
                row.classList.remove('is-hidden');
                return;
            }

            var haystack = row.getAttribute('data-search') || '';
            row.classList.toggle('is-hidden', haystack.indexOf(query) === -1);
        });

        updateCount();
        toggleEmptyState();

        var url = new URL(window.location.href);
        if (query === '') {
            url.searchParams.delete('q');
        } else {
            url.searchParams.set('q', query);
        }
        window.history.replaceState({}, '', url);
    }

    if (searchInput) {
        searchInput.addEventListener('input', function () {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(applySearch, 120);
        });

        if (searchInput.value.trim() !== '') {
            applySearch();
        }
    }

    function showDetailView() {
        if (detailView) {
            detailView.hidden = false;
        }
        if (detailEdit) {
            detailEdit.hidden = true;
        }
        if (modal) {
            document.getElementById('product-detail-title').textContent = 'Product details';
        }
    }

    function showDetailEdit() {
        if (!canManage || !detailEdit || !activeRow) {
            return;
        }

        var productId = activeRow.getAttribute('data-product-id');
        var action = updateUrlTemplate.replace('__ID__', productId);
        var query = searchInput ? searchInput.value.trim() : '';
        if (query !== '') {
            action += (action.indexOf('?') === -1 ? '?' : '&') + 'q=' + encodeURIComponent(query);
        }
        detailEdit.action = action;

        document.getElementById('edit-product-category').value = activeRow.getAttribute('data-category') || '';
        document.getElementById('edit-product-name').value = activeRow.getAttribute('data-name') || '';
        document.getElementById('edit-product-description').value = activeRow.getAttribute('data-description') || '';
        document.getElementById('edit-product-unit').value = activeRow.getAttribute('data-unit') || 'pieces';

        if (canViewPrices) {
            document.getElementById('edit-product-unit-price').value = activeRow.getAttribute('data-unit-price') || '0';
        }

        if (detailView) {
            detailView.hidden = true;
        }
        detailEdit.hidden = false;
        document.getElementById('product-detail-title').textContent = 'Edit product';
        document.getElementById('edit-product-name').focus();
    }

    function openDetail(row) {
        if (!modal || !row) {
            return;
        }

        activeRow = row;
        showDetailView();

        document.getElementById('product-detail-category').textContent = row.getAttribute('data-category') || '—';
        document.getElementById('product-detail-name').textContent = row.getAttribute('data-name') || '—';

        var description = row.getAttribute('data-description') || '';
        document.getElementById('product-detail-description').textContent = description !== '' ? description : '—';
        document.getElementById('product-detail-unit').textContent = row.getAttribute('data-unit') || '—';

        if (canViewPrices) {
            var price = row.getAttribute('data-price');
            document.getElementById('product-detail-price').textContent = price ? price + ' ETB' : '—';
        }

        modal.hidden = false;
        modal.classList.add('is-open');
    }

    function closeDetail() {
        if (!modal) {
            return;
        }

        modal.classList.remove('is-open');
        modal.hidden = true;
        activeRow = null;
        showDetailView();
    }

    rows.forEach(function (row) {
        row.addEventListener('click', function () { openDetail(row); });
        row.addEventListener('keydown', function (event) {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                openDetail(row);
            }
        });
    });

    if (modal) {
        modal.querySelectorAll('[data-product-detail-close]').forEach(function (btn) {
            btn.addEventListener('click', closeDetail);
        });
        modal.addEventListener('click', function (event) {
            if (event.target === modal) {
                closeDetail();
            }
        });
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && modal.classList.contains('is-open')) {
                if (detailEdit && !detailEdit.hidden) {
                    showDetailView();
                    return;
                }
                closeDetail();
            }
        });
    }

    var editBtn = document.getElementById('product-detail-edit-btn');
    var cancelEditBtn = document.getElementById('product-detail-cancel-edit');

    if (editBtn) {
        editBtn.addEventListener('click', showDetailEdit);
    }

    if (cancelEditBtn) {
        cancelEditBtn.addEventListener('click', showDetailView);
    }

    var addPanel = document.getElementById('add-product-panel');
    var toggleAdd = document.getElementById('toggle-add-product');
    var cancelAdd = document.getElementById('cancel-add-product');

    if (toggleAdd && addPanel) {
        toggleAdd.addEventListener('click', function () {
            addPanel.hidden = !addPanel.hidden;
            if (!addPanel.hidden) {
                document.getElementById('product-name')?.focus();
            }
        });
    }

    if (cancelAdd && addPanel) {
        cancelAdd.addEventListener('click', function () {
            addPanel.hidden = true;
        });
    }
})();
</script>
@endpush
