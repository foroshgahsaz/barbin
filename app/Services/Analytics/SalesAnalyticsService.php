<?php

namespace App\Services\Analytics;

use App\Enums\DeliveryStatus;
use App\Enums\StockMatchStatus;
use App\Models\DeliveryLog;
use App\Models\SalesFile;
use App\Models\SalesFileItem;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class SalesAnalyticsService
{
    public function summary(int $days = 90): array
    {
        $since = Carbon::now()->subDays($days);

        $files = SalesFile::query()->where('created_at', '>=', $since);

        return [
            'files_count' => (clone $files)->count(),
            'pdf_count' => (clone $files)->whereNotNull('pdf_path')->count(),
            'items_in_lists' => SalesFileItem::query()
                ->whereHas('salesFile', fn ($q) => $q->where('created_at', '>=', $since))
                ->count(),
            'distinct_products' => SalesFileItem::query()
                ->whereHas('salesFile', fn ($q) => $q->where('created_at', '>=', $since))
                ->distinct()
                ->count('product_code'),
        ];
    }

    /**
     * @return array{matched: int, zero: int, not_found: int, total: int, match_rate: float}
     */
    public function stockMatchBreakdown(int $days = 90): array
    {
        $since = Carbon::now()->subDays($days);

        $rows = SalesFileItem::query()
            ->whereHas('salesFile', fn ($q) => $q->where('created_at', '>=', $since))
            ->selectRaw('stock_status, COUNT(*) as c')
            ->groupBy('stock_status')
            ->pluck('c', 'stock_status');

        $matched = (int) ($rows[StockMatchStatus::Matched->value] ?? 0);
        $zero = (int) ($rows[StockMatchStatus::Zero->value] ?? 0);
        $notFound = (int) ($rows[StockMatchStatus::NotFound->value] ?? 0);
        $total = $matched + $zero + $notFound;

        return [
            'matched' => $matched,
            'zero' => $zero,
            'not_found' => $notFound,
            'total' => $total,
            'match_rate' => $total > 0 ? round(($matched / $total) * 100, 1) : 0.0,
        ];
    }

    /**
     * @return list<array{product_code: string, appearances: int}>
     */
    public function topProductsInLists(int $days = 90, int $limit = 15): array
    {
        $since = Carbon::now()->subDays($days);

        return SalesFileItem::query()
            ->whereHas('salesFile', fn ($q) => $q->where('created_at', '>=', $since))
            ->select('product_code', DB::raw('COUNT(*) as appearances'))
            ->groupBy('product_code')
            ->orderByDesc('appearances')
            ->limit($limit)
            ->get()
            ->map(fn ($r) => ['product_code' => $r->product_code, 'appearances' => (int) $r->appearances])
            ->all();
    }

    /**
     * @return list<array{month: string, count: int}>
     */
    public function filesPerMonth(int $months = 6): array
    {
        $since = Carbon::now()->subMonths($months)->startOfMonth();

        $driver = DB::connection()->getDriverName();
        $monthExpr = $driver === 'sqlite'
            ? "strftime('%Y-%m', created_at)"
            : "DATE_FORMAT(created_at, '%Y-%m')";

        return SalesFile::query()
            ->where('created_at', '>=', $since)
            ->selectRaw("{$monthExpr} as ym, COUNT(*) as c")
            ->groupBy('ym')
            ->orderBy('ym')
            ->get()
            ->map(fn ($r) => ['month' => $r->ym, 'count' => (int) $r->c])
            ->all();
    }

    /**
     * @return array{sent: int, failed: int, pending: int}
     */
    public function deliverySummary(int $days = 90): array
    {
        $since = Carbon::now()->subDays($days);

        $rows = DeliveryLog::query()
            ->where('created_at', '>=', $since)
            ->selectRaw('status, COUNT(*) as c')
            ->groupBy('status')
            ->pluck('c', 'status');

        return [
            'sent' => (int) ($rows[DeliveryStatus::Sent->value] ?? 0),
            'failed' => (int) ($rows[DeliveryStatus::Failed->value] ?? 0),
            'pending' => (int) ($rows[DeliveryStatus::Pending->value] ?? 0),
        ];
    }
}