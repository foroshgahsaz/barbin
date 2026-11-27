<?php

namespace App\Filament\Widgets\Analytics;

use App\Filament\Widgets\Analytics\Concerns\BarbinChartStyle;
use App\Services\Analytics\WarehouseAnalyticsService;

class SlowMoversBarChart extends AbstractBarbinAnalyticsChart
{
    use BarbinChartStyle;

    protected static ?string $heading = 'کندگردش (موجود، بدون لیست فروش)';

    protected static ?string $maxHeight = '300px';

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
        $rows = array_slice(app(WarehouseAnalyticsService::class)->slowMoversWithoutSales(), 0, 10);

        return [
            'labels' => array_column($rows, 'product_code'),
            'datasets' => [[
                'label' => 'موجودی',
                'data' => array_column($rows, 'quantity'),
                'backgroundColor' => $this->barbinPalette()[4],
                'borderRadius' => 6,
            ]],
        ];
    }
}