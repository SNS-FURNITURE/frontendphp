<?php

namespace App\Support;

class UnitOfMeasure
{
    public const DEFAULT = 'pieces';

    public static function normalize(?string $unit): string
    {
        $value = strtolower(trim((string) $unit));

        if ($value === '' || in_array($value, ['pc', 'pcs', 'piece'], true)) {
            return self::DEFAULT;
        }

        return trim((string) $unit);
    }
}
