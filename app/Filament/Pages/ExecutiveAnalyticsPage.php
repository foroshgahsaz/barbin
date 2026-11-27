<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\Analytics\CapitalTopProductsDoughnutChart;
use App\Filament\Widgets\Analytics\DeliveryStatusPieChart;
use App\Filament\Widgets\Analytics\MonthlySalesFilesBarChart;
use App\Filament\Widgets\Analytics\SalesMatchDoughnutChart;
use App\Filament\Widgets\Analytics\TopProductsSalesBarChart;
use Filament\Pages\Page;

class ExecutiveAnalyticsPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-line';

    protected static string $view = 'filament.pages.executive-analytics';

    protected static ?string $navigationLabel = 'گزارش مدیریتی';

    protected static ?string $navigationGroup = 'گزارش‌ها';

    protected static ?string $title = 'گزارش مدیریتی و فروش';

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user && ($user->can('reports.readonly_exec') || $user->can('settings.manage'));
    }

    protected function getFooterWidgets(): array
    {
        return [
            SalesMatchDoughnutChart::class,
            DeliveryStatusPieChart::class,
            MonthlySalesFilesBarChart::class,
            CapitalTopProductsDoughnutChart::class,
            TopProductsSalesBarChart::class,
        ];
    }

    public function getFooterWidgetsColumns(): int|string|array
    {
        return ['default' => 1, 'md' => 2, 'xl' => 2];
    }
}