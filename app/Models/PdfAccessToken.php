<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PdfAccessToken extends Model
{
    protected $fillable = [
        'sales_file_id',
        'token',
        'expires_at',
        'revoked_at',
        'view_count',
        'download_count',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function salesFile(): BelongsTo
    {
        return $this->belongsTo(SalesFile::class);
    }

    public function isValid(): bool
    {
        if ($this->revoked_at !== null) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }
}