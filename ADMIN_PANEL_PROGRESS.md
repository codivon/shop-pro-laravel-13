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
