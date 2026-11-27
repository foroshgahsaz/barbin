<?php

namespace App\Filament\Widgets\Analytics;

use App\Filament\Widgets\Analytics\Concerns\BarbinChartStyle;
use App\Services\Analytics\SalesAnalyticsService;
use Filament\Support\RawJs;

class DeliveryStatusPieChart extends AbstractBarbinAnalyticsChart
{
    use BarbinChartStyle;

    protected static ?string $heading = 'وضعیت ارسال پیام';

    protected static ?string $maxHeight = '280px';

    protected function getType(): string
    {
        return 'pie';
    }

    protected function getOptions(): array|\Filament\Support\RawJs|null
    {
        return RawJs::make(<<<'JS'
        { plugins: { legend: { position: 'bottom', rtl: true } } }
        JS);
    }

    protected function getData(): array
    {
        $d = app(SalesAnalyticsService::class)->deliverySummary(90);

        return [
            'labels' => ['موفق', 'ناموفق', 'در انتظار'],
            'datasets' => [[
                'data' => [$d['sent'], $d['failed'], $d['pending']],
                'backgroundColor' => [
                    $this->barbinPalette()[1],
                    $this->barbinPalette()[3],
                    $this->barbinPalette()[2],
                ],
            ]],
        ];
    }
}