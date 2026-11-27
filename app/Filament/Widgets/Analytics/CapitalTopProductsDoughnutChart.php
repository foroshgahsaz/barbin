<?php

namespace App\Filament\Widgets\Analytics;

use App\Filament\Widgets\Analytics\Concerns\BarbinChartStyle;
use App\Models\InventorySnapshot;
use Filament\Support\RawJs;

class CapitalTopProductsDoughnutChart extends AbstractBarbinAnalyticsChart
{
    use BarbinChartStyle;

    protected static ?string $heading = 'سهم سرمایه خوابیده (ریالی)';

    protected static ?string $description = 'برترین کالاها بر اساس قیمت خرید × موجودی';

    protected static ?string $maxHeight = '300px';

    protected function getType(): string
    {
        return 'pie';
    }

    protected function getOptions(): array|RawJs|null
    {
        return RawJs::make(<<<'JS'
        {
            plugins: { legend: { position: 'bottom', rtl: true, labels: { usePointStyle: true, boxWidth: 8 } } }
        }
        JS);
    }

    protected function getData(): array
    {
        $snapshot = InventorySnapshot::latestConfirmed();
        if (! $snapshot) {
            return ['labels' => ['بدون داده'], 'datasets' => [['data' => [1], 'backgroundColor' => ['#e5e7eb']]]];
        }

        $lines = $snapshot->items()
            ->whereNotNull('purchase_price')
            ->where('quantity', '>', 0)
            ->get()
            ->map(fn ($i) => [
                'code' => $i->product_code,
                'value' => (float) $i->purchase_price * (float) $i->quantity,
            ])
            ->sortByDesc('value')
            ->values();

        $top = $lines->take(6);
        $other = $lines->slice(6)->sum('value');
        $labels = $top->pluck('code')->all();
        $data = $top->pluck('value')->all();
        if ($other > 0) {
            $labels[] = 'سایر';
            $data[] = $other;
        }

        $colors = array_slice($this->barbinPalette(), 0, count($data));

        return [
            'labels' => $labels,
            'datasets' => [[
                'data' => $data,
                'backgroundColor' => $colors,
                'borderWidth' => 0,
            ]],
        ];
    }
}