<?php

namespace App\Filament\Widgets\Analytics;

use App\Filament\Widgets\Analytics\Concerns\BarbinChartStyle;
use App\Services\Analytics\WarehouseAnalyticsService;

class InventoryTimelineLineChart extends AbstractBarbinAnalyticsChart
{
    use BarbinChartStyle;

    protected static ?string $heading = 'روند Snapshot — خطی';

    protected static ?string $maxHeight = '320px';

    protected int|string|array $columnSpan = 'full';

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array|\Filament\Support\RawJs|null
    {
        return $this->chartOptions();
    }

    protected function getData(): array
    {
        $timeline = app(WarehouseAnalyticsService::class)->snapshotTimeline(90);
        $labels = array_column($timeline, 'date');
        $items = array_column($timeline, 'items');
        $inStock = array_column($timeline, 'in_stock');

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'کل کالا',
                    'data' => $items,
                    'borderColor' => $this->barbinBorderPalette()[2],
                    'backgroundColor' => 'rgba(59, 130, 246, 0.12)',
                    'fill' => true,
                    'tension' => 0.35,
                ],
                [
                    'label' => 'دارای موجودی',
                    'data' => $inStock,
                    'borderColor' => $this->barbinBorderPalette()[1],
                    'backgroundColor' => 'rgba(16, 185, 129, 0.08)',
                    'fill' => true,
                    'tension' => 0.35,
                ],
            ],
        ];
    }
}