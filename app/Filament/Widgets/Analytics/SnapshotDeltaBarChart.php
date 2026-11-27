<?php

namespace App\Filament\Widgets\Analytics;

use App\Filament\Widgets\Analytics\Concerns\BarbinChartStyle;
use App\Services\Analytics\WarehouseAnalyticsService;

class SnapshotDeltaBarChart extends AbstractBarbinAnalyticsChart
{
    use BarbinChartStyle;

    protected static ?string $heading = 'تغییر دو Snapshot آخر';

    protected static ?string $maxHeight = '260px';

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
        $delta = app(WarehouseAnalyticsService::class)->stockDeltaBetweenLatestSnapshots();

        return [
            'labels' => ['افزوده', 'حذف‌شده', 'تغییر مقدار'],
            'datasets' => [[
                'data' => [
                    $delta['added'],
                    $delta['removed'],
                    $delta['changed'],
                ],
                'backgroundColor' => [
                    $this->barbinPalette()[1],
                    $this->barbinPalette()[3],
                    $this->barbinPalette()[2],
                ],
                'borderRadius' => 8,
            ]],
        ];
    }
}