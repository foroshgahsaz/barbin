<?php

namespace App\Support;

use App\Enums\StockMatchStatus;

final class StockDisplay
{
    public static function statusLabel(?StockMatchStatus $status): string
    {
        return match ($status) {
            StockMatchStatus::Matched => 'موجود',
            StockMatchStatus::Zero => 'صفر',
            StockMatchStatus::NotFound => 'نامشخص',
            StockMatchStatus::Pending => 'در انتظار',
            default => '—',
        };
    }

    public static function quantityForDisplay(?float $quantity, ?StockMatchStatus $status): string
    {
        if ($status === StockMatchStatus::NotFound || $quantity === null) {
            return 'نامشخص';
        }

        return (string) (int) round($quantity);
    }
}