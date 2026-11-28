# اولین اجرا روی سرور / پاد (مثلاً `/var/www`)

## خطای `1071 Specified key was too long` (migrate روی MySQL)

1. در `AppServiceProvider`:

```php
Schema::defaultStringLength(191);
```

2. migration نقش‌ها (Spatie): `name` و `guard_name` طول **125** (در ریپو اصلاح شده).

سپس دیتابیس نیمه‌کاره را پاک کنید و دوباره migrate:

```bash
php artisan config:clear
php artisan migrate:fresh --force --seed
php artisan config:cache
```

اگر بعد از fail جدول `users` مانده: `migrate:fresh` یا `DROP TABLE users, migrations, ...`

## خطای `file_get_contents(.env): No such file`

Laravel قبل از `php artisan key:generate` به فایل **`.env`** نیاز دارد. اول فایل را بسازید، بعد کلید را تولید کنید.

```bash
cd /var/www

# یکی از این دو:
cp .env.example .env
# یا فایل کامل MySQL:
cp stock/documentation/env.mysql.full.example .env

# ویرایش دیتابیس و APP_URL (vi / nano)
php artisan key:generate --force
php artisan migrate --force --seed   # seed فقط بار اول
php artisan config:cache
```

## MySQL در Kubernetes

معمولاً `DB_HOST` نام سرویس داخلی است، نه `127.0.0.1`:

```env
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=barbin
DB_USERNAME=...
DB_PASSWORD=...
```

## پروداکشن

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://دامنه-شما
```

## بدون فایل example (اضطراری)

```bash
touch /var/www/.env
echo 'APP_NAME=Barbin' >> /var/www/.env
echo 'APP_ENV=production' >> /var/www/.env
echo 'APP_KEY=' >> /var/www/.env
echo 'APP_DEBUG=false' >> /var/www/.env
echo 'APP_URL=https://your-domain' >> /var/www/.env
echo 'DB_CONNECTION=mysql' >> /var/www/.env
# ... بقیه متغیرها
php artisan key:generate --force
```

## منوهای Filament نیست / `barbin:reset-admin` پیدا نشد

کد روی پاد **قدیمی** است. از **main** بعد از merge PR #7 دوباره Deploy کنید.

```bash
php artisan barbin:verify-deploy
php artisan barbin:ensure-storage
php artisan barbin:reset-admin
php artisan permission:cache-reset
php artisan optimize:clear
php artisan config:cache
```

**دیباگ:** پنل → تنظیمات → **لاگ خطاها** (فقط مدیر کل)

اگر `verify-deploy` چیزی را `[MISSING]` نشان داد → Runflare **Rebuild** بدون cache image قدیمی.

نقش مدیر: `php artisan tinker --execute="dump(App\\Models\\User::whereEmail('admin@barbin.local')->first()?->getRoleNames());"`

## خطای `POST admin/login` — Method Not Allowed (Filament)

صفحه لاگین Filament فقط **GET** دارد. ارسال فرم باید با **Livewire** به `POST /livewire/update` برود.
اگر مرورگر مستقیم `POST /admin/login` می‌زند، معمولاً **JavaScript Livewire لود نشده** یا **`route:cache` خراب** است.

```bash
php artisan route:clear
php artisan config:clear
php artisan view:clear
php artisan config:cache
# هرگز route:cache نزنید
```

در `.env`:

```env
APP_URL=https://barbin-xn3-barbin.runflare.cloud
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax
```

Document root باید `public/` باشد. در مرورگر Network تب کنید: `livewire/livewire.js` یا **`/vendor/livewire/livewire.js`** باید **200** باشد.

روی هر deploy / پاد حتماً (یا فایل‌ها از Git باشند):

```bash
php artisan livewire:publish --assets --no-interaction
php artisan route:clear
php artisan config:cache
```

`.env` باید با دامنه فعلی یکی باشد، مثلاً:

`APP_URL=https://barbin-ib3-barbin.runflare.cloud`
```