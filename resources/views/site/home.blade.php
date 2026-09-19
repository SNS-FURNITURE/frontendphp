@extends('layouts.marketing')

@section('title', 'SNS Furniture Manufacturing · Addis Ababa')
@section('meta_description', $company['subtitle'].' — '.$company['tagline'].'. Showrooms in Kality and Century Mall, Gurd Shola.')

@section('content')
<section class="hero">
    <div class="wrap hero-inner">
        <div class="eyebrow">Est. {{ $company['founded'] }} · Addis Ababa, Ethiopia</div>
        <h1>{{ $company['short_name'] }}</h1>
        <p>{{ $company['tagline'] }} — {{ $company['subtitle'] }}</p>
        <div class="hero-actions">
            <a class="btn btn-accent" href="{{ route('site.contact') }}">Visit showroom</a>
            <a class="btn btn-ghost" style="border-color:rgba(255,255,255,0.45);color:#fff" href="{{ route('site.products') }}">View products</a>
        </div>
    </div>
</section>

<section class="section" style="padding-bottom:0">
    <div class="wrap">
        <div class="stats">
            <div class="stat"><strong>{{ $company['experience'] }}</strong><span>Manufacturing legacy</span></div>
            <div class="stat"><strong>{{ $company['employees'] }}</strong><span>Skilled professionals</span></div>
            <div class="stat"><strong>{{ $company['machines_count'] }}</strong><span>Plant machines</span></div>
            <div class="stat"><strong>{{ $company['founded'] }}</strong><span>Year established</span></div>
        </div>
    </div>
</section>

<section class="section">
    <div class="wrap">
        <div class="section-head">
            <div>
                <h2>Featured collections</h2>
                <p class="muted" style="margin:0.35rem 0 0">Household, office, and institutional furniture from our workshops.</p>
            </div>
            <a class="btn btn-ghost" href="{{ route('site.products') }}">Browse all</a>
        </div>
        <div class="grid-products">
            @foreach ($featured as $product)
                <article class="product-card">
                    <a href="{{ route('site.product', $product['slug']) }}">
                        <img src="{{ $product['image'] }}" alt="{{ $product['name'] }}" loading="lazy" width="600" height="450">
                    </a>
                    <div class="body">
                        <h3><a href="{{ route('site.product', $product['slug']) }}">{{ $product['name'] }}</a></h3>
                        <p class="muted" style="margin:0;font-size:0.9rem">{{ $product['short'] }}</p>
                        <div class="price">{{ \App\Support\SiteCatalog::formatPrice($product['price']) }}</div>
                        <a class="btn btn-ghost" style="margin-top:auto;align-self:flex-start" href="{{ route('site.product', $product['slug']) }}">View details</a>
                    </div>
                </article>
            @endforeach
        </div>
    </div>
</section>

<section class="section" style="padding-top:0">
    <div class="wrap">
        <div class="section-head">
            <div>
                <h2>Why SNS</h2>
                <p class="muted" style="margin:0.35rem 0 0">Strengths from our official company profile.</p>
            </div>
            <a class="btn btn-ghost" href="{{ route('site.about') }}">Full profile</a>
        </div>
        <div class="strength-grid">
            @foreach ($company['strengths'] as $strength)
                <div class="strength-card">
                    <div class="badge">{{ $strength['badge'] }}</div>
                    <h3>{{ $strength['title'] }}</h3>
                    <p class="muted" style="margin:0">{{ $strength['description'] }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>

<section class="section" style="padding-top:0">
    <div class="wrap split">
        <div class="panel">
            <h3>Visit our showrooms</h3>
            <p class="muted">See and feel the craftsmanship in person.</p>
            <ul class="muted" style="padding-left:1.1rem">
                @foreach ($site['locations'] as $loc)
                    <li style="margin:0.45rem 0"><strong style="color:var(--ink)">{{ $loc['name'] }}</strong> — {{ $loc['address'] }}</li>
                @endforeach
            </ul>
            <p><a href="{{ $site['phone_href'] }}">{{ $site['phone'] }}</a></p>
            <a class="btn btn-primary" href="{{ route('site.contact') }}">Get directions</a>
        </div>
        <div class="panel">
            <h3>What customers say</h3>
            <p class="rating"><span class="stars">★★★★☆</span> {{ $site['rating'] }} Google rating</p>
            <p class="muted">Read recent reviews and share your visit experience.</p>
            <div style="display:flex;flex-wrap:wrap;gap:0.6rem;margin-top:1rem">
                <a class="btn btn-ghost" href="{{ $site['google_reviews'] }}" target="_blank" rel="noopener">Google reviews</a>
                <a class="btn btn-ghost" href="{{ $site['facebook'] }}" target="_blank" rel="noopener">Facebook</a>
            </div>
        </div>
    </div>
</section>
@endsection
