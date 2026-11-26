<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductImage extends Model
{
    protected $fillable = [
        'owner_user_id',
        'title',
        'file_path',
        'thumbnail_path',
        'mime_type',
        'file_size',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function codes(): HasMany
    {
        return $this->hasMany(ProductImageCode::class);
    }
}