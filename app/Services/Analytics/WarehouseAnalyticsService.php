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

    /**
     * سرمایه خوابیده (ریالی) بر اساس قیمت خرید × موجودی
     *
     * @return array{total_value: float, priced_lines: int, unpriced_lines: int, average_line_value: float|null}
     */
    public function capitalAtRest(?InventorySnapshot $snapshot = null): array
    {
        $snapshot ??= InventorySnapshot::latestConfirmed();
        if (! $snapshot) {
            return ['total_value' => 0, 'priced_lines' => 0, 'unpriced_lines' => 0, 'average_line_value' => null];
        }

        $priced = $snapshot->items()->whereNotNull('purchase_price')->where('quantity', '>', 0)->get();
        $unpricedCount = $snapshot->items()->whereNull('purchase_price')->where('quantity', '>', 0)->count();

        $total = 0.0;
        foreach ($priced as $item) {
            $total += (float) $item->purchase_price * (float) $item->quantity;
        }

        $pricedCount = $priced->count();

        return [
            'total_value' => round($total, 0),
            'priced_lines' => $pricedCount,
            'unpriced_lines' => $unpricedCount,
            'average_line_value' => $pricedCount > 0 ? round($total / $pricedCount, 0) : null,
        ];
    }

    /**
     * @return array{a: list<object>, b: list<object>, c: list<object>}
     */
    public function abcByValue(?InventorySnapshot $snapshot = null): array
    {
        $snapshot ??= InventorySnapshot::latestConfirmed();
        if (! $snapshot) {
            return ['a' => [], 'b' => [], 'c' => []];
        }

        $items = $snapshot->items()
            ->whereNotNull('purchase_price')
            ->where('quantity', '>', 0)
            ->get()
            ->sortByDesc(fn ($i) => (float) $i->purchase_price * (float) $i->quantity)
            ->values();

        $total = $items->sum(fn ($i) => (float) $i->purchase_price * (float) $i->quantity);
        if ($total <= 0) {
            return ['a' => [], 'b' => [], 'c' => []];
        }

        $running = 0.0;
        $a = [];
        $b = [];
        $c = [];

        foreach ($items as $item) {
            $lineValue = (float) $item->purchase_price * (float) $item->quantity;
            $running += $lineValue;
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

    /**
     * @return list<object>
     */
    public function excessByValue(float $minLineValue = 5_000_000, ?InventorySnapshot $snapshot = null): array
    {
        $snapshot ??= InventorySnapshot::latestConfirmed();
        if (! $snapshot) {
            return [];
        }

        return $snapshot->items()
            ->whereNotNull('purchase_price')
            ->where('quantity', '>', 0)
            ->get()
            ->filter(fn ($i) => ((float) $i->purchase_price * (float) $i->quantity) >= $minLineValue)
            ->sortByDesc(fn ($i) => (float) $i->purchase_price * (float) $i->quantity)
            ->take(30)
            ->values()
            ->all();
    }

    /**
     * @return list<object>
     */
    public function zeroStockList(?InventorySnapshot $snapshot = null, int $limit = 50): array
    {
        $snapshot ??= InventorySnapshot::latestConfirmed();
        if (! $snapshot) {
            return [];
        }

        return $snapshot->items()
            ->where('quantity', '<=', 0)
            ->orderBy('product_code')
            ->limit($limit)
            ->get()
            ->all();
    }

    /**
     * XYZ بر اساس نوسان موجودی در Snapshotها (ضریب تغییرات)
     *
     * @return array{x: list<array<string, mixed>>, y: list<array<string, mixed>>, z: list<array<string, mixed>>}
     */
    public function xyzByStockVolatility(int $days = 90): array
    {
        $since = Carbon::now()->subDays($days);
        $snapshotIds = InventorySnapshot::query()
            ->where('recorded_at', '>=', $since)
            ->orderBy('recorded_at')
            ->pluck('id');

        if ($snapshotIds->count() < 2) {
            return ['x' => [], 'y' => [], 'z' => []];
        }

        $rows = DB::table('inventory_snapshot_items')
            ->whereIn('inventory_snapshot_id', $snapshotIds)
            ->select('product_code', 'quantity')
            ->get()
            ->groupBy('product_code');

        $x = [];
        $y = [];
        $z = [];

        foreach ($rows as $code => $series) {
            $values = $series->map(fn ($r) => (float) $r->quantity)->values();
            $avg = $values->avg() ?: 0.0;
            $variance = $values->map(fn ($v) => ($v - $avg) ** 2)->avg() ?: 0.0;
            $std = sqrt($variance);
            $cv = $avg > 0 ? $std / $avg : ($std > 0 ? 1.0 : 0.0);
            $entry = [
                'product_code' => $code,
                'avg_quantity' => round($avg, 2),
                'cv' => round($cv, 3),
            ];
            if ($cv >= 0.35) {
                $x[] = $entry;
            } elseif ($cv >= 0.1) {
                $y[] = $entry;
            } else {
                $z[] = $entry;
            }
        }

        usort($x, fn ($a, $b) => $b['cv'] <=> $a['cv']);
        usort($y, fn ($a, $b) => $b['cv'] <=> $a['cv']);

        return [
            'x' => array_slice($x, 0, 15),
            'y' => array_slice($y, 0, 15),
            'z' => array_slice($z, 0, 15),
        ];
    }

    /**
     * @return array{added: int, removed: int, changed: int, first_date: ?string, last_date: ?string}
     */
    public function stockDeltaBetweenLatestSnapshots(): array
    {
        $lastTwo = InventorySnapshot::query()
            ->orderByDesc('recorded_at')
            ->limit(2)
            ->get();

        if ($lastTwo->count() < 2) {
            return ['added' => 0, 'removed' => 0, 'changed' => 0, 'first_date' => null, 'last_date' => null];
        }

        $newer = $lastTwo[0];
        $older = $lastTwo[1];

        $oldMap = $older->items()->pluck('quantity', 'product_code');
        $newMap = $newer->items()->pluck('quantity', 'product_code');

        $added = 0;
        $removed = 0;
        $changed = 0;

        foreach ($newMap as $code => $qty) {
            if (! $oldMap->has($code)) {
                $added++;
            } elseif ((float) $oldMap[$code] !== (float) $qty) {
                $changed++;
            }
        }

        foreach ($oldMap as $code => $qty) {
            if (! $newMap->has($code)) {
                $removed++;
            }
        }

        return [
            'added' => $added,
            'removed' => $removed,
            'changed' => $changed,
            'first_date' => $older->recorded_at?->format('Y-m-d'),
            'last_date' => $newer->recorded_at?->format('Y-m-d'),
        ];
    }

    /**
     * @return list<array{product_code: string, description: ?string, quantity: float, purchase_price: ?float, line_value: float}>
     */
    public function reorderSuggestions(float $maxQty = 5, int $limit = 20, ?InventorySnapshot $snapshot = null): array
    {
        $snapshot ??= InventorySnapshot::latestConfirmed();
        if (! $snapshot) {
            return [];
        }

        return $snapshot->items()
            ->where('quantity', '>', 0)
            ->where('quantity', '<=', $maxQty)
            ->whereNotNull('purchase_price')
            ->orderBy('quantity')
            ->limit($limit)
            ->get()
            ->map(fn ($i) => [
                'product_code' => $i->product_code,
                'description' => $i->description,
                'quantity' => (float) $i->quantity,
                'purchase_price' => $i->purchase_price !== null ? (float) $i->purchase_price : null,
                'line_value' => (float) $i->purchase_price * (float) $i->quantity,
            ])
            ->all();
    }

    /**
     * @return list<array{product_code: string, appearances: int, description: ?string}>
     */
    public function slowMoversWithoutSales(int $days = 90, int $limit = 20): array
    {
        $snapshot = InventorySnapshot::latestConfirmed();
        if (! $snapshot) {
            return [];
        }

        $since = Carbon::now()->subDays($days);
        $soldCodes = DB::table('sales_file_items')
            ->join('sales_files', 'sales_files.id', '=', 'sales_file_items.sales_file_id')
            ->where('sales_files.created_at', '>=', $since)
            ->distinct()
            ->pluck('sales_file_items.product_code');

        return $snapshot->items()
            ->where('quantity', '>', 0)
            ->whereNotIn('product_code', $soldCodes)
            ->orderByDesc('quantity')
            ->limit($limit)
            ->get()
            ->map(fn ($i) => [
                'product_code' => $i->product_code,
                'description' => $i->description,
                'quantity' => (float) $i->quantity,
            ])
            ->all();
    }
}