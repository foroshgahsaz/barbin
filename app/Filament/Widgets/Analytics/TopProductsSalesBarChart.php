<?php

namespace App\Filament\Widgets\Analytics;

use App\Filament\Widgets\Analytics\Concerns\BarbinChartStyle;
use App\Services\Analytics\SalesAnalyticsService;

class TopProductsSalesBarChart extends AbstractBarbinAnalyticsChart
{
    use BarbinChartStyle;

    protected static ?string $heading = 'پرتکرار در لیست‌های فروش';

    protected static ?string $maxHeight = '320px';

    protected int|string|array $columnSpan = 'full';

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array|\Filament\Support\RawJs|null
    {
        return $this->chartOptions(horizontal: true);
    }

    protected function getData(): array
    {
        $rows = app(SalesAnalyticsService::class)->topProductsInLists(90, 12);

        return [
            'labels' => array_column($rows, 'product_code'),
            'datasets' => [[
                'label' => 'تعداد حضور',
                'data' => array_column($rows, 'appearances'),
                'backgroundColor' => $this->barbinPalette()[2],
                'borderRadius' => 6,
            ]],
        ];
    }
}