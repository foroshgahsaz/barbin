<?php

namespace Tests\Unit;

use App\Support\JalaliDate;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class JalaliDateTest extends TestCase
{
    public function test_formats_with_persian_digits(): void
    {
        $value = JalaliDate::format(Carbon::parse('2024-03-20 14:30:00'), 'Y/m/d H:i');

        $this->assertStringContainsString('۰', $value);
        $this->assertStringNotContainsString('2024', $value);
    }
}