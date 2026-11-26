<?php

namespace App\Http\Controllers;

use App\Models\PdfAccessToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
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

        if (! $salesFile?->pdf_path || ! Storage::disk('local')->exists($salesFile->pdf_path)) {
            abort(404, 'فایل PDF یافت نشد.');
        }

        return Storage::disk('local')->download(
            $salesFile->pdf_path,
            $salesFile->title.'.pdf',
            ['Content-Type' => 'application/pdf']
        );
    }
}