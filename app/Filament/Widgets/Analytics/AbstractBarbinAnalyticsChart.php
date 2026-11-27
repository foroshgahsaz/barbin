<?php

namespace App\Filament\Widgets\Analytics;

use Filament\Widgets\ChartWidget;

abstract class AbstractBarbinAnalyticsChart extends ChartWidget
{
    protected static bool $isDiscovered = false;
}