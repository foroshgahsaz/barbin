<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\SecurePdfController;

Route::get('/', function () {
    return redirect('/admin');
});

Route::get('/pdf/{token}', [SecurePdfController::class, 'show'])->name('pdf.secure');
