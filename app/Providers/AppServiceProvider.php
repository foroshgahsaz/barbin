<?php

namespace App\Providers;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // MySQL/MariaDB با utf8mb4: ایندکس unique روی varchar(255) از 1000 بایت عبور می‌کند.
        Schema::defaultStringLength(191);
    }
}
