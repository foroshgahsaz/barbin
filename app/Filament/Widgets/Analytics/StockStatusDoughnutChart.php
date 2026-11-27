<?php

namespace App\Filament\Widgets\Analytics;

use App\Filament\Widgets\Analytics\Concerns\BarbinChartStyle;
use App\Services\Analytics\WarehouseAnalyticsService;
use Filament\Support\RawJs;

class StockStatusDoughnutChart extends AbstractBarbinAnalyticsChart
{
    use BarbinChartStyle;

    protected static ?string $heading = 'ترکیب موجودی (دایره‌ای)';

    protected static ?string $description = 'موجود / صفر — آخرین Snapshot';

    protected static ?string $maxHeight = '300px';

    protected static string $color = 'warning';

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array|\Filament\Support\RawJs|null
    {
        return RawJs::make(<<<'JS'
        {
            cutout: '62%',
            plugins: { legend: { position: 'bottom', rtl: true, labels: { usePointStyle: true } } }
        }
        JS);
    }

    protected function getData(): array
    {
        $summary = app(WarehouseAnalyticsService::class)->summary();
        $in = (int) ($summary['in_stock_count'] ?? 0);
        $zero = (int) ($summary['zero_stock_count'] ?? 0);

        return [
            'labels' => ['دارای موجودی', 'بدون موجودی'],
            'datasets' => [[
                'data' => [$in, $zero],
                'backgroundColor' => [$this->barbinPalette()[1], $this->barbinPalette()[3]],
                'borderWidth' => 0,
            ]],
        ];
    }
}