<?php

namespace App\Services\ProductImages;

use App\Models\ProductImage;
use App\Models\ProductImageCode;
use App\Support\ProductCode;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductImageService
{
    private const ALLOWED_MIMES = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];

    public function store(UploadedFile $file, int $ownerUserId, ?string $title = null): ProductImage
    {
        $mime = $file->getMimeType() ?: 'application/octet-stream';
        if (! in_array($mime, self::ALLOWED_MIMES, true)) {
            throw new \InvalidArgumentException('نوع فایل تصویر مجاز نیست.');
        }

        $ext = $file->guessExtension() ?: 'jpg';
        $name = Str::uuid()->toString().'.'.$ext;
        $path = 'product-images/'.$ownerUserId.'/'.$name;

        Storage::disk('local')->put($path, file_get_contents($file->getRealPath()));

        return ProductImage::query()->create([
            'owner_user_id' => $ownerUserId,
            'title' => $title,
            'file_path' => $path,
            'thumbnail_path' => $path,
            'mime_type' => $mime,
            'file_size' => $file->getSize(),
            'is_active' => true,
        ]);
    }

    /**
     * @param  list<string>  $productCodes
     */
    public function attachCodes(ProductImage $image, array $productCodes, ?string $primaryCode = null): void
    {
        $primary = ProductCode::normalize($primaryCode);

        foreach ($productCodes as $raw) {
            $code = ProductCode::normalize($raw);
            if ($code === null) {
                continue;
            }

            ProductImageCode::query()->firstOrCreate(
                [
                    'product_image_id' => $image->id,
                    'product_code' => $code,
                ],
                [
                    'is_primary' => $primary !== null && $primary === $code,
                ],
            );
        }
    }
}