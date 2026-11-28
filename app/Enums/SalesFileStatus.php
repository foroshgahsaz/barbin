<?php

namespace App\Enums;

enum SalesFileStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Ready = 'ready';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'در انتظار',
            self::Processing => 'در حال پردازش',
            self::Ready => 'آماده',
            self::Failed => 'خطا',
        };
    }
}