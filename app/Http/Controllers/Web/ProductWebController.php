<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\ProductCatalogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class ProductWebController extends Controller
{
    public function __construct(private ProductCatalogService $catalog) {}

    public function index(Request $request): View
    {
        abort_unless(auth()->user()?->canViewProducts(), 403);

        $category = trim((string) $request->query('category', ''));
        $search = trim((string) $request->query('q', ''));

        $catalogProducts = collect();
        $products = collect();
        $categories = collect();

        if (Schema::hasTable('products')) {
            $catalogProducts = $this->catalog->all();
            $categories = $this->catalog->categories($catalogProducts);

            if ($category !== '' && ! $categories->contains($category)) {
                $category = '';
            }

            $products = $this->filterCatalog($catalogProducts, $category);
        }

        return view('products.index', [
            'products' => $products,
            'categories' => $categories,
            'category' => $category,
            'search' => $search,
            'canViewPrices' => auth()->user()?->canViewInvoicePrices() ?? false,
            'canManage' => auth()->user()?->canManageProducts() ?? false,
            'units' => ['pieces', 'Set', 'm2'],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()?->canManageProducts(), 403);
        abort_unless(Schema::hasTable('products'), 503);

        $validated = $this->validatedProduct($request);
        $attributes = $this->productAttributes($validated);

        $this->catalog->create($attributes);

        return redirect()
            ->route('products.index', array_filter([
                'category' => $attributes['category'],
            ]))
            ->with('status', 'Product "'.$attributes['name'].'" added.');
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        abort_unless(auth()->user()?->canManageProducts(), 403);
        abort_unless(Schema::hasTable('products'), 503);

        $validated = $this->validatedProduct($request);
        $attributes = $this->productAttributes($validated);

        $this->catalog->update($product, $attributes);

        return redirect()
            ->route('products.index', array_filter([
                'category' => $attributes['category'],
                'q' => trim((string) $request->query('q', '')),
            ]))
            ->with('status', 'Product "'.$attributes['name'].'" updated.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedProduct(Request $request): array
    {
        return $request->validate([
            'category' => ['required', 'string', 'max:100'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'unit' => ['required', 'string', 'max:50'],
            'unit_price' => ['required', 'numeric', 'min:0'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function productAttributes(array $validated): array
    {
        return [
            'category' => trim($validated['category']),
            'name' => trim($validated['name']),
            'description' => filled($validated['description'] ?? null) ? trim($validated['description']) : null,
            'unit' => $validated['unit'],
            'unit_price' => $validated['unit_price'],
        ];
    }

    /**
     * @param  Collection<int, Product>  $catalogProducts
     * @return Collection<int, Product>
     */
    private function filterCatalog(Collection $catalogProducts, string $category): Collection
    {
        return $catalogProducts
            ->filter(function (Product $product) use ($category) {
                $productCategory = trim((string) $product->category);

                if ($productCategory === '') {
                    return false;
                }

                if ($category !== '' && $productCategory !== $category) {
                    return false;
                }

                return true;
            })
            ->values();
    }
}
