### Installation and Configuration

##### Execute these commands below, in order

```
1. clone the project
```

```
1. composer install
```

```
2. cp .env-example .env
```

```
4. php artisan migrate --seed
```

```
4. php artisan shop:link
```

after that you must run the command to work queue

```
5. php artisan queue:listen
```

**Register for first time:**

> _http(s)://example.com/login_

#### steps to update

##### pull latest changes using git and run these commands

```
1. git pull origin master
```

```
2. composer i
```

```
2. php artisan optimize:clear
```

```
4. php artisan updater:after
```
# shop-pro

---

## دموی آمادهٔ نصب (فاز ۸۵ — زیرساخت فروش)

با یک دستور، نصب تازهٔ فروشگاه به یک فروشگاه نمونهٔ کامل تبدیل می‌شود:

```bash
php artisan shoppro:demo
```

این دستور نصب می‌کند:
- **۱۰ محصول نمونهٔ فارسی** با قیمت/تخفیف/موجودی واقعی — شامل محصولات فیزیکی، ۲ محصول دانلودی و ۱ پیش‌فروش، در دسته‌های سیدشده
- **۳ مقالهٔ وبلاگ** + **۲ صفحهٔ ثابت** (دربارهٔ ما / تماس با ما)
- **مدیر دمو** در نصب خالی: `demo@shoppro.local` / `demo1234`

گاردهای ایمنی:
- روی فروشگاه واقعی (داده با سابقهٔ فروش) اجرا نمی‌شود — مگر با `--force`
- اجرای تکراری با پرچم `shoppro_demo_installed` بسته می‌شود
- در محیط production تأیید تعاملی می‌گیرد
- اگر سیدر پایه اجرا شده باشد (محصولات فیک بدون فروش)، آن‌ها را با دموی منتخب جایگزین می‌کند

مستندات کامل API: [`docs/API-DOCUMENTATION.md`](docs/API-DOCUMENTATION.md)
