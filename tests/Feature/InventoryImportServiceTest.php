<?php

namespace Tests\Feature;

use App\Enums\ImportBatchStatus;
use App\Models\User;
use App\Services\Inventory\InventoryImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class InventoryImportServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_import_and_confirm_creates_snapshot(): void
    {
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        $user = User::query()->where('email', 'admin@barbin.local')->first();
        $csv = "کد کالا,شرح کالا,واحد,موجودی\n00123,کالای تست,عدد,5\n00456,بدون موجودی,عدد,0\n";
        $path = 'inventory-uploads/test.csv';
        Storage::disk('local')->put($path, $csv);

        $service = app(InventoryImportService::class);
        $batch = $service->createPreview($user, 'test.csv', $path);

        $this->assertSame(ImportBatchStatus::Preview, $batch->status);
        $this->assertSame(2, $batch->row_count);

        $snapshot = $service->confirm($batch);
        $this->assertSame(2, $snapshot->items()->count());
        $this->assertSame('00123', $snapshot->items()->where('product_code', '00123')->value('product_code'));
    }
}