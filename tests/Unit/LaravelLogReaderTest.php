<?php

namespace Tests\Unit;

use App\Services\Logs\LaravelLogReader;
use Tests\TestCase;

class LaravelLogReaderTest extends TestCase
{
    public function test_rejects_invalid_log_file_name(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new LaravelLogReader)->read('../../../passwd');
    }

    public function test_reads_laravel_log_when_present(): void
    {
        $path = storage_path('logs/laravel.log');
        if (! is_file($path)) {
            $this->markTestSkipped('No laravel.log in environment');
        }

        $result = (new LaravelLogReader)->read('laravel.log');
        $this->assertTrue($result['exists']);
        $this->assertSame('laravel.log', $result['file']);
    }
}