<?php

namespace Tests\Unit;

use App\Enums\StockMatchStatus;
use App\Models\SalesFileItem;
use App\Support\SalesOutputFilter;
use Tests\TestCase;

class SalesOutputFilterTest extends TestCase
{
    private function item(StockMatchStatus $status, ?float $qty): SalesFileItem
    {
        $item = new SalesFileItem;
        $item->stock_status = $status;
        $item->stock_quantity = $qty;

        return $item;
    }

    public function test_excludes_unknown_and_low_stock(): void
    {
        $this->assertFalse(SalesOutputFilter::includeInCustomerList(
            $this->item(StockMatchStatus::NotFound, null),
        ));
        $this->assertFalse(SalesOutputFilter::includeInCustomerList(
            $this->item(StockMatchStatus::Matched, 4.9),
        ));
        $this->assertTrue(SalesOutputFilter::includeInCustomerList(
            $this->item(StockMatchStatus::Matched, 5),
        ));
        $this->assertFalse(SalesOutputFilter::includeInCustomerList(
            $this->item(StockMatchStatus::Zero, 0),
        ));
    }
}