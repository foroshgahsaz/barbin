<?php

namespace App\Filament\Widgets\Analytics;

use App\Filament\Widgets\Analytics\Concerns\BarbinChartStyle;
use App\Services\Analytics\WarehouseAnalyticsService;
use Filament\Support\RawJs;

class AbcQuantityPolarChart extends AbstractBarbinAnalyticsChart
{
    use BarbinChartStyle;

    protected static ?string $heading = 'ABC مقداری';

    protected static ?string $maxHeight = '280px';

    protected function getType(): string
    {
        return 'polarArea';
    }

    protected function getOptions(): array|RawJs|null
    {
        return RawJs::make(<<<'JS'
        { plugins: { legend: { position: 'bottom', rtl: true } } }
        JS);
    }

    protected function getData(): array
    {
        $abc = app(WarehouseAnalyticsService::class)->abcByQuantity();

        return [
            'labels' => ['کلاس A', 'کلاس B', 'کلاس C'],
            'datasets' => [[
                'data' => [
                    count($abc['a']),
                    count($abc['b']),
                    count($abc['c']),
                ],
                'backgroundColor' => [
                    $this->barbinPalette()[0],
                    $this->barbinPalette()[2],
                    $this->barbinPalette()[4],
                ],
            ]],
        ];
    }
}