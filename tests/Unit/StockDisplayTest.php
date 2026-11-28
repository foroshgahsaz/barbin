<?php

namespace Tests\Unit;

use App\Enums\StockMatchStatus;
use App\Support\StockDisplay;
use PHPUnit\Framework\TestCase;

class StockDisplayTest extends TestCase
{
    public function test_quantity_rounds_to_integer(): void
    {
        $this->assertSame('18', StockDisplay::quantityForDisplay(17.68, StockMatchStatus::Matched));
    }

    public function test_status_labels_in_persian(): void
    {
        $this->assertSame('موجود', StockMatchStatus::Matched->label());
        $this->assertSame('صفر', StockMatchStatus::Zero->label());
    }
}