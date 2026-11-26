# Barbin — سیستم موجودی، لیست فروش، PDF و اطلاع‌رسانی

Laravel 12 + Filament 3 برای مدیریت موجودی انبار، فایل‌های فروش، PDF فارسی، مشتریان، SMS.ir و پیام‌رسان‌ها.

## نصب

```bash
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
php artisan serve
```

پنل: `/admin` — `admin@barbin.local` / `password` (پس از ورود تغییر دهید)

مستندات انبار: [`stock/README.md`](stock/README.md)

```bash
php artisan test
php artisan queue:work   # برای ارسال پیامک انبوه
```

## پنل Filament (خلاصه ماژول‌ها)

- انبار: بارگذاری موجودی، Snapshot، تحلیل انبار
- فروش: فایل فروش، PDF، Excel نهایی، پیامک/کانال، تصاویر، مشتریان، گروه‌ها
- تنظیمات: کاربران، SMS.ir، پیام‌رسان‌ها، برندینگ PDF