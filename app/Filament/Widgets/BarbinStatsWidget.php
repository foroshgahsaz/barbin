<?php

namespace App\Filament\Widgets;

use App\Models\DeliveryLog;
use App\Models\SalesFile;
use App\Models\User;
use App\Services\Analytics\WarehouseAnalyticsService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class BarbinStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $warehouse = app(WarehouseAnalyticsService::class)->summary();

        $stats = [
            Stat::make('کل کالاهای Snapshot', (string) ($warehouse['items_count'] ?? 0)),
            Stat::make('موجود', (string) ($warehouse['in_stock_count'] ?? 0)),
            Stat::make('بدون موجودی', (string) ($warehouse['zero_stock_count'] ?? 0)),
        ];

        if (auth()->user()?->can('settings.manage')) {
            $stats[] = Stat::make('مدیران فروش', (string) User::role('sales_manager')->count());
            $stats[] = Stat::make('فایل‌های فروش', (string) SalesFile::count());
            $stats[] = Stat::make('PDF تولیدشده', (string) SalesFile::whereNotNull('pdf_path')->count());
            $stats[] = Stat::make('ارسال موفق', (string) DeliveryLog::where('status', 'sent')->count());
        }

        return $stats;
    }
}