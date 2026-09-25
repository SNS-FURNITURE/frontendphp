<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class ProductCatalogSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('products')) {
            $this->command?->warn('products table missing — run migrations first.');

            return;
        }

        $catalog = database_path('data/product_catalog.php');
        if (! is_file($catalog)) {
            $this->command?->warn('product_catalog.php not found.');

            return;
        }

        /** @var array<int, array{category:string,name:string,description:?string,unit:string,unit_price:string|float}> $rows */
        $rows = require $catalog;

        Product::withoutEvents(function () use ($rows): void {
            foreach ($rows as $row) {
                Product::query()->updateOrCreate(
                    [
                        'category' => $row['category'],
                        'name' => $row['name'],
                        'description' => $row['description'] ?? null,
                    ],
                    [
                        'unit' => $row['unit'] ?? 'pieces',
                        'unit_price' => $row['unit_price'],
                    ]
                );
            }
        });

        $this->command?->info('Synced '.count($rows).' catalog products.');
    }
}
