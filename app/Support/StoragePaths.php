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

        throw new RuntimeException(
            'ایجاد پوشه ذخیره‌سازی ممکن نشد. روی سرور: mkdir -p storage/app/sales-pdfs storage/app/private/sales-pdfs && chmod -R ug+rwx storage && php artisan barbin:ensure-storage',
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
            'نوشتن فایل ممکن نشد — مسیر: '.$absolute.' — مجوز storage را بررسی کنید.',
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
            return true;
        }

        try {
            $disk = Storage::disk($diskName);
        } catch (\Throwable) {
            return false;
        }

        if ($disk->exists($relativeDirectory)) {
            return true;
        }

        if ($disk->makeDirectory($relativeDirectory)) {
            return true;
        }

        try {
            $absolute = $disk->path($relativeDirectory);
        } catch (\Throwable) {
            return false;
        }

        if (@mkdir($absolute, 0775, true) || is_dir($absolute)) {
            return true;
        }

        $parent = storage_path('app');
        if (! is_dir($parent)) {
            @mkdir($parent, 0775, true);
        }
        if ($diskName === 'barbin' && ! is_dir($absolute)) {
            @mkdir($absolute, 0775, true);
        }

        return is_dir($absolute);
    }
}