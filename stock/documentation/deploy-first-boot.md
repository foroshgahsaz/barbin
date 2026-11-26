# اولین اجرا روی سرور / پاد (مثلاً `/var/www`)

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