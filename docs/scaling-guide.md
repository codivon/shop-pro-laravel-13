# 🚀 راهنمای مقیاس‌پذیری فروشگاه

> هدف: فروشگاه باید **میلیون‌ها محصول، هزاران دسته‌بندی و میلیون‌ها کاربر** را بدون افت سرعت، لگ یا خطا هندل کند.

این سند نتیجهٔ «موج آماده‌سازی برای مقیاس» است — چه چیزی بهینه شد، چطور تست می‌شود و چه چیزهایی در محیط production باید تنظیم شود.

---

## ۱) کارهایی که انجام شد (کد و دیتابیس)

### ۱-۱) رفع باگ بحرانی کش Observerها ⚡ (مهم‌ترین اصلاح)
- **مشکل**: `CategoryObserver` و `ProductObserver` در **کانستراکتور** خود کش‌ها را پاک می‌کردند. Observerها در هر ریکوئست instantiate می‌شوند → **در هر بازدید، کش منو/شمارنده‌ها می‌سوخت** و درخت هزاران دسته از نو ساخته می‌شد.
- **رفع**: ابطال کش فقط در رویدادهای واقعی `created/updated/deleted` انجام می‌شود.
- **اثر**: درخت منو (هزاران دسته) فقط یک‌بار ساخته و تا ابطال واقعی کش می‌ماند.

### ۱-۲) ستون‌های دینرمال‌شده روی `products` (مایگریشن `scale_readiness_for_millions`)
| ستون | کاربرد |
|---|---|
| `in_stock` | جایگزین subquery سنگین «موجود است؟» به‌ازای هر ردیف |
| `min_price` | جایگزین subquery محاسبهٔ ارزان‌ترین قیمت در مرتب‌سازی/فیلتر |

- نگهداری: `PriceObserver` با **یک کوئری** بعد از هر ذخیره/حذف قیمت آن‌ها را sync می‌کند.
- اصلاح دستی (بعد از آپدیت انبوه انبار / ایمپورت اکسل):
  ```bash
  php artisan products:sync-denormalized
  ```
- اسکوپ‌های `available` / `unavailable` / `orderByStock` / `orderByPrice` حالا روی این ستون‌ها کار می‌کنند (با فالبک خودکار به روش قدیمی اگر ستون‌ها نباشند).

### ۱-۳) ایندکس‌های جدید
- `products`: FULLTEXT `(title, title_en, description)` + `(published, created_at)` + `(category_id, published)` + `(brand_id, published)` + ایندکس `in_stock` و `min_price`
- `prices`: `(product_id, stock)`
- `orders`: `(user_id, created_at)` → «سفارش‌های من»
- `favorites`: UNIQUE `(user_id, product_id)` (تکراری‌ها هم در مایگریشن پاک شدند)

### ۱-۴) جستجو: FULLTEXT به‌جای LIKE
- قبلاً: `LIKE '%q%'` روی title **و description** → **full-scan تضمینی** در میلیون رکورد.
- حالا: `MATCH ... AGAINST (... IN BOOLEAN MODE)` روی ایندکس FULLTEXT (عبارت دقیق + پیشوند آخرین توکن). فقط توکن‌های خیلی کوتاه (<۳ حرف) با LIKE روی title جستجو می‌شوند.
- helper: `front_search_match()` — اگر ایندکس FULLTEXT نباشد یا در محیط تست باشیم، خودکار به LIKE (فقط title) برمی‌گردد.
- **نکتهٔ فنی**: ایندکس FULLTEXT ایندباکس فقط هنگام COMMIT به‌روز می‌شود؛ به همین دلیل در تست‌های تراکنشی، مسیر LIKE فعال است و مسیر FULLTEXT با دادهٔ واقعی (تست‌های مقیاس، بدون تراکنش) آزموده می‌شود.

### ۱-۵) حذف N+1ها از ویترین
| محل | قبلاً | حالا |
|---|---|---|
| ویجت دسته‌بندی‌های صفحه اصلی | به‌ازای هر دسته: ۲ کوئری بازگشتی + COUNT | helper `front_category_published_counts` — **یک کوئری تجمیعی + کش ۱۰ دقیقه** |
| لیست محصولات دسته | pluck همهٔ idها (میلیون آیتم در PHP!) + min/max با لیست غول‌پیکر | `front_category_subtree_ids` (مپ کش‌شده) + whereHas/زیرکوئری ایندکس‌خور |
| سایدبار فیلترها | کوئری pivot به‌ازای هر فیلتر با لیست کامل id | `front_filter_spec_values` / `front_filter_attribute_values` — زیرکوئری + کش ۵ دقیقه |
| ویجت‌های محصول/مقاله | `Category::find` + `allChildCategories` بازگشتی به‌ازای هر id | `front_widget_category_ids` — یک کوئری + مپ کش‌شده |
| منو (دسکتاپ/موبایل) | به‌ازای هر آیتم منو: `$menu->category` + `getCategories` | eager-load کامل درخت (`childrenmenus` خودش `category.categories` را لود می‌کند) |
| صفحهٔ محصول | مشخصات: یک کوئری به‌ازای هر گروه | یک لود + فیلتر در حافظه؛ محصولات مشابه با eager-load کامل |
| چیدمان کاربر | `inRandomOrder` روی کل products در هر بازدید | کش ۱۰ دقیقه‌ای |
| فوتر/سایدبار وبلاگ | کوئری در هر بازدید | کش ۵ دقیقه‌ای |

### ۱-۶) کش ویجت‌ها
- خروجی همهٔ ویجت‌های صفحه اصلی ۵ دقیقه کش می‌شود؛ کلید شامل `updated_at` ویجت است → ویرایش ویجت در پنل بلافاصله اعمال می‌شود.
- کلیدهای وابسته به دسته‌ها با «نسخهٔ دسته‌ها» (`front.categories.version`) که `CategoryObserver` هنگام تغییر دسته افزایش می‌دهد، خودکار باطل می‌شوند (بدون نیاز به Cache Tags که file driver پشتیبانی نمی‌کند).

### ۱-۷) Rate-limit جستجو (فقط production)
- `GET /search` → ۳۰۰ درخواست/دقیقه و `POST /search` (جستجوی زنده) → ۶۰۰/دقیقه به‌ازای هر کاربر/IP.
- در تست/سندباکس محدودیتی نیست (helper `production_throttle`).

### ۱-۸) بهینه‌سازی‌های جانبی
- `LogsSearch`: قبلاً با ورودی Model، `->count()` و `->pluck('id')` به Query Builder منتقل و **کل جدول categories** خوانده می‌شد — رفع شد.
- `Category::setCategoriesRelations`: O(n²) → O(n) (groupBy یک‌بار).
- select قدیمی جستجو ستون‌های حذف‌شدهٔ `price`/`discount` را می‌خواند (اتفاقی کار می‌کرد!) — اصلاح شد.
- `AppServiceProvider`: اسکن پوشهٔ Models برای ثبت Observerها حالا کش می‌شود (۱ ساعت).
- deprecation ریزی در `Viewer.php` (urldecode(null) روی PHP 8.4) رفع شد.

### ۱-۹) نتیجهٔ اندازه‌گیری واقعی (همان سرور تست)
| ریکوئست | کوئری‌های جدول categories | کل کوئری‌ها |
|---|---|---|
| صفحه اصلی — اولین (کش سرد) | ۳۲ → **۱۰** | ۶۴ → **۳۱** |
| صفحه اصلی — بعدی (کش گرم) | **۴** | **۱۵** |

---

## ۲) تست‌ها — پوشش مقیاس

اجرای کامل (توصیه‌شده در دو فاز برای محدودیت حافظهٔ سندباکس):

```bash
# فاز A — همه به‌جز تست‌های سنگین مقیاس
php artisan test tests/Feature/Auth tests/Feature/Cart tests/Feature/Catalog \
  tests/Feature/Checkout tests/Feature/Extra tests/Feature/Order tests/Feature/Payment \
  tests/Feature/Performance tests/Feature/Security tests/Feature/AdminPanelSmokeTest.php tests/Unit

# فاز B — مقیاس: میلیون رکورد، ۱۰۰۰ کاربر همزمان، آمادگی زیرساخت
php artisan test tests/Feature/Scale
```

وضعیت فعلی: **۲۱۲ پاس / ۰ شکست / ۳ skip** (۵۷۸ assertion)

کلاس‌های مرتبط با مقیاس:
- `tests/Feature/Scale/MillionScaleTest.php` — درج انبوه ۳۰هزار+ محصول/کاربر واقعی؛ آستانهٔ سرعت جستجو/محصول/ورود <۱s، صفحه‌بندی عمیق <۲s، اثبات ایندکس‌خور بودن
- `tests/Feature/Scale/ConcurrentUsersTest.php` — سرور واقعی PHP با ۶ worker + موج‌های `curl_multi` تا **۱۰۰۰ درخواست همزمان** + ۵۰ لاگین همزمان با CSRF
- `tests/Feature/Scale/ScaleReadinessTest.php` — سلامت زیرساخت مقیاس: sync ستون‌های دینرمال‌شده، زنده‌ماندن کش بعد از instantiate شدن Observerها، وجود ایندکس‌ها، تک‌کوئری بودن شمارش دسته‌ها، N+1 نداشتن صفحه اصلی

> 🔧 اگر اجرای کامل یکجا حافظه تمام کرد: `memory_limit` در `phpunit.xml` روی `2G` تنظیم شده است. تست ۱۰۰۰ کاربر همزمان (~۲۵۰ ثانیه) حافظهٔ زیاد مصرف می‌کند؛ اجرای دو فازی امن‌تر است.

---

## ۳) چک‌لیست production (برای سرور واقعی)

این موارد در `.env` سرور اصلی تنظیم شود:

```env
APP_ENV=production
APP_DEBUG=false

# کش و نشست روی Redis (file برای میلیون کاربر مناسب نیست)
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
```

و روی سرور یک‌بار اجرا شود:

```bash
php artisan config:cache && php artisan route:cache && php artisan view:cache
php artisan products:sync-denormalized   # اطمینان از همگام بودن ستون‌ها
php artisan queue:work                   # اجرای دائمی صف (با supervisor)
```

- **opcache**: در php.ini → `opcache.enable=1`، `opcache.validate_timestamps=0` (بعد از دیپلوی restart)
- **MariaDB**: `innodb_buffer_pool_size` حداقل ۵۰٪ رم سرور برای ایندکس‌های داغ
- **CDN/فایروال** (کلودفلر و…): محافظت لایه ۳/۴ — rate-limit اپلیکیشنی فقط لایهٔ ۷ را می‌پوشاند
- جستجوی فوق‌سریع در آینده: Meilisearch/TNTSearch به‌عنوان لایهٔ دوم (FULLTEXT فعلی تا چند میلیون رکورد کافی است)

### بعد از آپدیت انبوه انبار/قیمت با Query Builder
Observerها فقط روی save های Eloquent فعال‌اند؛ اگر با `DB::table('prices')->update(...)` یا ایمپورت اکسل انبوه تغییر دادید:
```bash
php artisan products:sync-denormalized
```

---

## ۴) چطور یک تست جدید اضافه کنیم؟ (آموزش)

### قدم ۱ — فایل کلاس تست را بسازید
Feature test ها در `tests/Feature/` و بر اساس دامنه در پوشهٔ خودش قرار می‌گیرند:

```bash
# مثال: تست جدید برای تخفیف‌ها
mkdir -p tests/Feature/Discount
touch tests/Feature/Discount/DiscountTest.php
```

```php
<?php

namespace Tests\Feature\Discount;

use PHPUnit\Framework\Attributes\Test;        // PHP 8 attribute — به‌جای @test
use Tests\Support\ShopTestSetup;               // فیکسچر مشترک فروشگاه
use Tests\TestCase;

class DiscountTest extends TestCase
{
    use ShopTestSetup;                          // تراکنش دیتابیس + helper های ساخت داده

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpShop();                     // آماده‌سازی محیط (کش، IP و…)
    }

    #[Test]
    public function expired_discount_code_is_rejected(): void
    {
        // ۱) دادهٔ آزمایش با helper های ShopTestSetup
        $user    = $this->newUser();
        $product = $this->newProduct();

        // ۲) اجرای رفتار
        $response = $this->actingAs($user, 'web')
            ->post('/discount/check', ['code' => 'EXPIRED10']);

        // ۳) ادعا
        $response->assertSessionHasErrors();
    }
}
```

### قدم ۲ — اجرا کنید
```bash
php artisan test tests/Feature/Discount/DiscountTest.php   # فقط همین کلاس
php artisan test tests/Feature/Discount                    # کل پوشه
php artisan test                                           # کل مجموعه
```

### نکات مهم
- **`#[Test]`** (attribute) یا شروع نام متد با `test` → PHPUnit آن را اجرا می‌کند.
- **دادهٔ آزمایش** را با helper های `tests/Support/ShopTestSetup.php` بسازید (`newUser`, `newProduct`, `newPrice`, `newCategory`, `newBrand`, `newOrder` و…) — همه‌چیز داخل تراکنش است و بعد از هر تست rollback می‌شود.
- اگر تست شما باید دادهٔ «committed» ببیند (مثل جستجوی FULLTEXT که در تراکنش باز دیده نمی‌شود)، الگوی `tests/Support/ScaleTestSetup.php` را ببینید (درج واقعی + پاکسازی در tearDown با نشانگر مشخص).
- **ادعاهای پرکاربرد**: `assertStatus`, `assertSee`, `assertRedirect`, `assertJsonFragment`, `assertDatabaseHas`, `assertEquals`, `assertLessThanOrEqual` (برای آستانه‌های کارایی).
- **تست کارایی؟** از `\DB::listen` برای شمارش کوئری یا `microtime(true)` برای زمان استفاده کنید — نمونه‌های واقعی در `ScaleReadinessTest` و `PerformanceTest` هستند.

---

## ۵) تست بار واقعی خارج از PHPUnit (k6 / ab)

برای شبیه‌سازی واقعی ۱۰۰۰ تا ۵۰۰۰ کاربر همزمان روی سرور production:

```bash
# k6 (توصیه‌شده) — اسکریپت آماده در tests/Load/
k6 run tests/Load/k6-shop-load.js
# متغیرها: MAX_VUS=1000 DURATION=5m BASE_URL=https://shop.opashop.ir

# Apache Bench (سریع و در دسترس)
bash tests/Load/run-ab.sh
```

اسکریپت‌ها + چک‌لیست مانیتورینگ در `tests/Load/README.md` آمده است.
