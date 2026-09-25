<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class ProductCatalogService
{
    /**
     * @return Collection<int, Product>
     */
    public function all(): Collection
    {
        if (! Schema::hasTable('products')) {
            return collect();
        }

        return Product::query()->orderBy('category')->orderBy('name')->get();
    }

    /**
     * @param  Collection<int, Product>|null  $products
     * @return Collection<int, string>
     */
    public function categories(?Collection $products = null): Collection
    {
        return Product::catalogCategories($products ?? $this->all());
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): Product
    {
        return Product::query()->create($attributes);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(Product $product, array $attributes): Product
    {
        $product->update($attributes);

        return $product->refresh();
    }

    /**
     * Persist the live DB catalog to database/data/product_catalog.php for seeds and deploys.
     */
    public function syncExportFile(): void
    {
        if (! Schema::hasTable('products')) {
            return;
        }

        $path = database_path('data/product_catalog.php');
        $directory = dirname($path);

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $rows = $this->all()
            ->map(fn (Product $product): array => [
                'category' => $product->category,
                'name' => $product->name,
                'description' => $product->description,
                'unit' => $product->unit,
                'unit_price' => number_format((float) $product->unit_price, 2, '.', ''),
            ])
            ->values()
            ->all();

        $contents = "<?php\n\nreturn ".var_export($rows, true).";\n";

        file_put_contents($path, $contents);
    }
}
