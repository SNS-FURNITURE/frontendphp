<?php

namespace App\Support;

class SiteCatalog
{
    /**
     * @return list<array<string, mixed>>
     */
    public static function products(): array
    {
        return config('site.products', []);
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function find(string $slug): ?array
    {
        foreach (self::products() as $product) {
            if (($product['slug'] ?? '') === $slug) {
                return $product;
            }
        }

        return null;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function featured(): array
    {
        return array_values(array_filter(self::products(), fn (array $p) => ! empty($p['featured'])));
    }

    /**
     * @param  array{category?: string, room?: string, material?: string, q?: string, min?: float|null, max?: float|null}  $filters
     * @return list<array<string, mixed>>
     */
    public static function filter(array $filters): array
    {
        $q = strtolower(trim((string) ($filters['q'] ?? '')));
        $category = (string) ($filters['category'] ?? '');
        $room = (string) ($filters['room'] ?? '');
        $material = (string) ($filters['material'] ?? '');
        $min = $filters['min'] !== null && $filters['min'] !== '' ? (float) $filters['min'] : null;
        $max = $filters['max'] !== null && $filters['max'] !== '' ? (float) $filters['max'] : null;

        return array_values(array_filter(self::products(), function (array $p) use ($q, $category, $room, $material, $min, $max) {
            if ($category !== '' && ($p['category'] ?? '') !== $category) {
                return false;
            }
            if ($room !== '' && ($p['room'] ?? '') !== $room) {
                return false;
            }
            if ($material !== '' && ($p['material'] ?? '') !== $material) {
                return false;
            }
            if ($min !== null && (float) $p['price'] < $min) {
                return false;
            }
            if ($max !== null && (float) $p['price'] > $max) {
                return false;
            }
            if ($q !== '') {
                $hay = strtolower(($p['name'] ?? '').' '.($p['short'] ?? '').' '.($p['description'] ?? ''));
                if (! str_contains($hay, $q)) {
                    return false;
                }
            }

            return true;
        }));
    }

    public static function formatPrice(float|int $price): string
    {
        return 'ETB '.number_format((float) $price, 0);
    }
}
