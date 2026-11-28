<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

final class MediaPath
{
    /**
     * @return list<string>
     */
    public static function disksToTry(): array
    {
        return ['public', 'barbin', 'local'];
    }

    public static function exists(string $relativePath): bool
    {
        $relativePath = ltrim(str_replace('\\', '/', $relativePath), '/');

        foreach (self::disksToTry() as $disk) {
            if (Storage::disk($disk)->exists($relativePath)) {
                return true;
            }
        }

        return false;
    }

    public static function absolute(string $relativePath): ?string
    {
        $relativePath = ltrim(str_replace('\\', '/', $relativePath), '/');

        foreach (self::disksToTry() as $disk) {
            if (Storage::disk($disk)->exists($relativePath)) {
                return Storage::disk($disk)->path($relativePath);
            }
        }

        return null;
    }

    public static function publicUrl(string $relativePath): ?string
    {
        $relativePath = ltrim(str_replace('\\', '/', $relativePath), '/');

        if (Storage::disk('public')->exists($relativePath)) {
            return Storage::disk('public')->url($relativePath);
        }

        return null;
    }

    public static function storeDisk(): string
    {
        return 'public';
    }
}