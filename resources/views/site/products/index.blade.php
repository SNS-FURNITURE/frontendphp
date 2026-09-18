@extends('layouts.marketing')

@section('title', 'Products · SNS Furniture')
@section('meta_description', 'Browse SNS Furniture collections for living rooms, bedrooms, offices, and more in Addis Ababa.')

@section('content')
<section class="section">
    <div class="wrap">
        <div class="section-head">
            <div>
                <h2>Product catalog</h2>
                <p class="muted" style="margin:0.35rem 0 0">Display-only catalog — visit a showroom or contact us for availability and quotes.</p>
            </div>
        </div>

        <form method="GET" class="filters">
            <div>
                <label>Search</label>
                <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Sofa, desk…">
            </div>
            <div>
                <label>Category</label>
                <select name="category">
                    <option value="">All</option>
                    @foreach ($site['categories'] as $key => $label)
                        <option value="{{ $key }}" @selected(($filters['category'] ?? '') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label>Room</label>
                <select name="room">
                    <option value="">All</option>
                    @foreach ($site['categories'] as $key => $label)
                        <option value="{{ $key }}" @selected(($filters['room'] ?? '') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label>Material</label>
                <select name="material">
                    <option value="">All</option>
                    @foreach ($site['materials'] as $key => $label)
                        <option value="{{ $key }}" @selected(($filters['material'] ?? '') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label>Min price</label>
                <input type="number" name="min" value="{{ $filters['min'] ?? '' }}" min="0" step="100">
            </div>
            <div>
                <label>Max price</label>
                <input type="number" name="max" value="{{ $filters['max'] ?? '' }}" min="0" step="100">
            </div>
            <div style="display:flex;align-items:end">
                <button class="btn btn-primary" type="submit" style="width:100%">Filter</button>
            </div>
        </form>

        @if (count($products) === 0)
            <p class="muted">No products match these filters.</p>
        @else
            <div class="grid-products">
                @foreach ($products as $product)
                    <article class="product-card">
                        <a href="{{ route('site.product', $product['slug']) }}">
                            <img src="{{ $product['image'] }}" alt="{{ $product['name'] }}" loading="lazy" width="600" height="450">
                        </a>
                        <div class="body">
                            <h3><a href="{{ route('site.product', $product['slug']) }}">{{ $product['name'] }}</a></h3>
                            <p class="muted" style="margin:0;font-size:0.9rem">{{ $site['categories'][$product['category']] ?? '' }} · {{ $site['materials'][$product['material']] ?? '' }}</p>
                            <div class="price">{{ \App\Support\SiteCatalog::formatPrice($product['price']) }}</div>
                            <a class="btn btn-ghost" style="margin-top:auto;align-self:flex-start" href="{{ route('site.product', $product['slug']) }}">View details</a>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </div>
</section>
@endsection
