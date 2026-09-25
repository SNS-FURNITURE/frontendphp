<?php

namespace App\Support;

class CustomerIdentity
{
    public static function normalize(?string $value): string
    {
        $value = trim((string) $value);
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

        return mb_strtolower($value);
    }

    /**
     * @return array{name_key: string, address_key: string}
     */
    public static function keys(string $name, ?string $address): array
    {
        return [
            'name_key' => self::normalize($name),
            'address_key' => self::normalize($address),
        ];
    }
}
