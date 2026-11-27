<?php

namespace App\Filament\Widgets\Analytics;

use App\Filament\Widgets\Analytics\Concerns\BarbinChartStyle;
use App\Services\Analytics\SalesAnalyticsService;
use Filament\Support\RawJs;

class SalesMatchDoughnutChart extends AbstractBarbinAnalyticsChart
{
    use BarbinChartStyle;

    protected static ?string $heading = 'تطبیق موجودی در لیست‌های فروش';

    protected static ?string $maxHeight = '300px';

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array|RawJs|null
    {
        return RawJs::make(<<<'JS'
        { cutout: '55%', plugins: { legend: { position: 'bottom', rtl: true } } }
        JS);
    }

    protected function getData(): array
    {
        $b = app(SalesAnalyticsService::class)->stockMatchBreakdown(90);

        return [
            'labels' => ['مطابق', 'صفر', 'نامشخص'],
            'datasets' => [[
                'data' => [$b['matched'], $b['zero'], $b['not_found']],
                'backgroundColor' => [
                    $this->barbinPalette()[1],
                    $this->barbinPalette()[0],
                    $this->barbinPalette()[3],
                ],
                'borderWidth' => 0,
            ]],
        ];
    }
}