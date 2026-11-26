# Runflare — رفع 404 روی `/` و Laravel

## علت شایع

روی Laravel **ریشه وب باید پوشه `public/` باشد**، نه `/var/www`.

اگر Runflare مسیر را `/var/www` گذاشته باشد، برای `/` فایلی نیست → **404** (یا صفحه پلتفرم).

## تنظیم درست

| مورد | مقدار |
|------|--------|
| Document Root / Web Root | `/var/www/public` |
| PHP | 8.3+ (شما 8.5 هم اوکی است) |
| `.env` | `APP_URL=https://barbin-xn3-barbin.runflare.cloud` |

## تست از داخل پاد

```bash
cd /var/www
php artisan route:list --path=/ 

# باید GET / → redirect به admin
curl -sI http://127.0.0.1:8000/  # اگر artisan serve دارید
# یا از داخل کانتینر:
curl -sI -H "Host: barbin-xn3-barbin.runflare.cloud" http://127.0.0.1/
```

```bash
# سلامت Laravel (بدون Filament)
curl -sI https://barbin-xn3-barbin.runflare.cloud/up
# انتظار: HTTP 200
```

- `/up` هم 404 → Laravel اصلاً از `public/index.php` اجرا نمی‌شود.
- فقط `/` 404 و `/admin` کار می‌کند → مسیر ingress روی Runflare را چک کنید.

## Permission

```bash
chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache
chmod -R ug+rwx /var/www/storage /var/www/bootstrap/cache
```

## بعد از deploy

```bash
php artisan route:clear
php artisan config:clear
php artisan config:cache
# route:cache نزنید
```

## مسیرهای مهم

| URL | انتظار |
|-----|--------|
| `/` | ریدایرکت 302 به `/admin` |
| `/admin` | صفحه لاگین Filament |
| `/up` | 200 OK |
| `/livewire/livewire.js` | 200 (برای لاگین) |

## Nginx نمونه (reference)

```nginx
root /var/www/public;
index index.php;

location / {
    try_files $uri $uri/ /index.php?$query_string;
}

location ~ \.php$ {
    fastcgi_pass unix:/run/php/php8.3-fpm.sock;
    fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
    include fastcgi_params;
}
```