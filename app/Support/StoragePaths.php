<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use RuntimeException;

final class StoragePaths
{
    public static function ensureDirectory(string $relativeDirectory): void
    {
        $relativeDirectory = trim(str_replace('\\', '/', $relativeDirectory), '/');
        if ($relativeDirectory === '') {
            return;
        }

        $disk = Storage::disk('local');
        if (! $disk->exists($relativeDirectory)) {
            if (! $disk->makeDirectory($relativeDirectory)) {
                throw new RuntimeException(
                    'ایجاد پوشه ذخیره‌سازی ممکن نشد (mkdir). روی سرور: chmod -R ug+rwx storage && php artisan barbin:ensure-storage',
                );
            }
        }
    }

    public static function putGeneratedFile(string $relativePath, string $binary): void
    {
        $relativePath = str_replace('\\', '/', $relativePath);
        self::ensureDirectory(dirname($relativePath));

        if (! Storage::disk('local')->put($relativePath, $binary)) {
            throw new RuntimeException('نوشتن فایل در storage ممکن نشد — مجوز پوشه storage/app/private را بررسی کنید.');
        }
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

        $disk = Storage::disk('local');
        foreach ($dirs as $dir) {
            if (! $disk->exists($dir)) {
                $disk->makeDirectory($dir);
            }
        }
    }
}