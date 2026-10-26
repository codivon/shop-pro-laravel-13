# مستندات فارسی API نسخهٔ ۱ (v1)

> فاز ۸۵ نقشهٔ راه — «زیرساخت فروش» | آخرین به‌روزرسانی: فاز ۹۵
>
> این سند مرجع رسمی API عمومی فروشگاه است و با کد هسته همگام نگه داشته می‌شود.
> قالب پاسخ‌ها و نام‌ها عیناً از کد استخراج شده‌اند؛ اگر بین سند و رفتار واقعی تفاوتی دیدید، رفتار واقعی ملاک است و لطفاً گزارش دهید.

---

## ۱) مقدمات

### ۱.۱ آدرس پایه (Base URL)
```
https://{دامنهٔ فروشگاه}/api/v1
```

### ۱.۲ هدرهای اجباری
| هدر | مقدار | توضیح |
|---|---|---|
| `Accept` | `application/json` | اجباری — در غیر این صورت درخواست رد می‌شود (Middleware `EnsureApiHeaderIsSet`) |
| `Authorization` | `Bearer {token}` | فقط برای مسیرهای احراز هویت‌شده |
| `Content-Type` | `application/json` | برای بدنهٔ JSON |

> مسیر `payments/verify/{token}` استثنای هدر `Accept` است (callback درگاه است و درگاه هدر نمی‌فرستد).

### ۱.۳ پوشهٔ پاسخ (Response Envelope)
همهٔ پاسخ‌ها در یک پوشهٔ استاندارد برمی‌گردند:

```json
{
  "success": true,
  "message": "پیام اختیاری",
  "result":  { "...": "داده‌ها" }
}
```

پاسخ خطا:
```json
{
  "success": false,
  "message": "پیام خطای فارسی",
  "error_code": 422,
  "errors": { "field": ["پیام اعتبارسنجی"] }
}
```

### ۱.۴ احراز هویت
احراز هویت با **Laravel Sanctum** (توکن شخصی) است. با `POST /api/v1/login` توکن بگیرید و در هدر `Authorization: Bearer ...` بفرستید.

### ۱.۵ محدودسازی نرخ (Throttle)
| مسیر | محدودیت |
|---|---|
| `POST /api/v1/login` | ۱۰ درخواست در دقیقه |
| `GET/POST /api/v1/payments/verify/{token}` | ۲۰ درخواست در دقیقه |
| بقیه | استاندارد فریم‌ورک |

---

## ۲) احراز هویت و کاربر

| متد | مسیر | احراز هویت | توضیح |
|---|---|---|---|
| POST | `/login` | — | ورود؛ خروجی شامل توکن |
| POST | `/register` | — | ثبت‌نام |
| GET | `/logout` | ✅ | خروج و ابطال توکن |
| POST | `/change-password` | ✅ | تغییر گذرواژه |
| GET | `/user` | ✅ | پروفایل کاربر جاری |
| PUT | `/user` | ✅ | ویرایش پروفایل |
| PUT | `/user/address` | ✅ | افزودن/ویرایش آدرس |

---

## ۳) محصولات

### ۳.۱ فهرست محصولات — `GET /products`
| پارامتر | نوع | توضیح |
|---|---|---|
| `per_page` | int | تعداد در صفحه (پیش‌فرض ۲۰) |
| `category_id` | int | فیلتر دسته (شامل زیر‌دسته‌ها) |
| `search` | string | جست‌وجو در عنوان فارسی/انگلیسی |
| `in_stock` | 0/1 | فقط موجودها |
| `special` | 0/1 | فقط ویژه‌ها |
| `discount` | 0/1 | فقط تخفیف‌دارها (فاز ۹۵ — معیار واقعی: قیمت تخفیف‌دارِ فعال و منقضی‌نشده) |
| `min_price` | int | کف قیمت (فاز ۹۵) |
| `max_price` | int | سقف قیمت |
| `sort_field` | string | `view` \| `sale` \| `cheapest` \| `expensivest` (پیش‌فرض: جدیدترین) |

### ۳.۲ سایر مسیرهای محصول
| متد | مسیر | توضیح |
|---|---|---|
| GET | `/products/{slug}` | جزئیات محصول (کلید مسیر = slug) |
| GET | `/products/{slug}/comments` | دیدگاه‌های تاییدشده (صفحه‌بندی) |
| POST | `/products/{slug}/comments` | ثبت دیدگاه (✅ احراز هویت) |
| GET | `/products/{slug}/relatedProducts` | محصولات مرتبط |
| GET | `/products/filters` | **فاز ۹۵** — فرادادهٔ فیلترها |

### ۳.۳ فرادادهٔ فیلترها — `GET /products/filters` (فاز ۹۵)
```json
{
  "success": true,
  "result": {
    "price":  { "min": 10000, "max": 95000000 },
    "categories": [ { "id": 1, "title": "موبایل", "slug": "mobile", "children_count": 4 } ],
    "special_count": 12,
    "discount_count": 48
  }
}
```
- `price` = کف و سقف واقعی قیمت‌های موجود و منتشرشده (قیمت مؤثر = تخفیف فعال در صورت وجود)
- `categories` = دسته‌های ریشه با شمار فرزندان

---

## ۴) جست‌وجوی سراسری — `GET /search` (فاز ۹۵)

| پارامتر | نوع | توضیح |
|---|---|---|
| `q` | string | **الزامی** — حداقل ۲ نویسه، حداکثر ۱۰۰ |
| `per_page` | int | تعداد محصولات در صفحه (پیش‌فرض ۲۰، حداکثر ۱۰۰) |

```json
{
  "success": true,
  "message": "نتایج جست‌وجو",
  "result": {
    "query": "گوشی",
    "products": { "...": "کالکشن صفحه‌بندی‌شدهٔ محصولات (همان ساختار /products)" },
    "products_count": 34,
    "categories": [ { "id": 1, "title": "گوشی موبایل", "slug": "mobile", "category_id": null } ],
    "categories_count": 1,
    "posts": [ { "id": 9, "title": "راهنمای خرید گوشی", "slug": "phone-guide" } ],
    "posts_count": 1
  }
}
```
- محصولات با همان موتور فیلتر `/products` جست‌وجو می‌شوند (عنوان فارسی/انگلیسی)
- دسته‌بندی‌ها و مقالات تا ۵ مورد اول به‌ترتیب

---

## ۵) سبد خرید

| متد | مسیر | توضیح |
|---|---|---|
| GET | `/cart` | محتوای سبد (مهمان: هدر `cart-id`؛ کاربر: سبد حساب) |
| POST | `/cart` | افزودن با `{ "price_id": 123, "quantity": 2 }` |
| DELETE | `/cart/{price_id}` | حذف قیمت از سبد |

قوانین مهم:
- محصول **پیش‌فروش**: فقط ۱ عدد و فقط قیمت منتشرشده
- محصول **دانلودی**: فقط یک‌بار در سبد
- پاسخ خطا با کد ۴۲۲ و پیام فارسی (موجودی ناکافی و ...)

---

## ۶) تسویه‌حساب (Checkout) — فاز ۹۵

### ۶.۱ خلاصهٔ چک‌اوت — `GET /checkout` (✅ احراز هویت)
همهٔ داده‌هایی که کلاینت برای رندر صفحهٔ تسویه‌حساب نیاز دارد، یک‌جا:

| پارامتر | نوع | توضیح |
|---|---|---|
| `city_id` | int | اختیاری — شهر مبنای محاسبهٔ ارسال (پیش‌فرض: شهر آدرس اول کاربر) |

ساختار پاسخ (`result`):
```json
{
  "items": [
    {
      "product_id": 10, "title": "کفش ورزشی", "slug": "shoes-1", "image": "https://...",
      "type": "physical", "quantity": 2, "price": 5000000, "discount_price": 4500000,
      "price_id": 55, "group_id": "store"
    }
  ],
  "download_items": [],
  "groups": [
    {
      "group_id": "store",
      "name": "نام فروشگاه",
      "seller_id": null,
      "total_weight": 800,
      "total_price": 9000000,
      "carriers": [
        {
          "id": 1, "title": "پست پیشتاز", "delivery_time_type": "range",
          "shipping_cost": 45000, "usable": true, "unusable_reason": null,
          "delivery_dates": []
        },
        {
          "id": 2, "title": "تیپاکس", "delivery_time_type": "user_select",
          "shipping_cost": 60000, "usable": true, "unusable_reason": null,
          "delivery_dates": [
            { "date": "2026-11-01", "jalali": "1405/08/10", "display": "شنبه 10 آبان 1405", "is_selectable": true }
          ]
        }
      ],
      "shipping_cost": 0
    }
  ],
  "totals": {
    "physical_total": 9000000,
    "download_total": 0,
    "shipping_total": 45000,
    "discount_amount": 200000,
    "final_price": 8845000
  },
  "weight": 800,
  "addresses": [ { "id": 3, "fullname": "علی رضایی", "mobile": "0912...", "province": "تهران", "city": "تهران", "city_id": 1, "address": "..." } ],
  "payment": {
    "wallet": { "enabled": true, "balance": 2500000 },
    "gateways": [ { "key": "zarinpal", "title": "زرین‌پال" } ],
    "module_gateways": [ { "key": "payyar", "title": "پرداخت‌یار" } ]
  },
  "discount": { "code": "SUMMER", "type": "percent", "amount": 10, "title": "تخفیف تابستان" },
  "city_id": 1
}
```

خطاها (۴۲۲ با پیام فارسی):
- سبد خالی
- تغییر موجودی برخی کالاها از زمان افزودن
- کد تخفیف سبد نامعتبر شده

### ۶.۲ ثبت سفارش — `POST /checkout` (✅ احراز هویت)

**بدنه:**
| فیلد | نوع | الزامی | توضیح |
|---|---|---|---|
| `gateway` | string | ✅ | `wallet` یا کلید درگاه فعال یا کلید درگاه ماژولی (`payyar`/`snappay`/`digipay` در صورت فعال بودن پکیج) |
| `address` | int | برای فیزیکی ✅ | شناسهٔ آدرس — امنیت IDOR: باید مال خود کاربر باشد |
| `carriers` | object | برای فیزیکی ✅* | نقشهٔ گروه → روش ارسال: `{ "store": 1, "seller_3": 4 }` |
| `delivery_dates` | object | فقط روش‌های `user_select` | نقشهٔ گروه → تاریخ `Y-m-d` از `delivery_dates` همان گروه |
| `description` | string | — | یادداشت سفارش (حداکثر ۱۰۰۰) |
| `installment_enabled` / `credit_enabled` | — | ⛔ | در v1 از API پشتیبانی نمی‌شود — ارسال = خطای ۴۲۲ |

\* کلیدهای `carriers` و `delivery_dates` دقیقاً همان `group_id`هایی هستند که در `GET /checkout` داخل `groups` آمده‌اند.

**خروجی موفق (۲۰۱):**
```json
{
  "success": true,
  "message": "پرداخت با موفقیت انجام شد و سفارش شما ثبت شد.",
  "result": {
    "order": { "...": "ریسورس استاندارد سفارش" },
    "paid": true,
    "payment": null
  }
}
```

**سه حالت پرداخت:**

1. **کیف پول (`gateway=wallet`)** — پرداخت آنی کامل:
   - موجودی کافی → رزرو موجودی، کسر کیف پول، سفارش `paid`، ایونت `OrderPaid` (اطلاع‌رسانی‌ها + امتیازها/جوایز پکیج‌ها)، خالی شدن سبد؛ `paid=true` و `payment=null`
   - موجودی ناکافی → ۴۲۲ با پیام فارسی (سفارش ساخته نمی‌شود)
   - *نکته:* پرداخت ترکیبی (کیف پول + مابقی درگاه) در v1 پشتیبانی نمی‌شود؛ برای شارژ کیف پول از نسخهٔ وب استفاده کنید.

2. **درگاه شتابی (کلید درگاه از جدول `gateways`)** — خروجی وب‌ویو:
   ```json
   {
     "success": true,
     "message": "سفارش شما ثبت شد. برای تکمیل خرید به درگاه پرداخت بروید.",
     "result": {
       "order": { "...": "..." },
       "paid": false,
       "payment": {
         "url": "https://gateway.example.com/pay/...",
         "method": "GET",
         "inputs": {},
         "verify_url": "https://{دامنه}/api/v1/payments/verify/{token}"
       }
     }
   }
   ```
   - کلاینت باید وب‌ویو را به `payment.url` بفرستد (با `method` و `inputs` در صورت POST)
   - پس از پرداخت، درگاه به `verify_url` برمی‌گردد و تأیید بدون نشست انجام می‌شود
   - رزرو موجودی در لحظهٔ ساخت لینک پرداخت انجام می‌شود

3. **درگاه ماژولی (`payyar` / `snappay` / `digipay`)**:
   - اگر ماژول ریدایرکت بدهد → `payment.url`
   - اگر ماژول فرم HTML رندر کند → `payment.html` (رشتهٔ HTML کامل برای رندر در وب‌ویو)

**خطاهای رایج (۴۲۲ — پیام فارسی):**
| شرط | پیام |
|---|---|
| روش پرداخت نامعتبر | «روش پرداخت انتخاب‌شده معتبر نیست.» |
| آدرس متعلق به کاربر دیگر (IDOR) | «آدرس انتخاب‌شده یافت نشد.» |
| روش ارسال هر گروه انتخاب نشده | «روش ارسال برای مرسوله {نام} انتخاب نشده است» |
| روش ارسال برای شهر/وزن قابل استفاده نیست | پیام `canUseCarrier` |
| تاریخ تحویل `user_select` نامعتبر | «تاریخ تحویل انتخاب شده برای مرسوله {نام} معتبر نیست» |
| اقساطی/اعتباری از API | «پرداخت اقساطی در حال حاضر از طریق API پشتیبانی نمی‌شود.» |

### ۶.۳ تأیید پرداخت (Callback درگاه) — `GET|POST /payments/verify/{token}`
- **بدون احراز هویت** (استثنای هدر `Accept`) — این روت عیناً `callbackUrl` درگاه است
- پارامترهای برگشتی درگاه باید دست‌نخورده به این مسیر برسند (shetabit آن‌ها را از request می‌خواند)
- موفق:
```json
{
  "success": true,
  "message": "پرداخت با موفقیت تأیید شد.",
  "result": { "order_id": 101, "status": "paid", "price": 8845000, "already_paid": false }
}
```
- ناموفق → `success=false` با پیام خطای درگاه؛ سفارش unpaid می‌ماند
- فراخوانی تکراری → `already_paid: true` (idempotent)

---

## ۷) سفارش‌ها (✅ احراز هویت)

| متد | مسیر | توضیح |
|---|---|---|
| GET | `/orders` | فهرست سفارش‌های کاربر (صفحه‌بندی) |
| GET | `/orders/{id}` | جزئیات سفارش با آیتم‌ها و تراکنش‌ها |

---

## ۸) علاقه‌مندی‌ها (✅ احراز هویت)

| متد | مسیر | توضیح |
|---|---|---|
| GET | `/favorites` | فهرست علاقه‌مندی‌ها |
| POST | `/favorites` | `{ "product_id": 10 }` |
| DELETE | `/favorites` | `{ "product_id": 10 }` |

---

## ۹) سایر مسیرهای عمومی

| متد | مسیر | توضیح |
|---|---|---|
| GET | `/categories` | درخت دسته‌بندی‌ها |
| GET | `/categories/{id}/filter` | فیلترهای دسته |
| GET | `/posts` / `/posts/{slug}` | مقالات وبلاگ |
| GET | `/posts/{slug}/comments` | دیدگاه‌های مقاله |
| GET | `/pages/{slug}` | صفحات ثابت |
| GET | `/provinces` | استان‌ها |
| GET | `/provinces/{id}/cities` | شهرهای استان |
| GET | `/app/settings` | تنظیمات عمومی اپ |
| GET | `/torob/products` | فید Torob |
| GET | `/emalls/products` | فید Emalls |

---

## ۱۰) ماتریس مالکیت داده

| دامنه | نسخهٔ وب | API v1 |
|---|---|---|
| جست‌وجو/فیلتر | ✅ | ✅ فاز ۹۵ (`/search` + `min_price` + `/products/filters`) |
| سبد خرید | ✅ | ✅ (از ابتدا) |
| تسویه‌حساب | ✅ (تم) | ✅ فاز ۹۵ (`/checkout`) |
| پرداخت کیف پول | ✅ | ✅ فاز ۹۵ (کامل، بدون ترکیب) |
| پرداخت درگاه شتابی | ✅ (callback نشستی) | ✅ فاز ۹۵ (callback بدون نشست با توکن) |
| درگاه‌های ماژولی | ✅ | ✅ فاز ۹۵ (url/html برای وب‌ویو) |
| اقساطی / اعتباری | ✅ (پکیج CreditPay) | ⛔ v1 — خطای صریح ۴۲۲ |
| سفارش‌ها | ✅ | ✅ (فقط خواندن) |

---

## ۱۱) نکات پیاده‌سازی برای کلاینت‌ها

1. **ترتیب فراخوانی تسویه‌حساب:** `GET /cart` → (اختیاری) `POST /cart` → `GET /checkout` → `POST /checkout` → در حالت درگاه: وب‌ویو → پرداخت → تأیید خودکار با callback → `GET /orders/{id}` برای نمایش نتیجه.
2. همیشه `carriers[*]` را از `GET /checkout` بخوانید؛ هرگز روش ارسال را حدس نزنید — هزینهٔ ارسال سمت سرور محاسبه می‌شود و مقدار کلاینت نادیده گرفته می‌شود.
3. `delivery_dates[].date` عیناً همان رشته‌ای است که باید در `delivery_dates[group]` ارسال کنید (قالب `Y-m-d`).
4. پاسخ ۴۲۲ همیشه `message` فارسی قابل نمایش مستقیم دارد؛ برای فیلدها `errors` را نمایش دهید.
5. توکن‌ها با Sanctum می‌سازند؛ برای خروج از `GET /logout` استفاده کنید تا توکن ابطال شود.

---

*مستندات در مخزن هسته (`docs/API-DOCUMENTATION.md`) نگهداری می‌شود و با هر فاز به‌روزرسانی می‌گردد.*
