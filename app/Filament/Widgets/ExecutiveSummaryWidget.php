<?php

namespace App\Filament\Widgets;

use App\Services\Analytics\SalesAnalyticsService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ExecutiveSummaryWidget extends BaseWidget
{
    public static function canView(): bool
    {
        $user = auth()->user();

        return $user && ($user->can('reports.readonly_exec') || $user->can('settings.manage'));
    }

    protected function getStats(): array
    {
        $sales = app(SalesAnalyticsService::class);

        return [
            Stat::make('لیست فروش (۹۰ روز)', (string) ($sales->summary()['files_count'] ?? 0)),
            Stat::make('نرخ تطبیق', ($sales->stockMatchBreakdown()['match_rate'] ?? 0).'%'),
            Stat::make('ارسال موفق', (string) ($sales->deliverySummary()['sent'] ?? 0)),
        ];
    }
}