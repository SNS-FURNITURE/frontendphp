<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', config('site.short_name'))</title>
    <meta name="description" content="@yield('meta_description', config('site.about'))">
    <meta name="keywords" content="SNS Furniture, furniture Addis Ababa, furniture Gurd Shola, furniture Kality, home furniture Ethiopia">
    <link rel="canonical" href="{{ url()->current() }}">
    <meta property="og:title" content="@yield('title', config('site.short_name'))">
    <meta property="og:description" content="@yield('meta_description', config('site.about'))">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,700&family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #0d0b21;
            --ink: #f4f2ff;
            --muted: #9b94b8;
            --wood: #a78bfa;
            --wood-deep: #6c5ce7;
            --stone: #1a1634;
            --line: #2a2550;
            --card: #16132e;
            --accent: #a3e635;
            --header: rgba(16, 14, 36, 0.92);
            --footer: #100e24;
            --input-bg: #0f0d1f;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: Outfit, system-ui, sans-serif;
            color: var(--ink);
            background: var(--bg);
            line-height: 1.55;
        }
        a { color: var(--wood); text-decoration: none; }
        a:hover { text-decoration: underline; }
        img { max-width: 100%; display: block; }
        .wrap { width: min(1120px, calc(100% - 2rem)); margin-inline: auto; }
        .site-header {
            position: sticky; top: 0; z-index: 40;
            background: var(--header);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid var(--line);
        }
        .nav {
            display: flex; align-items: center; justify-content: space-between;
            gap: 1rem; padding: 0.9rem 0;
        }
        .brand {
            display: flex; align-items: center; gap: 0.65rem; color: var(--ink); text-decoration: none; font-weight: 700;
        }
        .brand img { height: 40px; width: auto; border-radius: 8px; background: #fff; }
        .brand span { font-family: Fraunces, Georgia, serif; font-size: 1.15rem; color: #ff6b6b; }
        .nav-links { display: flex; gap: 1.1rem; align-items: center; flex-wrap: wrap; }
        .nav-links a { color: #d8d2f0; font-weight: 500; font-size: 0.95rem; text-decoration: none; }
        .nav-links a:hover, .nav-links a.active { color: #fff; }
        .btn {
            display: inline-flex; align-items: center; justify-content: center; gap: 0.4rem;
            border: 0; border-radius: 999px; padding: 0.7rem 1.2rem; font-weight: 700; cursor: pointer;
            font-size: 0.92rem; text-decoration: none; font-family: inherit;
        }
        .btn-primary { background: var(--wood-deep); color: #fff; }
        .btn-primary:hover { background: var(--wood); text-decoration: none; color: #120f24; }
        .btn-ghost { background: transparent; color: var(--ink); border: 1px solid var(--line); }
        .btn-ghost:hover { border-color: var(--wood); color: var(--wood); text-decoration: none; }
        .btn-accent { background: var(--accent); color: #142010; }
        .btn-accent:hover { filter: brightness(1.05); text-decoration: none; color: #142010; }
        .hero {
            position: relative; min-height: min(88vh, 760px);
            display: grid; align-items: end;
            background:
                linear-gradient(180deg, rgba(13,11,33,0.35), rgba(13,11,33,0.88)),
                url('https://images.unsplash.com/photo-1618221195710-dd6b41faaea6?auto=format&fit=crop&w=1800&q=80') center/cover no-repeat;
            color: #fff;
        }
        .hero-inner { padding: 4.5rem 0 3.5rem; }
        .hero .eyebrow { letter-spacing: 0.16em; text-transform: uppercase; font-size: 0.72rem; opacity: 0.85; margin-bottom: 0.75rem; }
        .hero h1 {
            font-family: Fraunces, Georgia, serif; font-size: clamp(2.4rem, 6vw, 4.2rem);
            line-height: 1.05; margin: 0 0 0.85rem; max-width: 14ch; font-weight: 700;
        }
        .hero p { max-width: 36rem; margin: 0 0 1.5rem; font-size: 1.08rem; opacity: 0.92; }
        .hero-actions { display: flex; flex-wrap: wrap; gap: 0.75rem; }
        .section { padding: 4rem 0; }
        .section-head { display: flex; justify-content: space-between; gap: 1rem; align-items: end; margin-bottom: 1.75rem; flex-wrap: wrap; }
        .section-head h2 { font-family: Fraunces, Georgia, serif; font-size: clamp(1.7rem, 3vw, 2.3rem); margin: 0; color: var(--ink); }
        .muted { color: var(--muted); }
        .grid-products {
            display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 1.25rem;
        }
        .product-card {
            background: var(--card); border: 1px solid var(--line); border-radius: 18px; overflow: hidden;
            display: flex; flex-direction: column;
        }
        .product-card img { aspect-ratio: 4/3; object-fit: cover; width: 100%; background: var(--stone); }
        .product-card .body { padding: 1rem 1.05rem 1.15rem; display: flex; flex-direction: column; gap: 0.35rem; flex: 1; }
        .product-card h3 { margin: 0; font-size: 1.05rem; font-family: Fraunces, Georgia, serif; color: var(--ink); }
        .product-card h3 a { color: inherit; }
        .price { font-weight: 700; color: var(--accent); }
        .filters {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 0.75rem;
            margin-bottom: 1.5rem; padding: 1rem; background: var(--card); border: 1px solid var(--line); border-radius: 16px;
        }
        .filters label { display: block; font-size: 0.72rem; font-weight: 700; margin-bottom: 0.25rem; color: var(--muted); text-transform: uppercase; letter-spacing: 0.04em; }
        .filters input, .filters select {
            width: 100%; border: 1px solid var(--line); border-radius: 10px; padding: 0.55rem 0.65rem;
            background: var(--input-bg); color: var(--ink); font: inherit;
        }
        .gallery { display: grid; gap: 0.75rem; }
        .gallery-main { border-radius: 18px; overflow: hidden; border: 1px solid var(--line); }
        .gallery-main img { width: 100%; aspect-ratio: 16/11; object-fit: cover; }
        .gallery-thumbs { display: flex; gap: 0.5rem; flex-wrap: wrap; }
        .gallery-thumbs img { width: 88px; height: 66px; object-fit: cover; border-radius: 10px; border: 1px solid var(--line); }
        .split { display: grid; grid-template-columns: 1.1fr 0.9fr; gap: 2rem; align-items: start; }
        .panel {
            background: var(--card); border: 1px solid var(--line); border-radius: 18px; padding: 1.25rem;
        }
        .panel h3 { margin-top: 0; font-family: Fraunces, Georgia, serif; color: var(--ink); }
        .map { width: 100%; height: 240px; border: 0; border-radius: 14px; }
        .hours { list-style: none; padding: 0; margin: 0; }
        .hours li { display: flex; justify-content: space-between; gap: 1rem; padding: 0.45rem 0; border-bottom: 1px solid var(--line); color: var(--ink); }
        .flash { background: #14352a; color: #8dffc1; padding: 0.75rem 1rem; border-radius: 12px; margin-bottom: 1rem; border: 1px solid #1f5a44; }
        .errors { background: #3a1515; color: #ffb4b4; padding: 0.75rem 1rem; border-radius: 12px; margin-bottom: 1rem; border: 1px solid #6b2a2a; }
        form label { display: block; font-size: 0.8rem; font-weight: 600; margin: 0.65rem 0 0.25rem; color: var(--ink); }
        form input, form textarea {
            width: 100%; border: 1px solid var(--line); border-radius: 10px; padding: 0.65rem 0.75rem;
            background: var(--input-bg); color: var(--ink); font: inherit;
        }
        form textarea { min-height: 120px; resize: vertical; }
        .site-footer {
            border-top: 1px solid var(--line); padding: 2.5rem 0 2rem; margin-top: 2rem; background: var(--footer);
        }
        .footer-grid { display: grid; grid-template-columns: 1.4fr 1fr 1fr; gap: 1.5rem; }
        .footer-grid h4 { margin: 0 0 0.6rem; font-family: Fraunces, Georgia, serif; color: var(--ink); }
        .footer-grid ul { list-style: none; padding: 0; margin: 0; }
        .footer-grid li { margin: 0.35rem 0; color: var(--muted); }
        .footer-grid a { color: #d8d2f0; }
        .rating { display: inline-flex; align-items: center; gap: 0.35rem; font-weight: 600; color: var(--ink); }
        .stars { color: var(--accent); letter-spacing: 0.05em; }
        @media (max-width: 860px) {
            .split, .footer-grid { grid-template-columns: 1fr; }
            .nav-links { gap: 0.75rem; font-size: 0.88rem; }
            .hero-inner { padding: 5rem 0 2.5rem; }
        }
    </style>
    @stack('styles')
</head>
<body>
@php $site = $site ?? config('site'); @endphp
<header class="site-header">
    <div class="wrap nav">
        <a class="brand" href="{{ route('site.home') }}">
            <img src="{{ asset('sns-logo.png') }}" alt="SNS Furniture">
            <span>SNS Furniture</span>
        </a>
        <nav class="nav-links" aria-label="Primary">
            <a href="{{ route('site.home') }}" class="{{ request()->routeIs('site.home') ? 'active' : '' }}">Home</a>
            <a href="{{ route('site.products') }}" class="{{ request()->routeIs('site.products*') ? 'active' : '' }}">Products</a>
            <a href="{{ route('site.about') }}" class="{{ request()->routeIs('site.about') ? 'active' : '' }}">About</a>
            <a href="{{ route('site.contact') }}" class="{{ request()->routeIs('site.contact') ? 'active' : '' }}">Locations</a>
            <a class="btn btn-primary" href="{{ route('site.contact') }}">Visit showroom</a>
        </nav>
    </div>
</header>

<main>
    @yield('content')
</main>

<footer class="site-footer">
    <div class="wrap footer-grid">
        <div>
            <h4>{{ $site['short_name'] }}</h4>
            <p class="muted">{{ $site['about'] }}</p>
            <p class="rating"><span class="stars">★★★★☆</span> {{ $site['rating'] }} on Google</p>
        </div>
        <div>
            <h4>Visit</h4>
            <ul>
                @foreach ($site['locations'] as $loc)
                    <li>{{ $loc['name'] }} — {{ $loc['address'] }}</li>
                @endforeach
                <li><a href="{{ $site['phone_href'] }}">{{ $site['phone'] }}</a></li>
            </ul>
        </div>
        <div>
            <h4>Connect</h4>
            <ul>
                <li><a href="{{ $site['facebook'] }}" target="_blank" rel="noopener">Facebook</a></li>
                <li><a href="{{ $site['google_reviews'] }}" target="_blank" rel="noopener">Google reviews</a></li>
                <li><a href="{{ route('site.products') }}">Product catalog</a></li>
                <li><a href="{{ route('site.contact') }}">Contact</a></li>
            </ul>
        </div>
    </div>
    <div class="wrap muted" style="margin-top:1.5rem;font-size:0.85rem">
        © {{ date('Y') }} {{ $site['brand'] }}. Furniture manufacturer &amp; retailer · Addis Ababa.
    </div>
</footer>
@stack('scripts')
</body>
</html>
