<?php

namespace App\Services\Pdf;

use App\Models\PdfAccessToken;
use App\Models\SalesFile;
use Illuminate\Support\Str;

class SecurePdfLinkService
{
    public function create(SalesFile $salesFile, ?\DateTimeInterface $expiresAt = null): PdfAccessToken
    {
        return PdfAccessToken::query()->create([
            'sales_file_id' => $salesFile->id,
            'token' => Str::random(48),
            'expires_at' => $expiresAt ?? now()->addDays(7),
        ]);
    }

    public function publicUrl(PdfAccessToken $token): string
    {
        return url('/pdf/'.$token->token);
    }
}