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
```