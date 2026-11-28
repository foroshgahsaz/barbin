<?php

use App\Console\Commands\ResetAdminPasswordCommand;
use App\Support\DeployProbe;
use App\Support\StorageDiagnostics;
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

Artisan::command('barbin:test-pdf {sales_file_id? : شناسه فایل فروش}', function () {
    $id = $this->argument('sales_file_id');
    $salesFile = $id
        ? \App\Models\SalesFile::query()->find($id)
        : \App\Models\SalesFile::query()->latest('id')->first();

    if (! $salesFile) {
        $this->error('فایل فروشی در دیتابیس نیست.');

        return 1;
    }

    $this->line('Sales file #'.$salesFile->id.' — '.$salesFile->title);

    try {
        $path = app(\App\Services\Pdf\SalesPdfGenerator::class)->generate($salesFile);
        $absolute = \App\Support\StoragePaths::absolutePath($path);
        $size = is_file($absolute) ? filesize($absolute) : 0;
        $this->info('PDF_OK');
        $this->line('relative: '.$path);
        $this->line('absolute: '.$absolute);
        $this->line('bytes: '.$size);

        return 0;
    } catch (\Throwable $e) {
        $this->error('PDF_FAIL: '.$e->getMessage());

        return 1;
    }
})->purpose('تست تولید PDF بدون tinker');

Artisan::command('barbin:storage-diagnose', function () {
    $report = StorageDiagnostics::run();
    $this->line(StorageDiagnostics::formatForConsole($report));

    $allOk = collect($report['write_probes'])->every(fn (array $p) => $p['ok']);

    return $allOk ? 0 : 1;
})->purpose('خطایابی مجوز storage (Runflare)');

Artisan::command('barbin:storage-repair', function () {
  if (trim((string) shell_exec('whoami 2>/dev/null')) !== 'root') {
    $this->warn('این دستور فقط با root در shell پاد مؤثر است (ترمینال Runflare).');
    $this->line('خروجی دستورات پیشنهادی:');
    $report = StorageDiagnostics::run();
    foreach ($report['fix_as_root'] as $cmd) {
      $this->line('  '.$cmd);
    }
    return 1;
  }

  $report = StorageDiagnostics::run();
  foreach ($report['fix_as_root'] as $cmd) {
    $this->line('$ '.$cmd);
    passthru($cmd, $code);
    if ($code !== 0) {
      $this->error('دستور ناموفق: '.$cmd);
    }
  }

  return $this->call('barbin:ensure-storage');
})->purpose('تعمیر chown/chmod storage (root در پاد)');

Artisan::command('barbin:ensure-storage', function () {
    \App\Support\LoggingPaths::ensureWritableTree();

    \App\Support\StoragePaths::touchStorageTree();

    $publicRoot = (string) config('filesystems.disks.public.root');
    if ($publicRoot !== '' && ! is_dir($publicRoot)) {
        @mkdir($publicRoot, 0775, true);
    }
  $link = public_path('storage');
    if ($publicRoot !== '' && ! file_exists($link) && is_dir($publicRoot)) {
        if (@symlink($publicRoot, $link)) {
            $this->info('Symlink public/storage → '.$publicRoot);
        }
    }
    foreach (['product-images', 'branding'] as $dir) {
        @mkdir($publicRoot.'/'.$dir, 0775, true);
    }
    $probe = 'sales-pdfs/.write-test';
    try {
        \App\Support\StoragePaths::putGeneratedFile($probe, 'ok');
        foreach (['barbin', 'local'] as $disk) {
            if (\Illuminate\Support\Facades\Storage::disk($disk)->exists($probe)) {
                \Illuminate\Support\Facades\Storage::disk($disk)->delete($probe);
            }
        }
        $this->info('storage/app/private قابل نوشتن است.');
        $this->line('barbin root: '.(string) config('filesystems.disks.barbin.root'));
        $this->line('local root: '.(string) config('filesystems.disks.local.root'));
    } catch (\Throwable $e) {
        $this->error($e->getMessage());
        $this->newLine();
        $this->call('barbin:storage-diagnose');

        return 1;
    }

    return 0;
})->purpose('ساخت پوشه‌های PDF/Excel و بررسی مجوز storage');

Artisan::command('barbin:publish-product-images', function () {
    $published = 0;
    foreach (\App\Models\ProductImage::query()->cursor() as $image) {
        foreach (['file_path', 'thumbnail_path'] as $field) {
            $rel = $image->{$field};
            if (! filled($rel)) {
                continue;
            }
            if (\Illuminate\Support\Facades\Storage::disk('public')->exists($rel)) {
                continue;
            }
            $src = null;
            foreach (['local', 'barbin'] as $disk) {
                if (\Illuminate\Support\Facades\Storage::disk($disk)->exists($rel)) {
                    $src = $disk;
                    break;
                }
            }
            if ($src === null) {
                $this->warn('یافت نشد: '.$rel);

                continue;
            }
            $dir = dirname(str_replace('\\', '/', $rel));
            if ($dir !== '.' && $dir !== '') {
                @\Illuminate\Support\Facades\Storage::disk('public')->makeDirectory($dir);
            }
            $binary = \Illuminate\Support\Facades\Storage::disk($src)->get($rel);
            if (\Illuminate\Support\Facades\Storage::disk('public')->put($rel, $binary)) {
                $published++;
                $this->line('→ public: '.$rel);
            }
        }
    }
    $this->info("منتشر شد: {$published} فایل");
    $this->call('barbin:ensure-storage');

    return 0;
})->purpose('کپی تصاویر محصول از دیسک خصوصی به public/storage برای URL وب');