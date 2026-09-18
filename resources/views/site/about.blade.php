@extends('layouts.marketing')

@section('title', 'About · SNS Furniture Manufacturing')
@section('meta_description', config('site.about'))

@section('content')
<section class="section">
    <div class="wrap" style="max-width:760px">
        <h1 style="font-family:Fraunces,Georgia,serif;font-size:clamp(2rem,4vw,2.8rem);margin:0 0 1rem">About SNS Furniture</h1>
        <p style="font-size:1.1rem">{{ $site['about'] }}</p>
        <div class="panel" style="margin-top:1.5rem">
            <h3>Craftsmanship &amp; manufacturing</h3>
            <p class="muted">We design and manufacture furniture for modern Ethiopian homes and workplaces — balancing style, comfort, and durability from workshop to showroom floor.</p>
        </div>
        <div class="panel" style="margin-top:1rem">
            <h3>Our mission</h3>
            <p class="muted">To make high-quality furniture accessible in Addis Ababa through carefully made collections and welcoming showrooms in Kality and Gurd Shola–Century Mall.</p>
        </div>
        <div style="margin-top:1.5rem;display:flex;gap:0.65rem;flex-wrap:wrap">
            <a class="btn btn-primary" href="{{ route('site.products') }}">View products</a>
            <a class="btn btn-ghost" href="{{ route('site.contact') }}">Visit us</a>
        </div>
    </div>
</section>
@endsection
