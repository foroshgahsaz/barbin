<?php

namespace App\Filament\Widgets\Analytics;

use App\Filament\Widgets\Analytics\Concerns\BarbinChartStyle;
use App\Services\Analytics\WarehouseAnalyticsService;

class AbcValueBarChart extends AbstractBarbinAnalyticsChart
{
    use BarbinChartStyle;

    protected static ?string $heading = 'ABC ارزشی (ستونی)';

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
        $abc = app(WarehouseAnalyticsService::class)->abcByValue();
        $values = [
            collect($abc['a'])->sum(fn ($i) => (float) $i->purchase_price * (float) $i->quantity),
            collect($abc['b'])->sum(fn ($i) => (float) $i->purchase_price * (float) $i->quantity),
            collect($abc['c'])->sum(fn ($i) => (float) $i->purchase_price * (float) $i->quantity),
        ];

        return [
            'labels' => ['A', 'B', 'C'],
            'datasets' => [[
                'label' => 'ارزش ریالی',
                'data' => $values,
                'backgroundColor' => [
                    $this->barbinPalette()[0],
                    $this->barbinPalette()[2],
                    $this->barbinPalette()[4],
                ],
                'borderRadius' => 8,
            ]],
        ];
    }
}