<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventorySnapshot extends Model
{
    protected $fillable = [
        'inventory_import_batch_id',
        'uploaded_by',
        'original_filename',
        'recorded_at',
        'items_count',
        'in_stock_count',
        'zero_stock_count',
        'import_status',
        'error_info',
    ];

    protected function casts(): array
    {
        return [
            'recorded_at' => 'datetime',
            'error_info' => 'array',
        ];
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(InventoryImportBatch::class, 'inventory_import_batch_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(InventorySnapshotItem::class);
    }

    public static function latestConfirmed(): ?self
    {
        return self::query()
            ->where('import_status', 'confirmed')
            ->orderByDesc('recorded_at')
            ->first();
    }

    public function stockMapForCodes(iterable $codes): array
    {
        $normalized = collect($codes)->map(fn ($c) => \App\Support\ProductCode::normalize($c))->filter()->unique()->values();

        return $this->items()
            ->whereIn('product_code', $normalized)
            ->pluck('quantity', 'product_code')
            ->all();
    }
}