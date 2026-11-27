<?php

namespace App\Support;

final class NumericCell
{
    public static function toFloat(mixed $raw): ?float
    {
        if ($raw === null || $raw === '') {
            return null;
        }

        if (is_int($raw) || is_float($raw)) {
            return (float) $raw;
        }

        $value = trim((string) $raw);
        $value = str_replace([',', '،', ' ', "\u{00A0}", '٬'], '', $value);
        $value = strtr($value, [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
        ]);

        if ($value === '' || ! is_numeric($value)) {
            return null;
        }

        return (float) $value;
    }
}