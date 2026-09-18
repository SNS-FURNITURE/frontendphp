{!! '<?xml version="1.0" encoding="UTF-8"?>' !!}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url><loc>{{ url('/') }}</loc></url>
    <url><loc>{{ url('/products') }}</loc></url>
    <url><loc>{{ url('/about') }}</loc></url>
    <url><loc>{{ url('/contact') }}</loc></url>
    @foreach (config('site.products', []) as $product)
        <url><loc>{{ url('/products/'.$product['slug']) }}</loc></url>
    @endforeach
</urlset>
