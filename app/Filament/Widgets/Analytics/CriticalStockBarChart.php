<?php

namespace App\Filament\Widgets\Analytics;

use App\Filament\Widgets\Analytics\Concerns\BarbinChartStyle;
use App\Services\Analytics\WarehouseAnalyticsService;

class CriticalStockBarChart extends AbstractBarbinAnalyticsChart
{
    use BarbinChartStyle;

    protected static ?string $heading = 'موجودی بحرانی (۱–۵)';

    protected static ?string $maxHeight = '300px';

    public static function canView(): bool
    {
        return auth()->user()?->can('reports.warehouse_ops') ?? false;
    }

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
        $items = array_slice(app(WarehouseAnalyticsService::class)->criticalStock(), 0, 10);
        $labels = array_map(fn ($i) => $i->product_code, $items);
        $data = array_map(fn ($i) => (float) $i->quantity, $items);

        return [
            'labels' => $labels,
            'datasets' => [[
                'label' => 'موجودی',
                'data' => $data,
                'backgroundColor' => $this->barbinPalette()[3],
                'borderRadius' => 6,
            ]],
        ];
    }
}