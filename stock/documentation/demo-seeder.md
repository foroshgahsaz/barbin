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
- کاربران (رمز همه: `password`):
  - `admin@barbin.local` — مدیر کل
  - `warehouse@barbin.local` — انبار
  - `sales@barbin.local` — فروش
  - `viewer@barbin.local` — گزارش مدیریتی (فقط خواندنی)
  - `operator@barbin.local` — اپراتور فروش (+ ارسال پیام)

Snapshotهای دمو شامل **قیمت خرید** هستند (برای ABC ارزشی و سرمایه خوابیده).

## مشاهده در پنل

- **تحلیل انبار** → روند Snapshot، ABC، بحرانی، مازاد
- **تاریخچه موجودی**
- **فایل‌های فروش** (با کاربر sales)
- **تاریخچه ارسال**