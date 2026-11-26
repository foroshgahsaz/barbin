<?php

namespace App\Enums;

enum StockMatchStatus: string
{
    case Pending = 'pending';
    case Matched = 'matched';
    case Zero = 'zero';
    case NotFound = 'not_found';
}