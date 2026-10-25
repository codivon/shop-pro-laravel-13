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

---

## فاز ۱۳ — درخواست چهارم کاربر (درگ SortableJS، توست اعتبارسنجی، پیکر نوشته‌ها، فیکس‌های Livewire 4)

**تاریخ:** 1405/07/27 — وضعیت: browser-verified، push شده

### ۱) باگ «دکمه‌ها از ابتدا در حال ذخیره…» (همه صفحات — ریشه واقعی)
- Livewire 4 عنصرهای `[wire:loading]` را با **استایل‌شیت تزریقی** مخفی می‌کند نه با inline style؛ قانون «تراز اسپینر» فاز ۱۱ با شرط `:not([style*="display: none"])` فرض inline بودن داشت → شرط هرگز عمل نمی‌کرد و **همه spanهای لودینگ داخل دکمه‌ها همیشه نمایان بودند** (در سندباکس هم بازتولید شد)
- فیکس: `button [wire\:loading][style]:not([style*=display:none])` — تراز inline-flex فقط وقتی که استایل inline (حین لودینگ) وجود دارد
- گارد دفاعی اضافه: کلاس `lw-booting` روی `<html>` + CSS `.lw-booting [wire\:loading]{display:none!important}` که با `livewire:init/initialized/navigated` برداشته می‌شود
- **نگهبان خودترمیم asset**: اگر Livewire بعد از ۶ ثانیه لود نشود (مثل 404 پس از تغییر APP_KEY)، یک‌بار در ۳۰ ثانیه reload می‌کند (sessionStorage ضد حلقه)

### ۲) خطای 404 `livewire-<hash>/livewire.js` روی سیستم کاربر (تشخیص)
- Livewire v4.4.7 مسیر asset را **مسیر دقیقِ مشتق از APP_KEY** رجیستر می‌کند: `sha256(APP_KEY.'livewire-endpoint')[:8]` (`EndpointResolver::prefix`) — هیچ wildcard یا کشی در کار نیست
- یعنی 404 فقط وقتی رخ می‌دهد که URL صفحه (اسنپ‌شات wire:navigate/تب باز/کش route) با APP_KEY فعلی نخواند. بیلد Vite ربطی ندارد
- راه‌حل کاربر: `php artisan optimize:clear` + ری‌استارت serve + Ctrl+F5؛ تشخیص: `route:list --path=livewire` در برابر view-source؛ از اجرای مجدد installer/`key:generate` در میانه session خودداری شود
- کندی لوکال: `php artisan serve` تک‌پردازنده است؛ پیشنهاد: vhost آپاچی Laragon + غیرفعال‌کردن debugbar؛ خطای `Max execution time 300s` در ServeCommand خطای پروسه ناظر است و بی‌خطر

### ۳) درگ‌انددراپ با SortableJS (جانشین کامل درگ دستی HTML5)
- `sortablejs` نصب شد (bun)؛ هلپر سراسری `window.ApDnd` در admin.js: init/serializeTree/save + initAll روی `livewire:navigated`
- سه سطح بازنویسی شد: **منوها** (درخت با لیست فرزندانِ همیشه‌رندر — فرزند شدن با رها روی کادر خط‌چین زیر گره، خروج به ریشه با رها در لیست ریشه)، **دسته‌بندی‌ها** (باندهای فاز ۱۱ که pointer-events:none بودند و عملاً drop نمی‌گرفتند حذف شدند)، **ابزارک‌ها** (باگ `draggingId` تعریف‌نشده در dragover — ReferenceError — حذف شد)
- `forceFallback: true` → درگ مبتنی بر موس/لمس (سازگار با RTL/تاچ، هم‌تجربه nestable پنل قبلی) + استایل‌های ghost/fallback
- اکشن‌های سرور جدید `saveTree(array)` در MenuManager و CategoryTree (اعتبارسنجی idها، تراکنش، نرمالایز ۱..n)؛ moveNode/moveNodeToRoot/moveUp/moveDown برای سازگاری حفظ شدند
- باگ‌های مسیر کشف‌شده: serialize با `:scope >` لیست ریشه را نمی‌افتاد (داخل wrapper پدینگ است) → جستجوی لیستِ خارج از گره‌ها؛ payload خالی → گارد سرور
- وریفای درگ واقعی (Playwright mouse): منو ۱۶ → داخل منو ۲۶ → DB: menu_id=26، ord=1؛ بازگشت به ریشه → دقیقاً baseline (ord=2)؛ دسته ۲۱ → داخل ۱۸ → category_id=18، ord=1؛ بازگشت به ریشه ✅؛ ابزارک ۴۰ بالای ۳۹ → 40:o1/39:o2 ✅

### ۴) پیکر دسته‌بندی نوشته‌ها (چک‌باکس → مودال درختی)
- PostForm: `categoryTree` به شکل تو‌درتو `{id,title,children}` تبدیل شد؛ ویو: چک‌باکس‌های کارتی → `<x-admin-panel::category-picker wireProp="categories" :multiple="true">` (دکمه + پنل درخت + جستجوی زنده + چیپ‌ها)
- وریفای: جستجوی «اپل» → ۲ ردیف، انتخاب دو مورد → چیپ‌های مسیر کامل + `categories=[39,33]`، ارسال فرم → نوشته با cat=[29] در DB

### ۵) پیام لحظه‌ای خطاهای اعتبارسنجی (همه فرم‌ها)
- هوک سراسری در admin.js: `Livewire.hook('commit')` → `snapshot.memo.errors` غیرخالی → `ApToast.error('لطفاً خطاهای مشخص‌شده در فرم را برطرف کنید.')` (ضدتکرار ۱.۵s) + پشتیبان `hook('request')` برای HTTP 422 واقعی
- وریفای: ارسال فرم خالی نوشته → توست اختصاصی «عنوان الزامی است» + توست سراسری جدید + خطای زیر فیلد

### ۶) باگ «ایجاد صفحه جدید کار نمی‌کند» — ریشه واقعی
- `window.Livewire.find(wireId)` در Livewire 4 آبجکت مرده (نه کامپوننت زنده) برمی‌گرداند → سینک `change:data` ادیتور روی `$wire.description` **بی‌صدا گم می‌شد** → خطای «محتوای صفحه الزامی است» برای همیشه
- فیکس: resolve کامپوننت با `Livewire.all().find(c => c.el === componentEl)`
- وریفای کامل: create (title+content+submit → ریدایرکت + رکورد DB با محتوا)، edit (prefill ادیتور + ذخیره تغییرات → DB)

### ۷) سایر
- منوی «دستگاه‌های بلاک شده» از config/admin-menu.php حذف شد (تب آن داخل نشست‌های فعال باقی است)
- build: ~11s، 0 warning؛ manifest 9 entry سازگار؛ laravel.log رشد صفر؛ دیتای تست کاملاً پاک شد (menus=30, pages=0, posts=0, widgets=1 = baseline)

---

## فاز ۱۴ — بخش پکیج‌ها (بازطراحی کامل) + انتخابگر رسانه File Manager (اسلایدر/بنر/تنظیمات)

### ۱) بخش پکیج‌ها — ریشه‌یابی و رفع دو باگ اصلی
- **«تصاویر پکیج لود نمی‌شود»**: قدیمی برای تصویر `thumbnail_url ?? thumbnail` می‌گرفت و پاسخ API معمولاً مسیر نسبی است؛ پنل جدید فقط `thumbnail` را مستقیم در `<img>` می‌گذاشت → مرورگر آن را روی دامنه خودِ سایت می‌جست (404). فیکس: هلپر جدید `App\Support\PackageMedia::thumbnailUrl()` (thumbnail_url → thumbnail → مسیر نسبی با پیشوند دامنه API مطلق می‌شود) + fallback آیکون با onerror در کارت‌ها و سربرگ
- **«متن‌ها به صورت کد HTML نمایش داده می‌شود»**: توضیحات API حاوی HTML است. کارت‌های لیست حالا از `PackageMedia::descriptionText()` (strip_tags + فشرده‌سازی فاصله + limit) متن تمیز نشان می‌دهند؛ صفحه جزئیات مثل قدیمی با `{!! !!}` رندر می‌کند (short_description، long_description، changelog) + کلاس استایل `pkg-prose` برای زیبایی HTML خام (h3/ul/li/a/code/img/table)
- **build bug کشف‌شده**: `@php(...)` تک‌خطی که بعد از آن یک بلوک چندخطی `@php ... @endphp` در همان ویو بیاید → کامپایل Blade خراب می‌شود (خروجی `<?php(...)` بدون بستن + دایرکتیوهای بعدی خام → 500). هر دو ویو پکیج از الگوی تک‌خطی پاک شدند (فراخوانی مستقیم هلپر در @if و src)

### ۲) صفحه جزئیات پکیج — پورت کامل عملیات پنل قبلی (قبلاً فقط نمایش بود)
- PackageShow جدید: `openInstall/confirmInstall` (مودال تأیید نصب با آلرت پرداخت و مبلغ پلن — پورت install-confirm-modal)، `updateModule` (چک expired/in-progress)، `toggleActivation`، `confirmUninstall/uninstall` (مودال هشدار حذف)، `refreshStatus` + `wire:poll.3s` هنگام status=updating (پورت startPolling/pollStatus هر ۳ ثانیه) + توست موفقیت/شکست با گذار وضعیت
- چیدمان مثل قدیمی: کارت سربرگ (تصویر بزرگ + نشان رایگان/پولی + چیپ‌های نویسنده/دسته/دانلود + توضیح HTML)، کارت «توضیحات کامل» + تاریخچه نسخه‌ها، پلن‌های قیمت‌گذاری (نصب هر پلن با مودال تأیید)، جدول لاگ‌ها با نشان‌های نصب/آپدیت/حذف و موفق/ناموفق/در حال اجرا، ستون عملیات با جعبه وضعیت ۴ حالته (نصب‌نشده / در حال نصب با polling / خطا با last_error / نصب‌شده با انقضای لایسنس)، دکمه‌های آپدیت/فعال‌سازی/غیرفعال‌سازی/حذف، کارت پیش‌نیازها و اشتراک
- `actionLabel()` برای برچسب فارسی عملیات لاگ؛ redirect به installed پس از حذف (مثل قدیمی)

### ۳) انتخابگر رسانه (File Manager) — تجربه پنل قبلی در پنل جدید
- کامپوننت جدید `<x-admin-panel::media-picker>`: ناحیه drop زیبا (حالت خالی + پیش‌نمایش با دکمه‌های تغییر/حذف روی hover + حالت «فایل یافت نشد» برای مسیرهای مرده) → کلیک = مودال داخل پنل با iframe روی `/file-manager/fm-button` (همان بسته Alexusmai پنل قبلی) → انتخاب/آپلود فایل در FM → callback مقدار را در فیلد می‌نویسد و پیش‌نمایش فوراً می‌آید
- پل ارتباطی: ویوی منتشرشده `vendor/file-manager/fmButton.blade.php` دوحالته شد (iframe → window.parent / popup قدیمی → window.opener — سازگاری کامل با صفحات قدیمی)؛ در admin.js: `window.ApMedia` (url/active/setLink) + `window.fmSetLink` + کامپوننت Alpine `apMediaPicker` با سینک input event → wire:model (روی commit بعدی مثل input مخفی قدیمی) + target-uid برای ایزوله‌سازی چند پیکر در یک صفحه
- پورت ۱:۱ ذخیره‌سازی مثل قدیمی: مقدار FM (مسیر/URL رشته‌ای) مستقیم ذخیره می‌شود؛ **اسلایدر/بنر**: SliderForm/BannerForm از WithFileUploads به رشته تغییر کرد (rules مثل قدیمی `required|string|max:2048`)؛ **تنظیمات**: Information (۵ فیلد info_*) و Others (factor_logo/watermarkImage) — گارد «فقط اگر مقدار عوض شده» + بازنشانی پراپرتی پس از ذخیره

### ۴) باگ مهم جانبی کشف‌شده: فرم تنظیمات هرگز ذخیره نمی‌شد
- `input type=email/url` با داده واقعی (مثلاً «x-test» یا شماره تماس) → اعتبارسنجی بومی HTML5 مانع submit می‌شد (checkValidity=false → نه submit نه commit). فیکس: `novalidate` روی فرم تنظیمات + بوت‌استرپ سراسری در admin.js که به هر فرم دارای email/url پس از DOMContentLoaded/livewire:navigated، novalidate می‌دهد (اعتبارسنجی سمت Livewire انجام می‌شود)

### ۵) وریفای مرورگری (E2E)
- اسلایدر: create → مودال FM باز شد → شبیه‌سازی انتخاب (fmSetLink) → پیش‌نمایش + input سینک → ذخیره → رکورد DB با مسیر FM ✓؛ edit → پیش‌نمایش اولیه ✓
- بنر: همان جریان کامل → رکورد DB ✓؛ ایندکس بنرها asset() با مسیرهای FM قدیمی ✓
- تنظیمات: ۵ پیکر هم‌زمان، انتخاب ایزوله فقط پراپرتی خودش (uid targeting) → ذخیره → option در DB ✓
- پکیج‌ها (با سرور mock محلی به‌جای update.weblak.ir): ایندکس با ۳ پکیج — تصویر نسبی مطلق شد و لود شد (loaded:true)، تصویر مطلق خراب → fallback آیکون، توضیحات متنِ تمیز بدون تگ؛ صفحه جزئیات: رندر HTML توضیحات/تاریخچه/پیش‌نیازها/لاگ‌ها ✓؛ مودال نصب رایگان ✓؛ مودال پلن پولی با مبلغ تخفیف‌دار (۷۹۰,۰۰۰) ✓؛ فراخوانی purchase به API واقعاً ارسال شد
- پاکسازی: رکوردهای تست حذف شدند (slider 59 / banner 29 / option info_logo_seller)، .env به حالت قبل، mock متوقف؛ laravel.log رشد خالص = فقط خطای عمدی mock (422 نصب در محیط تست)

---

## فاز ۱۵ — ممیزی امنیتی کامل + ربات‌گیر (Bot Trap)

### روش ممیزی
سه ایجنت موازی کل کدبیس (app/، resources/، themes/، routes/، config/، bootstrap/) را از سه منظر اسکن کردند: ① تزریق (SQLi/CMD/LFI/traversal) ② XSS و آپلود فایل ③ احراز هویت/مجوز/افشای تنظیمات. سپس موارد بحرانی اصلاح و هر فیکس با `php -l` و تست مرورگری E2E تأیید شد.

### ۱) ربات‌گیر (Bot Trap) — ویژگی جدید درخواستی
- **جدول `bot_traps`** (migration جدید): IP مهاجم، user_agent، مسیر تله، تعداد ضربه — با Model `App\Models\BotTrap` و کش ۱۰ دقیقه‌ای لیست IPها
- **مسیرهای طعمه**: `wp-login.php`, `wp-admin`, `xmlrpc.php`, `phpmyadmin`, `.env`, `.git/config`, `phpinfo.php`, `adminer.php`, ... (۲۶ مسیر رایج اسکنرها) + `/bot-trap/hit` — هر ضربه = مسدودسازی کامل IP؛ ربات‌های مطیع با robots.txt به سمت تله هدایت می‌شوند
- **میان‌افزار سراسری `BlockBots`**: IPهای ثبت‌شده در تله → 403 روی کل سایت (وب+API) بدون کوئری اضافه (کش)
- **تله‌ی فرم ورود (هر دو پنل)**: ① honeypot field نامرئی (`website_url`) — پر کردن توسط ربات = مسدودسازی IP ② time-trap — ارسال فرم در <۳ ثانیه = رد ③ کپچای تصویری (سرویس CaptchaService موجود — کد هش‌شده در سشن، یک‌بارمصرف، ضد brute-force روی خود کپچا) ④ محدودیت تلاش `LoginThrottle`: هر ۵ شکست ⇒ قفل تدریجی ۱۵ دقیقه → ۳۰ دقیقه → ۱ ساعت → ... (حداکثر ۲۴ ساعت) بر اساس «نام‌کاربری+IP»
- **OTP ضد بردار-force**: کدها از `rand` به `random_int` (CSPRNG) + محدودیت ۵ حدس در ۱۵ دقیقه به‌ازای هر کاربر+IP در `LoginWithCodeController::confirm` و `OneTimeLoginController::store` + throttle:10,1 روی مسیرها
- E2E مرورگری: ورود نادرست → خطای فارسی؛ ۵ شکست → پیام قفل ۱۵ دقیقه (حتی با رمز صحیح)؛ پر کردن honeypot توسط «ربات» → پیام عمومی + ثبت IP در bot_traps + 403 سراسری؛ خواندن کپچا با VLM و ورود موفق → داشبورد ✓

### ۲) تزریق — بحرانی‌های اصلاح‌شده
- **SQLi** در مرتب‌سازی استان/شهر (`orderByRaw('FIELD(ordering,'.$request->orderings.')')` بدون اعتبارسنجی) → `orderings.*=>integer` + `array_map('intval')`
- **حذف فایل دلخواه (path traversal)** در `TicketController::destoryFile` (`Storage::delete('tmp/'.$filename)` با `../../.env`) → گارد `basename` + hash_equals
- **تزریق .env** در `change_env()` (بودن `\n` در مقدار ⇒ تعریف کلید دلخواه مثل APP_KEY/DB_PASSWORD) → پاکسازی CR/LF/NUL + الگوی کلید + کوت‌کردن مقدار با فاصله
- **CMD injection** در `ShopLink` (`exec('ln -s '.$target.' '.$link)` بدون escape) → `escapeshellarg` (پورت رفتار Windows که از قبل escape داشت)
- **LFI/symlink/wipe قالب**: نام قالب در `ThemeController@update/destroy/activate/confirmDelete` (قدیم+جدید) با allowlist `is_safe_extension_name()` + گارد realpath داخل themes/ + گارد `asset_path` خالی/تراaversal قبل از `deleteDirectory` (قبلاً `''` ⇒ پاک شدن کل public!)
- **Zip-Slip** در آپلود قالب (Controller + Livewire): بررسی ZipArchive ورودی‌ها (`../`، مسیر مطلق، symlink) قبل از Madzipper + allowlist نام قالب استخراج‌شده + گارد آرگومان artisan `vendor:publish`
- **TLS**: `CURLOPT_SSL_VERIFYPEER/HOST` خاموش در ۵ فایل (اسکریپر، ایمپورت‌ها) → روشن (تحت MITM بودند)

### ۳) XSS و آپلود
- **Stored XSS توضیحات غنی**: هلپر `sanitize_rich_text()` (حذف script/style/object/embed/form/iframe غیرمجاز، on* handlers، javascript:/vbscript:/data: URLs) اعمال‌شده روی ذخیره‌سازی توضیحات محصول (ادمین+فروشنده)، مقاله (store/update)، صفحه؛ **خوراک پکیج‌ها** (HTML از API ثالث) در رندر هر دو پنل پاکسازی می‌شود؛ پیام تراکنش پنل قدیمی escape شد
- **RCE آپلود**: `config/file-manager.php` قبلاً `allowFileTypes=[]` + `maxUploadFileSize=null` بود (هر فایلی از جمله PHP!) → لیست سفید + ۲۰MB؛ آپلود تنظیمات قالب (Controller+Livewire) بدون هیچ validate بود → قانون image/mimes/8MB؛ **SVG** در MediaController و fallback → `SvgSanitizer` (حذف script/on*/javascript: URL/foreignObject/entity) قبل از ذخیره
- **`.htaccess`**: قطع اجرای PHP در `uploads|storage|themes` + مسدودسازی `.env/.git/composer.json` از وب؛ فایل `.htaccess` مدفون در `public/uploads/` و `storage/app/public/` (php_flag engine off + FilesMatch deny) — دفاع در عمق حتی اگر قانون اصلی بازنویسی شود

### ۴) احراز هویت/مجوز/کانفیگ
- **IDOR بحرانی API**: `Api/V1/OrderController::show` هر کاربر هر سفارشی را می‌خواند (PII کامل: نام/موبایل/آدرس) → گارد مالکیت
- **کانال‌های broadcast عمومی**: `OrderPaid`/`SendMessage`/`SendMessagePopUp`/`ContactCreated` کل مدل‌ها را روی کانال public پخش می‌کردند → PrivateChannel + `broadcastWith()` فیلترشده + مجوز واقعی در `routes/channels.php` (فقط ادمین پنل برای orders/contacts/send-message؛ خودِ کاربر برای inbox-user.{id})
- **پیام‌های OTP**: `VerifyCodeSent`/`NewUserCodeSent`/`NewSellerCodeSent` → `random_int`
- **AuthServiceProvider**: گیت‌ها برای Seller `return true` بودند → `false`
- **SettingController**: ۷ متود فروشنده‌تنظیم (commission/econtract/hero) بدون `$this->authorize` → اضافه شد
- **نصب‌گر وب**: `installedAlreadyAction='abort'`، `updaterEnabled=false`، ثبت `storage/installed` پس از نصب، قفل اتمی ساخت اولین ادمین (cache lock) + رمز min:8 + regex برای admin_route_prefix
- **`.env.example`**: APP_KEY هاردکد حذف (قبلاً با کپی خودکار روی .env ⇒ کلید عمومی شناخته‌شده!) + `APP_DEBUG=false` + راهنمای TRUST_PROXIES/CORS
- **Session**: `same_site` از `none` به `lax` | **CORS**: از `*` هاردکد به `CORS_ALLOWED_ORIGINS` | **Sanctum**: انقضای توکن ۳۰ روز + رفع deprecation `parse_url`
- **TrustProxies**: از کلاس حذف‌شده‌ی fideloper به کلاس داخلی لاراول + کنترل با `TRUST_PROXIES` (رفع 500 در نسخه ۱۳)
- **هدرهای امنیتی**: میان‌افزار `SecurityHeaders` سراسری (X-Frame-Options/X-Content-Type-Options/Referrer-Policy/Permissions-Policy)
- متفرقه: حذف `refresh-csrf` عمومی (403 بدون لاگین)، حذف `auth.password_confirmed_at` پس از ورود (دور زدن password.confirm)، ساده‌سازی VerifyCsrfToken (حذف استثنای پویا از کانفیگ ناموجود)، حذف ۴ عدد `dd()` باقی‌مانده در اسکریپر محصول، رمز min:8 در UserForm، throttle روی login قدیمی و API login و مسیرهای OTP

### ۵) رفع باگ Livewire 404 (معمای فاز قبل)
- ریشه‌یابی شد: پیشوند اندپوینت لایووایر ۴ از `sha256(APP_KEY.'livewire-endpoint')` ساخته می‌شود (`EndpointResolver::prefix`)؛ با تغییر/ناهمخوانی APP_KEY بین محیط‌ها (یا نبود key:generate پس از deploy) مسیرهای `/livewire-{hash}/update` و JS در HTML قدیمی به 404 می‌خورند. تست‌های این فاز (ورود Livewire با پست‌های متعدد update) سالم اجرا شد ⇒ راه‌حل عملیاتی: APP_KEY ثابت در هر سرور + `php artisan key:generate` فقط یک‌بار

### ۶) وریفای E2E (مرورگر واقعی)
- پنل جدید: ورود موفق → داشبورد، لیست محصولات ✓، `refresh-csrf` بدون لاگین 403 ✓
- کپچا: رندر 200x64 PNG ✓، ورود بدون کد → «وارد کردن کد امنیتی الزامی است» ✓، ورود با کد خوانده‌شده توسط VLM → موفق ✓ (یعنی کپچا انسانی‌پذیر و ماشینی‌ناپذیر نیست ولی برای ربات‌های ساده کافی است)
- پنل قدیمی: قفل تدریجی با پیام فارسی ✓، اجبار کپچا هنگام فعال بودن ✓
- ربات‌گیر: ضربه به `wp-admin` → ثبت در bot_traps → 403 کل سایت (وب+کپچا) ✓؛ پاکسازی دستی ⇒ رفع فوری (کش) ✓
- build: `bun run build:admin` — ~12s بدون warning؛ manifest سازگار

## فاز ۱۶ — دور پنجم بازخورد کاربر (۱۱ مورد: سایدبار/منو، لودینگ سراسری، توست، فایل‌منیجر، اعلان‌های هدر)

### ۱) سایدبار (E1 + E2)
- **باز ماندن منوی والد**: قبلاً همه آکاردئون‌ها همیشه با `open=false` شروع می‌شدند؛ حالا `apMenuGroup.init()` اگر آیتم فعال (`.is-active`) داخل گروه باشد آن را باز نگه می‌دارد (مثال کاربر: در صفحه دسته‌بندی وبلاگ، منوی وبلاگ بسته بود)
- **اسکرول روی منوی فعال**: `restorePosition` بازنویسی شد — حلقه تلاش (تا ۱۰ بار) تا گروه والد باز شود، سپس اسکرول نرم (scrollTo smooth) آیتم فعال به یک‌سوم بالای کادر
- **جستجوی منو**: هنگام تایپ در جستجوی منو، همه گروه‌ها به‌طور خودکار باز می‌شوند (رویداد `ap-menu-open-all`)
- **ترتیب گروه‌ها** (config/admin-menu.php): نمای کلی ← عملیات محصولات ← امور مالی و سفارشات ← امور فروشندگان ← مدیریت محتوا ← امور ظاهری ← امور پشتیبانی ← امور کاربران و مدیران ← امور مدیریتی
- **ترتیب آیتم‌ها**: محصولات اول گروه محصولات شد (انبارداری به انتها)، صفحات بعد از وبلاگ، منوها بعد از قالب، گزارشات به انتهای مالی، «نشست‌های فعال» و «مانیتور سیستم» از نمای کلی به ابتدای «امور مدیریتی» منتقل شدند

### ۲) دکمه جزئیات جستجوها (E3) — ریشه‌یابی باگ کلاس Livewire
- **علت**: پراپرتی `public bool $showDetails` و متد `showDetails(int $index)` هم‌نام بودند؛ در پروکسی `$wire` لایووایر پراپرتی بر متد اولویت دارد ⇒ `w.showDetails is not a function` و دکمه هیچ کاری نمی‌کرد
- **فیکس**: متد → `openDetails()` (View + wire:target هماهنگ شد)
- **اسکن سراسری همین الگو**: `LinkIndex` هم داشت! (`$createLink`/`$editLink` پراپرتی فرم در برابر متدهای `createLink()`/`editLink()`) → متدها به `storeLink()`/`openEdit()` تغییر نام یافتند (فرم ایجاد لینک و دکمه ویرایش قبلاً کاملاً خراب بودند)

### ۳) لودینگ سراسری (E4)
- هر کلیکی که درخواست Livewire می‌سازد (`wire:click`/`wire:submit`) لحظه‌ای کلاس `ap-clicking` می‌گیرد: محو + اسپینر `::after` (CSS خالص)
- نوار پیشرفت برند `#ap-top-progress` بالای صفحه در طول هر کامیت/ناوبری (فقط برای تعامل کاربر؛ wire:poll پس‌زمینه نوار را روشن نمی‌کند)
- شمارش کامیت‌های موازی با هوک `Livewire.hook('commit')` (succeed/fail) + پاک‌سازی خودکار؛ سازگار با تعویض DOM توسط wire:navigate (دریافت مجدد المان)

### ۴) توست‌ها — باگ اصلی کل پنل (E5)
- **ریشه‌یابی**: در `apToast` آبجکت `{...e.detail, visible:false}` خام بود و `t.visible=true` خارج از پروکسی واکنش‌گرای Alpine جهش می‌شد ⇒ **هیچ توستی از ابتدای پروژه هرگز در پنل جدید نمایش داده نمی‌شد**
- **فیکس**: `Alpine.reactive({...})` + پیام اعتبارسنجی با تعداد خطا: «۳ خطای اعتبارسنجی وجود دارد — …» (ارقام فارسی) در کنار خطاهای زیر فیلد
- E2E: خطای اجباریِ اسلایدر → هم توست قرمز نمایش داده شد هم خطای فیلد ✓

### ۵) گالری ویجت‌ها و فرم‌ها (E6)
- عنوان مودال: «گالیــری…» → «گالری ویجت‌ها و فرم‌ها»
- بازخورد کپی: دکمه ۲.۵ ثانیه به «کپی شد ✓» سبز تغییر می‌کند + توست موفقیت؛ پشتیبان `execCommand` برای مرورگرهای بدون Clipboard API

### ۶) ابزارک‌ها (E7)
- **z-index**: مودال «انتخاب نوع» از `z-[60]` به `z-[75]` — بالاتر از مودال مشخصات (`z-[65]`) چون از داخل ویرایش هم باز می‌شود (قبلاً زیر می‌رفت)
- **فیلدهای تنظیمات تخصصی**: گرید تک‌ستونه در مودال + `w-full` برای input/select + `col-span-full` برای درخت دسته‌بندی

### ۷) خطای `Admin::hasBoughtProduct()` (E8)
- تم فروشگاهی `auth()->user()->hasBoughtProduct()` صدا می‌زند؛ اگر سشن ادمین resolve شود ⇒ Fatal. به `Admin` متد `hasBoughtProduct()` + رابطه امن `orders()` (همیشه خالی، گارد `1=0` — مدیر سفارش ندارد) اضافه شد؛ `hasBought()` قبلی هم وابسته به همین رابطه بود و Fatal می‌داد

### ۸) به‌هم‌ریختگی تنظیمات قالب (E9) — تداخل بوت‌استرپ/تیلویند ۴
- **ریشه‌یابی**: کانفیگ قالب کلاس‌های قدیمی `col-md-3 col-6` می‌فرستد؛ تیلویند ۴ برای هر عدد ابزارک داینامیک می‌سازد: `.col-6 { grid-column: 6 }` ⇒ آیتم به ستون ۶ می‌رود و گرید ۶ ستونه خراب می‌شود (دقیقاً بهم‌ریختگی اسکرین‌شات کاربر)
- **فیکس**: هلپر `ap_clean_legacy_class()` (حذف col/col-N/col-{bp}-N بوت‌استرپ، حفظ col-span-2 تیلویند) اعمال روی تنظیمات قالب + آپشن‌های ابزارک‌ها
- **تصاویر تنظیمات قالب**: فیلدهای file به `<x-admin-panel::media-picker>` تبدیل شدند (انتخاب از رسانه‌های سایت = درخواست قبلی «از فایل‌های خود سایت»)؛ `ThemeSettings` دیگر آپلود مستقیم ندارد — مسیرها مثل بقیه تنظیمات ذخیره و لاگ می‌شوند
- سوییچ بله/خیر عرض کامل، فیلدهای متن `w-full` و `min-w-0`

### ۹) فایل‌منیجر به‌هم‌ریخته (E10) — تحریم CDN
- **ریشه‌یابی**: `fmButton.blade.php` بوت‌استرپ/آیکون‌ها را از `cdn.jsdelivr.net` می‌خواند — در ایران فیلتر است ⇒ آیکون‌ها حذف و گرید فروریخته (اسکرین‌شات کاربر). ضمناً آیکون‌های خود FM از FontAwesome (`fas fa-*`) هستند نه bootstrap-icons!
- **فیکس**: بوت‌استرپ ۵.۳.۲ + FontAwesome ۵.۱۵.۴ (CSS+webfonts) به‌صورت لوکال در `public/vendor/file-manager/` + ویو به‌روزشده
- **مودال زیر سایدبار**: `lg:pr-[17rem]` روی رپر مودال — دکمه بستن همیشه در دسترس
- E2E کامل: باز کردن از انتخابگر رسانه → آیکون‌ها ✓، پوشه‌ها ✓، ورود به پوشه ✓، دبل‌کلیک فایل → `fmSetLink` → پر شدن فیلد + پیش‌نمایش + بستن مودال ✓

### ۱۰) اعلان‌های هدر (E11)
- زنگوله از «لینک به پنل قدیمی» به **کامپوننت Livewire جدید `HeaderNotifications`** تبدیل شد:
  - شمارنده نخوانده‌ها (بج قرمز با ارقام فارسی، `wire:poll.60s`)
  - دراپ‌داون ۶ اعلان آخر: آیکون گرادیانی لوسید، عنوان فارسی نوع، زمان نسبی (امروز/دیروز/جلالی)، نقطه نخوانده
  - کلیک روی اعلان → `markAndGo` (خوانده‌شدن + ریدایرکت SPA با `navigate: true` به مسیر **پنل جدید**)
  - «خواندن همه» + «مشاهده همه اعلان‌ها» → `np.notifications.panel`
- `AdminNotificationTypes::npLink()`: نگاشت کامل انواع به روت‌های np.* (سفارش/کاربر/فروشنده/تیکت/دیدگاه/تماس/…) با fallback امن
- E2E: بج «۵» ✓، دراپ‌داون ✓، کلیک «سفارش جدید ثبت شد» → `/orders/1` پنل جدید + کاهش نخوانده در DB ✓

### ۱۱) وریفای و بیلد
- مرورگر واقعی: ورود با کپچا → داشبورد، سایدبار جدید (رندر ۱۰۱ لینک/۸ گروه)، تست‌های بالا
- تست به‌روزرسانی سایدبار نیازمند seed پرمیشن‌ها بود (PermissionSeeder اجرا شد — در DB کاربر موجود است)
- `bun run build:admin` ~۱۱s — بدون warning

---

## فاز ۱۷ — دور ششم بازخورد کاربر (۱۳ مورد: منوها، پکیج‌ها از پنل قدیمی، تب‌ها و لودینگ، مودال‌ها، بازطراحی sms/permissions/filters، باگ attribute/create و دکمه‌های بی‌استایل)

### ۱) منوها (F1 + F9) — `config/admin-menu.php`
- «نشست‌های فعال» و «مانیتور سیستم» از «امور مدیریتی» به بالای منو (گروه «نمای کلی»، بعد از داشبورد/فعالیت‌ها) برگشتند — درخواست صریح کاربر
- «بروزرسانی سیستم» فعلاً از منو حذف شد (روت `np.developer.index` همچنان کار می‌کند) + کامنت فارسی فاز ۱۷
- E2E: positions داشبورد=0، نشست=2، مانیتور=3، بدون «بروزرسانی سیستم» ✓

### ۲) بازار پکیج‌ها — بازسازی کامل از پنل قدیمی (F2)
- **کارت‌ها مثل قدیمی**: رسانهٔ بزرگ h-40 با placeholder، نشان‌های رنگی (رایگان با اشتراک/خریداری‌شده/رایگان/پولی)، مُهر «نصب‌شده» و نوار «در صف نصب/در حال نصب…» روی رسانه، اورلی hover «مشاهده جزئیات»، چیپ نسخه، **توضیح HTML غنی با `sanitize_rich_text`** (نه متن خام)، چیپ‌های نویسنده/دسته، فوتر قیمت «از X تومان»
- **دکمه‌های حالت‌مند مثل قدیمی**: در صف نصب (disabled) / در حال نصب (اسپینر) / آپدیت (hasUpdate با version_compare) / به‌روز / نصب با اشتراک / برسی و نصب / نصب
- **مودال تأیید نصب (پورت install-confirm-modal + index.js)**: بررسی خرید/اشتراک با اکشن جدید `checkPurchaseState()` (پورت checkPurchase: اولویت اشتراک فعال ← لایسنس خرید قبلی با verifyLicense ← جریان عادی)، کارت‌های انتخاب پلن با رادیو + پیش‌فرض ارزان‌ترین + نشان «ارزان‌ترین/یک‌بار مصرف» + قیمت خط‌خورده تخفیف، باکس لایسنس «قبلاً خریداری شده — بدون پرداخت»، باکس اشتراک، هشدار انقضا، لیبل دکمهٔ هوشمند (نصب پکیج/پرداخت و نصب/نصب با اشتراک/بررسی و نصب)
- نوار پیشرفت نصب با `wire:poll.4s="refreshPending"` (پورت progress.js) + توست نتیجه نصب کامل/ناموفق با آخرین لاگ خطا
- بنر اشتراک فعال/CTA با کد پروژه + دکمه کپی با بازخورد «کپی شد»، فیلتر دسته‌بندی select مثل قدیمی (فروشگاه/پرداخت/اعلان/سئو) + reset
- `PackageApiService` با توکن/URL خالی دیگر Fatal نمی‌دهد (صفحه پیام خطا + تلاش مجدد) — فیکس 500 صفحه پکیج در محیط بدون env
- فیکس باگ جدید: `paymentModal` بعد از تغییر x-data ریشه به `pkgMarket` تعریف نشده بود
- E2E با داده seed شده در کش: ۳ کارت (پولی/رایگان/اشتراکی) ✓، مودال پلن‌ها با پیش‌فرض ارزان‌ترین ✓، سوییچ پلن ✓، بستن با بک‌دراپ ✓، جریان رایگان ✓

### ۳) تب‌ها — سوییچ فوری (F3)
- **آمار سفارش‌ها**: کش سمت سرور `loadedTabs[cacheKey|tab]` — کلیک روی تب لودشده دیگر `$wire.loadTab` نمی‌زند (سوییچ 0ms)؛ نقطه سبز «آماده» روی تب‌های کش‌شده؛ با تغییر فیلتر بازه کش بی‌اعتبار؛ اسکلت «در حال آماده‌سازی نمودار…» داخل کارت (نه نوار سراسری)
- **تغییر گروهی قیمت**: `$wire.set('type', key, false)` (deferred) — تب‌سوییچ بدون رانtrip سرور؛ type هنگام ذخیره سینک می‌شود
- تب‌های بقیه صفحه‌ها (فرم محصول/تنظیمات/نشست‌ها) Alpine خالص و از قبل فوری (اندازه‌گیری ۱-۶ms)

### ۴) لودینگ فقط جایی که لازم است (F4)
- **ریشه‌یابی**: سلکتور `[wire:click],[wire:submit]` خودِ `<form>` را می‌گرفت ⇒ کلیک روی اینپوت/متن/لیبل داخل فرم، کل فرم را محو+اسپینردار می‌کرد؛ هدر آکاردئون‌ها هم اسپینر می‌گرفت
- سلکتور جدید: فقط `button/a/[role=button]/label` با wire:click + دکمه submit داخل `form[wire:submit]` (شامل دکمه‌های بیرون‌فرم با `form=` مثل تنظیمات)؛ ایمنی ۸ ثانیه برای پاک‌سازی اسپینر
- E2E: کلیک اینپوت/هدر/لیبل → بدون اسپینر و بدون dim ✓؛ دکمه ذخیره → اسپینر ✓

### ۵) مودال فایل‌منیجر (F5)
- حذف `lg:pr-[17rem]` → **وسط کامل صفحه**؛ z-index از 70 به **200** (بالای builder z-80 و مودال‌های z-75)؛ توست‌ها هم به z-[210] بالای همه مودال‌ها
- E2E: centered=true (مرکز دقیق)، z=200، لود کامل آیکون‌ها/پوشه‌ها ✓

### ۶) بستن مودال‌ها (F6) — بررسی سیستمی
- اسکن خودکار تمام bladeها: دکمه انصراف/بستن بدون type داخل فرم = ۰ مورد؛ هندلرهای بستن به متغیر تعریف‌نشده = فقط باگ paymentModal (فیکس شد)
- تست عملی بک‌دراپ/X: مودال نصب پکیج، مودال افزودن مشخصات، فایل‌منیجر، جزئیات جستجوها — همگی بسته می‌شوند ✓
- **فیکس سیستمی مهم**: کامپوننت `x-admin-panel::icon` اتریبیوت‌های اضافی (x-show، x-on:click، wire:loading و…) را **بی‌صدا حذف می‌کرد** (render فقط name/class می‌ساخت) — حالا از طریق ComponentAttributeBag در کلوژور رندر merge می‌شوند (سوییچ کپی/تیک و مشابه‌ها در کل پنل درست شد)

### ۷) بازطراحی تنظیمات پیامک (F7) — `admin/settings/sms`
- ۳ تب Alpine فوری: «تنظیمات عمومی» (کارت‌های رادیویی انتخاب ارائه‌دهنده با sync deferred + موبایل مدیر) / «رویدادهای پیامکی» (۱۵ سوییچ مدرن با ریلی CSS در گروه‌های ثبت‌نام/سفارش/کیف پول/مناسبت) / «ارائه‌دهنده و پترن‌ها» (credentials + پترن‌های گروه‌بندی‌شده با بج تعداد/ضروری/فعال، نمونه پترن بازشونده + کپی با بازخورد)
- پارشیال جدید `sms-switch.blade.php` (سوییچ با inset منطقی — RTL/LTR درست) جایگزین چک‌باکس خام؛ پارشیال قدیمی sms-toggle حذف شد
- E2E: تب‌ها ✓، سوییچ wire:model ✓، پترن‌ها با بج‌ها ✓

### ۸) بازطراحی مدیریت دسترسی‌ها (F8) — `admin/permissions`
- هدر گرادیانی + بج زنده «X دسترسی فعال» + انتخاب همه/حذف انتخاب‌ها (با confirm) + جستجوی زنده گروه/مورد
- وضعیت انتخاب سمت کلاینت (`apPermissions` Alpine): کسکید والد/فرزند فوری، شمارنده و بج هر گروه لحظه‌ای، چیپ‌های فرزند با حالت فعال — ذخیره با **یک کامیت** `$wire.save(perms)` (امضای save به `?array $selected` تغییر کرد؛ بدون پارامتر مثل قبل)
- کارت‌های دوستونه با بج «n از m فعال» + نقطه «انتخاب ناقص» روی والد
- E2E: ۴۲ گروه ✓، کسکید (۲۳۹ زنده) ✓، ذخیره + توست موفقیت ✓

### ۹) بازطراحی فیلترها (F10) — `admin/filters`
- **اینکس کارت‌محور** با آیکون/نوع/تعداد + empty-state با CTA
- **فرم بازطراحی‌شده**: سوییچ «برای صفحه فروشندگان»، ردیف‌های فشرده با شماره ترتیب/بج نوع/سوییچ فعال/جداکننده/ابزار جابجایی
- **مودال «افزودن مشخصات» (پورت add-filterable-modal)**: جستجوی زنده بین ۳ گروه (فیلتر ثابت/گروه ویژگی/مشخصات)، افزودن مستقیم با + (اکشن جدید `addFilterableRow` با جلوگیری از تکرار + توست)، آیتم اضافه‌شده سبز با تیک، چندافزودنی پشت‌سرهم، «اتمام و بستن»
- فیکس‌های مسیر: expression Alpine «q is not defined» (استیت جستجو به x-data خود مودال منتقل شد)، آیکون‌های +/✓ که به‌خاطر باگ Icon هر دو نمایش داده می‌شدند
- E2E: مودال ✓، جستجو (رم → ۱ مورد) ✓، افزودن ۲ مورد + توست ✓، ذخیره → ریدایرکت + کارت جدید در اینکس ✓ (با seed داده demo)

### ۱۰) باگ‌ها
- **`attributes/create` Fatal**: `attribute-form.blade.php:19` بدون گارد null (`$attribute->id` در حالت ایجاد) — هدر شرطی شد + عنوان «ایجاد ویژگی جدید»؛ E2E ✓
- **دکمه‌های بی‌استایل (F12)**: `ap-btn` پایه خنثیِ بدون ظاهر بود — حالا دکمه مدرن کامل (border + bg-surface-2 + hover + active:scale) با `:where` در دارک تا variantها همیشه برنده بمانند؛ utilityها (bg-red-600 و…) همچنان بر آن می‌نشینند؛ کلاس کمکی `.ap-rich` برای HTML غنی پاک‌سازی‌شده

### ۱۱) وریفای و بیلد
- ورود با کپچا (خواندن تصویر) → همه صفحات بالا در مرورگر واقعی چک شد
- فیکس کور: `refreshAfterInstall(?string $slug = null)` (deprecation)، `Str::limit` جایگزین `str_limit`
- `bun run build:admin` ~۱۰s تمیز؛ manifest + assets به‌صورت اتمیک کامیت شدند
- داده‌های دمو برای تست در DB سندباکس: StaticFilter/AttributeGroup/Specification + ۳ پکیج ساختگی در کش (کلید packages.list.ddab7a0c…) — برای تست UI

---

## فاز ۱۸ — دور هفتم بازخورد کاربر (مودال فایل‌منیجر زیر سایدبار، پیامک تب اول، ویجت شناور با پیش‌نمایش زنده، درگاه‌ها + لوگوی بانک، شبکه‌های اجتماعی کامل، بازطراحی کامل پشتیبان‌گیری + بازیابی)

### ۱) مودال «مدیریت فایل‌های سایت» همیشه روی همه (G1)
- علت ریشه‌ای: مودال داخل سلسله‌مراتب صفحه رندر می‌شد؛ اگر پدری stacking context می‌ساخت، مودال زیر سایدبار (z-50) و هدر می‌افتاد
- فیکس: `<template x-teleport="body">` در `media-picker.blade.php` + z-[300] → مودال در body رندر می‌شود و همیشه بالای سایدبار/هدر/مودال‌های دیگر است (اسکوپ Alpine حفظ می‌شود)
- E2E: باز شدن وسط و روی همه ✓، بستن با دکمه ✓

### ۲) تنظیمات پیامک — اطلاعات اتصال به تب اول (G2)
- کارت «اطلاعات اتصال به پنل» به تب «تنظیمات عمومی» منتقل شد و بسته به ارائه‌دهنده انتخابی (Alpine provider) فیلدهایش عوض می‌شود (x-show per provider — همه inputها در DOM هستند تا save هر ۵ پنل را ذخیره کند)
- تب سوم به «پترن‌های ارسال» تغییر نام داد (فقط پترن‌ها)
- E2E: تغییر ارائه‌دهنده → کاوه‌نگار «کلید وب سرویس» و حذف «شماره ارسالی» ✓

### ۳) ویجت شناور پشتیبانی — بازطراحی + پیش‌نمایش زنده (G3)
- چیدمان دو ستونه: تنظیمات (ظاهر دکمه با swatchهای رنگی، پنل گفتگو، اطلاعات تماس، ۶ شبکه) + ستون پیش‌نمایش sticky
- **پیش‌نمایش زنده**: شبیه‌سازی کامل ویجت فرانت (قاب سایت اسکلت‌مانند + FAB با pulse + پنل با خوشامدگویی/ساعات کاری/تماس/کانال‌های گفتگو/ردیف شبکه‌ها) — همه reactive به $wire بدون رفت‌وبرگشت (الگوی `$wire.set(..., false)` — درس فاز ۱۷-د)
- سوییچ فعال/غیرفعال مدرن + حالت «ویجت خاموش است» روی پیش‌نمایش
- فیکس: `:style` روی کامپوننت icon (PHP eval) → wrap در span؛ `:class` روی icon → `x-bind:class`
- E2E: تایپ خوشامدگویی → پیش‌نمایش فوری ✓، کلیک swatch فیروزه‌ای → FAB و پنل سبز شدند ✓

### ۴) درگاه‌های پرداخت — بازطراحی + لوگوی بانک (G4)
- کارت‌های برنددار ۲ ستونه با نوار رنگی برند + آواتار حرف اول/لوگو + چیپ key + سوییچ فعال مدرن + آکاردئون
- **تغییر تصویر بانک‌ها**: فیلد `logo` به gatewayFields همه درگاه‌ها اضافه شد (config GatewayConfig) + media-picker فایل‌منیجر سایت داخل هر کارت + نمایش لوگو در هدر کارت
- متد جدید `gatewayBranding()` (رنگ برند ۱۱ درگاه)؛ `faNum` حذف (fa-digits CSS)
- **فیکس داده**: ordering null در رکوردهای firstOrCreate → update با '' خطای SQL می‌داد → loadForm حالا null را '0' می‌کند
- E2E: فعال‌سازی زرین‌پال + merchantId + لوگو → ذخیره → DB تایید ✓، لوگو در هدر کارت و پیش‌نمایش ✓

### ۵) شبکه‌های اجتماعی — بازطراحی + همه شبکه‌ها (G5)
- کاتالوگ ۱۴ شبکه (تلگرام/اینستاگرام/واتساپ/آپارات/ایتا/روبیکا/بله/ایکس/فیسبوک/یوتیوب/لینکدین/تردز/توییچ/دیسکورد) با رنگ برند + ادغام تعریف‌های تم (badge «تم» + شبکه‌های سفارشی تم اضافه می‌شوند)
- جستجوی زنده + فیلتر همه/پرشده/خالی با شمارنده‌های زنده + بج «X از Y تکمیل شده» + دکمه پاک‌کردن هر فیلد
- وضعیت filled با استیت reactive Alpine (vals mirror) — قبلاً DOM-خوانی غیر-reactive بود
- فیکس: `@js()` داخل تگ کامپوننت در scope کامپوننت eval می‌شود (متغیر لوپ نمی‌رسد) → جایگزینی با `'{{ }}'`
- ۱۴ آیکون به icons-map اضافه شد (facebook/instagram/twitter/youtube/linkedin/twitch/at-sign/gem/play/table/file/power/palette/headphones)
- E2E: ۱۴ کارت ✓، پر کردن تلگرام → دایره سبز + شمارنده «۱ از ۱۴» ✓، فیلتر پرشده → ۱ کارت ✓، ذخیره → option ذخیره شد ✓

### ۶) پشتیبان‌گیری و بازیابی — بازطراحی کامل + سیستم جدید (G6+G7)
- عنوان منو/صفحه «پشتیبان‌گیری»؛ سرویس جدید `App\Services\Backup\SiteBackupService` جایگزین جریان spatie در این صفحه (کنترلر قدیمی دست‌نخورده)
- **رفع باگ «پیغام موفق ولی بکاپ ساخته نمی‌شود»**: علت = Job صف‌شده (dispatchAfterResponse) + وابستگی mysqldump که در اکثر هاست‌ها نبود؛ نسخه جدید **همگام و PHP-only** است (PDO dump + ZipArchive)
- **مودال ساخت انتخابی** (تمام موارد، پیش‌فرض همه تیک‌خورده، تب‌بندی + «همه» در هر تب): تب جداول (۱۳۲ جدول با تعداد رکورد + جستجو)، تب آپلودهای سایت (public/uploads به تفکیک زیرپوشه با تعداد/حجم/نوع)، تب رسانه‌های مدیریت (storage/app/public) — انتخاب کاملاً کلاینت‌ساید (Alpine apBackupCreate) و ارسال آرایه‌ها به `create(tables, groups)` با سفیدلیست سمت سرور
- فرمت ZIP: `manifest.json` + `database.sql` (DROP IF EXISTS + CREATE + INSERT تک‌ردیفی) + فایل‌ها با مسیر نسبی base_path (public/uploads/... ، storage/app/public/...) → بازیابی دقیقاً سر جای خودش
- **بازیابی (skip-if-exists)**: آپلود ZIP یا فایل آماده در `storage/app/backups/incoming`؛ پارسر SQL با درک نقل‌قول/کامنت؛ جدول موجود → کل بلوکش رد می‌شود؛ DROP هرگز اجرا نمی‌شود (هیچ داده‌ای حذف/بازنویسی نمی‌شود)؛ فایل موجود → رد؛ zip-slip guard + سقف حجم/تعداد ورودی
- **گزارش آخرین بازیابی** (last-restore.json): بج‌های خلاصه (افزوده/رد‌شده/ناموفق رکورد و فایل، جداول ساخته/رد‌شده) + جدول per-table (افزوده‌شده/رد شده (موجود)/اصلاح‌شده/ناموفق) + هشدار «N جدول از بازیابی رد شد» + یادداشت خطاها
- لیست «فایل‌های پشتیبان و عملیات آخر» با دانلود/حذف + مودال تایید حذف
- تست بک‌اند (tinker): ساخت ۱۳۲ جدول/۳۴۷۸ ردیف/۳۳ فایل → ZIP 3.7MB ✓ | بازیابی روی همه موجود → 0 افزوده/3478 رد/0 خطا ✓ | حذف یک جدول → ساخته شد + ردیف‌هایش درج شد ✓ | حذف یک فایل → بازگشت ✓
- E2E: مودال (تب‌ها/همه/جستجو/خلاصه) ✓، ساخت از UI → توست + فایل ✓، بازیابی آپلودی ✓، بازیابی incoming ✓، کارت گزارش ✓

### یادداشت‌های فنی برای فازهای بعد
- **Blade + attr کامپوننت‌ها**: هرگز `:attr`/`x-bind` با متغیر لوپ یا `@js()` روی تگ `x-...` (کامپوننت) — `:attr` به‌عنوان PHP و `@js` در scope کامپوننت eval می‌شود؛ روی المان‌های معمولی آزاد است
- `dispatchAfterResponse` + صف غیر-sync = Job هرگز اجرا نمی‌شود؛ کارهای سنگین مدیریتی یا sync یا جاب با worker
- `SHOW CREATE TABLE` → ستون ۱ (FETCH_NUM[1])، نه fetchColumn پیش‌فرض
- محیط سندباکس: هیچ پروسه‌ای بین دو ابزار زنده نمی‌ماند → `/home/z/start_stack.sh serve` در ابتدای هر فراخوانی (MariaDB در /home/z/bin/mariadb-full، دیتادایر /home/z/mariadb-data، دیتابیس shop/shop-shoppass، ادمین admin/admin1234، state مرورگر /tmp/ap_auth.json)

---

## فاز ۱۹ — دور هشتم بازخورد کاربر (درون‌ریزی، مودال‌ها، لودر ناوبری، قالب‌ها، جستجوها، عناوین صفحات، سفارشات، منوی فعال)

> ۱۰ آیتم (H1..H10) — همه با راستی‌آزمایی مرورگری؛ آخرین کامیت فاز ۱۸: `217a3a24`

### ۱) درون‌ریزی — بازطراحی کامل ۳ صفحه (H1)
- سه صفحه (محصولات/مقالات/کاربران) با ساختار مشترک جدید: هیرو گرادیانی + کارت فیلدها + **پیش‌نمایش زنده شبیه اکسل** + آپلود درگ‌اند‌دراپ + کارت «فایل‌های آپلودشده» + کارت خطاها
- **پیش‌فرض همه‌ی فیلدها تیک‌خورده** (`defaultAllFiltersChecked` در mount هر سه؛ دکمه‌های «انتخاب همه/هیچکدام» با حفظ الزامی‌ها)
- **پیش‌نمایش زنده شبیه اکسل** (`partials/excel-preview.blade.php`): ردیف حروف ستون‌ها (A,B,C…) با `$this->excelColumn()`، ردیف ۱ = عنوان ستون‌ها، ردیف ۲/۳ = داده نمونه (`sample` به فیلدهای محصولات اضافه شد)، ردیف خالی؛ با تیک/برداشتن تیک فیلدها ستون‌ها بلافاصله تغییر می‌کنند (wire:model.live) + بج «N ستون فعال» زنده
- **فایل‌های آپلودشده با حذف** (فاز ۱۹): `uploadedFiles()` Computed روی دیسک storage در `imports/{type}` (فیلتر بر اساس baseFilename) + `deleteUploadedFile(name)` امن (basename + سفیدلیست prefix)
- ریفکتور مشترک: ۴ پارشیال جدید در `imports/partials/` (import-form، excel-preview، uploaded-files، error-panel) — ویوها نازک شدند
- ۴ آیکون جدید به icons-map (list-checks، file-up، cloud-upload، inbox)
- E2E: برداشتن تیک «برند» → بج ۱۹→۱۸ و حذف ستون از گرید ✓

### ۲) مودال‌های مقام‌ها/سوالات متداول بسته نمی‌شدند (H2)
- علت: `x-on:click="closeFormModal"` روی لایه/دکمه انصراف — closeFormModal **متد Livewire** است نه تابع Alpine (ReferenceError بی‌صدا)؛ همان اسکن سراسری: فقط roles + faqs
- فیکس: بستن فوری `open = false` (entangle.live سینک می‌کند؛ resetForm در بازشدن بعدی اجرا می‌شود)
- E2E: کلیک لایه ✓، دکمه انصراف ✓ (هر دو مودال)

### ۳) ناوبری — «آخرین کلیک برنده است» (H3)
- **ریشه**: navigateTo لاراول/Livewire ناوبری درجریان را کنسل نمی‌کند؛ بدتر — abort کنسل‌شده‌ی prefetch درجریان → `whenFailed()` → **refetch زامبی** که آخر می‌رسد و صفحه‌ی قدیمی swap می‌شود (در تست واقعی مشاهده شد)
- **راه‌حل نهایی**: هر fetch ناوبری (هدر `X-Livewire-Navigate`) پشت **دروازه‌ی promise** — با رویداد `alpine:navigate` ناوبری تازه، درخواست‌های URLهای متفاوتِ ناوبری‌های قبلی stale + abort زیرین؛ چون promise دروازه هرگز settle نمی‌شود، Livewire نه swap قدیمی می‌زند نه whenFailed (زامبی حذف شد)
- نوار پیشرفت با هر کلیک ناوبری فوراً از نو شروع می‌شود (capture click → barStart)
- E2E: دو کلیک پشت‌سرهم (کاربران/بنرها در هر دو ترتیب) → همیشه آخرین صفحه لند می‌شود ✓

### ۴) لودر پیشرفت بزرگ‌تر + لودر گرد سراسری (H4)
- `#ap-top-progress` از ۳ به **۵px** با سایه قوی‌تر
- `#ap-nav-spinner` جدید در ابتدای تاپ‌بار (بالای پنل سمت راست) — هم‌گام با نوار: barStart/spinnerStart با هم، پایان هم‌زمان؛ CSS مخصوص (scale+opacity transition + چرخش)
- E2E: حین ناوبری هر دو فعال، بعد از ناوبری خاموش ✓

### ۵) آپلود قالب → داخل مدیریت قالب‌ها (H5)
- «آپلود قالب جدید» به‌صورت **کارت بازشو مدرن** (entangle showUpload) داخل صفحه لیست قالب‌ها با drag&drop کامل + حالت نصب
- `ThemeIndex` حالا store() کامل (پورت ThemeCreate با zip-slip guard) دارد؛ منوی «آپلود قالب جدید» حذف، روت `np.themes.create` حذف، ThemeCreate + ویو حذف
- E2E: دکمه → کارت باز می‌شود ✓

### ۶) جستجوها — بازطراحی کامل + آمار و نمودار (H6)
- هیرو + ۴ کارت آمار (کل/کلیدواژه یکتا/جستجوگر یکتا IP/نسبت محصولات-پست‌ها) + کارت‌های رتبه‌دار «پرجستجوترین‌ها» (۶ رتبه با آیکون جام/مدال) + دونات سهم نوع + میله‌ای افقی کلیدواژه‌ها + روند ۱۴روزه
- متدهای جدید SearchIndex: stats/topKeywords/dailySeries/keywordsChartOptions/typeChartOptions/dailyChartOptions + `openDetailsByKey(keyword,type)` (کارت‌های رتبه مستقیم مودال جزئیات را باز می‌کنند)
- فیکس: formatter برچسب سری ECharts آبجکت params می‌گیرد → formatter جدید `fn:faSeriesLabel` (قبلاً [object Object] می‌نوشت)
- دیتای دمو (۷۴ رکورد ۸ کلیدواژه) در دیتابیس سندباکس

### ۷) منوی دیدگاه‌ها + عنوان و آیکون همه صفحات (H7)
- «دیدگاه‌ها» (np.comments.posts) به زیرمنوی «وبلاگ» منتقل شد
- **تاپ‌بار حالا عنوان واقعی + آیکون هر صفحه** را نشان می‌دهد: `$title` (از #[Title]) صریحاً به topbar پاس می‌شود (کامپوننت anonymous اسکوپ والد نمی‌گیرد) + نقشه route→icon از config منو با تطبیق خانواده (صفحات show/edit آیکون آیتم لیست خانواده را می‌گیرند)
- ۹ صفحه‌ی h1-tan-آیکون: آیکون داخل h1 اضافه شد (کاربران/مرجوعی‌ها/مقالات/اسلایدرها/بنرها/سوالات متداول/مدیران/مقام‌ها/دلایل مرجوعی)
- اسکریپت ممیزی: ۱۵۷ صفحه np → ۴۲ صفحه هدر آیکون‌دار کامل، ۱۰۱ صفحه فقط عنوان تاپ‌بار (الان عنوان+آیکون دارند)، ۱۳ صفحه h1 بدون آیکون (الان آیکون دارند)

### ۸) خطای admin/pages/create (H8)
- `page-form.blade.php:29`: از فاز ۱۷ رندر Icon کلوژور برمی‌گرداند → cast رشته‌ای می‌ترکید («Object of class Closure could not be converted to string»)
- فیکس: فراخوانی کلوژور با `['attributes' => new ComponentAttributeBag()]` — صفحه create/wizedit سالم ✓

### ۹) فیلتر «سفارشات پرداخت شده» + وضعیت فعال منو (H9+H10)
- **ریشه مشترک**: `routeIs()` فقط نام روت را می‌دید → هر ۳ آیتم «همه/جدید/پرداخت‌شده» هم‌زمان is-active می‌شدند؛ صفحات خارج از منو (حرکات انبار و…) هیچ آیتمی را فعال نمی‌کردند
- menu-item.blade.php بازنویسی: تطبیق **پارامترهای query** برای آیتم‌های params دار؛ آیتم بدون پارامتر فقط وقتی فعال است که کلیدهای پارامتری هم‌روت‌ها در URL نباشند ($menuParamKeys از sidebar)؛ **تطبیق خانواده برای آیتم‌های *.index** (حرکات انبار → «لیست انبارها» فعال و گروه باز می‌ماند)
- sidebar: @each → foreach+include (برای رساندن $menuParamKeys به partial) + محاسبه نقشه کلیدهای پارامتری کل منو
- E2E: /orders?status=paid → فقط «سفارشات پرداخت شده» ✓ | /orders → فقط «همه سفارشات» ✓ | حرکات انبار → «لیست انبارها» + گروه باز ✓

### یادداشت‌های فنی فاز ۱۹
- **ساختار navigate در Livewire**: (۱) navigateTo هیچ کنسلی ندارد؛ (۲) abort کنسل‌شده‌ی prefetch درجریان → whenFailed → refetch زامبی؛ راه‌حل درست = دروازه‌ی promise (stale هرگز settle نمی‌شود) — نه abort مستقیم
- ** formatter سری ECharts** آبجکت params می‌گیرد نه مقدار — formatter جدا لازم است
- **کامپوننت anonymous Blade** اسکوپ والد را ارث نمی‌برد — داده‌ها باید صریح پاس شوند (`:title="..."`)
- `x-on:click="methodName"` در Blade فقط توابع Alpine را صدا می‌زند — متدهای Livewire باید `$wire.method()` باشند (الگوی باگ H2؛ با grep `[x-on:@click]="[a-z_]+"` قابل اسکن است)

---

## فاز ۲۰ — بازخورد کاربر (راند ۹): درگاه‌ها (کارت+مودال)، ۴۰۴ بکاپ، سایدبار موبایل، لاگ سیستم ترمینالی، دقت جستجوی منو

### ۱) درگاه‌های پرداخت — بازطراحی کارت‌محور + مودال تغییر تنظیمات (I1)
- **معماری جدید**: به‌جای «فرم سراسری + آکاردئون»، هر درگاه یک کارت مستقل است:
  - کارت: نوار رنگی برند + آواتار لوگو/حرف اول + نام + چیپ کلید + نام بانک + بج «پیکربندی شده/بدون پیکربندی»
  - سوییچ فعال/غیرفعال **روی کارت ذخیره‌ی فوری** می‌شود (`toggleActive`) — بدون فرم سراسری
  - دکمه «تغییر تنظیمات» → **مودال برنددار** (سربرگ گرادیان رنگ برند + لوگو + کلید) با نام/ترتیب/لوگو (media-picker)/فیلدهای اختصاصی
  - ذخیره‌ی مستقل هر درگاه: `saveGateway(id)` — لاگ فعالیت پورت دقیق updateGateways قدیمی (`persistGateway` + `logGatewayChanges`)
- **z-index مودال = ۱۵۰**: بالای سایدبار/هدر، **زیر مودال فایل‌منیجر (z-200)** تا انتخاب لوگو از داخل مودال درگاه کار کند (تست شد ✓)
- **درس مهم**: روی ریشه‌ی کارت هیچ `hover:-translate-y-*` مجاز نیست — transform والد، محتویِ fixed مودال می‌شود و مودال بریده می‌شود (فقط hover:shadow)
- بستن: دکمه X + کلیک پس‌زمینه + ESC + بستن خودکار بعد از ذخیره‌ی موفق (رویداد `gw-saved`)
- **درس مهم**: بلوک `@php ... @endphp` و `@php(...)`های متوالی داخل کامپوننت Livewire توسط ExtendBlade خراب کامپایل می‌شوند (ParseError: unexpected token) → از تگ خام `<?php ?>` استفاده شود

### ۲) رفع ۴۰۴ دانلود/حذف بکاپ (I2)
- **ریشه باگ**: `substr($file, strrpos($file, '/') + 1)` — برای فایل‌های ریشه‌ی دیسک `strrpos` مقدار `false` برمی‌گرداند و `false+1=1` یعنی «اولین حرف نام فایل حذف می‌شود» («backup-….zip» → «ackup-….zip») → `exists()` شکست → `abort(404)` در دانلود و حذف
- فیکس: `basename($file)` در لیست + `resolveBackupPath()` (جستجوی basename در کل دیسک) برای download/delete
- فایل‌های `incoming/` (ورودی‌های بازیابی) از فهرست «بکاپ‌های ساخته‌شده» حذف شدند
- حذفِ فایل‌یافت‌نشده دیگر abort نمی‌کند — toast خطای واضح می‌دهد

### ۳) رفع «سایدبار در موبایل باز نمی‌شود» (I3)
- **ریشه باگ**: دو `x-data="apSidebar"` موازی — یکی روی wrapper در layout و یکی روی `<aside>` → همبرگر `open`ِ wrapper را true می‌کرد (پوشش تیره می‌آمد) اما `open`ِ خود aside false می‌ماند و منو با `translate-x-full` بیرون می‌ماند
- فیکس: حذف `x-data` از `<aside>` → همه از اسکوپ wrapper ارث می‌برند (همبرگر/پوشش/دکمه بستن/جستجو یک state)
- E2E موبایل ۳۹۰px: باز شدن با همبرگر ✓ بستن با X ✓ transform none/backdrop ✓ دسکتاپ بدون رگرسیون ✓

### ۴) لاگ های سیستم — بازطراحی ترمینالی (I4)
- منو دیگر `external` (تب جدید) نیست — صفحه‌ی داخلی
- **طرح ترمینالی مثل نمونه‌ی کاربر**: سربرگ تیره با سه نقطه رنگی + نام فایل + حجم KB + فیلتر سطح + مرتب‌سازی (جدیدترین/قدیمی‌ترین) + جستجوی متنی (debounce) — همه در هدر تیره
- سربرگ صفحه: عنوان + توضیح + «دانلود لاگ» (BinaryFileResponse — پشتیبانی داخلی Livewire 4) + «پاک کردن لاگ» (با wire:confirm)
- رکوردها: بج سطح رنگی + تاریخ + env + متن دوخطی mono + بازکردن جزئیات با کلیک (استک در pre تیره) + حذف تکی + تیک انتخاب
- **نوار حذف گروهی سمت کلاینت (Alpine)** — درس فاز ۱۷: `wire:model` آرایه‌ای deferred تا commit بعدی سینک نمی‌شود → انتخاب/شمارنده/«حذف انتخاب‌شده‌ها» همه Alpine و آنی
- حذف رکورد با **uid (اثر انگشت md5)** نه ایندکس — بعد از تغییر فیلتر همیشه همان رکورد واقعی حذف می‌شود؛ بازنویسی اتمی فایل (tmp+rename)؛ تکراری‌ها به تعداد انتخاب حذف می‌شوند
- فیکس‌های جانبی: برش tail فقط وقتی tail-read واقعی بوده (کل فایل خوانده شده نباید اولین رکورد نابود شود)، `clearstatcache` برای پروسه‌های طول‌عمر، گارد fread صفر/فایل خالی

### ۵) جستجوی دقیق منو (I5)
- **قبلاً**: متن تختِ کل گروه چک می‌شد → جفت‌شدن کلمات دو منو فالس‌پازیو می‌ساخت و کل گروه با همه‌ی منوها می‌آمد
- حالا: هر آیتم جدا (`hasArray`) — گروه فقط اگر آیتمی منطبق داشته باشد؛ آیتم‌های غیرمنطبق مخفی؛ پدر فقط با لیبل خودش یا یکی از فرزندان؛ فرزند با لیبل خودش یا پدر؛ حین جستجو زیرمنو باز می‌ماند (`open || search !== ''` — بدون جهش state، با پاک‌کردن جستجو حالت قبلی برمی‌گردد)
- نرمال‌سازی قوی‌تر: ي/ك عربی، أإآ→ا، ة→ه، ارقام فارسی/عربی→لاتین، فشرده‌سازی فاصله
- E2E: «بنر» → فقط بنرها+لیست/جدید ✓ | «مانیتور» → فقط مانیتور سیستم ✓ | «سفارشات» → فقط آیتم‌های مرتبط ✓ | پاک‌کردن جستجو → ۱۳۴ لینک/۹ گروه برمی‌گردند ✓

### یادداشت‌های فنی فاز ۲۰
- `strrpos()` برمی‌گرداند false → `false+1=1` — هرگز برای basename از substr+strrpos استفاده نکنید (`basename()` بامن است)
- Livewire ExtendBlade: `@php` بلوکی/متوالی در کامپوننت‌ها ناامن نیست اما تگ خام `<?php ?>` همیشه کار می‌کند
- transform روی والد = محتوی fixed — کارت‌های دارای مودال فقط سایه‌هاور بگیرند
- دو اسکوپ Alpine هم‌نام (wrapper+aside) = باگ کلاسیک «state یکی تغییر می‌کند دیگری نه»
- دانلود فایل از اکشن Livewire 4 با `response()->download()`/`Storage::download()` پشتیبانی می‌شود (SupportFileDownloads، BinaryFileResponse/StreamedResponse)

---

## فاز ۲۱ — بازخورد دور ۱۰ (۱۱ مورد): خطای ذخیره محصول، دریافت از ترب، پیش‌نمایش تصاویر، CKEditor کامل، نصب‌کننده، پروفایل، کش، گارد فرانت، FK جستجو

### ۱) خطای ذخیره محصول — Data truncated / Incorrect integer value (J1)
- **ریشه**: ستون‌های enum/nullable با «رشته خالی» پر می‌شدند (`shipping_nature=''` → خطای 1265؛ `currency_id=''`/`seller_id=''`/`size_type_id=''` → Incorrect integer value روی MySQL strict)
- فیکس در store و update: همه‌ی ستون‌های nullable با `?: null` ذخیره می‌شوند
- **بونوس (بگ واقعی)**: `cart_max=0` با `min="1"` کل فرم HTML5-invalid می‌شد → **دکمه ذخیره بی‌صدا کار نمی‌کرد** (کلیک هیچ commit نمی‌فرستاد!) — min=0 شد (حتی min=1 روی cart_min هم بی‌معنا بود)
- E2E: ساخت محصول (خالی‌های enum/FK → NULL در DB) ✓ | ویرایش و ذخیره ✓

### ۲) پیش‌نمایش تصاویر دریافتی از دیجی‌کالا/ترب (J2)
- تصاویر دانلودشده حالا در تب «تصاویر» رندر می‌شوند: تصویر شاخص دریافتی (بج «دریافتی») + گالری دریافتی با دکمه حذف تکی
- `removeScraperImage(i)` / `removeScraperBase()` — فایل tmp بلافاصله پاک می‌شود؛ در save نهایی cleanup قبلی سر جایش است
- E2E با state شبیه‌سازی‌شده: بج‌ها + پیش‌نمایش + حذف ✓

### ۳) دریافت محصول از ترب — فعال و بازنویسی کامل (J3)
- متد خراب قدیمی (`getDataTorob` با echo/dd و DOM پنل قدیمی) پورت نشده بود؛ حالا `ProductScraper::fetchTorob()`:
  - استخراج prk از آدرس/کد → API رسمی ترب (۳ اندپوینت) → fallback: HTML صفحه + پارس JSON-LD (schema.org Product)
  - نگاشت به هم‌شکل خروجی دیجی‌کالا (title/price/galleryFiles/category/specs/...) → فرم بدون تغییر پر می‌شود
  - پیام خطای فارسی دقیق هنگام مسدود بودن ترب (arcaptcha)
- UI: کارت «دریافت محصول از دیجی‌کالا / ترب» با رادیو انتخاب منبع، hint و placeholder پویا
- E2E: خطای graceful ترب (آیپی سندباکس مسدود است) ✓ — روی هاست کاربر API/JSON-LD کار می‌کند

### ۴) منوی فعال در صفحه افزودن محصول (J4)
- **ریشه**: قاعده‌ی خانواده (*.index) صفحه‌ی create را هم به «لیست محصولات» وصل می‌کرد
- فیکس: sidebar حالا `$menuExactRoutes` (روت‌های دارای آیتم اختصاصی) را می‌سازد؛ قاعده‌ی خانواده فقط برای صفحات بی‌آیتم (edit/show/…) اعمال می‌شود
- E2E: create → فقط «محصول جدید» ✓ | edit → «لیست محصولات» ✓ | paid-orders → «سفارشات پرداخت شده» ✓ (بدون رگرسیون)

### ۵) بازطراحی فیلترهای لیست محصولات (J5)
- نوار مدرن: جستجو با آیکون + perPage + بازنشانی + دکمه «فیلترها» با بج شمارنده
- پنل پیشرفته: ۴ گروه **پیل سگمنتی** (انتشار/موجودی/ویژه/تایید) با state آنی + **انتخابگر درختی دسته‌بندی** (به‌جای select چندتایی زشت)
- چیپ‌های «فیلترهای فعال» با حذف تکی؛ CSS جدید `.ap-pill` و `.ap-filter-chip` (دارک مود هم)
- E2E: پیل منتشر شده → فیلتر شد ✓ | دسته سامسونگ → ۱ ردیف ✓ | بازنشانی → همه ✓

### ۶) CKEditor — نمایش کامل همه قابلیت‌ها (J6)
- **ریشه ادراک «نمایش نمی‌دهد»**: toolbar در CKEditor 5 به‌صورت پیش‌فرض سرریز را در دکمه ⋮ جمع می‌کند → کاربر فقط ~۱۲ آیتم می‌دید
- فیکس: `toolbar: { items: [...], shouldNotGroupWhenFull: true }` → **همه آیتم‌ها چندردیفه همیشه دیده می‌شوند** (مثل پنل قدیمی؛ ۵۳+ آیتم در ۲ ردیف)
- fallback خطای قابل مشاهده + یک retry خودکار (برای 404 chunk بعد از دیپلوی)
- E2E: products/posts/pages — toolbar کامل رندر می‌شود ✓

### ۷) نصب‌کننده — بازطراحی + رفع صفحه‌ی سیاه (J7)
- **ریشه**: پکیج rachidlaasri همه‌ی migrate+seed را در «یک» درخواست اجرا می‌کرد → روی هاست Timeout (صفحه سیاه) ولی MySQL ادامه می‌داد → رفرش = «جدول‌ها وجود دارند»
- **Container override** در AppServiceProvider: `DatabaseController` پکیج → کلاس خودمان که فقط صفحه‌ی پیشرفت را رندر می‌کند
- `MigrateController@step` (AJAX): هر درخواست فقط **یک** مایگریشن (`--path`) اجرا می‌کند → set_time_limit per-step؛ لیست pending هر بار از جدول migrations → **کاملاً resumable/idempotent**
- سید: یک‌بار با مارکر `storage/app/installer-seeded` + **تشخیص داده‌ی موجود** (roles/admins/options/...) → اجرای مجدد هیچ‌وقت duplicate نمی‌سازد
- **بازطراحی کامل RTL** با CSS داخلی (بدون FA/jQuery): پس‌زمینه گرادیانی، کارت مدرن، **نوار مراحل** (خوش‌آمد→نیازمندی‌ها→مجوزها→محیط→دیتابیس→پایان)، حلقه پیشرفت درصدی + لاگ زنده + حالت خطا با «تلاش مجدد»
- E2E واقعی: صفحه‌ی database خودش run شد و هم حالت خطا و هم حالت موفق (skip سید تکراری) دیده شد ✓

### ۸) صفحه پروفایل مدیر (J8)
- کامپوننت جدید `App\Livewire\Admin\Profile` + روت `np.profile` — نام/نام‌خانوادگی/username/email/bio + آپلود تصویر + ۸ شبکه اجتماعی (همان regexهای پنل قدیمی) + تغییر رمز (min:8)
- لینک تاپ‌بار «ویرایش پروفایل» حالا به پنل جدید می‌رود (قبلاً پنل قدیمی)؛ activity log هم ثبت می‌شود
- E2E: ذخیره (DB + لاگ) ✓

### ۹) پاک‌سازی کش در پنل جدید (J9)
- روت `np.cache-clear` (GET، فقط admin id=1): config/cache/route/view:clear → ریدایرکت با toast
- لینک تاپ‌بار از `admin.cache-clear` (پنل قدیمی) به `np.cache-clear`
- E2E: اجرا → داشبورد ✓

### ۱۰) گارد فرانت — «کاربر معمولی لاگین نمایش داده می‌شود» (J10)
- **ریشه واقعی**: میدل‌ور `ResolveDefaultGuard` روی «همه‌ی» درخواست‌ها گارد پیش‌فرض را بر اساس نشست عوض می‌کرد → با لاگین ادمین، کل صفحات فروشگاه گارد adminPanel می‌دیدند (هدر «پروفایل من» نشان می‌داد!)
- فیکس: تعیین گارد فقط برای `livewire/*`، `admin-old/*`، `admin/{prefix}*`، `sellers*` — صفحات فروشگاه همیشه web
- علاوه بر آن، کل ویوهای تم فرانت (۱۳ فایل) `auth()->…` → `auth('web')->…` (به‌جز پنل فروشندگان)
- **پاسخ سوال کاربر**: بله، گارد پنل مدیر `adminPanel` است و پیش‌فرض `web` — مشکل از middleware بود
- E2E: با نشست ادمین، فرانت «ورود | ثبت نام» نشان می‌دهد ✓

### ۱۱) خطای FK جدول searches (J11)
- همان ریشه‌ی J10: `user_id=1` (ادمین) در searches می‌نششت و FK به users می‌ترکید (1452)
- `LogsSearch::safeSearchUserId()`: گارد صریح web + `instanceof User` + exists
- E2E: همان جستجوی «کالاهای سوپرمارکتی» که قبلاً 1452 می‌داد → رکورد با `user_id=null` ثبت شد ✓

### یادداشت‌های فنی فاز ۲۱
- **فرم HTML5-invalid = دکمه submit بی‌صدا کار نمی‌کند** (هیچ commit هم نمی‌رود) — `:invalid` را در دیباگ فرم‌ها چک کنید
- `auth()->user()` در Livewire 4 با middleware عوض‌کردن گارد پیش‌فرض، به‌شدت کانتکست‌محور است — در کد فرانت همیشه `auth('web')` صریح بنویسید
- Container binding برای override کنترلرِ روتِ رشته‌ای پکیج‌ها (`['uses' => 'Class@method']`) کار می‌کند
- `$wire.set` آنی است ولی `btn.click()` بعد از آن در تست اتوماسیون ممکن است state یک قدم عقب بفرستد — برای تست قطعی از `$wire.action()` مستقیم استفاده کنید

## فاز ۲۲ — مانیتور سیستم: دانلود گزارش‌ها + ریشه‌کنی کوئری‌های کند

### ۱) دانلود «درخواست‌های کند» و «کوئری‌های کند» (درخواست کاربر)
- دو دکمه دانلود (آیکون download + اسپینر wire:loading) در سربرگ هر دو کارت صفحه مانیتور سیستم
- اکشن‌های `downloadSlowRequests()` / `downloadSlowQueries()` در PulseIndex — `response()->streamDownload()` (Livewire 4 SupportFileDownloads از StreamedResponse پشتیبانی می‌کند)
- گزارش متنی ساخت‌یافته تا **۱۰۰ مورد تازه (بدون کش)**: رتبه/مدت (ms)/زمان شمسی+میلادی/منبع (file:line)/SQL کامل یا Method+URI+via — مناسب ارسال مستقیم به پشتیبانی/ایجنت
- نام فایل: `slow-queries-YYYYMMDD-His.txt` / `slow-requests-...`
- E2E: هر دو دانلود در مرورگر با هک `URL.createObjectURL` گرفته و محتوایشان بررسی شد ✓

### ۲) باگ نمایش: کلیدهای Pulse از نوع JSON بودند!
- SlowRequests recorder کلید را `json_encode([method, uri, "via ..."|"Controller@class"])` ذخیره می‌کند — کد قبلی با `explode(' ')` پارسش می‌کرد → برای ردیف‌های livewire متد/URI آشغال نمایش داده می‌شد (دقیقاً همان خروجی HTML که کاربر فرستاد)
- SlowQueries هم `json_encode([sql, source])` — حالا source (فایل:خط) جدا شده و زیر SQL نمایش داده می‌شود
- برای درخواست‌های livewire، زیر URI لیبل «Livewire update» + title با منبع

### ۳) ریشه‌کنی کوئری‌های کند (هدف: صفر کوئری اضافه در هر درخواست)
- **پرمیشن‌ها** (`AuthServiceProvider:44` — تا ۵,۲۸۹ms، در «هر درخواست»!): کش ۱۵ دقیقه‌ای + باطل‌سازی فوری با رویدادهای saved/deleted مدل Permission و Role — کلید `auth.permissions.active` (۲۴۶ ردیف + roles)
- **`Schema::hasTable('redirects')`** (information_schema — تا ۳,۴۷۲ms در «هر درخواست» سراسری): `Cache::rememberForever` + باطل‌سازی با رویدادهای مدل Redirect و رویداد `MigrationsEnded` (نصب/مایگریشن) — به‌علاوه نقشه ریدایرکت‌ها کش ۱۰ دقیقه‌ای (قبلاً هم per-request کوئری می‌شد)
- **RecordAdminSession** (updateOrCreate = ۲-۳ کوئری در هر درخواست ادمین/هر آپدیت Livewire — تا ۱,۷۲۷ms): تrottle ۶۰ ثانیه‌ای با مارکر session (صفر کوئری در پنجره) + الگوی UPDATE-اول (مسیر گرم ۱ کوئری، مسیر سرد create)
- **ایندکس ترکیبی `(type, timestamp)` روی pulse_entries** (مایگریشن ۲۰۲۶_۰۷_۰۵_۱۰۱۰۱۰) — کوئری‌های مانیتور قبلاً بعد از فیلتر filesort می‌شدند
- **کش داشبورد مانیتور ۱۵ → ۳۰ ثانیه** — wire:poll هر ۱۰ ثانیه باقی است ولی ~۲/۳ pollها دیگر DB را نمی‌زنند
- SessionDriver نکته: کوئری‌های «update sessions» طبیعت درایور دیتابیسی سشن روی هاست کند است — تغییر driver تصمیم deploy است (در پیام توضیح داده شد)
- E2E: کش‌ها پس از مرور واقعی پر شدند (permissions=246)؛ last_activity در پیمایش صفحات بعدی ثابت ماند (throttle فعال)؛ dashboard/products/orders/users سالم

### ۴) باگ جانبی پیدا‌شده: کرش ۵۰۰ صفحات خطا برای ادمین (تم فرانت)
- `popup-message.blade.php` با `Auth::check()` (گارد پیش‌فرض) شاخه می‌گرفت ولی داخلش `auth('web')->user()` صدا می‌زد → روی مسیرهای پنل (ResolveDefaultGuard) ادمین وارد شاخه می‌شد و null → ۵۰۰
- صفحات خطا (۴۰۴/۵۰۰) که از master فرانت رندر می‌شوند برای ادمین «خطای سرور» می‌دادند — فیکس: `auth('web')->check()`
- E2E: URL اشتباه قبلی (`system/backups`) حالا تمیز ۴۰۴ می‌دهد؛ فرانت با نشست ادمین سالم است ✓

### یادداشت‌های فنی فاز ۲۲
- کلیدهای Pulse همیشه JSON-encoded آرایه هستند — هر نمایش/گزارشی باید `json_decode` با fallback داشته باشد
- `Schema::hasTable()` در میدلور سراسری = یک information_schema کوئری در هر درخواست — همیشه کش کنید
- کش لاراول فایل‌محور روی هاست اشتراکی از cache:database بسیار سریع‌تر است؛ برای شمارنده‌ها هم Cache::increment امن است
- rebuild ادمین: خروجی بیلد deterministic بود — بایت‌به‌بایت با فاز ۲۱ یکسان (کلاس‌های استفاده‌شده قبلاً در اسکن بودند) → assets بدون تغییر ماندند

## فاز ۲۴ — ترب: دریافت کامل اطلاعات (تصاویر گالری + تنوع قیمت بر اساس اینچ + ویژگی‌ها)

### مشکل کاربر
- «اطلاعات به صورت کامل از ترب دریافت نمی‌شود» — مثال: تلویزیون شیایوومی S سایز 55 اینچ که هم تصویر دارد، هم «تنوع قیمت بر اساس اینچ»، هم ویژگی‌ها

### سه ریشه‌ی اصلی که پیدا و رفع شد
1. **`extractTorobKey` prkهای UUID را ناقص می‌گرفت** — الگوی `[A-Za-z0-9]+` در اولین خط تیره متوقف می‌شد؛ لینک کاربر (`torob.com/p/bc71937a-185e-46d8-9327-e4e91643d718/...`) به prk اشتباهِ `bc71937a` تبدیل می‌شد → حتی روی هاست هم هیچ چیز (یا چیز اشتباهی) گرفته می‌شد. حالا `[A-Za-z0-9-]+` (تا ۶۰ کاراکتر، trim خط تیره‌های ابتدا/انتها)
2. **اندپوینت جدید ترب `/v4/base-product/details/` (جمع!) پارس نمی‌شد** — پیلود جدید (verified با MCP متن‌باز live-tested ۲۰۲۶-۰۹/۱۰) فیلدهای جدید دارد: `name1/name2`، `image_url`+`image_count`، `min_price/max_price`، `key_specs`، `structural_specs`، `variants[]`، `breadcrumbs[]`، `products_info.result[]` (پیشنهاد فروشنده‌ها)
3. **تنوع قیمت و ویژگی‌ها اصلا خوانده نمی‌شدند** — دو بخش کاملاً جدید در `mapTorobProduct`

### چیزهای جدید (ProductScraper)
- **نردبان API**: `details` جمع (با/بدون source=next_desktop) → `detail` قدیمی → v2 — همه با اعتبارسنجی `isTorobProductPayload`
- **لایه‌ی جستجو** (`torobSearchFallback`): اسلاگ URL → `q` فارسی → `/v4/base-product/search/` → ردیفِ همین prk → `more_info_url` آماده (prk+search_id) → details
- **گالری بازگشتی** (`collectTorobImagesRecursive`): کل پیلود برای کلیدهای `image_url/image1/image2/images/gallery/photos/image/main_image/thumb_url/desktop_image_url/image_path` پیمایش می‌شود (عمق ۱۴) — protocol-relative و /root هم هندل می‌شود؛ base image همیشه اول
- **ویژگی‌ها** (`torobSpecGroups`): `key_specs` [{header,items:[{key,value}]}] + `structural_specs` {headers:[{header,specs:{}]} → specGroups؛ مقدار آرایه‌ای با «،» می‌پیوندد؛ نشانه‌ی بخش `"title"` حذف می‌شود؛ شکل‌های قدیمی extra_info/specifications/specs هم پشتیبانی می‌شوند
- **تنوع قیمت** (`torobVariantRows`): `variants[]` (تب‌های تنوع) → پرمخاطب‌ترین تب (بیشترین آیتم قیمت‌دار، ≥۲ آیتم، تا ۳۰ ردیف) → گروه ویژگی به نام تب (type=checkbox) + ویژگی‌ها؛ **مقدار هر آیتم = بخش متمایز نام** (حذف پیشوند/پسوند مشترک همه‌ی آیتم‌ها با `torobSharedEdges`)؛ «تلویزیون ... سایز 43 اینچ ...» → `43`؛ قیمت از عدد یا `price_text` فارسی (`torobToman` — ارقام فارسی/عربی + جداکننده‌های ٬ ٫ ،)؛ مقدار تکراری → ارزان‌ترین قیمت
- **دسته‌بندی**: اول `breadcrumbs` (آخرین لینک ≠ ریشه 0)، بعد category_name/torob_category/category، بعد JSON-LD
- **برند**: brand_name/brand + fallback برند JSON-LD

### فرم محصول
- spec_type اکنون آگاه از منبع است: «دریافتی از ترب» / «دریافتی از دیجی‌کالا»
- راهنمای ورودی ترب به‌روز شد: «تنوع قیمت (مثلا بر اساس اینچ/سایز)، ویژگی‌ها...»
- ردیف‌های تنوع ترب از همان مسیر variantRows دیجی‌کالا وارد ماتریس قیمت می‌شوند (گروه‌های checkbox = چیپ‌های تک‌انتخابی فاز ۲۳)

### تست
- `test-scripts/torob_fixture_test.php` بازنویسی شد — **۴۱ تست، همه سبز**: پیلود واقعی تلویزیون (۵ سایز با قیمت، یک آیتم قیمت ۰ نامعتبر، تب دوم «اصالت کالا»)، پارس ارقام فارسی، نشانه‌ی title، ادغام مقدار تکراری، regression پیلود قدیمی، UUID-prk لینک کاربر، پاکسازی کامل
- اجرای دوم هم سبز = مسیر idempotent (ویژگی/گروه موجود دوباره ساخته نمی‌شود)
- از سندباکس ترب 490 (arcaptcha) می‌دهد — نردبان کامل ۷ مرحله‌ای تست شد: خطای شفاف با وضعیت هر لایه `[api: HTTP 490 | search: ... | page: ...]`؛ روی هاست ایران APIها پاسخ می‌دهند
- فیکس وسط راه: `preg_match` با delimiter `#` و `#` داخل کلاس کاراکتر = «Unknown modifier ']'» — delimiter به `~` تغییر کرد؛ `trim()` بایتی روی متن فارسی → regex یونیکد

### یادداشت‌های فنی فاز ۲۴
- delimiterهای PCRE: کاراکتر delimiter حتی داخل کلاس کاراکتر هم باید escape شود — برای الگوهای حاوی `#` از `~` استفاده کنید
- prk ترب دو شکل دارد: کد کوتاه (`RYAxZAw`) و UUID (`bc71937a-...`) — همیشه `-` را در کلاس کاراکتر بگنجانید
- قیمت‌های ترب می‌توانند در `price_text` با ارقام فارسی باشند — هرگز فقط کلیدهای عددی را نخوانید
- `variants[].items` آیتم‌های قیمت ۰ (ناموجود) هم دارد — فیلتر شوند وگرنه «مقدار» آشغال می‌شود

## فاز ۲۵ — ثبت‌نام مدیر پس از نصب + اسلاگ فارسی + ستون‌های ۵۰۰ + رفرش زنده گزینه‌ها + پیام خطای ۱۴۰۶

### درخواست‌های کاربر
1. پس از نصب (جداول + سیدر) مدیر باید به «صفحه ثبت‌نام مدیر» برود — این صفحه در پنل جدید ساخته نشده بود؛ دکمه‌ی صفحه‌ی «نصب کامل شد» هم باید به همین صفحه اشاره کند
2. بعد از ثبت‌نام، کارهای پنل قدیمی (مثل شورت‌کات/symlink استایل فرانت در public) هم انجام شود — کامل از پنل قدیم بررسی و منتقل شود
3. اسلاگ خودکار به انگلیسی تبدیل می‌شود — باید از `sluggable_helper_function` (هلپر خود کاربر) استفاده شود، همه‌جا
4. در دریافت از دیجی‌کالا، رنگ/دسته/برندِ تازه‌ساخته بدون رفرش نمایش داده نمی‌شود
5. خطای `Data too long for column 'slug'` (۱۴۰۶) باید پیام فارسی بدهد؛ عنوان و اسلاگ حداقل ۲۵۵ کاراکتر

### ثبت‌نام مدیر (صفحه جدید پنل)
- **کامپوننت Livewire جدید** `App\Livewire\Admin\Auth\Register` با لایوت `admin-panel::layouts.login` + ویوی مدرن هم‌سبک با ورود پنل (دو ستونه، پنل گرادیانی، تم روشن/تاریک، سنجش قدرت گذرواژه، پیش‌نمایش زنده آدرس پنل، چشمِ نمایش گذرواژه، هشدار «فقط یک‌بار») — مسیر `/setup` با نام `register` (+ ریدایرکت `/register`) و میدل‌ور `CheckUserNotExists`
- **SetupFinalizer** (سرویس مشترک): ثبت `ADMIN_ROUTE_PREFIX` → loadTheme → **shop:link (شورت‌کات استایل فرانت در public)** → theme:config → SESSION/QUEUE=database → option app_debug → نشان installed → پینگ مرکزی → optimize — هم پنل جدید هم InstallController قدیمی از همین سرویس استفاده می‌کنند
- **صفحه‌ی «نصب کامل شد»**: اگر مدری وجود ندارد دکمه‌ی اصلی «ساخت حساب مدیر (ثبت‌نام)» به `/setup` می‌رود؛ اگر باشد → دکمه‌ی ورود + هشدار رمز پیش‌فرض. ساخت «مدیر پیش‌فرض» در MigrateController حذف شد (ثبت‌نام جایگزین آن است)
- **سوییچ درایور سشن/صف** به مرحله‌ی دیتابیسِ نصب‌کننده منتقل شد (بعد از seed) — وگرنه سشنِ ثبت‌نام در درایور قدیم می‌نشت و ورود خودکار گم می‌شد

### سه باگ زنجیره‌ای که در تست E2E کشف و رفع شد (همگی ۵۰۰/ازکارافتادگی واقعی روی نصب تازه می‌دادند)
1. **`change_env` کامنت‌های .env را نابود می‌کرد** — `preg_split('/\s+')` خط «# Security flags (Phase 15)» را تکه‌تکه می‌کرد → dotenv با «Encountered an invalid name at [(Phase]» کل اپ را ۵۰۰ می‌کرد. بازنویسی خط‌به‌خط (کامنت‌ها حفظ، فقط KEY=VALUE بازنویسی/افزوده می‌شود)
2. **closure در `config/sluggable.php` کش کانفیگ را می‌شکست** — «sluggable.method is non-serializable» → هر config:cache/optimize می‌مُرد. حالا `'method' => 'sluggable_helper_function'` (رشته، سریالایزپذیر) + `maxLength => 480`
3. **config:cache وسط درخواست = جابه‌جایی Facadeها** — `getFreshConfiguration` یک اپ دوم بوت می‌کند و session/auth/livewire-store به اپ جدید می‌روند: (i) لاگینِ بعد از finalize در سشن بی‌ربط می‌نشت (ورود خودکارِ بی‌صدا گم می‌شد — پنل قدیم هم همین باگ را داشت)، (ii) افکت ریدایرکتِ Livewire در dehydrate خوانده نمی‌شد (مرورگر ریدایرکت نمی‌شد). رفع: لاگین قبل از finalize؛ پارامتر `$cacheConfig=false` در change_env؛ optimize+پینگ با `register_shutdown_function` بعد از ارسال پاسخ؛ حافظه/زمان best-effort بالا (OOM ۱۲۸M روی view:cache)
- ضد ارسال تکراری: ثبت‌نامِ موازی/دوباره دیگر روی unique مدیر کرش نمی‌کند (لاگین مدیر اول + ریدایرکت)

### اسلاگ فارسی (همه‌جا)
- `SlugService::createSlug`/پکیج sluggable حالا از هلپر کاربر استفاده می‌کند: همان عنوانی که قبلاً «k-mt-o-khr-d-l-t-…» (۲۳۵ کاراکتر بی‌معنی) می‌شد → «لپ-تاپ-15-6-اینچی-اچ-پی-…» (۱۷۷ کاراکتر معنادار)
- جایگزینی `Str::slug` (حذف‌کننده‌ی فارسی) با `sluggable_helper_function` در: BrandForm، BrandIndex، TagIndex (ایجاد/ویرایش)، TagController قدیمی، SellerController تم (اسلاگ فروشنده)

### ستون‌ها و پیام خطا
- مایگریشن `2026_10_20_120001_enlarge_title_slug_columns`: همه‌ی ستون‌های varchar(191/255) از نوع title/slug/meta_title در ~۳۰ جدول → **۵۰۰** با ALTER…MODIFY مبتنی بر information_schema (charset/nullability/default حفظ؛ ایندکس‌های unique اسلاگ‌ها دست‌نخورده — بعد از اجرا راستی‌آزمایی شد)
- ProductForm: title تا ۵۰۰ + اسلاگ تا ۵۰۰؛ اسلاگ خالی = ساخت سروریِ یکتای فارسی (نه ذخیره‌ی خام عنوان)؛ `handleSqlError()`: خطای ۱۴۰۶/22001 → پیام فارسی «عنوان/اسلاگ بیش از حد طولانی است» + خطای فیلد + توست؛ ۱۰۶۲ → «اسلاگ تکراری است»

### رفرش زنده گزینه‌ها بعد از دریافت
- `fetchFromSite()` کش‌های `np.pform.categories` و `np.pform.attr_groups` را باطل و computedها را unset می‌کند — رنگ/دسته/برندِ همین حالا ساخته‌شده بلافاصله در سلکت‌ها دیده می‌شود (قبلاً کش ۶۰ ثانیه‌ای بود و کاربر مجبور به رفرش بود)

### تست
- E2E مرورگر روی دیتابیس تازه (migrate+seed، صفر مدیر): /setup رندر → ثبت‌نام → مدیر creator + نقش ۱ ساخته شد، prefix در .env، شورت‌کات‌های public/storage ساخته شد، نشان installed ثبت شد، سشن در DB با داده‌ی لاگین ذخیره شد، مرورگر خودکار به داشبورد `/admin/{prefix}` رفت («سلام مدیر تست»)، و /setup بسته (404) شد
- رگرسیون ترب: ۴۱/۴۱ سبز؛ php -l همه‌ی فایل‌ها تمیز

---

## فاز ۲۶ — بازخورد کاربر (راند ۱۵): پدینگ ورودی‌های /setup، کارهای پس از ثبت‌نام (شورت‌کات themes)، فیلتر دسته‌بندی لیست محصولات، دراپ‌داون اندازه فونت CKEditor، پکیج‌ها (مودال جزئیات + «در حال نصب» + نوار پیشرفت)

### درخواست‌های کاربر
1. صفحه ثبت‌نام مدیر `/setup`: آیکون‌ها روی متن تایپ‌شده می‌افتند — پدینگ بده
2. کارهای پس از ثبت‌نام پنل قدیم در پنل جدید انجام نمی‌شود؛ InstallController و `configApplication()` را ببین؛ شورت‌کات themes در public ساخته نشده
3. لیست محصولات، فیلترینگ: کلیک روی دسته‌بندی‌ها هیچ منویی باز نمی‌کند
4. CKEditor: دراپ‌داون «اندازه فونت» از صفحه می‌زند بیرون — طول ثابت + اسکرول؛ ابزار language حذف شود
5. پکیج‌ها: تک‌صفحه لازم نیست؛ مودال جزئیات دقیقاً مثل پنل قدیم + توضیحات؛ بعد از درخواست نصب باید «در حال نصب» بماند تا نتیجه مشخص شود؛ مودال نوار پیشرفت نصب نمایش داده نمی‌شود

### ۱) پدینگ ورودی‌های /setup (رفع هم‌پوشانی آیکون‌ها)
- ریشه: (a) ورودی `username` دارای `dir="ltr"` است؛ `pe-10` (منطقی) روی جهتِ خودِ اینپوت حل می‌شود (راست) در حالی که آیکون با `end-3.5` در RTL سمت چپ است → متن زیر آیکون می‌رفت → به `pl-10` فیزیکی تغییر کرد
- (b) ورودی‌های گذرواژه: دکمه‌ی چشم `start-2.5` دقیقاً محل شروع تایپ RTL بود بدون هیچ پدینگ شروع → `ps-11 pe-10` (فضای چشم + آیکون کلید). کلاس‌های جدید (`ps-11` و…) بعد از **ری‌بیلد** دارایی‌های پنل (build:admin) در CSS نهایی موجودند — این فایل بعد از آخرین بیلدِ فاز ۲۵ ساخته شده بود و کلاس‌هایش در CSS نبود
- E2E: تایپ واقعی در نام کاربری/گذرواژه — متن هرگز زیر آیکون نمی‌رود (اسکرین‌شات)

### ۲) تضمین کارهای پس از ثبت‌نام (شورت‌کات استایل فرانت در public)
- **باگ اصلی ShopLink**: اگر symlink روی هاست غیرفعال بود (تابع disable شده «false» برمی‌گرداند نه exception!) یا پوشه‌ی `public/themes` اصلا وجود نداشت، `File::makeDirectory($link)` استثنا می‌داد و کل حلقه بی‌صدا می‌مرد → شورت‌کات تم هرگز ساخته نمی‌شد
- بازنویسی `ShopLink` برای هر لینک به‌صورت مستقل: تضمین پوشه‌ی والد (`ensureDirectoryExists`) → `@symlink` → فال‌بک `ln -s/mklink` → **فال‌بک نهایی کپی واقعی محتوا** + تشخیص symlink شکسته (`is_link && !file_exists`) و بازسازی؛ خطای هر لینک بقیه را نمی‌کشد و report می‌شود
- `SetupFinalizer`: `loadTheme` هم try/catch شد + متد جدید **`ensureThemeLinks()`** — بعد از shop:link «تأیید» می‌کند همه‌ی لینک‌های `front.links` + `filesystems.links` واقعاً در public موجودند؛ نبودند → والد + مسیر ساخته و محتوا کپی می‌شود (گارانتیِ همان کاری که `configApplication()` پنل قدیم می‌کرد)
- کل زنجیره‌ی SetupFinalizer با `configApplication()` قدیمی تطبیق داده شد (prefix، loadTheme، shop:link، theme:config، درایورها، app_debug، نشان نصب، optimize، پینگ) — کامل است

### ۳) فیلتر دسته‌بندی لیست محصولات (منو باز نمی‌شد)
- ریشه (با inspect مرورگر): کارت فیلترها `ap-card overflow-hidden` بود؛ پنل بازشوی انتخابگر دسته‌بندی (absolute، بیرون کارت) **کلیپ** می‌شد — elementFromPoint داخل مستطیل پنل، TD جدول را برمی‌گرداند!
- `overflow-hidden` از کارت فیلتر لیست محصولات + کارت تب‌های فرم محصول + کارت تب‌های فرم وبلاگ حذف شد (همگی همین باگ نهان را برای انتخابگر دسته داشتند)
- E2E: منو باز می‌شود، ۲۸ ریشه/گره دیده می‌شود، انتخاب چیپ می‌سازد و به Livewire سینک می‌شود

### ۴) CKEditor
- `TextPartLanguage` (ابزار زبان) از plugins و toolbar حذف شد (به درخواست کاربر) + آیتم toolbar آن حذف
- دراپ‌داون‌های نوار ابزار: `max-height: 280px + overflow-y: auto + overscroll-behavior: contain` (فهرست ۱۸ گزینه‌ای اندازه فونت دیگر از صفحه نمی‌زند بیرون) + محدودکردن عرض لیبل‌ها؛ ری‌بیلد دارایی‌ها

### ۵) پکیج‌ها — تجربه‌ی دقیقاً پنل قدیمی
- **تک‌صفحه `packages/{slug}` حذف شد** (روت + کامپوننت PackageShow + ویو) — «مشاهده جزئیات» و «آپدیت» کارت‌ها حالا **مودال جزئیات** را باز می‌کنند (پورت کامل مودال قدیمی modal.js): هیرو (تصویر/نام/نسخه/نشان‌ها/متا)، گالری، توضیحات کامل، تغییرات نسخه آخر (added/changed/fixed)، تاریخچه نسخه‌ها، پیش‌نیازها، وضعیت نصب (updating/failed/installed + انقضای لایسنس + فعال/غیرفعال)، لاگ‌های اخیر، پلن‌های قیمت‌گذاری انتخابی (پیش‌فرض ارزان‌ترین)، فوتر قیمت + نصب/آپدیت/فعال‌سازی/حذف (تأیید دومرحله‌ای داخل مودال)
- نصب از مودال همان مسیرهای لایسنس/اشتراک/پلن (`checkPurchaseState`) را طی می‌کند
- **«در صف نصب» حذف شد** — به درخواست کاربر: از لحظه‌ی درخواست تا نتیجه (نصب/خطا)، وضعیت «**در حال نصب…**» است؛ `InstalledModule::markPending` حالا در مسیر نصب رایگان و نصب مجدد با لایسنس هم **قبل از dispatch** صدا زده می‌شود (قبلاً فقط مسیر اشتراک)
- **مودال نوار پیشرفت نصب** (پورت progress.js): کارت شناور پایین صفحه با ۶ مرحله (دانلود/تأیید/استخراج/دیتابیس/انتشار/نهایی‌سازی)، نوار درصد، پیام مرحله، `wire:poll.2s="pollProgress"` — پایان موفق: toast سبز + بستن خودکار + رفرش لیست؛ خطا: toast با آخرین پیام لاگ. E2E کامل: باز شدن → polling → اتمام → toast «نصب … با موفقیت کامل شد» + کارت «نصب‌شده/به‌روز»
- toastهای نصب به «نصب پکیج آغاز شد…» تغییر کرد؛ آپدیت ماژول هم از لحظه‌ی درخواست «در حال نصب» می‌شود (`markUpdatingBeforeDispatch`)

### ۶) دو باگ زیرساختی که در همین فاز در تست نصب تازه کشف و رفع شد (هر دو پنل را کاملاً از کار می‌انداختند)
1. **`config/session.php`: `'secure' => true` هاردکد** — روی هر هاستی که با HTTP سرو می‌کند مرورگر کوکی سشن را **هرگز ارسال نمی‌کرد** → ورود مدیر، ورود خودکارِ پس از ثبت‌نام و همه‌ی stateهای Livewire مرده بود (هر درخواست سشن خالی جدید). حالا `'secure' => env('SESSION_SECURE_COOKIE', false)` + گزینه در `.env.example` (روی HTTPS true بگذارید)
2. **FinalController پکیج نصب‌کننده در «هر» بازدید از `/install/final` دستور `key:generate --force` اجرا می‌کرد** — APP_KEY بعد از نصب دائماً عوض می‌شد: کوکی‌های رمزشده نامعتبر، و چون هش endpointهای Livewire از APP_KEY ساخته می‌شود و `optimize` (shutdown پس از ثبت‌نام) آن هش را در route cache فریز می‌کرد، livewire.min.js و آپدیت‌های Livewire **۴۰۴** می‌شدند (بوت شدن Livewire از کار می‌افتاد — فرم ثبت‌نام مرده). کنترلر جدید `App\Http\Controllers\Install\FinalController` (بدون ساخت کلید) با `bind()` جایگزین پکیج شد
- تست کامل نصب تازه (کلون شبیه‌سازی‌شده + دیتابیس خالی): wizard → ۱۹۶ مایگریشن مرحله‌به‌مرحله → final (کلید دست‌نخورده ✓) → CTA «ساخت حساب مدیر» → /setup → ثبت‌نام → ساخت مدیر (creator + نقش ۱) → شورت‌کات themes/storage در public حتی با نبودِ پوشه‌ی والد ✓ → درایورها/پیشوند/نشان نصب ✓ → **ورود خودکار به داشبورد** ✓

### درس‌های کلیدی فاز ۲۶
1. کلاس‌های Tailwind ویوهای تازه فقط بعد از **build:admin** به CSS می‌روند — بعد از هر ویوی جدید حتماً ری‌بیلد (فایل‌های فرانت‌اند و بک‌اند همیشه هم‌قدم شوند)
2. `pe-*`/`ps-*` منطقی نسبت به جهتِ خودِ المان حل می‌شود؛ ورودی‌های `dir=ltr` داخل RTL باید پدینگ فیزیکی بگیرند
3. والدِ `overflow-hidden` پنل‌های dropdown مطلق را کلیپ می‌کند — گیرها فقط با `elementFromPoint` قابل کشف‌اند (rect درست است ولی paint زیر کارت است)
4. هش endpointهای Livewire 4 از APP_KEY مشتق می‌شود؛ هر چرخش کلید + route cache = ۴۰۴ همه‌ی Livewire. کلید پس از نصب هرگز نباید عوض شود (key:generate نصب‌کننده حذف شد)
5. کوکی سشن `secure=true` روی HTTP یعنی «بدون سشن» — همیشه از env خوانده شود
6. symlink روی هاست اشتراکی ممکن است disable باشد (false برمی‌گرداند نه exception) — فال‌بک کپی همیشه لازم است

---

## فاز ۲۷ — بازخورد کاربر (راند ۱۶): خطای نصب در پایان جداول + سیدرهای واردنشده، منو برای بی‌پرمیشن‌ها، خطای 1364 نصب پکیج (name)، تطابق کامل مودال پکیج با پنل قدیمی

### ۱) نصاب — «آخرِ ساخت جداول خطا می‌داد؛ تلاش مجدد سبز می‌شد ولی سیدرها وارد نشده بودند»
- **ریشه‌ی واحد هر دو علامت:** زنجیره‌ی «db:seed همه‌ی سیدرها در یک درخواست» + `change_env('SESSION_DRIVER','database')` با پیش‌فرضِ `$cacheConfig=true` که **وسط همان درخواست `config:cache` اجرا می‌کرد** (بوت Application دوم — درس فاز ۲۵). روی سیستم/هاست کند، درخواستِ آخر کشته می‌شد: در کد قدیمی وسط سید (بعد از RoleSeeder که `roles` را پر می‌کند) → پیام خطا؛ «تلاش مجدد» `databaseHasSeedData()` را true می‌دید و **سید را کلاً رد می‌کرد** → سبز شدن بدون سیدر!
- **رفع ۱ — سیدرها هم مرحله‌به‌مرحله مثل مایگریشن‌ها:** هر درخواست فقط «یک» سیدر (`db:seed --class`) اجرا می‌کند (۱۴ سیدر = ۱۴ درخواست کوتاه)؛ پیشرفت در `storage/app/installer-seeded.json` ثبت و با قطعی/رفرش دقیقاً از همان سیدر ادامه می‌یابد (تست شد: قطع مصنوعی بعد از سیدر ۵ → ادامه از ۶/۱۴ تا ۱۴/۱۴)
- **رفع ۲ — `change_env(..., false)`** در مرحله‌ی پایانی نصاب: هرگز config:cache داخل درخواست؛ درایور در درخواست بعدی AJAX اعمال می‌شود، کش نهایی هم `SetupFinalizer` بعد از ثبت‌نام با `register_shutdown_function` می‌سازد
- **رفع ۳ — تحمل درج تکراری (۱۰۶۲):** اگر سیدری از تلاش قبلی نیمه‌کاره مانده باشد، خطای duplicate به‌جای توقف، «done» ثبت می‌شود
- `DatabaseSeeder` حالا لیست مرجع `seederClasses()` دارد (تک منبع حقیقت برای نصاب و run)
- UI: در فاز سید، نام سیدرِ جاری + «X از ۱۴ سید اجرا شد» نمایش داده می‌شود و حلقه‌ی پیشرفت ۸۵٪→۹۹٪ می‌رود
- **E2E کامل روی کلون تازه (بدون .env، دیتابیس خالی):** wizard → ۱۹۶ مایگریشن → ۱۴ سیدر یکی‌یکی (۱۰۴ ثانیه) → **پنل سبز «دیتابیس با موفقیت ساخته شد» بدون هیچ خطا** → final → CTA «ساخت حساب مدیر» → /setup → ثبت‌نام → ورود خودکار به داشبورد + `admin_role` پر + ADMIN_ROUTE_PREFIX/SESSION_DRIVER/QUEUE_CONNECTION در .env + نشان نصب + ردیاب ۱۴/۱۴ finished ✓ (قبلاً در همان سناریو، درخواست آخرِ دی‌بی همیشه «خطا» می‌داد و هیچ سیدری ثبت نمی‌شد)

### ۲) منوها برای کسانی که پرمیژن ندارند نمایش داده می‌شد
- **۳۴ آیتم منو بدون کلید permission/creator_only بودند**؛ بدتر: آیتم‌های «پدر» بدون permission همیشه true برمی‌گرداندند → گروه‌هایی که «هیچ» آیتم مجازی نداشتند هم با عنوانِ خالی رندر می‌شدند
- config/admin-menu.php: «لیست حمل و نقل‌ها» → `carriers`، چهار آیتم تنظیمات فروشندگان (چرا ما/کمیسیون/سوالات/قرارداد) → `sellers`، چهار فرزند «برنامه‌نویس» → `creator_only`
- sidebar.blade.php: فیلتر **بازگشتی** جدید `$checkMenuItem` — پدر فقط با حداقل یک فرزندِ مجاز دیده می‌شود؛ فرزندان همان فیلتر واحد را استفاده می‌کنند؛ منوی ماژول‌ها هم مثل قدیمی داخل `@can('packages')` رفت
- گیت صفحه: چهار کامپوننت تنظیمات فروشندگان (SellerHero/Commission/Question/Econtract) که **هیچ گاردی نداشتند** → `can('sellers') ?: abort(403)` (بقیه‌ی صفحات np گارد دارند — audit کل پوشه انجام شد؛ ۶ مورد باقی‌مانده عمداً عمومی/شخصی‌اند: Login/Register/HeaderNotifications/PaymentCallback/Profile/Holidays مثل پنل قدیمی)
- **E2E:** ادمین با نقشِ فقط «products.index + products.category + carriers» → سایدبار دقیقاً «داشبورد + لیست محصولات + دسته‌بندی + حمل‌ونقل» (بدون هیچ گروه شبح‌وار)؛ creator → ۱۰۶ آیتم کامل ✓

### ۳) خطای ۱۳۶۴ نصب پکیج (Field 'name' doesn't have a default value)
- `InstalledModule::markPending('rack-widget', null, ...)` برای رکورد جدید ستون NOT NULL `name` را ست نمی‌کرد → در strict mode درج با 1364 می‌مرد و **نصب اصلا شروع نمی‌شد** (همان خطای ارسالی کاربر)
- رفع: نام از `PackageCache` پاس می‌شود (dispatchInstall) + fallback داخلی `deriveNameFromSlug()` (rack-widget → RackWidget، هماهنگ با نام پوشه Modules/ که بعد از استخراج ZIP توسط registerInstall جایگزین می‌شود) — سه حالت تست شد (null/خالی/واقعی) ✓

### ۴) مودال پکیج‌ها — تطابق ۱:۱ با پنل قدیمی (اسکرین‌شات ارسالی)
- **پاک‌ساز مخصوص پکیج `sanitize_package_html()`**: توضیحاتِ API پکیج‌ها «صفحه‌ی معرفی» کامل با `<style>` خودش است؛ `sanitize_rich_text` تگ style را با محتوا حذف می‌کرد و محتوا لخت می‌شد (پنل قدیمی خام تزریق می‌کرد). نسخه‌ی جدید: `<style>` حفظ، script/on*/javascript:/iframe ناامن مثل قبل حذف
- **سقف ارتفاع `max-h-64` توضیحات کامل حذف شد** (پنل قدیمی جعبه‌ی بدون سقف داشت؛ اسکرول را خود مودال می‌دهد) + قاب حاشیه‌رنگ مثل `.pkg-long-desc` قدیمی
- **باگ رندر کارت پیشرفت نصب:** کلاس عرض خراب `w-in(92vw,34rem)]` (به‌جای `w-[min(92vw,34rem)]`) باعث می‌شد کارت نوار پیشرفت بی‌عرض/به‌هم‌ریخته دیده شود — رفع شد (عرض مثل قدیمی: max 500px معادل)
- بقیه‌ی زنجیره «در حال نصب» از لحظه‌ی درخواست تا نتیجه (markPending روی همه‌ی مسیرها) + مودال پیشرفت با polling — از فاز ۲۶ حفظ شد

### درس‌های کلیدی فاز ۲۷
1. **هرگز config:cache داخل درخواست فعال** — این بار همان درس فاز ۲۵ در نصاب تکرار شده بود (change_env پیش‌فرض true)؛ علائمش: خطای «پایان کار + سبز شدن با تلاش مجدد + داده‌ی ناقص»
2. `databaseHasSeedData()` جEDI نمی‌تواند «سید کامل» را از «نیمه‌کاره» تشخیص دهد — کارهای طولانی باید مثل مایگریشن‌ها «رَکورد پیشرفت + قابل ادامه» باشند
3. فیلتر منوی بازگشتی: پدرِ بی‌پرمیشن با فرزندانِ همه‌ردشده = گروه شبح‌وار؛ شرط دیده‌شدن پدر باید از فرزندانش بیاید
4. تست CLI از Gate ج Dial نمی‌گذرد (`!runningInConsole`) — تست پرمیشن فقط با درخواست HTTP واقعی معتبر است
5. وقتی config cache وجود دارد، .env خوانده نمی‌شود — تغییر .env در تست، بعد از هر optimize باید config.php را هم پاک کند
6. tar کلون‌ها symlink مطلق را با همان target کپی می‌کند — تست «ساخت لینک» باید بعد از حذف public/themes انجام شود

---

## فاز ۲۹ — فیلتر وضعیت قیمت (رایگان/پولی) در بازار پکیج‌ها

**درخواست کاربر:** «برای صفحه پکیج‌ها، فیلترهای رایگان بودن هم اضافه کن»

### ۱) فیلتر وضعیت قیمت — سه‌حالته (همه / رایگان / پولی)
- `PackageIndex::$free` با `#[Url(as: 'free')]` — state در URL نگه داشته می‌شود (اشتراک/رفرش امن)؛ مقادیر '' | 'free' | 'paid'
- **API مرکزی این پارامتر را نمی‌شناسد → فیلترینگ سمت پنل:** `loadPackagesWithPriceFilter()` همه‌ی صفحات API (با احترام به search/category/sort) را با کشِ per-page جمع می‌کند (سقف ۲۵ صفحه ضد حلقه)، بر اساس وضعیت قیمت فیلتر و **سمت پنل صفحه‌بندی می‌کند** (meta مصنوعی current_page/last_page/per_page/total)
- تعریف «رایگان» = همان چیزی که کارت‌ها نشان می‌دهند: `is_free` از API **یا** حداقل یک پلن رایگان (min_price=0) **یا** رایگان با اشتراک فعال پروژه — `isEffectivelyFree()` مشترک بین فیلتر و شمارنده‌ی کارت آماری (قبلا کارت آماری فقط is_free خام را می‌شمرد و با بج‌های کارت نمی‌خواند)
- حین فیلتر فعال، syncCache و installedMap/pendingSlugs مثل حالت عادی اجرا می‌شود

### ۲) UI — دو نقطه‌ی ورود
- ردیف «وضعیت قیمت» داخل کارت فیلترها: پیل‌های همه (خنثی) / رایگان (emerald) / پولی (amber) + چیپ «فقط پکیج‌های رایگان ×» با دکمه‌ی حذف سریع
- کارت‌های آماری «کل پکیج‌ها / رایگان / پولی» حالا کلیک‌پذیرند و فیلتر را toggle می‌کنند (کارت فعال: ring برند + تیک)؛ «نصب‌شده» عادی ماند
- Empty-state اختصاصی برای هر فیلتر («پکیج رایگانی یافت نشد» / «پکیج پولی یافت نشد») + دکمه‌ی «حذف همه‌ی فیلترها»
- آیکون `gift` به icons-map اضافه شد (قبلاً در کارت آماری «رایگان» بود ولی وجود نداشت → بی‌صدا circle-alert رندر می‌شد!)

### ۳) باگ جانبی — فیلترهای زنده که زنده نبودند (رفع)
- **ریشه:** `wire:model.live` روی search/category/sort فقط URL را عوض می‌کرد؛ لیست فقط با Enter (submit فرم applyFilters) تازه می‌شد — یعنی select ها عملاً بی‌اثر بودند تا کاربر Enter بزند
- رفع: هوک‌های `updatedSearch/updatedCategory/updatedSort/updatedFree` → page=1 + loadPackages() — جستجو/دسته/ترتیب حالا فوری فیلتر می‌کنند (جستجو با debounce 400ms خود input)
- پیل‌های قیمت از مسیر متفاوت می‌روند: `setFreeFilter()` مستقیم (بدون دور اضافیِ update) — بدون double-fetch

### ۴) باگ جانبی — Esc مودال جزئیات (رفع)
- `x-on:keydown.escape.window="closeDetail()"` روی مودال جزئیات → `closeDetail` در Alpine component وجود نداشت → کنسول ReferenceError و Esc کار نمی‌کرد → `$wire.closeDetail()`

### ۵) بازآرایی داخلی (رفکتور بدون تغییر رفتار)
- بدنه‌ی `loadPackages()` به سه هلپر شکست: `enrichPackages()` (min_price/has_free_plan/اشتراک) + `normalizeSubscriptionSummary()` + `syncInstalledState()` — مسیر فیلتر همان‌ها را استفاده می‌کند تا enrich دو نسخه نشود

**E2E (مرورگر، فیکسچر کش ۸ پکیج در ۲ صفحه‌ی API):** پیل رایگان → دقیقاً ۳ پکیج رایگان شامل پکیجی از صفحه‌ی ۲ API (تست تجمیع چندصفحه‌ای) + URL `?free=free` + چیپ فیلتر؛ پیل پولی → ۴ کارت + «صفحه ۱ از ۲» و صفحه‌ی ۲ → پکیج ۵م (صفحه‌بندی سمت پنل)؛ toggle از کارت آماری؛ «همه» → خالی‌شدن URL و برگشت لیست؛ فیلتر ترکیبی `?category=payment&sort=newest&q=توربو` با رفرش کامل صفحه؛ موبایل ۳۹۰px بدون overflow؛ کنسول در سشن تازه: صفر خطا؛ Esc مودال بدون خطا ✓

---

## فاز ۳۰ — اسلایدرهای بدون زبان در پنل، لینک‌های نوبار فرانت به پنل جدید، بازطراحی «تنظیمات دیگر» با سوییچ، نرمال‌سازی is_free پکیج‌ها

### ۱) اسلایدرها در پنل نمایش داده نمی‌شدند (رفع — ریشه‌یابی)
- **ریشه:** دیتابیس واقعی ۲۹ ردیف در جدول `sliders` دارد که همه‌ی `lang = NULL` (ایمپورت/دیتای قدیمی). `Slider::detectLang()` = `where('lang', 'fa')` → لیست پنل **۰ ردیف** بود؛ در حالی‌که فرانت همان ردیف‌ها را نشان می‌داد (کوئری‌های فرانت مثل `forPageAndGroup` فیلتر lang ندارند)
- **رفع:** کوئری `SliderIndex::sliders()` حالا `lang = locale OR lang IS NULL` است — با تِست: قدیمی ۰ / جدید ۲۹ ردیف
- اسلایدرهای ساخته‌شده با فرم جدید (lang=fa) مثل قبل می‌مانند؛ ویرایش ردیف NULL-lang آن را fa می‌کند (پورت دقیق store/update قدیمی)

### ۲) نوبار «دسترسی سریع مدیر» فرانت → پنل جدید
- `themes/WeblakShop/.../layouts/master.blade.php`: هر ۱۷ لینک `route('admin.*')` (که به `/admin-old/admin/*` می‌رفت) به معادل‌های `np.*` (مسیر `/admin/admin/*`) تبدیل شد — محصولات/ایجاد/دسته‌ها/برندها/دیدگاه‌ها/پرسش‌وپاسخ/مقالات/ایجاد مقاله/دسته‌بندی مقالات/دیدگاه‌های مقالات/تنظیمات/ویرایش صفحه/پیشخوان/پروفایل/نشست‌ها/اعلان‌ها/خروج
- نگاشت‌های نام‌متفاوت: `admin.products.categories.index → np.categories.product`، `admin.posts.categories.index → np.categories.post`، `admin.user.profile.show → np.profile`
- `user-menu.blade.php`: لینک «پنل مدیریت» هم → `np.dashboard`
- چک دسترسی‌های `can()` بدون تغییر (کلیدهای پرمیژن همان‌ها هستند)
- E2E: نوبار فرانت با لاگین ادمین → ۱۷ لینک، صفر لینک admin-old، همه → `/admin/admin/*`

### ۳) «تنظیمات دیگر» — بازطراحی مدرن + سوییچ به‌جای select فعال/غیرفعال
- **درخواست:** «برای فعال و غیرفعال نباید از select استفاده بشه»
- پارشال جدید `partials/setting-switch.blade.php` (سوییچ منطقی RTL/LTR — هم‌خانواده‌ی sms-switch فاز ۱۷) + ۴ آیکون جدید به icons-map (`users-round`, `droplets`, `stamp`, `circle-off`)
- `Others.php`: سه پراپرتی `multi_vendor_system_status` / `user_referrals_enable` / `watermarkStatus` از string `'true'/'false'` به **bool واقعی** تبدیل شدند (mount با filter_var؛ save دوباره به `"true"/"false"` برای سازگاری کامل با دیتای قدیمی option ها)
- UI: تب چندفروشندگی = کارت سوییچ + چیپ وضعیت زنده (فعال/غیرفعال)؛ تب کاربران = سوییچ ریفرال + فیلدهای وابسته که خاموش بودنشان dim + pointer-events-none می‌شوند (wire:model.live)؛ تب تصاویر = سوییچ واترمارک با همان الگوی گیت شدن موقعیت/تصویر؛ فرمت تصویر از select به کارت‌های رادیویی WebP/JPG/PNG؛ تب Pusher = چیپ وضعیت خودکار (`BROADCAST_DRIVER=pusher|log`)؛ دکمه ذخیره در سربرگ + نوار چسبان مثل صفحه اطلاعات
- selectهای چندگزینه‌ای (نوع هدیه، نوع تخفیف، موقعیت واترمارک) طبق خواسته دست‌نخورده ماندند — فقط بله/خیرها سوییچ شدند

### ۴) باگ کشف‌شده حین E2E: ذخیره‌ی تنظیمات با change_env نیمه‌کاره می‌ماند (رفع)
- **ریشه:** `change_env()` حتی وقتی مقدار تغییری نکرده بود، `.env` را بازنویسی می‌کرد (۱۰ بار در هر ذخیره‌ی Others) → در `artisan serve` هر بازنویسی یعنی «Environment modified. Restarting server...» → **همان درخواستِ در حال اجرا کشته می‌شد**: envها نوشته شده بود ولی حلقه‌ی optionها و activity log هرگز اجرا نمی‌شد (بدون خطا، بدون toast!)
- **رفع:** `change_env` حالا اگر مقدار موجود (با/بدون کوتیشن) دقیقاً برابر مقدار جدید باشد، **هیچ کاری نمی‌کند** (بدون بازنویسی فایل، بدون config:cache اضافی) — فقط تغییر واقعی نوشته می‌شود؛ رفتار نصب/تغییر واقعی دقیقاً مثل قبل
- نتیجه: ذخیره‌ی Others کامل شد (۲۱ ردیف option نوشته شد، toast و activity log هم) — روی php-fpm هم از ۱۰ بار فایل‌نویسی/config:cache بیهوده در هر ذخیره جلوگیری می‌شود

### ۵) پکیج‌ها — «is_free برابر true حتماً جزو رایگان‌ها» (سخت‌سازی شکل داده)
- `normalizeIsFree()`: هر شکلی از API → بولین تمیز (`true/1/1.0/"1"/"true"/"TRUE"/"yes"/"on"` → رایگان؛ `"false"/"0"/false/null/نبودن` → رایگان نیست) — `empty()` خام رشته‌ی `"false"` را هم رایگان می‌گرفت!
- نرمال‌سازی در `enrichPackages()` (نشان‌های کارت/آمار/فیلتر/priceLabel همه از یک مقدار تمیز می‌خوانند)، `openDetail()` (مودال جزئیات) و `InteractsWithPackages::checkPurchaseState()` (مسیر نصب رایگان)
- `isEffectivelyFree()` اول از همه is_free را چک می‌کند → پکیج is_free=true حتی اگر پلن پولی هم داشته باشد جزو رایگان‌هاست
- E2E با فیکسچر ۷ پکیجی در ۲ صفحه با شکل‌های مختلف: فیلتر رایگان → دقیقاً ۴ کارت (bool true + رشته "true" + int 1 + رشته "TRUE" از صفحه‌ی ۲ — تجمیع چندصفحه‌ای)؛ فیلتر پولی → ۳ کارت، هیچ is_free=trueای در آن نیست

**pint + php -l سبز؛ اسکرین‌شات‌ها: /home/z/shots/p30-*.png**

## فاز ۳۱ — بازخورد کاربر (راند ۱۸): فیلتر رایگان/پولی بازهم غلط — تعریف «رایگان» فقط از is_free + شمارنده‌ی کارت‌های آماری که با تغییر فیلتر نمی‌پرد

### ۱) ریشه‌ی واقعی باگ: قانون «min_price=0 = رایگان» پکیج‌های پولیِ دارای طرح رایگان یک‌ماهه را هم «رایگان» می‌گرفت
- **علامت دقیقاً مطابق گزارش کاربر:** کلیک «رایگان» → همه‌ی پکیج‌ها؛ کلیک «پولی» → هیچ پکیجی. کاربر: «همه پکیج‌ها به جز ۲ تا پولی‌اند و همان ۲ تا طرح رایگان یک‌ماهه دارند ولی کلاً رایگان نیستند»
- **ریشه:** `isEffectivelyFree()` سه شرط داشت: `is_free` | `min_price===0` | `is_free_with_subscription`. دو پکیج پولی، پلن رایگانِ یک‌ماهه (price=0) دارند → `min_price=0` → «رایگان مؤثر»! همه‌ی پکیج‌ها true می‌شدند → فیلتر رایگان = همه، فیلتر پولی = هیچ
- **تعریف جدید (به انتخاب کاربر):** «رایگان» یعنی فقط `is_free = true` (کلاً رایگان). پکیجی که فقط یک پلن رایگان دارد یا با اشتراکِ فعال قابل نصب است، «کلاً رایگان» نیست → جزو «پولی‌ها»
- `isEffectivelyFree()` حالا تک‌خطی: `return normalizeIsFree($pkg['is_free'] ?? null);` — همان قانونی که نشان‌های (badge) کارت‌ها همیشه داشتند → فیلتر/آمار/نشان یک‌دست شدند
- مسیر نصب با پلن رایگان (نصب از طریق طرح یک‌ماهه) و نشان «رایگان با اشتراک» طبق پنل قدیمی دست‌نخورده ماندند

### ۲) کشف حین E2E: پاسخ کش‌شده‌ی قدیمی، تست را گمراه می‌کرد (گزارش «شاید در کش قرار میگیره» کاربر بی‌دلیل نبود)
- فایل‌کشِ محیط تست، ریسپانس‌های API فیکسچرِ جلسه‌های قبل (per_page=5، total=7، desc «p1-free-bool») را نگه داشته بود و `Cache::remember('packages.list.*')` آن‌ها را قبل از رسیدن به سرور برمی‌گرداند — `cache:clear` حلش کرد
- نکته‌ی زنده بودن این بحث: TTL لیست ۵ دقیقه است؛ روی هاست واقعی هم بعد از تغییر is_free در پنل مرکزی حداکثر ۵ دقیقه دیتای قبلی دیده می‌شود (رفتار عادی کش، باگ نیست)

### ۳) ریزِ کشف‌شده در همان تست: عددهای کارت‌های آماری با تغییر فیلتر می‌پرید
- قبلاً شمارنده‌ها روی `$packages` (برشِ فعلی) حساب می‌شد → با فیلتر پولی فعال: «کل ۲ / رایگان ۰» (!) در حالی که بازار ۱۲ پکیجی است
- **رفع:** پراپرتی عمومی `priceCounts` = آمار همیشه از «بازار»: در حالت عادی از صفحه‌ی جاری (per_page=12 کل بازار را پوشش می‌دهد)، در حالت فیلتر از کل نتایج تجمیع‌شده‌ی همه‌ی صفحات API **قبل از** اعمال فیلتر قیمت → عددها هنگام جابه‌جایی فیلتر ثابت می‌مانند

### E2E (مرورگر واقعی — پنل لوکال + سرور mock پکیج‌ها با دیتای گزارش کاربر: ۱۰ رایگان + ۲ پولی دارای طرح رایگان یک‌ماهه)
- «همه» → ۱۲ کارت (۱۰× نشان رایگان + ۲× نشان پولی)؛ کارت‌های آماری: کل ۱۲ / رایگان ۱۰ / پولی ۲
- «پولی» → دقیقاً `checkout-turbo` و `rack-widget` با نشان پولی و چیپ «فقط پکیج‌های پولی»؛ کارت‌های آماری **بدون تغییر** (۱۲/۱۰/۲)
- «رایگان» → دقیقاً ۱۰ پکیج is_free=true؛ URL state `?free=free/paid` سالم
- مودال جزئیات checkout-turbo: نشان «پولی» + پلن‌ها (یک‌ماهه رایگان + یک‌ساله) طبق پنل قدیمی
- ۵ صفحه‌ی دیگر پنل (داشبورد/محصولات/تنظیمات/اسلایدرها/نوشته‌ها) بدون رگرسیون

**php -l سبز؛ اسکرین‌شات‌ها: /home/z/my-project/shots/pkg-*.png**

## فاز ۳۲ — دو-پنلی شدن کامل پکیج‌ها (منو + صفحات) + رفع باگ‌های واقعی ماژول‌ها

**درخواست کاربر:** منوی ۱۰ پکیج در پنل جدید استایل ندارد؛ صفحات پکیج‌ها همه `@extends('back.layouts.master')` هستند — باید در «هر دو پنل» درست کار کنند (و برای پنل‌های آینده هم قرارداد داشته باشند).

### قرارداد دو-پنلی (ماژول‌محور و آینده‌پذیر)
- کوکی `ap_panel` = «پنل فعال»؛ هر دو layout هنگام لود با JS ست می‌کنند (new/old) — آخرین پنل بازدیدشده همیشه برنده است
- `EncryptCookies::$except += ['ap_panel']` — کوکی خام JS بدون این، middleware از درخواست حذف می‌کرد (root cause اصلی سوییچ نشدن)
- کلاس `Modules\{X}\Support\ActivePanel` در هر ۱۰ پکیج: `isNew()` (کوکی → admin-old مسیر → پیش‌فرض new) + `hasNewChrome()` (view()->exists لایه پل) + `useNewPanel()`
- منوی هر پکیج دو-رندره: سایدبارها با `@include($menuView, ['apPanel' => 'new|old'])` کانتکست می‌دهند؛ بدون کانتکست ActivePanel تشخیص می‌دهد
- صفحات پکیج‌ها: `@extends(ActivePanel::useNewPanel() ? 'admin-panel::layouts.module' : 'back.layouts.master')` — هر ۸۱ ویو back هر ۱۰ پکیج

### لایه‌ی پل `admin-panel::layouts.module` (جدید)
- کروم پنل جدید (سایدبار/تاپ‌بار/فوتر/توست + Vite + Livewire) + استک کامل CSS/JS پنل قدیمی (بوت‌استرپ و…)
- ترتیب CSS: پنل جدید ← بوت‌استرپ قدیمی ← کمپات `:where()` (نشت reboot بوت‌استرپ به کروم خنثی، بدون شکستن utilityهای تیلویند)
- مودال‌های main-errors/password-confirm + session toasts پنل قدیمی حفظ شده؛ @yield('title') → تاپ‌بار breadcrumb می‌گیرد

### باگ‌های واقعی کشف‌شده در ماژول‌ها (هیچ‌کدام تا حالا اجرا نشده بودند)
- **DigiPay**: RouteServiceProvider هرگز `routes/admin.php` را لود نمی‌کرد (صفحات ادمین در هیچ پنلی در دسترس نبود) + متد تکراری `testConnection()` (fatal redeclare) + منو در مسیر اشتباه `partials/` بود + view بدون namespace مدل (`DigiPaySetting`) + `LicenseGuard::check()` bool برمی‌گرداند ولی view آرایه می‌خواهد + امضای دستور پرمیشن با CreditPay تداخل داشت
- **CreditPay/InstallmentPayment**: ثبت‌نام منوی ادمین به فایل منوی «پنل کاربری» (front) اشاره می‌کرد — منوی ادمین از صفر ساخته شد (`back/partials/menu.blade.php` از روی روت‌ها + پرمیشن‌های واقعی)
- **CheckoutTurbo**: پوشه `Database/` با حرف بزرگ — `loadMigrationsFrom('database/migrations')` هرگز اجرا نمی‌شد (جدول‌ها ساخته نمی‌شدند؛ داشبورد ۵۰۰) — git mv به `database/`
- `CreditSetting::get` fail-safe شد (نصب تازه قبل از migrate کرش نمی‌کند)

### پنل جدید
- همه منوهای پکیج زیر «یک» تیتر پکیج‌ها (مثل قدیمی)، آکاردئون apMenuGroup، حالت فعال is-active، یکپارچه با جستجوی منو (apMenuSearch)
- E2E مرورگر: کلیک منو در هر دو پنل → صفحه با کروم همان پنل + breadcrumb + is-active + dark mode + استک jQuery/SweetAlert/toastr پکیج‌ها سالم؛ صفحه‌های کلیدی هر ۱۰ پکیج HTTP 200

**php -l + pint سبز؛ اسکرین‌شات‌ها: /home/z/shots/final-*.png**

## فاز ۳۴ — تزریق میزبان‌محور پکیج‌ها (بدون نیاز به آپدیت پکیج) + جابه‌جایی منو + دو فیکس سیستمی

**درخواست کاربر:** ۱) «تنظیمات برنامه‌نویس» هنگام ذخیره ارور `Undefined variable $start`؛ ۲) ارور `1062 Duplicate entry 'admin_device_unique'` در admin_sessions (هر دو پنل)؛ ۳) منوی پکیج‌ها به بالای «امور مدیریتی»؛ ۴) منوی پکیج‌ها در پنل جدید بی‌استایل است (اسکرین‌شات) و با کلیک روی پکیج‌ها ظاهر پنل قدیمی لود می‌شود — «باید یه جوری تزریق پنل جدید بشن و مهم نیست که SPA و لایوایر نیستن».

### ریشه‌ی واقعی مشکلات ۳ و ۴: «میزبان» به نسخه‌ی پکیج وابسته بود
- تا فاز ۳۲ سایدبار جدید پارشال منوی هر پکیج را مستقیم `@include` می‌کرد → خروجی مارک‌آپ به «نسخه‌ی نصب‌شده» وابسته بود؛ پکیج‌های قدیمیِ روی سرور کاربر مارک‌آپ بوت‌استرپی می‌دادند = بی‌استایل در سایدبار جدید
- صفحات پکیج‌های قدیمی همیشه `@extends('back.layouts.master')` (بدون شرط) = کروم قدیمی حتی از سایدبار جدید

### راه‌حل — «میزبان تصمیم می‌گیرد» (فقط تغییرات ریپوی اصلی؛ پکیج‌ها دست‌نخورده)
- **`App\Support\ActivePanel`**: رزولور واحد پنل فعال با اولویت `?ap=new|old` (لینک‌های سایدبار جدید) ← مسیر `admin-old/*` ← کوکی `ap_panel` ← پیش‌فرض جدید + `hasNewChrome()` + `layoutToExtend()`
- **`back/layouts/master.blade.php`** فقط یک wrapper شد: `@extends(ActivePanel::layoutToExtend())` — محتوای قبلی به `master-legacy.blade.php` منتقل شد. یعنی **هر ویوی کلاسیکی که مستر را extends کند — پکیج قدیمی یا جدید — خودکار کرومِ پنل فعال را می‌گیرد** (تزریق پنل جدید به پکیج‌های قدیمی بدون هیچ تغییری در پکیج)
- **`admin-panel::layouts.module`** هم wrapper شد (`module-new.blade.php` = محتوای پل فاز ۳۲) تا ویوهای پکیج‌های به‌روزشده هم از همان رزولور عبور کنند — دو تبِ همزمان دو پنل هم قطعی درست کار می‌کند
- **`App\Services\ModuleMenuExtractor`**: سایدبار جدید دیگر مارک‌آپ پکیج را include نمی‌کند؛ «داده» استخراج می‌کند: پارشال منوی پکیج در کانتکست قدیمی رندر و با DOMDocument پارس می‌شود (li.nav-item.has-sub / menu-title / menu-content / menu-item + active/open) → سایدبار با مارک‌آپ خودش رندر می‌کند = «دقیقاً مثل بقیه منوها» مستقل از نسخه پکیج؛ fallback: پارسِ ناموفق → include مستقیم (رفتار فاز ۳۲)؛ پشتیبانی از آیتم تکی بدون زیرمنو و کلاس menu-title برای آیتم‌ها (AIWriter)؛ مموای هر ریکوئست
- لینک‌های منوی پکیج در سایدبار جدید `?ap=new` می‌گیرند (پورت/کوئری/فرگمنت حفظ می‌شود) — باگ پورت‌افت در بازسازی URL تست و رفع شد
- **سایدبار جدید**: گروه مصنوعی «پکیج‌ها» دقیقاً **قبل از «امور مدیریتی»** تزریق می‌شود (partial جدید `sidebar-packages.blade.php`)؛ اگر امور مدیریتی نبود، آخر منو (fallback)
- Font Awesome 6.3 (همان CDN پنل قدیمی) به head پنل جدید اضافه شد — آیکون‌های منوی پکیج‌ها نمایش داده می‌شوند

### فیکس `1062 Duplicate entry 'admin_device_unique'`
- ریشه: میدل‌ور RecordAdminSession مسیر update-then-create داشت؛ دو درخواست همزمان (wire:poll + کلیک کاربر بعد از پنجره ۶۰ ثانیه) هر دو UPDATE خالی می‌گرفتند و هر دو INSERT → تکراری = 500 کل درخواست (و در لایو با صفحه‌ی خطای debug خراب، به‌صورت `Undefined variable $start` دیده می‌شد)
- فیکس: `AdminSession::upsert()` اتمیک (INSERT ... ON DUPLICATE KEY UPDATE) + catch کل блок (ردیابی نشست هیچ‌وقت درخواست ادمین را نمی‌ترکاند)
- تست: ۸ درخواست موازی با یک نشست → همه 200، صفر خطا در لاگ، دقیقاً ۱ ردیف؛ مسیر update هم تأیید شد

### نکته برای `Undefined variable $start` روی سرور
- هیچ ویویی در تاریخچه ریپو `$start` ندارد (git -S)؛ خطا از رندر «صفحه‌ی خطای debug» روی سرور می‌آید که زیر exception واقعی (همان 1062) رخ می‌داده. بعد از دیپلوی این فاز + یک‌بار `php artisan view:clear` روی سرور (پاک‌شدن ویوهای کامپایل‌شده قدیمی renderer خطا) علامت `$start` هم از بین می‌رود

### E2E مرورگر (سناریوی کاربر بازسازی شد: ماژول Story به نسخه‌ی «قبل از فاز ۳۳» برگردانده شد)
- سایدبار جدید: پکیج‌ها قبل از امور مدیریتی + ۱۰ گروه آکاردئونی با آیکون و استایل یکسان + is-active + بازشدن خودکار + جستجوی منو («دیجی» → فقط دیجی‌پی با زیرمنوی باز)
- کلیک «لیست استوری‌ها» از سایدبار جدید → صفحه پکیج قدیمی **داخل کروم پنل جدید** (کارت‌های بوت‌استرپی داخل main جدید + فوتر جدید + sidebar جدید)
- از سایدبار قدیمی (کوکی=old) → همان صفحه با کروم قدیمی؛ مسیر admin-old حتی با کوکی=new قدیمی می‌ماند؛ `?ap=new` روی کوکی=old برنده است
- CheckoutTurbo (نسخه جدید) → مثل قبل کروم جدید؛ صفحات خود پنل قدیمی (products) دست‌نخورده
- ذخیره تنظیمات توسعه‌دهنده: توست «تنظیمات توسعه‌دهنده ذخیره شد.» + persist واقعی option/env (دور رفت‌وبرگشت کامل)

**php -l + pint سبز؛ اسکرین‌شات‌ها: /home/z/shots/f34-*.png**

---

## فاز ۳۶ — ریشه‌کنی کامل «به‌هم‌ریختگی قالب» صفحات پکیج در پنل جدید (Cascade Layers)

### گزارش کاربر (اسکرین‌شات stories/create بعد از فاز ۳۵)
«منوها استایل خودشان را از دست می‌دهند و اسکرول‌شان کار نمی‌کند، خود صفحه پدینگ زیاد دارد، منوها هم پدینگ دارند» — فقط در صفحات پکیج‌ها.

### چهار ریشه‌ی واقعی (همه با اندازه‌گیری computed-style ثابت شد)
1. **`aside { display: block }`** — reboot بوت‌استرپ (`article,aside,…{display:block}`) قاعده‌ای unlayered (0,0,1) است و بر کلاس لایه‌دار `flex` تیلویند غالب می‌شود → سایدبار به‌جای ستون فلکس، بلوک می‌شد؛ `nav` داخلی (flex-1) به‌جای ارتفاع محدود، ارتفاعِ کل منو (۲۰۸۰px) می‌گرفت → **اسکرول داخلی سایدبار می‌مرد و برند/جستجو/منو/کارت‌کاربر از هم می‌پاشید**
2. **اسکیل فاصله‌گذاری قدیمی با `!important`** — bootstrap.css این پروژه اسکیل خودش را با !important تعریف می‌کند (`.px-4{padding:3.5rem!important}` ، `.py-2{1.5rem!important}` ، `.mt-2{1.5rem!important}` و…) و دقیقاً اسم کلاس‌های تیلویند را می‌رباید → پدینگ/مارفینِ کروم (nav سایدبار px-3، برند px-5، لینک‌ها، فوتر و main) منفجر می‌شد («منوها پدینگ دارند»)
3. **`.content-wrapper { margin-top: 6rem; padding: 1.8rem 2.2rem }`** (components.css) برای ناوبارِ ثابتِ پنل قدیمی طراحی شده؛ در کروم جدید تاپ‌بار در جریان سند است → فاصله‌ی مضاعف ۶rem بالای کارت («پدینگ زیاد صفحه»؛ کارت از ۱۵۳px به ۲۷۸px می‌افتاد)
4. **قواعد عنصری reboot روی فرم‌المان‌ها** — `input,button{font-size:inherit}` و `button{border-radius:0}` (unlayered) کلاس‌های لایه‌دار `text-[13px]` (اینپوت جستجو ۱۶px می‌شد) و `rounded-*` (دکمه‌های گروه منو مربع می‌شدند) را می‌کشتند؛ همچنین قاعده‌ی کمپاتِ قبلی `a{color:inherit}` رنگ سبزِ آیتم فعال منو را می‌کشت (unlayered بر لایه‌دار غالب است حتی با specificity پایین‌تر)

### راه‌حل ساختاری — Cascade Layers (فقط module-new؛ پنل قدیمی و SPA دست‌نخورده)
- **پرولود**: `@layer ap-early, theme, base, legacy, ap-late;` قبل از همه CSSها
- **کل CSS پنل قدیمی** (۱۸ فایل + فونت + FA) با `@import … layer(legacy)` داخل لایه می‌رود:
  - legacy بعد از base → reboot همچنان بر preflight غالب است (رفتار پنل قدیمی حفظ)
  - components/utilities تیلویند بعد از legacy → **هر کلاس تیلویند کروم بر هر قاعده عنصری بوت‌استرپ برنده است** (جنگ specificity/ترتیب برای همیشه تمام)
- **ap-early**: خنثی‌سازهای `!important` (برای !important، لایه‌ی جلوتر برنده است): بازسازی اسکیل تیلویند برای ۱۳ کلاس تصادمی (mt-2/mx-1/p-1/p-2/p-3/pt-1/pt-4/px-1/px-3/px-4/px-5/py-2/py-4) + rounded-lg + فونت span/input/textarea/select/button کروم — فقط داخل کروم؛ محتوای بوت‌استرپی پکیج دست‌نخورده
- **ap-late**: بازسازی‌های عادی فاز ۳۵ (root 16px، بدنه، فونت/رنگ کروم، ul/a/p، hidden sm:block، .content margin) + `#ap-chrome-side > aside{display:flex}` (بیمه‌ی پشتیبان) + `html body .content .content-wrapper{margin-top:0;padding:0}`
- `main` و `footer` در module-new به مقدارهای دلخواه (`px-[16px]` و…) تبدیل شدند چون کلاس‌های خودِ این دو عنصر با ترکیب‌کننده‌ی فرزند (`:is(...) .px-4`) پوشش داده نمی‌شوند
- rebuild بیلد ادمین (کلاس‌های دلخواه جدید کامپایل شدند)

### E2E (اندازه‌گیری + اسکرین‌شات، ماژول Story + ماژول DigiPay)
- stories/create: aside flex، nav ۵۶۰px با اسکرول داخلی، root 16px، کارت از y=153، main 24/32px، فوتر 16/32px، لینک فعال سبز (rgb(4,120,87))، اینپوت جستجو 13px/Vazirmatn، دکمه‌های گروه 12px/14px
- پاریتی کامل با داشبورد (aside 272px، لینک 8px/12px، عنوان گروه 11px، برند 64px)
- stories/index: فیلتر و لیست سالم؛ DigiPay/transactions: کروم جدید + محتوای بوت‌استرپی خود پکیج بی‌نقص
- دارک‌مود (کروم تیره سالم)، موبایل ۳۹۰px (سایدبار جمع، محتوا تمام‌عرض)، صفحه Livewire محصولات بدون رگرسیون
- پنل قدیمی (کوکی ap_panel=old): همان صفحه با کروم قدیمی — دست‌نخورده (لینک‌های ساده، بدون لایه)

**اسکرین‌شات‌ها: /tmp/final-create.png، /tmp/final-index-vp.png، /tmp/digipay-test2.png، /tmp/dark-create.png، /tmp/mobile-create.png، /tmp/oldpanel-create.png**

## فاز ۳۷ — پدینگ اضافی منو در صفحات پکیج‌ها (جنگ `!important` + لایه‌ها و بازنویسی فیزیکی شمارنده‌های ap-early)

### گزارش کاربر
«منو پنل با css های پکیج ها تداخل پیدا کرده... padding های اضافی استایل منو را خراب کرده»

### ریشه‌ها (اندازه‌گیری‌شده در مرورگر — `padding-left` محاسبه‌شده‌ی nav روی هر صفحه‌ی ماژول ۴۸px بود به‌جای ۱۲px)
1. **بلوک شمارنده‌های ap-early فاز ۳۶ LAYER نشده بود (unlayered)** — برای اعلان‌های `!important` ترتیب لایه‌ها برعکس می‌شود و قاعده‌ی unlayered به هر قاعده‌ی لایه‌دارِ `!important` می‌بازد؛ bootstrap.css قدیمی این پروژه اسکیل کامل فاصله‌گذاری را با `!important` می‌فرستد (`.px-3{padding-left/right:3rem!important}`، `.p-1{1rem!important}`، `.py-2{1.5rem!important}`، `.mt-2{1.5rem!important}` و…) داخل `layer(legacy)` → همه‌ی شمارنده‌ها باختند
2. **شمارنده‌ها LOGICAL بودند (`padding-inline`/`padding-block`)** — اعلان‌های فیزیکی (legacy از `padding-left/right/top/bottom` استفاده می‌کند) به همان longhand محاسباتی نگاشت می‌شوند و بر logical برنده‌اند، مستقل از ترتیب لایه‌ها

### راه‌حل (فقط blade، resources/views/admin-panel/layouts/module-new.blade.php)
- کل بلوک شمارنده‌ها داخل `@layer ap-early` رفت (اولین لایه‌ی پرولود → برنده‌ی جنگ `!important`)
- همه‌ی شمارنده‌ها به longhand فیزیکی با مقادیر تیلویند (N×0.25rem) بازنویسی شدند
- مجموعه‌ی شمارش‌شده به اسکیل کامل legacy گسترش یافت: p/px/py/pt/pb/pl/pr + m/mx/my/mt/mb/ml/mr × {0..5}
- `.rounded-lg` ماند؛ شمارنده‌ی `.border` اضافه شد (legacy `.border{border:1px solid #EDEDED!important}` رنگ حاشیه را می‌ربود — `--color-line` + واریانت `.dark` با `--color-night-line` بازگردانده شد)
- شمارنده‌های font-family ایرانیکَن به ap-early منتقل شدند
- اسکوپ بدون تغییر: فقط `#ap-chrome-side`/`#ap-chrome-top`/`.ap-footer`/`.ap-toasts`؛ محتوای پکیج و پنل قدیمی دست‌نخورده

### E2E (تأییدشده در مرورگر)
- padding nav: 16px 48px 24px → 4px 12px 24px؛ لینک‌ها 8px/12px با gap 10px و radius 12px = پاریتی داشبورد؛ ۴۶ لینک، همه ۳۶px بلند و یکنواخت؛ اینپوت جستجو py 8px + Vazirmatn؛ اسکرول داخلی nav سالم؛ دارک‌مود درست؛ موبایل ۳۹۰px بدون overflow؛ SPA پکیج‌ها + داشبورد بدون رگرسیون؛ پنل قدیمی /admin-old دست‌نخورده

**کامیت:** `da317e74`

### درس برای فازهای بعدی
1. **`!important` + لایه‌ها:** unlayered به لایه‌دار می‌بازد (برای `!important` ترتیب لایه‌ها برعکس می‌شود)
2. **فیزیکی بر logical غالب است** برای همان longhand محاسباتی
3. **یوتیلیتی‌های `px-*`/`py-*` تیلویند به `padding-inline`/`padding-block` (logical) کامپایل می‌شوند** — شمارنده در برابر قواعد فیزیکی legacy باید حتماً فیزیکی باشد

## فاز ۳۸ — بهم‌ریختگی صفحات پنل بعد از بازگشت از پکیج (Livewire استایل‌های <head> را حذف نمی‌کند)

### گزارش کاربر
«وقتی می‌رم تو صفحه‌ی یکی از پکیج‌ها همه‌چی درسته، ولی وقتی می‌رم تو صفحه‌های خود پنل بهم می‌ریزه و وقتی رفرش می‌کنم درست می‌شه — فقط زمانی بهم می‌ریزد که از صفحات پکیج بیام به صفحات پنل»

### ریشه (اندازه‌گیری‌شده — سورس `mergeNewHead` در vendor/livewire/livewire/dist/livewire.esm.js)
Livewire در wire:navigate استایل‌های `<head>` را **هرگز حذف نمی‌کند** (assetها = link[stylesheet]/style/script فقط «انباشته» می‌شوند؛ فقط non-assetها مثل meta/title تعویض می‌شوند). منوی سایدبار مشترک است (`menu-item` با `wire:navigate`) → ناوبری پکیج → پنل SPA است و این بلوک‌های head صفحه‌ی پل **برای همیشه** روی صفحه‌ی پنل می‌ماندند:
1. بلوک legacy (@import کل CSS قدیمی در `layer(legacy)`) — اسکیل `!important` قدیمی (`.px-3=3rem!important` و…) که شمارنده‌های ap-early فقط «داخل ap-chrome» خنثی‌اش می‌کنند، روی صفحات پنل اصلاً ap-chrome وجود ندارد → مستقیم به `px-3`/`p-3`/`py-2` سایدبار و تاپ‌بار پنل ضربه می‌زد (پدینگ منوها منفجر می‌شد = اسکرین‌شات کاربر)
2. `@stack('styles')` پکیج‌ها — **unlayered** (قوی‌تر از هر لایه‌ای) — استایل‌های خودِ پکیج (DigiPay/CheckoutTurbo/AIWriter/seoAudit همه push می‌کنند) روی صفحات پنل جا می‌ماند
3. بلوک ap-late (`html body{…}`، `aside{display:flex}` و…) — روی صفحات پنل اعمال می‌شد
رفرش درست می‌شد چون head تازه هیچ‌کدام را ندارد. جهت معکوس (پنل → پکیج) لود کامل است و سالم بود.

### راه‌حل (blade-only؛ بدون rebuild)
- **module-new.blade.php**: سه بلوک پلِ head (ap-early، legacy، ap-late) با `data-ap-bridge-style` نشانه‌گذاری شدند؛ `@stack('styles')` از head به بدنه منتقل شد (بعد از کروم، قبل از `<main>` — unlayered می‌ماند، با `replaceWith(newBody)` در هر ناوبری خودبه‌خود می‌رود، و چون قبل از main است بدون FOUC است)
- **layout.blade.php (پنل)**: پرولود `@layer ap-early, theme, base, legacy, ap-late;` قبل از @vite اضافه شد — برای مسیر «پنل ← (دکمه‌ی بازگشت/SPA) → پکیج» که mergeNewHead بلوک‌های پل را «دیرتر» append می‌کند؛ بدون این پرولود legacy بعد از utilities می‌افتاد و کروم پل می‌شکست؛ روی خود صفحات پنل بی‌اثر است (هیچ قاعده‌ای در این لایه‌ها نیست) و نسخه‌ی عیناً یکسانش در module-new با dedupe شدن mergeNewHead دوبار اجرا نمی‌شود
- **اسکریپت پاک‌سازی (عیناً یکسان در head هر دو layout)**: روی `livewire:navigated`، اگر صفحه‌ی مقصد پل نیست (`#ap-chrome-side` ندارد) همه‌ی `head [data-ap-bridge-style]` حذف می‌شوند — یک بار در هر سند اجرا می‌شود (head-asset → dedupe) و بین ناوبری‌ها زنده می‌ماند

### E2E (مرورگر واقعی — ۵ سناریو)
- پکیج (stories/create + digipay/settings) → پنل (redirects) با SPA: بعد از swap → `bridgeStyles=0`، nav padding ۱۲px (نه ۴۸px)، aside flex/272px، body padding 0 = پاریتی کامل با رفرش
- پنل → پکیج: رندر درست (chromeSide=true، ۳ بلوک، nav 12px)
- دکمه‌ی بازگشت مرورگر (پنل → پکیج با SPA): بلوک‌ها دیر append می‌شوند ولی پرولود پنل ترتیب لایه‌ها را نگه داشته → nav 12px و aside flex = کروم سالم
- پنل → پنل: ۰ بلوک، بدون رگرسیون
- استایل‌های push‌شده‌ی DigiPay در body رندر می‌شوند و بعد از ناوبری کاملاً پاک می‌شوند (`digipayStyleInBody=false` روی پنل)

## فاز ۳۹ — استقلال پکیج‌ها از پنل + رفع ارور ۵۰۰ افزودن استوری + تست انقضای لایسنس

### گزارش کاربر
۱. «بعضی از پکیج‌ها وابسته به پنل هستن — مثلاً در پکیج استوری فایل‌های js و css در خود پنل هستند (asset('back/assets/js/pages/stories/...'))»
۲. «موقع افزودن استوری ارور 500 می‌داد ولی ارور را نمایش نمی‌داد»
۳. «بقیه پکیج‌ها را هم بررسی کن که وابسته به پنل نباشند و اگر بودند فایل‌ها را به خود پکیج منتقل کن»
۴. «تست کن که اگر اشتراک یا زمان استفاده از پکیج تمام شود، پکیج از کار می‌افتد یا نه»

### ۱) ممیزی وابستگی پکیج‌ها به پنل (هر ۱۰ پکیج)
| پکیج | وابستگی | اقدام |
|---|---|---|
| Story | `story.css` + ۵ فایل js (`all/create/edit/index/details`) در `public/back/assets/` | **انتقال به پکیج** → `resources/assets/css/back/story.css` و `resources/assets/js/back/stories/*.js`؛ ۵ blade به `module_asset('Story', ...)` |
| SmartAssembly | `/back/assets/images/pages/no-image.jpg` هاردکد (در میزبان حتی وجود نداشت!) | تصویر placeholder داخل پکیج + `module_asset('SmartAssembly', ...)` در rules/index و rules/manager (متغیر JS) |
| seoAudit | همان تصویر در products/details | تصویر داخل پکیج + `module_asset('SeoAudit', ...)` |
| ۷ پکیج دیگر (DigiPay, CreditPay, InstallmentPayment, RackWidget, CheckoutTurbo, AICatalog, AIWriter) | فقط `@include('back.partials.plugins')` (ویوی میزبان — سرویس‌ساید، مجاز) و `asset('/no-image-product.svg')` | بدون تغییر |

قرارداد استقلال: asset پکیج در `Modules/{Name}/resources/assets/` می‌ماند، نصب‌کننده (`PackageInstallerService::publishModuleAssets`) آن را به `public/modules/{slug}/` کپی می‌کند و ویوها با `module_asset()` صدا می‌زنند — پکیج روی هر میزبانی که نصب‌کننده دارد مستقل است.

### ۲) ریشه‌یابی ارور ۵۰۰ افزودن استوری (بازتولید واقعی در مرورگر)
- **مسیر بازتولید:** جستجوی محصولِ دارای قیمتِ ناموجود/غیرقابل‌فروش (`getPrices()->first()` = null) → `Call to a member function get_attributes() on null` در `StoryController::getProductWithId:169` (و همین الگو در `edit()`) → POST `/admin/admin/stories/product` = **500**
- **چرا کاربر ارور را نمی‌دید:** همه‌ی AJAXهای پکیج (create/edit/all/index/details) هیچ `error:` callback نداشتند → 500/422 کاملاً بی‌صدا بود
- **اصلاحات (ریپوی پکیج استوری):**
  - helper نال-سیف `buildProductCard()` برای getProductWithId و edit (محصول بدون قیمت → کارت با قیمت خالی، بدون کرش؛ محصول حذف‌شده در edit → کارت خالی)
  - `parseJalaliExpiry()`: تبدیل تاریخ با ۶ فرمت جایگزین + تبدیل اعداد فارسی/عربی → در بدترین حالت **422 با پیام فارسی** به‌جای 500
  - `error:` handler برای تمام ۸ AJAX پکیج — پیام 422 (اعتبارسنجی)، 419 (انقضای نشست)، 500 (سرور) با toast
  - نال-سیف کردن `Storage::delete` قبلی در update

### ۳) تست انقضای اشتراک/لایسنس (سناریوی واقعی در مرورگر)
- `installed_modules.license_expires_at` استوری به گذشته تنظیم شد + کش پاک
- ✓ روت‌های ادمین (لیست/ایجاد/ویرایش استوری) → **403 + صفحه‌ی زیبای license_invalid** («ارتباط با سرور مجوز قطع شده است…»)
- ✓ منوی «استوری‌ها» از سایدبار پنل جدید حذف شد
- ✓ روت‌های فرانت (`/story/{id}`) → 403؛ ویجت استوری از صفحه‌ی اصلی حذف شد
- ✓ مسیر پنل قدیم هم همان رفتار را دارد
- ✓ بعد از تمدید (تاریخ آینده + پاک‌سازی کش) → ریکاور کامل بدون رستارت (چرخه‌ی ۱۵ دقیقه‌ای LicenseGuard + چک دوره‌ای ۲۴ ساعته)
- نکته: وقتی API مرکزی در دسترس نیست (sandbox) ماژول بلاک می‌ماند (رفتار امن درست است؛ گرسنه‌کشی نمی‌کند — تلاش مجدد ۱۵ دقیقه‌ای)

### E2E نهایی
- ایجاد استوری (تصویری + ویدیویی + با محصول): 200 → redirect به لیست ✓ (هر دو پنل)
- صفحه‌ی ویرایش استوریِ دارای محصولِ بدون قیمت فروش‌پذیر: بدون کرش ✓
- assetهای جدید: `modules/story/css/back/story.css` و `modules/story/js/back/stories/*.js` → 200 در هر دو پنل (کوکی ap_panel) ✓
- جستجوی محصول ناموجود → toast «محصول پیدا نشد!» ✓ (سیستم toast تایید شد)

**کامیت‌ها:** پکیج استوری `9d8399c..` (master)، SmartAssembly `1a7ee1e..` (master)، seoAudit `257f8ea..` (master)، میزبان `551125a0` (docs/project-analysis)

---

## فاز ۴۰ — رفع ارور «انتخاب طرح» در نصب پکیج‌های رایگان (is_free=true) + رفرش خودکار صفحه پس از نصب

**گزارش کاربر:** ۱) پکیج‌هایی که `is_free` آن‌ها true است، طرح‌های قیمت‌گذاری را در مودال نصب نمی‌آورند و به‌خاطر همان «ارور انتخاب طرح» می‌دهد. ۲) بعد از اینکه پکیج کامل نصب شد، صفحه رفرش شود تا پکیج به منو اضافه شود.

### ریشه‌یابی
- مودال تأیید نصب (Alpine `pkgMarket.ask()` در admin.js) برای `pkg.is_free` **زود برمی‌گشت** و `plans` را خالی می‌گذاشت (mode=free) — در حالی که پنل قدیمی `plans.length` را چک می‌کرد نه `is_free` را؛ یعنی پکیج‌های رایگانِ دارای پلن، پلن‌ها را نمی‌دیدند.
- با planId=null، سرور `createPurchase(slug, callback, adminId, null)` می‌زد و **API مرکزی برای purchase حتی پکیج رایگان `pricing_plan_id` می‌خواهد** → خطای «انتخاب طرح».

### اصلاحات
1. **admin.js (`pkgMarket.ask`)** — برای is_free: اگر پلن دارد، پلن‌ها مثل پولی‌ها ساخته و نمایش داده می‌شود (ارزان‌ترین مثل قدیمی پیش‌فرض انتخاب می‌شود)؛ فقط if پلن ندارد mode=free می‌ماند. برای is_free، چک اضافه‌ی checkPurchaseState هم حذف شد (خرید/لایسنس/اشتراک ندارد).
2. **`InteractsWithPackages::startInstall`** — fallback سمت سرور: اگر پلنی انتخاب نشده و پکیج `active_pricing_plans` دارد → ارزان‌ترین پلن خودکار انتخاب و به purchase پاس می‌شود (محافظ همه‌ی مسیرها: مودال تأیید، مودال جزئیات، هر کلاینتی که planId نمی‌فرستد).
3. **`PackageIndex::pollProgress` + `refreshPending`** — بعد از نصبِ موفق، `setTimeout(() => window.location.reload(), 1500)` اجرا می‌شود (Livewire `$this->js()`) تا منوی «پکیج‌ها»ی سایدبار (که سمت سرور از InstalledModule ساخته می‌شود) پکیج تازه‌نصب را نشان دهد. خطای نصب رفرش نمی‌کند.

### E2E (مرورگر واقعی + API فیک پکیج روی 8099)
- مودال نصب پکیج is_free دارای پلن: بخش «انتخاب طرح قیمت‌گذاری» با هر دو پلن نمایش داده شد، ارزان‌ترین (رایگان) پیش‌فرض انتخاب بود، دکمه «نصب پکیج» ✓
- درخواست purchase در لاگ: `"pricing_plan_id": 901` (planId از مودال) ✓
- نصب کامل از UI: دانلود zip واقعی → extract → migrate → permissions → assets → `installed_modules.status=installed, is_active=1` ✓
- پس از پایان نصب: `performance.getEntriesByType('navigation')[0].type === "reload"` → صفحه خودکار رفرش شد و منوی پکیج («رگال لباس» + زیرمنوها) در سایدبار ظاهر شد ✓
- تست جداگانه‌ی fallback سرور (Http::fake، بدون planId): ارزان‌ترین پلن خودکار ارسال شد (id=771) ✓
- assets بازسازی شد (bun run build:admin).

---

## فاز ۴۲ — ممیزی کامل پنل با هر ۱۰ پکیج نصب + زیرساخت تست (php artisan test) + ریشه‌یابی کندی

**گزارش کاربر:** ۱) ارور «A listener indicated an asynchronous response...» چیست؟ ۲) همه پکیج‌ها نصب شوند و تک‌تک صفحات پنل بررسی و کنسول چک و ارورها و موارد کاهنده سرعت رفع شود. ۳) `php artisan test` اجرا و ارورهایش رفع شود.

### ارور «A listener indicated an asynchronous response»
- این ارور از `chrome.runtime.sendMessage` افزونه‌های مرورگر است (انتظار پاسخ async که کانالش بسته شده) — **هیچ کدی در پروژه از API افزونه‌های مرورگر استفاده نمی‌کند** (rg روی public/، resources/، Modules/ صفر نتیجه).
- خزش کامل ۱۳۶ صفحه پنل با مرورگر تمیز Playwright: **صفر** occurrence — قطعاً نویز افزونه مرورگر کاربر (مترجم/آدبلاک/...) و بی‌ضرر برای اپ.

### خزش کامل پنل (agent-browser، هر ۱۰ پکیج نصب)
- ۱۳۶ صفحه (/admin/admin/* + همه صفحات پکیج‌ها + صفحات سئو) با اسنپ‌شات و لاگ کنسول/page-error در مرورگر تمیز.
- دو ارور کنسول واقعی پیدا و رفع شد:
  1. **socials.blade.php** — `x-text="{{ $f }} === 'all' ..."` با `{{ $f }}` بدون کوتیشن → «all/filled/empty is not defined». رفع: کوتیشن‌گذاری رشته‌ای (`'{{ $f }}'`).
  2. **admin.js — apPermissions** — `x-model="q"` بدون تعریف `q` در data کامپوننت → «q is not defined». رفع: `q: ''` به کامپوننت اضافه شد + بازسازی assetها (bun run build:admin — emptyOutDir:false، assetهای قدیمی حفظ شدند).
- نتیجه نهایی: **136/136 صفحه بدون هیچ خطای کنسول** (crawl_panel.sh با پاک‌سازی console بین صفحات).

### باگ seoAudit — پکیج «سئو» اصلاً لود نمی‌شد (404 همه صفحات + منوی ناموجود)
- **ریشه:** نام پوشه نصب‌شده `Modules/seoAudit` (از نام پوشه ZIP) در حالی که namespace کلاس‌ها `Modules\SeoAudit\...` است (فیلد name در module.json). روی لینوکس فایل‌سیستم case-sensitive است → autoloader سفارشی bootstrap/app.php فایل پروایدر را پیدا نمی‌کند → پروایدر/روت/منو/middleware هرگز لود نمی‌شد (DB می‌گفت «نصب است» ولی عملاً مرده بود).
- **رفع ساختاری در نصاب (`PackageInstallerService::extractZip`)**: نام کانونی از خود module.json داخل ZIP خوانده می‌شود؛ استخراج در پوشه موقت و انتقال به `Modules/{canonical}` — نصب‌های قبلی با حالت حروفی متفاوت هم بایگانی و جایگزین می‌شوند. یعنی هر پکیجی (حتی با نام پوشه ZIP نادرست) از این بعد همیشه case-consistent نصب می‌شود؛ آپدیت پکیج در پنل = self-heal روی سرور.
- **سندباکس:** پوشه به `Modules/SeoAudit` تغییر نام کرد + `installed_modules.name` و کلید modules_statuses.json اصلاح شد → پروایدر لود، روت‌ها ثبت، منو نمایش داده شد.
- مایگریشن‌های ماژول (۱۴ جدول سئو) و `seoaudit:install-permissions` (۲۱ پرمیژن + گرنت به نقش ۱) اجرا شد → همه صفحات سئو 200.

### ریشه‌یابی کندی پنل
1. **چک لایسنس بلاک‌کننده (۱۲۰ ثانیه!)** — `verifyLicense` با timeout سراسری `packages.api.timeout=120s` وسط لود صفحات پکیج اجرا می‌شود؛ سرور پکیج‌ها کند/غیرفعال = هنگ تا ۲ دقیقه. رفع: `PackageApiService::request()` پارامتر تایم‌اوت گرفت و `verifyLicense` با **6s total / 3s connect** صدا زده می‌شود (تأیید در لاگ: cURL error 28 → 3002ms).
2. **فیوز API بازار (PackageIndex)** — خطای API کش نمی‌شد → هر بار لود بازار پکیج‌ها دوباره تا 30s منتظر می‌ماند (اندازه‌گیری واقعی: TTFB 2.3s تا **30.5s**). رفع: circuit-breaker ۹۰ ثانیه‌ای — بعد از اولین خطا، لود صفحات بعدی بدون تماس با API و با نمایش پیام خطا (اندازه‌گیری بعد از رفع: **~420ms**).

### زیرساخت تست — `php artisan test` (قبلاً اصلاً tests/ وجود نداشت)
- `tests/TestCase.php` با override `createApplication()` — چون providerهای پکیج vendor/ خودشان را require می‌کنند و `Application::inferBasePath()` مسیر غلط می‌دهد (باگ واقعی محیط تست).
- **۱۵ تست** در ۴ کلاس: `ModuleDirectoryCaseTest` (سلامت نام پوشه/منیفست/پروایدر ماژول‌ها — این کلاس باگ seoAudit را برای همیشه می‌گیرد)، `PackageInstallerCanonicalNameTest` (۴ سناریوی استخراج ZIP با ZIP واقعی)، `ModuleHelpersTest`، `AdminPanelSmokeTest` (لاگین/ForceHttps/ریدایرکت مهمان/صفحه اصلی) — **15 passed (30 assertions)** + migrate phpunit.xml به اسکیمای جدید.
- سه باگ واقعی که تست‌ها پیدا و رفع کردند:
  1. **ForceHttps** — `$_SERVER['REMOTE_ADDR']` خام → در CLI/تست undefined و 500. رفع: `$request->ip()`.
  2. **AppServiceProvider::boot** — `viewComposer()` فقط در non-console ثبت می‌شد → در تست‌ها ویوهای تم با متغیر تعریف‌نشده ($menus) 500 می‌دادند. رفع: ثبت کامپوزرها (lazy) در حالت کنسول هم.
  3. **ThemeServiceProvider::boot** — همان گارد console → همان رفع.

**کامیت:** میزبان → docs/project-analysis

---

## Phase 43 — تست‌های مقیاس و ضد-هک (ادامه تسک ارتقای تست)

**تاریخ:** 2026-10-23 | **کامیت:** 349e8174

### وضعیت نهایی تست‌ها
```
Tests: 194 passed / 3 skipped / 0 failed (531 assertions) — ~6.5 دقیقه
```

### افزودنی‌های این فاز (۴۳ تست جدید + ۲ فیکس تست)
- **Scale/MillionScaleTest (۹):** میلیون محصول/کاربر — درج انبوه ۳۰هزار ردیف + آستانه‌های زمان + اثبات ایندکس
- **Scale/ConcurrentUsersTest (۲):** ۱۰۰۰ درخواست موازی واقعی (curl_multi + سرور چندورکره) + ۵۰ ورود همزمان
- **Security/PenetrationTest (۲۰):** ضد-هک کامل — تله ربات، هانی‌پات، session fixation، mass assignment، OTP قفل، IDOR
- **Extra/PlatformHealthTest (۱۲):** robots/manifest/404/تماس/خبرنامه/captcha
- **tests/Load:** k6 + ab برای بار واقعی ۱۰۰۰ تا ۵۰هزار کاربر

### باگ‌های امنیتی رفع‌شده (IDOR)
1. `GET /profile/addresses/{id}` — نمایش آدرس هر کاربری بدون چک مالکیت → اکنون 404
2. `GET /newsletter/unsubscribe/{id}` — لغو اشتراک قربانی → کنترل مالکیت با email/mobile

### تنظیمات مقیاس (env)
`SCALE_PRODUCTS=30000` `SCALE_USERS=20000` `SCALE_CONCURRENT=1000`


---

## Phase 44 — تب «امنیت» در تنظیمات برنامه‌نویس + 2FA مدیر + بازار قالب‌ها (تغییر نام بازار)

**تاریخ:** 2026-10-24 | **زیربنا:** 252 passed / 0 failed / 3 skipped (فاز A: 223 + فاز B: 29)

### بخش ۱ — تب «امنیت» در تنظیمات برنامه‌نویس (`np.developer.index`)

تب پنجم (کلید Alpine: `security`) بعد از «آپدیت‌کننده» در `developer.blade.php` + اکشن `saveSecurity()` در `Developer.php` (فقط سازنده، اعتبارسنجی محدوده‌ها).

**سرویس مرکزی `app/Services/SecuritySettings.php`** — همه کلیدها در جدول options (ثبت در `config/general.php → non_language_options`) و پیش‌فرض‌ها دقیقاً معادل رفتار قبلی:

| کلید | پیش‌فرض | اثر |
|---|---|---|
| `security_captcha_enabled` | از `config('security.login_captcha')` | کلید اصلی کپچا |
| `security_captcha_admin_login` | on | ورود مدیر (هر دو پنل) |
| `security_captcha_register` | on | ثبت‌نام |
| `security_captcha_customer_login` | off (جدید) | قدم اول ورود مشتری (POST /login → Check_Mobile_Email) |
| `security_captcha_comments` | off (جدید) | پرسش محصول + دیدگاه مقاله + بررسی محصول |
| `security_time_trap_seconds` | 3 | تلهٔ زمان؛ ۰ = خاموش |
| `security_honeypot_enabled` | on | هانی‌پات |
| `security_lockout_enabled` | on | کلید اصلی قفل تلاش |
| `security_lockout_{admin,customer,comments}_{max,minutes}` | 5/15، 5/1، 5/10 | سقف + پنجرهٔ دقیقه‌ای هر نقطه |
| `security_headers_enabled` | on | هدرهای امنیتی |
| `security_hsts_enabled` | off | HSTS (فقط روی HTTPS — RFC 6797) |
| `admin_2fa_enabled` + `admin_2fa_channel` | off + email | ورود دومرحله‌ای مدیر |

**سیم‌کشی رفتار واقعی:**
- `BotDetector` — هانی‌پات و تلهٔ زمان از تنظیمات؛ `LoginThrottle` — سقف/پنجره/کلید اصلی از تنظیمات (قفل تدریجی حفظ شد)؛ `LoginRequest` (ورود مشتری Breeze) — همان؛ `SecurityHeaders` — کلید اصلی + HSTS.
- کپچای ثبت‌نام: `RegisterRequest` + ویو قالب `register.blade.php` گیت‌دار شد.
- ورود مشتری (قالب): `UserController::Check_Mobile_Email` — هانی‌پات/تلهٔ زمان/کپچا + ویو `auth/login.blade.php` (فیلدهای مخفی + `<x-captcha />` گیت‌دار). `CheckPassword` — قفل تلاش مشتری (RateLimiter `front-login:*`).
- دیدگاه‌ها: سرویس جدید **`CommentProtection`** (هانی‌پات→trap + تلهٔ زمان + کپچا + قفل تلاش `comments:*`) وصل شد به `ProductController::comments`، `ArticleController::commentStore` و `ReviewController::store` + فرم‌های قالب (`components/comments.blade.php`، `articles/partials/comments.blade.php`، `products/partials/add-review.blade.php`) با `{!! CommentProtection::hiddenFields() !!}` + کپچای گیت‌دار. (نکته: روت `post.comments` به `BlogController::comments` اشاره می‌کند که وجود ندارد — دیدگاه وبلاگ واقعاً از `ArticleController::commentStore` می‌گذرد.)

### بخش ۲ — ورود دومرحله‌ای مدیر (2FA)

- **`AdminTwoFactorService`** — کد ۶ رقمی (`random_int`، فقط hash در سشن با نمک app.key)، اعتبار ۵ دقیقه، قفل ۵ تلاش در ۵ دقیقه (RateLimiter بر اساس session id)، ارسال مجدد هر ۶۰ ثانیه، `Session::regenerate()` در شروع چالش (ضد fixation) و پس از ورود.
- **صفحهٔ چالش** — `App\Livewire\Admin\Auth\TwoFactorChallenge` + ویو `admin-panel::livewire/admin/auth/two-factor-challenge.blade.php` (شمارش معکوس اعتبار/ارسال با Alpine، نمایش مقصد ماسک‌شده ایمیل/موبایل) — روت `np.two-factor.challenge` (`/admin/{prefix}/two-factor`) داخل گروه GuestAdminPanel.
- **هر دو پنل**: سرویس جدید **`AdminAuthResolver`** (یافتن Admin با username/email + تأیید رمز **بدون login** — چون `SessionGuard::validate()` فقط bool برمی‌گرداند) → در صورت فعال بودن 2FA: `startChallenge()` → ریدایرکت به چالش → پس از تأیید کد، `login()` واقعی + ریدایرکت به مقصد (np.dashboard یا `/admin-old/{prefix}`). اعمال در `Login.php` (Livewire) و `AdminLoginController` (پنل قدیمی؛ چک status ACTIVE با مدل، بدون تغییر ویوها).
- **کانال‌ها**: ایمیل → Mailable موجود `SendMessage` به ایمیل مدیر؛ پیامک → `SmsService` با نوع `VERIFY_CODE` به `option('admin_mobile_number')` (تنظیمات پیامک). اگر مقصد کانال انتخابی خالی باشد پیام راهنمای قابل‌اقدام نمایش داده می‌شود.
- خروج از هر دو پنل وضعیت چالش را پاک می‌کند؛ `Login::mount` سشنِ در انتظار را به چالش هدایت می‌کند.

### بخش ۳ — «بازار پکیج‌ها» → «بازار» + فروش قالب‌ها (Templates)

**تغییر نام** (منو، Title، h1، برچسب‌ها — پنل جدید و قدیمی): گروه منو «بازار»، آیتم «پکیج‌ها و قالب‌ها»، برچسب دسترسی `packages` → «بازار (پکیج و قالب)». روت‌ها/نام‌های روت بدون تغییر (سازگاری کامل).

**سرویس API (`PackageApiService`)** — ۷ متد جدید طبق مستند OpShop Updater §5.2: `listTemplates` / `getTemplate` / `createTemplatePurchase(normal|exclusive)` / `verifyTemplatePayment` / `checkTemplateUpdate` / `createTemplateDownloadUrl` / `getTemplateDownloadUrl` (+`downloadTemplate` سرور-سمت).

**دیتابیس** (مایگریشن `2026_10_24_100001_theme_market_for_bazaar`): ستون `type` روی `package_purchases` (package|template) + جدول `installed_themes` (slug/name/version/purchase_type/transaction_id/status/last_error). مدل **`InstalledTheme`** با `markPending/markAsInstalled/markAsFailed/isActive`.

**نصب (`ThemeInstallerService` + `InstallThemeJob`)** — الگوی امن PackageInstallerService/ThemeIndex: دانلود (توکن یک‌بارمصرف یا download-url) → **تأیید SHA-256** → اسکن **Zip-Slip** → تشخیص نام قالب از `config/general.php` داخل ZIP → بکاپ نسخهٔ قبلی به `themes/.backups/<name>_<time>` → استخراج به `themes/<Name>` → ثبت نسخه → dump-autoload + vendor:publish + shop:link (قابل خاموش‌کردن با `packages.themes.refresh_after_install` برای تست). مسیر themes با `packages.themes.path` قابل بازنویسی است. `activate()` همان زنجیرهٔ symlink+env+publish+shop:link؛ `uninstall()` قالب فعال را منع می‌کند و دارایی‌های public را با گارد مسیر پاک می‌کند.

**جریان خرید**: مودال جزئیات قالب (Livewire — `$tDetail*`) با کارت‌های قیمت عادی/اختصاصی + تأیید دومرحله‌ای خرید اختصاصی (هشدار «حذف از بازار») → خرید پولی: رکورد `PackagePurchase(type=template)` + ریدایرکت `payment_url` → درگاه → callback پنل با `?type=template` → **`handleTemplateCallback`** (شاخهٔ جدید در `PackagePaymentCallback`) → `verifyTemplatePayment` → `InstallThemeJob`. خرید مجددهٔ normal / رایگان → همان لحظه `download_token` → نصب فوری. نصب‌ها با `wire:poll.4s="refreshPendingTemplates"` تا toast نتیجه دنبال می‌شوند.

**UI بازار**: کنترل تب «پکیج‌ها / قالب‌ها» در سربرگ (`?type=template` فارندلی)، گرید کارت‌های قالب (کاور/placeholder گرادیانی، نشان فعال/نصب‌شده/خریداری‌شده، مُهر «در حال نصب»، hover اورلی جزئیات، فروش‌شمار)، جستجوی اختصاصی، صفحه‌بندی، خطای API مستقل (همان فیوز ۹۰ ثانیه‌ای)، مودال جزئیات با نسخه‌ها/حجم/اکشن‌های فعال‌سازی-به‌روزرسانی-حذف.

**رفع باگ‌های کشف‌شده در این فاز:**
1. `SessionGuard::validate()` فقط bool برمی‌گرداند (نه مدل) → ساخت `AdminAuthResolver` با provider مستقیم.
2. تداخل تریت (`api()` در دو تریت) → حذف از تریت قالب.
3. regex درایو ویندوز در اسکن Zip-Slip (`/[\\\/]/` → «Unknown modifier») → delimiter `#` + escape درست.
4. `moveDirectory` بدون پدر موجود شکست می‌خورد → mkdir ریشهٔ themes قبل از انتقال.
5. `type`/`version` خارج از `$fillable`/NOT NULL → افزودن به fillable و ایمن‌سازی مقادیر.
6. quirk ویرایشی: `ValidationException::errors('key')` قابل‌اعتماد نیست → `errors()['key']`.

### تست‌های جدید (۴۰ تست)
- `tests/Feature/Security/SecuritySettingsTest.php` (۱۷) — ذخیرهٔ تب (فقط سازنده)، گیت‌های کپچا/هانی‌پات/تلهٔ زمان/قفل/هدرها/HSTS، `CommentProtection`، ورود مشتری.
- `tests/Feature/Security/AdminTwoFactorTest.php` (۹) — جریان کامل ایمیل، قفل ۵ تلاش، throttle ارسال مجدد ۶۰ ثانیه، انقضا، کانال پیامک بدون شماره، هر دو کانال، ریدایرکت پنل قدیمی.
- `tests/Feature/Extra/ThemeMarketTest.php` (۱۴) — قرارداد API (assertSentInOrder)، نصب ZIP واقعی + Zip-Slip + هش + بکاپ، رندر دو تب بازار، خرید پولی/مجدد/اختصاصی، callback موفق/ناموفق، حذف قالب.

**کامیت:** میزبان → docs/project-analysis


---

## Phase 45 — بازطراحی تب امنیت + سرویس واحد بازار + تب «همه» + رفع نصب‌کننده

**تاریخ:** 2026-10-24 | **زیربنا:** فاز A: 224 passed / 3 skipped (697 assertions) + فاز B: 29 passed

### بخش ۱ — بازطراحی مدرن تب «امنیت» تنظیمات برنامه‌نویس (درخواست کاربر: ظاهر چک‌باکس/رادیو پیش‌فرض خوب نیست)

- **کامپوننت جدید `x-admin-panel::toggle`** (`resources/views/admin-panel/components/toggle.blade.php`): سوییچ iOS-سبک با ورودی checkbox واقعی (`sr-only` + `peer-checked`) — سازگاری کامل با `wire:model`، بازخورد بصری «همان لحظه» بدون رفت‌وبرگشت سرور، حرکت دستگیره با `inset-inline-start` (کاملاً RTL-ایمن و انیمیشن springی) + حالت focus و disabled و سایز `sm`.
- **تب امنیت**: هر ۹ چک‌باکس → سوییچ مدرن؛ کلیدهای اصلی با کارت گرادیانی برند + چیپ «کلید اصلی»؛ **با خاموش‌کردن کلید اصلی (کپچا/قفل/2FA) زیرتنظیمات همان لحظه کم‌رنگ و غیرقابل‌کلیک می‌شوند** (Alpine `x-data` + `x-on:change` روی سوییچ).
- **کانال 2FA**: سه کارت رادیویی (ایمیل/پیامک/هر دو) با آیکون + نشان تیک انیمیشنی (`peer-checked:scale-100`) و highlight کارت انتخاب‌شده.
- جدول قفل تلاش‌ها و کارت‌های آماری تمیز شد؛ **۴ چک‌باکس تب‌های دیگر (کرون/حالت توسعه/دیباگ‌بار/آپدیت قدیمی) هم برای یکدستی سوییچ شدند**؛ لیبل‌های `aria-label` برای همه کنترل‌ها.

### بخش ۲ — سرویس واحد بازار: پکیج + قالب در یک کلاس (درخواست کاربر)

- `ThemeInstallerService` **حذف شد**؛ همه‌ی متدهایش به `PackageInstallerService` منتقل شدند (سرویس‌دهنده یکی است — پس نصب هم یکی):
  - `installTemplate()` / `themesPath()` / `activateTheme()` / `uninstallTheme()` + internals (`downloadTemplateZip`، `extractThemeZip` با اسکن Zip-Slip، `detectThemeRootDir`، `readThemeVersion`، `refreshThemeFramework`).
- ارجاع‌ها: `InstallThemeJob` → `installTemplate()`؛ `InteractsWithTemplates` → `activateTheme/uninstallTheme`؛ `ThemeMarketTest` → سازندهٔ دوآرگومانی.
- لایه‌ی API از قبل واحد بود (`PackageApiService` — پکیج + قالب با هم).

### بخش ۳ — تب «همه» در بازار (درخواست کاربر: لندینگِ قالب‌ها + پکیج‌ها با سویچ)

- `$type` سه‌حالته شد: `''` = **همه (پیش‌فرض لندینگ)** | `template` | `package` — لینک‌های قدیمی `?type=template` سر جایشان.
- **لندینگ «همه»**: اول بخش «قالب‌ها» (سربرگ با آیکون + شمارنده + «مشاهده همه قالب‌ها»)، بعد بخش «پکیج‌ها» — هر بخش فقط ۶ کارت اول (ضد سردرگمی) + **جستجوی واحد** که هم‌زمان قالب و پکیج را می‌گردد.
- کارت‌های آماری/فیلتر پیشرفته/صفحه‌بندی فقط در تب اختصاصی هر بخش؛ خطا و نوار «در حال نصب» هر بخش مستقل در تب «همه» هم دیده می‌شود.
- `loadMarket()`: در حالت همه هر دو لیست را (با کش/فیوز مشترک) می‌خواند؛ هلپرهای `showsTemplates()/showsPackages()/showsAll()` برای گیت‌های ویو.

### بخش ۴ — رفع نصب‌کننده: «اول وضعیت نصب، بعد هرچیز دیگر» (باگ کاربر: installed نیست + env هست → خطای اتصال دیتابیس)

- **`AppServiceProvider::boot()`** بازنویسی شد: اگر `storage/installed` نیست (فارغ از وجود/نبود .env):
  1. درایورهای `session/cache/queue` روی `file/sync` قفل می‌شوند → هیچ مسیری (حتی خود صفحات /install با StartSession) به دیتابیس دست نمی‌زند؛
  2. درخواست‌های خارج از `/install` با **`HttpResponseException`** دقیقاً در بوت قطع و به `/install` ریدایرکت می‌شوند (قبلاً `redirect()->send()` پاسخ را می‌فرستاد ولی اجرا ادامه می‌یافت)؛
  3. `loadTheme()`/کامپوزرها فقط بعد از نصب بوت می‌شوند.
- **`BlockBots`**: گارد `application_installed()` (تلهٔ ربات دیتابیسی تا بعد از نصب خاموش است).
- **`MyEnvironmentManager::saveFileWizard`**: اگر .env موجود باشد با اطلاعات جدید ویزارد **بروزرسانی** می‌شود و **APP_KEY همیشه تازه تولید و جایگزین می‌شود** (حتی اگر از قبل هست) — سشن/کوکی‌های نصب قبلی باطل می‌شوند. اگر .env نیست، از فرم ساخته می‌شود (رفتار قبلی حفظ شد).

### تست دستی نصب (سرور محلی)
- installed نیست + env سالم → `GET /` 302 به `/install` (200) ✓
- **installed نیست + env با رمز اشتباه دیتابیس → 302 به `/install` بدون هیچ خطای دیتابیس؛ صفحات wizard/requirements 200** ✓ (باگ کاربر)
- env اصلاً نیست → bootstrap از نمونه می‌سازد → 302 به `/install` (200) ✓
- ویزارد: بروزرسانی env موجود با DB جدید + APP_KEY جدید + حفظ بقیه کلیدها (ADMIN_ROUTE_PREFIX و…) ✓
- بعد از نصب: `/` 200 و `/install` 404 (قفل نصب‌کننده) ✓

### تست‌ها
- `ThemeMarketTest` → ۱۵ تست (تست جدید `bazaar_all_tab_renders_templates_first_then_packages` + تب پکیج‌ها حالا `?type=package`).
- فاز A: **224 passed / 3 skipped** | فاز B (Scale): **29 passed** — همه سبز.

### نکتهٔ کشف‌شده
- در ادغام سرویس، `use Throwable;` از imports جا مانده بود → `catch (Throwable)` در namespace بدون ایمپورت هرگز match نمی‌شد (رد شدن بی‌صدا از markAsFailed) — با تست zip-slip کشف و رفع شد.

**کامیت:** میزبان → docs/project-analysis

---

## Phase 46 — رفع نهایی باگ نصب‌کننده: «ماژول‌ها قبل از چکِ نصب بوت نمی‌شوند»

**تاریخ:** 2026-10-25 | **باگ کاربر:** فایل installed نیست ولی به‌جای ریدایرکت به /install خطای ۵۰۰: `SQLSTATE[HY000] [1045] Access denied ... SQL: select * from installed_modules where slug = rack-widget limit 1`

### ریشه‌یابی (چرا فاز ۴۵ کافی نبود)
- ترتیب بوت لاراول: **اول پرووایدرهای پکیج‌ها (منظوم `LaravelModulesServiceProvider`) و بعد پرووایدرهای خودِ اپ** (`config/app.php`: Nwidart قبل از `AppServiceProvider` لیست شده).
- nwidart در همان فاز register، پرووایدرهای همه‌ی ماژول‌های فعال (`modules_statuses.json`) را از طریق `ModuleManifest::getProviders()` + `ProviderRepository->load()` رجیستر می‌کند.
- یعنی پرووایدر ماژول RackWidget (که در `boot()` خودش وضعیت نصبش را از دیتابیس می‌پرسد: `InstalledModule::where('slug','rack-widget')->first()`) **قبل از** «چکِ installed» در `AppServiceProvider::boot()` اجرا می‌شود → با اعتبارنامه‌ی ناقص دیتابیس، همان اولین کوئری ۵۰۰ می‌دهد و ریدایرکت به /install هرگز اتفاق نمی‌افتد.
- بازتولید محلی با ماژول آزمایشی (provider که در boot از `InstalledModule` کوئری می‌گیرد + `modules_statuses.json` فعال + حذف نشان installed): کل بوت می‌مُرد — دقیقاً همان رفتار کاربر.

### راه‌حل (فاز ۴۶ — گیتِ ماژول در بوت‌استرپ)
- **`bootstrap/app.php`** — قبل از رجیستر شدن هر پرووایدری، اگر `storage/installed` نیست:
  ```php
  $app->resolving(\Nwidart\Modules\ModuleManifest::class, function ($manifest): void {
      $manifest->paths = \Illuminate\Support\Collection::empty();
  });
  ```
  مسیرهای اسکن ماژول‌ها خالی می‌شوند → هیچ `module.json` خوانده نمی‌شود، هیچ پرووایدر ماژولی رجیستر/بوت نمی‌شود و هیچ فایل helperای از `Modules/` include نمی‌شود.
- چرا `resolving()`؟ singleton `ModuleManifest` بعداً توسط خودِ nwidart تعریف می‌شود؛ hook ما در اولین resolve (وسط register پکیج، قبل از `getProviders()`) اجرا می‌شود — بدون دست‌زدن به vendor.
- کش‌ها سد راه نیستند: `ProviderRepository::shouldRecompile` لیست پرووایدرها را مقایسه می‌کند و manifest را خودش از نو می‌سازد؛ بعد از نصب (بودن نشان) ماژول‌ها دوباره لود می‌شوند.
- گیت هم HTTP و هم console است (نصب‌نشده = هیچ مایگریشن/کش‌سازی ماژول هم نباید بیفتد).
- نکته‌ی ظریف: مسیر نشان با `__DIR__.'/../storage/installed'` خوانده می‌شود نه `$__installBasePath` (که در ابتدای همان فایل `unset` می‌شود؛ ارجاع بعدی warning و خراب‌شدن هدرِ ریدایرکت می‌داد — در تست دستی کشف شد).

### تست دستی کامل (سرور محلی — سناریوی دقیق کاربر)
- installed نیست + .env با `DB_DATABASE=`/`DB_USERNAME=` خالی + ماژول فعال موجود → `GET /` = **302 تمیز به /install** (بدون هیچ کوئری دیتابیس؛ قبلاً ۵۰۰) ✓
- `GET /admin2old` هم 302 به /install ✓
- صفحات `/install`، wizard، requirements، database همه **200** با env خراب ✓
- ویزارد: POST با اعتبارنامه‌ی درست → env **بروزرسانی** شد (کلیدهای DB جدید) + **APP_KEY تازه** تولید شد + بقیه کلیدها حفظ شدند → ریدایرکت به مرحله‌ی database ✓
- پس از برگرداندن نشان installed و حذف ماژول آزمایشی: `GET /` = 200 و `GET /install` = 404 (قفل نصب‌کننده) ✓

### تست‌ها (بدون تغییر مورد انتظار)
- AdminPanelSmokeTest: 5 ✓ | Unit: 9 ✓ / 1 skipped | Catalog: 19 ✓ | Security: 62 ✓ — همه سبز.

**کامیت:** میزبان → docs/project-analysis

---

## Phase 47 — سخت‌سازی نهایی مسیر نصب: ریدایرکتِ پیش از هر پرووایدر + خاموشی کامل دیتابیس روی /install

**تاریخ:** 2026-10-25 | **پیگیری باگ کاربر (فاز ۴۶ کافی نبود):** روی سرور، `/` درست به /install ریدایرکت می‌شد ولی **خود صفحات /install ۵۰۰ می‌دادند**.

### ریشه‌یابی (با شنود SQL روی مسیر نصب)
با `DB::listen` موقت روی حالت نصب‌نشده، سه کوئری زنده پیدا شد:
1. `select ... from redirects` — میدلور **سراسری** `RedirectMiddleware` (گارد قبلی‌اش فقط «وجود .env» بود، نه وضعیت نصب)؛
2. `insert into pulse_entries` و `pulse_aggregates` — **Laravel Pulse** که recoderهایش در boot پکیج (قبل از AppServiceProvider) فعال می‌شوند و در پایان درخواست درج می‌کنند.

پرووایدرهای ماژول‌ها (فاز ۴۶) درست بسته شده بودند — این دو لایه باقی‌مانده بودند.

### سه لایه‌ی دفاعی جدید
- **لایه ۱ — `bootstrap/app.php`: ریدایرکتِ پیش از هر پرووایدر.** اگر `storage/installed` نیست و درخواست HTTP به‌جز `/install*` است، بلافاصله `header('Location: /install')` + `exit` — یعنی «چک نصب، قبل از پکیج‌ها» به‌صورت واقعی: حتی یک پرووایدر (ماژول/پکیج ثالث/میدلور) بوت نمی‌شود. کنسول مستثنا (artisan/test سالم بمانند).
- **لایه ۲ — `bootstrap/app.php`: خاموشی کامل Pulse در حالت نصب‌نشده** با مقداردهی `$_ENV['PULSE_ENABLED']='false'` پیش از لود کانفیگ (چون boot پکیج Pulse قبل از AppServiceProvider است؛ تنظیم کانفیگ در AppServiceProvider دیر می‌شد).
- **لایه ۳ — `RedirectMiddleware`: گارد `application_installed()`** جایگزین گارد ضعیف «وجود .env» — در حالت نصب‌نشده این میدلور سراسری صفر کوئری می‌زند.

### تست دستی (سرور محلی — با ماژول آزمایشی فعال + حذف نشان installed)
- `.env` با اعتبارنامه‌ی خالی (سناریوی کاربر): `GET /` و `GET /admin2old` = **302 → /install** | `GET /install`، wizard، requirements، database = **200** | **صفر کوئری SQL در کل لاگ** ✓
- `.env` با دیتابیس خالی (وصل ولی بدون جدول): همه‌ی بالا 200/302 و صفر خطا ✓
- کوکی سشن قدیمی/خراب: /install = 200 ✓
- بعد از برگرداندن نشان: `/` = 200 و `/install` = 404 ✓

### تست‌ها
Smoke+Unit: 14 ✓ / 1 skip | Catalog: 19 ✓ | Security: 62 ✓ | Extra: 27 ✓ | Auth: 24 ✓ — همه سبز.

### نکته عملیاتی برای سرور کاربر
- بعد از pull: `php artisan optimize:clear` و ترجیحاً ری‌استارت PHP-FPM/OpCache (اگر opcache با validate_timestamps=0 تنظیم شده باشد، کد جدید بدون ری‌استارت اعمال نمی‌شود).
- حذف پوشه Modules لازم نیست؛ اما اگر دیتابیس را از نو ساخته‌اند، بعد از نصب باید پکیج‌ها را از بازار دوباره نصب کرد تا مایگریشن‌های خودشان (جداولشان) اجرا شود.

**کامیت:** میزبان → docs/project-analysis

---

## Phase 48 — تحلیل لاگ سرور کاربر + توقف graceful صف و زمان‌بند پیش از نصب

**تاریخ:** 2026-10-25 | **ورودی کاربر:** لاگ `laravel.log` سرور (سه رکورد) برای پیگیری «فاز ۴۶/۴۷ هنوز اعمال نشده».

### تحلیل دقیق لاگ (سه رکورد، دو ماهیت متفاوت)
1. **سه رکورد `production.ERROR`** (فاصله ۴ ثانیه‌ای): ورکر صف — `select * from jobs ... FOR UPDATE SKIP LOCKED` → `Table 'owqdlxcv_testShop.jobs' doesn't exist`. یعنی یک دیمن `queue:work` روی سرور روشن است؛ کانفیگ دیتابیس را در لحظه‌ی استارت در حافظه دارد (`owqdlxcv_testShop` — اتصال موفق، جدول ندارد) و هر ۳~۴ ثانیه لاگ را شلوغ می‌کند. به درخواست وب ربطی ندارد.
2. **یک رکورد `local.ERROR`** (مهم!): stacktrace کامل نشان می‌دهد:
   - `Modules/RackWidget/app/Services/LicenseGuard.php(27)` → `LicenseGuard::check('rack-widget')` از `RackWidgetServiceProvider.php(58)` در `boot()`؛
   - زنجیره: `BootProviders->bootstrap()` → `Kernel->bootstrapWith()` → `public/index.php(53)` — یعنی **درخواست وب** و پرووایدر ماژول **رجیستر شده و تا فاز boot رفته**؛
   - اتصال دیتابیس درخواست وب: `Database: ''` + `user: ''` (اعتبارنامه‌ی خالی — برخلاف ورکر که کانفیگ قدیمیِ در حافظه داشت) → `Access denied for user ''@'localhost'` → ۵۰۰.
   - نتیجه: هر جا پرووایدر ماژولی «رجیستر و بوت» شده، **کدِ در حال اجرا روی سرور فاز ۴۶/۴۷ نیست** — با گیتِ فعلی bootstrap/app.php این زنجیره ناممکن است (چه درخواست `/` باشد که لایه‌ی ۱ با ۳۰۲ قطعش می‌کند، چه `/install` که لایه‌ی ۲ مسیرهای اسکن ModuleManifest را خالی می‌کند). دو نشانه‌ی فرعی هم همین را تأیید می‌کنند: کانال لاگ وب `local` ولی ورکر `production` (env/‏.env بعد از استارت ورکر عوض شده) و اعتبارنامه‌ی متفاوت وب/ورکر (ورکر کانفیگ قدیمی درون‌حافظه‌ای دارد).
   - پس علت، **استکه بودن کد روی سرور** است: pull نشده، یا OpCache با `validate_timestamps=0` فایل قدیمی را سرو می‌کند، یا `bootstrap/cache/config.php` قدیمی مانده.

### راه‌حل عملیاتی (چک‌لیست سرور کاربر)
1. `git log --oneline -1` روی سرور → باید آخرین کامیت فاز ۴۸ باشد؛ اگر نبود: `git pull` (اگر تداخل بود، تغییرات محلی سرور ذخیره/دور ریخته شود) و سپس `php artisan optimize:clear`.
2. مطمئن شوید `bootstrap/cache/config.php` وجود ندارد (کانفیگ کش‌شده با اعتبارنامه‌ی قدیمی/خالی = همین خطای «Access denied با user خالی» را می‌سازد).
3. OpCache ریست شود (cPanel: MultiPHP Manager → Reset OPcache، یا ری‌استارت PHP-FPM؛ نشانه‌ی کلاسیک: artisan درست کار می‌کند ولی وب کد قدیمی اجرا می‌کند).
4. **ورکر صف را تا پایان نصب خاموش کنید**؛ بعد از نصب دوباره روشن کنید.
5. تست نهایی: `GET /` باید 302 به /install بدهد (بدون هیچ خطای دیتابیس در لاگ).

### بهبود کد (لایه ۴ — فاز ۴۸)
- **`bootstrap/app.php`** — داخل گیتِ نصب‌نشده: اگر CLI است و دستور جاری `queue:work` / `queue:listen` / `schedule:run` باشد، با پیام شفاف فارسی روی STDERR و کد خروج ۰ خارج می‌شویم — بدون بوت هیچ پرووایدری و بدون اسپمِ خطای «Table 'jobs' doesn't exist» در laravel.log. بعد از نصب رفتار عادی است. (دستورهای موردنیاز نصب دست‌نخورده‌اند.)

### تست‌ها (دو فاز)
- فاز ۱ (Smoke/Auth/Cart/Catalog/Checkout/Extra): **115 ✓** (321 assertions)
- فاز ۲ (Order/Payment/Performance/Scale/Security/Unit): **140 ✓ / 1 skip** (451 assertions)
- تست دستی گیت: با حذف نشان installed → `queue:work` و `schedule:run` با پیام فارسی و exit=0 متوقف؛ با نشان → رفتار عادی ✓ | `php -l` سبز ✓
- نکته جانبی (خارج از دامنه این فاز): `route:list` به‌خاطر `abort(403)` در سازنده‌ی `MediaController` هنگام جمع‌آوری روت‌ها HttpException می‌دهد — رفتار قدیمی و بی‌ربط به نصب‌کننده.

**کامیت:** میزبان → docs/project-analysis
---

## Phase 49 — قالب دوم فروشگاه «سانیار» — فاز A: اسکلت و زیرساخت

**تاریخ:** 2026-10-24 | **برنچ:** docs/project-analysis | **تست (پس از merge با فازهای ۴۴–۴۸ میزبان و اجرای مایگریشن installed_themes):** ۲۵۳ passed / 0 failed / 3 skipped (فاز A: 224 + فاز B: 29 — 751 assertions)؛ تم سانیار در مخزن مستقل themes/Saniar کامیت شد (commit 76217fa)

### معماری قالب‌ها (خروجی مطالعه عمیق)
- themes/<Name> با PSR-4 `Themes\` → انتخاب تم از env `CURRENT_THEME` → ثبت داینامیک `Themes\<Name>\src\ThemeServiceProvider` در `AppServiceProvider::loadTheme()`؛ viewها با namespace `front::`؛ assetها با symlink `public/themes/<Name>` → `src/resources/assets`؛ seed اولیه ویجت‌ها با `php artisan theme:config` (run_theme_config → widget_seeder مال تم فعال).
- **نکته توزیع:** themes/.gitignore فقط WeblakShop را در گیت میزبان نگه می‌دارد (تم پیش‌فرض محصول)؛ تم‌های دیگر مثل ماژول‌ها از پنل با ZIP نصب می‌شوند → **قالب سانیار مخزن گیت مستقل گرفت** (themes/Saniar/.git، برنچ master).

### تحویل‌شده‌های فاز A
1. **اسکلت کامل** `themes/Saniar`: کپی ساختار WeblakShop (۳۰ کنترلر + ۱۲ کنترلر sellers + Requests + routes/web.php با ۱۷۵ روت + helpers + lang) با namespace `Themes\Saniar\src` — بدون هیچ تغییری در WeblakShop یا کد میزبان.
2. **Assets سانیار** (از github codivon/saniar-html-template): Bootstrap 5 RTL + Bootstrap Icons + Swiper + Waves + Timer + Touchspin + jQuery + IranYekan؛ ۱۵MB در src/resources/assets + ۳۷ صفحه HTML خام در reference/ برای فازهای بعد.
3. **config/general.php سانیار**: theme_name/asset_path/links/demo/socials (آیکون‌های bootstrap-icons)/sliderGroups/bannerGroups/linkGroups/settings/pages/errors مطابق قرارداد میزبان.
4. **Layout اصلی داینامیک** (layouts/master.blade.php): هدر سانیار (لوگو از option، جستجو به front.products.index، سبد با شمارنده واقعی، auth dropdown داینامیک)، مگامنو از Menu+Category (تب دسته‌بندی محصولات + منوهای پنل + تخفیف‌ها + فروشندگان)، فوتر از linkGroups+socials+enamad+ساماندهی، منوی موبایل offcanvas درختی، سبد offcanvas با اقلام واقعی، floating contact از optionها، GTM/Pixel، نوار سریع ادمین، بنر بالای هدر — همه داینامیک.
5. **صفحه اصلی + سیستم ویجت**: index.blade با حلقه ویجت (سازگار با WidgetBuilder پنل + WidgetRegistry ماژول‌ها) و ۱۱ ویجت سانیار: main-slider (Swiper اصلی + اسلایدر عمودی شگفت‌انگیز با moment_products — افزوده به get_widget)، products-slider (پرفروش/جدید/تخفیف‌دار)، بنرها (۱/۲/۴تایی)، دسته‌بندی‌ها، بلاگ، لوگوی همکاران، خدمات، FAQ آکاردئونی.
6. **widget_seeder سانیار**: چیدمان پیش‌فرض ۱۱ ویجت خانه + ۴ ویجت صفحه مقالات؛ رفع باگ کلیدهای تکراری (widget_options UNIQUE(widget_id,key)) با بازنویسی آپشن‌ها بدون merge.
7. **404 اختصاصی**: fallback روت تم به config('front.errors.404) تغییر کرد → صفحه 404 برند‌دار سانیار (noindex).
8. **SEO پایه در layout**: canonical، OG، Twitter Card، robots، JSON-LD WebSite+SearchAction در index، noindex در 404.

### باگ‌های کشف‌شده (لاتنت میزبان — بدون تغییر کد میزبان، فقط ریشه‌یابی و ثبت)
1. `front.blogs.index` تعریف‌نشده (روت واقعی: front.blog.**index** تک‌عدد) — در partials/menu/static-menus تم WeblakShop هم استفاده شده → اگر ادمین منوی static نوع posts بسازد، ویترین WeblakShop هم 500 می‌دهد (لاتنت).
2. `Category::getLinkAttribute()` برای postcat به `front.posts.category` اشاره می‌کند که **در هیچ فایل روتی تعریف نشده** → هر `->link` روی دسته مقاله 500 می‌دهد (لاتنت در WeblakShop static-menus). در سانیار از `route('front.articles.index', ['cat' => slug])` استفاده شد.
3. `php artisan dump-autoload` سفارشی (Illuminate Composer) در این سندباکس classmap را واقعا بازتولید نمی‌کند — با composer.phar مستقیم درست شد.

### تصمیم‌های معماری (مطابق خواسته کاربر)
- CURRENT_THEME روی WeblakShop می‌ماند (تست‌ها سبز)؛ سانیار از پنل np.themes قابل فعال‌سازی است و برای توسعه با flip موقت .env تست شد.
- تبدیل Livewire 4 + SPA (wire:navigate) طبق خواسته کاربر «در آخر» (فاز I) انجام می‌شود؛ سانیار فعلاً Blade خالص مثل ساختار تم‌ها.

### فازبندی کل پروژه قالب سانیار (۹ فاز)
A=۴۹ اسکلت ✓ | B=۵۰ تکمیل ویجت‌های خانه ✓ | C=۵۱ کاتالوگ (category/search/product/compare/favorite/brand/specials) | D=۵۲ سبد+چک‌اوت+پرداخت+فاکتور | E=۵۳ احراز هویت | F=۵۴ پنل کاربر | G=۵۵ محتوا (بلاگ/تماس/درباره/FAQ/CMS) | H=۵۶ فروشندگان (ویترین+auth+پنل) | I=۵۷ Livewire4+SPA+SEO نهایی+تست اختصاصی تم.

## Phase 50 — قالب سانیار فاز B: تکمیل ویجت‌های خانه

**تاریخ:** 2026-10-25 | **برنچ میزبان:** docs/project-analysis | **تم:** themes/Saniar (مخزن مستقل — کامیت 16ab88f، پوش به برنچ `theme-saniar` همان ریپو) | **تست:** فاز A: 224 passed / 3 skipped (678 assertions) + فاز B (Scale): 29 passed (73 assertions) — همه سبز؛ route:list همان استثنای شناخته‌شده فاز ۴۸ (MediaController abort(403)) بدون ارتباط با این فاز.

### تحویل‌شده‌های فاز B
1. **ویجت «پیشنهاد شگفت‌انگیز» (products-moment-block)** — بازسازی کامل بخش amazing در index.html مرجع: هدر سفید + اسلایدر fade اصلی (تصویر، تایمر، عنوان، درصد تخفیف، توضیح کوتاه ۱۷۰ کاراکتری، تا ۴ مشخصه ویژه، قیمت + قیمت خط‌خورده + دکمه خرید) + ستون thumbs پیوندی (کارت‌های کوچک با تصویر/عنوان/عنوان لاتین/قیمت) که با کلیک، اسلاید اصلی را جابه‌جا می‌کند (رفتار thumbs سوایپر مرجع).
2. **تایمر شمارش معکوس داینامیک**: گزینه جدید `timer_date` (input type=date) در کانفیگ HomeWidgets سانیار + قاعده nullable|date — از ویجت‌ساز پنل تنظیم می‌شود؛ خالی = بدون تایمر؛ با رسیدن تاریخ، timer.js خودش element را hide می‌کند. پلاگین timer قالب (ثانیه قرمز + دقیقه/ساعت/روز) عین مرجع رندر می‌شود.
3. **ویجت «کادر محصولات با پس‌زمینه» (products-colorful-block)** — بلوک رنگی سانیار: پس‌زمینه از `block_color` (پیش‌فرض #ef394e)، ستون تبلیغاتی از `image` + دکمه مشاهده همه از `link/link_title`، اسلایدر محصولات با کارت سانیار؛ گزینه `title` به کانفیگ اضافه شد (در WeblakShop لاتنت بود — کانفیگش title نداشت ولی view می‌خواند).
4. **ویجت «اسلایدر تمام صفحه» (fullscreen-slider)** — دو سوایپر جدا (گروه fullscreen_slider دسکتاپ / mobile_sliders موبایل) با rewind+fade، pagination و ناوبری scoped؛ اگر اسلایدر موبایل تعریف نشده باشد اسلایدر دسکتاپ در همه سایزها می‌نشیند (بدون بخش خالی موبایل)؛ زیرنویس title/description اسلایدر روی تصویر.
5. **رفع باگ قالب اصلی (در app.js تم):** `new Swiper(".product-slider-swiper")` و `.free-mode` فقط «اولین» نمونه را init می‌کردند — با چیدمان پیش‌فرض (۲ بلوک products-default + بلوک رنگی) یعنی ۲ اسلایدر مرده! حالا querySelectorAll+forEach؛ ناوبری/صفحه‌بندی فقط از داخل همان کانتینر؛ amazing هم چند-نمونه‌ای شد (scope با closest) و بدون ستون thumbs هم کار می‌کند؛ loop فقط با >۱ اسلاید؛ fullscreen از rewind استفاده می‌کند (۲ اسلاید بدون هشدار Swiper).
6. **helpers سانیار:** case شگفت‌انگیز جدا شد و specifications ویژه (wherePivot special) + description را eager-load می‌کند؛ بقیه بلوک‌ها سبک ماندند. درصد تخفیف fallback از ستون `discount` خودِ Price وقتی regularPrice صفر است (دمو-داده‌ها) — در product-card هم اعمال شد.
7. **widgets.css جدید** (استایل تمام‌صفحه/بلوک رنگی/اسکرول‌بار نازک thumbs) + لینک در master بعد از responsive.css.

### باگ‌های کشف‌شده (لاتنت میزبان — فقط ریشه‌یابی و ثبت)
1. **`scopeDetectLang` سخت‌گیر است**: `where('lang', locale)` — اسلایدرهای `lang=NULL` (همه رکوردهای این DB و سرور کاربر طبق فاز ۳۰) را حذف می‌کند. case فول‌اسکرین تم WeblakShop هم detectLang دارد → ویجت fullscreen اسلایدر تم پیش‌فرض روی دیتای واقعی هیچ‌وقت چیزی نشان نمی‌دهد (لاتنت). در سانیار به «lang=NULL یا locale» اصلاح شد؛ کد میزبان دست نخورد.
2. **قیمت‌های دمو:** regular_price=0 + discount=3 (ستون discount همان درصد است — سند: مایگریشن 2021_08_26) → فرمول درصد از قیمت‌ها ۰ می‌شود؛ fallback از ستون discount افزوده شد.
3. `route:list` روی سرور هم همان استثنای MediaController را می‌دهد (فاز ۴۸) — پیش‌روی با `git log -1` طبق دستورالعمل قبلی.

### تصمیم‌های این فاز
- **Remote مخزن تم:** توکن گیت‌هاب اجازه ساخت ریپوی جدید نمی‌دهد (403 Resource not accessible) → تم به برنچ اختصاصی **`theme-saniar`** در همان ریپو shop-pro-laravel-13 پوش شد (مستقل از docs/project-analysis؛ تاریخچه تم دست‌نخورده). اگر ریپوی مجاز خواستید ساخته شود، remote عوض می‌شود.
- **دمو-ویجت‌ها در DB توسعه:** روی ویجت لحظه‌ای timer_date=2026-12-20 تنظیم + دو ویجت تمام‌صفحه (ordering 0) و بلوک رنگی (ordering 10) برای دمو/تست ساخته شد — از پنل قابل حذف/ویرایش‌اند؛ چیدمان seed پیش‌فرض (widget_seeder) عین مرجع باقی ماند.
- **E2E مرورگر (دسکتاپ+موبایل، تم فعال موقت):** ۸ سوایپر init، تایمر زنده (ثانیه شمار)، thumbs با کلیک واقعی (realIndex دنبال شد)، دکمه خرید → صفحه محصول فارسی، بدون خطا/هشدار کنسول، بدون overflow افقی موبایل، VLM تأیید بصری بخش‌ها.

**فاز بعدی: C (۵۱) کاتالوگ — category/search/product/compare/favorite/brand/specials.**

---

## Phase 51 — کلونِ خام: صفحات /install هم ۵۰۰ می‌دادند (SecurityHeaders → option() → دیتابیس)

**تاریخ:** 2026-10-25 | **ورودی کاربر:** پروژه را کامل حذف و دوباره clone کرد؛ این‌بار خطای جدید: `Access denied for user ''@'localhost' ... SQL: select * from options where lang = fa` با stacktrace از `SecurityHeaders.php(27) → SecuritySettings::headersEnabled() → option() → options` در حالی که درخواست از کل پایپ‌لاین میدلور (RedirectMiddleware → SecurityHeaders) رد شده بود.

### ریشه‌یابی (چرا این‌بار از میدلور بود، نه از پرووایدر ماژول)
- کلونِ خام = بدون `Modules/` (ماژول RackWidget مال سرور قبلی بود) → خطای LicenseGuard رفع شد؛
- اما `bootstrap/app.php` (فاز ۲۳) وقتی `.env` نیست از `.env.example` کپی می‌سازد (رفتار عمدی و درست — کاربر هم دید که env «خودکار ساخته می‌شود») → اعتبارنامه‌ی دیتابیس خالی؛
- درخواستِ ریدایرکت‌شده به `/install` از گیت لایه‌ی ۱ رد می‌شود (باید هم رد شود) و در پایپ‌لاین میدلور سراسری به `SecurityHeaders` می‌رسد؛
- `SecurityHeaders` (برخلاف `BlockBots` که گارد فاز ۴۵ دارد) **گارد `application_installed()` نداشت** → `SecuritySettings::headersEnabled()` → `option('security_headers_enabled','on')` → در `option()` کش گزینه‌ها سرد است → کوئری `Option::where('lang','fa')` → با اعتبارنامه‌ی خالی ۵۰۰.
- **علت مثبت کاذب تست‌های قبلی:** در تست‌های دستی فاز ۴۶/۴۷ کش فایلِ محلی `options.fa.__loaded__` از قبل پر بود → `option()` بدون دیتابیس جواب می‌داد و «صفر کوئری» دیده می‌شد؛ روی کلون خام (کش سرد) همین مسیر می‌ترکید. درس: سناریوی «کلون خام» باید با کش سرد تست شود.

### اصلاحات (فاز ۵۱ — دو لایه‌ی دفاعی)
1. **`bootstrap/helpers.php` — گارد مرکزی در `option()`**: پیش از نصب، هر فراخوانی `option()` بدون دیتابیس مقدار پیش‌فرض آرگومان را برمی‌گرداند. چون `option()` در میدلورها/کامپوزرها/سرویس‌های مختلف صدا زده می‌شود، گارد در نقطه‌ی مرکزی هر مصرف‌کننده‌ای را ایمن می‌کند. (بررسی شد: SetupFinalizer، سیدرها و کنترلرهای نصب‌کننده هیچ‌کدام از `option()` استفاده نمی‌کنند؛ پس جریان نصب دست نمی‌خورد.)
2. **`app/Http/Middleware/SecurityHeaders.php` — گارد صریح `application_installed()`** (هم‌سبک با BlockBots) — پاسخ نصب بدون پرس‌و‌جو از وضعیت هدرها از دیتابیس.

### تست رگرسیون جدید (tests/Feature/Extra/InstallerFreshCloneTest.php)
- سناریوی دقیق «کلون خام»: حذف نشان قبل از بوت + کش سرد + `database.default` روی اتصالِ ناموجود (`ghost_connection`) تا هر دست‌زدن به دیتابیس منفجر شود؛
- ۴ تست: رندر `/install` و `/install/requirements` بدون دیتابیس، بازگشت پیش‌فرض از `option()`، و عبور میدلور SecurityHeaders — **همه سبز**؛
- **تست جهش (mutation check)**: با حذف گارد `option()` → تست option قرمز ✓؛ با حذف هر دو گارد → هر ۴ تست قرمز (بازتولید دقیق ۵۰۰ کاربر) ✓؛ با گاردها → سبز ✓ — یعنی تست‌ها واقعاً از رگرسیون محافظت می‌کنند.

### تست‌های کامل
- Smoke/Auth/Cart/Catalog/Checkout/Extra: **119 ✓** (شامل ۴ تست جدید) | Security: 62 ✓ | Order/Payment: 32 ✓ | Unit: 9 ✓/1skip | Performance: 8 ✓ | ScaleReadiness: 18 ✓ | MillionScale: 9 ✓
- دو تست ConcurrentUsers در این نشست قابل تأیید مجدد نبود (زیرساخت سندباکس دیمن MariaDB را وسط اجرای ~۴ دقیقه‌ای می‌کُشت → `Connection refused` در کوئری پاک‌سازی tearDown) — در اجرای کاملِ نشست قبل سبز بودند و ربطی به تغییرات این فاز ندارند (با نشان installed اجرا می‌شوند و گاردهای فاز ۵۱ فقط در حالت نصب‌نشده فعال‌اند).

### جمع‌بندی برای کاربر
- رفتار «ساخت خودکار .env از .env.example» عمدی است (کلید APP_KEY و بوتِ نصب‌کننده)؛ مشکل آن نبود که چیزی قبل از نصب به دیتابیس برسد — الان هر دو لایه بسته شد و `/` → 302 و صفحات `/install` با هر .envِ ناقصی 200 می‌دهند.

**کامیت:** میزبان → docs/project-analysis

---

## Phase 52 — قالب سانیار فاز C: کاتالوگ کامل (محصول/دسته/جستجو/مقایسه/علاقه‌مندی/برند/ویژه/تخفیف)

**تاریخ:** 2026-10-24 | **برنچ میزبان:** docs/project-analysis (بدون تغییر کد میزبان — این فاز ۱۰۰٪ در ریپوی تم) | **تم:** themes/Saniar کامیت fa753e0 → پوش به برنچ theme-saniar | **تست:** فاز ۱: 228 passed / 0 failed / 3 skipped (683 assertions) + فاز ۲ (Scale با مقیاس کاهش‌یافته SCALE_PRODUCTS=2000): 29 passed (73 assertions) — مقیاس کامل روی این سندباکس OOM سندباکس را می‌کشد (مشکل مستند فاز ۵۱؛ کد میزبان دست‌نخورده)

### تحویل‌شده‌های فاز C
1. **۱۴ نمای Blade کاتالوگ + ۱۲ پارشال** با طراحی سانیار (Bootstrap 5 RTL + bootstrap-icons): products/show (گالری zoom، واریانت، فروشندگان، مشخصات، دیدگاه‌ها با مرتب‌سازی، پرسش‌وپاسخ، نمودار قیمت Chart.js، اشتراک‌گذاری، معرفی به دوستان)، category-products (فیلترهای offcanvas موبایل + نوار کناری دسکتاپ + noUiSlider قیمت + مرتب‌سازی + صفحه‌بندی AJAX با pushState)، category (درخت زیردسته‌ها)، search (نتایج + تب‌ها)، compare (جدول مشخصات چندمحصولی)، specials، discounts، brands/show + پارشال‌های product-info/category-filters/reviews-comments/stores-template/sizes-modal/compare-modal/add-review/links-tab/product-meta/store-row/question-answer-data/paginate.
2. **جستجوی زنده هدر** (live-search.js + search.css): تایپ ≥۲ کاراکتر → POST ajax_search با debounce → دراپ‌داون با بخش‌های محصول (تصویر/عنوان/قیمت/بج درصد تخفیف)، دسته‌بندی، برند + دسته‌های برند + «مشاهده همه نتایج»؛ بستن با کلیک بیرون/Escape؛ کار روی فیلد دسکتاپ و موبایل.
3. **اسکریپت‌های صفحه:** show.js (۵۹۷ خط — واریانت AJAX prices، فروشندگان AJAX get-stores، افزودن به سبد با جایگزینی کامل offcanvas سبد + شمارنده هدر، علاقه‌مندی toggle، اطلاع‌رسانی موجودی، دیدگاه/پرسش AJAX، فرم دیدگاه با نقاط + خروجی نقاط، toast سبک سانیار)، category.js (فیلتر GET فرم + noUiSlider → تغییر خودکار، مرتب‌سازی با event delegation، صفحه‌بندی AJAX + pushState + اسکرول)، compare.js.
4. **نمایه‌های حداقلی auth** برای جریان علاقه‌مندی: auth/login (OTP-محور)، auth/password-login، layout اختصاصی با auth.css — فاز E (احراز هویت کامل) بعداً کامل می‌شود.
5. **user/favorites + user layout**: صفحه علاقه‌مندی‌های من با پنل کاربر سانیار.
6. **helpers سانیار:** saniar_category_brands(category) — برندهای دارای محصولِ زیردرخت دسته با کش ۱۰ دقیقه (برای نوار برند صفحه دسته).
7. **app.js:** گارد وجود المنت برای همه سوایپرها (homeSlider/suggetMoment قبلاً در صفحات دیگر با el=null خراب می‌ساختند) + رفع تقدم گالری محصول.

### دو باگ کشف‌شده و رفع‌شده (در همین فاز)
1. **`Undefined stdClass::$image` در search-result** (۵۰۰): پارشال جدید روی ->image دسته‌ها می‌زد ولی ajax_search فقط id/title/slug انتخاب می‌کرد. رفع: ستون image به select دسته‌ها و دسته‌های برند اضافه شد. (در WeblakShop لاتنت نیست — پارشالش دفاعی است و آیکون ثابت می‌گذارد.)
2. **باندل Swiper قالب فاقد ماژول Thumbs است** (فقط ۱ رخداد "thumbs" در باندل!): یعنی `thumbs:{swiper}` بی‌اثر و کلیک روی thumbnail گالری محصول + هایلایت thumb فعال هرگز کار نمی‌کرد — حتی در دموی HTML اصلی قالب. رفع: ناوبری دستی با رویداد بومی click روی کانتینر thumbs → slideTo + toggle کلاس swiper-slide-thumb-active (CSS از قبل در product.css بود). نکته: سیستم رویداد Swiper این باندل click را emit نمی‌کند (رویداد بومی لازم است).

### نکته محیطی (برای نشست‌های بعدی)
- **DATABASE_URL سندباکس Next.js به shell نشت می‌کند** (`DATABASE_URL=file:/home/z/my-project/db/custom.db`) و Laravel آن را به‌عنوان URL اتصال MySQL تفسیر می‌کند → «Unsupported driver [file]» در همه دستورات artisan. راه‌حل: پیشوند `DATABASE_URL=` برای هر دستور artisan/tinker در این سندباکس. (سرور وب مستقیماً php -S است و از محیط shell ارث نمی‌برد → سالم.)
- **مقیاس کامل تست Scale (۳۰هزار محصول) دیمن MariaDB را OOM می‌کند** و بعدش همه تست‌ها Connection refused می‌گیرند — تا زمان restart با `bash /home/z/db-start.sh`. با SCALE_PRODUCTS=2000 همه ۲۹ تست سبز. (فاز ۵۱ همین را مستند کرده بود؛ این‌بار با متغیر محیطی حل عملی شد.)

### E2E مرورگر (تم فعال موقت، دسکتاپ 1280 + موبایل 390)
- ۹ صفحه: محصول (گالری ۳ اسلاید + کلیک thumb → جابه‌جایی + هایلایت همگام، افزودن به سبد → toast + offcanvas با قلم واقعی + شمارنده هدر=۲، علاقه‌مندی toggle → قلب پر)، دسته (۵ کارت، ۶ فیلتر، اسلایدر قیمت، مرتب‌سازی AJAX → URL به‌روز، تیک چک‌باکس → ۵→۳ محصول)، جستجوی زنده (۹ آیتم)، نتایج جستجو (۳ کارت)، مقایسه (۱۲ ردیف × ۲ محصول)، ویژه (۲)، تخفیف‌دار (۵)، برند (۳ + لوگو)، علاقه‌مندی‌ها (پس از ورود: کارت iPhone).
- ورود کامل: phone → صفحه رمز سانیار → خانه. بدون هیچ خطای کنسول در همه صفحات. موبایل: scrollWidth=390 بدون overflow افقی (محصول + دسته). تأیید بصری VLM: دراپ‌داون جستجو، گالری با thumb هایلایت‌شده، صفحه علاقه‌مندی‌ها.

**کامیت:** میزبان (فقط این فایل) → docs/project-analysis | **فاز بعدی: D (۵۳) سبد + چک‌اوت + پرداخت + فاکتور**

## Phase 53 — قالب سانیار فاز D: سبد خرید + چک‌اوت + پرداخت + فاکتور

**تاریخ:** 2026-10-24 | **برنچ میزبان:** docs/project-analysis (کد میزبان دست‌نخورده — این فاز ۱۰۰٪ در ریپوی تم + گزارش) | **تم:** themes/Saniar کامیت 19fac805 → پوش به برنچ theme-saniar | **تست:** فاز ۱ (Smoke/Auth/Cart/Catalog/Checkout/Extra): **119 ✓ / 326 assertions** | فاز ۲ (Security/Order/Payment/Unit): **104 ✓ / 3 skipped** | Scale: Readiness 18 ✓ + MillionScale 9 ✓ (SCALE_PRODUCTS=2000) + Performance 8 ✓ | ConcurrentUsers: ۲ تست همان مشکل مستند زیرساختی فاز ۵۱ (دیمن MariaDB می‌میرد؛ در نشست سبز قبلی معتبر).

### تحویل‌شده‌های فاز D
1. **صفحه سبد خرید** (carts/index + cart.css + carts/cart.js): نوار ۳-مرحله‌ای (سبد/جزییات پرداخت/تکمیل)، کارت آیتم سانیار (تصویر/بج درصد تخفیف/فروشنده/واریانت‌ها/بج دانلودی)، استپر تعداد اختصاصی با min=cart_min و max=cart_max (نام input قرارداد product-{pivot_id}؛ TouchSpin سراسری قالب روی name=count بایند می‌شود و تداخل ندارد)، آپدیت AJAX سبد (PUT با خطاهای 406 → جعبه #cart-errors)، حذف با مودال تأیید، سایدبار factor چسبان، حالت خالی (empty-cart.svg + CTA)، noindex.
2. **چک‌اوت کامل** (carts/checkout + partials): آدرس‌ها به‌صورت کارت addresses-item (radio name=address + data-city + ویرایش/پیش‌فرض)، مودال افزودن/ویرایش آدرس **بدون نقشه** مطابق مرجع سانیار (checkout.html نقشه ندارد؛ validation سرور lat/lng اختیاری) + لود شهرهای هر استان از روت میزبان /province/get-cities؛ مرسولات per فروشنده با حامل‌ها (کارت shipping-item + radio مخفی carrier_id_{group}) و بخش تاریخ تحویل (send-item + radio carrier_date_{group})؛ درگاه‌ها/کیف پول (bank-item + wallet-balance با چک موجودی)؛ مودال کد تخفیف + حذف کوپن؛ یادداشت سفارش (description)؛ ولیدیشن کلاینت + سرور.
3. **محاسبه زنده قیمت** بر قرارداد exact سرور: GET front.checkout.prices با پارامترهای city_id/carriers[carrier_id_x]/idGroupSelect/is_first_load → سه HTML (checkout_sidebar جایگزین #checkout-sidebar، carriers_container جایگزین #checkout-carrier-container، deliveryDateForOne تزریق در #send-period-container-{group})؛ در بار اول حامل پیش‌فرض سرور چک می‌شود و برای حامل‌های user_select درخواست دوم تاریخ‌ها را می‌گیرد (زنجیره‌ای)؛ نکته PHP: پارامتر carriers باید همیشه ارسال شود وگرنه foreach(null) ترک می‌کند (کلید ساختگی __none).
4. **نتیجه پرداخت** (carts/orderResultInfo): سه حالت (paid → آیکون سبز + جدول مشخصات پرداختی/وضعیت سفارش + دکمه‌های پیگیری/چاپ فاکتور/بازگشت؛ transaction-error|unpaid → fail-pay + «پرداخت مجدد» فقط برای unpaid (سفارش canceled → «ثبت سفارش مجدد»)؛ error → حالت لغو). تاریخ جلالی با Morilog.
5. **پیگیری سفارش** (user/orders/show روی user.layouts.master): هدر وضعیت (بج پرداخت/تعداد/تخفیف/ارسال + تاریخ جلالی)، کارت پرداخت سفارش unpaid (انتخاب درگاه → GET orders.pay?gateway=)، اقلام با حامل/تاریخ تحویل، جدول جمع‌ها، اطلاعات ارسال، بنر transaction-error.
6. **فاکتور چاپی** (user/orders/print): صفحه مستقل مطابق factor.html مرجع — صورتحساب رسمی (مشخصات فروشنده از optionها / خریدار / اقلام با کد کالا / جمع‌ها / امضاها)، تاریخ+شماره تراکنش موفق آخر، دکمه پرینت، استایل print جدا (factor.css — بدون inline CSS طبق قاعده).
7. **زیرساخت مشترک**: css/components.css در master (toast سبک سانیار + overlay لودینگ + اسپینر)، دکمه سایدبار با routeIs بین سبد/چک‌اوت متن عوض می‌کند، steps.blade مشترک.
8. **JS بدون پلاگین جدید** (carts/cart.js، carts/checkout.js، carts/order-show.js، carts/factor.js): همگام‌سازی delegated برای shipping/bank/send-item ها (چون jquery-app بایند direct دارد و عناصر AJAX‌جایگزین را از دست می‌دهد)، toast الگوی فاز C، CSRF از meta، مدیریت 406/419/422.

### باگ‌های کشف‌شده و رفع‌شده (در تم سانیار — کد میزبان دست‌نخورده)
1. **تخفیف منفی میزبان با داده real**: در prices ستون regular_price برابر NULL است (فاز ۳۰)؛ Cart::totalDiscount می‌شود Σ(0 − discountPrice) → **منفی**؛ نتیجه: finalPrice سایدبار ≈ دوبرابر (178M → 347.1M دیده شد) و در ثبت سفارق discount_amount منفی → MySQL 1264 رد می‌کرد. رفع در تم: helperهای saniar_cart_price_without_discount/saniar_cart_discount/saniar_cart_final_price + اصلاح getPrices و store (قیمت نهایی = Σ discount_price + ارسال − فقط کوپن، چون physicalTotal از قبل تخفیف کالایی دارد — دوبارکسر هم رفع شد). **لاتنت میزبان/WeblakShop ثبت شد.**
2. **paid_at وجود ندارد**: OrderController::orderPaid هر دو تم 'paid_at' => now() می‌نویسد ولی ستونش در orders نیست → هر پرداخت واقعی → Unknown column → rollback → لغو سفارش! در سانیار حذف شد؛ تاریخ پرداخت در فاکتور از آخرین transaction موفق خوانده می‌شود. **لاتنت WeblakShop باقی است.**
3. **nested form**: فرم حذف کوپن داخل #checkout-form بود → HTML parser فرم را می‌شکست → POST سفارش 405 Method Not Allowed. فرم با form-attribute بیرون منتقل شد.
4. **dataType json روی پاسخ متنی**: PUT /cart متن 'success' برمی‌گرداند؛ dataType:'json' → parseError → همیشه fail. به text + پارس دستی 406 اصلاح شد.
5. **scopeActive حامل‌ها**: فقط حامل‌های با carrige_forward یا tariff فعال‌اند — نکته دیتایی (نه باگ)؛ در seed/دمو باید carrige_forward=1 باشد.
6. **پرداخت کیف پول بدون reserve**: payUsingWallet → مستقیم orderPaid → confirmReservation بدون reserve قبلی → reserved_stock منفی شد (−۲ در دمو). در دمو دستی صفر شد. **رفتار میزبان؛ ثبت شد.**
7. ریز: aria-label لینک لوگو (accessibility «NULL»)، radio حامل پیش‌فرض از selected_carrier سرور، ولیدیشن gateway وقتی هیچ درگاهی تعریف نشده.

### نکات محیطی این فاز (برای نشست‌های بعدی)
- قبل از phpunit: داده‌های دمو (کاربر 09120000001/آدرس‌ها/کیف پول از histories/حامل‌های «پست پیشتاز» و «تیپاکس»/کوپن SANIARTEST/سفارش‌های دمو) باید حذف شوند — تست‌های Checkout با assertDatabaseCount حساس‌اند (این بار ۷ شکست صرفاً از این بود). حامل‌ها/کوپن softDelete هستند (withTrashed→restore).
- کیف پول کاربر از WalletHistory با status=success محاسبه می‌شود (Wallet::balance())؛ ستون balance فقط cache است (refereshBalance).
- DatabaseReady: بعد از OOM دیمن (اجرای Scale) → bash /home/z/db-start.sh + ~۸ ثانیه صبر؛ ConcurrentUsers همچنان قابل تأیید در این سندباکس نیست (فاز ۵۱).
- جدول wallet تستی: شارژ ۲۰۰M با histories; wallet منفی شد برای تست ناکافی → دوباره deposit جبرانی.

### E2E مرورگر (تم فعال موقت، دسکتاپ 1280 + موبایل 390)
- **مسیر موفق کامل**: ورود (phone+password) → محصول iPhone → خرید ۲ عدد → سبد (استپر ۲ + بروزرسانی PUT سبز + ادامه → checkout) → آدرس فعال تهران → getPrices اولیه (حامل‌ها + هزینه) → انتخاب تیپاکس → ۷ تاریخ جلالی آبان ۱۴۰۵ → تاریخ انتخاب → کوپن SANIARTEST (رفع تاریخ اعتبار → تخفیف ۸,۹۵۰,۰۰۰) → ثبت → **پرداخت کیف پول موفق → orderResultInfo/361 «پرداخت شما با موفقیت انجام شد» + مبلغ ۱۶۹,۱۲۸,۸۰۰ (exact با سایدبار)** → پیگیری سفارش (اقلام/حامل/تاریخ/جمع‌ها) → فاکتور چاپی کامل.
- **مسیر ناموفق**: کیف پول ناکافی + درگاه zarinpal ساختگی → Shetabit fail (شبکه سندباکس) → cancelOrderAndRefund → orderResultInfo/362 صفحه fail-pay درست (دکمه «ثبت سفارش مجدد» چون canceled).
- آدرس جدید از مودال (استان → ۴۲ شهر AJAX → ثبت → reload → کارت جدید دیده شد)؛ ولیدیشن خالی مودال؛ حذف آیتم سبد → صفحه «سبد خرید شما خالی است».
- موبایل 390px: cart/checkout/orders بدون overflow افقی (390=390)؛ سایدبار در موبایل استاتیک؛ **صفر خطای کنسول در کل جریان**؛ تأیید بصری VLM (چک‌اوت دسکتاپ + سبد + نتیجه): «no overlapping elements or broken styles».

**فاز بعدی: E (۵۴) احراز هویت کامل (ورود/ثبت‌نام OTP/رمز/فراموشی/ورود فروشنده) — نمای‌های حداقلی auth فاز C کامل می‌شوند.**

## Phase 54 — قالب سانیار فاز E: احراز هویت کامل (ورود/ثبت‌نام OTP/فراموشی رمز/ورود پیامکی/verify)

**تاریخ:** 2026-10-24 | **برنچ میزبان:** docs/project-analysis (کد میزبان دست‌نخورده — ۱۰۰٪ در ریپوی تم) | **تم:** themes/Saniar کامیت 6960b458 → پوش به برنچ theme-saniar | **تست:** فاز ۱ (Smoke/Auth/Cart/Catalog/Checkout/Extra): **119 ✓** | فاز ۲ (Security/Order/Payment/Unit): **104 ✓ / 3 skipped** | ScaleReadiness **18 ✓** + MillionScale **9 ✓** (SCALE_PRODUCTS=2000) + Performance **8 ✓** | ConcurrentUsers: همان محدودیت مستند زیرساختی فاز ۵۱ (دیمن MariaDB وسط اجرا می‌میرد؛ نشست سبز قبلی معتبر)

### معماری کشف‌شده در این فاز (نقشه کامل auth میزبان)
- میزبان `routes/auth.php` GET صفحات را از `config('front.pages.*')` تم رندر می‌کند (login/register/forgot-password/login-with-code/one-time-login) اما POST /login را روت تم (UserController) می‌بَرد (تم بعد از میزبان register می‌شود → برنده). GET /register همیشه ۳۰۳ به /login (ویوی register مرده).
- فلوها: موبایل → POST /login → /login/password → کاربر موجود=رمز، ناموجود=OTP (UserMobileVerify) → تعیین رمز (SettFirstOnePassword) → /welcome. فراموشی: forgot-password (کپچای اجباری) → one-time-login?mobile → سشن forget_password_link → ویو change-password (OTP+رمز جدید، native POST) → toast. ورود پیامکی: login-with-code → one-time-login?type=login → AJAX confirm. one-time-login بدون سشن فراموشی → force_to_password_change=1 → میدلور CheckPasswordChange → /profile/force-change-password (فقط روت‌های force مجاز). verify: میدلور verified (وقتی sms_to_verify_user=on) → /verify + تغییر شماره (فقط ستون username عوض می‌شود، ورود با ستون mobile — رفتار میزبان).
- همه OTPهای سیستم ۵ رقمی‌اند (random_int(10000,99999)) — بج‌های ۶ رقمی WeblakShop اشتباه است؛ سانیار ۵ خانه ساخت. NewUserCodeSent در constructor کد را دوباره تولید می‌کند → کد نهایی همیشه از DB خوانده شود.

### تحویل‌شده‌های فاز E (همه در ریپوی تم)
1. **۱۰ نمای Blade** با طراحی سانیار (کارت auth مرجع + bg-auth): password-register-verify، welcome، forgot-password، login-with-code، one-time-login، change-password، verify، change-username، passwords/force-change، passwords/reset + پارشال مشترک OTP (۵ خانه + countdown-bar + resend).
2. **auth.js** (pages/): پرش خودکار/پِیست/جمع در hidden، جایگزینی رقم در خانه پر، ارسال خودکار با data-otp-autosubmit، شمارش معکوس زنده (نوار+متن) و لینک «ارسال مجدد» در پایان، فرم‌های AJAX (data-ajax/data-redirect/data-append-mobile/data-stay) با خطای 422/429، نمایش رمز، پاک‌سازی جعبه خطا/موفقیت در سابمیت.
3. **auth.css فاز E**: اعلان‌های کارت، جعبه خطا/موفقیت، استایل OTP و countdown با پالت تم (روی کلاس‌های style.css مرجع سوار شد)، کپچای میزبان بدون فونت mdi (refresh با ⟳)، کارت خوش‌آمد، توست سشن انیمیت CSS (بدون JS).
4. **UserController تم**: welcome() حالا compact('user') می‌فرستد (سشن UserWelcome قبل از رندر فراموش می‌شد → شماره خالی).
5. **دو باگ falsy جکویی**: data-otp-autosubmit و data-append-mobile بدون مقدار → .data() رشته خالی falsy → با attr() چک شد (ارتباط مستقیم با دو شکست E2E).

### E2E مرورگر (تم فعال موقت 1280+390)
- **ثبت‌نام:** موبایل جدید → صفحه OTP سانیار → توزیع خودکار ارقام → ارسال خودکار → تعیین رمز → /welcome (شماره واقعی + CTAها). **ورود:** رمز غلط=خطای قرمز، رمز درست=خانه با شمارنده سبد. **خروج** از دراپ‌داون هدر.
- **ورود با پیامک:** گزینه on → صفحه + کپچا (حل با VLM) → AJAX → one-time-login?type=login&mobile → OTP → لاگین خودکار.
- **فراموشی:** forgot-password + کپچا → change-password (OTP+رمز جدید در یک فرم) → POST native → /login + توست سبز «رمز تغییر کرد» → ورود با رمز جدید ✓.
- **تغییر اجباری:** ورود one-time → force=1 → مسیر محافظت‌شده → bounce به force-change → رمز →AJAX→ بازگشت intended، force=0.
- **verify:** bounce به /verify → OTP → verified_at + بازگشت intended؛ تغییر شماره → AJAX → /verify با کد جدید؛ شمارش معکوس زنده (93%→00:00 → لینک resend ظاهر، نوار مخفی — تأیید VLM)؛ کد غلط = «کد تایید وارد شده صحیح نمی باشد».
- **پنل:** /profile/change-password → data-stay + جعبه سبز (تا فاز F مسیر پروفایل ساخته شود — TODO در ویو)؛ رمز قبلی غلط = خطای قرمز. تغییر رمز واقعاً در DB اعمال شد (Hash::check).
- موبایل 390: پنج صفحه auth بدون overflow (390=390)؛ VLM: کارت کامل، بدون همپوشانی. **صفر خطای کنسول** در کل نشست. رنگ دکمه‌ها #275fe0 = پالت رسمی خود قالب سانیار (فولوفالو مرجع).

### نکات دیتایی/محیطی (برای نشست‌های بعد)
- گزینه‌های forgot_password_link/login_with_code/sms_to_verify_user برای E2E موقتاً on و بعد به off بازیابی شدند (مقادیر اصلی DB).
- **پاکسازی ناتمام فاز D:** حامل‌های دموی 109/110 (پست پیشتاز/تیپاکس) فعال مانده بودند در حالی که همه tariffs حذف شده بودند → Carrier::active()->first() در تست‌ها حاملِ بدون تعرفه برمی‌داد → ۳ شکست Checkout (shipping 0). با soft-delete آنها ۵۲/۵۲ سبز شد — درسی برای پایان هر فاز: دموی E2E کامل پاک شود.
- کاربر تست E2E (09350001111/09350002222) با همه رکوردهایش حذف شد؛ users_count=7.
- دیمن MariaDB دوباره وسط Scale مرد (رفتار مستند) → db-start.sh + بازیابی؛ Readiness/Million/Performance پس از restart سبز.
- کپچای میزبان فقط هش در سشن دارد (CaptchaService) — حل خودکار در E2E فقط با خواندن تصویر (canvas→VLM) ممکن است.

**کامیت:** تم → theme-saniar (6960b458) | میزبان (فقط این فایل) → docs/project-analysis | **فاز بعدی: F (۵۵) پنل کاربر — پروفایل/ویرایش/سفارش‌ها/آدرس‌ها/کیف پول/تیکت/پیام‌ها/اعلان‌ها/معرفی‌ها (+ وصل‌کردن redirect صفحه reset به پروفایل)**
## Phase 56 — بازار: ترتیب قالب/پکیج + حذف کارت خالیِ تکراری + صفحهٔ تنظیمات برنامه‌نویس (عرض + `</div>` اضافه) + asset استاتیک لایووایر + قفلِ بیلد npm

### درخواست کاربر (۶ مورد)
1. «صفحهٔ تنظیمات برنامه‌نویس کوچیک شده» (اسکرین‌شات)
2. ذخیرهٔ تب «تنظیمات دیگر» → ارور `Undefined variable $start`
3. بازار: پکیج‌ها نمایش داده می‌شوند ولی زیرشان «پکیجی برای نمایش وجود ندارد»
4. بازار: نمایش قالب‌ها بیاید بالای پکیج‌ها
5. ارور کنسول بازار: 404 + رد شدن `livewire.js` به‌خاطر MIME `text/html`
6. بیلد `npm run build:admin` → `Rollup failed to resolve import "sortablejs"`

### ۱) باگ عرض صفحهٔ تنظیمات برنامه‌نویس — ریشه: یک `</div>` اضافه (فاز ۴۵)
- **بازتولید واقعی با مرورگر**: ستون اصلی پنل به‌جای ~۱۰۰۸px فقط **۶۶۳px** بود؛ علت: یک `</div>` اضافه در انتهای `developer.blade.php` (اضافه‌شده در بازطراحی تب امنیتِ فاز ۴۵ — کامیت df9a4fbe) درخت HTML را خراب می‌کرد؛ پارسر مرورگر با تگِ بستنِ اضافه، `<div class="flex min-w-0 flex-1 …">` ستون اصلی را «زودتر» می‌بست و **فوتر بیرون از ستون** به‌عنوان فرزند سومِ flex ردیف، ~۳۴۵px عرض می‌بلعید (۱۲۸۰−۲۷۲سایدبار−۳۴۵فوتر=۶۶۳).
- اسکن عمق `<div>` در فایل: ۷۶ باز / ۷۷ بسته → حذف خط ۵۸۷ اضافه → **۷۶/۷۶ متوازن**.
- **راستی‌آزمایی مرورگری**: عرض ستون ۶۶۳→**۱۰۰۸px**، فوتر داخل ستون (`HEADER,MAIN,FOOTER`)، بدون اسکرول افقی؛ موبایل ۳۹۰px هم سالم؛ پنج تب (کرون/بروزرسانی/تنظیمات دیگر/آپدیت‌کننده/امنیت) کامل داخل کارت.
- **نکته**: با همین تگ خراب، morphing لایووایر روی این صفحه هم غیرقابل‌اعتصام بود؛ حذفش رفتار ذخیره/رندر مجدد را هم پایدار می‌کند.

### ۲) ارور «Undefined variable $start» — در کد مخزن وجود ندارد (۳ اثبات)
- جستجوی کامل مخزن + **تمام تاریخچهٔ git با pickaxe** (`git log -S'$start' --all -- '*.blade.php'`): هیچ blade/کامپوننتی در هیچ فازی `$start` نداشته است (تنها تطابق، متغیر `$startingLine` در رندرر استثنای لاراول است).
- اجرای هر ۴ متد ذخیره (saveSharedHosting/saveGeneral/saveSecurity/downApplication) با `Livewire::test` → همه سبز.
- جریان واقعی HTTP (GET صفحه + POST آپدیت لایووایر) و **کلیک مرورگری واقعی روی «ذخیره تغییرات»** در تب تنظیمات دیگر → توست «ذخیره شد»، بدون ارور.
- نتیجه: ارور از محیط سرور کاربر است — احتمال اول: **viewهای کامپایل‌شدهٔ کهنه** در `storage/framework/views` (قبل از pullهای اخیر) یا فایل شخصی‌سازی‌شده/ویوی ماژول روی سرور. نسخهٔ این فاز با حذف `</div>` اضافه همان صفحه را از ریشه سالم می‌کند؛ اگر بعد از deploy باز تکرار شد: `php artisan view:clear` + ارسال tail لاگ (استک‌تریس دقیقاً نام فایل ویو را می‌دهد).

### ۳+۴) بازار — ترتیب بخش‌ها و کارت خالی
- `packages/index.blade.php`: بلوک «قالب‌ها» به **بالای** بلوک «پکیج‌ها» منتقل شد (در تب «همه» اول قالب‌ها بعد پکیج‌ها؛ تب‌های اختصاصی تغییری نمی‌کنند) — درخواست شمارهٔ ۴.
- باگ کارت خالی: زنجیرهٔ `@if ($type === 'package') … @elseif (! $apiError)` باعث می‌شد در تب «همه» **همیشه** کارت «پکیجی برای نمایش وجود ندارد» زیر گرید پکیج‌ها رندر شود → شرط به `@elseif (! $apiError && ! count($packages))` تغییر کرد — درخواست شمارهٔ ۳.
- راستی‌آزمایی مرورگری با تزریق دیتای آزمایشی از طریق `$wire.set`: ۲ کارت پکیج رندر شد و کارت خالی حذف شد؛ با `packages=[]` کارت خالی دوباره ظاهر می‌شود (هر دو مسیر تست شد).

### ۵) 404 + MIME لایووایر — assetها استاتیک شدند
- ریشه: آدرس asset لایووایر در Livewire 4 از هش APP_KEY ساخته می‌شود (`/livewire-b7b8f7ea/livewire.js`)؛ بعد از هر چرخش APP_KEY (مثل اجرای دوبارهٔ نصب‌کننده) URLِ قدیمیِ رندرشده در HTML کش‌شده 404 (text/html) می‌دهد؛ علاوه بر آن کانفیگ‌های nginx که `*.js` را فقط از فایل سیستم سرو می‌کنند این مسیر داینامیک را 404 می‌کنند.
- راه‌حل: `php artisan livewire:publish --assets` → `public/vendor/livewire/` (manifest + livewire.js + min/csp) **کامیت شد**؛ Livewire خودش اگر manifest منتشرشده ببیند، اسکریپت را از `/vendor/livewire/livewire.min.js?id=<hash>` می‌دهد = فایل واقعی روی دیسک، مستقل از APP_KEY و سازگار با nginx.
- راستی‌آزمایی مرورگری: `<script src="…/vendor/livewire/livewire.min.js?id=8ea5922c">` → **HTTP 200**، همهٔ POSTهای آپدیت لایووایر 200، کنسول بدون خطا.
- محافظ موجود در layout (ری‌لود خودکار ۶ثانیه‌ای اگر لایووایر لود نشد) همچنان پابرجاست.

### ۶) بیلد npm — قفلِ ناهماهنگ
- ریشه: `sortablejs` از فاز ۱۳ در `package.json` هست ولی **`package-lock.json` آخرین بار در فاز ۰** تولید شده بود؛ روی سیستم کاربر node_modules فاقد sortablejs → Rollup resolve نمی‌کرد.
- `npm install` مجدد اجرا شد → package-lock جدید (۲۷۴۰ خط اضافه: sortablejs/echarts/ckeditor5/tailwindcss4/vite6/...) کامیت شد؛ **`npm run build:admin` از این پس هم `npm ci` و هم `npm install` سبز است** (تست شد: ۱۴۵۲ ماژول، خروجی admin-CjflNnFy.js + ckeditor5 chunk کامیت شدند).
- برای کاربر: فقط `git pull` و یک بار `npm install` (یا `npm ci`)، سپس `npm run build:admin` — یا اصلاً نیازی به بیلد نیست چون خروجی بیلد جدید هم داخل همین کامیت هست.

### تست‌ها
- فاز A (Smoke/Auth/Cart/Catalog/Checkout/Extra/Security/ThemeMarket): **۱۸۱ passed** (۵۴۹ assertion)
- فاز B (Order/Payment/Performance + Unit): **۴۹ passed / 1 skipped**
- Scale/MillionScale: **۹ passed** + ScaleReadiness: **۱۸ passed** (جمعاً ۲۵۷ سبز؛ دو تست ConcurrentUsers باز هم به‌خاطر کشته‌شدن دیمن MariaDB سندباکس وسط اجرای ~۲۵۰ثانیه‌ای در همین محیط قابل تأیید نیست — در جلسات قبل سبز بوده)
- روت‌ها: `np.packages.index` و `np.developer.index` ثبت‌اند؛ لاگ پس از پایان تست‌ها بدون ERROR جدید.

**کامیت:** docs/project-analysis → `fix(admin+market+build): فاز ۵۶ …`

## Phase 55 — قالب سانیار فاز F: پنل کاربر کامل (پروفایل/سفارش‌ها/آدرس‌ها/کیف پول/اطلاعیه/پیام/نظر/تیکت/معرفی/بازدید/مرجوعی)

**تاریخ:** 2026-10-24 | **برنچ میزبان:** docs/project-analysis (کد میزبان دست‌نخورده — ۱۰۰٪ در ریپوی تم) | **تم:** themes/Saniar کامیت 897e6670 → پوش به برنچ theme-saniar | **تست:** فاز ۱ (Unit+AdminPanelSmoke+Auth+Cart+Catalog+Checkout+Extra): **126 ✓ / 3 skipped** (346 assertions) | فاز ۲ (Security+Order+Payment+Performance): **102 ✓** (337 assertions) | ScaleReadiness **18 ✓** + MillionScale **9 ✓** (SCALE_PRODUCTS=2000؛ اجرای اول Scale وسط ConcurrentUsers دیمن MariaDB را کشت → ۲۹ شکست Connection refused → restart با db-start.sh → ۲۷ تست سبز؛ دو ConcurrentUsers همان محدودیت مستند فاز ۵۱)

### تحویل‌شده‌های فاز F (همه در ریپوی تم)
1. **۱۹ نمای Blade + ۲ پارشال** با طراحی سانیار (Bootstrap 5 RTL + bootstrap-icons؛ کلاس‌های رزرو قالب: status-panel-user/site-tabel/ui-box/notifi/comment/ticket-message/addresses-item):
   - **پروفایل (داشبورد)**: جعبه خوش‌آمد + panel.svg، ۴ کارت وضعیت (سفارش/آدرس/علاقه/کیف پول با بج)، اطلاعات شخصی (۶ سلول)، آخرین سفارش‌ها (ردیف‌های site-tabel با تصویر/مبلغ/وضعیت)، علاقه‌مندی اخیر + ویجت middle-banners-4 (داینامیک از Widget).
   - **ویرایش پروفایل**: floating labels، تاریخ تولد سه‌انتخابی، موبایل قفل‌شده، newsletter سوییچ، لینک تغییر رمز.
   - **سفارش‌ها**: ۴ تب وضعیت (همه/تکمیل/انتظار/لغو) با شمارنده + جدول main-table + صفحه‌بندی + empty-state.
   - **آدرس‌ها**: گرید کارت addresses-item (پیشفرض بج‌دار) + مودال افزودن/ویرایش (بدون نقشه — قرارداد فاز D) با استان→شهر AJAX (/province/get-cities)، حذف با مودال تأیید، دکمه پیشفرض‌سازی.
   - **کیف پول**: کارم موجودی + مودال شارژ (amount+gateway — سابمیت عادی چون پاسخ، رندر درگاه است) + مودال برداشت (چک موجودی کلاینت) + جدول تاریخچه + مودال جزئیات تراکنش (AJAX wallet/show) + صفحه مستقل create.
   - **اطلاعیه‌ها**: آلارم‌های خوانده/ناخوانده + آمار کل/خوانده‌نشده. **پیام‌ها**: کارت‌های دیده‌نشده (نوار رنگی) + مودال جزئیات AJAX. **نظرات من**: کارت با بج وضعیت/ستاره/نقاط قوت‌وضعف + مودال ویرایش کامل (rating/suggest/title/body/points با review[advantages][]).
   - **تیکت‌ها**: جدول با بج وضعیت، فرم ثبت (اولویت+پیام+فایل)، گفتگوی ticket-message (حباب‌های active=کاربر/پشتیبان + فایل‌ها) + فرم پاسخ FormData، هشدار تیکت بسته.
   - **معرفی‌ها**: ۳ کارت آمار + جعبه کد معرف (کپی کد/لینک) + قوانین داینامیک از settings + جدول جوایز (معرف/معرفی‌شده — تخفیف یا کیف پول) + جدول زیرمجموعه‌ها با وضعیت خرید.
   - **بازدیدهای اخیر** + **مرجوعی‌ها**: index جدولی (۹ ستون + بج‌های statusLabels با تبدیل آیکن fa→bi)، create (بنر محصول + تفکیک مبالغ + ۷ دلیل + پیش‌نمایش ۵ تصویر)، show (تایم‌لاین ۶-مرحله‌ای + دکمه‌های لغو/ارسال‌کردم طبق وضعیت).
2. **لایوت پنل**: منوی ۱۳ آیتمی داینامیک (پروفایل/ویرایش/سفارش‌ها+بج/مرجوعی/کیف پول/علاقه/آدرس/بازدید/اطلاعیه/پیام/نظر/تیکت/معرفی) + خروج؛ کارت کاربر با موجودی کیف پول؛ offcanvas موبایل.
3. **اتصال reset→پروفایل** (خواستهٔ صریح فاز E TODO): صفحه تغییر رمز data-redirect به profile?password=changed؛ پروفایل توست سبز برای password=changed و profile=updated (توست سروری با انیمیشن CSS).
4. **توست‌های فلش سشن** (toast-success/toast-error) در layouts/master اصلی (جریان برداشت کیف پول سرور redirect-back دارد).
5. **user-panel.css** (۲۰۰ خط: توست/سلول اطلاعات/تب‌ها/کارت پیام/کد معرف/تایم‌لاین/ستاره‌ها) + **panel.js** (۵۶۶ خط، ۱۰ ماژول گاردشده: toast/ویرایش پروفایل AJAX/آدرس‌ها/کیف پول/پیام/تیکت FormData/نظرات+نقاط/بازدید/کپی/پیش‌نمایش تصاویر).

### دو باگ لاتنت میزبان/WeblakShop کشف و در تم رفع شد (کد میزبان دست‌نخورده)
1. **update_profile با ایمیل/کدملی خالی → 422**: کاربران ثبت‌نام‌شده با موبایل ایمیل ندارند؛ ConvertEmptyStringsToNull مقدار '' را null می‌کند ولی بدون قانون nullable، string روی null شکست می‌خورد («فیلد ایمیل باید رشته باشد» → ویرایش پروفایل هرگز کار نمی‌کرد). رفع تم: nullable برای email/national_code. **لاتنت WeblakShop باقی است.**
2. **حذف از بازدیدهای اخیر هرگز کار نمی‌کرد**: روت محصول /product/{slug} مفرد است و میدلور Viewer مقدار page_path='product' ذخیره می‌کند، ولی destroy با 'products' (جمع) فیلتر می‌کرد. رفع تم. **لاتنت WeblakShop باقی است.** (نکته: در WeblakShop این صفحه اصلاً خالی می‌ماند چون index درست فیلتر می‌کند ولی حذف بی‌اثر است.)

### ریزباگ‌های کشف‌شده حین E2E (رفع در تم)
- دکمه «ثبت آدرس جدید» data-bs-toggle="modal" نداشت (کلیک بی‌اثر) و دکمه ویرایش به‌جای modal یک tooltip داشت — هر دو اصلاح شد (bootstrap هر عنصر فقط یک data-bs-toggle می‌پذیرد).
- کپی کد معرف با مقدار خالی گارد می‌خورد (کاربر tinker-made بدون referral_code — ثبت‌نام واقعی تم همیشه کد می‌سازد، خط 205 UserController).

### نکات دیتایی/محیطی (برای نشست‌های بعد)
- صفحه مرجوعی برای E2E به shipping_status='delivered' + delivery_date نیاز دارد (isWithinReturnPeriod از delivery_date??paid_at می‌خواند و paid_at در orders وجود ندارد — ربط به فاز D).
- سشن مرگ بعد از تغییر رمز مشکلی ندارد: StartSession سشنِ جاری را post-response بازمی‌نویسد → redirect به پروفایل سالم کار می‌کند (تست شد).
- referral_code واقعی از Referral::generateCode() (هر دو مسیر ثبت‌نام تم/میزبان آن را می‌سازند).
- کش مرورگر روی GET /profile/addresses/{id} (JSON پرکردن مودال) — در توسعه دیتا عوض شود مرورگر را reload کنید.
- Scale: اولین اجرای کامل دیمن را وسط ConcurrentUsers می‌کشد (رفتار مستند ۵۱)؛ بعد از db-start.sh هر ۲۷ تست سبز.

### E2E مرورگر (تم فعال موقت، دسکتاپ 1280 + موبایل 390)
- **ورود → پروفایل**: ۴ کارت وضعیت + اطلاعات + آخرین سفارش واقعی. **ویرایش پروفایل**: ثبت → redirect profile?profile=updated + توست سبز (پس از رفع باگ nullable). **تغییر رمز**: رمز غلط=خطای قرمز؛ درست → profile?password=changed + توست؛ کاربر لاگین ماند.
- **سفارش‌ها**: تب unpaid → empty-state؛ تب paid → ردیف 486. **کیف پول**: مودال جزئیات (شارژ ۵M)؛ برداشت ۹M → خطای «بیشتر از موجودی»؛ برداشت ۵۰۰K → توست سشن «درخواست برداشت با موفقیت ارسال شد» + موجودی ۵M→۴.۵M.
- **آدرس‌ها**: افزودن (استان → ۴۲ شهر AJAX → ثبت → ۲ کارت)؛ ویرایش (پر شدن فرم + شهر انتخابی) → ذخیره؛ حذف با مودال → ۱ کارت.
- **اطلاعیه/پیام**: محتوا + بج خوانده‌نشده + مودال AJAX. **تیکت**: ثبت UI (اولویت زیاد) → ظاهر در فهرست؛ پاسخ UI → ۲ پیام. **نظرات**: مودال ویرایش با rating=4/suggest=yes/points=۲ چپ → ثبت → ۵ ستاره + «منتظر تایید».
- **معرفی‌ها**: کد + کپی (توست «با موفقیت کپی شد») + جدول جوایز/زیرمجموعه. **بازدیدها**: حذف کار کرد (پس از رفع باگ page_path). **مرجوعی**: فرم ثبت (۷ دلیل + تفکیک مبالغ) → ساخت #45 → صفحه show با تایم‌لاین + دکمه لغو.
- موبایل 390px: هر ۷ صفحه کلیدی scrollWidth=390 (بدون overflow)؛ دکمه شناور منو فعال. **صفر خطای کنسول/صفحه در کل نشست؛ لاگ سرور فقط Connection-refused حین مرگ دیمن Scale.** تأیید بصری VLM: داشبورد (دسکتاپ+موبایل) و گفتگوی تیکت بدون همپوشانی.
- **پاکسازی کامل دمو**: هر دو کاربر تست (09350003333/04444) با سفارش/اقلام/مرجوعی/تیکت/پیام/اطلاعیه/دیدگاه/نقاط/آدرس/کیف پول/تراکنش/معرفی/تخفیف/بازدید/سشن حذف شدند (کاربران باقی‌مانده: ۸، همه از قبل).

**کامیت:** تم → theme-saniar (897e6670) | میزبان (فقط این فایل) → docs/project-analysis | **فاز بعدی: G (۵۷) — نمای فروشنده سانیار (seller/seller-table + روت‌های seller.*) و سپس صفحات محتوایی (بلاگ/تماس/درباره/FAQ/404) + آماده‌سازی مارکت قالب‌ها**

## Phase 58 — بازار: بنر اشتراک در تب «همه» همیشه «اشتراک فعالی ندارید» می‌گفت (با وجود اشتراک فعال)

### درخواست کاربر
«در بازار پکیج‌ها و قالب‌ها، من اشتراکم ۲ روز باقی‌مانده ولی گفته اشتراک فعالی ندارید» — در صفحهٔ بازار کارت‌ها/مودال نصب «رایگان با اشتراک — ۲ روز باقی‌مانده» نشان می‌داد ولی بنر بالای صفحه می‌گفت «اشتراک فعالی ندارید» (تناقض دو پیام از یک دیتای واحد).

### ریشه‌یابی
- بنر اشتراک (`packages/index.blade.php` خط ۶۵) شرطش `$type === 'package' && … has_active_subscription` بود؛ اما تب پیش‌فرضِ بازار «همه» است (`$type=''` از فاز ۴۵) → در تب «همه» (و هر تب غیر از «پکیج‌ها») **حتی با اشتراک فعال** همیشه شاخهٔ خاکستریِ «اشتراک فعالی ندارید» رندر می‌شد.
- تناقض ظاهری: کارت‌ها و مودال نصب از دیتای **per-package** (`pkg.subscription` — مستقل از تب) تغذیه می‌شوند و درست «۲ روز باقی‌مانده» را نشان می‌دادند؛ فقط بنر به تب حساس بود.
- گلاساری API (`API-AGENT-SPEC.md`): اشتراک فقط **پکیج‌ها** را پوشش می‌دهد (Subscription Plan → "its included packages become free while active") → بنر در تب «قالب‌ها» اصلاً معنا ندارد.

### رفع
1. `index.blade.php` — شرط سبز: `@if ($this->showsPackages() && !empty($subscriptionSummary) && ($subscriptionSummary['has_active_subscription'] ?? false))` (نمایش در «همه» + «پکیج‌ها») و شاخهٔ CTA خاکستری به `@elseif ($this->showsPackages())` تبدیل شد → در تب «قالب‌ها» بنر کلاً حذف می‌شود.
2. `PackageIndex::normalizeSubscriptionSummary()` — مقاوم‌سازی: اگر API پرچم `has_active_subscription` را نفرستاده باشد ولی `expires_at` آینده (یا `days_remaining>0` بدون تاریخ) داشته باشد، اشتراک «فعال» استنتاج می‌شود؛ اگر API پرچم را صریحاً فرستاده باشد همان مقدار (با اعتبارسنجی `isFuture`) معیار است. (بررسی جانبی: محاسبهٔ محلی `days_remaining` با `now()->diffInDays()` در Carbon 3.14 برای تاریخ آینده مثبت است — مشکلی ندارد.)

### تست‌ها (۵ رگرسیون جدید در ThemeMarketTest)
- `bazaar_all_tab_shows_active_subscription_banner` — تب «همه» + اشتراک فعال → بنر سبز «اشتراک فعال: طرح طلایی» (بازتولید دقیق باگ کاربر)
- `bazaar_packages_tab_shows_active_subscription_banner` — همان در تب «پکیج‌ها»
- `bazaar_templates_tab_hides_subscription_banner` — تب «قالب‌ها» بدون هیچ بنری
- `bazaar_without_subscription_shows_cta_banner` — بدون اشتراک → CTA خاکستری
- `subscription_summary_without_flag_is_derived_from_expiry` — حذف پرچم → استنتاج از انقضا
- **تست جهش**: شرط قدیمی `$type === 'package'` موقتاً برگردانده شد → هر دو تست تبه شدند (۲ failed) → فیکس دوباره اعمال شد → همهٔ ۲۰ تست ThemeMarketTest سبز.
- فاز A (Smoke/Auth/Cart/Catalog/Checkout/Extra): **۱۲۴ passed / ۳۴۴ assertion** | فاز B (Order/Payment/Performance/Scale/Security + Unit): **۱۳۸ passed / 3 skipped** (جمعاً ۲۶۲ سبز؛ ۳ skip همان ConcurrentUsers سندباکس)

**کامیت:** docs/project-analysis → `fix(market): فاز ۵۸ — بنر اشتراک بازار در تب «همه» درست نشان داده شود + استنتاج پرچم اشتراک`

## Phase 57 — قالب سانیار فاز G: فروشندگان بخش ۱ (لندینگ + احراز هویت کامل + فروشگاه عمومی + اسکلت پنل و داشبورد)

**تاریخ:** 2026-10-24 | **برنچ میزبان:** docs/project-analysis (کد میزبان دست‌نخورده — ۱۰۰٪ در ریپوی تم) | **تم:** themes/Saniar کامیت 77408a02 → پوش به برنچ theme-saniar | **تست:** فاز ۱ (AdminPanelSmoke+Auth+Cart+Catalog+Checkout+Extra+Unit): **126 ✓ / 3 skipped** (346 assertions) | فاز ۲ (Security+Order+Payment+Performance): **102 ✓** (337 assertions) — اعداد دقیقاً برابر فاز F

### تحویل‌شده‌های فاز G (۱۲ نمای Blade + ۲ JS + ۱ CSS — همه در ریپوی تم)
1. **لندینگ فروشندگان** (`sellers/index`): کاملاً داینامیک — اسلایدر از `Slider pages∋sellers` (کروسل bootstrap؛ دیتای دمو دو اسلاید «تست» داشت)، در نبودِ اسلایدر هیرو گرادیانی جایگزین با CTA؛ مزایا از SellerHero (آیکون HTML دیتابیس + fallback)، نقشه راه ۴ مرحله‌ای (خط‌چین اتصال دسکتاپ، عمودی موبایل)، مدارک حقیقی/حقوقی، کمیسیون از SellerCommission، FAQ آکاردئونی از SellerQuestion، نوار CTA پایانی. extends layouts/master فروشگاه (SEO + هدر/فوتر سایت).
2. **احراز هویت فروشنده ۷ نما** (`sellers/auth/`): لایوت اختصاصی (الگوی auth فاز E با عرض متغیر `$auth_card_class` + نوار سه‌مرحله‌ای ثبت‌نام done/current) — ورود، ثبت‌نام، تایید موبایل (پارشال OTP مشترک فاز E: ۵ خانه + نوار شمارش معکوس؛ ارسال مجدد اینجا POST است)، اطلاعات کسب‌وکار (۲۸ فیلد طبق Store_registration_business_details: سوییچ کارت‌رادیویی حقیقی/حقوقی با نمایش/مخفی‌سازی بخش‌ها، تاریخ تولد سه‌انتخابی، آبشار استان→شهر با `/province/get-cities`، قرارداد الکترونیک آکاردئونی از SellerEcontract + چک‌باکس اجباری)، مدارک (سوییچ بلی/خیر مالیات → نمایش باکس گواهی؛ آپلود **فوری** روی انتخاب فایل به `registration_documents_store?imageFor=` + حذف DELETE + پیش‌نمایش FileReader؛ «ثبت نهایی» → `documents-check`)، اتمام ثبت‌نام (آیکون تبریک + دکمه ورود به پنل + مودال جدول کمیسیون از دسته‌های دارای commission — در دیتای فعلی خالی).
3. **فروشگاه عمومی فروشنده** (`sellerStore`): هدر فروشگاه (بنر گرادیانی + لوگو/حرف اول + بج تایید + استان/شهر + تاریخ عضویت جلالی + ۳ آمار: کالا/بازدید/تنوع از Viewer و SellerVariant)، فیلتر ستونی (جستجو s + سوییچ موجودی + اسلایدر قیمت nouislider + آکاردئون‌های داینامیک از `$filterable` فیلتر is_seller: Specification مقادیر تفکیک‌شده / AttributeGroup با سواچ رنگ / StaticFilter برند و child_category از `$categorise`)، گرید product-card + مرتب‌سازی ۵گانه + صفحه‌بندی — همه روی سازوکار مشترک category.js (هوک‌های #category-products-div/#products-filter-form/js-sort-radio/price-slider). SEO: canonical + robots index + JSON-LD Store + h1/h2.
4. **اسکلت پنل + داشبورد** (`sellers/panel/`): لایوت سایدباری الگوی پنل کاربر فاز F — ۱۹ آیتم منو با سرگروه‌ها (محصولات/سفارش‌ها/مالی/حمل‌ونقل/تعامل/حساب)، کارت فروشنده + کیف پول (getWallet)، offcanvas موبایل + دکمه شناور. داشبورد: ۴ کارت آمار بازدید (کل/امروز با روند ±%/یکتا/میانگین ۳۰روز) + نمودار خطی chartjs ۷ روز با برچسب جلالی (داده از data-attrs — بدون inline JS) + کارت وضعیت کالاها (تنوع‌ها/کالا/بدون‌رقیب/رقیب از SellerVariant) + جدول ۵ تنوع اخیر (پیوند دستی به Product/Price چون SellerVariant مدل بدون relation است).
5. **JS بدون پلاگین جدید**: seller-auth.js (~۳۷۰ خط — موتور OTP کپی اثبات‌شده فاز E + فرم‌های data-ajax با پاسخ JSON میزبان {status,message,redirect} + خطای 422/429/419 + ارسال مجدد POST با هشتگ errorTime + آپلود مدارک + آبشار شهر + ولیدیشن required سمت کلاینت که فیلدهای بخش مخفی را رد می‌کند)؛ seller-panel.js (نمودار). **FilePond/CDN حذف شد** (WeblakShop از unpkg + back plugins می‌خواند).
6. **seller.css** (~۳۰۰ خط): لندینگ (هیرو/کارت مزیت/نقشه راه/مدارک/کمیسیون/FAQ/CTA)، auth (نوار مراحل/کارت رادیویی/باکس آپلود + پیش‌نمایش)، پنل (کارت آماد/نمودار)، فروشگاه عمومی (هدر/آواتار/آمار) + موبایل.

### دو باگ خودم در حین E2E (رفع شد)
1. **مسیر seller-auth.js در لایوت**: `js/pages/seller-auth.js` به‌جای `js/pages/sellers/seller-auth.js` → 404 → فرم‌ها native سابمیت می‌شدند و JSON پاسخ به‌عنوان document رندر می‌شد (ثبت‌نام کار می‌کرد ولی تجربه خراب). رفع؛ راستی‌آزمایی با `jQuery._data(form,'events')`.
2. **ارتفاع کانواس chartjs**: maintainAspectRatio:false + inline style → کانواس ۲۸هزار پیکسل بلند شد. رفع با wrapper ارتفاع‌ثابت + CSS important — اندازه نهایی 577×260.

### باگ لاتنت WeblakShop کشف‌شده (ثبت شد — کد میزبان دست‌نخورده)
- **فرم business-details فروشنده در WeblakShop هیچ‌گاه قابل ثبت نیست**: select شهر در آن کوئری `@php $cities=[] @endphp` خالی است، هیچ آبشار AJAXی هم ندارد → انتخاب شهر غیرممکن → ولیدیشن `city_id required` همیشه رد → فروشنده جدید در WeblakShop هرگز از مرحله ۱ رد نمی‌شود. سانیار آبشار `/province/get-cities` را بایند کرد (۴۲ شهر تهران لود شد).
- نکته معماری: WeblakShop پنل فروشنده را با assetهای ادمین (`asset('back/...')`) می‌سازد؛ سانیار الگوی پنل کاربر خودش را تعمیم داد.

### E2E مرورگر (تم فعال موقت، دسکتاپ 1280 + موبایل 390)
- **مسیر کامل ثبت‌نام موفق**: لندینگ (کروسل اسلایدر داینامیک + CTA) → ثبت‌نام (email+رمز+موبایل 09350005555) → **AJAX سبز** → صفحه OTP سانیار → تایپ ۵ رقم → **ارسال خودکار** → اطلاعات کسب‌وکار (سوییچ حقیقی؛ انتخاب استان تهران → **۴۲ شهر AJAX** → تهران؛ قرارداد تیک) → ثبت → مدارک (بلی مالیات → باکس سوم ظاهر؛ ۳ آپلود فوری سبز + پیش‌نمایش + حذف) → ثبت نهایی → تبریک → «ورود به پنل فروشندگان» → **ورود خودکار به داشبورد**.
- **داشبورد**: ۴ کارت آمار + نمودار chartjs درست داخل کارت (577×260) + کیف پول ۰ + منوی ۱۹ آیتمی؛ موبایل 390 بدون overflow (390=390) + دکمه شناور منو.
- **فروشگاه عمومی**: فروشنده تازه‌ثبت‌شده ۴۰۴ می‌دهد (status_documents=Waiting → checkIsFullActive رد) — رفتار درست میزبان؛ با تایید موقت مدارک → صفحه ۲۰۰ با هدر/آمار/گرید؛ افزودن واریانت دمو → کارت محصول رندر؛ کلیک «ارزان‌ترین» → URL با sort_type=cheapest + گرید AJAX (سازوکار category.js روی فروشگاه کار کرد).
- **ورود/خروج**: خروج GET → ورود با ایمیل+رمز → داشبورد؛ رمز غلط → جعبه قرمز «رمز عبور وارد شده صحیح نمی باشد.» روی همان صفحه.
- **صفر خطای کنسول/صفحه در کل نشست**؛ لاگ سرور فقط یک ERROR از fixture تست پرداخت (محیط testing، انتظاری). تأیید بصری VLM روی ۸ اسکرین‌شات: «بدون همپوشانی/شکستگی، RTL صحیح» در لندینگ/ثبت‌نام/OTP/فرم کسب‌وکار/مدارک/داشبورد(دسکتاپ+موبایل)/فروشگاه.

### نکات دیتایی/محیطی (برای نشست‌های بعد)
- OTP فروشنده ۵ رقمی از DB خوانده شد (NewSeller.code_mobile_verification) — NewUserCodeSent در constructor کد را دوباره می‌سازد.
- `fill` پلی‌رایت در خانه OTP با maxlength فقط ۲ رقم پخش می‌کند؛ در E2E باید رقم‌به‌رقم `press` شد (رفتار ابزار، نه باگ).
- فروشنده تازه ثبت‌نام‌شده تا تایید مدارک ادمین (status_documents=Accept) فروشگاه عمومی ۴۰۴ دارد.
- multi_vendor_system_status=true فعال بود (برای صفحه فروشگاه لازم است).
- `option('info_site_title')` دموی فعلی «سانیار» است؛ بنر بالای هدر alt/عنوان «NULL» دارد (دیتای دموی Banner — از فاز A روی همه صفحات، خارج از scope این فاز).
- **پاکسازی کامل دمو انجام شد**: فروشنده/اطلاعات/کیف پول/واریانت/قیمت/اطلاع‌رسانی‌ها/NewSeller/فایل‌های مدارک (uploads/sellers) حذف؛ sellers=0, newsellers=0; CURRENT_THEME بازیابی → WeblakShop؛ خانه ۲۰۰.

**کامیت:** تم → theme-saniar (77408a02) | میزبان (فقط این فایل) → docs/project-analysis | **فاز بعدی: H (۵۸) — بخش ۲ پنل فروشنده: مدیریت محصولات (index/find/create/edit/variant + قالب‌های داینامیک قیمت/مشخصات/فایل‌ها/سایز) — سنگین‌ترین بخش با ۱۵+ نمای باقی‌مانده، سپس سفارش‌ها/کیف پول/کمیسیون (I) و حمل‌ونقل/تیکت/نظر/آمار/پروفایل (J)**

## Phase 59 — قالب سانیار فاز H: مدیریت محصولات فروشنده (index / find / create / edit / variant)

**تاریخ:** 2026-10-24 | **برنچ میزبان:** docs/project-analysis (کد میزبان دست‌نخورده — ۱۰۰٪ در ریپوی تم) | **تم:** themes/Saniar کامیت 15a5afb7 → پوش به برنچ theme-saniar | **تست:** فاز ۱ (AdminPanelSmoke+Unit+Auth+Cart+Catalog+Checkout+Extra): **131 ✓ / 3 skipped** (364 assertions) | فاز ۲ (Security+Order+Payment+Performance): **102 ✓** (337 assertions)

### تحویل‌شده‌های فاز H (۱۳ فایل Blade + ۱ JS ~۶۳۰ خط + CSS — همه در ریپوی تم)
1. **۵ نمای اصلی** با طراحی سانیار (Bootstrap5 RTL + bi-icons، بدون هیچ پلاگین جدید):
   - **index**: فیلترها (عنوان دبونس/دسته چندانتخابی/موجودی/ویژه) + جدول AJAX با ردیف (تصویر/عنوان+شناسه/گروه/برند/بج وضعیت/تعداد تنوع/عملیات) + صفحه‌بندی سروری (perpage ۱۰/۲۵/۵۰ + اعداد پنجره‌ای) + مرتب‌سازی روی شناسه + شمارندهٔ نتایج.
   - **find**: جستجوی محصولات منتشرشدهٔ کل سایت (عنوان + دسته) با ستون «کمترین قیمت» و دکمهٔ «افزودن تنوع».
   - **create / edit**: فرم ۸-تبی راست‌چین (ناوبری pill عمودی دسکتاپ/اسکرولی موبایل): اطلاعات کلی (عنوان/انگلیسی/دستهٔ درختی با پیشوند عمق/نوع فیزیکی-دانلودی/برند/وزن/واحد/ماهیت/دسته‌های چندگانه/برچسب chips/توضیحات)، قیمت(تنوع)، فایل‌ها، تصاویر، مشخصات، سایزبندی، امتیاز ویژگی‌ها، سئو (slug خودکار کلاینت‌ساز از عنوان با regex یونیکد). edit مقادیر و ردیف‌های سروری را پر می‌کند + بج وضعیت فعلی + لینک مشاهده در فروشگاه.
   - **variant**: کارت محصول (تصویر با placeholder استاندارد no-image-product.svg/دسته/برند/کمترین قیمت/کمیسیون دسته) + هشدار تطابق مشخصات + ردیف‌های تنوع + سابمیت عادی سروری (ریدایرکت + توست).
2. **۸ پارشال**: form-body مشترک create/edit، prices-include (ردیف سروری با pi صریح)، prices-template (قالب JS)، files-include/template، specification-row (با si/gi صریح)، specification-template (گروه/ردیف/AbilityScore + مودال تأیید بارگذاری)، index-filters داخل خود index.
3. **seller-products.js** — سه حالت با data-attr روی تگ اسکریپت (بدون inline JS):
   - **دیتاتیبل سبک**: POST فرم‌انکد به apiIndex/apiIndexFind با query[title]/query[category_id][]/query[stock]/query[special] + pagination[page]/[perpage] + sort — رندر ردیف‌ها با escape کامل، صفحه‌بندی، مرتب‌سازی، مدیریت ریدایرکت سشن.
   - **فرم**: سوییچ فیزیکی/دانلودی (نمایش/مخفی تب‌ها + required پویا)؛ افزودن/حذف ردیف قیمت با بازنویسی نیم‌اسپیس (prices[i][…]) + محاسبهٔ زندهٔ قیمت نهایی؛ ردیف‌های فایل (download_files[i][…])؛ گروه/ردیف مشخصات (شمارنده‌های data-attr) + بارگذاری گروه‌های نوع مشخصات از endpoint جدید با مودال تأیید؛ سایزها (لود سایزهای نوع + افزودن ردیف مقدار)؛ امتیاز ویژگی‌ها؛ برچسب‌ها (chips دست‌ساز، CSV، بدون وابستگی)؛ گالری (آپلود فوری به image-store + پیش‌نمایش FileReader + جابه‌جایی با فلش + حذف با image-delete برای فایل‌های موقت + سینک CSV مخفی)؛ اعتبارسنجی کلاینت با فعال‌سازی تب مربوطه + توست؛ سابمیت AJAX FormData → «success» → ریدایرکت به لیست + هندل 422/419.
   - **variant**: افزودن/حذف ردیف + محاسبهٔ قیمت + گارد حداقل یک ردیف.
4. **seller.css +۱۸۰ خط**: تب‌های فرم، کارت ردیف تنوع/فایل، گروه مشخصات، chips، باکس آپلود، گرید گالری با اکشن‌های شناور، جدول، ریسپانسیو موبایل.

### دو endpoint کمکی seller-friendly در تم (ریشه: روت‌های ادمین پشت گارد Admin برای فروشنده 403 می‌دهند)
- `GET seller.products.spec-type-data?name=…` → JSON گروه‌ها/مشخصاتِ نوع (معادل spectypes.getdata ادمین که HTML بک‌آفیس برمی‌گرداند — اینجا JSON خام و رندر سمت کلاینت با طراحی سانیار).
- `GET seller.products.size-type-values/{sizetype}` → JSON سایزهای راهنما (معادل sizetypes.show ادمین).
- هر دو در ProductSellerController تم + روت‌های داخل گروه Seller؛ بدون تغییر میزبان.

### باگ‌های لاتنت WeblakShop/میزبان کشف‌شده (ثبت — کد میزبان دست‌نخورده)
1. **روت‌های کمکی فرم محصول فروشنده در WeblakShop همه برای فروشنده خراب‌اند**: labels autocomplete (admin.get-labels)، tags (admin.get-tags)، تولید slug (POST /product/slug)، بارگذاری گروه‌های نوع مشخصات (spectypes/spec-type-data) و لود سایزها (admin.sizetypes.show در data-action) — همه داخل گروه ادمین با گارد `Admin` (میدلور ریدایرکت به لاگین ادمین) → فرم فروشندهٔ WeblakShop این قابلیت‌ها را عملاً ندارد. سانیار: برچسب=chips محلی، slug=کلاینت‌ساز، دو نوع/سایز=endpointهای جدید تم.
2. **ناسازگاری id در فیلتر قیمت‌های فروشنده**: کنترلر قیمت‌ها را با seller_id=seller_info()->id (PK جدول sellers_info) می‌نویسد ولی prices_seller() و ویوهای edit/variant با seller_info()->seller_id (PK sellers) فیلتر می‌کنند — فقط وقتی دو id عدداً برابر باشند (که در جریان ثبت‌نام رخ می‌دهد) کار می‌کند. سانیار رفتار WeblakShop را عیناً حفظ کرد (برابری).
3. **تست Checkout «order summary» رفتار دوبارحسابی تخفیفِ WeblakShop را مستند می‌کند (۳۶۰,۰۰۰)** — فاز D سانیار این دوبارحسابی را در کپی خودش اصلاح کرده (۴۰۰,۰۰۰ صحیح)؛ به همین دلیل **مجموعهٔ تست همیشه باید با CURRENT_THEME=WeblakShop اجرا شود** (این بار اول با Saniar اجرا شد → ۱ شکست «انتظاری» → با WeblakShop ۱۳۱✓/۳skipped). کامنت خود تست می‌گوید «⚠️ باید در رفع اشکال بررسی شود».

### E2E مرورگر (تم فعال موقت، دسکتاپ 1280 + موبایل 390 — فروشندهٔ تست tinker با مدارک Accept/ACTIVE)
- **ورود → index**: جدول AJAX ۲ ردیف واقعی با بج وضعیت/عملیات. **create فیزیکی**: عنوان/دسته/وزن/برچسب chip → تب قیمت (انتخاب رنگ آبی + گارانتی، قیمت ۱۵م/تخفیف ۱۰٪ → قیمت نهایی زندهٔ ۱۳,۵۰۰,۰۰۰) → افزودن ردیف دوم/حذف → ثبت AJAX → ریدایرکت + توست؛ DB: محصول #۳۲۳۷۷ Waiting + قیمت 514551 (attrs=1,4) + برچسب + SellerVariant ✓.
- **edit**: مقادیر پیش‌فرض (وزن/قیمت/موجودی) → تغییر قیمت ۱۵م→۱۶م → ذخیره → DB به‌روز + تاریخچهٔ تغییرات قیمت (۲ رکورد) ✓.
- **variant محصول ۵**: ردیف جدید ۱۳.۵م/موجودی ۷ → سابمیت سروری → ریدایرکت؛ DB: قیمت 514552 برای فروشنده + SellerVariant ✓.
- **find**: جستجوی «گوشی» → ۵ نتیجه با کمترین قیمت (بازهٔ تنوع جدیدم در قیمت فروشگاه لحاظ شد) + دکمهٔ افزودن تنوع ✓.
- **گالری**: آپلود فوری ۲ تصویر (نام‌های tmp در CSV) → جابه‌جایی ترتیب (CSV به‌روز) → حذف تایل (فایل tmp از دیسک پاک شد) → ذخیره → گالری در /uploads/products ماندگار + tmp خالی ✓.
- **دانلودی**: سوییچ نوع → تب فایل‌ها → آپلود PDF واقعی + عنوان/قیمت ۲۵۰هزار → ثبت → DB: محصول #۳۲۳۷۸ + price با file (pdf + title) ✓.
- **اعتبارسنجی**: فرم خالی → توست «عنوان الزامی» + می‌ماند؛ نوع دانلودی بدون فایل → توست مربوطه + پرش به تب فایل‌ها ✓.
- **موبایل 390px**: index/create/تب قیمت همه scrollWidth=390 (بدون overflow)؛ دکمهٔ شناور منو با FAB سبد همپوشانی ندارد (اندازه‌گیری مستقیم: منو x=310 راست، collapzion x=3 چپ — گزارش VLM مثبت کاذب بود).
- **VLM**: ۴ اسکرین‌شات بررسی شد؛ یافتهٔ واقعی: fallback تصویر variant از 404-Error.svg (گمراه‌کننده) → با no-image-product.svg استاندارد میزبان جایگزین شد و مجدد تأیید شد. **صفر خطای کنسول/صفحه در کل نشست.**

### پاکسازی دمو
محصولات تست (#۳۲۳۷۷/#۳۲۳۷۸ + قیمت‌ها/تغییرات/گالری/فایل دانلودی/نقاط/سایز/دسته/برچسب + لیبل یتیم)، تنوع فروشنده روی محصول ۵، فروشندهٔ تست + info + wallet، اطلاع‌رسانی‌ها — همه حذف شد؛ sellers=0, products=5 (دموی اصلی), tmp=0, public/uploads پاک؛ CURRENT_THEME → WeblakShop؛ خانه ۲۰۰. لاگ: فقط ۴ ERROR محیط testing از fixture پرداخت (انتظاری).

**کامیت:** تم → theme-saniar (15a5afb7) | میزبان (فقط این فایل) → docs/project-analysis | **فاز بعدی: I (۶۰) — سفارش‌های فروشنده (index/show/print + shippings-status + not-completed + print-all) و کیف پول/کمیسیون فروشنده (wallet/commission)، سپس J: حمل‌ونقل (carriers/tariffs) + پرسش/تیکت/اعلان/آمار/پروفایل + صفحات محتوایی (بلاگ/تماس/درباره/FAQ/404)**

## Phase 60 — قالب سانیار فاز I: سفارش‌های فروشنده + کیف پول/کمیسیون (سفارش/مالی)

**تاریخ:** 2026-10-24 | **برنچ میزبان:** docs/project-analysis (کد میزبان دست‌نخورده — ۱۰۰٪ در ریپوی تم) | **تم:** themes/Saniar کامیت d9237692 → پوش به برنچ theme-saniar | **تست:** فاز ۱ (Unit+AdminPanelSmoke+Auth+Cart+Catalog+Checkout+Extra): **131 ✓ / 3 skipped** (364 assertions) | فاز ۲ (Security+Order+Payment+Performance): **102 ✓** (337 assertions) — ابتدا با دادهٔ تست E2E جدول orders آلوده بود (۴ شکست assertDatabaseCount)؛ پس از پاکسازی کامل، هر دو فاز سبز مطابق خط پایه.

### تحویل‌شده‌های فاز I (۱۴ نمای Blade + ۲ JS + ۱ لایه چاپ مستقل + CSS — همه در ریپوی تم)
1. **سفارش‌ها (۷ نما + ۴ پارشال)**:
   - **index**: ۹ فیلتر (نام خریدار/نام کاربری/شماره سفارش/وضعیت پرداخت/وضعیت ارسال/نام محصول/شناسه محصول/از-تا تاریخ شمسی) + جدول AJAX (خریدار+موبایل+یوزرنیم، مبلغ فروش من، ۲ بج وضعیت، بج «برگشت وجه») + انتخاب گروهی با چک‌باکس → نوار عملیات (پرینت فاکتورها/لیبل‌ها/برچسب‌ها + تغییر وضعیت گروهی + لغو انتخاب) + صفحه‌بندی سروری + مودال خروجی اکسل (۱۶ فیلد + فیلترهای جاری تزریق‌شده به فرم).
   - **show**: ۳ کارت خلاصه (فاکتور/فروشنده+کمیسیون/وضعیت+کد رهگیری) + کارت تغییر وضعیت ۸گانه + اطلاعات گیرنده (با لینک نقشه بیرونی به‌جای پلاگین نقشهٔ WeblakShop) + جدول اقلام با ویژگی‌ها/رنگ + جمع‌ها (تخفیف/اقلام/ارسال/قابل پرداخت) + مودال لغو با گردش مالی و شرط موجودی.
   - **not-completed**: اقلام در انتظار ارسال با شمارندهٔ pendingToSend + صفحه‌بندی سروری + حالت خالی دوستانه.
   - **چاپ**: print (فاکتور تک) / print-all (فاکتورهای گروهی — برخلاف WeblakShop سفارش‌های دانلودی هم فاکتور دارند) / shipping-forms (برچسب تمام‌عرض) / shipping-forms-min (لیبل ۱۰×۷cm) / shipping-form-single (برچسب تک‌مرسوله با روت جدید و دکمه در show) + پارشال‌های factor/shipping-form/shipping-form-min — همه روی **layouts/printable.blade.php مستقل سانیار** (بوت‌استرپ RTL تم + فونت iran-yekan + print.css اختصاصی) به‌جای back.layouts.printable؛ چاپ خودکار پس از لود تصاویر با js/pages/print.js (بدون inline JS).
2. **کیف پول/کمیسیون (۴ نما)**: کارت موجودی گرادیانی + مودال شارژ (مبلغ+درگاه داینامیک) + مودال برداشت (سقف موجودی) + جدول تاریخچه با بج نوع/وضعیت/status_pay + صفحهٔ تراکنش (۱۳ فیلد شامل درگاه/سفارش/پیگیری) | کمیسیون: ۳ کارت آماری (max/avg/count) + جدول درختی بازگشتی با بج «مستقیم/ارثی/صفر عمدی/پیش‌فرض» + جستجوی زندهٔ vanilla + راهنمای علائم.
3. **JS (۲ فایل + ۱ چاپ)**: seller-orders.js — حالت index (دیتاتیبل سبک با ردیف‌های seller-friendly، دبونس فیلترها، مرتب‌سازی، صفحه‌بندی پنجره‌ای، انتخاب گروهی، چاپ گروهی با ids[]، تغییر وضعیت گروهی، تزریق فیلتر به فرم اکسل) + حالت show (تغییر وضعیت → اگر canceled مودال → POST با reason+refund، کد رهگیری، ریلود + توست) | seller-commission.js (جستجوی ردیف‌ها) | print.js (auto-print + دکمه چاپ).
4. **API/کنترلر (کپی تم — بدون تغییر میزبان)**: apiIndex بازنویسی شد — eager-load user/items اسکوپ‌دار + ردیف تمیز (id/name/mobile/username/status/shipping_status از آیتم/refunded/items_count/quantity/price_seller/created_at شمسی/show_url/print_url) به‌جای OrderCollection خام (WeblakShop به links.seller_view و priceSeller ناموجود در JS تکیه داشت).

### اصلاحات امنیتی/مالی سانیار نسبت به کپی WeblakShop (۶ مورد — باگ‌های لاتنت ثبت‌شده)
1. **IDOR سفارش/چاپ/تاریخچه کیف پول بسته شد**: show/print/shipping-form/tracking-code با assertSellerOrder (بدون آیتم از این فروشنده → 404)؛ چاپ‌های گروهی و shippingsStatus با whereHas(items,seller)؛ wallet.show با بررسی wallet->seller_id — تست E2E: سفارش 99999 → 404، تاریخچهٔ کیف پول کاربر → 404.
2. **بازگشت وجه ۳ باگ**: (الف) بدون DB::transaction — با «کیف پول کاربر یافت نشد»، موجودی فروشنده کم و تاریخچه ثبت می‌شد ولی آیتم refunded نمی‌شد و کاربر چیزی نمی‌گرفت؛ (ب) جمع بدون ضرب در quantity (برای qty=2 کمتر از مبلغ واقعی برمی‌گشت: ۱۹,۶۹۵,۰۰۰ به‌جای ۳۰,۹۴۵,۰۰۰)؛ (ج) بررسی‌ها بعد از جهش مالی. سانیار: کل عملیات تغییر وضعیت+لغو در یک transaction، همهٔ بررسی‌ها (کیف پول کاربر/فروشنده + برگشت مضاعف) قبل از اولین mutation، مبلغ = Σ(price×qty)+ارسال. تست E2E: فروشنده ۴۲م→۱۱.۰۵۵م، خریدار +۳۰.۹۴۵م، آیتم refunded=30,945,000 ✓؛ لغو مجدد → خطای «قبلاً برگشت ثبت شده»؛ تغییر وضعیت بعد از refund → خطای گارد.
3. **خروجی اکسل فروشنده در WeblakShop همیشه ۵۰۰ بود**: فرم GET ولی seller() فقط به بگ request (POST) اضافه می‌شد — input() در GET از بگ query می‌خواند → $request->seller=null → خطای نمای میزبان orders-lists. سانیار: تزریق به هر دو بگ + پیش‌فرض export_type=excel (exportPrint تعریف‌نشده بود). تست: xlsx واقعی ۷.۷KB دانلود شد.
4. **TypeError لاگین فروشنده**: getLoginField($request->username) قبل از اعتبارسنجی → POST بدون username = 500. سانیار: validate پیش از فراخوانی → 302.
5. **روت‌های مرده حذف شد**: orders.multipleDestroy/update/destroy (متد متناظر در کنترلر وجود ندارد → هر فراخوانی BadMethodCall) + seller.commission.guide (متد showCommissionRates ناموجود). حذف گروهی WeblakShop عملاً خراب بود.
6. **کد رهگیری seller-friendly**: admin.orders.set-tracking-code پشت گارد Admin → در WeblakShop برای فروشنده 403. سانیار: POST seller.orders.tracking-code (روت + متد جدید در کپی تم) — آیتم‌های همین فروشنده به‌روز می‌شوند. تست UI→DB: 77777777777777 ✓.

### E2E مرورگر (تم فعال موقت، دسکتاپ 1280 + موبایل 390 — فروشندهٔ تست + ۳ سفارش ۶۲۲/۶۲۳/۶۲۴ + کیف پول)
- **index**: ۳ ردیف AJAX با escape کامل | فیلتر وضعیت+ارسال تعاملی (paid+sent → ۱ ردیف) | ریست | انتخاب ۲ ردیف → نوار گروهی + شمارنده | لغو انتخاب.
- **show (۶۲۳)**: تغییر وضعیت «تحویل داده شد» از UI → DB ✓ | کد رهگیری از UI → DB ✓ | مودال لغو باز شد و بدون تأیید بسته شد (وضعیت دست‌نخورده ماند) | ارقام کمیسیون/تسویه درست.
- **چاپ**: فاکتور (عنوان «سانیار» با fallback سه‌مرحله‌ای factor_title→info_site_title→config، ۵ ردیف، جمع‌ها: 25,500,000+45,000−425,000=25,120,000 منطبق بر priceSeller میزبان) | برچسب ۲مرسوله‌ای | لیبل ۱۰×۷ | body[data-saniar-print] auto-print.
- **wallet**: موجودی 11,255,000 (پس از بازگشت وجه) + ۴ تاریخچه + صفحهٔ تراکنش ۷ ردیفی | **commission**: درخت ۲۸ ردیف — ریشه ۵٪ صریح، فرزند ۸٪ صریح، نوه ۰٪ عمدی، ۱۴ بج «ارثی» (سامسونگ/شیائومی/نوکیا/هواوی/انر → ۸٪ به ارث) | جستجوی «گوشی» → ۲ ردیف.
- **موبایل 390**: هر ۵ صفحه scrollWidth=390 (بدون overflow) | **VLM روی ۹ اسکرین‌شات**: همه «سالم» | **صفر خطای کنسول/صفحه** — فقط ۲ ERROR لاگ که هر دو باگ لاتنت بالا بودند و رفع شدند.
- **نکته تست**: تست‌های DatabaseTransactions هستند؛ دادهٔ تست E2E باید قبل از اجرای مجموعه پاک شود (۴ شکست assertDatabaseCount('orders',0) با ۳ سفارش تست).

### پاکسازی دمو
فروشندهٔ تست + info + کیف پول + ۳ سفارش + آیتم‌ها + تاریخچه‌ها + کیف پول خریدار تست + کمیسیون‌های موقت دسته‌ها (۳ ردیف → NULL) — همه حذف/بازگردانی شد؛ sellers=0, orders=0, items=0, sellerWallets=0, cats-with-commission=0؛ CURRENT_THEME → WeblakShop؛ خانه ۲۰۰.

**کامیت:** تم → theme-saniar (d9237692) | میزبان (فقط این فایل) → docs/project-analysis | **فاز بعدی: J (۶۱) — حمل‌ونقل فروشنده (carriers/tariffs + holidays موجود) و پرسش و پاسخ + تیکت‌ها + اعلان‌ها + آمار + پروفایل، سپس فاز نهایی K: صفحات محتوایی (بلاگ/تماس/درباره/FAQ/404) + Livewire 4 SPA (wire:navigate) + سئو نهایی + تست‌های اختصاصی تم**

## Phase 61 — قالب سانیار فاز J: حمل‌ونقل + تیکت + پرسش‌وپاسخ + اعلان + پروفایل + آمار فروشنده

**تاریخ:** 2026-10-24 | **برنچ میزبان:** docs/project-analysis (کد میزبان دست‌نخورده — ۱۰۰٪ در ریپوی تم) | **تم:** themes/Saniar کامیت b8e6c660 → پوش به برنچ theme-saniar | **تست:** فاز ۱ (Unit+AdminPanelSmoke+Auth+Cart+Catalog+Checkout+Extra): **134 ✓ / 3 skipped** (364 assertions) | فاز ۲ (Security+Order+Payment+Performance): **102 ✓** (337 assertions) — هر دو دقیقاً منطبق بر خط پایه (دو بار اجرا شد: قبل و بعد از اصلاح نهایی کنترلر پرسش‌ها).

### تحویل‌شده‌های فاز J (۱۵ نمای Blade + ۱ پارشال AJAX + ۹ JS + ۲ CSS صفحه‌ای — همه در ریپوی تم)
1. **حامل‌های من (carriers): ۳ نما + پارشال شهرها**: index (جدول با تصویر/مرکز فروشگاه/پوشش/پس‌کرایه/وضعیت + دراپ‌داون عملیات + مودال حذف + مودال شهرهای AJAX با روت جدید seller.carriers.cities)، create/edit (فرم ۳ بخشی سانیار: اطلاعات کلی / مناطق و هزینه‌ها / بازه زمانی ارسال). **درخت شهرهای چک‌باکسی سمت سرور** جایگزین comboTree شد (بدون پلاگین جدید؛ با سوییچ استان و حالت indeterminate). پیش‌نمایش تاریخ‌های ارسال از `seller.holidays.get-start-dates` با کارت‌های شمسی و بج تعطیل/جمعه — در create و edit هر دو (باگ edit که روت admin.* می‌خواند رفع شد).
2. **تعرفه‌های ارسال (tariffs): ۳ نما**: index با چیز حامل + بازگشت، create (hidden carrier_id)، edit — همه روی روت‌های seller (باگ ویرایش WeblakShop که به `admin.tariffs.update/index` پوینت می‌کرد کاملاً شکسته بود). حذف با مودال تأیید.
3. **تیکت‌ها: ۳ نما**: index (جدول اولویت/وضعیت با بج‌های سانیار)، create (موضوع/اولویت/پیام/پیوست چندگانه)، show (چت حباب‌های `.comment` — پیام فروشنده active — با آواتار و دانلود فایل + هشدار تیکت بسته + فرم پاسخ AJAX).
4. **پرسش و پاسخ: ۲ نما**: index (فیلترهای status/ordering خودکار + جدول ۷ ستونه با ستون محصول + مودال lg)، show (پارشال AJAX بدون لایوت: سه حالت پرسش/پاسخ خود/پاسخ دیگران + ویرایش داخل مودال + پاسخ textarea).
5. **اعلان‌ها: ۱ نما**: فیلتر اولویت/ترتیب/فقط‌ناخوانده + آکاردئون bootstrap با بج اولویت و نقطه ناخوانده + `{!! message !!}` (HTML ادمین) + خواندن خودکار در باز شدن.
6. **پروفایل شما: ۱ نما (۶ تب pill)**: اطلاعات فروشنده (چیت‌های فقط‌خواندنی + دسته اصلی با optgroup — ریشه هم قابل انتخاب) / تماس و آدرس (آبشار استان-شهر با پیش‌پرشدن شهر فعلی) / حساب بانکی (شبا text+LTR) / مدارک (هشدار وضعیت + پیش‌نمایش ۳ مدرک + آپلود فقط در Reject + سوییچ مالیات) / قرارداد (مودال امضای econtract) / تغییر رمز. مودال تأیید «نیازمند بررسی مجدد کارشناسان» قبل از ثبت.
7. **آمار و بازدید: ۱ نما**: ۵ کارت آماری (seller-stat-card) + کارت تفکیک امروز + نمودار Chart.js دو تب «تعداد بازدیدها/بازدیدکنندگان یکتا» با ۸ pill دوره‌ای (روزانه/هفتگی/ماهانه/سالانه × ۲ تب) + بازه تاریخ شمسی اختیاری + متای کل/میانگین از اندپوینت‌های viewCounts/viewerCounts.
8. **زیرساخت مشترک**: نشان اعلان ناخوانده در سایدبار پنل (`notifications-count-number` + unreadNotifications())، فلش toast-warning در master + کلاس CSS نارنجی، seller-carriers.css و seller-profile.css صفحه‌ای (seller.css دست‌نخورده).

### اصلاحات امنیتی/ساختاری سانیار نسبت به کپی WeblakShop (۱۲ مورد — باگ‌های لاتنت ثبت‌شده)
1. **IDOR حامل‌ها**: edit/update/destroy/cities هر حاملِ هر فروشنده/سراسری را می‌گرفت → assertSellerCarrier (404). تست E2E: حامل ۱ → 404.
2. **IDOR تعرفه‌ها**: index/create/store با where seller_id + findOrFail؛ edit/update/destroy با assertSellerTariff (مالکیت حامل).
3. **IDOR پرسش‌ها**: show/update هر کامنتِ هر محصولی → assertSellerQuestion (commentable باید محصول فروشنده باشد).
4. **`Ticket::create(['message'=>...])` → SQL 500 همیشگی**: جدول tickets ستون message ندارد؛ پیام اول به TicketMessage منتقل شد.
5. **پیوست zip تیکت → کرش Intervention**: uploadOptimizedImage روی zip شکست می‌خورد؛ storeTicketFile: تصویر→بهینه‌سازی، zip→Storage خام (تست E2E: zip واقعی دانلود-پذیر ثبت شد).
6. **پاسخ فروشنده → SQL 500 همیشگی**: comments ستون seller_id ندارد (الگوی میزبان: user_id/admin_id)؛ پاسخ فقط با comment_id ثبت و pending می‌ماند.
7. **SQL 500 فیلتر اعلان ناخوانده**: scopeFilter میزبان `where('read')` روی notification_manages می‌زد (ستون فقط روی پیوت است) → کوئری دستی با wherePivot؛ read() هم مقاوم به ردیف پیوت غایب شد + read_at ثبت می‌شود.
8. **پروفایل location هرگز ذخیره نمی‌شد**: فرم location می‌فرستاد، کنترلر lat_and_long می‌خواند → یکسان‌سازی روی location (تست E2E: «35.700000,51.400000» در DB ثبت شد).
9. **status_documents دو جدول ناهمگام**: آپلود مجدد مدارک فقط sellers_info را Waiting می‌کرد ولی UI از sellers می‌خواند → هردو همگام می‌شوند.
10. **آمار فروشنده ۵۰۰**: view('seller.statistics.index') در هیچ فضای‌نامی نبود → front::sellers.panel.statistics.index + حذف latestVisitors/breakdown مرده.
11. **تورم آمار**: baseSellerQuery دو شرط عمومی `page_path like '%/store/%'` و `'%/shop%'` بازدید همهٔ فروشگاه‌ها را می‌شمرد → هر سه شرط به slug فروشنده اسکوپ شد.
12. **روات مرده حذف شد**: profile.store، questions.create/store/edit/destroy، carriers.show، tariffs.show، tickets.edit (همگی بدون متد در کنترلر → BadMethodCall) + ویرایش default_delivery_range هاردکد شد → bind واقعی + toast حذف «برند»→«روش ارسال» + شبا type=number→text.

### E2E مرورگر (تم فعال موقت، دسکتاپ 1280 + موبایل 390 — فروشندهٔ تست کامل + حامل/تعرفه/تیکت/پرسش/اعلان/بازدید)
- **حامل‌ها**: index با ردیف واقعی؛ **create فیزیکی**: عنوان/استان→۴۲ شهر (آبشار)→انتخاب شهر، select_city→درخت ۱۱۳۲ چک‌باکس ظاهر، user_select→۷ کارت پیش‌نمایش تعطیلات، بدون شهر → توست خطا (گارد کلاینت)، با ۲ شهر → ثبت → ریدایرکت؛ DB: حامل ۳۰۷ با cities=2, range=7, user_select ✓. **edit**: bind کامل شامل بازهٔ متنی ذخیره‌شده (رفع باگ هاردکد) + شهر فعلی پیش‌انتخاب ✓. مودال شهرها: گروه‌بندی استان ✓.
- **تعرفه‌ها**: بدون carrier → ریدایرکت به حامل‌ها + توست هشدار نارنجی ✓؛ جدول ۲ ردیف بج‌دار؛ **ایجاد فیزیکی** ۵۰۰۰گرم/۷۰هزار → DB ردیف سوم ✓.
- **IDOR**: /seller/carriers/1/edit → ۴۰۴ برند‌دار سانیار ✓.
- **تیکت‌ها**: index (بج متوسط/پاسخ‌داده‌شده)؛ show چت ۲ حباب (فروشنده amber + پشتیبان)؛ **پاسخ فیزیکی با zip ۱۹۴بایتی** → DB: msgs=3, status=pending, file=uploads/tickets/...zip ✓.
- **پرسش‌ها**: جدول + مودال AJAX (محصول/متن/فرم پاسخ)؛ **پاسخ فیزیکی** → DB: کامنت ۲۶ (comment_id=24, pending)، پرسش→accepted ✓.
- **اعلان‌ها**: باز کردن ناخوانده → POST read + پیام نمایش + **نشان سایدبار از ۱ به مخفی** ✓؛ فیلتر فقط‌ناخوانده → خالی بدون خطای SQL ✓.
- **پروفایل**: ۶ تب + bind کامل (شبا LTR، شهر تهران پیش‌انتخاب)؛ **ثبت فیزیکی بایو** → مودال تأیید → توست موفق؛ DB: status_work=EditProfile, bio ✓؛ **ثبت location** → DB ✓. باگ یافت‌شده در E2E: دستهٔ ریشهٔ ذخیره‌شده در optgroup غیرقابل‌انتخاب بود → ریشه به‌عنوان گزینه «همهٔ زیرشاخه‌ها» اضافه شد.
- **آمار**: ۵ کارت (کل=۱۲)، fetch نمودار ۲۰۰ + متا ۱۲؛ سوییچ تب بازدیدکننده + دوره روزانه → به‌روزرسانی ✓.
- **موبایل 390**: هر ۶ صفحه scrollWidth=390 (بدون overflow) | **صفر خطای کنسول/صفحه/لاگ** در کل نشست.

### پاکسازی دمو
فروشندهٔ تست + info + کیف پول + variant/قیمت + کامنت‌های ۲۴/۲۵/۲۶ + تیکت ۳ + پیام‌ها + فایل zip + حامل‌های ۳۰۷/۳۰۸ + تعرفه‌های ۳ گانه + اعلان‌ها + ردیف‌های پیوت + ادمین تست + ۱۲ بازدید — همه حذف شد؛ sellers=0, tickets=0, carriers=0, admins=0, کامنت‌های محصول فقط دموهای قبلی (۷/۸/۹)؛ public/uploads/tickets پاک؛ CURRENT_THEME → WeblakShop.

**کامیت:** تم → theme-saniar (b8e6c660) | میزبان (فقط این فایل) → docs/project-analysis | **فاز بعدی: K (۶۲) — صفحات محتوایی (بلاگ/تماس/درباره/FAQ/404 اختصاصی‌تر) + تبدیل Livewire 4 SPA (wire:navigate) + سئو نهایی + تست‌های اختصاصی تم سانیار (فاز پایانی قالب)**

## Phase 62 — قالب سانیار فاز K (پایانی): صفحات محتوایی + Livewire 4 SPA + سئوی نهایی + تست‌های اختصاصی تم

**تاریخ:** 2026-10-24 | **برنچ میزبان:** docs/project-analysis (کد میزبان دست‌نخورده — ۱۰۰٪ در ریپوی تم) | **تم:** themes/Saniar کامیت eec5ac2d → پوش به برنچ theme-saniar | **تست:** فاز ۱ (Unit+AdminPanelSmoke+Auth+Cart+Catalog+Checkout+Extra): **134 ✓ / 3 skipped** (364 assertions) | فاز ۲ (Security+Order+Payment+Performance): **102 ✓** (337 assertions) | **تست اختصاصی تم (جدید):** `php vendor/bin/phpunit -c themes/Saniar/phpunit.xml` → **24 ✓** (95 assertions) — بدون تغییر میزبان (phpunit/TestCase/تست‌ها همه در ریپوی تم با بوت‌استراپ به vendor میزبان)

### تحویل‌شده‌های فاز K (۱۳ نمای Blade + ۲ پارشال کامنت + ۳ ویجت وبلاگ + ۵ JS + ۲ CSS — همه در ریپوی تم)
1. **وبلاگ (blogs/index)**: لندینگ ویجت‌محور (همان قرارداد WeblakShop — widgets صفحه posts) + سربرگ اختصاصی (جستجو → blog/search + دکمه آرشیو) + نوار دسته‌ها (navbar-blog مرجع با اسکرول افقی) + گرید سه‌کاره blog-news-item + اسلایدر blog-item؛ **fallback کاملاً داینامیک** برای نصب بدون ویجت (آخرین/پربازدید/دسته‌ها مستقیم از DB).
2. **آرشیو مقالات (articles/index + ۲ پارشال)**: breadcrumb + pills مرتب‌سازی (۵ حاله) + جستجوی dبونس‌دار (q) + chips فیلترهای فعال با حذف تک‌تک + شمارندهٔ نتایج فارسی + کارت نویسنده (با فیلتر profile) + گرید کارت (ویدئو/پادکست/انتخاب سردبیر بج‌دار) + حالت خالی دوستانه + صفحه‌بندی/مرتب‌سازی AJAX (JSON: data/pagination/total — بدون ریلود).
3. **صفحهٔ مقاله (articles/show + ۲ پارشال)**: عنوان/نویسنده/تاریخ/بازدید/زمان-مطالعه (محاسبه سروری) + تصویر شاخص + خلاصهٔ رنگی + محتوا + تگ‌ها + لایک AJAX (مهمان با IP) + اشتراک تلگرام/واتساپ/کپی-لینک + سایدبار sticky (آخرین مطالب + هشتگ‌های داغ + دسته‌بندی‌ها) + اسلایدر پیشنهادهای سردبیر + نظرات کامل (پندینگ‌های کاربر، لیست تاییدشده با پاسخ‌های تودرتو، لایک/دیسلایک کامنت با کوکی، پاسخ تاشو، صفحه‌بندی AJAX).
4. **تماس با ما (contact)**: کارت‌های تلفن/ایمیل/آدرس/شبکه‌های اجتماعی همه از optionها (با لینک نقشهٔ بیرونی به‌جای پلاگین سنگین — الگوی فاز I) + فرم AJAX با کپچای x-captcha میزبان + رفرش خودکار کپچا بعد از خطا/موفقیت + توضیحات بالا/پایین از تنظیمات (dt_*).
5. **صفحات داینامیک (pages/show)**: محتوای ادمین + شورت‌کدها + تایپوگرافی .dynamic-content (h2-h4/لیست/جدول/بلاک‌کوت/آکاردئون FAQ الگوی مرجع) + JSON-LD WebPage.
6. **۴۰۴ ارتقایافته**: کد بزرگ فارسی ۴۰۴ + جستجوی نجات + ۴ دسترسی سریع + پیشنهاد محصولات ویژه (کش ۱۰ دقیقه) + robots تک‌متای noindex (باگ متای تعارض با master رفع شد).
7. **SPA لایووایر ۴ (wire:navigate)**: @livewireStyles/Scripts در master؛ اسکریپت هسته با `data-navigate-once` (تزریق از ThemeServiceProvider→useScriptTagAttributes — جلوگیری از دوباره‌بوت Livewire/Alpine در ناوبری)؛ ۶ کتابخانهٔ vendor علامت navigate-once؛ اسکریپت‌های init (app.js/صفحه‌ای) مجدد اجرا می‌شوند؛ wire:navigate روی لینک‌های ساختاری (لوگو/فوتر/نوار موبایل/فیلترها) + **spa-navigate.js** سراسری برای همهٔ لینک‌های محتوایی با گاردها (target=_blank، خارجی، #، tel:/mailto:، مسیرهای ادمین/livewire، [data-no-spa])؛ رفع live-search.js برای SPA (الگوی readyState — DOMContentLoaded در ناوبری قبلاً رخ داده بود).
8. **سئو**: description داینامیک هر صفحه (متغیر description در extends با فالبک) + og:image/og:type/article:published_time/article:tag + canonical تک‌صفحه + robots بازنویسی‌پذیر + JSON-LDهای فوتر (Organization+WebSite+SearchAction — همه از دیتابیس) و مقاله (Article+BreadcrumbList) و صفحه (WebPage) و تماس (ContactPage) + sitemap.articles/statics رفع‌شده.
9. **تست اختصاصی تم**: `themes/Saniar/phpunit.xml` (env CURRENT_THEME=Saniar + بوت‌استراپ vendor میزبان) — ۲۴ تست: دودی همهٔ صفحات، قرارداد JSON-LD/OG/canonical، فلوی کامل لایک/دیدگاه/پاسخ/لایک-کامنت (UI→DB)، گارد «NULL»، ریدایرکت blog/search، حذف روت مرده، AJAX shapes، sitemap، SPA assets، کمکی saniar_seo_value.

### باگ‌های لاتنت رفع‌شده نسبت به کپی WeblakShop/میزبان (۱۲ مورد — کد میزبان دست‌نخورده)
1. **رابطهٔ categories روی Post اصلاً تعریف نشده** (پیوتcategory_post بدون استفاده؛ پست‌ها FK تکی category_id دارند): with/whereHas('categories') در کنترلر → RelationNotFound → **آرشیو و صفحهٔ مقالهٔ WeblakShop همیشه ۵۰۰**؛ سانیار: category تکی + فیلتر cat با type=postcat.
2. **روت مردهٔ blog/search**: به BlogController::search ناموجود پوینت می‌کرد (BadMethodCall) — پیاده‌سازی شد (ریدایرکت به آرشیو با q؛ خود آرشیو فیلتر عنوان گرفت).
3. **روت مردهٔ blogs/{post}/comments** (متد comments ناموجود) حذف شد؛ نظرات از مسیر articles.comments می‌آیند.
4. **getComments با status='approved'** در حالی که واژگان سیستم 'accepted' است → لیست AJAX نظرات همیشه خالی + بدون eager پاسخ‌های تاییدشده — هر دو اصلاح شد (روت GET comments/{post} هم که اصلاً وجود نداشت اضافه شد: متد مرده→اندپوینت واقعی).
5. **statics.xml همیشه ۵۰۰**: ۶ روت ناموجود (front.home/about/faq/blog/shop) → با ۵ روت واقعی (index/products/blog/articles/contact) جایگزین شد.
6. **articles.xml همیشه ۵۰۰**: پارامتر ['post'=>…] به روت {slug} → Missing parameter slug.
7. **og:image از ستون ناموجود pages.image** (asset(null)) + ۸. **canonical نادرست pages/show به روت front.articles.show** — هر دو اصلاح شدند.
9. **پیش‌فرض رشتهٔ literal «NULL»** ستون‌های meta_title/meta_description (باگ مهاجرت میزبان؛ ردیف بدون ستون → عنوان/توضیح «NULL») → کمکی saniar_seo_value با گارد، در pages/show + articles/show.
10. **کلیدهای '@context' در json_encode داخل Blade** به دایرکتیو Laravel 11+ @context تفسیر می‌شدند (schema مخدوش؛ در category-products فازهای قبلی هم لاتنت بود) → escape `@@` طبق سازوکار compileEscapes در ۵ فایل.
11. **متای تعارضی robots در ۴۰۴** (master index,follow + push noindex) → robots متغیری از extends ($robots).
12. **کامپوزر نشت‌آفرین @yield در twitter:image** که کل کامپایل master را تا انتهای فایل می‌شکست (پیدا شد در E2E: @stack('meta') خام!) → بازطراحی با متغیر $ogImage.

### E2E مرورگر (تم فعال موقت در .env، دسکتاپ 1280 + موبایل 390 — دادهٔ دمو: ۶ پست/۴ تگ/۳ کامنت/۲ صفحه)
- **SPA**: ناوبری دسته→آرشیو→مقاله با ماندگاری window marker (بدون ریلود کامل)؛ ری‌اینیت سوایپرها و **جستجوی زندهٔ هدر پس از ناوبری** (باگ readyState رفع‌شده؛ pre-fix FAILED، post-fix سبز)؛ wire:navigate ۴۴ لینک + data-navigate-once ۷ اسکریپت در خروجی.
- **آرشیو**: sort=most_viewed (URL+کارت‌ها)، جستجوی «شیائومی» → ۱ نتیجهٔ دقیق، فیلتر ترکیبی cat+sort+q، chips حذف فیلتر، شمارنده فارسی.
- **مقاله**: لایک ۰→۱ (active)؛ ثبت دیدگاه → prepend پندینگ + توست؛ پاسخ (collapse) → توست + بستن فرم؛ لایک کامنت ۱۲→۱۳؛ JSON-LD سه‌گانه در head (Article/BreadcrumbList/Organization-WebSite).
- **تماس**: کپچای غلط → توست «کد امنیتی اشتباه است» + رفرش خودکار؛ **کپچای واقعی با VLM خوانده شد → ثبت موفق UI→DB (contacts=1)** + ریست فرم.
- **صفحات**: آکاردئون FAQ (collapse show c2)؛ دربارهٔ ما + گارد «NULL» (عنوان NULL → عنوان واقعی پس از اصلاح)؛ JSON-LD WebPage در head.
- **۴۰۴**: notfound-code + جستجو + ۴ لینک + محصولات ویژه + robots:noindex تک‌متا.
- **sitemap**: ۱۳ فایل ۲۰۰ (articles/statics پس از رفع باگ)؛ home ۲۰۰ با WeblakShop پس از بازگردانی.
- **موبایل 390px**: هر ۶ صفحه scrollWidth=390 (بدون overflow) | **VLM روی ۶ اسکرین‌شات**: همهٔ صفحات «سالم و حرفه‌ای»؛ دو فلگ VLM (FAB سبد روی محتوا/واتساپ پایین-چپ) همان مثبت کاذبِ مستندشدهٔ فاز H و طراحی اصل قالب‌اند؛ عنوان‌هایی که VLM خواند توهم متن ریز بود (DOM واقعی صحیح). **صفر خطای کنسول/صفحه** در کل نشست.

### پاکسازی دمو
۶ پست + ۴ تگ + پیوت‌ها + ۵ کامنت + لایک‌های پست + ۲ صفحهٔ داینامیک + پیام تماس + اطلاع‌رسانی‌های وابسته + ادمین تست + ۱۲ فایل sitemap تولیدی — همه حذف شد؛ posts=0, tags=0, pages=0, contacts=0, admins=0, comments=0, postLikes=0؛ products=5 (دموی اصلی), orders=0؛ CURRENT_THEME → WeblakShop؛ خانه ۲۰۰.

**کامیت:** تم → theme-saniar (eec5ac2d) | میزبان (فقط این فایل) → docs/project-analysis | **پایان نقشهٔ راه قالب سانیار (فازهای A تا K کامل شد) — گام بعدی پیشنهادی: ادغام theme-saniar در شاخهٔ اصلی + فعال‌سازی تدریجی تم در محیط staging + تست رگرسیون کامل با CURRENT_THEME=Saniar روی مجموعهٔ ۲۳۶ تست**

## Phase 63 — ادغام theme-saniar در شاخهٔ اصلی + رگرسیون کامل ۲۳۶تایی با CURRENT_THEME=Saniar

**تاریخ:** 2026-10-24 | **برنچ میزبان:** docs/project-analysis | **تم (مخزن مستقل themes/Saniar):** کامیت ادغام d9054ca1 + رفع‌ها 8cd755fe (هر دو برنچ master و theme-saniar)

### ۱) ادغام theme-saniar در شاخهٔ اصلی (بدون force push)
- برنچ پیش‌فرض مخزن گیت‌هاب (`master`) هنوز اسنپ‌شات قدیمی اپ کامل بود (adaccbcd) و با theme-saniar واگرا بود (۲۶۹ در برابر ۱۱ کامیت) → پوش مستقیم رد می‌شد و force پوش ممنوع.
- راه‌حل: **کامیت ادغام واقعی دوعوالد d9054ca1** — والد اول adaccbcd (master قبلی)، والد دوم eec5ac2d (فاز K)؛ درخت نهایی دقیقاً = درخت theme-saniar (۱۷٬۸۵۴ فایلِ فقط-master حذف؛ ۵ تداخل add/add روی .gitignore/config/general.php/package.json/phpunit.xml/webpack.mix.js با theirs حل شد).
- نتیجه: پوش **fast-forward** (`adaccbcd..d9054ca1`) — تاریخچهٔ هر دو خط حفظ شد و اکنون شاخهٔ اصلی مخزن = بستهٔ مستقل تم سانیار.
- همگن‌سازی: برنچ theme-saniar هم با `master:theme-saniar` به 8cd755fe رسانده شد تا هر دو برنچ یکسان بمانند.

### ۲) رگرسیون کامل با CURRENT_THEME=Saniar — هدف فاز: کشف رگرسیون‌های پورت
اجرا: `CURRENT_THEME=Saniar php vendor/bin/phpunit …` (dotenv حالت immutable دارد → env خط فرمان بر .env غلبه می‌کند؛ `config('general.current_theme')` از env می‌خواند).

**اولین اجرا: ۲ خطا از ۲۳۶** — هر دو ریشه‌یابی و رفع شد:

1. **🔒 رگرسیون امنیتی واقعی — گاردهای فاز ۴۴ میزبان در پورت سانیار جامان بود** (کشف با SecuritySettingsTest::customer_login_honeypot_traps_ip): بلوک ربات‌گیر (هانی‌پات + تلهٔ زمان + کپچای ورود مشتری) در `Check_Mobile_Email` سانیار وجود نداشت و قفل بروت‌فورس `CheckPassword` هم نبود → پورت کامل: ۳ use (BotDetector/CaptchaService/SecuritySettings) + گارد سه‌گانه در Check_Mobile_Email + سه نقطهٔ RateLimiter در CheckPassword (بررسی اولیه / پاک‌سازی پس از ورود موفق / ثبت تلاش ناموفق). فرم ورود سانیار فیلدهای هانی‌پات را داشت ولی هیچ‌کس آن‌ها را اعتبارسنجی نمی‌کرد!
2. **CheckoutTest::order_summary_breaks_down_prices_correctly** (۴۰۰٬۰۰۰ در برابر ۳۶۰٬۰۰۰): سانیار در فاز D «دوبار‌حسابی تخفیف کالایی» را که خود تست با ⚠️ مستند کرده بود اصلاح کرده (totals از قبل با discount_price هستند؛ فقط کوپن کسر می‌شود؛ `discount_amount` زیر هر دو تم یکسان = ۴۰٬۰۰۰) → تست میزبان **هوشمند به تم** شد (WeblakShop=۳۶۰٬۰۰۰ رفتار فعلی سرور / Saniar=۴۰۰٬۰۰۰ اصلاح‌شده) و زیر هر دو تم سبز است.
3. **OrderTest::order_history_shows_own_orders**: نمای سانیار شماره سفارش را بدون `#` رندر می‌کرد → `#{{ $order->id }}` مطابق قرارداد پلتفرم («سفارش #۱۲»).

**باگ لاتنت مجموعهٔ تست تم** (`SaniarContentPagesTest::test_article_show_renders_seo_and_schema`): تست به دیتای دموی حذف‌شدهٔ فاز ۶۲ وابسته بود — سایدبار «آخرین مطالب» پست جاری را کنار می‌گذارد (`where('id','!=',…)`) و بلوک «هشتگ‌های داغ» پشت `@if($hotTags->isNotEmpty())` است؛ با posts=0/tags=0 دو رشته هرگز رندر نمی‌شدند → تست خودکفا شد (پست دوم + تگ اختصاصی داخل تراکنش تست).

### نتیجهٔ نهایی رگرسیون (همه سبز)
- فاز ۱ (Unit+AdminPanelSmoke+Auth+Cart+Catalog+Checkout+Extra): **۱۳۴ ✓ / ۳ skipped (۳۶۴ اظهار)** — دقیقاً برابر خط پایهٔ WeblakShop
- فاز ۲ (Security+Order+Payment+Performance): **۱۰۲ ✓ (۳۳۷ اظهار)** — دقیقاً برابر خط پایه
- مجموعهٔ اختصاصی تم (`phpunit -c themes/Saniar/phpunit.xml`): **۲۴ ✓ (۹۵ اظهار)**
- تک‌تست چک‌اوت زیر WeblakShop (پیش‌فرض) هم مجدداً تأیید شد — سوییچ تم اثری بر خط پایه ندارد.
- پاکسازی: ۲ فایل sitemap تولیدی تست‌ها حذف شد؛ CURRENT_THEME → WeblakShop.

**کامیت:** تم → master (d9054ca1 ادغام + 8cd755fe) و theme-saniar (8cd755fe) | میزبان (تست هوشمند چک‌اوت + این فایل) → docs/project-analysis | **گام بعدی پیشنهادی: فعال‌سازی CURRENT_THEME=Saniar در محیط staging + E2E مرورگر مسیرهای طلایی (خانه/کاتالوگ/سبد/پرداخت/پنل) روی دیتای واقعی + تصمیم دربارهٔ تم پیش‌فرض نصب‌کننده برای نصب‌های تازه**

## Phase 64 — بازار: قفل نصب همزمان + مودال پیشرفت تمام‌صفحهٔ بلوکه‌شده (درخواست کاربر)

**تاریخ:** 2026-10-24 | **برنچ میزبان:** docs/project-analysis

### درخواست کاربر
> «وقتی یک پکیج یا قالب درحال نصب هست، دیگر نتواند یک پکیج یا قالب دیگر را همزمان نصب کند، پس نصب همزمان نداشته باشیم. وقتی مودال نصب باز هست و درحال نصب هست، قابل بستن نباشه و جاهای دیگه از صفحه هم قابل کلیک نباشه.»

### وضعیت قبل
- نوار پیشرفت نصب یک **تُستِ شناور پایین‌صفحه** بود (نه مودال): همیشه دکمهٔ بستن داشت، کل صفحه آزاد بود و می‌شد حین نصب، نصب دوم را هم شروع کرد.
- گاردها فقط **per-slug** بودند («نصب این پکیج همچنان در جریان است» برای همان slug) — نصب همزمان پکیج دوم/قالب باز بود.
- نصب قالب اصلاً مودال پیشرفت نداشت (فقط نوار بالای لیست + اسپینر کارت).
- با رفرش صفحه، هر ردی از «نصب در جریان» در UI گم می‌شد (نوارِ بالای لیست می‌ماند ولی مودال نه).

### رفع‌ها

**۱) قفل سراسری نصب سمت سرور — منبع حقیقت = دیتابیس (مقاوم حتی برای چند تب/چند ادمین)**
- `PackageIndex::hasOtherInstallationInProgress(?string $exceptSlug)` — رکورد `updating` تازه (پنجرهٔ تازگی **۱۰ دقیقه‌ای**، هم‌قرارداد `syncInstalledState`) در InstalledModule **یا** InstalledTheme = قفل فعال. پنجرهٔ ۱۰ دقیقه‌ای تا رکورد گیرکردهٔ updating (مثل کرش worker صف) قفل دائمی نسازد (`failed()` هر دو job هم رکورد را failed می‌کند؛ این فقط بیمهٔ جانبی است).
- `PackageIndex::isSlugInstalling($slug)` — آیا همین slug (پکیج/قالب) در حال نصب است؟ (پیام متمایز «نصب این مورد همچنان در جریان است…»).
- گارد در همهٔ دروازه‌های نصب: `startInstall` (نصب/خرید/اشتراک/لایسنس پکیج)، `updateFromDetail` (آپدیت از مودال)، `startTemplatePurchase` (خرید قالب)، `reinstallTemplate` (نصب مجدد قالب)، و `PackageInstalled::updateModule` (آپدیت از صفحهٔ ماژول‌های نصب‌شده).
- **مسیر callback پرداخت هم بسته شد** — `PackagePaymentCallback` (پکیج و قالب): اگر حین بازگشت از درگاه نصب دیگری در جریان باشد، نصب صف نمی‌شود؛ خرید `markAsPaid` می‌شود (لایسنس/توکن ذخیره) و پیام هدایت به «بررسی و نصب»/«نصب مجدد قالب» (بدون پرداخت مجدد) نمایش داده می‌شود.
- fail-safe جانبی: `$package['active_versions']` در `startInstall` به `collect($package['active_versions'] ?? [])` تغییر کرد — پاسخ ناقص API دیگر warning→exception نمی‌دهد (در تست‌ها warningها ErrorException می‌شوند و همین باعث کشف شد).

**۲) مودال پیشرفت تمام‌صفحهٔ بلوکه‌شده (blade — packages/index.blade.php)**
- نوار شناور پایین‌صفحه → **مودال مرکزی تمام‌صفحه** (`fixed inset-0 z-[100]`، بالاتر از همهٔ مودال‌های z-90).
- حین نصب (`status=installing`): **بدون دکمهٔ بستن، بدون کلیک بک‌دراپ، بدون Esc** — اورلی تمام‌صفحه همهٔ کلیک‌های صفحه را می‌گیرد (غیرقابل بستن + صفحه قفل).
- پس از پایان (موفق/ناموفق): بستن با دکمهٔ بستن/دکمهٔ «بستن» پانویس/کلیک بک‌دراپ/Esc → `dismissProgress`.
- پانویس راهنما حین نصب: «تا پایان نصب، این پنجره قابل بستن نیست و صفحه قفل است.»
- گرید ۶ مرحله فقط برای پکیج رندر می‌شود (نصب قالب لاگ مرحله‌ای ندارد).

**۳) نصب قالب هم از مودال پیشرفت می‌رود (unified progress)**
- `progressKind: package|template` + `pollTemplateProgress()` — وضعیت کلی InstalledTheme (updating/installed/failed + last_error).
- `refreshAfterTemplateInstall($slug)` (الگوی `afterInstallQueued` با method_exists) از `dispatchTemplateInstall` — مودال بلوکه‌شده برای قالب هم باز می‌شود → درخواست کاربر «پکیج یا قالب» کامل پوشش داده شد.

**۴) فرار از قفل با رفرش بسته شد (`resumeActiveInstall` در mount)**
- اگر رکورد updating تازه‌ای باشد (پکیج/قالب — تازه‌ترین بر اساس updated_at)، مودال پیشرفت در load بعدی **دوباره باز می‌شود**؛ با رفرش یا بازگشت از درگاه نمی‌توان از قفل فرار کرد.

### تست‌ها — `tests/Feature/Extra/InstallLockTest.php` (۱۱ تست جدید)
مسدودی متقاطع (پکیج↔پکیج، قالب→پکیج، پکیج→قالب خرید/نصب‌مجدد)، پیام متمایز same-slug، **آزاد شدن قفل پس از پایان نصب**، **رکورد stale (۱۱ دقیقه) قفل نمی‌سازد**، باز شدن مودال قالب‌محور + پانویس قفل، resume مودال پس از رفرش + **عدم وجود هر مسیر بستن (dismissProgress) در DOM حین نصب**، بستن‌پذیری پس از پایان، reset کامل با dismissProgress.

### تست جهش
گارد قفل در `startInstall` موقتاً با `if (false && …)` باطل شد → دقیقاً ۲ تست مسدودی پکیج قرمز شدند → بازگردانی → ۱۱/۱۱ سبز.

### نتیجهٔ کامل (MariaDB sandbox، دو فاز در یک دستور با start/wait DB)
- فاز ۱ `(AdminPanelSmoke|Auth|Cart|Catalog|Checkout|Extra)`: **۱۳۵ ✓ (۳۷۴ اظهار)** = ۱۲۴ قبلی + ۱۱ جدید
- فاز ۲ `(Order|Payment|Performance|Scale|Security)|Unit`: **۱۵۰ ✓ / ۳ skipped (۴۶۸ اظهار)** (skipهای شناخته‌شدهٔ ConcurrentUsers)
- جمع: **۲۸۵ تست سبز** — ThemeMarketTest ۲۰ ✓ بدون رگرسیون

**کامیت:** میزبان (PackageIndex + ۲ تریت + PackagePaymentCallback + PackageInstalled + blade + ۱۱ تست + این فایل) → docs/project-analysis | **گام بعدی پیشنهادی: E2E مرورگر جریان نصب (باز شدن مودال بلوکه، غیرقابل بستن بودن حین نصب، آزادسازی پس از پایان) روی دیتای واقعی + polling بین چند تب**

---

## Phase 65 — رفع کرش «تنظیم به عنوان قالب» روی هاست‌های بدون symlink+exec — سرویس ThemeAssetLinker

**تاریخ:** 2026-10-24 | **برنچ میزبان:** docs/project-analysis | **گزارش کاربر (محیط واقعی):** فعال‌سازی سانیار از پنل → `Call to undefined function Illuminate\Filesystem\exec()` — **تست:** Unit+AdminPanelSmoke+Extra: **70 ✓ / 3 skipped (192 اظهار)** شامل ۱۱ تست قفل نصبِ فاز ۶۴ همکار

### ریشه‌یابی
- `Filesystem::link()` لاراول روی لینوکس: اگر `function_exists('symlink')` نبود مستقیماً `exec('ln -s …')` می‌زد — بدون گارد روی وجود خود `exec`.
- روی هاست‌های اشتراکی سخت‌گیرانه هر دو تابع در `disable_functions`‌اند → `Error: Call to undefined function Illuminate\Filesystem\exec()` (پیشوند namespace = نشانهٔ قطعی تابع disable‌شده، چون PHP تابع غیرفعال را undefined حساب می‌کند).
- دکمهٔ «تنظیم به عنوان قالب» در ۳ نقطه بدون گارد/try-catch به `File::link()` وصل بود: `ThemeIndex::activate` (پنل جدید — بدون try/catch → ۵۰۰ خام)، `PackageInstallerService::activateTheme` (بازار)، `Back/ThemeController::update` (پنل قدیمی). `shop:link` از فاز ۲۶ از قبل زره‌پوش بود و سالم ماند.
- تصمیم کاربر: سانیار پیش‌فرض نصب‌های تازه نمی‌شود (پیش‌فرض = WeblakShop؛ هیچ تغییری لازم نبود).

### تحویل‌شده‌ها
1. **سرویس جدید `App\Services\ThemeAssetLinker`** — زنجیرهٔ idempotent: symlink سالم موجود → `symlink()` بومی (با @) → `exec(ln -s|mklink)` فقط با گارد `function_exists('exec')` → **کپی واقعی محتوا + مارکر `.shoplink-copy`**. فال‌بک کپی هرگز fail نمی‌کند؛ سناریوهای لبه: لینک شکسته (بازسازی)، کپی کهنهٔ خودمان (نوسازی در هر فعال‌سازی + ارتقای خودکار کپی→symlink وقتی هاست اجازه داد)، پوشه/فایل ناشناس (هرگز نابود نمی‌شود)، هدف غایب (RuntimeException با پیام فارسی).
2. **ادغام در هر ۳ نقطه**: ThemeIndex::activate حالا try/catch کامل + توست warning اختصاصی برای حالت کپی («symlink روی این هاست در دسترس نیست؛ دارایی‌ها کپی شدند»)؛ PackageInstallerService و ThemeController با همان سرویس.
3. **۸ تست واحد** (`tests/Unit/ThemeAssetLinkerTest.php`): میزبان سالم (symlink)، هاست سخت‌گیرانه (کپی+مارکر — شبیه‌سازی با پارامترهای allowSymlink/allowExec بدون نیاز به disable واقعی)، idempotency، نوسازی کپی کهنه، ارتقا کپی→symlink، symlink موجود دست‌نخورده، لینک شکسته، پوشهٔ ناشناس محفوظ، هدف غایب.
4. **شبیه‌سازی سناریوی واقعی کاربر** روی دارایی‌های واقعی سانیار: `linkOrCopy($target, tmpLink, false, false)` → mode=copy، css کپی شد، مارکر ثبت شد؛ روی public واقعی → mode=existing (بدون دست‌گذاری).

### نتیجه برای کاربر
روی هاست بدون symlink و exec، فعال‌سازی قالب از پنل حالا کار می‌کند: دارایی‌ها کپی واقعی می‌شوند (هر فعال‌سازی مجدد = نوسازی خودکار؛ اگر هاست بعداً symlink را باز کند، فعال‌سازی بعدی کپی را خودکار به symlink ارتقا می‌دهد).

**کامیت:** میزبان (سرویس + ۳ ادغام + ۸ تست + این فایل) → docs/project-analysis | **گام بعدی پیشنهادی: اجرای فعال‌سازی روی هاست واقعی کاربر (update + فعال‌سازی سانیار) و تأیید توست کپی + سرو CSS/JS از public/themes/Saniar**

---

## Phase 66 — مودال جزئیات قالب: نمایش کامل جزئیات HTML + مودال بزرگ‌تر + حالت قالب رایگان

**تاریخ:** 2026-10-26 | **درخواست کاربر:** «در تب قالبِ بازار، مودال جزئیات قالب جزئیات و جزئیات خرید را نمایش نمی‌دهد؛ مودال بزرگ‌تر شود (lg) و جزئیات HTML سمت بازار کامل رندر شود؛ قالب رایگان بدون بخش نوع خرید، فقط دکمه نصب + پیام رایگان»

### ریشه‌یابی
مودال جزئیات قالب (`$tDetail*` در `packages/index.blade.php`) فقط `short_description` را با `strip_tags` + سقف ۱۶۰ نویسه نشان می‌داد؛ **توضیحات کامل HTML (فیلدهای `long_description`/`description`/`content` API) اصلاً رندر نمی‌شد** — برخلاف مودال پکیج که «توضیحات کامل» دارد. عرض مودال `max-w-2xl` (۶۷۲px) برای جزئیات HTML بازاری تنگ بود. برای قالب رایگان، بخش «نوع خرید» (کارت‌های خرید عادی/اختصاصی) نمایش داده می‌شد که برای قالب رایگان بی‌معنا و گمراه‌کننده است.

### رفع‌ها
**۱) جزئیات کامل HTML (`index.blade.php` — مودال قالب)**
- بخش جدید «جزئیات قالب» با `{!! sanitize_package_html(...) !!}` (همان پاک‌ساز مودال پکیج که `<style>` توضیحات API را نگه می‌دارد و script/on*/iframe ناامن را حذف می‌کند) — رندر خام HTML بازار، مقاوم به سه نام فیلد: `long_description` ← `description` ← `content`.
- `short_description` هم از `strip_tags+limit` به رندر HTML پاک‌سازی‌شده تغییر کرد (اگر HTML باشد درست دیده می‌شود).

**۲) مودال بزرگ‌تر**
- `max-w-2xl` → **`max-w-4xl`** (۸۹۶px ≈ modal-lg/xl پنل قدیمی) + `max-h-[92vh]` مثل مودال پکیج — جزئیات HTML کامل و باز جا می‌شود.

**۳) حالت قالب رایگان**
- تشخیص رایگان: `$tIsFree = is_free || (discount_price ?? price ?? min_price) === 0` + نشان سبز «رایگان» در هیرو.
- **شاخهٔ جدید `@elseif ($tIsFree)`** جایگزین کل بخش نوع خرید: جعبهٔ سبز «این قالب رایگان است — نیازی به خرید نیست» + فقط دکمهٔ «نصب قالب» (با حالت loading «در حال شروع نصب…»)؛ «نوع خرید را انتخاب کنید»، «خرید عادی» و «خرید اختصاصی» دیگر برای قالب رایگان رندر نمی‌شوند.
- برچسب مردهٔ «دریافت رایگان» در شاخهٔ پولی حذف شد (قالب رایگان دیگر به آن شاخه نمی‌رسد).

**۴) گارد سرور-ساید (`InteractsWithTemplates::startTemplatePurchase`)**
- قالب رایگان همیشه `purchase_type=normal` خرید می‌شود — حتی اگر state سمت کلاینت روی exclusive بماند (gated UI کافی نیست؛ گارد سرور الزامی است). گارد تأیید دومرحله‌ای exclusive فقط برای قالب پولی فعال می‌ماند.

### تست‌ها — `ThemeMarketTest.php` (۴ تست جدید)
`template_detail_modal_renders_full_html_description` (رندر خام `<h2>/<ul>/<style inline>` + short_description HTML)، `template_detail_modal_for_free_theme_hides_purchase_type` (پیام رایگان + دکمه نصب + عدم رندر نوع خرید)، `template_detail_modal_for_paid_theme_keeps_purchase_types` (حفظ کارت‌ها و قیمت‌ها برای پولی)، `free_template_purchase_forces_normal_type` (state exclusive + قالب رایگان → job با purchaseType=normal). **تغییر فیکسچر:** `exclusive_purchase_requires_double_confirmation` حالا قالب پولی می‌سازد (قالب بدون قیمت = رایگان و همیشه normal — رفتار جدید عمدی است).

### تست جهش
(۱) گارد سرور به حالت قبل برگشت → `free_template_purchase_forces_normal_type` قرمز؛ (۲) `@elseif ($tIsFree)` → `@elseif (false && $tIsFree)` → تست مودال رایگان قرمز. بازگردانی → همه سبز.

### نتیجهٔ کامل (MariaDB sandbox، دو فاز در یک دستور با start/wait DB)
- فاز ۱ `(AdminPanelSmoke|Auth|Cart|Catalog|Checkout|Extra)`: **۱۳۹ ✓ (۳۹۲ اظهار)**
- فاز ۲ `(Order|Payment|Performance|Scale|Security)|Unit`: **۱۵۰ ✓ / ۳ skipped (۴۶۸ اظهار)**
- جمع: **۲۸۹ تست سبز** — ThemeMarketTest ۲۴ ✓ (۲۰ قبلی + ۴ جدید)

**کامیت:** میزبان (InteractsWithTemplates + blade مودال قالب + ۴ تست + این فایل) → docs/project-analysis | **گام بعدی پیشنهادی: اگر API بازار فیلد گالری/پیش‌نمایش قالب (screenshots) را می‌فرستد، گالری تصاویر مثل مودال پکیج اضافه شود؛ و رندر واقعی جزئیات HTML یک قالب واقعی روی لایو بازبینی چشمی شود.**


---

## Phase 67 — مودال خالیِ جزئیات قالب: ریشه‌یابی قطعی، قرارداد واقعی API (thumbnail_url)، گالری و بازطراحی مدرن

**تاریخ:** 2026-10-26 | **گزارش کاربر (با اسکرین‌شات):** مودال جزئیات قالب باز می‌شود ولی بدنه کاملاً خالی است؛ «هنوز جزئیات لود نمیشه»؛ تصاویر کارت‌های قالب هم لود نمی‌شود — «برای تصویر شاخص از thumbnail_url استفاده کن»؛ «اگر API گالری داشت اضافه کن»؛ «مودال زیبا و مدرن باشد»

### ریشه‌یابی (سه لایه)
1. **شکل پاسخ API** — `request()` در `PackageApiService` روی پاسخ 2xx با بدنهٔ غیرJSON فقط warning لاگ می‌کرد و **null برمی‌گرداند**؛ null وارد `Cache::remember` می‌شد و تا ۱۰ دقیقه هر کلیک، مودال خالی می‌داد. همچنین `$data['data'] ?? $data` روی wrapperهای متفاوت (template/result/دوتایی) شکست می‌خورد و auto-vivification روی null آرایهٔ ساختگی می‌ساخت.
2. **فیلد تصویر شاخص** — کارت‌ها و مودال از `thumbnail/cover/preview_image/image` می‌خواندند؛ فیلد واقعی API **`thumbnail_url`** است (تأیید کاربر) → همهٔ تصاویر شکسته.
3. **عدم خودتشخیصی UI** — اگر هر سه state (loading/error/detail) خالی بود، بدنهٔ مودال کاملاً سفید می‌ماند بدون هیچ راهنما/تلاش دوباره (اسکرین‌شات کاربر دقیقاً همین بود).

### رفع‌ها
**۱) `PackageApiService::request()`** — پاسخ 2xx با JSON نامعتبر حالا RuntimeException واضح می‌دهد («پاسخ سرور پکیج‌ها معتبر نبود») → دیگر null تولید/کش نمی‌شود؛ خطا در UI نمایش داده می‌شود.

**۲) `InteractsWithTemplates`**
- هلپر **`resolveTemplatePayload()`**: استخراج خودِ قالب از هر شکلی — `{data:{…}}`، `{template:{…}}`، `{result:{…}}`، wrapper دوتایی و بدنهٔ خام — با شناسایی slug/name و حداکثر ۳ لایه بازکردن.
- **پاسخ بی‌داده هرگز کش نمی‌شود** (`Cache::forget` در هر ۳ مسیر خطا) + لاگ تشخیصی (type + کلیدهای پاسخ) + پیام خطای قابل‌فهم برای کاربر.
- پراپرتی جدید **`tDetailSlug`** برای دکمهٔ «تلاش دوباره».

**۳) تصاویر** — کارت گرید و هیروی مودال: `thumbnail_url` اول + fallbackهای قبلی.

**۴) گالری** — خواندن `gallery`/`images`/`screenshots`/`preview_images` (آیتم رشته یا آرایه با url/path/thumbnail_url/image_url) → گرید ۲/۳ ستونی با لایت‌باکس (لینک جدید).

**۵) بازطراحی مدرن مودال** — هیروی تصویری تمام‌عرض (h-44/52) با گرادیان + نشان‌های شناور (رایگان/پولی/اختصاصی فروخته/فعال/نصب‌شده/به‌روزرسانی) + نام و متادیتا (نسخه/سازنده/دسته/دانلود/فروش) روی تصویر؛ دکمهٔ بستن شناور دایره‌ای؛ **فوتر چسبان** قیمت+CTA (نصب قالب / خرید و نصب / خرید اختصاصی و ادامه — با حالت loading)؛ بخش‌ها با آیکون و عنوان واضح؛ حالت بارگذاری انیمیشنی ping؛ **حالت خطا: آیکون + پیام + دکمهٔ «تلاش دوباره» + بستن — بدنهٔ مودال دیگر هیچ‌وقت خالی نمی‌ماند**؛ نصب ناموفق دوباره می‌تواند بخرد/نصب کند (شاخهٔ CTA فوتر اصلاح شد).

### تست‌ها — `ThemeMarketTest.php` (۵ تست جدید؛ جمع ۳۲)
`template_detail_modal_shows_thumbnail_url_and_gallery`، `template_detail_modal_shows_error_with_retry_for_unusable_payload` (با اثبات عدم کش‌شدن پاسخ بد: کلیک دوم دوباره API را صدا می‌زند)، `template_detail_payload_extraction_supports_wrapped_responses`، `template_detail_invalid_json_response_shows_clear_error`، `bazaar_template_cards_use_thumbnail_url`.

### تست جهش (دوجهته)
(۱) اعتبارسنجی پاسخ باطل شد (`if (false)`) → تست خطا/تلاش‌دوباره قرمز؛ (۲) `Cache::forget` شاخهٔ پاسخ‌بد حذف شد → قرمز (کلیک دوم دیگر به API نمی‌زند). بازگردانی با replace معکوس → همه سبز. (درس: بازگردانی جهش هرگز با `git checkout` نباید انجام شود — تغییرات فازِ کامیت‌نشده را هم می‌برد!)

### نتیجهٔ کامل (MariaDB sandbox، دو فاز در یک دستور با start/wait DB)
- فاز ۱ `(AdminPanelSmoke|Auth|Cart|Catalog|Checkout|Extra)`: **۱۴۴ ✓ (۴۰۹ اظهار)**
- فاز ۲ `(Order|Payment|Performance|Scale|Security)|Unit`: **۱۵۸ ✓ / ۳ skipped (۴۹۰ اظهار)**
- جمع: **۳۰۲ تست سبز** — ThemeMarketTest ۳۲ ✓

**کامیت:** میزبان (PackageApiService + تریت + PackageIndex + blade + ۵ تست + این فایل) → docs/project-analysis | **گام بعدی پیشنهادی: بازبینی چشمی مودال جدید روی لایو با یک قالب واقعی؛ اگر باز پاسخ ناقص آمد، کلیدهای لاگ‌شده در `laravel.log` («Template detail payload unusable») شکل دقیق پاسخ بازار را نشان می‌دهد و resolveTemplatePayload باید با همان گسترش یابد.**

## Phase 68 — تغییرنام تم به SaniarTheme + گارد ساختار قالب (ریشهٔ ۴۰۴ خام صفحه اصلی) + قرارداد ویجت‌ها + seed خودکار چیدمان

**تاریخ:** 2026-10-24 | **برنچ میزبان:** docs/project-analysis | **تم:** themes/SaniarTheme (مخزن مستقل) | **تست:** میزبان فاز ۱ (SaniarTheme): **۱۵۹ ✓ / ۳ skipped (۴۴۳ اظهار)** | فاز ۲: **۱۰۲ ✓ (۴۰۰ اظهار)** | اختصاصی تم: **۲۹ ✓ (۱۴۲ اظهار)** (۲۴ قبلی + ۵ قرارداد ویجت) | ترکیب ۴-فایلی حساس به ترتیب (Security+Checkout+Activation+Linker) ۶ اجرای متوالی سبز

### گزارش کاربر (محیط واقعی) و ریشه‌یابی
- «قالب جدید را فعال می‌کنم ولی صفحهٔ اصلی ۴۰۴ می‌دهد» + «فعال‌کردن قالب دیگر خطای Undefined array key children» — ریشهٔ اصلی: **تغییرنام دستی پوشهٔ تم روی هاست بدون هماهنگی namespace**. سازوکار ۴۰۴: `get_current_theme()` کلاس `Themes\{پوشه}\src\ThemeServiceProvider` را می‌سازد؛ با ناهم‌خوانی، فایل پیدا می‌شود ولی کلاسِ خواسته‌شده تعریف نمی‌شود → `class_exists` false → `loadTheme()` **بی‌صدا** provider را رد می‌کرد → هیچ روت فرانتی ثبت نمی‌شد → صفحهٔ اصلی ۴۰۴. کاوش کامل کد نشان داد «children» هیچ‌جا در جریان فعال‌سازی بدون گارد نیست (همهٔ `['children']`ها گارد دارند) → خطای کاربر از وضعیت ناسازگار (پوشه/namespace/کش کهنه) می‌آمده؛ گارد جدید حالا به‌جای هر رفتار عجیب، پیام فارسی دقیق می‌دهد. کشف جانبی در تست: **فراخوانی دوم class_exists روی همان کلاسِ ناموجود = include دوبارهٔ فایل = «Fatal: Cannot redeclare class»** — گارد جدید با تحلیل متن namespace (بدون include) از این تله پرهیز می‌کند.
- تصمیم کاربر: نام پوشه تم = **SaniarTheme** (پیش‌فرض نصب‌های تازه همچنان WeblakShop).

### تحویل‌شده‌ها
1. **تغییرنام کامل Saniar → SaniarTheme** (مخزن تم): پوشه + namespace در ۴۵ فایل PHP (`Themes\SaniarTheme\src`) + config (theme_name/asset_path/links) + phpunit.xml (CURRENT_THEME=SaniarTheme) + classmap وندور نوسازی‌شده (۴۴ ورودی جدید، صفر ورودی کهنه). تست چک‌اوتِ هوشمند به تم نیز به‌روز شد. برچسب‌های console.error در JS (cosmetic) دست‌نخورده ماند.
2. **سرویس جدید `App\Services\ThemeStructureGuard`** — اعتبارسنجی ساختار قالب قبل از ثبت CURRENT_THEME در هر ۳ نقطهٔ فعال‌سازی (ThemeIndex::activate پنل جدید / PackageInstallerService::activateTheme بازار / Back/ThemeController::update پنل قدیمی): class_exists تک‌مرحله‌ای؛ در صورت شکست، فایل provider **بدون include** (regex روی namespace) تحلیل می‌شود → پیام فارسی دقیق («فضای‌نام واقعی (Themes\OldName\src) با نام پوشه هم‌خوانی ندارد…»); اگر namespace درست بود ولی کلاس رزول نشد (classmap کهنه) → dump-autoload امن + تلاش مجدد. `loadTheme()` نیز حالا در صورت گم‌شدن کلاس **warning لاگ** می‌کند (پایان بی‌صدایی). پس از ثبت: config:clear + view:clear + route:clear تا سوییچ بلافاصله اثر کند.
3. **قرارداد ویجت‌های صفحهٔ اصلی (خواستهٔ صریح مالک)** — سه شکاف واقعی پیدا و رفع شد: (الف) ویجت اضافهٔ سانیار `products-slider` (اسلایدر محصولات — نمای اختصاصی) در config/HomeWidgets ثبت نشده بود → ثبت کامل (۱۰ option + rules، کلون ساختاری products-default-block)؛ (ب) کیس دیتای آن در get_widget_uncached نبود → ویجت همیشه خالی رندر می‌شد → case fall-through اضافه شد؛ (ج) تصاویر پیش‌نمایش ویجت‌ها در assets/widgets تم نبودند (۱۴ تصویر کپی/تکمیل شد + fullscreen-slider.jpg). **۵ تست قرارداد** (SaniarWidgetContractTest): هر @case صفحه اصلی ↔ نمای Blade در پوشهٔ widgets ↔ ثبت config ↔ وجود تصویر ↔ کیس دیتا.
4. **Seed خودکار چیدمان پیش‌فرض در اولین بازدید**: تابع مردهٔ `theme_first_config()` (کاشت ۱۵ ویجت: ۱۱ خانه + ۴ بلاگ) در MainController::index سانیار وصل شد — فعال‌سازی تازه روی نصب بدون ویجت، دیگر صفحهٔ خالی نمی‌دهد (idempotent؛ E2E: ۰ → ۱۵ ردیف با بازدید اول؛ بلوک‌ها واقعاً رندر شدند). WeblakShop دست‌نخورده (مرجع冻结).
5. **رفع باگ لاتنت فاز K**: `with('admin:id,full_name,image')` در blogs/index — full_name ستون نیست بلکه Accessor است → eager constraint واقعی: `id,first_name,last_name,image` (پیش از تغییرنام هرگز اجرا نمی‌شد چون ویجت‌های posts موجود بودند؛ پس از تغییرنام fallback اجرا شد و ۵۰۰ داد).
6. **ایمنی هم‌بارگذاری helpers دو تم**: ۱۸ تابع سطح‌بالای helpers سانیار با گارد `function_exists` (۴ تابع saniar_* از قبل گارد داشتند) — کشف در تست: با ترتیب‌دهیِ PHPUnit بر اساس cache مدت‌زمان، هر دو provider تم در یک پروسه لود می‌شدند → «Cannot redeclare production_throttle» (توابع هم‌نام دو تم) → Fatal. در production هر پروسه یک تم دارد (رفتار بدون تغییر)؛ در تست‌ها گاردها fatal را حذف کردند. WeblakShop دست‌نخورده.
7. **۲ تست فیچر فعال‌سازی** (ThemeActivationTest، از روت پنل قدیمی + Gate::before الگوی موجود): قالب شکسته (پوشه/namespace ناهم‌خوان) → ۴۲۲ + پیام فارسی + فایل .env **بیت‌به‌بیت دست‌نخورده**؛ قالب سالم → ۲۰۰ success + ثبت .env. مقاوم‌سازی جانبی: SecuritySettingsTest کپچای ادمین حالا option را صریح ست می‌کند (به وضعیت محیط DB وابسته نباشد).

### E2E مرورگر (سناریوی دقیق کاربر — دسکتاپ، سرور توسعه)
- **قالب شکسته BrokenDemo** (namespace عامدانه ناهم‌خوان): کلیک «تنظیم به عنوان قالب» → **توست خطای واضح فارسی** + CURRENT_THEME دست‌نخورده ✓ (پایان ۴۰۴ خام/خطاهای گمراه‌کننده).
- **SaniarTheme**: فعال شد → صفحهٔ اصلی **۲۰۰** (نه ۴۰۴) + دارایی‌ها از /themes/SaniarTheme/ (css/img 200) + seed خودکار ۱۵ ویجت در اولین بازدید + بلوک‌ها (۶ swiper) رندر شدند + بلاگ ۲۰۰.
- **برگشت به WeblakShop** (جایی که کاربر خطای children می‌گرفت): بدون هیچ خطا → ۲۰۰ + دارایی‌ها ✓. پاکسازی کامل (پوشه دمو، ادمین تست، captcha→on، CURRENT_THEME→WeblakShop، ۱۷ ردیف ویجت قدیمی Saniar حذف).

**کامیت:** تم → master/theme-saniar (تغییرنام + ویجت‌ها + seed + helpers guards) | میزبان (سرویس گارد + ۳ ادغام + loadTheme warning + ۲ تست + مقاوم‌سازی تست کپچا + CheckoutTest + classmap + این فایل) → docs/project-analysis | **گام بعدی پیشنهادی: استقرار روی هاست واقعی کاربر (پول + حذف پوشه دستیِ تغییرنام‌یافته + کپی themes/SaniarTheme از ریپوی تم + فعال‌سازی از پنل) و تأیید توست‌ها؛ اگر خطای children دوباره دید، لاگ laravel.log حالا هشدار گارد را با جزئیات ثبت می‌کند**
## Phase 69 — کرش «htmlspecialchars: array given» در مودال قالب: نرمال‌سازی فیلدهای آبجکتی API + cursor-pointer + لودینگ روی کارت‌ها

**تاریخ:** 2026-10-27 | **گزارش کاربر:** کلیک روی قالب در بازار → خطای `htmlspecialchars(): Argument #1 ($string) must be of type string, array given` (View: packages/index.blade.php)؛ درخواست: cursor-pointer روی کارت‌های قالب و پکیج + لودینگ روی خود کارت تا کامل شدن مودال (با همان اسپینر `ap-nav-spinner`).

### ریشه‌یابی
- پیام خطا دقیقاً `htmlspecialchars(...)` است نه `sanitize_package_html(): Argument #1 ($html)` → آرایه مستقیم به Blade `{{ }}` (یعنی `e()` → `htmlspecialchars`) رسیده؛ کاندیدها فیلدهایی هستند که مودال به‌صورت رشته echo می‌کند: **`author`/`category`** (در APIهای بازار معمولاً آبجکت: `{name}` / `{title}`)، `thumbnail_url` (`{url}`) و `name`.
- اثبات با تست جهش: حذف نرمال‌سازی → همان خانوادهٔ خطا (`sanitize_package_html(): Argument #1 ($html) must be of type ?string, array given` برای description آرایه‌ای) بازتولید شد.

### رفع‌ها
**۱) `InteractsWithTemplates` — لایهٔ نرمال‌سازی (`normalizeTemplateDetail()` پس از `resolveTemplatePayload`):**
- `name/slug/author/category/version` → `templateScalar()` (آبجکت با کلید name/title/label → رشته؛ وگرنه اولین رشته؛ وگرنه null).
- `thumbnail_url/thumbnail/cover/preview_image/image` → `templateScalar()` با کلیدهای url/src/path/large/original.
- `short_description/long_description/description/content` → `templateRichText()` (رشته | `{'html': …}` | آرایهٔ پاراگراف‌ها → پیوست با خط جدید).
- فیلدهای عددی/بولی (`price/discount_price/exclusive_price/min_price/downloads/purchases_count/is_free/is_purchased/is_sold_exclusively`) → مقدار غیراسکالر → null (fallback ویو فعال می‌شود).
- گالری: `templateGalleryUrls()` — ۶ فیلد ممکن (gallery/images/screenshots/preview_images/previews/media) × هر شکل آیتم (رشته | آبجکت با url/src/path/full/large/original/image) → لیست URL یکتا.

**۲) blade — لایهٔ دوم دفاع (گاردهای اسکالر در `@php` مودال و کارت گرید):** `$tName/$tAuthor/$tCategory/$tCover/$tLongDesc/$tShortDesc` همه با `is_scalar/is_string` امن شدند؛ بلاک `short_description` که مستقیم `sanitize_package_html($tDetail['short_description'])` می‌خواند (حفرهٔ لاتنت جهش) حالا از متغیر گاردشده می‌خواند؛ ردیف نسخه‌ها (`v{{$tVer['version']}}`) هم گارد اسکالر گرفت.

**۳) UX کارت‌ها (درخواست کاربر):**
- کارت قالب: `relative cursor-pointer` + **اورلی لودینگ روی خود کارت** (`wire:loading.flex` + `wire:target="openTemplateDetail('slug')"` — دقیقاً همان کلیک) با اسپینر SVG الگوی `ap-nav-spinner` + متن «در حال باز کردن جزئیات…»؛ `pointer-events-none` تا فقط نمایشی باشد.
- کارت پکیج: `relative` + همان اورلی برای `wire:target="openDetail('slug')"` (cursor-pointer از قبل داشت).
- مزیت `wire:target` پارامتری: فقط کارتی که روی آن کلیک شده لودینگ می‌گیرد، نه کل گرید.

### تست‌ها — `ThemeMarketTest.php` (۳ تست جدید؛ جمع ۳۵)
- `template_detail_modal_coerces_object_fields_to_strings` — پاسخ API با author/category/تصویر/description آبجکتی → رندر بدون کرش + رشته‌های استخراج‌شده + HTML خام + src تصویر (کِشندهٔ خطای کاربر).
- `template_detail_modal_normalizes_paragraph_arrays_and_object_gallery` — آرایهٔ پاراگراف‌ها (پیوست) + گالری از سه شکل آیتم (آبجکت url / آبجکت path / رشته).
- `bazaar_cards_show_pointer_and_per_card_loading_spinner` — cursor-pointer + `wire:target` اسپینر روی کارت قالب (`openTemplateDetail('moonlight')`) و کارت پکیج (`openDetail('pkg-a')`).

### تست جهش (دوجهته)
(۱) فراخوانی `normalizeTemplateDetail` باطل → هر دو تست نرمال‌سازی قرمز (بازتولید خطای کاربر)؛ (۲) `wire:target` اورلی کارت قالب مخدوش → تست سوم قرمز. بازگردانی با replace معکوس → همه سبز.

### نتیجهٔ کامل (MariaDB sandbox، دو فاز در یک دستور با start/wait DB)
- فاز ۱ `(AdminPanelSmoke|Auth|Cart|Catalog|Checkout|Extra)`: **۱۴۷ ✓ (۴۲۶ اظهار)**
- فاز ۲ `(Order|Payment|Performance|Scale|Security)|Unit`: **۱۵۸ ✓ / ۳ skipped (۴۹۰ اظهار)**
- جمع: **۳۰۵ تست سبز** — ThemeMarketTest ۳۵ ✓
- نکتهٔ محیطی: اجرای دوم با دو نمونهٔ mariadbd روی یک datadir تداخل کرد (۹۱ شکست کاذب) → pkill + راه‌اندازی تمیز → دقیقاً همان اعداد انتظار. تست دو فازی فقط با «pkill قبلی + start/wait + هر دو فاز در یک دستور» معتبر است.

**کامیت:** میزبان (تریت + blade + ۳ تست + این فایل) → docs/project-analysis | **گام بعدی پیشنهادی: اگر روی لایو باز فیلدی به شکل غیرمنتظره آمد، یک نمونهٔ پاسخ واقعی از `laravel.log` («Template detail payload unusable») یا لاگ شبکه مرورگر بفرستید تا نگاشت دقیقِ همان فیلد اضافه شود.**

---

## Phase 70 — قفل‌شدن Forbidden سراسری (تلهٔ ربات) + کرش Intervention هنگام ذخیرهٔ محصول + مودال قالب زده‌بیرون از صفحه + به‌هم‌ریختگی فایل‌منیجر

### گزارش کاربر (۴ مشکل هم‌زمان)
1. «کل سایت Forbidden می‌دهد — هر آدرسی» (هم‌زمان با کار سشن موازی روی themes)
2. افزودن محصول (درون‌ریزی دیجی‌کالا) هنگام ذخیره: `Intervention\Image\ImageManager::__construct(): Argument #1 ($config) must be of type array, string given — bootstrap/helpers.php on line 1559`
3. مودال جزئیات قالب بازار «بهم‌ریخته و از بالا/پایین صفحه زده بیرون»
4. admin/file-manager: پیش‌نمایش تصاویر نمی‌آید + مودال/گزینه‌های آپلود کاملاً به‌هم‌ریخته (اسکرین‌شات)

### ۱) Forbidden سراسری — ریشه: تلهٔ ربات (BotTrap) و قفل‌شدن خودی
مسیر واقعی رویداد: `BlockBots` (میان‌افزار سراسری) هر IPِ ثبت‌شده در `bot_traps` را از **کل سایت** با متن سادهٔ `Forbidden` و 403 جواب می‌دهد — دقیقاً همان چیزی که کاربر می‌دید. IP مدیر (بدون هیچ تخلفی) به یکی از سه شکل گرفتار شده بود: IP اشتراکی CGNAT اپراتور (ربات دیگرِ همان IP به تله مثل `/wp-admin` خورده)، پرشدن هانی‌پات فرم توسط autofill مرورگر خودِ کاربر، یا ضربهٔ اسکنر از یک IP مشترک. مشکل مهندسی: مسدودی **همیشگی** بود و هیچ راه‌فرار ادمینی نداشت (حتی `/admin` هم 403 می‌داد).

رفع چهارلایه:
- **انقضای خودکار ۲۴ ساعته** — `BotTrap::isBlocked` فقط IPهایی را می‌بندد که در ۲۴ ساعت گذشته تله خورده‌اند (`BLOCK_TTL_HOURS=24`)؛ ربات واقعی دوباره تله می‌خورد و شمارنده تازه می‌شود — امنیت حفظ، قفل ابدی منتفی.
- **بای‌پَس مدیر واردشده** — `BlockBots` برای `auth('adminPanel')->check()` همیشه پاس می‌دهد (مهاجم بیرونی نه سشن دارد نه با این bypass چیزی می‌گیرد).
- **عدم تله‌گذاری خودی** — `BotDetector::trap` برای کاربر/مدیرِ واردشده هیچ‌وقت رکورد نمی‌سازد (autofill دیگر نمی‌تواند مدیر را قفل کند).
- **فرمان اضطراری SSH** — `php artisan security:unblock` با `--list` / `ip` / `--all` + پاک‌سازی فوری کش (بدون انتظار ۱۰ دقیقه‌ای TTL کش)؛ رفع قفل از phpMyAdmin هم ممکن است (`TRUNCATE bot_traps;`) — برای سرور لایو کاربر این راه فرار فوری است.

### ۲) کرش ذخیرهٔ محصول (درون‌ریزی دیجی‌کالا) — ریشه: `new ImageManager(Driver::class)`
`uploadOptimizedImage` نام کلاس درایور را به‌صورت «رشته» به سازنده می‌داد. روی vendor سالم v3.11 سازنده `string|DriverInterface` می‌پذیرد، ولی روی هاست کاربر vendor قدیمی‌تر است (سازندهٔ `array $config` — همان پیام خطا) و TypeError می‌دهد. رفع: `new ImageManager(new Driver())` + fallback امن `new ImageManager(['driver' => new Driver()])` برای سازنده‌های آرایه‌ایِ قدیمی‌تر.
⚠️ **اقدام لازم روی سرور لایو:** vendor با `composer.lock` ناهم‌خوان است — بعد از `git pull` یک‌بار `composer install` (یا `composer update intervention/image`) اجرا شود؛ همین ناهم‌خوانی ریشهٔ «پیش‌نمایش فایل‌منیجر» هم بود (endpointهای `thumbnails/preview` آن با `Intervention\Image\Laravel\Facades\Image::read()` کار می‌کنند و با vendor قدیمی 500 می‌دهند).

### ۳) مودال جزئیات قالب زده‌بیرون از صفحه — ریشه: CSS کامپایلِ کهنه
ساختار مودال از فاز ۶۷ درست بود (`fixed inset-0` + `max-h-[93vh]` + بدنهٔ `overflow-y-auto`) ولی آخرین build یعنی `admin-DcHdNrUP.css` کلاس‌های جدید (`max-h-[93vh]`، `min-h-[320px]`، `h-44`، …) را نداشت → پنل بدون سقف ارتفاع، از بالا و پایین بیرون می‌زد. رفع: `npm run build:admin` دوباره اجرا شد → `admin-CluSKFvB.css` با همهٔ کلاس‌ها + سقف مودال برای هم‌خوانی با بقیه به `max-h-[90vh]` تغییر کرد و در CSS جدید تضمین‌شده (تست `compiled_admin_css_contains_template_modal_constraints`).

### ۴) فایل‌منیجر (admin/file-manager) — دو ریشه
- **به‌هم‌ریختگی آپلود/گزینه‌ها:** UI فایل‌منیجر alexusmai با **Bootstrap 4** نوشته شده ولی `vendor/file-manager/css/bootstrap.min.css` به v5.3.2 رفته بود (کلاس‌های `custom-file`، مودال v4 و… در v5 حذف شده‌اند) → مودال آپلود به‌صورت بلاک بازِ بی‌استایل رندر می‌شد (دقیقاً اسکرین‌شات کاربر). رفع: bootstrap به **v4.6.2** برگشت (bootstrap-rtl.css هم که از قبل مخصوص v4 بود حالا سازگار است).
- **پیش‌نمایش تصاویر:** همان مشکل vendor (بند ۲) — endpoint `thumbnails`/`preview` با Intervention v3 کار می‌کند؛ `composer install` روی سرور حلش می‌کند.
- **CDNهای فیلترشده:** `<link>`های `use.fontawesome.com` و `stackpath.bootstrapcdn.com` (در ایران فیلتر/کند) در `file-manager-iframe` و `ckeditor` به نسخهٔ لوکال `fontawesome.min.css` + `bootstrap.min.css` منتقل شدند (الگوی فاز ۱۶ در fmButton).

### تست‌ها (+۱۱: ۲۸۹ → ۳۰۰ + ۵ assets/UI = جمع ران ۳۰۵)
- `UploadOptimizedImageTest` (+۲): ذخیرهٔ موفق webp/png از فایل آپلودی و از مسیر (مسیر دیجی‌کالا) با کدِ رفع‌شده.
- `FileManagerViewTest` (+۵): دارایی‌های صرفاً لوکال iframe/ckeditor، سازگاری Bootstrap 4.6.2 (کلاس‌های `custom-file`/`input-group-prepend`)، فونت‌های FA لوکال، و وجود کلاس‌های قید مودال در CSS کامپایل admin.
- `PenetrationTest` (+۴): انقضای ۲۴ ساعته مسدودی (۲۳ ساعت هنوز مسدود/۲۵ ساعت آزاد)، بای‌پَس مدیر واردشده، فرمان `security:unblock` (لیست/IP/همه)، عدم تله‌گذاری کاربرِ واردشده.

### تست جهش (دوجهته، هر دو قرمز شدند و بازگردانی شدند)
- حذف بای‌پَس BlockBots → `logged_in_admin_never_gets_blocked` قرمز (بازتولید Forbidden کاربر) ✓
- حذف شرط TTL از `BotTrap::isBlocked` → `bot_trap_block_auto_expires` قرمز ✓
- (کرش Intervention روی vendor سالم سندباکس قابل بازتولید نبود — سازندهٔ v3.11 رشته را می‌پذیرد؛ همان ناهم‌خوانی vendor سرور کاربر؛ تست‌های آپلودکلِ مسیر را پوشش می‌دهند.)

### نتیجهٔ کامل (MariaDB sandbox)
- فاز ۱ `(AdminPanelSmoke|Auth|Cart|Catalog|Checkout|Extra)`: **۱۵۵ ✓ / ۱ skip (۴۶۹ اظهار)**
- فاز ۲ `(Order|Payment|Performance|Scale|Security)`: **۱۳۵ ✓** + `Unit`: **۱۵ ✓ / ۳ skip**
- جمع: **۳۰۵ تست سبز** — PenetrationTest ۲۴ ✓، ThemeMarketTest ۳۲ ✓
- ⚠️ نکتهٔ محیطی جدید: در فاز Performance/Scale با buffer pool پیش‌فرض، mariadbd حدود ۲.۲GB مصرف می‌کند و OOM-killer آن را می‌کشد (۹۵ شکست کاذب با `Connection refused`) → اجرای درست: `--innodb-buffer-pool-size=256M --performance-schema=OFF` + تقسیم فاز ۲ به سه دسته. این خط در سندباکس‌های کم‌حافظه الزامی است.

**کامیت:** helpers + BlockBots/BotTrap/BotDetector + SecurityUnblock + bootstrap 4.6.2 + bladeهای فایل‌منیجر + blade مودال (90vh) + CSS کامپایل admin (rebuild) + ۱۱ تست + این فایل → docs/project-analysis

---

## Phase 71 — کلید اصلی تلهٔ ربات در تنظیمات برنامه‌نویس + صفحهٔ زیبای مسدودسازی + ریشه‌یابی قطعی و رفع «کشته‌شدن ماریا‌دی‌بی توسط ConcurrentUsersTest»

### گزارش کاربر (۲ محور)
1. «رکورد bot_traps را از دیتابیس حذف کردم ولی Forbidden ادامه داشت» + «اگر Forbidden به‌خاطر bot_traps است صفحه باید ظاهر زیبا داشته باشد و بگوید بلاک شده‌ای» + «آیا می‌شود در تنظیمات برنامه‌نویس غیرفعالش کرد؟ اگر نمی‌شود اضافه‌اش کن»
2. «لودتست ConcurrentUsersTest در این سندباکس ماریا‌دی‌بی را می‌کُشد — اگر درست نشده، درستش کن و پوش کن»

### ۱) تلهٔ ربات — کلید اصلی + صفحهٔ مسدودسازی
- **ریشهٔ سردرگمی حذف رکورد:** `BotTrap::isBlocked` لیست آی‌پی‌های مسدود را **۱۰ دقیقه در کش** نگه می‌دارد؛ حذف دستی رکورد از دیتابیس تا انقضای کش اثر نمی‌کرد.
- **کلید اصلی (تنظیمات برنامه‌نویس → تب امنیت):** `SecuritySettings::botTrapEnabled()` (option `security_bot_trap_enabled`، پیش‌فرض روشن). خاموش ⇒ (الف) `BlockBots` هیچ IPای را مسدود نمی‌کند — حتی مسدودی‌های موجود فوراً بی‌اثر (راه‌قطع اضطراری)، (ب) `BotTrap::catch` هیچ تلهٔ جدیدی ثبت نمی‌کند (مسیرهای طعمه همچنان ۴۰۳ می‌مانند)، (ج) در لیست optionهای سفیدليست config/general ثبت شد.
- **صفحهٔ زیبای مسدودسازی:** سرویس `BlockPage` + ویو `errors/bot-blocked.blade.php` (مبتنی بر `errors.layouts.modern` — خودکفا بدون asset خارجی، چون کاربر مسدود به هیچ asset همین سایت هم دسترسی ندارد). دو حالت: `blocked` (مسدودی سراسری + نمایش آی‌پی + توضیح انقضای خودکار ۲۴ ساعته) و `trap` (فقط این مسیر ممنوع). هدرهای `no-store` تا پس از رفع مسدودی از کش CDN نیاید. JSON مشتری‌ها ۴۰۳ ساختاریافته می‌گیرند.
- **رفع مسدودی از UI تنظیمات برنامه‌نویس:** جدول تله‌های فعال ۲۴ ساعت اخیر (`activeTraps`) + `unblockIp` (حذف رکورد + `flushCache` — فوراً اثر می‌کند) + `unblockAllIps` + نکتهٔ کهربایی دربارهٔ کش ۱۰ دقیقه‌ای (پاسخ به سردرگمی کاربر).
- **رفع باگ جانبی:** کوئری سایت‌تیتر در `errors/layouts/modern` روی جدول options با ستون‌های اشتباه (`key/value` به‌جای `option_name/option_value`) بود → جایگزینی با helper امن `option()`.
- **۹ تست جدید** (`BotTrapSettingsTest`): کلید خاموش ⇒ مسدودی موجود بی‌اثر / ثبت تلهٔ جدید متوقف (طعمه‌ها ۴۰۳ می‌مانند) / کلید روشن ⇒ چرخهٔ کامل تله و مسدودی / صفحهٔ فارسی زیبا (عنوان + انقضا + آی‌پی) / JSON ۴۰۳ / ذخیرهٔ کلید از تنظیمات / رفع مسدودی تک‌IP و همه با پاک‌کردن کش / فیلتر ۲۴ ساعتهٔ لیست تله‌ها.

### ۲) ConcurrentUsersTest و کشته‌شدن ماریا‌دی‌بی — ریشه‌یابی قطعی
علامت: mariadbd وسط لودتست SIGKILL می‌شد؛ `dmesg` نشان داد OOM-killer با `anon-rss: ~2.3-2.4GB` آن را انتخاب می‌کند — **با وجود buffer pool ۹۶MB!** یعنی مقصر buffer pool نبود.

مسیر ریشه‌یابی (با general_log و pmap و بازپخش تک‌تک کوئری‌ها روی نمونهٔ تازهٔ ماریا‌دی‌بی):
- سرور تست بار (`php -S`) در محیط production اجرا می‌شود ⇒ `front_search_match` مسیر **FULLTEXT MATCH** را برمی‌دارد (در env testing مسیر LIKE است).
- بازپخش کوئری‌ها روی نمونهٔ تازه: کوئری جستجو (`MATCH … AGAINST` روی products) **به‌تنهایی ~۵۰۰-۵۱۱MB حافظهٔ گذرا** در ماریا‌دی‌بی می‌گرفت — کوئری‌های دیگر جمعاً < ۵MB.
- علت واقعی: **بادکردن ایندکس FULLTEXT** — تست مقیاس ۳۰هزار محصول را درج و حذف می‌کند؛ حذف در InnoDB فقط نشانه‌گذاری است و سطل wordها فقط با OPTIMIZE/recreate پاک می‌شود → فایل `FTS_..._INDEX_1.ibd` تا **۸۸MB دادهٔ آشغال** رفته بود. اولین MATCH در هر پروسهٔ تازه، این ساختار بادکرده را لود می‌کرد (سقف‌ها: `innodb_ft_result_cache_limit=2GB` و `innodb_ft_total_cache_size=640MB`) و glibc حافظهٔ آزادشده را پس نمی‌داد → در موج‌های همزمانِ ۴ ورکره، RSS ماریا‌دی‌بی از ~۲.۴GB گذشت و OOM-killer دیتابیس را کشت.
- **اثبات:** پس از `OPTIMIZE TABLE products`، همان کوئری MATCH روی نمونهٔ تازه فقط **+۲MB** گرفت (قبلاً +۵۱۱MB).

رفع چهارلایه:
- **رفع ریشه (کد):** `ScaleTestSetup::cleanupScaleData` پس از حذف دادهٔ مقیاس، ایندکس FULLTEXT را بازسازی می‌کند (`OPTIMIZE TABLE products` — روی جدول سبز چند میلی‌ثانیه؛ فقط در محیط تست).
- **گارد حافظهٔ دیتابیس:** `ScaleTestSetup::guardDatabaseMemory()` — روی ماشین < ۸GB اگر `innodb_buffer_pool_size` پیکربندی‌شده از ۲۵۶MB بزرگ‌تر باشد، با تغییر اندازهٔ آنلاین (`SET GLOBAL`) به همان سقف می‌رسد تا دیتابیس هرگز بزرگ‌ترین مصرف‌کنندهٔ رم نباشد (فقط کوچک‌سازی؛ fail-safe؛ در PerformanceTest هم ص زده می‌شود). در سناریوی آزمایشی «شروع با pool=2G» اثرش تأیید شد (۲۰۴۸→۲۵۶MB و بقای دیتابیس).
- **ورکر هم‌مقیاس حافظه:** `ConcurrentUsersTest::workerCount()` — تعداد ورکرهای `php -S` بر اساس `MemAvailable` (≥۳GB: ۶، <۳GB: ۴، <۲GB: ۲) + لاگ تصمیم در STDERR.
- **اتصال ماندگار فقط برای سرور تست بار:** option جدید `PDO::ATTR_PERSISTENT` در `config/database.php` که فقط با env `DB_PERSISTENT=true` فعال می‌شود (پیش‌فرض: غیرفعال) — ۴ ورکر به‌جای ۱۰۰۰ چرخهٔ create/destroy اتصال، ۴ اتصال همیشگی دارند (سریع‌تر: rps=4 و wall=264s در برابر rps=3 و 385s).

### نتیجهٔ کامل (MariaDB sandbox — همهٔ دسته‌ها پس از رفع)
- فاز ۱ `(AdminPanelSmoke|Auth|Cart|Catalog|Checkout|Extra)`: **۱۵۵ ✓ / ۱ skip (۴۶۹ اظهار)**
- `(Order|Payment|Performance|Security)`: **۱۱۵ ✓ (۳۸۹ اظهار)**
- `(ScaleReadiness|Unit)`: **۳۳ ✓ / ۳ skip (۸۹ اظهار)**
- `MillionScaleTest` → `ConcurrentUsersTest` (سناریوی بازتولید کامل: ۳۰هزار درج/حذف سپس لودتست): **۱۱ ✓** — ماریا‌دی‌بی زنده با **حداکثر RSS = ۲۷۵MB** (قبلاً مرگ در ~۲.۴GB)
- جمع: **۳۱۴ تست سبز / ۴ skip** — BotTrapSettingsTest ۹ ✓، PenetrationTest ۲۵ ✓
- سناریوی «کُشنده» اثبات‌شده: شروع ماریا‌دی‌بی با `--innodb-buffer-pool-size=2G` + اجرای ConcurrentUsersTest → گارد pool را به ۲۵۶MB می‌رساند، ۲ تست سبز، دیتابیس زنده.

**کامیت:** BlockPage + bot-blocked.blade + modern layout fix + BlockBots/BotTrap/BotTrapController (کلید اصلی) + SecuritySettings + Developer (toggle + جدول مسدودی‌ها + unblock) + config/general + ScaleTestSetup (گارد + OPTIMIZE) + ConcurrentUsersTest (ورکر هم‌مقیاس) + PerformanceTest + config/database (DB_PERSISTENT) + ۹ تست + این فایل → docs/project-analysis

---

## فاز ۷۲ — بن اکانت (فقط سازنده) + پیام مسدودی در صفحهٔ ورود پنل جدید + 2FA فردی و شماره همراه مستقل + محافظت ۴۸ ساعتهٔ «سیستم اصلی» (۱۶ تست جدید — ۳۴۲ سبز)

### ۱) بن اکانت در صفحهٔ نشست‌های فعال — فقط سازنده
- **مودال بلاک بازطراحی شد (مدرن):** مودال جدید `max-w-lg` با سربرگ گرادیانی قرمز + دکمهٔ بستن، کارت هدف (آواتار حرف اول + نام/ایمیل + بج دستگاه و آیپی)، **کارت‌های انتخابی نوع بلاک** (نشست/آیپی/دستگاه/مرورگر/همه + بن اکانت)، **پیل‌های مدت** (دائمی/۱ روز/۱ هفته/۱ ماه)، هشدار قرمز ویژهٔ بن اکانت (فقط وقتی انتخاب شده)، انیمیشن‌های Alpine و دکمهٔ ارسال با متن پویا («بن اکانت»/«بلاک شود»).
- **گزینهٔ بن اکانت فقط برای سازنده:** در ویو با گارد سرور-ساید `$canAccountBan` (سازنده باشد × هدف مدیرِ غیرسازنده باشد × هدف خودش نباشد) رندر می‌شود؛ در `blockSession()` هم `isCreator()` اجباری است (غیرسازنده ⇒ 403). بن اکانت سازنده یا خودِ سازنده ممنوع است (توست خطا).
- **اثر بن اکانت:** رکورد `blocked_devices` با `block_type=account` + خروج فوری از **همهٔ نشست‌های** آن مدیر (admin_sessions + جدول sessions).
- **کنترل مسدودی:** `CheckBlockedDevice::activeBlockFor()` — نوع account بدون توجه به دستگاه/آیپی همیشه مسدود است (در میدل‌ور و هر دو صفحهٔ ورود). نوع‌های دیگر مثل قبل با نشست/آیپی/اثرانگشت دستگاه/مرورگر می‌خوانند.
- **لیست بلاک‌ها:** بج قرمز «بن اکانت» + مقدار «کل حساب کاربری»؛ رکوردهای بن اکانت فقط برای سازنده در تب بلاک‌ها دیده می‌شوند؛ رفع بن اکانت و پاک‌سازی بلاک‌های شامل بن اکانت فقط توسط سازنده.
- **آیکون‌های جدید در icons-map:** `shield-ban`، `shield-alert`، `shield-check`، `user-lock`، `network` (از lucide-static v1.59.0).

### ۲) کاربر مسدود — پیام در همان صفحهٔ ورود پنل جدید (رفع هدایت به پنل قدیم)
- ریشه: `CheckBlockedDevice` به `route('admin.login')` ریدایرکت می‌کرد که **مسیر پنل قدیم** (`/admin-old/{prefix}/login`) است.
- رفع: مقصد ریدایرکت به `route('np.login')` (صفحهٔ ورود پنل جدید) تغییر کرد با `withErrors` تا پیام فارسی مسدودی در همان فرم ورود نمایش داده شود.
- **بهتر از ریدایرکت:** در هر دو صفحهٔ ورود (پنل جدید `Login` و قدیمی `AdminLoginController`) **قبل از ورود** `activeBlockFor()` چک می‌شود؛ کاربر مسدود همان‌جا روی فرم پیام می‌گیرد (بدون login-then-kick). پیام بن اکانت: «حساب کاربری شما توسط سازندهٔ سایت مسدود شده است…» + تاریخ پایان برای مسدودی موقت.
- ضمناً باگ قدیمی interpolation خراب `{.$blocked->reason}` در پیام میدل‌ور اصلاح شد.

### ۳) 2FA فردی در پروفایل + شماره همراه مستقل از نام کاربری
- **مهاجرت:** `admins.mobile` (nullable + ایندکس) و `admins.two_factor_disabled` (boolean) و `admin_sessions.is_primary/primary_until` و enum `blocked_devices.block_type` + مقدار `account`.
- **پروفایل (admin/profile):** فیلد «شماره همراه» جدا از نام کاربری (با توضیح «با این شماره هم می‌توانید وارد شوید و کد 2FA به آن ارسال می‌شود») + **کارت «ورود دومرحله‌ای برای حساب من»** با سوییچ فوری (`toggleTwoFactor`) — وقتی کلید سراسری در تنظیمات برنامه‌نویس خاموش است، به‌جای سوییچ توضیح نمایش داده می‌شود. لاگ فعالیت هم ثبت می‌شود.
- **جریان ورود:** `SecuritySettings::twoFactorEnabled() && ! $admin->two_factor_disabled` در هر دو پنل — مدیر می‌تواند فقط برای خودش 2FA را خاموش کند.
- **صفحهٔ مدیران:** فیلد «شماره همراه» جدا شد (لیبل قبلی «شماره همراه (نام کاربری)» روی همان فیلد username بود!) + ستون جدید شماره همراه در جدول + اعتبارسنجی یکتایی موبایل. **ورود با شماره موبایل** هم اکنون ممکن است (`AdminAuthResolver` → username/email/**mobile**).
- **2FA پیامک مقصد هوشمند:** شمارهٔ موبایل خودِ مدیر مقدم بر `admin_mobile_number` تنظیمات پیامک است (`AdminTwoFactorService::targetMobile`).

### ۴) رفع پیام گمراه‌کنندهٔ 2FA ایمیلی
- پیام قبلی همیشه «شماره تلفن مدیر (admin_mobile_number) در تنظیمات پیامک ثبت نشده یا ایمیل مدیر خالی است» بود — حتی برای کانال ایمیل.
- اکنون پیام دقیق بر اساس کانال: ایمیل ⇒ «ارسال کد تأیید با ایمیل ممکن نشد — ایمیل مدیر در پروفایل ثبت نشده یا سرویس ایمیل با خطا مواجه شده است…»؛ پیامک ⇒ پیام مخصوص شماره/سرویس پیامک؛ هر دو ⇒ پیام ترکیبی.

### ۵) محافظت ۴۸ ساعتهٔ «سیستم اصلی» در برابر هکرِ صاحب رمز
- **تعریف:** اولین دستگاهی که برای مدیر نشست می‌سازد (وقتی هیچ نشست فعال دیگری ندارد) «سیستم اصلی» است (`RecordAdminSession` — درج دستگاه جدید بدون نشست فعال دیگر ⇒ `is_primary=true` + `primary_until=now+48h`؛ استفادهٔ مستمر از دستگاه اصلی پنجره را تمدید می‌کند). اثر انگشت = مرورگر|سیستم‌عامل|نوع‌دستگاه|آیپی (همان کلید یکتای admin_sessions).
- **قاعده:** اگر کسی با اطلاعات همان مدیر از دستگاه دیگر (آیپی/مرورگر/سیستم متفاوت) وارد شود، تا ۴۸ ساعت دکمه‌های «خروج» و «بلاک» روی ردیف نشست اصلی **غیرفعال** است (UI: disabled + توضیح + بج کهربایی «سیستم اصلی») و **گارد سرور-ساید** در `destroySession`/`blockSession` هم عملیات را رد می‌کند (توست خطا). «خروج از تمام دستگاه‌ها» و «خروج از سایر دستگاه‌ها» نشست‌های محافظت‌شده را رد می‌کنند و تعدادشان را در توست اعلام می‌کنند.
- از همان دستگاه اصلی (اثر انگشت یکسان) همهٔ دکمه‌ها فعال می‌مانند — سناریوی اشتراک رایانهٔ اداری هم پوشش داده شد.
- **بازسازی ساختاری:** کلاس مشترک `App\Support\DeviceFingerprint` (session/device/browser) — سه نسخهٔ کپی‌شدهٔ قبلی در `RecordAdminSession`/`CheckBlockedDevice`/`SessionIndex` حذف و به آن منتقل شد؛ فرمت‌های قدیمی md5 حفظ شد تا بلاک‌های موجود معتبر بمانند.

### ۶) تست‌ها (۱۶ تست جدید — `AccountBanSessionTrustTest`)
بن اکانت سازنده ✓ / ممنوعیت غیرسازنده (403) ✓ / ممنوعیت بن اکانت سازنده ✓ / پیام مسدودی روی فرم ورود پنل جدید ✓ / ریدایرکت میدل‌ور به np.login (نه admin-old) ✓ / نشست اصلی از دستگاه دیگر بن/خروج نمی‌شود ✓ / از همان دستگاه قابل مدیریت است ✓ / انقضای ۴۸ ساعت ⇒ آزاد ✓ / اولین ورود = سیستم اصلی (GET واقعی np.profile) ✓ / خاموشی فردی 2FA ⇒ ورود مستقیم ✓ / روشنی 2FA برای مدیر بدون خاموشی ⇒ چالش ✓ / پیام دقیق کانال ایمیل (بدون admin_mobile_number) ✓ / ورود با شماره موبایل ✓ / ذخیرهٔ موبایل جدا از username در فرم مدیران ✓ / سوییچ 2FA و موبایل در پروفایل ✓ / گزینهٔ بن اکانت فقط در مودال سازنده ✓

### نتیجهٔ کامل (MariaDB sandbox)
- فاز ۱ `(AdminPanelSmoke|Auth|Cart|Catalog|Checkout|Extra)`: **۱۵۵ ✓ / ۱ skip (۴۶۹ اظهار)**
- فاز ۲ `(Order|Payment|Performance|Scale|Security)|Unit`: **۱۸۷ ✓ / ۳ skip (۵۹۵ اظهار)**
- جمع: **۳۴۲ تست سبز / ۴ skip** — ماریا‌دی‌بی پس از ConcurrentUsersTest زنده (Threads_connected=1)

**کامیت:** Migration (admins.mobile/two_factor_disabled + admin_sessions.is_primary/primary_until + blocked_devices enum account) + DeviceFingerprint + CheckBlockedDevice (بازنویسی + activeBlockFor/blockMessage) + RecordAdminSession (نشست اصلی) + SessionIndex (بن اکانت + گاردهای ۴۸ ساعته) + Login/AdminLoginController (چک مسدودی قبل از ورود + خاموشی فردی 2FA) + AdminTwoFactorService (موبایل فردی + پیام‌های دقیق) + AdminAuthResolver (ورود با موبایل) + Profile/AdminIndex + ویوهای sessions/profile/admins + icons-map + ۱۶ تست + این فایل → docs/project-analysis

---

## فاز ۷۳ — لایهٔ سازگاری Intervention (رفع کرش ذخیرهٔ محصول از دیجی‌کالا) + نمایش تصاویر فایل‌منیجر و پیش‌نمایش + بازطراحی انتخاب «مدت بلاک» (۹ تست جدید — ۳۳۹ سبز)

### ۱) ریشه‌یابی قطعی کرش «Call to undefined method Intervention\Image\ImageManager::read()»
- **vendor سرور لایو قدیمی است** (نسخهٔ آلفای v3 با سازندهٔ آرایه‌ای که `read()` ندارد) و کاربر نمی‌تواند composer اجرا کند؛ کدِ مخزن API نسخهٔ نهایی v3 را صدا می‌زند. زنجیرهٔ فاز ۷۰ (`new ImageManager(new Driver())` → fallback آرایه‌ای) روی آلفا مدیر را می‌سازد ولی در `$manager->read(...)` (helpers:1630) می‌مُرد — دقیقاً همان پیام کاربر.
- **راه‌حل: لایهٔ سازگاری «تشخیص قابلیت»** در helpers.php با ۵ هلپر جدید (`shop_image_manager` / `shop_image_open` / `shop_image_scale` / `shop_image_watermark` / `shop_image_encode`) — روی هر سه نسل کار می‌کند:
  - v2 → `make()` / `resize()`+constraint / `insert()` / `encode(فرمت, کیفیت)`
  - v3 آلفا → `make()` با سازندهٔ آرایه‌ای / انکودر آبجکتی در صورت وجود
  - v3 نهایی → `read()` / `scale()` / `place()` / انکودر آبجکتی (رفتار قبلی — تست‌ها دست‌نخورده سبز)
- **انکود هرگز آپلود را نمی‌شکند:** اگر فرمت هدف (مثل webp) پشتیبانی نشود → jpg → png → انکود پیش‌فرض؛ و نام فایل اکنون **بعد از** انکود با «فرمت واقعیِ خروجی» ساخته می‌شود (دیگر پسوند و محتوا هم‌خوان می‌مانند).
- scale/واترمارک هم best-effort شدند (خطا ⇒ ذخیرهٔ تصویر اصلی؛ آپلود شکست نمی‌خورد). `uploadOptimizedImage` و `uploadOptimizedImageFromPath` و مسیر MediaController/ذخیرهٔ محصول (افزودن + ویرایش) اکنون روی هر vendor کار می‌کنند.

### ۲) نمایش تصاویر فایل‌منیجر + پیش‌نمایش (ریشهٔ مشترک با بند ۱)
- endpointهای `thumbnails`/`preview` پکیج alexusmai با `Intervention\Image\Laravel\Facades\Image::read()` (v3 انحصاری) نوشته شده‌اند و روی vendor قدیمی 500 می‌دهند → در گرید فقط آیکون عکس شکسته و پیش‌نمایش خالی بود.
- **`App\Services\CompatFileManager`** (زیرکلاس پکیج) در AppServiceProvider به‌جای کلاس پکیج بایند شد:
  - `preview`: **فایل خام با mime درست** — بدون پردازش تصویر؛ همیشه کار می‌کند و سریع‌تر است.
  - `thumbnails`: رسترها با لایهٔ سازگار `shop_image_*` کوچک می‌شوند (v3: `coverDown` / v2: `fit`)؛ **SVG/بقیهٔ فرمت‌ها و هر خطا ⇒ خود فایل خام (fail-safe)** — تصویر همیشه نمایش داده می‌شود حتی برای فایل خراب.

### ۳) بازطراحی «مدت زمان بلاک» در مودال مسدودسازی (صفحهٔ نشست‌های فعال)
- **کاشی‌های آیکون‌دار ۳×۲** به‌جای پیل‌های فشردهٔ ۴تایی: دائمی (∞) / ۱۲ ساعت (تایمر — پیش‌فرض جدید) / ۱ روز / ۱ هفته / ۱ ماه / **سفارشی** — هر کاشی: آیکون + برچسب + زیرنویس (نیم‌روز، ۲۴ ساعت، ۷ روز، ۳۰ روز) + حالت انتخاب‌شدهٔ قرمز.
- **پنل مدت سفارشی:** انتخاب کاشی «سفارشی» ⇒ ورودی عدد + انتخابگر واحد چهاربخشی (ساعت/روز/هفته/ماه) با اعتبارسنجی سرور (`blockCustomAmount` 1..999 + `blockCustomUnit`) و محاسبهٔ دقیق `blocked_until`.
- **خط وضعیت زنده** (Alpine): دائمی ⇒ «تا رفع دستی» / غیر آن ⇒ «خودکار برداشته می‌شود».
- آیکون‌های جدید در icons-map: `infinity`، `timer`. پشتیبانی سرور از `12hours` هم اضافه شد (راستی‌آزمایی: BlockedDevice با انقضای +۱۲ ساعت).

### ۴) تست‌ها (۹ تست جدید — `FileManagerImagesTest` ۵ + `AccountBanSessionTrustTest` ۴+)
بایند CompatFileManager ✓ / thumbnails رستر کوچک‌شده ✓ / SVG خام ✓ / پیش‌نمایش = بایت‌به‌بایت خود فایل ✓ / fail-safe فایل خراب ✓ / پیش‌فرض ۱۲ ساعت ✓ / مدت سفارشی ۳ روز و ۶ ساعت ✓ / اعتبارسنجی مقدار خالی و صفر ✓ / کاشی‌های جدید در مودال ✓

### نتیجهٔ کامل (MariaDB sandbox — سه دستهٔ B طبق الگوی فاز ۷۱)
- فاز ۱ `(AdminPanelSmoke|Auth|Cart|Catalog|Checkout|Extra)`: **۱۶۰ ✓ / ۱ skip (۴۸۱ اظهار)**
- `(Order|Payment|Performance|Security)`: **۱۳۵ ✓ (۴۶۶ اظهار)**
- `(ScaleReadiness|Unit)`: **۳۳ ✓ / ۳ skip** — `MillionScale→ConcurrentUsers`: **۱۱ ✓** (ماریا‌دی‌بی زنده)
- جمع: **۳۳۹ تست سبز / ۴ skip**

**یادداشت لایو:** نیازی به composer نیست — پس از `git pull` کرش ذخیرهٔ محصول (افزودن از دیجی‌کالا و ویرایش) و تصاویر/پیش‌نمایش فایل‌منیجر روی vendor قدیمی هم رفع است. اگر بعداً composer اجرا شد، همه‌چیز روی v3 نهایی هم بی‌تغییر کار می‌کند.

**کامیت:** helpers.php (لایهٔ سازگاری ۵ هلپری + بازنویسی uploadOptimizedImage) + CompatFileManager + AppServiceProvider (بایند) + SessionIndex (12hours + custom) + sessions.blade (کاشی‌های جدید + پنل سفارشی) + icons-map (infinity/timer) + ۹ تست + این فایل → docs/project-analysis

## فاز ۷۴ — رفع «Unknown driver type.» ذخیرهٔ محصول (تلهٔ vendor ماژول SeoAudit روی هاست) + مودال ایجاد/ویرایش مدیر بیرون‌زده از صفحه + استثنای سازنده در محافظت ۴۸ ساعتهٔ «سیستم اصلی» (۴ تست جدید — ۳۴۳ سبز)

### ۱) «Unknown driver type.» — تلهٔ جدید لایو پس از فاز ۷۳ (لاگ کامل کاربر)
- لاگ لایو: `Intervention\Image\Exception\NotSupportedException: Unknown driver type.` از
  `Modules/SeoAudit/vendor/intervention/image/src/Intervention/Image/ImageManager.php:124` — یعنی
  **سیمبل ImageManager از vendor ماژول (نسل v2) لود می‌شود** در حالی که کلاس `Drivers\Gd\Driver` v3 هنوز
  از vendor اصلی قابل لود است → تشخیص نسخهٔ فاز ۷۳ با `class_exists(Driver::class)` به‌اشتباه «v3» تشخیص می‌داد
  و به سازندهٔ v2 آرایهٔ `['driver' => new Driver()]` می‌داد؛ v2 آرایه را می‌پذیرفت اما در
  `createDriver()` هنگام `make('RIFF…WEBP')` منفجر می‌شد (باینری webp دیجی‌کالا در لاگ).
- **رفع — پروب واقعی به‌جای حدس نسخه:** `shop_image_manager()` اکنون کاندیدها را به ترتیب می‌سازد
  (v3 نهایی با درایور آبجکتی → v3 آلفا آرایه‌ای → v2 رشته‌ای imagick/gd) و هر کاندید را با
  **بازکردن یک PNG یک‌پیکسلی در حافظه** اعتبارسنجی می‌کند؛ هر نسخه‌ای که decode واقعی را رد کند
  کنار گذاشته می‌شود و اولین کاندیدِ پروب‌سبز کش می‌شود. v2 معیوب در پلهٔ دوم گیر می‌افتد و سرانجام
  v2 سالم با درایور رشته‌ای انتخاب می‌شود. کاندیدِ سازه‌شده ولی پروب‌ردشده هم به‌عنوان پشتیبان بدترین حالت (GD ناموجود) نگه داشته می‌شود.
- **دفاع دوم در `uploadOptimizedImage`:** اگر درایورِ انتخاب‌شده باینری را باز نکند (مثلاً webp روی GD قدیمی)،
  فایل **خام با پسوند اصلی** ذخیره می‌شود (`products-*.webp` بایت‌به‌بایت) — آپلود محصول هرگز ۵۰۰ نمی‌دهد.

### ۲) مودال ایجاد/ویرایش مدیر — بیرون‌زدن از بالا و پایین صفحه (اسکرین‌شات کاربر)
- ریشه: کانتینر با `sm:items-center` مودال بلندتر از ویوپورت را وسط می‌گذارد → سرریز دوطرفه
  (بخش بالای مودال حتی با اسکرول هم غیرقابل‌دسترس می‌شود — باگ کلاسیک flexbox centering) و مودال سقف ارتفاع نداشت.
- رفع با همان الگوی اثبات‌شدهٔ مودال‌های بازار (فاز ۷۰): قاب `flex max-h-[90vh] flex-col overflow-hidden` +
  هدر چسبان با خط زیرین + فرم `min-h-0 flex-1 overflow-y-auto p-6`. همهٔ کلاس‌ها در build فعلی
  CSS پنل موجودند (بدون نیاز به build مجدد). دکمه‌ها داخل فرم اسکرول‌شو باقی می‌مانند.

### ۳) استثنای سازنده در محافظت ۴۸ ساعتهٔ «سیستم اصلی» (درخواست کاربر)
- «این مورد برای creator مستثنی باشه، سازنده باید بتونه همه کار بکنه» — در `SessionIndex::isProtectedFromViewer()`
  اگر بینندهٔ فعلی سازنده باشد ⇒ `false` (هیچ نشستی از سازنده پنهان/محافظت نمی‌شود؛ دکمه‌های خروج/بن همیشه فعال).
- امنیت حفظ شده: محافظت برای **بقیهٔ مدیرها** به قوت خودش است — اگر کسی با رمز مدیر دیگری از دستگاه دیگر وارد شود،
  همان محدودیت ۴۸ ساعته را می‌بیند. مسیرهای `destroyAllAdminSessions`/`logoutOtherDevices` (مختص سازنده) اکنون
  بدون skip نشست محافظت‌شده کل نشست‌ها را پاک می‌کنند.

### ۴) تست‌ها (۴ تست جدید — `UploadOptimizedImageTest` +۲ / `AccountBanSessionTrustTest` +۲)
پروب manager باید واقعاً decode کند ✓ / باینری غیرقابل‌بازکردن ⇒ فایل خام بایت‌به‌بایت ✓ /
خروج و بلاکِ نشست اصلیِ مدیر توسط سازنده از دستگاه متفاوت ✓ / مودال مدیر سقف ویوپورت دارد ✓
- جهش‌آزمایی دوجهته: حذف استثنای سازنده ⇒ تست سازنده قرمز؛ حذف کلاس‌های سقف مودال ⇒ تست مودال قرمز (بازگردانی هر دو).

### نتیجهٔ کامل (MariaDB sandbox — الگوی فاز ۷۱)
- فاز ۱ `(AdminPanelSmoke|Auth|Cart|Catalog|Checkout|Extra)`: **۱۶۲ ✓ / ۱ skip (۴۸۹ اظهار)**
- `(Order|Payment|Performance|Security)`: **۱۳۷ ✓ (۴۷۴ اظهار)**
- `(ScaleReadiness|Unit)`: **۳۳ ✓ / ۳ skip** — `MillionScale→ConcurrentUsers`: **۱۱ ✓** (ماریا‌دی‌بی زنده، ورکر ۴تایی)
- جمع: **۳۴۳ تست سبز / ۴ skip**

**یادداشت لایو:** پس از `git pull` (بدون composer) هر سه گزارش رفع است: ذخیرهٔ محصول دیجی‌کالا با webp،
مودال مدیران، و اختیارات سازنده در نشست‌های فعال. لایهٔ پروب روی هاست‌های v3 سالم هم رفتار قبلی را حفظ می‌کند.

**کامیت:** bootstrap/helpers.php (پروب نسخه + fail-safe آپلود خام) + SessionIndex (استثنای سازنده) +
admins.blade.php (مودال سقف‌دار) + ۴ تست + این فایل → docs/project-analysis

## فاز ۷۵ — گارد یکپارچگی vendor Intervention Image (رفع کرش جدید لایو «EncodedImage contains 5 abstract methods») + مسیر GD خالص + فرمان تشخیص ops:intervention-doctor (۹ تست جدید — ۳۵۶ سبز)

### ۱) کرش جدید لایو پس از فاز ۷۴ (لاگ کامل کاربر)
- خطای دقیق: `Class Intervention\Image\EncodedImage contains 5 abstract methods and must therefore be
  declared abstract or implement the remaining methods (Intervention\Image\Interfaces\FileInterface::save,
  toFilePointer, size, ...)` در `vendor/intervention/image/src/EncodedImage.php:9` + در رندر صفحهٔ خطا:
  `Call to undefined function highlight_file()` (به‌دلیل disable_functions هاست — بی‌ربط به ریشه است).
- **ریشه:** فایل‌های vendor روی هاست از دو نسخهٔ مختلف v3 مخلوط شده‌اند — «FileInterface» جدید است و
  «File/EncodedImage» قدیمی؛ این خطا E_ERROR/E_COMPILE_ERROR است و در لاگ مسیر `handleShutdown` دیده می‌شود
  ⇒ **با try/catch قابل شکار نیست**؛ یعنی هیچ دور-andازانی دور encode نجات نمی‌دهد و تنها راه نجات این است
  که هیچ کلاسی از Intervention در چنین وضعیتی autoload نشود.
- vendor داخل ریپو (3.11.8) با بازتاب کامل سالم است ⇒ مشکل فقط وضعیت هاست است؛ پس دفاع در لایهٔ کد ساخته شد.

### ۲) پروب متنی یکپارچگی — `shop_intervention_vendor_ok()` (بدون بارگذاری هیچ کلاسی)
- زنجیرهٔ «لازم»: `EncodedImageInterface → FileInterface` (متدهای interfaceها با تحلیل متنی فایل‌ها).
- زنجیرهٔ «دارا»: `EncodedImage → File` + traitهای بدنه (`CanBuildFilePointer` و…).
- حل نام: نقشهٔ alias های `use` سربرگ + نام‌های هم-namespace با کاندیدهای استاندارد پکیج
  (`shop_vendor_resolve_file` — ریشهٔ namespace پکیج همان پوشهٔ src است)؛ نام‌های بیرونی مثل Stringable نادیده.
- حکم: هر متدِ لازم بدون «دارا» ⇒ معیوب (fail-closed). v2 (نبودن EncodedImage.php) سالم تلقی می‌شود
  (لایهٔ سازگار فاز ۷۳ هندلش می‌کند). چهار سناریو تست شد: سالم ✓ / مخلوط ✓ / v2 ✓ / پوشهٔ ناموجود ✓.
- در `uploadOptimizedImage` **قبل از هر کار** صدا زده می‌شود؛ اگر معیوب بود ⇒ کل مسیر به GD خالص می‌رود.

### ۳) مسیر GD خالص — بدون حتی یک کلاس از Intervention
- `shop_gd_fallback_encode()`: decode با `imagecreatefromstring` (هر فرمتی که GD فعلی باز کند) →
  چرخش EXIF بهترین‌تلاش → آلفای سالم → مقیاس فقط-کوچک‌کردن با حفظ نسبت (`imagescale` با پشتیبان
  `imagecopyresampled`) → انکود فقط با توابعی که واقعاً در بیلد GD سرور هستند (webp → jpg → png) و
  **پسوند واقعی خروجی** برمی‌گردد؛ بازنشدن decode ⇒ [null, پسوند اصلی] یعنی «خام ذخیره کن».
- `upload_optimized_image_via_gd()`: رفتار و خروجی دقیقاً مثل `uploadOptimizedImage` — حذف تصویر قبلی
  (توابع مشترک جدید `shop_upload_image_options` / `shop_delete_previous_upload`)، ذخیره با پسوند واقعی،
  بهینه‌ساز اختیاری با گارد. آپلود محصول روی بدترین سرور ممکن (vendor مخلوط + GD فقیر) هم کار می‌کند.
- `CompatFileManager::thumbnails` هم گارد پروب گرفت: vendor معیوب ⇒ فایل خام (رفتار fail-safe فاز ۷۳ حفظ می‌شود).

### ۴) تلهٔ تشخیصی + فرمان پزشک
- `shop_register_intervention_trap()`: در shutdown، اگر آخرین خطای fatal از Intervention بود، لاگِ شفاف
  با راهنمای درمان نوشته می‌شود تا ریشه در چند ثانیه پیدا شود (کرش‌های غیرقابل شکار دیگر خام نمی‌مانند).
- `php artisan ops:intervention-doctor` (بدون بارگذاری کلاس‌ها): نسخهٔ نصب‌شده و composer.lock،
  **اسکن همهٔ کپی‌های intervention/image** زیر پروژه (vendor اصلی + vendorهای داخلی ماژول/قالب/پکیج —
  مثل vendor معیوب SeoAudit روی هاست) با یکپارچگی هر کدام، قابلیت‌های GD (توابع غایب + probe واقعی decode)،
  وضعیت OPcache (خطر کلاس‌های کهنه با validate_timestamps=0)، حکم نهایی + درمان گام‌به‌گام فارسی؛
  `--json` برای پیوست‌کردن در گزارش. کد خروج ۰ سالم / ۱ معیوب.

### ۵) درمان سمت هاست (برای کاربر — بعد از pull)
```
composer install            # یا: composer update intervention/image --with-all-dependencies
php artisan optimize:clear
php artisan ops:intervention-doctor    # بررسی — خروجی باید ✔ سالم باشد
```
+ ریست OPcache از پنل هاست (اگر validate_timestamps=0 باشد، بدون ریست، کلاس‌های قدیمی در حافظه می‌مانند).
تا قبل از اصلاح vendor، آپلود تصویر و فایل‌منیجر خودکار از مسیر GD خالص/خام عبور می‌کنند و سایت از کار نمی‌افتد.

### ۶) تست‌ها (۹ تست جدید — `InterventionVendorGuardTest`)
پروب: سالم ✓ / مخلوط مثل سرور لایو ✓ / v2 ✓ / پوشهٔ ناموجود ✓ — GD خالص: انکود+مقیاس+نسبت (۱۰۰×۷۵) ✓ /
باینری بازنشدنی ⇒ null ✓ — آپلود GD کامل روی Storage جعلی ✓ / فایل خام بایت‌به‌بایت ✓ — فرمان پزشک (کد خروج + JSON) ✓

### نتیجهٔ کامل (MariaDB sandbox — الگوی فاز ۷۱)
- **۳۵۶ تست / ۱۱۰۷ اظهار / ۰ خطا / ۴ skip** — شامل ConcurrentUsers (۱۰۰۰ کاربر همزمان، rps=4، restarts=0)

**کامیت:** bootstrap/helpers.php (پروب + GD خالص + تله + بازآرایی مشترک آپلود) + CompatFileManager (گارد پروب)
+ InterventionDoctor.php (فرمان پزشک) + ۹ تست + این فایل → docs/project-analysis

---

## فاز ۷۶ — پاک‌سازی آخرین نقاطِ مصرف مستقیم Intervention Image (کامل‌کردن گارد فاز ۷۵) + تست ضد-رگرسیون «ممنوعیت فراخوانی مستقیم» (۲ تست جدید)

> **زمینه:** کاربر لاگ تازهٔ ساعت ۰۶:۰۴ فرستاد — همان کرش «EncodedImage contains 5 abstract methods»
> (چرا هنوز؟ چون Phase 75 هنوز روی هاست pull نشده بود). بررسی مجدد کد نشان داد دو نقطهٔ مصرف مستقیم
> باقی مانده که از گارد فاز ۷۵ عبور می‌کردند + ۵ import مردهٔ فاساد v2. این فاز آن‌ها را می‌بندد تا
> بعد از pull، «هیچ» مسیری — حتی اسکریپ دیجی‌کالا و مقالهٔ AI — دیگر به کلاس‌های Intervention وابسته نباشد.

### ۱) نقطهٔ باقی‌مانده ۱ — اسکریپ «افزودن از سایت» در ProductController
- کد قبلی: `Image::make($image)->save('upload/image/...jpg', 100, 'jpg')` + `$img->filesize()`
  با `use Intervention\Image\Facades\Image;` (فاساد مخصوص v2 — در vendor v3 اصلاً وجود ندارد؛
  در v3 صحیح هم «Class not found» می‌داد و در هاست مخلوط، کرش غیرقابل‌شکار abstract methods).
- جایگزین: دانلود باینری با `Http::timeout(20)` + انکود `shop_gd_fallback_encode()` (GD خالص —
  بدون هیچ کلاسی از Intervention) + نوشتن با پسوند واقعی (`$ext`) و `strlen` برای سایز؛
  هر خطا فقط «آن تصویر» را رد می‌کند نه کل ذخیرهٔ محصول (fail-safe همسو با فاز ۷۴).

### ۲) نقطهٔ باقی‌مانده ۲ — `get:article_ai` (دو فراخوانی)
- کد قبلی: `Image::make($path)->resize(1024, null, aspectRatio)->save()`
- جایگزین: متد `shrinkWithGd($path, 1024)` در خود کماند — GD خالص با `shop_gd_fallback_encode`؛
  خرابی مقیاس = ماندن فایل خام (مقاله از کرش نمی‌میرد).

### ۳) پاک‌سازی importهای مرده (فاساد v2 که در vendor v3 وجود ندارد)
- حذف `use Intervention\Image\Facades\Image;` از: `ProductsImport`، `TicketController`،
  `SellerControllers`، `UserController`، `PostController` — هیچ‌کدام استفادهٔ واقعی نداشتند
  (فقط import)؛ حذف آن‌ها هیچ تغییر رفتاری ندارد ولی کد را با سیاست «صفر وابستگی مستقیم» هم‌راستا می‌کند.
- نتیجهٔ اسکن کل `app/` + `Modules/`: صفر ارجاع مستقیم به Intervention — همهٔ کار تصویر از
  لایهٔ سازگار `shop_image_*` / `uploadOptimizedImage` / GD خالص عبور می‌کند.

### ۴) تست ضد-رگرسیون جدید (`InterventionFreeCallSitesTest` — ۲ تست)
- `no_direct_intervention_import_or_call_survives_in_app_code`: اسکن متنی همهٔ فایل‌های PHP در
  `app/` + `Modules/` (با حذف کامنت‌ها مثل پروب فاز ۷۵) — ممنوعیت: `use Intervention\...`،
  `Image::make(`، `new ImageManager`؛ اگر کسی دوباره فراخوانی مستقیم اضافه کند تست قرمز می‌شود.
- `reformed_call_sites_use_pure_gd_layer`: تثبیت اینکه دو نقطهٔ اصلاح‌شده واقعاً از
  `shop_gd_fallback_encode` استفاده می‌کنند و فاساد v2 در آن‌ها نیست.

### ۵) وضعیت لایو (پیام به کاربر)
- لاگ ۰۶:۰۴ به این معنی است که **فاز ۷۵ هنوز روی هاست اعمال نشده** (کامیت ۲۳:۴۱ UTC بوده و push شده؛
  سرور باید pull کند). تا قبل از pull، همان کرش قدیمی تکرار می‌شود — طبیعی است.
- بعد از pull: `php artisan optimize:clear` + ریست OPcache از پنل هاست؛ سپس `php artisan ops:intervention-doctor`
  (خروجی باید «سالم» بدهد). اگر vendor هاست مخلوط بماند هم آپلود محصول و فایل‌منیجر از مسیر GD خالص/خام
  عبور می‌کنند و سایت از کار نمی‌افتد؛ فقط بهینه‌سازی تصویر موقتاً ساده‌تر است.
- ⚠ نکتهٔ فرعی از لاگ: `highlight_file()` undefined یعنی `disable_functions` هاست — صفحهٔ خطای
  Symfony هم کرش می‌کند؛ ربطی به ریشه ندارد ولی `APP_DEBUG=false` (همان‌طور که ویزارد می‌نویسد) باید باشد.

### ۶) تلمبهٔ آزمایشی sandbox این فاز (محدودیت محیط)
- در این sandbox، PHP/MariaDB از دسترس خارج شده بود — PHP 8.4.23 استاتیک نصب شد؛ MariaDB قابل نصب نبود
  (بدون root) و مایگریشن‌ها روی sqlite سازگار نیستند (drop foreign key by name) ⇒ اجرای کامل ۳۵۶ تست
  روی MariaDB ممکن نشد (محدودیت محیط، نه کد).
- اجرا شد: `php -l` هر ۸ فایل تغییر یافته ✔ — `InterventionFreeCallSitesTest` (۲/۲) ✔ —
  `InterventionVendorGuardTest` (۹/۹) ✔ — `tests/Unit` (۱۸/۱۸، ۳ skip محیطی) ✔ —
  `ops:intervention-doctor` (خروجی سالم) ✔.

**کامیت:** ProductController (اسکریپ → GD خالص) + GetArticleAiCommand (shrinkWithGd) + حذف ۵ import مرده
+ InterventionFreeCallSitesTest + این فایل → docs/project-analysis

---

## فاز ۷۷ — GD «مسیرِ اصلی» آپلود + پروب OPcache + گزینهٔ ریست OPcache در پزشک (رفع تکرار کرش بعد از pull — لاگ ۰۶:۴۶ کاربر)

### ۱) چرا بعد از pull فاز ۷۵ هنوز کرش داشتیم؟ (ریشهٔ واقعی)
- کاربر فاز ۷۵ را روی هاست pull کرده بود (آخرین کامیت دریافت‌شده‌اش فاز ۷۵ بود) و باز هم همان
  «EncodedImage contains 5 abstract methods» آمد (لاگ ۰۶:۴۶).
- تحلیل: روی هاست `opcache.validate_timestamps=0` است — PHP کلاس‌های کامپایل‌شدهٔ کهنه را از حافظه
  سرو می‌کند حتی وقتی فایل‌های دیسک با git pull سالم شده‌اند. پروب متنی فاز ۷۵ «دیسک» را می‌خواند
  (سبز می‌شود) اما کلاسی که واقعاً load می‌شود کهنه/مخلوط است ⇒ همان کرش. دو حالت ممکن:
  دیسک سالم+OPcache کهنه (پروب فریب می‌خورد) یا خود helpers.php هم کهنه (پروب اصلاً اجرا نمی‌شود).
- درس: اتکای مسیر آپلود به «هر کلاسی» روی چنین هاستی نامطمئن است — حتی با پروب.

### ۲) فاز ۷۷ — GD مسیرِ اصلی؛ Intervention فقط پشتیبانِ آخر
- `uploadOptimizedImage` حالا اول `upload_optimized_image_via_gd` را صدا می‌زند (اگر GD موجود باشد —
  عملاً همیشه)؛ Intervention فقط وقتی استفاده می‌شود که GD در دسترس نباشد و پروب سبز باشد.
- یعنی روی بدترین سرور ممکن (vendor مخلوط + OPcache کهنه) ذخیرهٔ محصول/مقاله/تاکت/فروشنده… هیچ‌گاه
  به کلاس‌های Intervention نمی‌رسد — کرش از این مسیرها «غیرممکن» شد.
- برای کامل‌شدن هم‌رفتاری: **واترمارک به مسیر GD اضافه شد** — `shop_gd_apply_watermark()` با
  `imagecopy` و آلفای سالم (نه imagecopymerge)، ۵ موقعیت مثل مسیر Intervention، کوچک‌سازی متناسب
  واترمارک بزرگ‌تر از تصویر، fail-safe (خطا = بدون واترمارک). ترتیب همان مسیر Intervention: مقیاس ← واترمارک ← انکود.

### ۳) پروب OPcache — `shop_intervention_opcache_stale()`
- دیسک سالم است؟ کافی نیست — نسخهٔ کامپایل‌شدهٔ OPcache هم باید تازه باشد.
- مقایسهٔ `timestamp` اسکریپت‌های کامپایل‌شدهٔ OPcache (داخل پوشهٔ src Intervention) با `filemtime` دیسک؛
  اگر فرق داشت ⇒ vendor «از نظر این درخواست» معیوب ⇒ GD/خام. اگر `validate_timestamps=1` باشد خودِ
  OPcache هم‌تراز می‌شود (false)؛ اگر OPcache اصلاً نباشد ⇒ null (پروب دیسک کافی است).
- به `shop_intervention_vendor_ok()` وصل شد ⇒ `CompatFileManager` هم از همین حفاظ استفاده می‌کند.

### ۴) پزشک — گزینهٔ `--write-opcache-reset`
- چون ریست OPcache روی هاست اشتراکی همیشه دکمهٔ آماده ندارد، پزشک حالا یک فایل PHP یک‌بارمصرف با نام
  تصادفی (`opcache-reset-xxxxxxxx.php`) در public می‌سازد؛ یک بار بازدید در مرورگر = ریست کش همان FPM؛
  سپس حذف فایل. بخش OPcacheِ گزارش هم درمان گام‌به‌گام چاپ می‌کند.

### ۵) تست خودِ «افزودن محصول دیجی‌کالا» (درخواست صریح کاربر)
- API دیجی‌کالا از sandbox در دسترس نیست (307 geo-block) اما **کل خط تصویر با باینری webp واقعی
  ۱۲۰۰×۹۰۰** (همان فرمت دیجی‌کالا) شبیه‌سازی و سبز شد:
  resizeAndSaveSiteImage (GD، ۶۰۰×۴۵۰ حفظ نسبت) ← uploadOptimizedImage با گزینه‌های دقیق
  updateProductImages (size [800,800]) ← فایل معتبر webp ذخیره شد — و **هیچ کلاس Intervention در
  کل خط autoload نشد** (بررسی با class_exists بدون autoload). مسیر جایگزین `Image::make` هم سبز.
- تست‌های جدید فاز ۷۷ (۵ تست): GD در نقطهٔ ورود آپلود حتی با vendor سالم + واترمارک آلفادار GD (پیکسل‌سنجی) +
  واترمارک بزرگ‌تر از تصویر + پروب OPcache بدون OPcache + فایل ریست پزشک. (۱۶ تست فایل گارد: همگی سبز)

### ۶) درمان روی هاست (ترتیب دقیق برای کاربر)
```
cd /home/owqdlxcv/repositories/shop.opashop.ir
git status --short | head        # اگر vendor تغییر محلی داشت، اول: git checkout -- vendor/
git pull origin docs/project-analysis
php artisan optimize:clear
php artisan ops:intervention-doctor --write-opcache-reset
#  ← آدرس چاپ‌شده را یک‌بار در مرورگر باز کنید («OPcache reset OK») و فایل را حذف کنید
php artisan ops:intervention-doctor   # گزارش نهایی باید سالم باشد
```
- از این نسخه به بعد حتی بدون ریست OPcache، ذخیرهٔ محصول از مسیر GD می‌گذرد و ۵۰۰ نمی‌دهد؛
  ریست OPcache برای «فعال‌شدن دوبارهٔ بهینه‌سازی کامل Intervention» لازم است، نه برای بقا.

**کامیت:** bootstrap/helpers.php (GD-first + واترمارک GD + پروب OPcache) + InterventionDoctor (--write-opcache-reset)
+ ۵ تست جدید + این فایل → docs/project-analysis

---

## فاز ۷۸ — هشدار کاهش قیمت + پیش‌فروش (دو قابلیت هستهٔ «دمو-فروشی») + نقشهٔ راه پکیج‌ها

> **زمینه:** پس از باز شدن خطای Intervention (فاز ۷۵-۷۷)، کاربر خواست پروژه «کامل‌تر و حرفه‌ای‌تر» شود و
> امکانات در قالب «پکیج کارآمد» عرضه گردد. با بررسی کدِ هر ۱۰ مخزن پکیج موجود، دو-مسیر بودن توسعه
> شفاف شد: هستهٔ رایگانِ ضروری + پکیج‌های فروشی. طبق تصمیم مدیر، پوشهٔ vendor داخل پکیج‌ها می‌ماند (خودکفایی).
> بررسی نشان داد «پرسش‌وپاسخ محصول» از قبل با مدل Comment (سؤال=کامنت ریشه، پاسخ=ریپلای) به‌همراه
> مدیریت در هر دو پنل پیاده شده است؛ بنابراین این فاز روی دو شکاف واقعی تمرکز کرد: **هشدار قیمت** و **پیش‌فروش**.

### ۱) هشدار کاهش قیمت (پیامکی، کاربر و مهمان)
- جدول جدید `price_alerts` (product_id، user_id نال‌پذیر، mobile، name، target_price، active، notified_at)
  — هدفِ اشتراک = قیمت مؤثرِ لحظهٔ اشتراک؛ نسخهٔ `updateOrCreate` روی (product_id, mobile) یعنی
  اشتراک مجدد بعد از اعلان، هشدار را دوباره فعال می‌کند.
- **رهگیری افت قیمت در `PriceObserver::saved`**: مقایسهٔ «قیمت مؤثر» (همان منطق hasDiscount/salePrice)
  بین مقادیر خامِ قبل از ذخیره (getRawOriginal) و مقدار فعلی؛ فقط افت → `ProcessPriceAlerts` صف می‌شود.
  تغییرِ موجودی/انتشار بدون افت قیمت هیچ Job‌ای راه نمی‌اندازد (بینی‌هزینهٔ صف).
- `ProcessPriceAlerts`: کمترین قیمت مؤثرِ قابل‌خرید (stock>0 + published) محصول را حساب می‌کند؛ هشدارهای
  فعالِ با هدف بالاتر را پیامک داده و علامت می‌زند. اگر قابل‌خرید نیست، هشدارها فعال می‌مانند.
- **پیامک الگودار**: نوع جدید `PRICE_ALERT` در Sms::TYPES + متد `priceAlert()` در هر ۵ سرویس
  (ippanel/kavenegar/melipayamak/idehpardazan/farazsms) با کلید گزینهٔ واحد `price_alert_pattern_code`.
  اگر الگو تنظیم نشده باشد: بدون ارسال، علامت‌خوردن + هشدار لاگ (بدون حلقهٔ تکرار). متغیرهای الگو: title و price.
- **فرانت**: دکمهٔ «هشدار کاهش قیمت» زیر دکمهٔ خرید (product-info-sidebar) + مودال مهمان در show.blade.php
  + هندلر AJAX در show.js (همان الگوی stock-notify: X-CSRF-TOKEN + block/unblock + توست).
- **پنل**: پنل قدیم (Back\PriceAlertController + back/price-alerts/index + resource route + سایدبار +
  دسترسی جدید `products.price-alert`) و پورت پنل جدید (Livewire `Admin\PriceAlert\PriceAlertIndex` +
  route در admin-panel.php). ستون وضعیت: «در انتظار افت قیمت / اعلان شده (با تاریخ شمسی)».

### ۲) پیش‌فروش (سفارش کالای ناموجود)
- ستون `pre_order` روی products + چک‌باکس در هر سه فرم محصول (پنل قدیم create/edit، پنل جدید ProductForm)
  و ستون‌های ذخیره در Back\ProductController.
- **عبور کنترل‌شده از ۷ گیتِ موجودی** (فقط برای pre_order=1):
  ۱) `Product::addableToCart` ۲) سبد وبِ افزودن ۳) سبد وبِ آپدیت ۴) `check_cart_quantity` در helpers
  ۵) رزرو موجودی قبل از پرداخت ۶) کنترل دوباره + تأیید رزرو در orderPaid ۷) سبد API v1.
- **قاعدهٔ سقف یک عدد**: افزودن مجددِ کالای پیش‌فروش در سبد وب و API خطای «تنها یک عدد از آن قابل سفارش است» می‌دهد.
- **حفاظت انبار در لغو**: `CancelOrder` برای آیتم‌های پیش‌فروش unreserve/افزایشِ موجودی را رد می‌کند
  (چون هرگز رزرو/کسر نداشته‌اند؛ وگرنه موجودی غیرواقعی باد می‌کرد).
- **UI**: نشان «پیش‌فروش» در صفحهٔ محصول + متن دکمهٔ «ثبت پیش‌فروش» + بدون هشدار کمبود موجودی در سبد.

### ۳) نقشهٔ راه محصول (PACKAGES_ROADMAP.md)
- سند جدید در ریشهٔ پروژه: فازبندی ۷۸ تا ۸۵، جدول وضعیت ۱۰ پکیج موجود، چک‌لیست «حد فروش»،
  تصمیم vendor، حذف SnappPay (درایورش در multipay هسته است) و ادغام InstallmentPayment+CreditPay در فاز ۷۹.

### ۴) تست‌ها (۱۵ تست جدید — هر ۱۵ سبز)
- `PriceAlertTest` (۸): اشتراک مهمان با هدف=قیمت فعلی، اشتراک کاربر بدون ورودی موبایل،
  افت قیمت → علامت‌خوردن + صفر پیامک بدون الگو، افزایش قیمت → بی‌اثر، افت با موجودی صفر → فعال می‌ماند،
  اشتراک مجدد → فعال دوباره، لغو توسط کاربر، رد موبایل نامعتبر.
- `PreOrderTest` (۷): افزودنی‌بودن پیش‌فروشِ ناموجود، بلاک‌شدن معمولیِ ناموجود در سبد، افزودن مهمان به سبد وب،
  سقف یک عدد (وب)، سقف یک عدد + 422 در API (با x-api-key)، عبور check_cart_quantity،
  لغو خودکار → موجودی پیش‌فروش دست‌نخورده.
- **رگرسیون محیط سندباکس**: MariaDB ۱۱.۴.۵ پرتابل نصب شد (port 3307 + .env.testing)؛ مایگریشن کامل ۲۰۱/۲۰۱
  سبز؛ کل مجموعه ۳۷۸ تست اجرا شد — ۴۰ خطا/شکستِ موجود **با git stash اثبات شد که پیش از این فاز هم
  روی دیتابیس تازهٔ بدون seed وجود داشتند** (FK province_id=1 در ShopTestSetup، پیشوند ادمین، ThemeActivation).
  سابست نواحی تغییریافته: Cart+Catalog+Extra+Unit = ۱۶۲ تست، فقط همان ۱ شکستِ ازقبل‌موجود.

### ۵) درمان روی هاست (ترتیب دقیق برای کاربر)
```
cd /home/owqdlxcv/repositories/shop.opashop.ir
git status --short | head
git pull origin docs/project-analysis
php artisan optimize:clear
# (پیشنهادی) php artisan migrate --force   ← دو مایگریشن جدید price_alerts و pre_order
```
- اگر پس از pull و optimize:clear خطای «column not found: pre_order» دیدید، یعنی مایگریشن اجرا نشده؛
  دستور بالا را بزنید.

**کامیت:** ۲ مایگریشن + PriceAlert/Job/Notification + Observer + ۵ سرویس پیامک + Sms::TYPES + Product
+ سبد وب/API + helpers + OrderController + CancelOrder + کنترلر/روت/ویو/JS فرانت + پنل قدیم و Livewire
+ ۳ فرم محصول + PACKAGES_ROADMAP.md + ۱۵ تست جدید + این فایل → docs/project-analysis

---

## فاز ۷۹ (بخش الف) — امتیازآوری پکیج‌های فروشی: پاکسازی مخزن‌ها، تعیین نقش AI، نصب خودکار DigiPay

> **زمینه:** طبق PACKAGES_ROADMAP (فاز ۷۸)، هر ۱۰ پکیج موجود کلون و کدنویسی بررسی شدند. این بخش،
> آیتم‌های کم‌ریسکِ «حد فروش» را اجرا و به مخزن‌های هر پکیج پوش کرد؛ ادغام قسطی/اعتباری و تست عمیق
> به «فاز ۷۹-ب» منتقل شد. تصمیم مدیر: پوشهٔ vendor داخل پکیج‌ها می‌ماند (خودکفایی).

### ۱) پاکسازی مخزن‌ها (۴ پوش جداگانه)
- **RackWidget** (`0fbedca`): حذف پوشهٔ `dist/` با ۳۵MB zip قدیمی (v1.5 تا v1.11) — مصنوعات توزیع در ریپوی سورس جایی ندارند؛ نصب مارکت سبک‌تر شد.
- **CreditPay v1.0.4** (`4101e63`): حذف ۴ ویوی legacy بدون ارجاع (users/index1، users/show2، settings/index1، front/checkout_hook_) + همگام‌سازی قواعد AGENTS.md + بامپ نسخه و چنج‌لاگ. راهنمای ادغام `OrderController_credit_section.php` عمداً حفظ شد (اتومات‌سازی در ۷۹-ب).
- **AIWriter** (`bfbc7f7`) و **AICatalog** (`aa02598`): بخش «تفکیک نقش با پکیج دیگر» در هر دو README — مرز شفاف: AIWriter=مقاله/بلاگ/اتوبلاگ، AICatalog=محتوای محصول/صفحه با گردش‌کار و QC. رفع ابهام خریدار در مارکت.

### ۲) DigiPay v1.2.0 — نصب/حذف کاملاً خودکار درگاه (`185c678`)
- کشف: هاست (PackageInstallerService) بعد از نصب فقط `Modules\DigiPay\Console\Commands\InstallPermissionsCommand` را صدا می‌زند؛ بوت‌زمانیِ ماژول هم فقط «ساختن» را بلد بود و هنگام حذف ماژول دیگر بوت نمی‌شود.
- راه‌حل: همان دستور نصب، ثبت درگاه را هم اجرا می‌کند؛ و در `--remove` درگاه unregister می‌شود تا درگاه مرده در چک‌اوت نماند. هر دو عملیات try/catch (خرابی، نصب ماژول را نمی‌شکند + راهنمای دستی چاپ می‌شود).

### ۳) کشف مهم: «مایگریشن تکراری» SeoAudit باگ نیست
- دو جفت فایل 2023_12_31/2024_01_01 با همان جدول، **عمداً** این‌طور هستند: نسخهٔ 2024 شیم تعمیری idempotent با گارد hasTable است که جای فایل قدیمی v2 باقی‌مانده روی سرور را می‌گیرد (مستند در کامنت فایل). حذف نشد — ردیف آن در نقشهٔ راه به «بررسی شد ✅» تغییر یافت.

### ۴) نتیجه
- ۵ پوش روی مخزن‌های پکیج‌ها + به‌روزرسانی PACKAGES_ROADMAP.md (جدول وضعیت + تفکیک ۷۹-الف/ب).
- گام بعدی (۷۹-ب): ادغام InstallmentPayment+CreditPay، اتومات‌سازی CheckoutTurbo/CreditPay، تست پایهٔ ۴ پکیج پول‌محور.

**کامیت:** PACKAGES_ROADMAP.md + این فایل → docs/project-analysis

---

## فاز ۷۹ (بخش ب) — ادغام پکیج قسطی+اعتباری، اتصال خودکار تم، تست پایهٔ پکیج‌های پول‌محور

> **زمینه:** طبق نقشهٔ راه، InstallmentPayment و CreditPay (۹۰٪ همپوشانی فرضی) در یک پکیج واحد ادغام شدند،
> اتصال تم به پکیج خودکار شد و تست پایهٔ پکیج‌های پول‌محور به مخزن هسته اضافه شد.

### ۱) کشف‌های بازبینی قبل از ادغام (تصحیح فرض قبلی)
- FinnotechService «تکراری» نبود: نسخهٔ InstallmentPayment **ابَرمجموعهٔ ۱۱۰۰ خطی** با ۱۸ سرویس KYC صادرات است و نسخهٔ ۲۸۶ خطی CreditPay فقط استعلام هویت دارد → ادغام یعنی «جایگزینی کوچک با بزرگ + حفظ قرارداد فلوی اعتباری».
- شاخهٔ اعتباری تم WeblakShop با API حفظ‌شدهٔ CreditService در v2 **بدون هیچ پچی** کار می‌کند — پچ دستی OrderController صرفاً ارتقای اختیاری است نه پیش‌نیاز.

### ۲) CreditPay v2.0.0 — ادغام کامل (کامیت `26fbb75`)
- **انتقال ساب‌سیستم اقساط طرح‌محور** با همان جدول‌های `installment_*` (۷ مدل، ۳ مایگریشن شماره‌گذاری‌شدهٔ ۰۰۱۰۱-۰۰۱۰۳ با گارد hasTable، ۱۰ کنترلر، ۲۳ ویو، CSS/JS) → ارتقای خریداران InstallmentPayment بدون ازدست‌دادن داده.
- **FinnotechService واحد (۱۴۰۰ خط)**: ۱۸ سرویس KYC + شیم‌های سازگار فلوی اعتباری (`verifyIdentity`/`saveResult`/`getToken` با کش مستقل `finnotech_access_token_credit`)؛ خوانندهٔ پیکربندی دوزبانه (`credit_settings` اولویت → `installment_settings` پشتیبان) تا تنظیمات ارتقایی‌ها زنده بماند.
- **سیم‌کشی کامل**: منوی ادمین/کاربر اقساطی + ویجت داشبورد، کامندهای `installments:process-overdue` (۰۹:۱۰) و `installments:send-reminders` (۱۰:۱۰)، پرمیشن‌های اقساطی در کامندهٔ واحد نصب پرمیشن، `ActivateInstallmentPlanAfterOrderPaid` روی OrderPaid + `InstallmentOrderObserver` برای لغو خودکار.
- **پاکسازی ارجاع‌های بین‌ماژولی**: گیت `module_is_active('InstallmentPayment')` در checkout_hook و CreditService به گیت داخلی `installment_settings.enabled` (با گارد hasTable) تبدیل شد؛ ۴ پوشهٔ ویو هم‌نام با ویوهای خود CreditPay به پیشوند `installment_` منتقل شد (صفر برخورد نام ویو بین دو ساب‌سیستم).
- InstallmentPayment آرشیو شد: بنر توقف توسعه + راهنمای مهاجرت ۵-مرحله‌ای بدون ازدست‌دادن داده (`5a0e9d0`).

### ۳) v2.0.1 — اتصال خودکار تم: `credit:wire-theme` (کامیت `5caa071`)
- کشف بحرانی: تم WeblakShop هنوز به ویوهای `installment-payment::` قدیمی include می‌زند → پس از مهاجرت به v2، کرش «view not found» قطعی بود.
- دستور idempotent با بکاپ خودکار (`*.bak-creditpay`): ۱) اصلاح includeهای قدیمی به `credit-pay::front.installment_checkout_hook` / `installment_sidebar_hook` + تبدیل گاردها؛ ۲) درج هوک‌های غایب بعد از هوک اعتباری (نصب تازه)؛ ۳) گزارش سلامت شاخهٔ اعتباری OrderController.
- منطق تبدیل روی **کپی فایل‌های واقعی تم** آزمایش شد: ۶/۶ چک (تبدیل، حذف namespace قدیمی، درج، تعویض گارد) + idempotency سبز.

### ۴) v2.0.2 — رفع باگ ارثی مایگریشن (کامیت `89dd2c3`)
- اجرای واقعی مایگریشن‌های ماژول روی MariaDB 11.4 باگ گرفت: ایندکس دوبل `due_date` (باگ ارثی از InstallmentPayment 1.0.3 — نصب تازهٔ آن هرگز روی MySQL سبز نمی‌شد). رفع شد و هر ۱۳ مایگریشن سبز شد.

### ۵) تست پایهٔ پکیج‌های پول‌محور — `tests/Feature/Packages/MoneyPackagesTest.php` (۱۰ تست)
- فلسفه: روی میزبانِ خریدارِ نصب‌کرده **راستی‌آزمایی واقعی**، روی میزبان بدون پکیج Skip تمیز با پیام روشن (بدون گمراه‌کردن).
- پوشش: مانیفست v2 و ساختار ادغام (۱۲ فایل حیاتی + مایگریشن + ویوهای هوک)، سیم‌کشی پرووایدر (کامند/لیسنر/آبزرور/منو)، قرارداد FinnotechService (۱۸ سرویس KYC + ۶ کلیدِ قرارداد verifyIdentity در حالت غیرفعال)، ریاضی کارمزد درصدی سبد و جریمهٔ دیرکرد (خالص و درون‌حافظه)، ثبت `credit:wire-theme`، سلامت CheckoutTurbo/DigiPay.
- **راستی‌آزمایی سندباکس**: ماژول موقتاً در Modules/ نصب، مایگریشن اجرا و ۸ تست سبز (۸۹ assertion) شد — سپس جداول rollback و ماژول پاک‌سازی شد (مخزن هسته تمیز ماند). سناریوی Checkout هسته هم ۲۱/۲۱ سبز ماند.

### نتیجه
- ۸ پوش/کامیت روی ۲ مخزن پکیج (CreditPay v2.0.2 + InstallmentPayment آرشیو) + مخزن هسته: ۱۰ تست جدید + PACKAGES_ROADMAP (جدول وضعیت + ۷۹-ب) + این فایل.
- ۸ از ۱۰ پکیج اکنون 🟢 فروش‌پذیرند؛ باقی‌ماندهٔ جزئی: مستندسازی Story و الگوی توزیع بدون zip.
- گام بعدی (فاز ۸۰): پکیج جدید «مارکتینگ و پیام‌رسانی» (سبد رهاشده + کمپین پیامکی + سگمنت‌بندی).

**کامیت:** tests/Feature/Packages/MoneyPackagesTest.php + PACKAGES_ROADMAP.md + این فایل → docs/project-analysis

---

## فاز ۸۰ — پکیج جدید «مارکتینگ و پیام‌رسانی» (سبد رهاشده + کمپین پیامکی + سگمنت‌بندی + گزارش کمپین)

### ۱) هسته — زیرساخت پیامک مارکتینگ (پریسدنت فاز ۷۸ برای PRICE_ALERT)
- دو نوع پیامک جدید در `Sms::TYPES`: `CART_RECOVERY` (یادآور سبد؛ متغیرهای name/items/total) و `MARKETING_CAMPAIGN` (کمپین؛ متغیرهای name/store) — متد `cartRecovery()`/`marketingCampaign()` در هر ۵ درایور (`Ippanel/Kavenegar/Melipayamak/IdehPardazan/Faraz`).
- UI پترن‌ها در `SmsSettings::patternDefinitions()` برای هر ۵ ارائه‌دهنده + **تسویهٔ بدهی فاز ۷۸**: `price_alert_pattern_code` که از UI جا مانده بود اضافه شد.
- تغییری در جریان ارسال نشد — همان `SmsChannel → SmsService → لاگ خودکار جدول sms` هسته.

### ۲) پکیج `MarketingMessaging` v1.0.0 (مخزن مستقل — الگوی DigiPay/CreditPay)
- **۵ مایگریشن گارددار `hasTable`**: `marketing_settings`، `marketing_segments` (معیارهای JSON)، `marketing_campaigns`، `marketing_campaign_targets` (یگانگی `campaign_id+user_id`)، `marketing_abandoned_carts` (یگانگی `cart_id+stage`).
- **سبد رهاشده**: کشف کلیدی — `attach/updateExistingPivot` فقط `updated_at` پارچ `cart_product` را عوض می‌کند → معیار رهاشدگی = `MAX(cart_product.updated_at)` نه `carts.updated_at`. کامند ساعتی `marketing:process-abandoned-carts` با دنبالهٔ دومرحله‌ای (۴ و ۲۴ ساعت، قابل‌تنظیم)، `--dry-run`، سقف batch، انقضای سبدِ خالی‌شده، بازیابی خودکار با `OrderPaid` (لیستنر `MarkCartRecovered`).
- **کمپین**: سگمنت با ۱۰ معیار (خریدار/غیرخریدار، تعداد/مبلغ خرید، بازهٔ ثبت‌نام، خرید در N روز اخیر، مشتری غایب، خبرنامه)، ارسال فوری/زمان‌بندی‌شده (`marketing:send-campaign` هر ۵ دقیقه)، توقف امن بدون پترن، گزارش تبدیل: لیستنر `TrackCampaignConversion` روی `OrderPaid` سفارشِ تا ۳۰ روز پس از کمپین را به گیرندهٔ پیامک وصل می‌کند (نرخ تبدیل + فروش ناشی).
- **تضمین رضایت (بدون استثنا)**: گیرنده = موبایل معتبر + `users.notification=1`؛ سبد مهمان/رزروشده/با سفارشِ پس از فعالیت مستثنی.
- **پنل دو-پنلی**: ۸ ویو (کمپین‌ها/ساخت/گزارش، سگمنت‌ها/ساخت، سبد رهاشده، تنظیمات) + منوی دو-شاخه‌ای `ModuleMenuRegistry` (پریسدنت DigiPay) — پرمیژن‌های `marketing.*`.
- **قرارداد نصب**: `marketing:install-permissions {--remove}` در مسیر ثابت `Modules\MarketingMessaging\Console\Commands` (قرارداد `PackageInstallerService`).

### ۳) تست — ۲۲ سبز / ۱۰۹ assertion (MariaDB ۱۱.۴ سندباکس)
- `tests/Unit/SmsMarketingTypesTest.php` (۶ تست): قرارداد TYPES + وجود متد در ۵ درایور + پترن‌های UI.
- `tests/Feature/Packages/MarketingMessagingTest.php` (۱۶ تست، الگوی MoneyPackagesTest — Skip تمیز بدون ماژول): مانیفست v1، قرارداد نصب‌کننده، گارد hasTable مایگریشن‌ها، منوی دو-پنلی، سگمنت‌سازی (رضایت/موبایل/سابقهٔ خرید/مبلغ)، مستثنیات سبد رهاشده (رضایت/مهمان/رزرو/سفارشِ پس از فعالیت)، دنبالهٔ دومرحله‌ای + ضدتکرار + انقضا، بازیابی با OrderPaid، ارسال کمپین + ضدتکرار + تبدیل، توقف امن بدون پترن، قرارداد متغیرهای قالب.
- نصب موقت ماژول در Modules/ + مایگریشن سبز (۵/۵) → تست → پاک‌سازی کامل (مخزن هسته تمیز می‌ماند).

**کامیت:** هسته (Sms + ۵ درایور + SmsSettings + ۲ تست + ROADMAP + این فایل) + مخزن جدید `shop-pro-MarketingMessaging-module` → docs/project-analysis

---

## فاز ۸۱ — شخصی‌سازی/غیرفعال‌سازی هشدار کاهش قیمت + تکمیل پیش‌فروش + اسنپ‌پی

**تاریخ**: ۱۴۰۴/۰۸/۰۳ | **شاخه**: docs/project-analysis | **تست**: ۲۵ سبز (۱۰ جدید + ۱۵ رگرسیون) + Unit ۲۳ سبز

### گزارش‌های کاربر و ریشه‌یابی
| گزارش کاربر | ریشه | رفع |
|---|---|---|
| «دکمه هشدار کاهش قیمت کار نمی‌کند» | binding مستقیم `$('#price_alert_btn').click()` بعد از جایگزینی AJAX بلوک product-info (تغییر تنوع/ویژگی) می‌میرد | ۴ binding → `$(document).on(...)` delegated |
| «از تنظیمات غیرفعالش کنیم» | هیچ کلیدی وجود نداشت | `dt_price_alert_status` + گیت کامل (دکمه/مودال/endpoint/Observer/Job) |
| «صفحهٔ ادمین داشته باشیم» | صفحه بود ولی آیتم منو نداشت | منوی پنل جدید config/admin-menu.php |
| «نوتیف خود فروشگاه» | هیچ نوتیفی نبود | `PriceAlertsSent` database+WebPush به مدیران + رجیستری + بلید پنل قدیم |
| «فیلد پترن در تنظیمات پیامک» | از فاز ۸۰ آماده بود | راستی‌آزمایی شد (۵ ارائه‌دهنده) |
| «متن دکمه و آیکون قابل شخصی‌سازی» | هاردکد | ۳ فیلد تم + پیش‌فرض |
| «پیش‌فروش در فرانت دیده نمی‌شود» | `$selected_price` فیلتر موجودی → null → شاخهٔ ناموجود | fallback قیمت منتشرشده + تنوع‌های پیش‌فروش‌آگاه + نشان کارت‌ها + سفارش‌ها |

### فایل‌های هسته (۱۹ فایل)
- مدل: `Product.php` (saleablePrices/isPreOrder/addableToCart/getLowestPrice/get_attributes/getPriceWithAttributes/hasAttributeStock)
- کنترلرها: تم ProductController (show/prices)، تم CartController، API CartController، ProductResource
- هشدار قیمت: ProcessPriceAlerts (گیت + نوتیف مدیران)، PriceObserver (گیت)، PriceAlertsSent (جدید)، AdminNotificationTypes، theme PriceAlertController (گیت)
- UI: show.js (delegated)، product-info-sidebar، show، product-card، product-block×2، carts/index، user/orders/show، back/orders/show، helpers (cart_min/max)، admin-menu.php، تم general.php (۶ فیلد تنظیمات + CSRF اسنپ‌پی)
- تست: `tests/Feature/Extra/Phase81Test.php` (۱۰ تست)

### پکیج‌ها (سوییچ به گزارش PACKAGES_ROADMAP فاز ۸۱)
DigiPay v1.3.0 (اسنپ‌پی + ری‌برندینگ) پوش شد؛ CreditPay/InstallmentPayment برگردانده شدند؛ InstallmentCredit آمادهٔ مخزن جدید؛ دو تصویر شاخص تولید شد.

## فاز ۸۲ (اصلاحی) — انتقال تنظیمات هشدار کاهش قیمت/پیش‌فروش به «تنظیمات دیگر» سایت + خودکفایی CSRF اسنپ‌پی + پوش مخزن InstallmentCredit

**تاریخ**: ۱۴۰۴/۰۸/۰۳ | **شاخه**: docs/project-analysis | **تست**: Phase82Test ۷ سبز (۱ اسکیپ تمیز بدون ماژول) + Phase81 ۹ سبز + Unit ۲۴ سبز

### درخواست کاربر و پاسخ
| درخواست کاربر | اقدام |
|---|---|
| «تنظیمات را به صفحه تنظیمات دیگر اضافه کن چون مربوط به خود سایت هستند» | ۶ فیلد (dt_price_alert_status/button_text/button_icon + dt_pre_order_status/button_text/badge_text) از تنظیمات قالب (themes/WeblakShop/config/general.php) حذف و به صفحهٔ «تنظیمات دیگر» منتقل شد — تب جدید «قابلیت‌های فروشگاه» در پنل جدید (Livewire Others) + سکشن در پنل قدیم (back/settings/others.blade.php)؛ ذخیره با فرمت «1»/«0» (سازگار با گیت‌های هسته) + قاعدهٔ «خالی بماند = پیش‌فرض» (نرمال‌سازی در هر دو پنل) |
| «تنظیمات پکیج باید در خود پکیج باشد» | کارت تنظیمات اسنپ‌پی از قبل در پکیج DigiPay است ✓؛ استثنای CSRF کال‌بک POST اسنپ‌پی که در تم مرده بود (exceptVerifyCsrfToken هیچ‌جا مصرف نمی‌شود) به خود پکیج منتقل شد: `withoutMiddleware([App\Http\Middleware\VerifyCsrfToken::class])` روی مسیر در routes/web.php — بدون وابستگی به تم؛ DigiPay v1.3.1 |
| «موارد را پوش کردی؟» | راستی‌آزمایی: کامیت فاز ۸۱ روی origin/docs/project-analysis بود؛ مخزن خالی InstallmentCredit (ساختهٔ کاربر) به ریموت وصل و پوش شد؛ مخزن‌های قدیمی CreditPay/InstallmentPayment دست‌نخورده تأیید شد |

### نکتهٔ محیطی مهم (تست‌ها)
- **DATABASE_URL سندباکس**: متغیر محیطی `DATABASE_URL=file:/home/z/my-project/db/custom.db` (مربوط به پروژهٔ Next.js همسایه) در ConnectionFactory لاراول (کلید `url` اتصال‌ها) پارس می‌شود → «Unsupported driver [file]». راه‌حل اجرای تست: `env -u DATABASE_URL php vendor/phpunit/phpunit/phpunit ...`
- **گیت‌های پرمیشن فقط خارج از CLI** تعریف می‌شوند (AuthServiceProvider) → در تست‌های Livewire ادمین، گیت باید دستی `Gate::define` شود.
- PHPUnit 12 مقدار `<server>` را فقط در `$_SERVER` می‌گذارد (نه `$_ENV`) — الگوی تست قبلی مشکلی ندارد ولی هنگام دیباگ env باید در نظر گرفته شود.

### فایل‌ها
- هسته: themes/WeblakShop/config/general.php (حذف ۶ فیلد + حذف CSRF مردهٔ اسنپ‌پی)، app/Livewire/Admin/Settings/Others.php (۶ پراپرتی + mount با «?:» + ذخیرهٔ «1»/«0» + نرمال‌سازی خالی→پیش‌فرض)، resources/views/admin-panel/livewire/admin/settings/others.blade.php (تب «قابلیت‌های فروشگاه»)، resources/views/back/settings/others.blade.php (سکشن جدید)، app/Http/Controllers/Back/SettingController.php (عناوین + نرمال‌سازی)، tests/Feature/Extra/Phase82Test.php (۷ تست)
- پکیج DigiPay v1.3.1: routes/web.php (withoutMiddleware خودمحور) + SnappPayController (داک‌بلاک) + module.json + README

## فاز ۸۳ — بازطراحی کامل هشدار کاهش قیمت: ریشهٔ «دکمه هنوز کار نمی‌کند»، شخصی‌سازی گسترده با پیش‌نمایش زنده، مودال مدرن با انتخاب پیامک/اعلان پروفایل، لیست زیرمنوی «اعلان‌ها»

**تاریخ**: ۱۴۰۴/۰۸/۰۳ | **شاخه**: docs/project-analysis | **تست**: Phase83Test ۱۵ سبز + Phase81/82 رگرسیون + PriceAlert/PreOrder فاز ۷۸ + Unit ۲۴ سبز

### درخواست کاربر و پاسخ
| درخواست کاربر | اقدام |
|---|---|
| «دکمه هشدار قیمت یک مارجین رایت دارد، حذف کن» | دکمه بازنویسی شد: کلاس‌های قدیمی (`product-stock-action btn btn-secondary cart-not-available w-100 mt-2` که مارجین ارثی می‌گرفتند) حذف → کلاس مستقل `dt-pa-btn` با `margin:10px 0 0` صریح؛ هیچ مارجین راست/چپی از هیچ قانونی نمی‌ماند |
| «شخصی‌سازی خیلی بیشتر باشد، نمونه تصویر را فرستادم (تنظیمات CheckoutTurbo) با ظاهر زیبا و مدرن» | ۹ فیلد جدید در تب «قابلیت‌های فروشگاه» (تنظیمات دیگر): رنگ دکمه (۶ سواچ گرادیانی)، اندازه (کوچک/متوسط/بزرگ)، گردی گوشه‌ها (۰-۳۰px اسلایدر)، موقعیت (زیر/بالای دکمهٔ خرید، چسبیده به قیمت)، متن/آیکون (با پیش‌نمایش آیکون)، عنوان/توضیح مودال، کانال‌های اطلاع‌رسانی (پیامک/اعلان پروفایل) + **کارت پیش‌نمایش زندهٔ خط‌چین** («همان چیزی که مشتری می‌بیند» — ماک کارت محصول تم با دکمهٔ زنده) + **پیش‌نمایش مودال** با ذخیرهٔ وایت‌لیستی (رنگ/اندازه/موقعیت → پیش‌فرض؛ گردی → کلمپ ۰-۳۰؛ کانال صفر → «هر دو»)؛ پنل قدیم هم فیلدها را دارد |
| «دکمه هشدار قیمت در فرانت کار نمی‌کند هنوز؛ مودال زیبا و مدرن با توضیح قابلیت + انتخاب پیامک/نوتیف پروفایل» | **ریشهٔ واقعی پیدا شد**: روی هاستِ بدون-symlink، دارایی‌های تم «کپی واقعی» در public/themes است و بعد از هر git pull کهنه می‌ماند → show.jsِ رفع‌شدهٔ فاز ۸۱ هرگز به سایت نرسیده بود! راه‌حل: (الف) سرویس `ThemeAssetSync` — در هر بوت تم (اسکن کش‌شدهٔ ۳۰ دقیقه‌ای) اگر کپی از منبع جدیدتر باشد (مقایسهٔ مُهر مارکر `.shoplink-copy` با maxMtime منبع) خودکار نوسازی می‌شود؛ پس از `git pull` + `optimize:clear` اولین بازدید تازه می‌کند، بدون shop:link دستی. (ب) کل CSS+JS قابلیت اینلاین در show.blade.php شد (بلید مستقیم از سورس سرو می‌شود) — قابلیت از هر هاستی به‌روز می‌رسد. (ج) مودال جدید: کارت شیشه‌ای با انیمیشن، آیکون زنگول متحرک، توضیح قابلیت، کارت‌های انتخاب کانال (پیامک/اعلان پروفایل — برای مهمان قفل با لینک ورود)، فیلد نام/موبایل مهمان، دکمهٔ گرادیانی با state بارگذاری، **حالت موفقیت با تیک انیمیشنی**؛ fetch با CSRF + پیام خطای درون‌خطی |
| «لیست کاربرانی که هشدار قیمت کلیک کردند در پنل نیست؛ زیرمنوی اعلان‌ها وقتی فعال است» | آیتم منو از «محصولات» به زیرمنوی «اعلان‌ها» منتقل شد (پنل جدید + قدیم) با **گیت تنظیماتی جدید**: کلید `enabled => dt_price_alert_status` در admin-menu.php و پشتیبانی سایدبار از آن؛ صفحهٔ لیست هم ارتقا یافت: تب‌های فیلتر (همه/در انتظار/اطلاع‌داده‌شده با شمارنده)، ستون «مشتری» (نشان کاربر عضو/مهمان + نام)، ستون «راه اطلاع‌رسانی» (نشان پیامک/اعلان پروفایل)، تاریخ اطلاع‌رسانی در مودال جزئیات |

### زیرساخت کانال‌ها
- مایگریشن گارددار: `notify_sms` (پیش‌فرض true — رفتار قدیمی) + `notify_profile` (پیش‌فرض false) در price_alerts
- کنترلر فرانت: پذیرش `channels[]` با وایت‌لیست از `dt_price_alert_channels`؛ مهمان همیشه فقط پیامک (حتی اگر پروفایل بخواهد)؛ «فقط پروفایل» + مهمان → 422 با پیام راهنما؛ بدون فیلد کانال (کلاینت فاز ۷۸) → «هر دو» (سازگاری رو به عقب)؛ پاسخ JSON برای مودال
- ProcessPriceAlerts: کانال پیامک فقط اگر `notify_sms` (و الگو تنظیم باشد)؛ کانال پروفایل → رکورد NotificationManage (private=user، عنوان «کاهش قیمت «...»») + پیوت notification_manage_users با **ضدتکرار روزانه هر کاربر** — در front profile/notifications با userRecipients دیده می‌شود؛ بستن هشدار بعد از پردازش مثل فاز ۷۸ (بدون حلقهٔ تکرار)
- مهم: آی‌دی دکمه از `price_alert_btn` به `dt_price_alert_btn` تغییر کرد (بی‌اثرکردن bindingهای کهنهٔ JS که روی هاست باقی مانده بود)؛ binding کلیک دلیگیت روی document است (سوئیچ تنوع با AJAX بلوک را جایگزین می‌کند — درس فاز ۸۱)

### نکتهٔ استقرار برای کاربر
- بعد از `git pull`: `php artisan migrate --force` (ستون‌های جدید) + `php artisan optimize:clear` — کپی دارایی‌های تم با اولین بازدید خودکار تازه می‌شود (یا فوراً `php artisan shop:link`)
- برای پیامک: الگوی `price_alert_pattern_code` در تنظیمات پیامک پنل باید پیکربندی شود؛ بدون الگو هشدار بدون پیامک بسته می‌شود (لاگ هشدار ثبت است)

### فایل‌ها
- هسته: app/Services/ThemeAssetSync.php (جدید) + ThemeAssetLinker (مارکر مُهدار) + themes/WeblakShop/src/ThemeServiceProvider.php (هوک بوت) — ریشهٔ «تغییرات را نمی‌بینم»
- تم: products/show.blade.php (مودال/CSS/JS اینلاین + حذف مودال قدیمی)، products/partials/product-info-sidebar.blade.php (دکمهٔ شخصی‌سازی‌پذیر با ۳ موقعیت)، assets/js/pages/products/show.js (حذف بایندهای قدیمی؛ ?v=14)
- هستهٔ پشتی: PriceAlertController تم (کانال‌ها/JSON)، ProcessPriceAlerts (کانال پروفایل)، PriceAlert model (casts)، مایگریشن
- پنل: Others Livewire (۹ فیلد + PALETTE/SIZES/PLACEMENTS + ذخیرهٔ وایت‌لیستی + channelsLabel)، price-alert-settings.blade.php (جدید — UI مدرن با پیش‌نمایش زنده)، others.blade.php (تب features)، back/settings/others.blade.php (پنل قدیم)، SettingController قدیم (نرمال‌سازی)، admin-menu.php + sidebar.blade.php (گیت enabled + جابه‌جایی منو)، PriceAlertIndex + price-alerts.blade.php (تب‌ها/ستون‌ها) + back/price-alerts/index.blade.php
- تست: tests/Feature/Extra/Phase83Test.php (۱۵ تست — ThemeAssetSync با مسیرهای override، کانال‌های کنترلر، اعلان پروفایل با ضدتکرار، منو با گیت، UI مستندات)

---

## فاز ۸۴ — هشدار کاهش قیمت: موقعیت «شناور در گوشهٔ صفحه» (۹ موقعیت) + «تگ دلخواه (سلکتور)» با پیش‌نمایش زنده، حذف دکمهٔ ذخیرهٔ بالایی تنظیمات دیگر، گیتِ خاموشی اثبات‌شده با رندر واقعی، برگشت منو به «محصولات» زیر «اطلاع‌رسانی موجودی»، تیک/بوردر انتخاب در مودال بلاک نشست‌ها

| درخواست کاربر | پاسخ |
|---|---|
| «موردی‌های شناور در گوشه صفحه و در تگ دلخواه در سلکتور را به موقعیت دکمه اضافه کن؛ دکمهٔ شناور هم دوباره شخصی‌سازی داره (راست،بالا،چپ،پایین،وسط یا چپ‌بالا،راست‌بالا و....) و شخصی‌سازی تگ دلخواه هم یک اینپوت داره که کلاس یا آیدی اضافه کنیم» | سلکت «موقعیت دکمه» (مثل نمونهٔ CheckoutTurbo) حالا ۵ گزینه دارد: زیر/بالای دکمهٔ خرید، چسبیده به قیمت، **شناور در گوشهٔ صفحه**، **در تگ دلخواه (سلکتور)**. برای شناور: **انتخاب‌گر ۹موقعیته** (بالا-چپ/بالا-وسط/بالا-راست، وسط-چپ/وسط صفحه/وسط-راست، پایین-چپ/پایین-وسط/پایین-راست) با تیک و بوردر روی گزینهٔ فعال + پیش‌نمایش زندهٔ ماکِت صفحهٔ مرورگر با دکمهٔ شناور که با تغییر موقعیت نرم جابه‌جا می‌شود. برای تگ دلخواه: **ریپیتر سلکتور** با ردیف‌های کلاس/آیدی + دکمهٔ سطل زباله + «افزودن سلکتور» (Enter هم می‌افزاید) + چیپ‌های سلکتورهای پرکاربرد قابل‌کلیک (#buy-box، .product-seller-row، .product-actions>.wrap، .product-carousel .owl-item) + راهنمای «از هر سلکتور فقط اولین عنصرِ منطبق دکمه می‌سازد؛ اگر هیچ‌کدام پیدا نشود، خودکار زیر دکمهٔ خرید» — ذخیرهٔ JSON با sanitize (حذف < > کاراکترهای خطرناک، سقف ۱۰ مورد، dedupe، حداکثر ۱۲۰ کاراکتر)؛ پنل قدیم هم سه فیلد جدید را دارد (سلکت موقعیت شناور + تکست‌اریای سلکتورها) |
| «در صفحه تنظیمات دیگر یک دکمه ذخیره در پایین داریم، دکمه بالایی رو حذف کن» | دکمهٔ submit سربرگ حذف شد؛ ذخیره فقط از نوار پایین (تست: دقیقاً یک `<button type="submit">` در کل صفحه) |
| «وقتی قابلیت هشدار کاهش قیمت را خاموش می‌کنم، در فرانت نمایش میده» | **اثبات با رندر واقعی صفحهٔ محصول** (GET صفحه در تست): با `dt_price_alert_status='0'` هم دکمه (`dt_price_alert_btn`) و هم مودال (`dt_pa_overlay`) از HTML حذف می‌شوند؛ با '1' هر دو هستند — ۱۶ تست فاز ۸۴ سبز. رفع تکمیلی: کلیدهای قابلیت‌های فروشگاه به `non_language_options` اضافه شدند (گیت در همهٔ زبان‌ها یک مقدار می‌خواند — قبلاً فرانتِ زبان غیر-fa می‌توانست پیش‌فرض '1' بگیرد). **نکتهٔ عملیاتی برای کاربر**: بعد از خاموش‌کردن سوییچ باید «ذخیره تغییرات» (نوار پایین) زده شود + روی هاست `php artisan optimize:clear` (کش blade/بوت) |
| «منوی هشدار کاهش قیمت را در محصولات و پایین منو اطلاع‌رسانی موجودی اضافه کن + پرمیژنش در config/general.php» | آیتم منو از «اعلان‌ها» (فاز ۸۳) به **«محصولات» دقیقاً بلافاصله بعد از «اطلاع‌رسانی موجودی»** برگشت — پنل جدید (admin-menu.php) و پنل قدیم (sidebar.blade.php)؛ گیت `enabled => dt_price_alert_status` حفظ شد (فقط وقتی قابلیت فعال باشد). پرمیژن `products.price-alert` از فاز ۷۸ در `config/general.php` بخش permissions ثبت است (تست راستی‌آزمایی شد)؛ اگر در صفحهٔ نقش‌ها دیده نمی‌شود یک‌بار `php artisan db:seed --class=PermissionSeeder` (سطر پرمیژن به جدول اضافه می‌شود) |
| «در نشست‌های فعال، مودال بلاک، بخش مدت زمان بلاک: کلیک روی هر مورد تیک یا بوردر داشته باشد» | علاوه بر peer-checked قبلی، حالا **بایندینگ مستقیم `$wire`** روی همهٔ کاشی‌هاست: بوردر رز + هاله + پس‌زمینهٔ تینت + بج تیک روی کاشی انتخاب‌شده — نوع بلاک (۵ گزینه + بن اکانت)، مدت زمان (۶ گزینه) و واحد مدت سفارشی (۴ گزینه). چون استایل به‌صورت اینلاین با Alpine اعمال می‌شود، حتی با CSS پنل کهنه روی هاست هم انتخاب کاملاً مشخص است (راستی‌آزمایی زندهٔ مرورگری: computed style بوردر rgb(225,29,72) + بج opacity:1 پس از کلیک؛ با جابه‌جایی انتخاب، بوردر از کاشی قبلی برداشته می‌شود) |

### جزئیات فرانت (تم)
- دکمهٔ شناور: `.dt-pa-float--{9موقعیت}` با `position:fixed`، عرض خودکار، سایهٔ عمیق‌تر، انیمیشن ورود، ریسپانسیو موبایل (فاصلهٔ ۱۰px و ارتفاع کمتر)
- تزریق با سلکتور: دکمهٔ منبع مخفی (`[hidden]`) + `#dt_pa_inject_config` (JSON سلکتورها) → تابع `dtPaInject()` (اینلاین show.blade.php): برای هر سلکتور فقط اولین عنصرِ منطبق، کلونِ بدون-آی‌دی با کلاس `dt-pa-btn--inject` بعد از آن درج می‌شود؛ اگر هیچ‌کدام منطبق نشد، دکمهٔ اصلی بدون مخفی‌بودن زیر `.btn-add-to-cart` برمی‌گردد (fallback امن). تابع گلوبال `window.dtPriceAlertInject` + هوک `ajaxSuccess` جی‌کوئری → بعد از جایگزینی AJAX بلوک محصول (تغییر تنوع) تزریق دوباره و idempotent است
- کلیک مودال از همهٔ نمونه‌ها: دلیگیت روی `.dt-pa-btn` (اصلی + شناور + کلون‌ها)
- CSS قابلیت همچنان کاملاً اینلاین در show.blade.php (مستقل از کپی assetهای هاست — درس فاز ۸۳)

### فایل‌های فاز ۸۴
- پنل جدید: app/Livewire/Admin/Settings/Others.php (PLACEMENTS/FLOAT_POSITIONS/SELECTOR_EXAMPLES + props آرایه‌ای سلکتورها + اکشن‌های paAddSelector/paRemoveSelector/paAddExampleSelector + sanitizeSelectors/parseSelectors + ذخیرهٔ JSON) + partials/price-alert-settings.blade.php (سلکت ۵گزینه‌ای + انتخاب‌گر ۹موقعیته + ریپیتر + چیپ‌ها + پیش‌نمایش ماکِت شناور) + others.blade.php (حذف دکمهٔ بالایی)
- پنل قدیم: back/settings/others.blade.php (سلکت موقعیت ۵گزینه + موقعیت شناور + تکست‌اریای سلکتورها) + SettingController::updateOthers (نرمال‌سازی float_pos/selectors)
- تم: product-info-sidebar.blade.php (رندر float/inject + خواندن گزینه‌ها) + show.blade.php (CSS شناور/تزریق + dtPaInject + هوک ajaxSuccess)
- منو/گیت: config/admin-menu.php + back/partials/sidebar.blade.php + config/general.php (non_language_options)
- نشست‌ها: admin-panel/.../system/sessions.blade.php (بایندینگ $wire روی نوع/مدت/واحد)
- تست: tests/Feature/Extra/Phase84Test.php (۱۶ تست: وایت‌لیست placement/float، sanitize و اکشن‌های سلکتور، رندر واقعی خاموشی، رندر شناور/سلکتور، جایگاه دقیق منو، پرمیژن، non_language_options، تک‌دکمه‌ای‌بودن ذخیره، بایندینگ‌های مودال بلاک، UI پنل قدیم/جدید) + به‌روزرسانی تست منوی Phase83 (تصمیم کاربر تغییر کرد)
