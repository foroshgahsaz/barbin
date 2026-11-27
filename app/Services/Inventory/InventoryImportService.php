<?php

namespace App\Services\Inventory;

use App\Enums\ImportBatchStatus;
use App\Models\InventoryImportBatch;
use App\Models\InventorySnapshot;
use App\Models\InventorySnapshotItem;
use App\Models\User;
use App\Services\Excel\ColumnResolver;
use App\Services\Audit\AuditLogger;
use App\Support\NumericCell;
use App\Support\ProductCode;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Services\Excel\SpreadsheetReader;

class InventoryImportService
{
    /**
     * @return array{stats: array<string, int>, errors: list<array<string, mixed>>, rows: list<array<string, mixed>>}
     */
    public function parseFile(string $absolutePath): array
    {
        $rows = SpreadsheetReader::rows($absolutePath);

        if ($rows === []) {
            return ['stats' => $this->emptyStats(), 'errors' => [['message' => 'فایل خالی است']], 'rows' => []];
        }

        $header = array_map(fn ($c) => (string) $c, $rows[0]);
        $config = config('barbin.inventory');

        $codeIdx = ColumnResolver::resolveIndex($header, $config['code_headers']);
        $descIdx = ColumnResolver::resolveIndex($header, $config['description_headers']);
        $unitIdx = ColumnResolver::resolveIndex($header, $config['unit_headers']);
        $qtyIdx = ColumnResolver::resolveIndex($header, $config['quantity_headers']);
        $priceIdx = ColumnResolver::resolveIndex($header, $config['purchase_price_headers']);

        $errors = [];
        if ($codeIdx === null || $qtyIdx === null) {
            $errors[] = ['message' => 'ستون کد کالا یا موجودی یافت نشد.'];

            return ['stats' => $this->emptyStats(), 'errors' => $errors, 'rows' => []];
        }

        $parsed = [];
        $seen = [];
        $duplicateCodes = [];
        $invalid = 0;
        $zero = 0;
        $positive = 0;

        foreach (array_slice($rows, 1) as $line => $row) {
            $code = ProductCode::normalize($row[$codeIdx] ?? null);
            if ($code === null) {
                $invalid++;
                $errors[] = ['row' => $line + 2, 'message' => 'کد کالا نامعتبر'];

                continue;
            }

            if (isset($seen[$code])) {
                $duplicateCodes[$code] = true;
            }
            $seen[$code] = true;

            $quantity = NumericCell::toFloat($row[$qtyIdx] ?? 0) ?? 0.0;
            if ($quantity > 0) {
                $positive++;
            } else {
                $zero++;
            }

            $purchasePrice = null;
            if ($priceIdx !== null) {
                $rawPrice = $row[$priceIdx] ?? null;
                $purchasePrice = is_numeric($rawPrice) ? round((float) $rawPrice, 2) : null;
            }

            $parsed[] = [
                'product_code' => $code,
                'description' => $descIdx !== null ? trim((string) ($row[$descIdx] ?? '')) : null,
                'unit' => $unitIdx !== null ? trim((string) ($row[$unitIdx] ?? '')) : null,
                'quantity' => $quantity,
                'purchase_price' => $purchasePrice,
            ];
        }

        foreach (array_keys($duplicateCodes) as $dup) {
            $errors[] = ['message' => "کد تکراری: {$dup}", 'type' => 'duplicate'];
        }

        return [
            'stats' => [
                'row_count' => count($parsed),
                'duplicate_codes_count' => count($duplicateCodes),
                'invalid_rows_count' => $invalid,
                'zero_stock_count' => $zero,
                'positive_stock_count' => $positive,
            ],
            'errors' => $errors,
            'rows' => $parsed,
        ];
    }

    public function createPreview(User $user, string $originalFilename, string $storedPath): InventoryImportBatch
    {
        $absolute = Storage::disk('local')->path($storedPath);
        $result = $this->parseFile($absolute);

        return InventoryImportBatch::query()->create([
            'uploaded_by' => $user->id,
            'original_filename' => $originalFilename,
            'stored_path' => $storedPath,
            'status' => ImportBatchStatus::Preview,
            'row_count' => $result['stats']['row_count'],
            'duplicate_codes_count' => $result['stats']['duplicate_codes_count'],
            'invalid_rows_count' => $result['stats']['invalid_rows_count'],
            'zero_stock_count' => $result['stats']['zero_stock_count'],
            'positive_stock_count' => $result['stats']['positive_stock_count'],
            'preview_errors' => $result['errors'],
        ]);
    }

    public function confirm(InventoryImportBatch $batch): InventorySnapshot
    {
        if ($batch->status === ImportBatchStatus::Confirmed) {
            return $batch->snapshot ?? throw new \RuntimeException('Snapshot missing');
        }

        $absolute = Storage::disk('local')->path($batch->stored_path);
        $result = $this->parseFile($absolute);

        if ($result['stats']['duplicate_codes_count'] > 0) {
            throw new \InvalidArgumentException('تا زمان رفع کدهای تکراری، تأیید مجاز نیست.');
        }

        return DB::transaction(function () use ($batch, $result, $absolute): InventorySnapshot {
            $snapshot = InventorySnapshot::query()->create([
                'inventory_import_batch_id' => $batch->id,
                'uploaded_by' => $batch->uploaded_by,
                'original_filename' => $batch->original_filename,
                'recorded_at' => now(),
                'items_count' => $result['stats']['row_count'],
                'in_stock_count' => $result['stats']['positive_stock_count'],
                'zero_stock_count' => $result['stats']['zero_stock_count'],
                'import_status' => 'confirmed',
                'error_info' => $result['errors'],
            ]);

            foreach (array_chunk($result['rows'], 500) as $chunk) {
                $insert = array_map(fn (array $row) => [
                    'inventory_snapshot_id' => $snapshot->id,
                    'product_code' => $row['product_code'],
                    'description' => $row['description'],
                    'unit' => $row['unit'],
                    'quantity' => $row['quantity'],
                    'purchase_price' => $row['purchase_price'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ], $chunk);

                InventorySnapshotItem::query()->insert($insert);
            }

            AuditLogger::log('inventory.confirmed', $snapshot, ['items' => $snapshot->items_count]);

            $batch->update([
                'status' => ImportBatchStatus::Confirmed,
                'confirmed_at' => now(),
                'inventory_snapshot_id' => $snapshot->id,
                'row_count' => $result['stats']['row_count'],
                'duplicate_codes_count' => $result['stats']['duplicate_codes_count'],
                'invalid_rows_count' => $result['stats']['invalid_rows_count'],
                'zero_stock_count' => $result['stats']['zero_stock_count'],
                'positive_stock_count' => $result['stats']['positive_stock_count'],
                'preview_errors' => $result['errors'],
            ]);

            return $snapshot;
        });
    }

  private function emptyStats(): array
    {
        return [
            'row_count' => 0,
            'duplicate_codes_count' => 0,
            'invalid_rows_count' => 0,
            'zero_stock_count' => 0,
            'positive_stock_count' => 0,
        ];
    }
}