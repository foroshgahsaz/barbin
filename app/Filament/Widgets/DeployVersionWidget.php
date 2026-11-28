<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class DeployVersionWidget extends Widget
{
    protected static string $view = 'filament.widgets.deploy-version';

    protected static ?int $sort = -2;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->user()?->can('settings.manage') ?? false;
    }

    /**
     * @return array<int, array{label: string, ok: bool}>
     */
    public function featureChecks(): array
    {
        return [
            ['label' => 'لاگ خطاها (پنل)', 'ok' => file_exists(app_path('Filament/Pages/LaravelLogViewerPage.php'))],
            ['label' => 'تلگرام (درایور)', 'ok' => file_exists(app_path('Services/Messengers/TelegramDriver.php'))],
            ['label' => 'تاریخ شمسی', 'ok' => file_exists(app_path('Support/JalaliDate.php'))],
            ['label' => 'نمودارهای تحلیل', 'ok' => file_exists(app_path('Filament/Widgets/Analytics/StockStatusDoughnutChart.php'))],
            ['label' => 'PDF بدون mkdir مستقیم', 'ok' => file_exists(app_path('Support/StoragePaths.php'))],
        ];
    }

    public function allFeaturesOk(): bool
    {
        foreach ($this->featureChecks() as $row) {
            if (! $row['ok']) {
                return false;
            }
        }

        return true;
    }
}