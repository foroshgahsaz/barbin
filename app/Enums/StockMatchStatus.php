<?php

namespace App\Enums;

enum StockMatchStatus: string
{
    case Pending = 'pending';
    case Matched = 'matched';
    case Zero = 'zero';
    case NotFound = 'not_found';

    public function label(): string
    {
        return \App\Support\StockDisplay::statusLabel($this);
    }
}