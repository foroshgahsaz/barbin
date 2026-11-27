<?php

namespace Tests\Unit;

use App\Support\NumericCell;
use PHPUnit\Framework\TestCase;

class NumericCellTest extends TestCase
{
    public function test_parses_comma_separated_price(): void
    {
        $this->assertSame(784601000.0, NumericCell::toFloat('784,601,000.00000'));
    }

    public function test_persian_digits(): void
    {
        $this->assertSame(12.5, NumericCell::toFloat('۱۲.۵'));
    }
}