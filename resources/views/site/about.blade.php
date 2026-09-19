@extends('layouts.marketing')

@section('title', 'About · SNS Furniture Manufacturing')
@section('meta_description', $company['subtitle'].' '.$company['tagline'].'.')

@section('content')
<section class="section">
    <div class="wrap">
        <div style="max-width:760px;margin-bottom:2.5rem">
            <h1 style="font-family:Fraunces,Georgia,serif;font-size:clamp(2rem,4vw,2.8rem);margin:0 0 0.75rem">About SNS Furniture</h1>
            <p style="font-size:1.1rem;margin:0 0 0.75rem">{{ $company['subtitle'] }}</p>
            <p class="muted" style="margin:0">{{ $site['about'] }} Located {{ $company['location'] }} ({{ $company['altitude'] }}).</p>
        </div>

        <div class="stats" style="margin-bottom:2.5rem">
            <div class="stat"><strong>{{ $company['founded'] }}</strong><span>Founded</span></div>
            <div class="stat"><strong>{{ $company['employees'] }}</strong><span>Employees</span></div>
            <div class="stat"><strong>{{ $company['capital'] }}</strong><span>Capital</span></div>
            <div class="stat"><strong>{{ $company['machines_count'] }}</strong><span>Machines</span></div>
        </div>

        <div class="quote-panel" style="margin-bottom:2.5rem">
            <blockquote>“{{ $company['ceo']['quote'] }}”</blockquote>
            <p style="margin:0 0 1rem;opacity:0.9">{{ $company['ceo']['author'] }} · {{ $company['ceo']['role'] }}</p>
            @foreach ($company['ceo']['body'] as $para)
                <p style="margin:0 0 0.75rem;opacity:0.92;max-width:52rem">{{ $para }}</p>
            @endforeach
        </div>

        <div class="split" style="margin-bottom:2.5rem">
            <div class="panel">
                <h3>Mission</h3>
                <p class="muted" style="margin:0">{{ $company['mission'] }}</p>
            </div>
            <div class="panel">
                <h3>Vision</h3>
                <p class="muted" style="margin:0">{{ $company['vision'] }}</p>
            </div>
        </div>

        <div class="panel" style="margin-bottom:2.5rem">
            <h3>Objective</h3>
            <p class="muted" style="margin:0">{{ $company['objective'] }}</p>
        </div>

        <div style="margin-bottom:2.5rem">
            <div class="section-head">
                <h2>Our journey</h2>
            </div>
            <div class="timeline">
                @foreach ($company['timeline'] as $item)
                    <div class="timeline-item">
                        <div>
                            <div class="year">{{ $item['year'] }}</div>
                            <div class="muted" style="font-size:0.82rem">{{ $item['capital'] }}</div>
                        </div>
                        <div>
                            <h3 style="margin:0 0 0.35rem;font-family:Fraunces,Georgia,serif">{{ $item['title'] }}</h3>
                            <p class="muted" style="margin:0">{{ $item['description'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div style="margin-bottom:2.5rem">
            <div class="section-head">
                <h2>Core values</h2>
            </div>
            <div class="value-grid">
                @foreach ($company['values'] as $value)
                    <div class="value-card">
                        <h3>{{ $value['title'] }}</h3>
                        <p class="muted" style="margin:0">{{ $value['description'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="split" style="margin-bottom:2.5rem">
            <div>
                <div class="section-head"><h2>Workshops</h2></div>
                <div class="value-grid">
                    @foreach ($company['workshops'] as $shop)
                        <div class="value-card">
                            <h3>{{ $shop['name'] }}</h3>
                            <p class="muted" style="margin:0">{{ $shop['desc'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
            <div>
                <div class="section-head"><h2>Trusted by</h2></div>
                <div class="value-grid">
                    @foreach ($company['client_sectors'] as $sector)
                        <div class="value-card">
                            <h3>{{ $sector['category'] }}</h3>
                            <p class="muted" style="margin:0">{{ implode(' · ', $sector['clients']) }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div style="margin-bottom:1.5rem">
            <div class="section-head"><h2>Product lines</h2></div>
            <div class="value-grid">
                @foreach ($company['product_lines'] as $line)
                    <div class="value-card">
                        <h3>{{ $line['title'] }}</h3>
                        <p class="muted" style="margin:0">{{ $line['materials'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>

        <div style="display:flex;gap:0.65rem;flex-wrap:wrap">
            <a class="btn btn-primary" href="{{ route('site.products') }}">View products</a>
            <a class="btn btn-accent" href="{{ route('site.contact') }}">Visit us</a>
        </div>
    </div>
</section>
@endsection
