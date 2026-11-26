<?php

namespace App\Models;

use App\Enums\ImportBatchStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryImportBatch extends Model
{
    protected $fillable = [
        'uploaded_by',
        'original_filename',
        'stored_path',
        'status',
        'row_count',
        'duplicate_codes_count',
        'invalid_rows_count',
        'zero_stock_count',
        'positive_stock_count',
        'preview_errors',
        'confirmed_at',
        'inventory_snapshot_id',
    ];

    protected function casts(): array
    {
        return [
            'status' => ImportBatchStatus::class,
            'preview_errors' => 'array',
            'confirmed_at' => 'datetime',
        ];
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function snapshot(): BelongsTo
    {
        return $this->belongsTo(InventorySnapshot::class, 'inventory_snapshot_id');
    }
}