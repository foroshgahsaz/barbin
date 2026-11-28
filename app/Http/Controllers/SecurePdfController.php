<?php

namespace App\Http\Controllers;

use App\Models\PdfAccessToken;
use Illuminate\Http\Request;
use App\Support\StoragePaths;
use Symfony\Component\HttpFoundation\Response;

class SecurePdfController extends Controller
{
    public function show(string $token, Request $request): Response
    {
        $access = PdfAccessToken::query()->where('token', $token)->firstOrFail();

        if (! $access->isValid()) {
            abort(410, 'لینک منقضی یا لغو شده است.');
        }

        $access->increment('download_count');
        $salesFile = $access->salesFile;

        if (! $salesFile?->pdf_path || ! StoragePaths::exists($salesFile->pdf_path)) {
            abort(404, 'فایل PDF یافت نشد.');
        }

        return StoragePaths::downloadResponse(
            $salesFile->pdf_path,
            $salesFile->title.'.pdf',
        );
    }
}