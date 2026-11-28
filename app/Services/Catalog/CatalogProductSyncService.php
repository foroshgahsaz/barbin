<?php

namespace App\Services\Catalog;

use App\Models\CatalogProduct;
use App\Support\ProductCode;

class CatalogProductSyncService
{
    /**
     * @param  list<array{product_code: string, description: ?string, unit: ?string, quantity: float, purchase_price: ?float}>  $rows
     * @return array{added: int, skipped_existing: int}
     */
    public function syncFromImportRows(array $rows, bool $seedCatalogReference): array
    {
        $catalogEmpty = ! CatalogProduct::query()->exists();

        if ($catalogEmpty && ! $seedCatalogReference) {
            return ['added' => 0, 'skipped_existing' => 0];
        }

        $added = 0;
        $skipped = 0;

        foreach ($rows as $row) {
            $code = ProductCode::normalize($row['product_code'] ?? null);
            if ($code === null) {
                continue;
            }

            if (CatalogProduct::query()->where('product_code', $code)->exists()) {
                $skipped++;

                continue;
            }

            CatalogProduct::query()->create([
                'product_code' => $code,
                'description' => filled($row['description'] ?? null) ? trim((string) $row['description']) : null,
                'unit' => filled($row['unit'] ?? null) ? trim((string) $row['unit']) : null,
                'product_image_id' => null,
            ]);
            $added++;
        }

        return ['added' => $added, 'skipped_existing' => $skipped];
    }
}