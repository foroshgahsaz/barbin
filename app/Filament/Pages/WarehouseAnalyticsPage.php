<?php

namespace App\Filament\Pages;

use App\Services\Analytics\WarehouseAnalyticsService;
use Filament\Pages\Page;

class WarehouseAnalyticsPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static string $view = 'filament.pages.warehouse-analytics';

    protected static ?string $navigationLabel = 'تحلیل انبار';

    protected static ?string $navigationGroup = 'انبار';

    protected static ?string $title = 'تحلیل و هوشمندی انبار';

    public array $summary = [];

    public array $unchanged = [];

    public array $critical = [];

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user && ($user->can('reports.analytics') || $user->can('reports.warehouse_ops') || $user->can('reports.readonly_exec'));
    }

    public function mount(WarehouseAnalyticsService $analytics): void
    {
        $this->summary = $analytics->summary();
        $this->unchanged = $analytics->unchangedProducts(90);
        $this->critical = $analytics->criticalStock();
    }
}