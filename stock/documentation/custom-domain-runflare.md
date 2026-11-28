# دامنه سفارشی (مثلاً chinipay.org)

## Runflare

1. در پنل سرویس barbin، دامنه را به **همان Deployment** وصل کنید (Custom Domain / Route).
2. DNS: رکورد `A` یا `CNAME` طبق راهنمای Runflare به سرویس اشاره کند.
3. در `.env` روی پاد:

```env
APP_URL=https://chinipay.org
```

```bash
php artisan config:cache
```

4. اگر `504 Gateway Timeout` می‌گیرید: دامنه به پاد اشتباه یا سرویس down است — در پنل Runflare مسیر Host را با `barbin-ozh-barbin.runflare.cloud` یکسان کنید.

## تست

```bash
curl -sI https://chinipay.org/up
```

باید `200` باشد (نه 404/504).