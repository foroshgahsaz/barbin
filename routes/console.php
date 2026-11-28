<?php

use App\Console\Commands\ResetAdminPasswordCommand;
use App\Support\DeployProbe;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Permission;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('barbin:verify-deploy', function () {
    $report = DeployProbe::report();
    $allOk = true;

    foreach ($report['checks'] as $row) {
        $this->line(($row['ok'] ? '[OK]' : '[MISSING]').' '.$row['label']);
        if (! $row['ok']) {
            $allOk = false;
        }
    }

    $this->newLine();
    $this->line($report['expected_merge']);
    $this->line('فونت: '.$report['urls']['peyda_font']);
    $this->line('Permissions in DB: '.Permission::count());

    if (is_file(base_path('.git/HEAD'))) {
        $this->line('HEAD: '.trim((string) file_get_contents(base_path('.git/HEAD'))));
    }

    if (! $allOk) {
        $this->error('کد قدیمی یا ناقص — Runflare را از main Redeploy کنید (بدون کش image).');
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
        foreach (['barbin', 'local'] as $disk) {
            if (\Illuminate\Support\Facades\Storage::disk($disk)->exists($probe)) {
                \Illuminate\Support\Facades\Storage::disk($disk)->delete($probe);
            }
        }
        $this->info('storage/app/private قابل نوشتن است.');
    } catch (\Throwable $e) {
        $this->error($e->getMessage());

        return 1;
    }

    return 0;
})->purpose('ساخت پوشه‌های PDF/Excel و بررسی مجوز storage');