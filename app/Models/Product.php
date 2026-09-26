<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class Product extends Model
{
    protected $table = 'products';

    public $timestamps = false;

    protected $fillable = [
        'category',
        'name',
        'description',
        'unit',
        'unit_price',
    ];

    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:2',
        ];
    }

    /**
     * @return Collection<int, Product>
     */
    public static function catalog(): Collection
    {
        if (! Schema::hasTable('products')) {
            return collect();
        }

        return static::query()
            ->orderBy('category')
            ->orderBy('name')
            ->get();
    }

    /**
     * @param  Collection<int, Product>|null  $products
     * @return Collection<int, string>
     */
    public static function catalogCategories(?Collection $products = null): Collection
    {
        return ($products ?? static::catalog())
            ->pluck('category')
            ->map(fn ($category): string => trim((string) $category))
            ->filter()
            ->unique()
            ->sort()
            ->values();
    }
}
