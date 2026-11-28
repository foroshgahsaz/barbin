<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CatalogProduct extends Model
{
    protected $fillable = [
        'product_code',
        'description',
        'unit',
        'product_image_id',
    ];

    public function image(): BelongsTo
    {
        return $this->belongsTo(ProductImage::class, 'product_image_id');
    }

    public function hasImage(): bool
    {
        return $this->product_image_id !== null
            && $this->relationLoaded('image')
                ? ($this->image !== null && $this->image->is_active)
                : ProductImage::query()
                    ->whereKey($this->product_image_id)
                    ->where('is_active', true)
                    ->exists();
    }
}