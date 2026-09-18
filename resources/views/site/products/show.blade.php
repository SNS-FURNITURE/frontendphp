@extends('layouts.marketing')

@section('title', $product['name'].' · SNS Furniture')
@section('meta_description', $product['short'])

@section('content')
<section class="section">
    <div class="wrap split">
        <div class="gallery">
            <div class="gallery-main">
                <img src="{{ $product['image'] }}" alt="{{ $product['name'] }}" loading="eager" width="1200" height="825">
            </div>
            @if (! empty($product['gallery']))
                <div class="gallery-thumbs">
                    @foreach ($product['gallery'] as $img)
                        <img src="{{ $img }}" alt="" loading="lazy" width="176" height="132">
                    @endforeach
                </div>
            @endif
        </div>
        <div>
            <p class="muted" style="margin:0 0 0.35rem">{{ $site['categories'][$product['category']] ?? '' }}</p>
            <h1 style="font-family:Fraunces,Georgia,serif;font-size:clamp(1.8rem,3vw,2.4rem);margin:0 0 0.65rem">{{ $product['name'] }}</h1>
            <p class="price" style="font-size:1.25rem;margin:0 0 1rem">{{ \App\Support\SiteCatalog::formatPrice($product['price']) }}</p>
            <p>{{ $product['description'] }}</p>
            <div class="panel" style="margin:1.25rem 0">
                <p style="margin:0 0 0.55rem"><strong>Dimensions</strong><br><span class="muted">{{ $product['dimensions'] }}</span></p>
                <p style="margin:0"><strong>Materials</strong><br><span class="muted">{{ $product['materials_detail'] }}</span></p>
            </div>
            <div style="display:flex;flex-wrap:wrap;gap:0.65rem">
                <a class="btn btn-primary" href="{{ route('site.contact', ['product' => $product['name']]) }}">Request quote</a>
                <a class="btn btn-ghost" href="{{ route('site.products') }}">Back to catalog</a>
            </div>
            <p class="muted" style="margin-top:1rem;font-size:0.88rem">Catalog is for display. Confirm stock and finish options at our Kality or Gurd Shola showrooms.</p>
        </div>
    </div>
</section>
@endsection
