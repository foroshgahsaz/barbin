<?php

namespace App\Models;

use App\Enums\SalesFileStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalesFile extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'original_filename',
        'source_path',
        'output_excel_path',
        'pdf_path',
        'pdf_generated_at',
        'product_count',
        'processing_status',
        'inventory_snapshot_id',
        'notification_status',
        'processing_errors',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'processing_status' => SalesFileStatus::class,
            'pdf_generated_at' => 'datetime',
            'processing_errors' => 'array',
            'meta' => 'array',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function inventorySnapshot(): BelongsTo
    {
        return $this->belongsTo(InventorySnapshot::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SalesFileItem::class)->orderBy('row_number');
    }

    public function deliveryLogs(): HasMany
    {
        return $this->hasMany(DeliveryLog::class);
    }

    public function pdfTokens(): HasMany
    {
        return $this->hasMany(PdfAccessToken::class);
    }
}