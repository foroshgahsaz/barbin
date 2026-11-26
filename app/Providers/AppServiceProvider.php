<?php

namespace App\Providers;

use App\Models\SalesFile;
use App\Policies\SalesFilePolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
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

        if (config('app.env') === 'production' || str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }

        Gate::policy(SalesFile::class, SalesFilePolicy::class);
    }
}
