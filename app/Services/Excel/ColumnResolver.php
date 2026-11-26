<?php

namespace App\Services\Excel;

use Illuminate\Support\Str;

class ColumnResolver
{
    /**
     * @param  array<int, string>  $headerRow
     * @param  list<string>  $candidates
     */
    public static function resolveIndex(array $headerRow, array $candidates): ?int
    {
        $normalizedHeaders = collect($headerRow)->map(fn ($h) => self::normalizeHeader($h));

        foreach ($candidates as $candidate) {
            $needle = self::normalizeHeader($candidate);
            $index = $normalizedHeaders->search($needle);
            if ($index !== false) {
                return (int) $index;
            }
        }

        foreach ($headerRow as $index => $header) {
            $h = self::normalizeHeader($header);
            foreach ($candidates as $candidate) {
                if (Str::contains($h, self::normalizeHeader($candidate))) {
                    return $index;
                }
            }
        }

        return null;
    }

    public static function normalizeHeader(mixed $value): string
    {
        return Str::lower(trim(preg_replace('/\s+/u', ' ', (string) $value) ?? ''));
    }
}