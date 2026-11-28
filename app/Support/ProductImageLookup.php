<?php

namespace App\Support;

use App\Models\CatalogProduct;
use App\Models\ProductImage;
use App\Models\ProductImageCode;

final class ProductImageLookup
{
    /**
     * @param  list<string>  $productCodes
     * @return array<string, string> normalized_code => absolute path for mPDF
     */
    public static function mapForCodes(array $productCodes): array
    {
        $normalized = [];
        foreach ($productCodes as $raw) {
            $code = ProductCode::normalize($raw);
            if ($code !== null) {
                $normalized[$code] = true;
            }
        }

        $codes = array_keys($normalized);
        if ($codes === []) {
            return [];
        }

        $map = [];

        $catalog = CatalogProduct::query()
            ->whereIn('product_code', $codes)
            ->whereNotNull('product_image_id')
            ->with('image')
            ->get();

        foreach ($catalog as $product) {
            $code = ProductCode::normalize($product->product_code);
            if ($code === null) {
                continue;
            }
            $embed = self::embedFromImage($product->image);
            if ($embed !== null) {
                $map[$code] = $embed;
            }
        }

        $missing = array_diff($codes, array_keys($map));
        if ($missing !== []) {
            $links = ProductImageCode::query()
                ->whereIn('product_code', $missing)
                ->with('image')
                ->get();

            foreach ($links as $link) {
                $code = ProductCode::normalize($link->product_code);
                if ($code === null || isset($map[$code])) {
                    continue;
                }
                $embed = self::embedFromImage($link->image);
                if ($embed !== null) {
                    $map[$code] = $embed;
                }
            }
        }

        foreach ($missing as $code) {
            if (isset($map[$code])) {
                continue;
            }
            $images = ProductImage::query()
                ->where('is_active', true)
                ->whereHas('codes', fn ($q) => $q->where('product_code', $code))
                ->with('codes')
                ->get();
            foreach ($images as $image) {
                $embed = self::embedFromImage($image);
                if ($embed !== null) {
                    $map[$code] = $embed;
                    break;
                }
            }
        }

        return $map;
    }

    private static function embedFromImage(?ProductImage $image): ?string
    {
        if ($image === null || ! $image->is_active) {
            return null;
        }

        return PdfImageEmbed::firstEmbedded([
            $image->file_path,
            $image->thumbnail_path,
        ]);
    }
}