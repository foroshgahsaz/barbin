<?php

namespace App\Support;

use RuntimeException;

final class MpdfTempDir
{
    public static function path(): string
    {
        $candidates = [
            storage_path('app/tmp/mpdf'),
            rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'barbin-mpdf',
        ];

        foreach ($candidates as $dir) {
            if (StoragePaths::ensureAbsoluteDirectory($dir) && is_writable($dir)) {
                return $dir;
            }
        }

        throw new RuntimeException(
            'پوشهٔ موقت mPDF قابل نوشتن نیست. روی پاد: chown -R www-data:www-data /var/www/storage && mkdir -p /tmp/barbin-mpdf && chmod 1777 /tmp',
        );
    }
}