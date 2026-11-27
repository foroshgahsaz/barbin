# سیدر دمو ۳ ماهه

## اجرا روی سرور

```bash
cd /var/www
php artisan barbin:seed-demo --force
php artisan permission:cache-reset
```

## خروجی

- حدود **۱۳ Snapshot موجودی** در ۹۰ روز (هفتگی)
- **۶ فایل فروش** با ردیف‌های تطبیق‌شده
- **۱۰ مشتری** در ۴ گروه
- **لاگ ارسال** نمایشی
- کاربران:
  - `warehouse@barbin.local` / `password` (مسئول انبار)
  - `sales@barbin.local` / `password` (مدیر فروش)

مدیر کل: `admin@barbin.local` / `password`

## مشاهده در پنل

- **تحلیل انبار** → روند Snapshot، ABC، بحرانی، مازاد
- **تاریخچه موجودی**
- **فایل‌های فروش** (با کاربر sales)
- **تاریخچه ارسال**