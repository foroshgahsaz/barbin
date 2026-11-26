# راهنمای `.env` محلی

## مراحل

```bash
cp .env.example .env
php artisan key:generate
touch database/database.sqlite   # اگر DB_CONNECTION=sqlite
php artisan migrate --seed
php artisan serve
```

## چه چیز در `.env` است / نیست

| تنظیم | کجا |
|--------|-----|
| `APP_URL` | `.env` — برای لینک امن PDF حتماً دامنه واقعی در پروداکشن |
| `SMS_IR_BASE_URL` | `.env` (اختیاری) |
| SMS.ir API Key، Template، پارامتر الگو | Filament → تنظیمات SMS.ir |
| Bale Bot Token | جدول `messengers` (driver=`bale`) |
| Rubika | `ROUBIKA_SEND_ENDPOINT` + رکورد `messengers` |
| نام شرکت، لوگو PDF | `system_settings` گروه `branding` (فعلاً از seed/دیتابیس) |

## MySQL محلی

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=barbin
DB_USERNAME=root
DB_PASSWORD=
```

سپس: `php artisan migrate --seed`

## Worker (ارسال انبوه بعداً)

```bash
php artisan queue:work --queue=default
```

با `QUEUE_CONNECTION=database` همین کافی است.