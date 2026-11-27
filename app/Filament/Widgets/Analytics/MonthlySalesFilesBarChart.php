<?php

namespace App\Filament\Widgets\Analytics;

use App\Filament\Widgets\Analytics\Concerns\BarbinChartStyle;
use App\Services\Analytics\SalesAnalyticsService;

class MonthlySalesFilesBarChart extends AbstractBarbinAnalyticsChart
{
    use BarbinChartStyle;

    protected static ?string $heading = 'فایل فروش — ماهانه';

    protected static ?string $maxHeight = '300px';

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array|\Filament\Support\RawJs|null
    {
        return $this->chartOptions();
    }

    protected function getData(): array
    {
        $rows = app(SalesAnalyticsService::class)->filesPerMonth(6);

        return [
            'labels' => array_column($rows, 'month'),
            'datasets' => [[
                'label' => 'تعداد لیست',
                'data' => array_column($rows, 'count'),
                'backgroundColor' => $this->barbinPalette()[0],
                'borderRadius' => 8,
            ]],
        ];
    }
}