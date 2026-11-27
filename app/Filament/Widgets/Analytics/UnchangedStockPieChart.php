<?php

namespace App\Filament\Widgets\Analytics;

use App\Filament\Widgets\Analytics\Concerns\BarbinChartStyle;
use App\Services\Analytics\WarehouseAnalyticsService;
use Filament\Support\RawJs;

class UnchangedStockPieChart extends AbstractBarbinAnalyticsChart
{
    use BarbinChartStyle;

    protected static ?string $heading = 'عدم تغییر موجودی ۹۰ روزه';

    protected static ?string $description = 'ثابت مانده / بقیه کالاها';

    protected static ?string $maxHeight = '280px';

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array|RawJs|null
    {
        return RawJs::make(<<<'JS'
        { cutout: '58%', plugins: { legend: { position: 'bottom', rtl: true } } }
        JS);
    }

    protected function getData(): array
    {
        $summary = app(WarehouseAnalyticsService::class)->summary();
        $unchanged = app(WarehouseAnalyticsService::class)->unchangedProducts(90);
        $fixed = count($unchanged['items'] ?? []);
        $total = (int) ($summary['items_count'] ?? 0);
        $changed = max(0, $total - $fixed);

        return [
            'labels' => ['بدون تغییر', 'تغییر یافته / داده ناکافی'],
            'datasets' => [[
                'data' => [$fixed, $changed],
                'backgroundColor' => [$this->barbinPalette()[2], $this->barbinPalette()[6]],
                'borderWidth' => 0,
            ]],
        ];
    }
}