<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\SecurePdfController;
use App\Support\DeployProbe;

Route::get('/deploy-status', function () {
    return response()->json(DeployProbe::report());
});

Route::get('/', function () {
    return redirect('/admin');
});

Route::get('/pdf/{token}', [SecurePdfController::class, 'show'])->name('pdf.secure');
