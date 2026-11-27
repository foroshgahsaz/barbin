<?php

namespace App\Filament\Widgets\Analytics\Concerns;

use Filament\Support\RawJs;

trait BarbinChartStyle
{
    /**
     * @return list<string>
     */
    protected function barbinPalette(): array
    {
        return [
            'rgba(245, 158, 11, 0.85)',
            'rgba(16, 185, 129, 0.85)',
            'rgba(59, 130, 246, 0.85)',
            'rgba(239, 68, 68, 0.85)',
            'rgba(139, 92, 246, 0.85)',
            'rgba(236, 72, 153, 0.85)',
            'rgba(20, 184, 166, 0.85)',
            'rgba(251, 146, 60, 0.85)',
        ];
    }

    protected function barbinBorderPalette(): array
    {
        return array_map(
            fn (string $c) => preg_replace('/0\.85\)/', '1)', $c) ?? $c,
            $this->barbinPalette(),
        );
    }

    protected function chartOptions(bool $horizontal = false): RawJs
    {
        if ($horizontal) {
            return RawJs::make(<<<'JS'
            {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    x: { beginAtZero: true, grid: { color: 'rgba(148, 163, 184, 0.15)' } },
                    y: { grid: { display: false } }
                }
            }
            JS);
        }

        return RawJs::make(<<<'JS'
        {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'bottom',
                    rtl: true,
                    labels: { usePointStyle: true, padding: 16 }
                }
            },
            scales: {
                x: { grid: { display: false } },
                y: { grid: { color: 'rgba(148, 163, 184, 0.15)' }, beginAtZero: true }
            }
        }
        JS);
    }
}