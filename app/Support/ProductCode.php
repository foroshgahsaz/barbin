<?php

namespace App\Support;

final class ProductCode
{
    public static function normalize(mixed $raw): ?string
    {
        if ($raw === null) {
            return null;
        }

        if (is_float($raw) || is_int($raw)) {
            if (is_float($raw) && floor($raw) !== $raw) {
                return null;
            }

            $raw = (string) (int) $raw;
        }

        $code = trim((string) $raw);

        if ($code === '') {
            return null;
        }

        return $code;
    }
}