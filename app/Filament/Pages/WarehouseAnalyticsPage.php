<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\Analytics\AbcQuantityPolarChart;
use App\Filament\Widgets\Analytics\AbcValueBarChart;
use App\Filament\Widgets\Analytics\CapitalTopProductsDoughnutChart;
use App\Filament\Widgets\Analytics\CriticalStockBarChart;
use App\Filament\Widgets\Analytics\ExcessStockBarChart;
use App\Filament\Widgets\Analytics\InventoryTimelineLineChart;
use App\Filament\Widgets\Analytics\SlowMoversBarChart;
use App\Filament\Widgets\Analytics\SnapshotDeltaBarChart;
use App\Filament\Widgets\Analytics\StockStatusDoughnutChart;
use App\Filament\Widgets\Analytics\UnchangedStockPieChart;
use App\Filament\Widgets\Analytics\XyzVolatilityBarChart;
use Filament\Pages\Page;

class WarehouseAnalyticsPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static string $view = 'filament.pages.warehouse-analytics';

    protected static ?string $navigationLabel = 'تحلیل انبار';

    protected static ?string $navigationGroup = 'انبار';

    protected static ?string $title = 'تحلیل و هوشمندی انبار';

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user && ($user->can('reports.analytics') || $user->can('reports.warehouse_ops') || $user->can('reports.readonly_exec'));
    }

    protected function getFooterWidgets(): array
    {
        return [
            StockStatusDoughnutChart::class,
            CapitalTopProductsDoughnutChart::class,
            UnchangedStockPieChart::class,
            InventoryTimelineLineChart::class,
            AbcQuantityPolarChart::class,
            AbcValueBarChart::class,
            XyzVolatilityBarChart::class,
            SnapshotDeltaBarChart::class,
            CriticalStockBarChart::class,
            ExcessStockBarChart::class,
            SlowMoversBarChart::class,
        ];
    }

    public function getFooterWidgetsColumns(): int|string|array
    {
        return ['default' => 1, 'md' => 2, 'xl' => 3];
    }
}