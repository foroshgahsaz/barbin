<?php

namespace Tests\Unit;

use App\Support\ProductCode;
use PHPUnit\Framework\TestCase;

class ProductCodeTest extends TestCase
{
    public function test_preserves_leading_zeros(): void
    {
        $this->assertSame('00123', ProductCode::normalize('00123'));
        $this->assertSame('00123', ProductCode::normalize(' 00123 '));
    }

    public function test_trims_spaces(): void
    {
        $this->assertSame('A100', ProductCode::normalize(' A100 '));
    }

    public function test_rejects_empty(): void
    {
        $this->assertNull(ProductCode::normalize(''));
        $this->assertNull(ProductCode::normalize(null));
    }
}