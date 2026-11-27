<?php

namespace App\Filament\Widgets\Analytics;

use App\Filament\Widgets\Analytics\Concerns\BarbinChartStyle;
use App\Services\Analytics\WarehouseAnalyticsService;

class XyzVolatilityBarChart extends AbstractBarbinAnalyticsChart
{
    use BarbinChartStyle;

    protected static ?string $heading = 'XYZ نوسان موجودی';

    protected static ?string $description = 'تعداد کالا در هر کلاس';

    protected static ?string $maxHeight = '280px';

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
        $xyz = app(WarehouseAnalyticsService::class)->xyzByStockVolatility(90);

        return [
            'labels' => ['X پرنوسان', 'Y متوسط', 'Z پایدار'],
            'datasets' => [[
                'label' => 'تعداد کالا',
                'data' => [
                    count($xyz['x']),
                    count($xyz['y']),
                    count($xyz['z']),
                ],
                'backgroundColor' => [
                    $this->barbinPalette()[3],
                    $this->barbinPalette()[0],
                    $this->barbinPalette()[1],
                ],
                'borderRadius' => 8,
            ]],
        ];
    }
}