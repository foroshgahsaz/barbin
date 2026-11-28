<?php

namespace App\Support;

use Intervention\Image\Laravel\Facades\Image;

final class PdfImageEmbed
{
    /**
     * مسیر مطلق مناسب برای تگ img در mPDF (ترجیح JPEG/PNG).
     */
    public static function absoluteForMpdf(?string $relativePath): ?string
    {
        if (! filled($relativePath)) {
            return null;
        }

        $absolute = MediaPath::absolute((string) $relativePath);
        if ($absolute === null || ! is_readable($absolute)) {
            return null;
        }

        return self::normalizeFormat($absolute);
    }

  /**
   * @param  list<string|null>  $relativeCandidates
   */
    public static function firstEmbedded(array $relativeCandidates): ?string
    {
        foreach ($relativeCandidates as $relative) {
            $path = self::absoluteForMpdf($relative);
            if ($path !== null) {
                return $path;
            }
        }

        return null;
    }

    private static function normalizeFormat(string $absolute): ?string
    {
        $ext = strtolower(pathinfo($absolute, PATHINFO_EXTENSION));

        if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'], true)) {
            return $absolute;
        }

        if ($ext === 'webp') {
            return self::convertToJpeg($absolute);
        }

        return is_readable($absolute) ? $absolute : null;
    }

    private static function convertToJpeg(string $sourceAbsolute): ?string
    {
        $dir = MpdfTempDir::path();
        if (! is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        $target = $dir.'/pdf_embed_'.md5($sourceAbsolute).'.jpg';

        if (is_file($target) && filemtime($target) >= filemtime($sourceAbsolute)) {
            return $target;
        }

        try {
            Image::read($sourceAbsolute)->toJpeg(88)->save($target);

            return is_readable($target) ? $target : null;
        } catch (\Throwable) {
            return null;
        }
    }
}