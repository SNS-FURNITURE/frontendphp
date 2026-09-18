@extends('layouts.marketing')

@section('title', 'Locations & Contact · SNS Furniture')
@section('meta_description', 'Visit SNS Furniture showrooms in Kality and Gurd Shola–Century Mall, Addis Ababa. Call 090 028 2029.')

@section('content')
<section class="section">
    <div class="wrap">
        <div class="section-head">
            <div>
                <h2>Locations &amp; contact</h2>
                <p class="muted" style="margin:0.35rem 0 0">Two showrooms in Addis Ababa · {{ $site['phone'] }}</p>
            </div>
            <div class="rating"><span class="stars">★★★★☆</span> {{ $site['rating'] }} Google</div>
        </div>

        @if (session('status'))
            <div class="flash">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="errors">
                <ul style="margin:0;padding-left:1.1rem">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="split" style="margin-bottom:2rem">
            @foreach ($site['locations'] as $loc)
                <div class="panel">
                    <h3>{{ $loc['name'] }}</h3>
                    <p>{{ $loc['address'] }}</p>
                    <iframe class="map" title="Map {{ $loc['name'] }}" loading="lazy" referrerpolicy="no-referrer-when-downgrade" src="{{ $loc['map_embed'] }}"></iframe>
                </div>
            @endforeach
        </div>

        <div class="split">
            <div class="panel">
                <h3>Business hours</h3>
                <ul class="hours">
                    @foreach ($site['hours'] as $row)
                        <li><span>{{ $row['day'] }}</span><strong>{{ $row['hours'] }}</strong></li>
                    @endforeach
                </ul>
                <p style="margin-top:1rem"><a href="{{ $site['phone_href'] }}">{{ $site['phone'] }}</a></p>
                <div style="display:flex;flex-wrap:wrap;gap:0.55rem;margin-top:0.75rem">
                    <a class="btn btn-ghost" href="{{ $site['facebook'] }}" target="_blank" rel="noopener">Facebook page</a>
                    <a class="btn btn-ghost" href="{{ $site['google_reviews'] }}" target="_blank" rel="noopener">Google reviews</a>
                </div>
            </div>

            <div class="panel">
                <h3>Send a message</h3>
                <p class="muted">Ask about a product, showroom visit, or quote.</p>
                <form method="POST" action="{{ route('site.contact.submit') }}">
                    @csrf
                    <label>Name</label>
                    <input name="name" value="{{ old('name') }}" required>
                    <label>Phone</label>
                    <input name="phone" value="{{ old('phone') }}" required>
                    <label>Email (optional)</label>
                    <input type="email" name="email" value="{{ old('email') }}">
                    <label>Message</label>
                    <textarea name="message" required>{{ old('message', request('product') ? 'I am interested in: '.request('product') : '') }}</textarea>
                    <button class="btn btn-primary" type="submit" style="margin-top:1rem">Send inquiry</button>
                </form>
            </div>
        </div>
    </div>
</section>
@endsection
