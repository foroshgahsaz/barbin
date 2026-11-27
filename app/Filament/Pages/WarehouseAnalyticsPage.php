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

    public array $timeline = [];

    public array $excess = [];

    public array $abc = [];

    public array $abcValue = [];

    public array $capital = [];

    public array $xyz = [];

    public array $zeroStock = [];

    public array $stockDelta = [];

    public array $reorder = [];

    public array $excessValue = [];

    public array $slowMovers = [];

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
        $this->timeline = $analytics->snapshotTimeline(90);
        $this->excess = $analytics->excessStock();
        $this->abc = $analytics->abcByQuantity();
        $this->abcValue = $analytics->abcByValue();
        $this->capital = $analytics->capitalAtRest();
        $this->xyz = $analytics->xyzByStockVolatility(90);
        $this->zeroStock = $analytics->zeroStockList();
        $this->stockDelta = $analytics->stockDeltaBetweenLatestSnapshots();
        $this->reorder = $analytics->reorderSuggestions();
        $this->excessValue = $analytics->excessByValue();
        $this->slowMovers = $analytics->slowMoversWithoutSales();
    }
}