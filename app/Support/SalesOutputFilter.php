<?php

namespace App\Support;

use App\Enums\StockMatchStatus;
use App\Models\SalesFileItem;

final class SalesOutputFilter
{
    public static function minimumStockQuantity(): int
    {
        return (int) config('barbin.sales_output.min_stock_quantity', 5);
    }

    public static function includeInCustomerList(SalesFileItem $item): bool
    {
        if (in_array($item->stock_status, [StockMatchStatus::NotFound, StockMatchStatus::Pending], true)) {
            return false;
        }

        if ($item->stock_status === StockMatchStatus::Zero) {
            return false;
        }

        $qty = $item->stock_quantity !== null ? (float) $item->stock_quantity : null;
        if ($qty === null) {
            return false;
        }

        return $qty >= self::minimumStockQuantity();
    }

    /**
     * @param  iterable<int, SalesFileItem>  $items
     * @return list<SalesFileItem>
     */
    public static function filterItems(iterable $items): array
    {
        $out = [];
        foreach ($items as $item) {
            if (self::includeInCustomerList($item)) {
                $out[] = $item;
            }
        }

        return $out;
    }
}