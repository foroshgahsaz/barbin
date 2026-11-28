<?php

namespace App\Support;

final class LoggingPaths
{
    public static function primaryLogFile(): string
    {
        $configured = env('LOG_FILE_PATH');
        if (is_string($configured) && $configured !== '') {
            return $configured;
        }

        return storage_path('app/logs/laravel.log');
    }

    public static function ensureWritableTree(): void
    {
        $legacy = storage_path('logs');
        $primaryDir = dirname(self::primaryLogFile());

        foreach ([$legacy, $primaryDir] as $dir) {
            if (! is_dir($dir)) {
                @mkdir($dir, 0775, true);
            }
        }

        $primaryFile = self::primaryLogFile();
        if (! is_file($primaryFile)) {
            @file_put_contents($primaryFile, '');
            @chmod($primaryFile, 0664);
        }

        $legacyFile = $legacy.'/laravel.log';
        if (! is_file($legacyFile)) {
            @file_put_contents($legacyFile, '');
            @chmod($legacyFile, 0664);
        }
    }
}