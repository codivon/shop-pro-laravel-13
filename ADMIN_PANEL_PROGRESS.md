# 📋 گزارش پیشرفت توسعه پنل مدیریت جدید — ADMIN PANEL PROGRESS

> **این فایل حافظه‌ی پروژه است.** اگر کانتکست/حافظه‌ی ایجنت پاک شد، با خواندن همین فایل + `PROJECT_ANALYSIS.md` + `/home/z/my-project/worklog.md` کار دقیقاً از همین‌جا ادامه پیدا می‌کند.
> آخرین به‌روزرسانی: **فاز ۷ کامل شد (ج۱+ج۲+ج۳+ج۴)** — همه ۶۸ کنترلر Back پوشش داده شدند؛ ۳۲ روت np. جدید، منو ۱۰۰٪ روی np.* (۱۱۸ آیتم)، اسموک‌ها: ج۲ ۶۰/۶۰، ج۳ ۷۹/۷۹، ج۴ ۸۴/۸۴؛ تایید مرورگری ۲۷/۲۷ صفحه ✅
> فاز بعدی: **۸ — QA نهایی، بهینه‌سازی، همگام‌سازی با پنل قدیمی**

---

## ۱) مأموریت (خواسته کاربر)

ساخت **پنل مدیریت جدید و مستقل** برای فروشگاه چندفروشندگی ایرانی (Laravel ^13 / PHP ^8.3 / فارسی / RTL / جلالی):

1. **اجباری: Livewire 4** + SPA با `wire:navigate` — سرعت بالا، صفحه‌بندی سمت سرور (محصولات خیلی زیاد است)
2. ظاهر **کاملاً جدید و مدرن** با انیمیشن‌های جذاب + **تم روشن/تاریک با ذخیره‌ی ترجیح**
3. **CSS/JS حتماً فایل‌های کامپایل مستقل** — هیچ استایل/اسکریپت inline در Blade نیست
4. آدرس‌ها: **پنل جدید روی همان آدرس فعلی `admin/{ADMIN_ROUTE_PREFIX}`** و پنل قدیمی منتقل به `admin-old/{ADMIN_ROUTE_PREFIX}` (تصمیم کاربر)
5. **هر ۶۸ کنترلر قدیمی `Back/` باید قدم‌به‌قدم و بدون جاافتادگی بازسازی شوند**؛ امکانات جدید بعد از اتمام همه فازها اضافه می‌شود
6. RBAC موجود عیناً اعمال شود: `authorizeResource()` + درخت دسترسی `config('general.permissions')`
7. **پس از هر فاز: اجرای کامل و بدون خطا روی سیستم + push کامل** (برنچ کاری: `docs/project-analysis`)
8. نمودارها: کتابخانه‌ای پیشرفته‌تر از ApexCharts با پشتیبانی فارسی → **Apache ECharts** انتخاب شد (انیمیشن‌های قوی، RTL، فرمت‌کننده فارسی)
9. 🆕 (درخواست جدید کاربر): **گزارش کار هر قسمت داخل خود پروژه در همین فایل** ثبت شود.

---

## ۲) معماری پنل جدید (تصمیم‌های فنی نهایی)

| مورد | انتخاب |
|---|---|
| فریم‌ورک | Livewire **4.4.7** + Alpine (bundled) |
| استایل | **Tailwind CSS 4** (standalone build) — مستقل از Bootstrap قدیمی |
| بیلد | `vite.admin.config.mjs` → ورودی‌ها `resources/admin-panel/css/admin.css` + `resources/admin-panel/js/admin.js` → خروجی **`public/admin-panel/`** (hotFile: `admin-panel.hot`) |
| نمودار | **Apache ECharts** (tree-shaken در admin.js، فرمت‌کننده‌های فارسی با registry `fn:`) |
| فونت | Vazirmatn (لوکال) |
| تم | روشن/تاریک با **کوکی + بدون FOUC** در layout |
| آیکون‌ها | کامپوننت `x-admin-panel::icon` + نقشه SVG در `resources/views/admin-panel/icons-map.php` (lucide v1.59) |
| تاریخ | jdate() + `@majidh1/jalalidatepicker` (جداکننده `-` هماهنگ با سرور) |
| ادیتور | CKEditor 5 با پل `ApEditor` |
| منو | `config/admin-menu.php` — آیتم‌های مهاجرت‌نشده به روت‌های `admin.*` (پنل قدیمی) اشاره می‌کنند تا هیچ‌چیز گم نشود |
| روت‌ها | `routes/admin-panel.php` — گروه `Route::prefix('admin/'.admin_route_prefix())->name('np.')` — **خارج از حلقه locale** یک‌بار ثبت می‌شود |
| ویوها | `resources/views/admin-panel/livewire/admin/{module}/{page}.blade.php` + `return view('admin-panel::livewire.admin...')->layout('admin-panel::layout')` |

---

## ۳) زیرساخت اجرا/تست در sandbox (خیلی مهم برای ادامه کار)

- **PHP استاتیک**: `/home/z/bin/php` (wrapper که `DATABASE_URL` را unset می‌کند → `php-real` 8.4.23). PHP در PATH نیست!
- **دیتابیس تست**: SQLite در `/tmp/nptest.sqlite` (`.env` لوکال: `DB_CONNECTION=sqlite`, `DB_DATABASE=/tmp/nptest.sqlite`, `DB_FOREIGN_KEYS=false`) — کامیت نمی‌شود
- **سرور**: `cd /home/z/shop-pro-laravel-13 && /home/z/bin/php artisan serve --host=0.0.0.0 --port=8000` (بک‌گراند)
- **دیتای تست**: permissions/roles/admin (رمز `admin1234`)، کاربر `testuser` (رمز `testdata`)، ۵ سفارش، دسته‌بندی‌ها؛ + از فاز ۳: فروشندگان آزمایشی
- **تأیید مرورگری**: با agent-browser روی `http://localhost:8000/admin/{prefix}` — لاگین → جریان‌های اصلی → تم تاریک/روشن → موبایل
- `vendor/` **در گیت ترک شده** (۱۴۰۲۶ فایل) — composer لازم نیست نصب شود
- هنگام تغییر `resources/admin-panel/*` باید بیلد Vite اجرا شود: `node_modules` موجود؛ `bunx vite build --config vite.admin.config.mjs` (یا npm)
- لاگ خطاها: `storage/logs/laravel.log`

---

## ۴) نقشه فازها و وضعیت

| فاز | محتوا | وضعیت | کامیت |
|---|---|---|---|
| تحلیل | PROJECT_ANALYSIS.md (۶۵۷ خط) | ✅ پوش شد | `54519067` |
| **۰** | زیرساخت پنل: layout/سایدبار/تاپ‌بار/لاگین/داشبورد/آیکون/توست/تم | ✅ | `d35be321` |
| **۱** | ماژول محصولات (لیست + فرم ۸ تب + ProductPersistence + اسکرپر دیجی‌کالا) + نمودارهای داشبورد | ✅ | `ae45ec97` + `71255a9e` |
| **۲** | ماژول سفارشات (لیست/نمایش/آیتم/ایجاد/ناتمام/اکسل/چاپ/برچسب/عملیات گروهی) | ✅ | `2e78a2ad` |
| **۳** | **فروشندگان**: لیست/نمایش+ویرایش (۷ تب)/محصولات/تنوع‌ها/سفارشات/بازدیدها/اعلان‌ها + ۴ صفحه تنظیمات فروشندگی | ✅ | `e30df7c9` |
| **۴** | **کاربران/مالی**: users (لیست/فرم/نمایش/اعلان/بازدید/اکسل)، تراکنش‌ها، کمیسیون فروشندگان، تاریخچه کیف پول، شارژ/برداشت، صف برداشت (unpay-refund)، مرجوعی‌ها (workflow کامل + موتور بازگشت وجه)، دلایل مرجوعی، تخفیف‌ها (فرم کامل با چیپ‌پیکر) | ✅ | `6a0d3e32` |
| **۵** | **تکسونومی**: درخت دسته‌بندی‌ها (محصول/وبلاگ) + فرم ویرایش، برندها، گروه ویژگی‌ها + ویژگی‌ها، نوع مشخصات، راهنمای سایز (ماتریس مقادیر)، فیلدهای اختصاصی، فیلترها، تگ‌ها | ✅ | `559a5de3` |
| **۶** | **محتوا**: وبلاگ (پست‌ها)، صفحات، منوها، لینک‌های فوتر + گروه‌ها، اسلایدرها، بنرها، سوالات متداول، دیدگاه‌ها (محصول/وبلاگ با درخت پاسخ)، نظرات محصولات، اطلاع از موجودی، فرم‌ساز + پاسخ‌ها، ابزارک‌های صفحه اصلی/وبلاگ، ویجت شناور | ✅ | `87e66b7e` |
| **۷** | **پشتیبانی/سیستم** — الف: تیکت‌ها، پیام‌ها (گروهی/تولد)، تماس، خبرنامه، اعلان‌های سراسری ✅ `f553fd28`؛ ب۱/ب۲: ارز، تعطیلات، استان/شهر، حمل‌ونقل/تعرفه، جستجوها، robots، ریدایرکت‌ها، کلید API ✅ `a7f8c5e2`؛ ب۳/ب۴: انبارها (۲۰۶۵خط کامل)، نشست‌ها، لاگ فعالیت ✅ `dbd364b9`؛ ج۱: ادمین‌ها/نقش‌ها/دسترسی‌ها ✅ `37bf304d`؛ ج۲: آمار (۷ صفحه)+SmsShow+قیمت‌گذاری گروهی ✅؛ ج۳: تنظیمات (۵ صفحه)+توسعه‌دهنده+بله ✅؛ ج۴: پکیج‌ها+تم‌ها+ایمپورت+Pulse+بکاپ+فایل‌منیجر+اعلان‌های پنل+لاگ‌ها ✅ | ✅ | `09a7305d` |
| ۸ | پرداخت نهایی: QA کامل (اسموک ۲۹۰/۲۹۰ + مرورگر ۱۸ صفحه + تعامل مودال)، بیلد Vite نهایی | ✅ | `74414727` |

> کنترلرهای باقی‌مانده (۶۴ عدد از ۶۸) در فازهای ۴ تا ۸ پوشش داده می‌شوند؛ لیست کامل: `ls app/Http/Controllers/Back/`

---

## ۵) جزئیات فازهای انجام‌شده

### فاز ۰ — زیرساخت ✅ (کامیت `d35be321`)
- `resources/views/admin-panel/layout.blade.php` (تم کوکی‌محور بدون FOUC)، sidebar (منوی permission-gated از `config/admin-menu.php` با جستجو + آکاردئون)، topbar (تغییر تم، فول‌اسکرین، پروفایل)، login دوستونه انیمیشن‌دار، dashboard (کارت‌های آماری + آخرین سفارش‌ها، کش ۶۰ ثانیه)، کامپوننت Icon، toasts، menu-item
- سواپ روت‌ها: پنل جدید `admin/{prefix}`، قدیمی `admin-old/{prefix}`
- اصلاح seeders مطابق casts مدل‌ها (Banner آرایه‌ای، Category اسلاگ یونیک blog-)

### فاز ۱ — محصولات ✅ (کامیت `ae45ec97` + `71255a9e`)
- **`app/Services/AdminPanel/ProductPersistence.php`** — پورت ۱:۱ متدهای private ذخیره کنترلر قدیمی (قیمت‌ها/تنوع‌ها با تطبیق اتریبیوت، فایل‌های دانلودی، اسپک‌ها، برند firstOrCreate، گالری، دسته‌ها، لیبل‌ها، سایزها، امتیازها، حذف با پاکسازی موجودی)
- **ProductIndex**: فیلترهای متصل به URL با `#[Url]`، رفع N+1 قدیمی، حذف گروهی، تاریخ جلالی
- **ProductForm**: ۸ تب — ریپیتر تنوع با چیپ اتریبیوت (رنگ تک‌انتخابی)، فایل‌های دانلودی، گروه‌های اسپک، سایزها، امتیازها، فیلدها (filds)، گالری با ترتیب‌دهی، CKEditor، تگ/لیبل چیپی، تولید اسلاگ، دیت‌پیکر جلالی
- داشبورد: ECharts (فروش ۳۰ روزه خطی، دونات وضعیت، برترین محصولات) با ارقام فارسی
- اسکرپر دیجی‌کالا: سرویس ProductScraper + کارت در فرم محصول
- **باگ‌های یافت و رفع‌شده در تست مرورگر**: `@entangle` با نام رشته‌ای، تداخل `$categories` → `extraCategories`، محاسبه قیمت با DOM-read، پلاگین List در CKEditor v49، `Price::hasDiscount` فراخوانی متد به‌جای پراپرتی، CASE بین‌دیتابیسی برای lowestPrice

### فاز ۲ — سفارشات ✅ (کامیت `2e78a2ad`)
- OrderIndex (۳ تب فیلتر، انتخاب گروهی، تغییر وضعیت گروهی، اکسل با انتخاب ستون از route قدیمی با `query[...]`)، OrderShow (نقشه OSM، حذف، فرم‌های ارسال)، OrderItemShow (تغییر وضعیت/کد رهگیری/چاپ)، OrderCreate (اتوکامپلیت کاربر، آبشار استان→شهر، جستجوی محصول با تنوع قیمت، جمع زنده، پورت ۱:۱ store با رویداد OrderCreated و کاهش موجودی)، OrderNotCompleted
- **باگ‌های رفع‌شده**: اکسیون‌های Alpine بدون تعریف → `$wire.*`، `@in_array` نامعتبر، اسکوپ مودال نقشه، **رفع مشکل ریشه‌ای `@can` بعد از morph با میدل‌ور `ResolveDefaultGuard` روی گروه web** (اندپوینت /livewire/update گارد را به web برمی‌گرداند)، Collection در prop عمومی → آرایه، fallback sqlite برای orderByStock، پیش‌ساخت آدرس کامل برای کاربران بدون آدرس، افزودن slug به select، قرارداد `Export` برای OrdersExport خراب قدیمی

---

## ۶) فاز ۳ — فروشندگان ✅ (تست مرورگری کامل شد)

**کنترلر مبنا**: `app/Http/Controllers/Back/SellerControllers.php` (۴۸۸ خط) + `UpdateSellerRequest` + نمای `back/sellers/*`

### ساخته‌شده‌ها:
- **کامپوننت‌ها** (`app/Livewire/Admin/Sellers/`): `SellerIndex` (لیست با فیلتر نام/کدملی-موبایل/وضعیت مدارک + بج وضعیت ترکیبی + حذف گروهی)، `SellerShow` (آمار + ردیف وضعیت + ۷ تب + ۶ جدول فرعی + مودال اعلان)، `SellerProducts` (سراسری + هر فروشنده)، `SellerVariants`، `SellerOrders` (سراسری + هر فروشنده + اکسل + چاپ + عملیات گروهی)، `SellerViews`، `SellerNotifications` (CRUD کامل)
- **تنظیمات** (`app/Livewire/Admin/Sellers/Settings/`): `SellerHero`، `SellerCommission`، `SellerQuestion` (هر سه CRUD)، `SellerEcontract` (سطر واحد)
- **سرویس**: `app/Services/AdminPanel/SellerPersistence.php` — پورت ۱:۱ update (همه فیلدهای info + آپلود ۴ تصویر هرکدام از input خودش) و destroy (کسکید تصاویر/محصولات/تنوع‌ها + پاک‌سازی رکورد info)
- **ویوها**: `resources/views/admin-panel/livewire/admin/sellers/` (index/show/products/variants/orders/views/notifications + settings/*)
- **منو**: گروه «امور فروشندگان» و «تنظیمات فروشندگان» → روت‌های `np.*` سوئیچ شد
- **دسترسی‌ها**: sellers.index/view/update/delete/export/products/orders/orders.export + گیت 503 با `option('multi_vendor_system_status')`

### باگ‌های قدیمی که رفع شدند:
1. اکسپورت سراسری خراب (`$seller` تعریف‌نشده → ۵۰۰) → صفحه سراسری به `np.orders.export` فاز ۲ هدایت شد؛ اکسپورت هر فروشنده با `SellerOrdersExport` قدیمی (تست: HTTP 200)
2. مدل `SellerInfo` بدون `$guarded` → MassAssignmentException در `firstOrCreate` → `protected $guarded=['id']` (الگوی Seller/SellerVariant)
3. `sellerInfo()` بدون default برای ستون‌های NOT NULL (state_id/city_id/address/...) → defaults اضافه شد
4. دکمه حذف با `onclick="...$wire.delete()"` — در onclick خام `$wire` تعریف نیست → `wire:click="delete" wire:confirm="..."`
5. رویداد `open-url` در ویو سفارشات فروشندگان listener نداشت → اضافه شد
6. `update()` قدیمی: آپلود هر ۴ تصویر از یک input، ذخیره نشدن `private_business` و `location`، رول `confirmed` غلط → همه اصلاح شدند
7. حذف رکورد یتیم `sellers_info` پس از destroy (برای محیط بدون FK)

### نتایج تست مرورگر (agent-browser روی :8000):
لیست با آمار و بج‌های ترکیبی ✓ — پرونده با ۷ تب و ذخیره در DB ✓ — تب تماس با آبشار استان/شهر ✓ — ایجاد/ویرایش/حذف اعلان با پیوت ✓ — محصولات/تنوع‌ها/بازدیدها ✓ — سفارشات سراسری و هر فروشنده ✓ — اکسل هر دو مسیر HTTP 200 ✓ — ۴ صفحه تنظیمات (ایجاد/ویرایش/حذف/ذخیره قرارداد) ✓ — حذف کسکید از UI (فروشنده+info+تنوع+محصول) ✓ — منو و لینک‌ها ✓ — تم تاریک/روشن با کوکی ✓ — جدول موبایل با overflow-x-auto ✓ — کنسول پاک و بدون خطای جدید در laravel.log ✓

### تله‌های فاز ۳ (برای فازهای بعدی هم مفید):
- **جدول `sellers.id` کلید جهانی است** (seller_info.seller_id، seller_variants، prices.seller_id، order_items.seller_id)؛ SellerInfo.id فضای id جدا دارد
- `orders.seller_id` **JSON array** است → کوئری مرجع: `OrderItem::where('seller_id')->pluck('order_id')->unique() → Order::whereIn`
- `{seller}` با **slug** بایند می‌شود (getRouteKeyName='slug')
- create/store قدیمی خالی است → صفحه ایجاد ساخته نشد (ثبت‌نام از فرانت)
- `Seller` خودش Authenticatable با گارد `seller` است — هیچ user_id به users ندارد
- کمیسیون: درصد صحیح روی `categories.commission` (سقف والد) — اسنپ‌شات در `order_items.commission`؛ SellerCommission فقط ردیف تبلیغاتی است
- خوانده‌شدن اعلان: پیوت `notification_manage_users.read/read_at`
- `back/sellers/categories.blade.php` مربوط به محصولات است (فاز ۵) — ربطی به این فاز ندارد
- در onclick خام JS، `$wire` تعریف نیست — همیشه `wire:click` + `wire:confirm` یا Alpine استفاده کن
- مدل‌های این پروژه اغلب بدون `$fillable`/`$guarded` هستند → قبل از هر `create/firstOrCreate` چک کن و در صورت نیاز `protected $guarded=['id']` اضافه کن (الگوی پروژه)

---

## ۷) فاز ۴ — کاربران/مالی/مرجوعی/تخفیف ✅ (کامیت `6a0d3e32`، تست مرورگری کامل)

**۴ ایجنت موازی** (Users/Finance/Returns/Discounts) → ۳۹ فایل + ادغام روت/منو توسط هماهنگ‌کننده.

### ماژول کاربران (`app/Livewire/Admin/Users/` + ویوها)
- `UserIndex` — فیلترهای fullname/email/username/level، حذف تکی/گروهی با قانون قدیمی (خودِ ادمین + creator مستثنی)، مودال اکسل (ستون‌ها + `query[...]` → روته قدیمی `np.users.export` با رویداد open-url)
- `UserForm` (create+edit) — تولد جلالی ۳ سلکت (سال داینامیک تا الان، نه ۱۴۰۰ ثابت)، فیلدهای اختصاصی Fild (۷ نوع) با saveFieldValues، آواتار uploadOptimizedImage، رمز→پاک‌کردن نشست‌ها، referral_code
- `UserShow` — ۷ کارت آمار (کیف پول/سفارشات با لینک فیلتر username/نظرات/بازدید امروز/سبد/اعلان‌ها/آخرین خرید موفق) + خلاصه اعلان‌ها
- `UserNotifications` — CRUD اعلان خصوصی user با پیوت user_id (بدون حذف مثل قدیمی)
- `UserViews` — بازدیدهای کاربر

### ماژول مالی (`app/Livewire/Admin/Finance/` + `transactions/`)
- `TransactionIndex` — فیلترهای کامل scopeFilter (cardNumber/transId camelCase!)، مودال جزئیات، حذف تکی/گروهی
- `TransactionSellerDeposits` — لیست کمیسیون با نام فروشنده/حذف‌شده
- `WalletHistoryIndex` + `WalletShow` + `WalletTransactionCreate` (پورت ۱:۱ store با DB::transaction + رویدادهای WalletAmountIncreased/Decreased + اعتبارسنجی سقف برداشت) + `WalletHistoryShow` (status_pay + trackingId unique)
- `RequestDepositIndex/History` — صف برداشت‌ها + تغییر وضعیت گروهی + اکسل (روته قدیمی) + **پورت کامل unpay-refund** (ایجاد ردیف deposit «بازگشت وجه» source=seller + افزایش موجودی) — trait مشترک `Concerns/InteractsWithWalletHistory`

### ماژول مرجوعی (`app/Livewire/Admin/Returns/` + `app/Services/AdminPanel/ReturnPersistence.php`)
- `ReturnIndex` — ۶ کارت وضعیت + ۳ کارت نوع پرداخت + فیلترها (OR گروه‌بندی‌شده — باگ قدیمی رفع)
- `ReturnShow` — تایم‌لاین وضعیت + گالری + پنل اکشن کامل: approve/markCustomerShipped/markReceived/reship/complete/reject با همه گاردهای can* قدیمی + wire:confirm
- `ReturnPersistence::complete()` — پورت ۱:۱ موتور بازگشت: recompute breakdown، increment کیف پول + WalletHistory با توضیح sprintf، restoreCredit (گیت CreditPay)، cancelPaymentPlan، آپدیت آیتم؛ auth('adminPanel')->id() (رفع ابهام گارد قدیمی)
- `ReturnReasons` — CRUD + toggle + ordering=max+1 + تعداد استفاده

### ماژول تخفیف (`app/Livewire/Admin/Discounts/`)
- `DiscountIndex` — withCount سفارش موفق (scopePaid)
- `DiscountForm` — ۲ تب، دیت‌پیکر جلالی `Y-m-d H:i:s` (خروجی با خط تیره مطابق سرور)، **چیپ‌پیکر جستجویی برای کاربران و محصولات** (هرگز همه را load نمی‌کند)، include/exclude با پیوت type، پورت کامل store/update + convertPersianToEnglish + Jalalian::fromFormat

### نتایج تست مرورگر (agent-browser روی :8000)
ایجاد کاربر (با ۳ فیلد سفارشی + تولد جلالی + referral) ✓ ویرایش/حذف تکی و گروهی ✓ اعلان کاربر (پیوت) ✓ شارژ/برداشت کیف پول + اعتبارسنجی سقف («مبلغ نباید بزرگتر از...») ✓ تغییر status_pay + ثبت trackingId ✓ unpay-refund: موجودی +۴۰,۰۰۰ و ردیف بازگشت وجه ✓ تکمیل مرجوعی: بازگشت ۲,۲۵۰,۰۰۰ به کیف پول + ردیف تاریخچه + تایم‌لاین ✓ رد درخواست با دلیل ✓ دلایل CRUD ✓ ایجاد تخفیف (اعتبارسنجی تاریخ/درصد + redirect) ✓ ویرایش با prefill جلالی ✓ اکسل کاربران HTTP 200 ✓ تم تاریک/روشن ✓ جدول موبایل اسکرول افقی ✓ — لاگ بدون خطای جدید ✓

### تله‌های فاز ۴:
- **ستون‌های snake/کمل:** تراکنش‌ها `cardNumber`/`transId`/`traceNumber` کمل‌کیس‌اند؛ مرجوعی `return_reason_id` (نه reason_id!)؛ ReturnImage `path`؛ seller_deposits ستون type ندارد
- `wallet_histories.withdraw=1` فقط توسط کنترلرهای فرانت (تم) ست می‌شود — برداشت دستی ادمین این فلگ را ندارد → صف برداشت فقط ردیف‌های فرانت را نشان می‌دهد؛ select وضعیت پرداخت فقط برای withdraw=1
- منبع کیف پول: قدیمی «هر چیز غیر seller = کاربر» است (`source != 'seller'`) — چک user اول نکن
- admin تست (id=1) level=creator است و همه گیت‌ها را رد می‌کند — ولی `can()` فقط در context وب (نه console) تعریف می‌شود (AuthServiceProvider)
- دکمه‌های «بله حذف شود» تکراری در دو مودال (تکی/گروهی) — در تست JS همیشه scope به `[role=dialog]` بده
- دیت‌پیکر پنل `Y-m-d` (خط تیره) خروجی می‌دهد؛ مقدار با اسلش → خطای «تاریخ نامعتبر» toast
- مدل Fild بدون $guarded بود → `protected $guarded=['id']` اضافه شد (الگوی پروژه)

---

## ۸) فاز ۵ — تکسونومی ✅ (کامیت `559a5de3`)

**۳ ایجنت موازی** (5-a categories / 5-b brands+attributes+spectypes / 5-c sizetypes+filds+filters+tags) → ۳۵ فایل + AttributeForm extended by coordinator (create mode).

- **CategoryTree** (productcat+postcat) — رندر بازگشتی، ایجاد inline با SlugService، حذف شاخه با پاک‌سازی فرزندان (allChildCategories شامل خودِ ریشه)، مرتب‌سازی ↑↓ + دراپ‌داون پدر (جایگزین drag&drop قدیمی — انحراف مستند)، create با ordering=max+1
- **CategoryForm** — پورت ۱:۱ update (متا/سئو، filter_type+filter_id، commission فقط productcat، published، تصویر + background_image هرکدام از input خودش — باگ قدیمی رفع)
- **BrandIndex/Form** — unique name+slug، الگوی ذخیره تصویر storeAs همان قدیمی، حذف portable با صفر کردن products.brand_id
- **AttributeGroupIndex** — inline create + مودال ویرایش + ↑↓ ترتیب؛ **AttributeGroupAttributes** — لیست هر گروه با چیپ رنگ
- **AttributeForm** — create+edit دوتایی (value فقط برای گروه رنگی)
- **SpecTypeForm** — ریپیتر تودرتو گروه→مشخصه با firstOrCreate + پیوت orderings (پورت دقیق)
- **SizeTypeIndex/Form/Values** — ریپیتر سایز، update با حذف whereNotIn، **ماتریس مقادیر گروه×سایز + لاگ spatie activity دقیق**
- **FildIndex/Form** — belongs_to=blogs (باگ قدیمی posts→500 در MySQL strict رفع)، select_options reactive
- **FilterForm** — ریپیتر filterable مورف (static_filter/attributeGroup/specification + separator) پورت add_filterable، قید تک‌فیلتر فروشنده
- **TagIndex** — آمار ۴ کارته، مودال جزئیات (usage_details + taggables grouped)، bulk delete، CSV با روته قدیمی

### تله‌های فاز ۵:
- `{category}` و `{brand}` با **slug** بایند می‌شوند (getRouteKeyName)؛ بقیه مدل‌های تکسونومی id
- مسیرهای ثابت (`categories/product`, `categories/post`) قبل از `{category}` تعریف شوند
- Category بدون $casts — published دستی بولی شود؛ allChildCategories شامل خودِ ریشه است
- مشخصات/گروه مشخصات ستون lang ندارند (برندها/گروه ویژگی/spec_type دارند)
- Livewire 4 نام‌های رزروشده: computed `attributes()` ممنوع → `attributesList()`؛ `hydrateGroups` ممنوع → `loadGroups()`
- اندیسیک ویوها به روت‌هایی که هنوز ثبت نشده‌اند → همیشه route:list را قبل از تست مرورگر چک کن

---

## ۸.۱) فاز ۶ — محتوا ✅ (کامیت `87e66b7e`، تست مرورگری کامل)

**۵ ایجنت موازی** (6-a posts / 6-b pages+menus+links / 6-c sliders+banners+faqs / 6-d moderation / 6-e forms+widgets+floating) → **۶۱ فایل** (۳۳ کامپوننت/کلاس + ۲۸ ویو/partial) + ادغام: ۳۱ روت + ۲۲ سوئیچ منو توسط هماهنگ‌کننده.

### ماژول‌های ساخته‌شده:
- **وبلاگ** (`Posts/PostIndex` + `PostForm`): لیست با جستجو/حذف گروهی، فرم ۴ تب (کلی/سئو/مدیا + فیلدهای اختصاصی)، دیت‌پیکر جلالی `Y-m-d H:i:s`، CKEditor، درخت دسته postcat، تولید اسلاگ با SlugService، گارد اسلاگ تکراری (پورت Repetition-slug)
- **صفحات** (`Pages/PageIndex` + `PageForm`): پورت ۱:۱ + **بازنویسی لینک** `/pages/{oldslug}` → منوها و لینک‌های فوتر پس از تغییر اسلاگ (متا در ویرایش عمداً آپدیت نمی‌شود — parity)
- **منوها** (`Menus/MenuManager`): تک‌صفحه‌ای، درخت رندر بازگشتی، ۴ نوع (نرمال/دسته/ثابت/مگامنو) با همان کلیدهای `config('general.static_menus')`، select والد + ↑↓ (جایگزین drag&drop nestable — انحراف مستند)، گارد ضدحلقه، حذف کسکید
- **لینک‌های فوتر** (`Links/LinkIndex` + `LinkGroups`): گروه‌ها از `config('front.linkGroups')` + ردیف‌های options؛ ذخیره با `option_update('link_groups_{key}')`؛ افزودن/حذف گروه (فقط گروه‌های افزوده‌شده)
- **اسلایدرها/بنرها** (`Sliders/*`، `Banners/*`): کارت‌های آماری + فرم با گرید چک‌باکس pages/groups(/places) از متدهای استاتیک مدل، پیام‌های اعتبارسنجی فارسی عیناً، آپلود `uploadOptimizedImage` (وب‌پی)، مرتب‌سازی 1..n
- **سوالات متداول** (`Faqs/FaqIndex`): جدول + مودال CRUD + حذف گروهی + order خالی → max+1
- **دیدگاه‌ها** (`Comments/CommentsIndex` با prop type): پورت کامل scopeFilter، مودال جزئیات با درخت پاسخ، ویرایش متن/وضعیت با **۳ شاخه لاگ spatie**، پاسخ مدیر، ویرایش/وضعیت/حذف پاسخ — گیت صحیح per-type (قدیمی `||` غلط داشت)
- **نظرات محصولات** (`Reviews/ReviewIndex`): پورت scopeFilter، مودال جزئیات با نقاط قوت/ضعف، فرم ویرایش با چیپ‌پیکر + بازایجاد points + `refreshRating()` + لاگ diff کامل old/new با `event('updated'/'deleted')`
- **اطلاع از موجودی** (`StockNotify/StockNotifyIndex`): لیست/مودال/حذف + لینک فروشگاهی شرطی
- **فرم‌ساز** (`Forms/FormIndex` + `FormBuilder` + `FormSubmissions`): سازنده داینامیک Alpine با ۱۲ نوع فیلد (مودال دو مرحله‌ای)، پورت دقیق update قدیمی (فقط فیلدهای جدید append با maxOrder+1، نام تکراری → پیام فارسی)، حذف/جابجایی فیلدهای موجود، پاسخ‌ها با حذف فایل عکس (پورت deleteSubmission)، حذف گروهی فرم‌ها با کلید قدیمی `filds.delete` (عجیب ولی عیناً)
- **ابزارک‌ها** (`Widgets/WidgetIndex|WidgetForm` + `PostWidget*`): رندر داینامیک options از `WidgetTemplateRegistry` (input/select/file/post_categories/product_categories)، sync پیوت دسته‌ها، activity log diff کامل — **انحراف مهم**: قدیمی با رجیستری خالی 404 می‌داد؛ پنل جدید حالت خالی دوستانه دارد (رجیستری واقعاً ۱۴ نوع از کانفیگ قالب دارد)
- **ویجت شناور** (`Settings/FloatingWidget`): همه کلیدهای fw_* با `option_update`، رنگ hex، ۶ شبکه اجتماعی
- پیش‌نمایش فرم از **کنترلر قدیمی** استفاده می‌کند (`np.forms.preview` → `LegacyFormController@preview` — نمای مستقل)

### نتایج تست مرورگر (agent-browser روی :8000):
لاگین → ایجاد نوشته (redirect + DB: slug/created_by/status='end') ✓ ایجاد صفحه با محتوای CKEditor ✓ ایجاد منوی نرمال (ordering=max+1) ✓ ذخیره گروه‌های لینک (option_name/option_value) ✓ ایجاد FAQ مودالی (order=1) ✓ **فرم‌ساز کامل**: افزودن ۲ فیلد (متن اجباری + انتخابگر با گزینه) → ذخیره (form_fields با order 1,2) ✓ ایجاد ابزارک (۳ options از رجیستری) ✓ ایجاد اسلایدر با آپلود واقعی (وب‌پی + گرید pages/groups + اعتبارسنجی «انتخاب حداقل یک صفحه») ✓ دیدگاه‌ها با seed واقعی (بج‌ها/مودال/درخت پاسخ مدیر) ✓ نظرات (ستاره/توصیه) ✓ اطلاع از موجودی ✓ فرم‌ها/ابزارک‌ها/وبلاگ/ویجت شناور همه رندر ✓ تم تاریک ✓ موبایل 390px ✓ — **صفر خطای جدید در laravel.log** (خطاهای 02:45-02:47 فقط مربوط به دیباگ باگ زیر است)

### باگ فاز ۶ که یافت و رفع شد:
1. **⚠️ کلمه `@script` داخل کامنت JS در blade** (form-builder): خروجی کامپوننت در همان نقطه **truncate** می‌شد (۶.۳KB به‌جای ۵۱KB) → Livewire هنگام mount با `SupportMultipleRootElementDetection` خطای «childNodes on null» → ۵۰۰. **قانون: هرگز @script/@endscript در متن کامنت‌های JS داخل blade ننویس** (پردازشگر خروجی Livewire آن را به‌عنوان مارکر می‌شناسد)
2. `@script` محدوده خودش دارد → سازنده Alpine باید `window.fbBuilder = ...` شود تا x-data resolve شود
3. دیتابیس تست SQLite: ستون `lang` روی pages/menus/links و `products.rating/reviews_count` جاافتاده بود → با ALTER TABLE ترمیم شد (فقط محیط تست — migrationهای کامیت‌شده درست‌اند؛ اگر /tmp پاک شد دوباره چک شود)

### تله‌های فاز ۶:
- **{post} و {page} با slug بایند می‌شوند**؛ slider/banner/faq/form/widget با id
- `->defaults('type','post')` به mount کامپوننت می‌رسد (الگوی np.categories.post)
- متن دکمه‌های sticky فرم‌ها (`sticky bottom-4 z-20`) روی انتهای فرم می‌افتد — در تست با scrollIntoView block:center کلیک کن
- StoryController قدیمی **خالی** است و هیچ روت/مدیریتی ندارد → چیزی برای پورت نبود (استوری‌ها از طریق پکیج‌ها در آینده)
- پل CKEditor خروجی را روی `$wire.description` می‌نویسد — در PostForm هم property `description` → ستون content
- رندر مودال‌های Alpine در a11y snapshot گاهی دیر می‌آید — با `window.Alpine.$data(el)` state را چک کن
- `banners` ستون lang ندارد (detectLang ندارد — پورت دقیق)

---

## ۹) الگوها (Conventions) برای همه فازهای بعدی

1. کلاس: `app/Livewire/Admin/<Module>/<Name>.php` — `#[Title('...')]`, `WithPagination`, `public int $perPage = 15`, فیلترها با `#[Url(as:'...', except:'')]`
2. ویو: `resources/views/admin-panel/livewire/admin/<module>/<page>.blade.php` + `->layout('admin-panel::layout')`
3. ذخیره/حذف پیچیده: سرویس `app/Services/AdminPanel/<Name>Persistence.php` (پورت ۱:۱)
4. دسترسی در اکشن: `auth('adminPanel')->user()?->can('...') ?: abort(403)` (در blade هم `@can` با میدل‌ور ResolveDefaultGuard کار می‌کند)
5. توست: `$this->dispatch('toast', message: '...', type: 'success')`
6. مودال حذف: `public ?int $confirmingDelete = null` + `confirmingBulkDelete` + Alpine entangle live
7. صفحه‌بندی: نویگیشن Tailwind دست‌ساز (`previousPage/nextPage/gotoPage`)، بدون wire:navigate روی صفحات
8. منو: `config/admin-menu.php` — آیتم‌ها را در فاز مربوطه از `admin.*` به `np.*` تغییر بده + آیکون در `icons-map.php`
9. بعد از هر تغییر blade/js/css: بیلد Vite + پاک‌سازی کش ویو (`php artisan view:clear`)
10. تست مرورگری با agent-browser روی :8000 → رفع باگ → کامیت فارسی-انگلیسی + push → **به‌روزرسانی همین فایل**

---

## ۱۰) وضعیت گیت و ادامه کار

- برنچ کاری: **`docs/project-analysis`** (پوش به origin با توکن — remote آماده است)
- **⚠️ SANDBOX RESET (2026-10-19):** repo از GitHub بازکلون شد (تا `52025a2d`)؛ PHP استاتیک 8.4.8 در /home/z/bin/php؛ DB تست از صفر بازسازی شد (repairs دوباره اعمال شد — بخش ۳ و تله‌های فازها را برای repairهای دوباره ببین: deleted_at provinces/cities، ordering/lang/timestamps جغرافیا، lang صفحات/منوها/لینک‌ها/پست‌ها/تخفیف‌ها، مارک‌کردن مهاجرت‌های cosmetic، touch storage/installed)
- تاریخچه: `54519067` (تحلیل) ← `d35be321` (فاز ۰) ← `ae45ec97` (فاز ۱) ← `71255a9e` (اسکرپر) ← `2e78a2ad` (فاز ۲) ← `e30df7c9` (فاز ۳) ← `6a0d3e32` (فاز ۴) ← `559a5de3` (فاز ۵) ← `87e66b7e` (فاز ۶) ← `4fc0a162` (docs) ← `f553fd28` (فاز ۷-الف) ← `a7f8c5e2` (فاز ۷-ب۱/ب۲) ← `dbd364b9` (فاز ۷-ب۳/ب۴)
- **چک‌لیست ازسرگیری** (اگر حافظه پاک شد):
  1. این فایل + `PROJECT_ANALYSIS.md` + `/home/z/my-project/worklog.md` را بخوان
  2. `git log --oneline -8` و `git status` را چک کن
  3. `/home/z/bin/php artisan serve --host=0.0.0.0 --port=8000` را بک‌گراند اجرا کن (اگر بالا نیست)
  4. دیتابیس تست: `/tmp/nptest.sqlite` (اگر /tmp پاک شده: migrations + seeders → ساخت مجدد)
  5. فاز بعدی را از جدول بخش ۴ بردار و با الگوهای بخش ۷ بساز
  6. بعد از اتمام: تست مرورگر → کامیت + push → **این فایل را آپدیت کن**

### فاز ۷-ج۲/ج۳/ج۴ — آمار + تنظیمات + ابزارهای سیستم ✅
- **ج۲ آمار**: ViewsPage/ViewersPage/ViewsList/OrdersPage/ProductsStats/UsersStats/SmsLog (+SmsShow) — تریت‌های قدیمی (ViewStatisticsTrait و...) مستقیم use شدند؛ چارت‌ها ECharts با الگوی داشبورد؛ `InteractsWithStatistics` پورت متدهای private کنترلر قدیمی + no-op `authorize()` (تریت‌های قدیمی روی گارد web صدازدن → ۴۰۳ در Livewire)
- **ج۲ قیمت‌گذاری**: PriceIndex (ویرایش inline قیمت/موجودی + price_changes + لاگ) و PriceGroupIndex (درصد/مبلغ ثابت، محصول/دسته/برند — پورت دقیق updatePricesGroup)
- **ج۳ تنظیمات**: Information (آپلود لوگو/آیکون + admin_route_prefix با مقایسه config نه env)، Socials، Gateways (gateway_configs)، Others (env keys + multi_vendor)، SmsSettings (۷۶ کلید: credentials + پترن‌ها + ۱۵ toggle)
- **ج۳ سیستم**: Developer (تنظیمات/پایین-بالای سایت/وب‌پوش/پاک‌کردن کش/آپدیت‌کننده)، BaleSettings (توکن/تست اتصال/وب‌هوک با BaleMessenger)
- **ج۴**: PackageIndex/Installed/Show/PaymentCallback (packages_cache + installed_modules + InstallPackageJob)، ThemeIndex/Create/Settings، PostsImport/ProductsImport/UsersImport (آپلود xlsx واقعی → Jobها)، PulseIndex (wire:poll.10s جای SSE)، BackupIndex، FileManager، PanelNotifications، LogsIndex (پارس امن لاگ)
- **تله‌های ثبت‌شده این فاز** (مهم برای فاز ۸):
  1. تریت‌های آماری قدیمی داخل متدها `$this->authorize()` صدا می‌زنند (گارد web) → در کامپوننت no-op شود
  2. `change_env()` آخرش `config:cache` اجرا می‌کند → سشن/گیت CLI می‌ریزد؛ وقتی کانفیگ کش شده `env()` null برمی‌گرداند (مقایسه با config نه env)
  3. مدل Price: accessor `discount_price` از ستون `price` محاسبه می‌کند (نوشتن ستون discount_price درست است ولی خواندش shadow می‌شود — پورت قدیمی همان است)
  4. `@php(...)` تک‌خطی در بعضی بسترها truncate می‌شود → فرم بلوکی `@php @endphp` (۸۸ ویو اصلاح شد)
  5. `@disabled` بدون آرگومان = PHP نامعتبر (در imports ویوها متن ساده شد)
  6. مدل Admin: status CHECK constraint ('ACTIVE')، first_name/last_name NOT NULL
  7. `#[Computed]` در تست یک-ریکوئستیه کش می‌شود → فیلترهای `#[Url]` با `Livewire::withQueryParams` تست شوند
  8. روت‌های ثابت (packages/payment/callback, packages/installed, themes/create, themes/settings) باید قبل از `{slug}`/`{param}` بیایند

### وضعیت push
- کامیت‌های 52025a2d..37bf304d (فاز ۶/۷-الف/ب/ج۱) با توکن جدید پوش شد ✅
- کامیت 09a7305d (ج۲/ج۳/ج۴ + روت‌ها + منو) و 74414727 (بیلد Vite نهایی) پوش شد ✅ — برنچ کاملاً سینک با origin

---

## ۱۱) فاز ۸ — QA نهایی ✅ (کامیت `74414727`)

### اسموک (tinker/Livewire::test) — ۲۹۰/۲۹۰ سبز
- ۷-ج۱ (ادمین‌ها/نقش‌ها/دسترسی‌ها): **۶۷/۶۷** — فیلترها، CRUD کامل، گاردها (creator/self)، درخت دسترسی با کسکید، لاگ‌های spatie
- ۷-ج۲ (آمار/پیامک/قیمت‌گذاری): **۶۰/۶۰** — تریت‌های آماری قدیمی، فیلترهای `#[Url]` با withQueryParams
- ۷-ج۳ (تنظیمات/توسعه‌دهنده/بله): **۷۹/۷۹** — آپلود لوگو/آیکون، change_env + config:cache، ۷۶ کلید پیامک، تست اتصال بله
- ۷-ج۴ (پکیج/تم/ایمپورت/Pulse/بکاپ/لاگ/اعلان): **۸۴/۸۴** — Http::fake بازار پکیج‌ها، Queue::fake جاب‌ها، xlsx واقعی با PhpSpreadsheet، پارس امن لاگ
  - نکته هارنس: شمارنده RESULT در اسکوپ tinker صفر می‌ماند (global داخل require) — شمارش واقعی: `grep -c '^PASS:'` = ۸۴ و `grep '^FAIL:'` = هیچ
  - نکته اجرا: با `-d memory_limit=1G` اجرا شود (RouteCollection با ۱۱۸ روت، سقف ۱۲۸M را می‌زند)

### مرورگر (agent-browser روی :8000) — ۱۸/۱۸ صفحه
- admins، roles، permissions، statistics/views، statistics/orders، settings/information، settings/sms، packages، packages/installed، themes، developer، bale-settings، products/prices، imports/products، pulse، backups، logs، notifications/panel
- همه: تیتر فارسی درست + `wire:snapshot` حاضر + بدون خطای ۵۰۰
- تعامل: مودال «مدیر جدید» باز شد و همه فیلدها (نام/نام‌خانوادگی/ایمیل/نام‌کاربری/گذرواژه/تکرار) حاضر بودند ✓
- laravel.log بعد از پاس: فقط آرتیفکت‌های موردانتظار اسموک (fake-timeout پکیج‌ها) + خطای ۴۰۳ API بازار که کامپوننت درست catch می‌کند

### یادداشت‌های محیطی
- بعد از ریست سندباکس: `bun install --frozen-lockfile` (۱۰۷۷ پکیج) قبل از بیلد Vite لازم است
- بیلد نهایی: `bunx vite build --config vite.admin.config.mjs` → ۱۱.۴۵s، CSS نهایی `admin-VCpJJI8O.css`
- **پروژه پنل ادمین تکمیل شد** — همه ۶۸ کنترلر Back پوشش داده شدند (Story خالی بود؛ InstallController عمداً قدیمی ماند؛ file-manager iframe محتوای قدیمی)

---

## ۱۲) فاز ۹ — بازخوردهای کاربر (۸ قابلیت) ✅

| # | درخواست | راه‌حل | وضعیت |
|---|---|---|---|
| ۱ | تم ذخیره نمی‌شد | `EncryptCookies::$except` خالی بود — کوکی `admin-theme` را exclude کردیم (کوکی خام JS سمت سرور decrypt نمی‌شد) | ✅ مرورگر: reload → dark می‌ماند |
| ۲ | CKEditor کامل مثل قدیم | ۵۵ پلاگین (فونت/رنگ/هایلایت/کد/بالانویس/جدول کامل/تصویر+آپلود/ایموجی/سورس/شوبلاک/فول‌اسکرین/HTML-embed/special chars/page break/find&replace/…) + UI فارسی + provider آپارات + آداپتر آپلود → `np.media.upload` + WordCount | ✅ 12/12 |
| ۳ | منوی فعال + اسکرول | `$hasActive` زیرمنوها + variantهای create/edit/show؛ `apSidebar.restorePosition` (scroll به آیتم فعال، sessionStorage) | ✅ navScrollTop=228 + active درست |
| ۴ | داشبورد پر | ۱۲ کارت + ۴ نمودار + ۶ لیست + میانبرها — همه Cache::remember(60)، لینک‌های gated | ✅ 53/53 |
| ۵ | درگ‌انددراپ دسته‌ها | هندل درگ + before/after/inside با بازخورد بصری + `moveNode` سرور (ضد-حلقه) — دکمه‌های ↑↓ حفظ شد | ✅ 41/41 + درگ واقعی مرورگر |
| ۶ | تمایز زیرشاخه‌ها | tint سه سطحی + وزن فونت نزولی + خط نقطه‌چین برند + پرفیکس └ | ✅ |
| ۷ | گالری ویجت/فرم صفحات | مودال پورت قدیم: جستجو + چیپ + ۱۸ ویجت + [form-1] + فرم‌های واقعی؛ کپی + درج در کرسر CKEditor | ✅ 34/34 + درج واقعی |
| ۸ | ویجت‌ساز مدرن زنده | **WidgetBuilder تمام‌صفحه**: iframe پیش‌نمایش واقعی سایت + پنل ابزارک با درگ ترتیب + افزودن کارت‌محور (بدون سلکت) + فرم تنظیمات inline + ذخیره → رفرش زنده پیش‌نمایش + سوییچ دستگاه + هایلایت postMessage + بازطراحی قالب‌ها/ابزارک‌ها | ✅ 65/65 + end-to-end مرورگر |

### تله‌های جدید فاز ۹
1. `EncryptCookies` همه کوکی‌های set-شده سمت کلاینت را می‌شکند — هر کوکی JS جدید باید exclude شود
2. `$var` blade داخل `:class="..."` بدون `{{ }}` برای Alpine literal می‌ماند → خطای «not defined» و شکستن اسکوپ Alpine (در builder رخ داد)
3. در Livewire 4 تست: `Testable` متد `getStatusCode()` ندارد → `assertSuccessful()` در try/catch
4. auto-open بعد از create + openEditor تگر-کلوز = double-toggle باگ UX — سوییچ بهتر از تگر وقتی auto-open هست
5. مدل بدون casts: `=== false` روی ستون integer می‌خورد؛ `is_active` را boolean cast شد
6. SQLite FK cascade خاموش است — حذف وابسته‌ها صریح انجام شود
7. تریت‌های آماری/فایل‌ها: دیسک default این پروژه `local` با root=`public/uploads` است (storeAs → /uploads/...)
8. تریت قدیمی لاگ ابزارک: دستی «ویرایش کرد» + خودکار Observer — تفکیک با description like

## ۱۲ — Phase 10: رفع تداخل گیت + اصلاح بیلد (Hotfix)

**تاریخ:** 1405/07/27 — **کامیت:** `3ce7a2d8` (push شده، verified)

### مشکل کاربر
1. «بیلد لوکال کلی خطا و اخطار می‌دهد» → لاگ ارسالی بررسی شد: **صفر error**؛ ۷۲ اخطار `unsupported-css-nesting` (esbuild target قدیمی chrome87/safari14) + ۱ اخطار chunk-size مربوط به ckeditor5.
2. «تداخل احتمالی با تغییرات من» → merge کاربر (`f0a4555d`) **manifest ناسازگار** ساخت: manifest به `ckeditor5-CJyut1VD.js` اشاره می‌کرد ولی فایل واقعی `ckeditor5-pO5LpMiS.js` بود → در ریموت، CKEditor روی همه صفحات 404 می‌شد.

### اقدامات
- FF-merge به `f0a4555d` (بدون تداخل — local ancestor بود).
- `vite.admin.config.mjs`: `target: [chrome112, edge112, firefox117, safari16.5]` (Tailwind v4 حداقل همین را لازم دارد)، `chunkSizeWarningLimit: 2600`، `sourcemap:false`، `reportCompressedSize:false`.
- Rebuild کامل: **0 warning / 0 error**، 11.1s (قبلاً 46s). manifest↔assets کاملاً سازگار (اسکریپت check: همه ref ها موجود، صفر orphan، chunk baked = `ckeditor5-BGZg8Use.js` موجود).
- Browser verify: login → dashboard رندر کامل RTL؛ `pages/create` → toolbar CKEditor رندر، chunk 200، صفر console error.

### درس برای ادامه (workflow بیلد)
- هر بار بیلد: `npm run build:admin` سپس **کل پوشه `public/admin-panel` را با هم commit کن** (manifest + assets یکجا) — نیمه-commit = CKEditor 404.
- قبل از push حتماً `git pull --rebase` تا دوباره merge-commit ناسازگار ساخته نشود.

## ۱۳ — Phase 11: دور دوم بازخورد کاربر (۱۳ مورد)

**تاریخ:** 1405/07/27 — وضعیت: browser-verified، push شده

### ۱) باگ‌ها
- **sanctum deprecation** (`parse_url(null)`) → `parse_url((string) env('APP_URL', ''))`
- **خطای FK جدول searches** (1452) → trait `LogsSearch`: `auth()->id()` (id کهنه session کاربر حذف‌شده) → `safeSearchUserId()` با `auth()->user()?->id` (theme controllers هم از همین trait استفاده می‌کنند)
- **تراز اسپینر دکمه‌ها** → CSS: `button [wire\:loading]:not([style*="display: none"])` → inline-flex وسط‌چین (Livewire هنگام نمایش inline-block ست می‌کرد)
- **is-active منو** → menu-item فقط match دقیق؛ آیتم‌های دارای کلید `active_routes` استثنا هستند (ابزارک‌ها — درخواست صریح کاربر)؛ آکاردئون والد با منطق قبلی باز می‌ماند

### ۲) قابلیت‌ها
- **ثبت اولین مدیر**: URL `register` → `setup` (+ ریدایرکت 302 قدیمی)؛ بازطراحی کامل مستقل (بدون bootstrap/jQuery): هیرو گرادیانی + steps، strength-meter گذرواژه، پیش‌نمایش زنده آدرس پنل، eye toggle، اعتبارسنجی vanilla + fetch، پاسخ سرور `redirect` هم اضافه شد
- **درگ دسته‌بندی‌ها** → بازطراحی کامل: سه نوار رهاسازی واقعی روی هر ردیف (بالا=قبل/وسط=زیرشاخه/پایین=بعد) + برچسب شناور وضعیت + ناحیه «سطح ریشه» برای خروج از زیرمجموعه + اکشن `moveNodeToRoot`؛ هر ۴ حالت browser-verified (before/after/inside/root)
- **انتخابگر کارتی** (`ap-check-card`) → دسته‌بندی‌های نوشته/محصول + options ابزارک + وضعیت‌های نوشته؛ به‌جای چک‌باکس خام
- **تنظیمات قالب** → بخش‌بندی خودکار فیلدها با linebreak به کارت‌های مستقل + سربرگ گرادیانی + نوار ذخیره چسبان
- **ابزارک‌ها**:
  - لیست به‌جای کارت + درگ‌انددراپ ترتیب + مودال ایجاد/ویرایش
  - جریان افزودن دو مرحله‌ای: انتخاب نوع (گرید کارت) → مشخصات (نوع/عنوان/وضعیت/ترتیب/options) → «ایجاد»
  - ویرایش: مودال (نه select) — نوع به‌صورت باکس کلیک‌شدنی که مودال انتخاب نوع را باز می‌کند
  - ویجت‌ساز مقالات: `np.widgets.posts-builder` (PostWidgetBuilder، preview=/blog)
  - منو: «ویجت‌ساز صفحه اصلی» → `np.widgets.index` (اول لیست، بعد builder)
  - trait مشترک `InteractsWithWidgetTemplates` (منطق options/لاگ یکجا)
- **widget-form (صفحه کامل)**: select نوع → گرید کارت‌های کلیک‌شدنی

### ۳) مشکل clone/pull کاربر (ریشه‌ای)
`.gitattributes` قبلی `* text=auto` داشت → روی ویندوز minified assets خروجی بیلد CRLF می‌شدند → تداخل‌های فانتومی و خطای overwrite هنگام pull. فیکس: `public/admin-panel/** -text` (بعد از قانون‌های eol)، باینری‌ها explicit binary، سورس‌ها eol=lf، `/admin-panel.hot` به gitignore.

### ۴) وریفای
- build نهایی: 0 warning/0 error (11.2s)
- مرورگر: لیست ابزارک‌ها + هر دو مودال + ایجاد واقعی بنر تکی + ویرایش/فعال‌سازی ✓، builder خانه (preview زنده ابزارک فعال) ✓، builder مقالات ✓، درخت دسته‌ها: هر ۴ حالت درگ با کالیبره DB ✓، فرم نوشته: کارت‌ها + is-active دقیق (فقط «نوشته جدید») ✓، تنظیمات قالب بخش‌بندی ✓، ثبت مدیر (طرح + redirect قدیمی 302) ✓

## ۱۴ — Phase 12: دور سوم بازخورد کاربر (۱۷ مورد)

**تاریخ:** 1405/07/27 — وضعیت: browser-verified، push شده (کامیت `3a19f772`)

### ۱) منو (سایدبار)
- **برندها/ویژگی‌ها/فیلترها/نقش‌ها کار نمی‌کردند** → ریشه: آیتم‌های سطح-۳ با children به‌صورت `<a href="#">` رندر می‌شدند. فیکس: flatten به برگ‌های مستقیم (برندها → np.brands.index، نقش‌ها → np.roles.index، …)
- **آیتم‌های تکراری «X جدید» که مودال شدند حذف شدند**: برند جدید، گروه ویژگی جدید، نقش جدید، مدیر جدید، استان جدید، شهر جدید، ایجاد انبار جدید، لینک جدید (روت np.links.create هم اصلاً وجود نداشت)
- **چندفروشندگی خاموش → منوها مخفی**: فلگ `vendor => true` روی گروه «امور فروشندگان»، «لیست کمیسیون فروشندگان»، «صف پرداخت سهم‌ها»؛ سایدبار سه‌سطح فیلتر می‌کند (گروه/آیتم/فرزندان) با `option('multi_vendor_system_status')`. هر دو حالت on/off مرورگر-تایید شد
- **آکاردئون‌ها به‌صورت پیش‌فرض بسته** (حتی گروه حاوی صفحه فعال؛ is-active روی آیتم باقی می‌ماند)؛ اسکرول-ریستور فقط روی آیتم فعالِ نمایان
- **تورفتگی زیرمنو**: `ms-4 (16px) + border-s-2 + ps-2.5` — از راست ≥۱۰px خواسته‌شده

### ۲) انتخاب دسته‌بندی (کامپوننت مشترک جدید)
- `ap-category-picker` (Alpine در admin.js + کامپوننت blade `category-picker` + پارشال بازگشتی `category-picker-node` + سکشن CSS): دکمه + پنل درختی کارتی با جستجوی زنده (فیلتر برچسب/فرزندان + بازشدن خودکار)، چیپ‌های انتخاب‌شده با X، بج تعداد زیرشاخه، فوتر شمارنده در حالت چندگانه
- **پیشنهاد پرکاربرد**: چیپ‌های top-6 بر اساس شمارش pivot `category_product`
- ادغام در: فرم محصول (تک‌انتخابی اصلی + چندانتخابی بیشتر — سِلکت قدیمی حذف)، فرم منوها (انتخاب دسته مگامنو/دسته‌ای)
- نکته: پارشال گره برای اسکوپ Alpine نیاز به `x-data="{ node: @js($node) }"` روی li داشت (فیکس 4-a)

### ۳) باگ‌ها
- **تب تصاویر محصول**: label «افزودن تصاویر» گالری `relative` نداشت → `absolute inset-0` کل صفحه را می‌پوشاند و هر کلیک فایل‌سلکتور ویندوز را باز می‌کرد. فیکس + آدیت همه inputهای file اورلی (بقیه سالم بودند)
- **نوار «۰ مورد انتخاب شده» سوالات متداول/مدیران**: ریشه واقعی = `class` استاتیک + `@class([...])` جدا → دو اتریبیوت class؛ مرورگر اولی را نگه می‌داشت و hidden هرگز اعمال نمی‌شد → merge در یک `@class(['ap-card flex ...', '!hidden' => ...])`
- **مودال مدیران**: عنوان «ایجاد کاربر جدید» → «ایجاد مدیر جدید»؛ دکمه انصراف/کلیک بیرون که `closeFormModal` را در اسکوپ Alpine صدا می‌زد (ReferenceError) → `open = false` + بستن با ESC + دکمه ثبت «ایجاد مدیر»

### ۴) بازطراحی‌ها (عامل‌های موازی، همه browser-verified)
- **فرم کاربر** (4-c): هیرو با آواتار دایره‌ای + سوییچ تایید شماره + ۳ کارت بخش‌بندی‌شده + سنجه قدرت گذرواژه + نوار ذخیره چسبان با اسپینر تک‌خطی
- **فرم‌ساز** (4-c): تب‌بندی (اطلاعات فرم/فیلدها/پیام‌ها و اعلان/سئو) + بج شمارنده فیلدها + فیکس isEditing و empty-state ویرایش
- **منوها درگ‌انددراپ** (4-d): الگوی اثبات‌شده دسته‌ها (۳ نوار before/after/inside + برچسب شناور + ناحیه «ریشه») + `moveNode`/`moveNodeToRoot` با گارد ضدحلقه + نرمال‌سازی ordering؛ 48/48 smoke + درگ واقعی مرورگر (DB: menu_id/ordering)
- **CKEditor** (4-b): + Bookmark، SelectAll، Style (۵ تعریف ap-style-*)، TextTransformation، Autoformat، **GeneralHtmlSupport** (برای persistence کلاس style ضروری بود)؛ toolbar ۴۸→۵۳ آیتم. Print/Templates/TextDirection/FormatPainter/Forms/Flash فقط CK4 یا پولی‌اند و در CK5 رایگان وجود ندارند

### ۵) وریفای نهایی
- build: 11.2s، 0 warning/0 error؛ manifest: 9 entry، 0 missing/0 orphan
- مرورگر: سایدبار (openGroups:0، hashLinks:[]، vendor on/off)، brands/roles/filters/attribute-groups رندر، FAQ (نوار مخفی→«۱ مورد انتخاب شده» بعد از انتخاب)، مودال مدیران (عنوان/انصراف/بیرون/ESC)، تب تصاویر (هر دو input محدود به کارت)، picker (۲ پیکر، ۵۶ گره، جستجو)، CKEditor (style/bookmark)، منوها (۳۰ هندل درگ + is-active)
- laravel.log: رشد صفر بایت
