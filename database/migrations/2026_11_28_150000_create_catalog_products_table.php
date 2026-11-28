<?php

use App\Models\CatalogProduct;
use App\Models\ProductImageCode;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catalog_products', function (Blueprint $table) {
            $table->id();
            $table->string('product_code', 64)->unique();
            $table->string('description')->nullable();
            $table->string('unit', 32)->nullable();
            $table->foreignId('product_image_id')->nullable()->constrained('product_images')->nullOnDelete();
            $table->timestamps();
            $table->index('product_image_id', 'catalog_products_image_idx');
        });

        $byCode = [];
        foreach (ProductImageCode::query()->with('image')->orderBy('id')->get() as $link) {
            if ($link->image === null || ! $link->image->is_active) {
                continue;
            }
            $code = $link->product_code;
            if (! isset($byCode[$code])) {
                $byCode[$code] = $link->product_image_id;
            } elseif ($link->is_primary) {
                $byCode[$code] = $link->product_image_id;
            }
        }

        foreach ($byCode as $code => $imageId) {
            CatalogProduct::query()->firstOrCreate(
                ['product_code' => $code],
                [
                    'description' => null,
                    'unit' => null,
                    'product_image_id' => $imageId,
                ],
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('catalog_products');
    }
};