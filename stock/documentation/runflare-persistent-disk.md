# دیسک دائم Runflare (PDF / Excel / بارگذاری)

بدون دیسک دائم، پوشه `storage/app` داخل کانتینر ممکن است **قابل نوشتن نباشد** یا با هر Redeploy پاک شود.

## پیشنهاد در پنل Runflare

| فیلد | مقدار پیشنهادی |
|------|----------------|
| **نام دیسک** | `barbin-storage` (هر نام انگلیسی یکتا) |
| **حجم** | حداقل ۲ گیگ (۵ گیگ برای آرامش) |
| **متصل به (mount)** | **`/var/www/storage/app`** |

با این mount نیاز به تغییر `.env` نیست؛ همان دیسک `barbin` کار می‌کند.

بعد از اتصال دیسک، داخل پاد:

```bash
php artisan barbin:ensure-storage
php artisan config:cache
```

## روش دوم (mount جدا)

اگر Runflare فقط مسیر دیگری می‌دهد (مثلاً `/mnt/runflare-disk`):

1. دیسک را بسازید (نام: `barbin-data`)
2. mount به `/mnt/barbin-data`
3. در `.env` سرویس:

```env
BARBIN_FILES_DISK=barbin
BARBIN_STORAGE_ROOT=/mnt/barbin-data
# اختیاری — همان دیسک زیرپوشه private:
BARBIN_PRIVATE_ROOT=/mnt/barbin-data/private
```

```bash
php artisan barbin:ensure-storage
php artisan optimize:clear
php artisan config:cache
```

## پوشه‌هایی که خودکار ساخته می‌شوند

- `sales-pdfs/`
- `sales-exports/`
- `sales-uploads/`
- `inventory-uploads/`
- `product-images/`

## تلگرام — ارسال فایل فروش

**ارسال به کانال** قبل از پیام، در صورت نیاز PDF را می‌سازد و با `sendDocument` همراه caption می‌فرستد. اگر فقط متن آمد، یعنی فایل روی دیسک نبوده (همان خطای storage).