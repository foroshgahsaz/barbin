# Barbin MVP — فهرست قابلیت‌ها

## نقش‌ها

| نقش | کاربر دمو |
|-----|-----------|
| super_admin | admin@barbin.local |
| warehouse_manager | warehouse@barbin.local |
| sales_manager | sales@barbin.local |
| sales_operator | operator@barbin.local (+ notify اختیاری) |
| organization_viewer | viewer@barbin.local |

رمز همه: `password`

## گزارش‌ها (پنل)

### تحلیل انبار (`WarehouseAnalyticsPage`)

1. خلاصه Snapshot
2. روند ۹۰ روز
3. تفاوت دو Snapshot آخر
4. عدم تغییر موجودی
5. بحرانی (۱–۵) — `reports.warehouse_ops`
6. پیشنهاد خرید
7. مازاد عددی / ارزشی
8. کالای صفر موجودی
9. کندگردش (بدون حضور در لیست فروش)
10. ABC مقداری
11. ABC ارزشی
12. XYZ نوسان موجودی

### گزارش مدیریتی (`ExecutiveAnalyticsPage`) — `reports.readonly_exec`

13. سرمایه خوابیده
14. خلاصه فایل فروش
15. نرخ تطبیق موجودی
16. آمار ارسال پیام
17. پرتکرار در لیست‌ها
18. فایل فروش ماهانه
19. ABC ارزشی خلاصه
20. تغییر Snapshot

## داده

- ستون اختیاری **قیمت خرید** در اکسل موجودی (`قیمت خرید`, `purchase_price`, …)
- سیدر دمو: `php artisan barbin:seed-demo --force`