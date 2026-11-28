<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use RuntimeException;

final class StoragePaths
{
    /**
     * @return list<string>
     */
    private static function disksToTry(): array
    {
        return array_values(array_unique([
            BarbinStorage::diskName(),
            'local',
            'barbin',
        ]));
    }

    public static function ensureDirectory(string $relativeDirectory, ?string $diskName = null): string
    {
        $relativeDirectory = trim(str_replace('\\', '/', $relativeDirectory), '/');
        if ($relativeDirectory === '') {
            return $diskName ?? BarbinStorage::diskName();
        }

        $disks = $diskName !== null ? [$diskName] : self::disksToTry();

        foreach ($disks as $name) {
            if (self::mkdirOnDisk($name, $relativeDirectory)) {
                return $name;
            }
        }

        $hints = StorageDiagnostics::formatForConsole(StorageDiagnostics::run());

        throw new RuntimeException(
            "ایجاد پوشه ذخیره‌سازی ممکن نشد. در ترمینال پاد اجرا کنید:\nphp artisan barbin:storage-diagnose\n\n".$hints,
        );
    }

    public static function putGeneratedFile(string $relativePath, string $binary, ?string $diskName = null): string
    {
        $relativePath = str_replace('\\', '/', $relativePath);
        $dir = dirname($relativePath);
        $disk = $diskName ?? self::ensureDirectory($dir === '.' ? '' : $dir);

        if (Storage::disk($disk)->put($relativePath, $binary)) {
            return $disk;
        }

        $absolute = Storage::disk($disk)->path($relativePath);
        if (@file_put_contents($absolute, $binary) !== false) {
            return $disk;
        }

        throw new RuntimeException(
            'نوشتن فایل ممکن نشد — مسیر: '.$absolute.' — php artisan barbin:storage-diagnose',
        );
    }

    public static function touchStorageTree(): void
    {
        $dirs = [
            'sales-pdfs',
            'sales-exports',
            'sales-uploads',
            'inventory-uploads',
            'product-images',
            'private',
            'tmp',
            'tmp/mpdf',
        ];

        foreach (self::disksToTry() as $diskName) {
            foreach ($dirs as $dir) {
                try {
                    self::mkdirOnDisk($diskName, $dir);
                } catch (\Throwable) {
                    // try next disk
                }
            }
        }

        $privateRoot = (string) config('filesystems.disks.local.root');
        if ($privateRoot !== '' && ! is_dir($privateRoot)) {
            self::ensureAbsoluteDirectory($privateRoot);
        }
    }

    public static function absolutePath(string $relativePath, ?string $diskName = null): string
    {
        $diskName ??= BarbinStorage::diskName();

        if (Storage::disk($diskName)->exists($relativePath)) {
            return Storage::disk($diskName)->path($relativePath);
        }

        foreach (self::disksToTry() as $name) {
            if (Storage::disk($name)->exists($relativePath)) {
                return Storage::disk($name)->path($relativePath);
            }
        }

        return Storage::disk($diskName)->path($relativePath);
    }

    public static function exists(string $relativePath): bool
    {
        foreach (self::disksToTry() as $name) {
            if (Storage::disk($name)->exists($relativePath)) {
                return true;
            }
        }

        return false;
    }

    public static function downloadResponse(string $relativePath, string $filename)
    {
        foreach (self::disksToTry() as $name) {
            if (Storage::disk($name)->exists($relativePath)) {
                return Storage::disk($name)->download($relativePath, $filename);
            }
        }

        abort(404);
    }

    private static function mkdirOnDisk(string $diskName, string $relativeDirectory): bool
    {
        if ($relativeDirectory === '') {
            return self::diskRootWritable($diskName);
        }

        try {
            $disk = Storage::disk($diskName);
        } catch (\Throwable) {
            return false;
        }

        if (! self::diskRootWritable($diskName)) {
            return false;
        }

        try {
            $absolute = $disk->path($relativeDirectory);
        } catch (\Throwable) {
            return false;
        }

        if (is_dir($absolute)) {
            return is_writable($absolute);
        }

        if ($disk->makeDirectory($relativeDirectory)) {
            return is_writable($absolute) || is_dir($absolute);
        }

        if (self::ensureAbsoluteDirectory($absolute)) {
            return is_writable($absolute);
        }

        return false;
    }

    private static function diskRootWritable(string $diskName): bool
    {
        try {
            $root = Storage::disk($diskName)->path('');
        } catch (\Throwable) {
            return false;
        }

        $root = rtrim($root, DIRECTORY_SEPARATOR);
        if (! is_dir($root)) {
            if (! self::ensureAbsoluteDirectory($root)) {
                return false;
            }
        }

        return is_writable($root);
    }

    public static function ensureAbsoluteDirectory(string $absolute): bool
    {
        $absolute = rtrim(str_replace('\\', '/', $absolute), '/');
        if ($absolute === '') {
            return false;
        }

        if (is_dir($absolute)) {
            return true;
        }

        $parent = dirname($absolute);
        if ($parent !== $absolute && ! is_dir($parent)) {
            if (! self::ensureAbsoluteDirectory($parent)) {
                return false;
            }
        }

        if (@mkdir($absolute, 0775) || @mkdir($absolute, 0777)) {
            @chmod($absolute, 0775);

            return true;
        }

        clearstatcache(true, $absolute);

        if (is_dir($absolute)) {
            @chmod($absolute, 0775);

            return true;
        }

        return false;
    }
}