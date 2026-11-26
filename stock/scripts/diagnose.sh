#!/usr/bin/env bash
# Barbin — نمایش خطاها و وضعیت از ترمینال (روی پاد / سرور)
set -u

ROOT="${1:-/var/www}"
cd "$ROOT" || { echo "مسیر یافت نشد: $ROOT"; exit 1; }

echo "========== Barbin diagnose @ $(date -u '+%Y-%m-%d %H:%M:%S UTC') =========="
echo "PWD: $ROOT"
echo ""

echo "---------- PHP ----------"
php -v 2>/dev/null || echo "php not in PATH"
echo ""

echo "---------- .env ----------"
if [ -f .env ]; then
  grep -E '^(APP_|DB_|LOG_|SESSION_|QUEUE_)' .env | grep -v PASSWORD || true
else
  echo "فایل .env وجود ندارد!"
fi
echo ""

echo "---------- Laravel log (آخرین ۸۰ خط) ----------"
LOG="storage/logs/laravel.log"
if [ -f "$LOG" ]; then
  tail -n 80 "$LOG"
else
  echo "لاگ هنوز ساخته نشده: $LOG"
  ls -la storage/logs/ 2>/dev/null || echo "storage/logs/ وجود ندارد"
fi
echo ""

echo "---------- Artisan: migrate status ----------"
php artisan migrate:status 2>&1 || true
echo ""

echo "---------- Artisan: config (فقط کلیدها) ----------"
php artisan about 2>&1 | head -40 || true
echo ""

echo "---------- Routes (/, admin, up) ----------"
php artisan route:list --path=/ --columns=method,uri,name 2>&1 | head -5 || true
php artisan route:list --path=admin --columns=method,uri 2>&1 | head -8 || true
php artisan route:list --path=up 2>&1 | head -5 || true
echo ""

echo "---------- Permission storage/cache ----------"
ls -ld storage bootstrap/cache 2>/dev/null
echo ""

echo "---------- curl داخلی (اگر وب‌سرور روی 80 است) ----------"
for url in "http://127.0.0.1/up" "http://127.0.0.1/" "http://127.0.0.1/health.html"; do
  echo ">> $url"
  curl -sI -m 3 "$url" 2>&1 | head -3 || echo "curl failed"
done
echo ""

echo "---------- آخرین خطاهای PHP-FPM (مسیرهای رایج) ----------"
for f in /var/log/php*-fpm.log /var/log/php8.*/fpm.log /tmp/php-fpm.log; do
  [ -f "$f" ] && echo "=== $f ===" && tail -n 30 "$f"
done 2>/dev/null
echo "========== پایان =========="