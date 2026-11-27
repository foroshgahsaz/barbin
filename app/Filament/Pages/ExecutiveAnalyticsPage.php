<?php

namespace App\Filament\Pages;

use App\Services\Analytics\SalesAnalyticsService;
use App\Services\Analytics\WarehouseAnalyticsService;
use Filament\Pages\Page;

class ExecutiveAnalyticsPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-line';

    protected static string $view = 'filament.pages.executive-analytics';

    protected static ?string $navigationLabel = 'گزارش مدیریتی';

    protected static ?string $navigationGroup = 'گزارش‌ها';

    protected static ?string $title = 'گزارش مدیریتی و فروش';

    public array $capital = [];

    public array $abcValue = [];

    public array $salesSummary = [];

    public array $matchBreakdown = [];

    public array $delivery = [];

    public array $topProducts = [];

    public array $filesPerMonth = [];

    public array $stockDelta = [];

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user && ($user->can('reports.readonly_exec') || $user->can('settings.manage'));
    }

    public function mount(WarehouseAnalyticsService $warehouse, SalesAnalyticsService $sales): void
    {
        $this->capital = $warehouse->capitalAtRest();
        $this->abcValue = $warehouse->abcByValue();
        $this->stockDelta = $warehouse->stockDeltaBetweenLatestSnapshots();
        $this->salesSummary = $sales->summary();
        $this->matchBreakdown = $sales->stockMatchBreakdown();
        $this->delivery = $sales->deliverySummary();
        $this->topProducts = $sales->topProductsInLists();
        $this->filesPerMonth = $sales->filesPerMonth();
    }
}