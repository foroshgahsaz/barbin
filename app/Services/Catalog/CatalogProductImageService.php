<?php

namespace App\Services\Catalog;

use App\Models\CatalogProduct;
use App\Models\ProductImage;
use App\Models\ProductImageCode;
use App\Services\ProductImages\ProductImageService;
use App\Support\MediaPath;
use App\Support\ProductCode;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class CatalogProductImageService
{
    public function __construct(
        private ProductImageService $productImages,
    ) {}

    public function attachUpload(CatalogProduct $product, UploadedFile $file, int $ownerUserId): ProductImage
    {
        $code = ProductCode::normalize($product->product_code);
        if ($code === null) {
            throw new \InvalidArgumentException('کد کالا نامعتبر است.');
        }

        $previousId = $product->product_image_id;

        $image = $this->productImages->store($file, $ownerUserId, $product->description ?: $code);

        ProductImageCode::query()
            ->where('product_code', $code)
            ->where('product_image_id', '!=', $image->id)
            ->delete();

        ProductImageCode::query()->updateOrCreate(
            [
                'product_image_id' => $image->id,
                'product_code' => $code,
            ],
            ['is_primary' => true],
        );

        $product->update(['product_image_id' => $image->id]);

        if ($previousId !== null && $previousId !== $image->id) {
            $this->deleteOrphanImage($previousId);
        }

        return $image;
    }

    public function attachFromStoredPath(CatalogProduct $product, string $relativePath, int $ownerUserId): ProductImage
    {
        $relativePath = ltrim(str_replace('\\', '/', $relativePath), '/');
        $absolute = MediaPath::absolute($relativePath);
        if ($absolute === null || ! is_readable($absolute)) {
            foreach (['local', 'public', 'barbin'] as $disk) {
                if (Storage::disk($disk)->exists($relativePath)) {
                    $absolute = Storage::disk($disk)->path($relativePath);
                    break;
                }
            }
        }
        if ($absolute === null || ! is_readable($absolute)) {
            throw new \RuntimeException('فایل تصویر موقت یافت نشد.');
        }

        $file = new UploadedFile($absolute, basename($absolute), null, null, true);

        return $this->attachUpload($product, $file, $ownerUserId);
    }

    public function detachImage(CatalogProduct $product): void
    {
        $imageId = $product->product_image_id;
        if ($imageId === null) {
            return;
        }

        ProductImageCode::query()
            ->where('product_code', $product->product_code)
            ->delete();

        $product->update(['product_image_id' => null]);

        $this->deleteOrphanImage($imageId);
    }

    private function deleteOrphanImage(int $imageId): void
    {
        $stillLinked = CatalogProduct::query()->where('product_image_id', $imageId)->exists()
            || ProductImageCode::query()->where('product_image_id', $imageId)->exists();

        if (! $stillLinked) {
            ProductImage::query()->whereKey($imageId)->delete();
        }
    }
}