<?php

namespace Tests\Unit;

use App\Models\CatalogProduct;
use App\Services\Catalog\CatalogProductSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogProductSyncServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_seed_required_when_catalog_empty(): void
    {
        $rows = [
            ['product_code' => '00123', 'description' => 'کالا', 'unit' => 'عدد', 'quantity' => 1, 'purchase_price' => null],
        ];

        $result = app(CatalogProductSyncService::class)->syncFromImportRows($rows, false);

        $this->assertSame(0, $result['added']);
        $this->assertSame(0, CatalogProduct::query()->count());
    }

    public function test_initial_seed_creates_queue_rows_without_images(): void
    {
        $rows = [
            ['product_code' => '00123', 'description' => 'کالا اول', 'unit' => 'عدد', 'quantity' => 0, 'purchase_price' => null],
            ['product_code' => '00456', 'description' => 'دوم', 'unit' => 'عدد', 'quantity' => 5, 'purchase_price' => null],
        ];

        $result = app(CatalogProductSyncService::class)->syncFromImportRows($rows, true);

        $this->assertSame(2, $result['added']);
        $this->assertSame(2, CatalogProduct::query()->whereNull('product_image_id')->count());
        $this->assertSame('کالا اول', CatalogProduct::query()->where('product_code', '00123')->value('description'));
    }

    public function test_daily_import_only_adds_unknown_codes_without_changing_descriptions(): void
    {
        CatalogProduct::query()->create([
            'product_code' => '00123',
            'description' => 'شرح ثابت',
            'unit' => 'عدد',
        ]);

        $rows = [
            ['product_code' => '00123', 'description' => 'شرح جدید از فایل', 'unit' => 'کارتن', 'quantity' => 3, 'purchase_price' => null],
            ['product_code' => '00999', 'description' => 'کالای تازه', 'unit' => 'عدد', 'quantity' => 1, 'purchase_price' => null],
        ];

        $result = app(CatalogProductSyncService::class)->syncFromImportRows($rows, false);

        $this->assertSame(1, $result['added']);
        $this->assertSame('شرح ثابت', CatalogProduct::query()->where('product_code', '00123')->value('description'));
        $this->assertSame('کالای تازه', CatalogProduct::query()->where('product_code', '00999')->value('description'));
    }
}