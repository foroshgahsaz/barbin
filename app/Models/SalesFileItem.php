<?php

namespace App\Models;

use App\Enums\StockMatchStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesFileItem extends Model
{
    protected $fillable = [
        'sales_file_id',
        'row_number',
        'product_code',
        'description',
        'unit',
        'price',
        'stock_quantity',
        'stock_status',
        'match_notes',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'stock_quantity' => 'decimal:4',
            'stock_status' => StockMatchStatus::class,
        ];
    }

    public function salesFile(): BelongsTo
    {
        return $this->belongsTo(SalesFile::class);
    }
}