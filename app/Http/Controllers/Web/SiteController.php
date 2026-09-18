<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Support\SiteCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SiteController extends Controller
{
    public function home(): View
    {
        return view('site.home', [
            'featured' => SiteCatalog::featured(),
            'site' => config('site'),
        ]);
    }

    public function products(Request $request): View
    {
        $filters = [
            'q' => $request->query('q'),
            'category' => $request->query('category'),
            'room' => $request->query('room'),
            'material' => $request->query('material'),
            'min' => $request->query('min'),
            'max' => $request->query('max'),
        ];

        return view('site.products.index', [
            'products' => SiteCatalog::filter($filters),
            'filters' => $filters,
            'site' => config('site'),
        ]);
    }

    public function product(string $slug): View
    {
        $product = SiteCatalog::find($slug);
        abort_unless($product !== null, 404);

        return view('site.products.show', [
            'product' => $product,
            'site' => config('site'),
        ]);
    }

    public function about(): View
    {
        return view('site.about', ['site' => config('site')]);
    }

    public function contact(): View
    {
        return view('site.contact', ['site' => config('site')]);
    }

    public function contactSubmit(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'phone' => ['required', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:120'],
            'message' => ['required', 'string', 'min:10', 'max:2000'],
        ]);

        // Inquiry-only catalog — log for follow-up (no payment/cart).
        logger()->info('site.contact_inquiry', $validated);

        return back()->with('status', 'Thank you — we received your message and will contact you soon.');
    }
}
