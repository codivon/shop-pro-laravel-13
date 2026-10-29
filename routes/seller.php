<?php

/*
|--------------------------------------------------------------------------
| Seller Panel Routes (فاز ۱۰۸ — مستقل از تم)
|--------------------------------------------------------------------------
| کل مسیرهای پنل فروشنده (ثبت‌نام/ورود/پنل) از تم به هسته منتقل شد تا تعویض
| قالب هیچ تأثیری روی پنل فروشنده نداشته باشد. نام‌ها و URIها عیناً مثل قبل
| هستند (seller.*) تا هیچ route() و تستی نشکند.
|
| پنل قدیمی: resources/views/sellers-old + App\Http\Controllers\Seller
| پنل مدرن (لایوایر ۴): وقتی option seller_panel_modern روشن باشد (پیش‌فرض
| روشن — سوییچ در برنامه‌نویس ← تب «چندفروشندگی»)، صفحات GET از
| App\Livewire\Seller سرو می‌شوند؛ اندپوینت‌های POST/PUT/DELETE و خروجی‌های
| اکسل/پرینت (داده‌ای و قابل‌چاپ) همیشه همان کنترلرهای قدیمی‌اند — در هر دو
| حالت یک منبع حقیقت برای منطق کسب‌وکار.
|
| ⚠️ خواندن option در سطح تعریف روت مثل روت‌های تم فقط با لود per-request
| کار می‌کند — پروژه از route:cache استفاده نمی‌کند (روت‌های تم closure دارند).
|
| ثبت: RouteServiceProvider — خارج از حلقهٔ locale، با میدل‌ور web.
*/

use App\Http\Controllers\Back\HolidayController;
use App\Http\Controllers\Seller\DashboardSellerController;
use App\Http\Controllers\Seller\NotificationSellerController;
use App\Http\Controllers\Seller\OrderSellerController;
use App\Http\Controllers\Seller\ProductSellerController;
use App\Http\Controllers\Seller\ProfileSellerController;
use App\Http\Controllers\Seller\QuestionSellerController;
use App\Http\Controllers\Seller\SellerCarrierController;
use App\Http\Controllers\Seller\SellerController;
use App\Http\Controllers\Seller\SellerDemandController;
use App\Http\Controllers\Seller\SellerInventoryController;
use App\Http\Controllers\Seller\SellerRegistrationController;
use App\Http\Controllers\Seller\SellerReviewController;
use App\Http\Controllers\Seller\SellerStatisticsController;
use App\Http\Controllers\Seller\SellerTariffController;
use App\Http\Controllers\Seller\SellerTicketController;
use App\Http\Controllers\Seller\WalletSellerController;
use App\Livewire\Seller\Auth\BusinessDetails;
use App\Livewire\Seller\Auth\Checkout;
use App\Livewire\Seller\Auth\Documents;
use App\Livewire\Seller\Auth\Landing;
use App\Livewire\Seller\Auth\Login;
use App\Livewire\Seller\Auth\Register;
use App\Livewire\Seller\Auth\RegisterMobile;
use App\Livewire\Seller\Auth\SellerIdentity;
use App\Livewire\Seller\Auth\SellerPassword;
use App\Livewire\Seller\Auth\SellerType;
use App\Livewire\Seller\Carriers\Create as CarrierCreate;
use App\Livewire\Seller\Carriers\Edit as CarrierEdit;
use App\Livewire\Seller\Carriers\Index as CarrierIndex;
use App\Livewire\Seller\Dashboard;
use App\Livewire\Seller\Demand;
use App\Livewire\Seller\Inventory;
use App\Livewire\Seller\Notifications;
use App\Livewire\Seller\Orders\Index as OrdersIndex;
use App\Livewire\Seller\Orders\NotCompleted;
use App\Livewire\Seller\Orders\Show as OrdersShow;
use App\Livewire\Seller\Products\Create as ProductCreate;
use App\Livewire\Seller\Products\Edit as ProductEdit;
use App\Livewire\Seller\Products\Find as ProductFind;
use App\Livewire\Seller\Products\Index as ProductIndex;
use App\Livewire\Seller\Products\Variants as ProductVariants;
use App\Livewire\Seller\Profile;
use App\Livewire\Seller\Questions\Index as QuestionsIndex;
use App\Livewire\Seller\Questions\Show as QuestionsShow;
use App\Livewire\Seller\Reviews;
use App\Livewire\Seller\Statistics\Index as StatisticsIndex;
use App\Livewire\Seller\Statistics\ViewCounts;
use App\Livewire\Seller\Statistics\ViewerCounts;
use App\Livewire\Seller\Tariffs\Create as TariffCreate;
use App\Livewire\Seller\Tariffs\Edit as TariffEdit;
use App\Livewire\Seller\Tariffs\Index as TariffIndex;
use App\Livewire\Seller\Tickets\Create as TicketCreate;
use App\Livewire\Seller\Tickets\Index as TicketIndex;
use App\Livewire\Seller\Tickets\Show as TicketShow;
use App\Livewire\Seller\Wallet\Commission;
use App\Livewire\Seller\Wallet\Index as WalletIndex;
use App\Livewire\Seller\Wallet\Settlements;
use App\Livewire\Seller\Wallet\Show as WalletShow;
use App\Support\MultiVendorSettings;

Route::group(['as' => 'seller.', 'prefix' => 'seller'], function () {

    $modern = MultiVendorSettings::modernPanelEnabled();

    // ========== صفحات عمومی فروشنده (بدون لاگین) ==========
    Route::get('/', $modern ? Landing::class : [SellerController::class, 'index'])->name('index');

    // لاگین
    Route::get('login', $modern ? Login::class : [SellerController::class, 'login'])->name('login');
    Route::post('login', [SellerController::class, 'login_check'])->name('login_check');

    // فاز ۱۱۲ — روش دوم ورود: موبایل + کد تأیید ۶ رقمی (فقط پنل مدرن)
    if ($modern) {
        Route::post('login/otp/send', [SellerController::class, 'login_otp_send'])->name('login_otp_send');
        Route::post('login/otp/verify', [SellerController::class, 'login_otp_verify'])->name('login_otp_verify');
    }

    // ثبت‌نام (۵ گام — فرم‌های POST در هر دو حالت به همان کنترلر قدیمی می‌روند)
    Route::get('registration', $modern ? Register::class : [SellerController::class, 'registration'])->name('registration');
    Route::post('registration', [SellerController::class, 'registration_new_seller'])->name('registration_new_seller');
    Route::get('registration/mobile', $modern ? RegisterMobile::class : [SellerController::class, 'registration_mobile'])->name('registration_mobile');
    Route::post('registration/mobile', [SellerController::class, 'registration_mobile_check'])->name('registration_mobile_check');

    // فاز ۱۱۰ — ثبت‌نام مرحله‌ای جدید (فقط پنل مدرن): موبایل ← کد ۶ رقمی ←
    // وضعیت ← اطلاعات تکمیلی (فینوتک) ← کسب‌وکار ← مدارک ← پایان.
    // ذخیرهٔ هر مرحله در seller_registrations + گیت ترتیب مراحل سمت سرور.
    Route::get('registration/type', $modern ? SellerType::class : [SellerController::class, 'registration'])->name('registration_type');
    Route::get('registration/identity', $modern ? SellerIdentity::class : [SellerController::class, 'registration'])->name('registration_identity');

    if ($modern) {
        Route::post('registration/phone', [SellerRegistrationController::class, 'sendPhoneOtp'])->name('registration_phone_send');
        Route::post('registration/verify', [SellerRegistrationController::class, 'verifyPhoneOtp'])->name('registration_phone_verify');
        Route::post('registration/resend', [SellerRegistrationController::class, 'resendPhoneOtp'])->name('registration_phone_resend');
        Route::post('registration/password', [SellerRegistrationController::class, 'savePassword'])->name('registration_password_store');
        Route::post('registration/type', [SellerRegistrationController::class, 'saveType'])->name('registration_type_store');
        Route::post('registration/identity', [SellerRegistrationController::class, 'saveIdentity'])->name('registration_identity_store');
        Route::post('registration/business', [SellerRegistrationController::class, 'saveBusiness'])->name('registration_business_store');
    }

    // گام رمز عبور (فاز ۱۱۲ — فقط پنل مدرن؛ پنل قدیمی به ثبت‌نام برمی‌گردد)
    Route::get('registration/password', $modern ? SellerPassword::class : [SellerController::class, 'registration'])->name('registration_password');

    // مرحله 1: اطلاعات کسب و کار
    Route::get('registration/business-details', $modern ? BusinessDetails::class : [SellerController::class, 'registration_business_details'])->name('registration_business_details');
    Route::post('registration/business-details', [SellerController::class, 'registration_business_details_store'])->name('registration_business_details_store');

    // مرحله 2: مدارک
    Route::get('registration/documents', $modern ? Documents::class : [SellerController::class, 'registration_documents'])->name('registration_documents');
    Route::post('registration/documents', [SellerController::class, 'registration_documents_store'])->name('registration_documents_store');
    Route::delete('registration/documents', [SellerController::class, 'registration_documents_delete'])->name('registration_documents_delete');
    Route::post('registration/documents-check', [SellerController::class, 'registration_documents_check'])->name('registration_documents_check');

    // مرحله 3: نهایی
    Route::get('registration/checkout', $modern ? Checkout::class : [SellerController::class, 'registration_checkout'])->name('registration_checkout');
    Route::post('registration/checkout', [SellerController::class, 'registration_checkout_store'])->name('registration_checkout_store');

    // دریافت کد جدید
    Route::post('/get-new-code-for-seller', [SellerController::class, 'get_new_code'])->name('get-new-code-for-seller');

    // ========== پنل فروشنده (نیاز به لاگین) ==========
    Route::group(['middleware' => ['Seller']], function () use ($modern) {

        // خروج (هر دو حالت — کنترلر قدیمی)
        Route::get('logout', [SellerController::class, 'logout'])->name('logout');

        // داشبورد
        Route::get('dashboard', $modern ? Dashboard::class : [DashboardSellerController::class, 'index'])->name('dashboard');

        // ========== مدیریت محصولات ==========
        Route::prefix('products')->name('products.')->group(function () use ($modern) {
            Route::get('/', $modern ? ProductIndex::class : [ProductSellerController::class, 'index'])->name('index');
            Route::post('api/index', [ProductSellerController::class, 'apiIndex'])->name('apiIndex');
            // فاز ۱۰۶-۶ — خروجی اکسل؛ روت ثابت قبل از وایلدکارد {product} (درس فاز ۹۱)
            // (خروجی داده‌ای است — هر دو حالت کنترلر قدیمی)
            Route::get('export', [ProductSellerController::class, 'export'])->name('export');
            Route::get('create', $modern ? ProductCreate::class : [ProductSellerController::class, 'create'])->name('create');
            Route::post('store', [ProductSellerController::class, 'store'])->name('store');
            Route::get('{product}/edit', $modern ? ProductEdit::class : [ProductSellerController::class, 'edit'])->name('edit');
            Route::put('{product}/edit', [ProductSellerController::class, 'update'])->name('update');
            Route::post('image-store', [ProductSellerController::class, 'image_store'])->name('image-store');
            Route::post('image-delete', [ProductSellerController::class, 'image_delete'])->name('image-delete');
            Route::get('find', $modern ? ProductFind::class : [ProductSellerController::class, 'find'])->name('find');
            Route::post('find/api/index', [ProductSellerController::class, 'apiIndexFind'])->name('apiIndexFind');
            Route::get('{product_id}/variant', $modern ? ProductVariants::class : [ProductSellerController::class, 'variant'])->name('variant');
            Route::post('{product}/variant', [ProductSellerController::class, 'variant_store'])->name('variant.store');
        });

        // ========== کیف پول و کمیسیون ==========
        Route::get('commission', $modern ? Commission::class : [WalletSellerController::class, 'commission'])->name('commission');
        // فاز ۱۲۱ — صفحهٔ تکراری «نرخ کمیسیون» (commission-guide) حذف شد؛ از ابتدا
        // عیناً همان دادهٔ «کمیسیون و هزینه‌ها» را سرو می‌کرد (کنترلر قدیمی هم
        // showCommissionRates() را به commission() وصل می‌کرد). منبع واحد: seller.commission.

        Route::prefix('wallet')->name('wallet.')->group(function () use ($modern) {
            Route::get('/', $modern ? WalletIndex::class : [WalletSellerController::class, 'index'])->name('index');
            // فاز ۱۰۶-۱ — صورت‌حساب تسویه (گیت از تنظیمات چندفروشندگی) —
            // قبل از وایلدکارد {wallet} تا مسیرش بلعیده نشود (درس فاز ۹۱: روت ثابت قبل از وایلدکارد)
            Route::get('settlements', $modern ? Settlements::class : [WalletSellerController::class, 'settlements'])->name('settlements');
            // خروجی اکسل صورت‌حساب — داده‌ای است، هر دو حالت کنترلر قدیمی
            Route::get('settlements/export', [WalletSellerController::class, 'settlementsExport'])->name('settlements.export');
            Route::get('{wallet}', $modern ? WalletShow::class : [WalletSellerController::class, 'show'])->name('show');
            Route::post('store', [WalletSellerController::class, 'store'])->name('store');
            Route::post('withdraw', [WalletSellerController::class, 'withdraw'])->name('withdraw');
            Route::any('payment/callback/{gateway}', [WalletSellerController::class, 'verify'])->name('verify');
        });

        // ========== پروفایل فروشنده ==========
        Route::prefix('profile')->name('profile.')->group(function () use ($modern) {
            Route::get('/', $modern ? Profile::class : [ProfileSellerController::class, 'index'])->name('index');
            Route::post('/', [ProfileSellerController::class, 'store'])->name('store');
            Route::put('{seller}/update', [ProfileSellerController::class, 'update'])->name('update');
            Route::post('set-econtract', [ProfileSellerController::class, 'set_econtract'])->name('set_econtract');
        });

        // ========== اعلان‌ها ==========
        Route::prefix('notifications')->name('notifications.')->group(function () use ($modern) {
            Route::get('/', $modern ? Notifications::class : [NotificationSellerController::class, 'index'])->name('index');
            Route::post('{notification}/read', [NotificationSellerController::class, 'read'])->name('read');
        });

        // ========== پرسش و پاسخ ==========
        // فاز ۱۰۸ — در حالت مدرن فقط index/show از لایوایر سرو می‌شود؛ بقیهٔ
        // اکشن‌های resource (store/update/destroy/edit) همان کنترلر قدیمی‌اند.
        if ($modern) {
            Route::get('questions', QuestionsIndex::class)->name('questions.index');
            Route::get('questions/{question}', QuestionsShow::class)->name('questions.show');
            Route::resource('questions', QuestionSellerController::class)->only(['create', 'store', 'edit', 'update', 'destroy'])->names([
                'create' => 'questions.create',
                'store' => 'questions.store',
                'edit' => 'questions.edit',
                'update' => 'questions.update',
                'destroy' => 'questions.destroy',
            ]);
        } else {
            Route::resource('questions', QuestionSellerController::class)->names([
                'index' => 'questions.index',
                'create' => 'questions.create',
                'store' => 'questions.store',
                'show' => 'questions.show',
                'edit' => 'questions.edit',
                'update' => 'questions.update',
                'destroy' => 'questions.destroy',
            ]);
        }

        // ========== مدیریت سفارشات ==========
        Route::prefix('orders')->name('orders.')->group(function () use ($modern) {
            Route::get('/', $modern ? OrdersIndex::class : [OrderSellerController::class, 'index'])->name('index');
            Route::post('api/index', [OrderSellerController::class, 'apiIndex'])->name('apiIndex');
            Route::post('api/shippings-status', [OrderSellerController::class, 'shippingsStatus'])->name('shippings-status');
            Route::post('{order}/shipping-status', [OrderSellerController::class, 'shipping_status'])->name('shipping-status');
            // پرینت/خروجی — صفحات مستقل قابل‌چاپ، هر دو حالت کنترلر قدیمی
            Route::get('{order}/print', [OrderSellerController::class, 'print'])->name('print');
            Route::delete('api/multipleDestroy', [OrderSellerController::class, 'multipleDestroy'])->name('multipleDestroy');
            Route::get('not-completed', $modern ? NotCompleted::class : [OrderSellerController::class, 'notCompleted'])->name('notCompleted');
            Route::get('api/printAllShippingForms', [OrderSellerController::class, 'printAllShippingForms'])->name('printAllShippingForms');
            Route::get('api/printAll', [OrderSellerController::class, 'printAll'])->name('printAll');
            Route::get('api/printAllShippingForms', [OrderSellerController::class, 'printAllShippingForms'])->name('printAllShippingForms');
            Route::get('api/printAllShippingFormsMin', [OrderSellerController::class, 'printAllShippingFormsMin'])->name('printAllShippingFormsMin');

            Route::get('export/create', [OrderSellerController::class, 'export'])->name('export');

            // Resource routes
            Route::get('{order}', $modern ? OrdersShow::class : [OrderSellerController::class, 'show'])->name('show');
            Route::put('{order}', [OrderSellerController::class, 'update'])->name('update');
            Route::delete('{order}', [OrderSellerController::class, 'destroy'])->name('destroy');
        });

        // ========== روش‌های ارسال ==========
        // فاز ۱۰۸ — در حالت مدرن صفحات GET از لایوایر؛ store/update/destroy قدیمی
        if ($modern) {
            Route::get('carriers', CarrierIndex::class)->name('carriers.index');
            Route::get('carriers/create', CarrierCreate::class)->name('carriers.create');
            Route::get('carriers/{carrier}/edit', CarrierEdit::class)->name('carriers.edit');
            Route::resource('carriers', SellerCarrierController::class)->only(['show', 'store', 'update', 'destroy'])->names([
                'show' => 'carriers.show',
                'store' => 'carriers.store',
                'update' => 'carriers.update',
                'destroy' => 'carriers.destroy',
            ]);

            Route::get('tariffs', TariffIndex::class)->name('tariffs.index');
            Route::get('tariffs/create', TariffCreate::class)->name('tariffs.create');
            Route::get('tariffs/{tariff}/edit', TariffEdit::class)->name('tariffs.edit');
            Route::resource('tariffs', SellerTariffController::class)->only(['show', 'store', 'update', 'destroy'])->names([
                'show' => 'tariffs.show',
                'store' => 'tariffs.store',
                'update' => 'tariffs.update',
                'destroy' => 'tariffs.destroy',
            ]);
        } else {
            Route::resource('carriers', SellerCarrierController::class)->names([
                'index' => 'carriers.index',
                'create' => 'carriers.create',
                'store' => 'carriers.store',
                'show' => 'carriers.show',
                'edit' => 'carriers.edit',
                'update' => 'carriers.update',
                'destroy' => 'carriers.destroy',
            ]);

            Route::resource('tariffs', SellerTariffController::class)->names([
                'index' => 'tariffs.index',
                'create' => 'tariffs.create',
                'store' => 'tariffs.store',
                'show' => 'tariffs.show',
                'edit' => 'tariffs.edit',
                'update' => 'tariffs.update',
                'destroy' => 'tariffs.destroy',
            ]);
        }

        // ========== تیکت‌ها ==========
        if ($modern) {
            Route::get('tickets', TicketIndex::class)->name('tickets.index');
            Route::get('tickets/create', TicketCreate::class)->name('tickets.create');
            Route::get('tickets/{ticket}', TicketShow::class)->name('tickets.show');
            Route::resource('tickets', SellerTicketController::class)->only(['edit', 'store', 'update'])->names([
                'edit' => 'tickets.edit',
                'store' => 'tickets.store',
                'update' => 'tickets.update',
            ]);
        } else {
            Route::resource('tickets', SellerTicketController::class)->except(['destroy'])->names([
                'index' => 'tickets.index',
                'create' => 'tickets.create',
                'store' => 'tickets.store',
                'show' => 'tickets.show',
                'edit' => 'tickets.edit',
                'update' => 'tickets.update',
            ]);
        }

        Route::prefix('holidays')->name('holidays.')->group(function () {
            Route::get('/', [HolidayController::class, 'index'])->name('index');
            Route::post('/check', [HolidayController::class, 'check'])->name('check');
            Route::post('/convert-to-jalali', [HolidayController::class, 'convertToJalali'])->name('convert-to-jalali');
            Route::post('/get-start-dates', [HolidayController::class, 'getStartDates'])->name('get-start-dates');
            Route::post('/update/{year}', [HolidayController::class, 'updateHolidays'])->name('update');
            Route::get('/get/{year}', [HolidayController::class, 'getHolidaysByYear'])->name('get');
        });

        // آمار فروشنده
        Route::prefix('statistics')->name('statistics.')->group(function () use ($modern) {
            Route::get('/', $modern ? StatisticsIndex::class : [SellerStatisticsController::class, 'index'])->name('index');
            Route::get('/view-counts', $modern ? ViewCounts::class : [SellerStatisticsController::class, 'viewCounts'])->name('viewCounts');
            Route::get('/viewer-counts', $modern ? ViewerCounts::class : [SellerStatisticsController::class, 'viewerCounts'])->name('viewerCounts');
            // Route::get('/latest-visitors', [SellerStatisticsController::class, 'latestVisitors'])->name('latestVisitors');
        });

        // فاز ۱۰۶-۳ — سیگنال‌های تقاضا (منتظر موجودی / منتظر کاهش قیمت)
        // گیت سوییچ داخل کنترلر/کامپوننت است (خاموش → داشبورد + toast)
        Route::get('demand-signals', $modern ? Demand::class : [SellerDemandController::class, 'index'])->name('demand.signals');

        // فاز ۱۰۶-۴ — نظرات محصولات از نگاه فروشنده (فقط-خواندنی؛ تأیید/رد با ادمین)
        Route::get('reviews', $modern ? Reviews::class : [SellerReviewController::class, 'index'])->name('reviews.index');

        // فاز ۱۰۶-۵ — مرکز موجودی (ناموجود/کم‌موجود + تاریخچهٔ حرکت انبار — فقط-خواندنی)
        Route::get('inventory', $modern ? Inventory::class : [SellerInventoryController::class, 'index'])->name('inventory.index');
    });
});
