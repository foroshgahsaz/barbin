<?php

use App\Http\Controllers\SecurePdfController;
use App\Support\DeployProbe;
use App\Support\MediaPath;
use Illuminate\Support\Facades\Route;

Route::get('/deploy-status', function () {
    return response()->json(DeployProbe::report());
});

Route::get('/', function () {
    return redirect('/admin');
});

Route::get('/pdf/{token}', [SecurePdfController::class, 'show'])->name('pdf.secure');

Route::get('/storage/{path}', function (string $path) {
    $path = ltrim(str_replace('\\', '/', $path), '/');
    if (str_contains($path, '..')) {
        abort(404);
    }
    if (! MediaPath::exists($path)) {
        abort(404);
    }
    $absolute = MediaPath::absolute($path);
    if ($absolute === null || ! is_file($absolute)) {
        abort(404);
    }

    $mime = mime_content_type($absolute) ?: 'application/octet-stream';

    return response()->file($absolute, [
        'Cache-Control' => 'public, max-age=86400',
        'Content-Type' => $mime,
    ]);
})->where('path', '.*')->name('storage.fallback');