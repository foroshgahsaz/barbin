<?php

namespace Tests\Unit;

use App\Support\StoragePaths;
use Tests\TestCase;

class StoragePathsTest extends TestCase
{
    public function test_ensure_absolute_directory_creates_nested_path(): void
    {
        $base = storage_path('app/barbin-test-'.uniqid('', true));
        $deep = $base.'/sales-exports/1';

        $this->assertTrue(StoragePaths::ensureAbsoluteDirectory($deep));
        $this->assertDirectoryExists($deep);

        @unlink($deep);
        @rmdir(dirname($deep));
        @rmdir($base);
    }
}