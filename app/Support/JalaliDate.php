<?php

namespace App\Support;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Morilog\Jalali\Jalalian;

final class JalaliDate
{
    public static function format(mixed $date, string $pattern = 'Y/m/d H:i', bool $persianDigits = true): string
    {
        if ($date === null || $date === '') {
            return '—';
        }

        if (! $date instanceof CarbonInterface) {
            try {
                $date = Carbon::parse($date);
            } catch (\Throwable) {
                return '—';
            }
        }

        $formatted = Jalalian::fromDateTime($date)->format($pattern);

        return $persianDigits ? self::toPersianDigits($formatted) : $formatted;
    }

    public static function formatGregorianYearMonth(string $yearMonth): string
    {
        try {
            $carbon = Carbon::createFromFormat('Y-m', $yearMonth)->startOfMonth();
        } catch (\Throwable) {
            return $yearMonth;
        }

        return self::format($carbon, 'Y/m', persianDigits: true);
    }

    public static function toPersianDigits(string $value): string
    {
        return str_replace(
            ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'],
            ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'],
            $value,
        );
    }
}