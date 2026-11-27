<?php

namespace App\Services\Analytics;

use App\Models\InventorySnapshot;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class WarehouseAnalyticsService
{
    /**
     * @return list<object>
     */
    public function criticalStock(float $maxQty = 5, ?InventorySnapshot $snapshot = null): array
    {
        $snapshot ??= InventorySnapshot::latestConfirmed();
        if (! $snapshot) {
            return [];
        }

        return $snapshot->items()
            ->where('quantity', '>', 0)
            ->where('quantity', '<=', $maxQty)
            ->orderBy('quantity')
            ->limit(100)
            ->get()
            ->all();
    }

    public function summary(?InventorySnapshot $snapshot = null): array
    {
        $snapshot ??= InventorySnapshot::latestConfirmed();

        if (! $snapshot) {
            return [
                'items_count' => 0,
                'in_stock_count' => 0,
                'zero_stock_count' => 0,
                'snapshot_date' => null,
            ];
        }

        return [
            'items_count' => $snapshot->items_count,
            'in_stock_count' => $snapshot->in_stock_count,
            'zero_stock_count' => $snapshot->zero_stock_count,
            'snapshot_date' => $snapshot->recorded_at,
        ];
    }

  /**
     * کالاهای بدون تغییر موجودی در بازه — بر اساس Snapshotها (نه فروش قطعی).
     */
    public function unchangedProducts(int $days = 90): array
    {
        $since = Carbon::now()->subDays($days);

        $snapshots = InventorySnapshot::query()
            ->where('recorded_at', '>=', $since)
            ->orderBy('recorded_at')
            ->pluck('id', 'recorded_at');

        if ($snapshots->count() < 2) {
            return ['label' => 'احتمال کندگردش / عدم تغییر موجودی', 'items' => []];
        }

        $firstId = $snapshots->first();
        $lastId = $snapshots->last();

        return [
            'label' => 'عدم تغییر موجودی در بازه',
            'items' => DB::table('inventory_snapshot_items as a')
                ->join('inventory_snapshot_items as b', 'a.product_code', '=', 'b.product_code')
                ->where('a.inventory_snapshot_id', $firstId)
                ->where('b.inventory_snapshot_id', $lastId)
                ->whereColumn('a.quantity', 'b.quantity')
                ->select('a.product_code', 'a.description', 'a.quantity')
                ->limit(200)
                ->get()
                ->all(),
        ];
    }

    /**
     * @return list<array{date: string, items: int, in_stock: int}>
     */
    public function snapshotTimeline(int $days = 90): array
    {
        return InventorySnapshot::query()
            ->where('recorded_at', '>=', Carbon::now()->subDays($days))
            ->orderBy('recorded_at')
            ->get(['recorded_at', 'items_count', 'in_stock_count'])
            ->map(fn ($s) => [
                'date' => $s->recorded_at->format('Y-m-d'),
                'items' => $s->items_count,
                'in_stock' => $s->in_stock_count,
            ])
            ->all();
    }

    /**
     * موجودی مازاد (آستانه بالا — بدون قیمت خرید فقط تعداد)
     *
     * @return list<object>
     */
    public function excessStock(float $minQty = 50, ?InventorySnapshot $snapshot = null): array
    {
        $snapshot ??= InventorySnapshot::latestConfirmed();
        if (! $snapshot) {
            return [];
        }

        return $snapshot->items()
            ->where('quantity', '>=', $minQty)
            ->orderByDesc('quantity')
            ->limit(50)
            ->get()
            ->all();
    }

    /**
     * طبقه‌بندی ABC براساس «سهم موجودی عددی» (نه ارزش ریالی — تا داده قیمت خرید نداشته باشیم)
     *
     * @return array{a: list<object>, b: list<object>, c: list<object>}
     */
    public function abcByQuantity(?InventorySnapshot $snapshot = null): array
    {
        $snapshot ??= InventorySnapshot::latestConfirmed();
        if (! $snapshot) {
            return ['a' => [], 'b' => [], 'c' => []];
        }

        $items = $snapshot->items()
            ->where('quantity', '>', 0)
            ->orderByDesc('quantity')
            ->get();

        $total = $items->sum('quantity');
        if ($total <= 0) {
            return ['a' => [], 'b' => [], 'c' => []];
        }

        $running = 0.0;
        $a = [];
        $b = [];
        $c = [];

        foreach ($items as $item) {
            $running += (float) $item->quantity;
            $pct = ($running / $total) * 100;
            if ($pct <= 80) {
                $a[] = $item;
            } elseif ($pct <= 95) {
                $b[] = $item;
            } else {
                $c[] = $item;
            }
        }

        return ['a' => $a, 'b' => $b, 'c' => $c];
    }
}