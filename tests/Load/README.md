# تست بار خارجی (k6 / Apache Bench)

تست‌های همزمانی داخل PHPUnit (`tests/Feature/Scale/ConcurrentUsersTest.php`) زیرساخت
سندباکس محدودی دارند (۲ هسته). برای سنجش واقعی **۱۰۰۰ تا ۵۰هزار کاربر همزمان** روی
سرور واقعی (nginx + php-fpm + opcache + MariaDB)، از ابزارهای زیر استفاده کنید:

---

## ۱) k6 — تست بار حرفه‌ای (پیشنهادی)

### نصب
```bash
sudo apt install k6        # دبیان/اوبونتو
brew install k6            # مک
# یا: https://k6.io/docs/get-started/installation/
```

### اجرای ۱۰۰۰ کاربر همزمان
```bash
k6 run -e BASE_URL=https://shop.opashop.ir tests/Load/k6-shop-load.js
```

سناریو: طی ۹ دقیقه از ۲۵۰ کاربر به **۱۰۰۰ کاربر همزمان** می‌رسد، ۳ دقیقه در اوج می‌ماند و پایین می‌آید.

### آستانه‌های SLO (رد شدن = کد خروج غیرصفر، مناسب CI/CD)
- خطای HTTP کمتر از ۱٪
- ۹۵٪ درخواست‌ها زیر ۳ ثانیه
- ۹۹٪ درخواست‌ها زیر ۵ ثانیه

### سناریوی بزرگ‌تر (مثلاً ۵۰۰۰ کاربر)
```bash
k6 run -e BASE_URL=https://shop.opashop.ir -e MAX_VUS=5000 tests/Load/k6-shop-load.js
```

> ⚠️ قبل از تست بار روی سایت زنده، از تیم هاستینگ اجازه بگیرید و در ساعات کم‌ترافیک اجرا کنید.

---

## ۲) Apache Bench — تست سریع بدون نصب

```bash
./tests/Load/run-ab.sh https://shop.opashop.ir 1000 100
#                        آدرس سایت          کل   همزمانی
```

معیارها:
- `Requests per second` — توان پردازش (بالاتر بهتر)
- `Failed requests` — باید **0** باشد
- `Time per request` (میانگین) — زیر ۱ ثانیه خوب است

---

## ۳) چک‌لیست حین تست بار

| چه چیزهایی را همزمان مانیتور کنید | ابزار |
|---|---|
| CPU و RAM سرور | `htop` / مانیتورینگ هاست |
| تعداد اتصال فعال MariaDB | `SHOW PROCESSLIST;` |
| Slow query ها | `slow_query_log = ON` |
| خطاهای PHP | `storage/logs/laravel.log` |
| خطای 502/503 | access log nginx |

## ۴) اگر تست بار رد شد

1. **k6 گزارش p95 بالای ۳ ثانیه** → معمولاً N+1 یا نبود ایندکس؛ فایل
   `tests/Feature/Performance/PerformanceTest.php` و `tests/Feature/Scale/MillionScaleTest.php`
   را اجرا کنید تا کوئری مقصر پیدا شود.
2. **خطای 502** → php-fpm کم‌ظرفیت است؛ `pm.max_children` را بالا ببرید.
3. **خطای Too many connections** → `max_connections` MariaDB یا اتصال‌دهی pool.
4. **OOM** → RAM سرور یا `memory_limit` PHP.
