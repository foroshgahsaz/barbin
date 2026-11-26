<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventorySnapshotItem extends Model
{
    protected $fillable = [
        'inventory_snapshot_id',
        'product_code',
        'description',
        'unit',
        'quantity',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'meta' => 'array',
        ];
    }

    public function snapshot(): BelongsTo
    {
        return $this->belongsTo(InventorySnapshot::class, 'inventory_snapshot_id');
    }
}