<?php

namespace App\Services\Sales;

use App\Enums\SalesFileStatus;
use App\Enums\StockMatchStatus;
use App\Models\InventorySnapshot;
use App\Models\SalesFile;
use App\Models\SalesFileItem;
use App\Models\User;
use App\Services\Excel\ColumnResolver;
use App\Support\NumericCell;
use App\Support\ProductCode;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Services\Excel\SpreadsheetReader;

class SalesFileImportService
{
    public function __construct(
        private readonly SalesInventoryMatcher $matcher,
    ) {}

    public function import(User $user, string $title, string $originalFilename, string $storedPath): SalesFile
    {
        $snapshot = InventorySnapshot::latestConfirmed();

        $absolute = Storage::disk('local')->path($storedPath);
        $parsed = $this->parseSalesRows($absolute);

        return DB::transaction(function () use ($user, $title, $originalFilename, $storedPath, $snapshot, $parsed): SalesFile {
            $salesFile = SalesFile::query()->create([
                'user_id' => $user->id,
                'title' => $title,
                'original_filename' => $originalFilename,
                'source_path' => $storedPath,
                'product_count' => count($parsed),
                'processing_status' => SalesFileStatus::Processing,
                'inventory_snapshot_id' => $snapshot?->id,
            ]);

            $stockMap = $snapshot ? $snapshot->stockMapForCodes(collect($parsed)->pluck('product_code')) : [];

            foreach ($parsed as $row) {
                $match = $this->matcher->match($row['product_code'], $stockMap, $snapshot !== null);
                SalesFileItem::query()->create([
                    'sales_file_id' => $salesFile->id,
                    'row_number' => $row['row_number'],
                    'product_code' => $row['product_code'],
                    'description' => $row['description'],
                    'unit' => $row['unit'],
                    'price' => $row['price'],
                    'stock_quantity' => $match['quantity'],
                    'stock_status' => $match['status'],
                    'match_notes' => $match['notes'],
                ]);
            }

            $salesFile->update(['processing_status' => SalesFileStatus::Ready]);

            return $salesFile->fresh(['items']);
        });
    }

    /**
     * @return list<array{row_number: int, product_code: string, description: ?string, unit: ?string, price: ?float}>
     */
    public function parseSalesRows(string $absolutePath): array
    {
        $rows = SpreadsheetReader::rows($absolutePath);
        if ($rows === []) {
            return [];
        }

        $header = array_map(fn ($c) => (string) $c, $rows[0]);
        $config = config('barbin.sales');

        $codeIdx = ColumnResolver::resolveIndex($header, $config['code_headers']);
        $descIdx = ColumnResolver::resolveIndex($header, $config['description_headers']);
        $unitIdx = ColumnResolver::resolveIndex($header, $config['unit_headers']);
        $priceIdx = ColumnResolver::resolveIndex($header, $config['price_headers']);

        if ($codeIdx === null) {
            throw new \InvalidArgumentException('ستون کد کالا در فایل فروش یافت نشد.');
        }

        $parsed = [];
        $rowNumber = 0;

        foreach (array_slice($rows, 1) as $row) {
            $rowNumber++;
            $code = ProductCode::normalize($row[$codeIdx] ?? null);
            if ($code === null) {
                continue;
            }

            $price = $priceIdx !== null ? NumericCell::toFloat($row[$priceIdx] ?? null) : null;

            $parsed[] = [
                'row_number' => $rowNumber,
                'product_code' => $code,
                'description' => $descIdx !== null ? trim((string) ($row[$descIdx] ?? '')) : null,
                'unit' => $unitIdx !== null ? trim((string) ($row[$unitIdx] ?? '')) : null,
                'price' => $price,
            ];
        }

        return $parsed;
    }
}