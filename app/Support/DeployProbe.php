<?php

namespace App\Support;

use Illuminate\Support\Facades\Schema;

class DeployProbe
{
    /**
     * @return array<int, array{key: string, label: string, ok: bool}>
     */
    public static function checks(): array
    {
        $messengerSource = is_file(app_path('Models/Messenger.php'))
            ? (string) file_get_contents(app_path('Models/Messenger.php'))
            : '';

        return [
            [
                'key' => 'peyda_font',
                'label' => 'فونت Peyda (woff2)',
                'ok' => is_file(public_path('fonts/PeydaWebVF.woff2')),
            ],
            [
                'key' => 'peyda_css',
                'label' => 'استایل فونت (peyda-font.css)',
                'ok' => is_file(public_path('css/peyda-font.css')),
            ],
            [
                'key' => 'messenger_encrypted_cast',
                'label' => 'رمزنگاری bot_token (Laravel 12)',
                'ok' => str_contains($messengerSource, "'bot_token' => 'encrypted'")
                    && ! str_contains($messengerSource, 'AsEncryptedString'),
            ],
            [
                'key' => 'laravel_log_viewer',
                'label' => 'صفحه لاگ خطاها',
                'ok' => is_file(app_path('Filament/Pages/LaravelLogViewerPage.php')),
            ],
            [
                'key' => 'telegram_driver',
                'label' => 'درایور تلگرام',
                'ok' => is_file(app_path('Services/Messengers/TelegramDriver.php')),
            ],
            [
                'key' => 'jalali',
                'label' => 'تاریخ شمسی',
                'ok' => is_file(app_path('Support/JalaliDate.php')),
            ],
            [
                'key' => 'analytics_charts',
                'label' => 'نمودارهای تحلیل',
                'ok' => is_file(app_path('Filament/Widgets/Analytics/StockStatusDoughnutChart.php')),
            ],
            [
                'key' => 'storage_paths',
                'label' => 'PDF/Excel (StoragePaths)',
                'ok' => is_file(app_path('Support/StoragePaths.php')),
            ],
            [
                'key' => 'application_error_logs',
                'label' => 'جدول لاگ خطا در ادمین',
                'ok' => Schema::hasTable('application_error_logs'),
            ],
        ];
    }

    public static function allOk(): bool
    {
        foreach (self::checks() as $row) {
            if (! $row['ok']) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array{ok: bool, checks: array<int, array{key: string, label: string, ok: bool}>, expected_merge: string, urls: array<string, string>}
     */
    public static function report(): array
    {
        return [
            'ok' => self::allOk(),
            'checks' => self::checks(),
            'expected_merge' => 'PR #18 (8663aac) — Peyda + encrypted bot_token',
            'urls' => [
                'peyda_css' => url('/css/peyda-font.css'),
                'peyda_font' => url('/fonts/PeydaWebVF.woff2'),
            ],
        ];
    }
}