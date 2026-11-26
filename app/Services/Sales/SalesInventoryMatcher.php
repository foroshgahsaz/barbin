<?php

namespace App\Services\Sales;

use App\Enums\StockMatchStatus;
use App\Support\ProductCode;

class SalesInventoryMatcher
{
    /**
     * @param  array<string, float|string>  $stockMap
     * @return array{status: StockMatchStatus, quantity: ?float, notes: ?string}
     */
    public function match(string $productCode, array $stockMap, bool $hasSnapshot): array
    {
        $code = ProductCode::normalize($productCode);
        if ($code === null) {
            return [
                'status' => StockMatchStatus::NotFound,
                'quantity' => null,
                'notes' => 'کد نامعتبر',
            ];
        }

        if (! $hasSnapshot) {
            return [
                'status' => StockMatchStatus::NotFound,
                'quantity' => null,
                'notes' => 'Snapshot موجودی تأیید‌شده وجود ندارد',
            ];
        }

        if (! array_key_exists($code, $stockMap)) {
            return [
                'status' => StockMatchStatus::NotFound,
                'quantity' => null,
                'notes' => 'کد در فایل انبار یافت نشد',
            ];
        }

        $qty = (float) $stockMap[$code];

        if ($qty > 0) {
            return [
                'status' => StockMatchStatus::Matched,
                'quantity' => $qty,
                'notes' => null,
            ];
        }

        return [
            'status' => StockMatchStatus::Zero,
            'quantity' => 0.0,
            'notes' => null,
        ];
    }
}