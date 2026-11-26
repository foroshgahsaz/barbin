<?php

namespace Tests\Unit;

use App\Enums\StockMatchStatus;
use App\Services\Sales\SalesInventoryMatcher;
use PHPUnit\Framework\TestCase;

class SalesInventoryMatcherTest extends TestCase
{
    private SalesInventoryMatcher $matcher;

    protected function setUp(): void
    {
        parent::setUp();
        $this->matcher = new SalesInventoryMatcher;
    }

    public function test_not_found_when_code_missing_in_warehouse(): void
    {
        $result = $this->matcher->match('X1', ['A1' => 5], true);
        $this->assertSame(StockMatchStatus::NotFound, $result['status']);
        $this->assertNull($result['quantity']);
    }

    public function test_zero_stock_is_valid(): void
    {
        $result = $this->matcher->match('A1', ['A1' => 0], true);
        $this->assertSame(StockMatchStatus::Zero, $result['status']);
        $this->assertSame(0.0, $result['quantity']);
    }

    public function test_positive_stock(): void
    {
        $result = $this->matcher->match('A1', ['A1' => 12], true);
        $this->assertSame(StockMatchStatus::Matched, $result['status']);
        $this->assertSame(12.0, $result['quantity']);
    }
}