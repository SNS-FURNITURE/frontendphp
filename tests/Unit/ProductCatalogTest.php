<?php

namespace Tests\Unit;

use App\Models\Product;
use Tests\TestCase;

class ProductCatalogTest extends TestCase
{
    public function test_catalog_categories_are_unique_sorted_and_non_empty(): void
    {
        $products = collect([
            Product::make(['category' => 'Bench', 'name' => 'B3']),
            Product::make(['category' => 'Beds', 'name' => 'Carter']),
            Product::make(['category' => 'Beds', 'name' => 'LUKAS']),
            Product::make(['category' => '  ', 'name' => 'Blank']),
            Product::make(['category' => 'Chairs', 'name' => 'C1']),
        ]);

        $categories = Product::catalogCategories($products);

        $this->assertSame(['Beds', 'Bench', 'Chairs'], $categories->all());
    }

    public function test_fillable_matches_catalog_columns(): void
    {
        $product = new Product;

        $this->assertSame(
            ['category', 'name', 'description', 'unit', 'unit_price'],
            $product->getFillable(),
        );
        $this->assertFalse($product->usesTimestamps());
    }
}
