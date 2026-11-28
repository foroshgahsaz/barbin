<?php

use App\Console\Commands\ResetAdminPasswordCommand;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Permission;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('barbin:verify-deploy', function () {
    $checks = [
        'LaravelLogViewerPage' => file_exists(app_path('Filament/Pages/LaravelLogViewerPage.php')),
        'TelegramDriver' => file_exists(app_path('Services/Messengers/TelegramDriver.php')),
        'JalaliDate' => file_exists(app_path('Support/JalaliDate.php')),
        'StoragePaths (PDF)' => file_exists(app_path('Support/StoragePaths.php')),
        'AnalyticsCharts' => file_exists(app_path('Filament/Widgets/Analytics/StockStatusDoughnutChart.php')),
        'ExecutiveAnalyticsPage' => file_exists(app_path('Filament/Pages/ExecutiveAnalyticsPage.php')),
        'barbin:ensure-storage' => true,
        'MessengerResource' => file_exists(app_path('Filament/Resources/MessengerResource.php')),
        'WarehouseAnalyticsPage' => file_exists(app_path('Filament/Pages/WarehouseAnalyticsPage.php')),
    ];

    $allOk = true;
    foreach ($checks as $name => $ok) {
        $this->line(($ok ? '[OK]' : '[MISSING]').' '.$name);
        if (! $ok) {
            $allOk = false;
        }
    }

    $this->line('Permissions in DB: '.Permission::count());
    $this->line('هدف: همه [OK] — شاخه deploy باید main (بعد از merge PR #15، commit 164d619+).');
    if (is_file(base_path('.git/HEAD'))) {
        $this->line('HEAD: '.trim((string) file_get_contents(base_path('.git/HEAD'))));
    }

    if (! $allOk) {
        $this->error('کد قدیمی است — Runflare را Redeploy کنید از آخرین main.');
    }

    return $allOk ? 0 : 1;
})->purpose('بررسی deploy نسخه جدید');

Artisan::command('barbin:seed-demo {--force : بدون تأیید}', function () {
    if (! $this->option('force') && ! $this->confirm('داده دمو ~۳ ماه اضافه می‌شود (بدون حذف داده فعلی). ادامه؟', true)) {
        return 1;
    }
    $this->call('db:seed', ['--class' => 'Database\\Seeders\\BarbinDemoThreeMonthSeeder', '--force' => true]);
    $this->info('Demo seeded. لاگین: sales@barbin.local / warehouse@barbin.local — password');

    return 0;
})->purpose('سیدر نمایشی ۳ ماه موجودی، فروش و مشتری');

Artisan::command('barbin:ensure-storage', function () {
    \App\Support\StoragePaths::touchStorageTree();
    $probe = 'sales-pdfs/.write-test';
    try {
        \App\Support\StoragePaths::putGeneratedFile($probe, 'ok');
        \Illuminate\Support\Facades\Storage::disk('local')->delete($probe);
        $this->info('storage/app/private قابل نوشتن است.');
    } catch (\Throwable $e) {
        $this->error($e->getMessage());

        return 1;
    }

    return 0;
})->purpose('ساخت پوشه‌های PDF/Excel و بررسی مجوز storage');