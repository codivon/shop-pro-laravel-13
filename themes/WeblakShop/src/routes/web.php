<?php

use App\Http\Controllers\Back\HolidayController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Themes\WeblakShop\src\Controllers\AddressesController;
use Themes\WeblakShop\src\Controllers\ArticleController;
use Themes\WeblakShop\src\Controllers\BlogController;
use Themes\WeblakShop\src\Controllers\BrandController;
use Themes\WeblakShop\src\Controllers\CartController;
use Themes\WeblakShop\src\Controllers\CommentController;
use Themes\WeblakShop\src\Controllers\ContactController;
use Themes\WeblakShop\src\Controllers\DiscountController;
use Themes\WeblakShop\src\Controllers\FavoriteController;
use Themes\WeblakShop\src\Controllers\FormController;
use Themes\WeblakShop\src\Controllers\MainController;
use Themes\WeblakShop\src\Controllers\NewsletterController;
use Themes\WeblakShop\src\Controllers\OrderController;
use Themes\WeblakShop\src\Controllers\PageController;
use Themes\WeblakShop\src\Controllers\PriceAlertController;
use Themes\WeblakShop\src\Controllers\ProductController;
use Themes\WeblakShop\src\Controllers\ReturnController;
use Themes\WeblakShop\src\Controllers\ReviewController;
use Themes\WeblakShop\src\Controllers\SellerController;
use Themes\WeblakShop\src\Controllers\sellers\DashboardSellerController;
use Themes\WeblakShop\src\Controllers\sellers\NotificationSellerController;
use Themes\WeblakShop\src\Controllers\sellers\OrderSellerController;
// seller controller
use Themes\WeblakShop\src\Controllers\sellers\ProductSellerController;
use Themes\WeblakShop\src\Controllers\sellers\ProfileSellerController;
use Themes\WeblakShop\src\Controllers\sellers\QuestionSellerController;
use Themes\WeblakShop\src\Controllers\sellers\SellerCarrierController;
use Themes\WeblakShop\src\Controllers\sellers\SellerStatisticsController;
use Themes\WeblakShop\src\Controllers\sellers\SellerTariffController;
use Themes\WeblakShop\src\Controllers\sellers\SellerTicketController;
use Themes\WeblakShop\src\Controllers\sellers\WalletSellerController;
use Themes\WeblakShop\src\Controllers\SitemapController;
use Themes\WeblakShop\src\Controllers\StockNotifyController;
use Themes\WeblakShop\src\Controllers\TicketController;
use Themes\WeblakShop\src\Controllers\UserController;
use Themes\WeblakShop\src\Controllers\UserHistoryController;
use Themes\WeblakShop\src\Controllers\VerifyController;
use Themes\WeblakShop\src\Controllers\WalletController;

// ------------------ Front Part Routes

Route::group(['as' => 'front.'], function () {
    // ------------------ MainController
    Route::get('/', [MainController::class, 'index'])->name('index');

    Route::get('/stores', [MainController::class, 'showStore'])->name('showStore');
    Route::get('/store/{seller}', [MainController::class, 'showSellerStore'])->name('showSellerStore');
    // ------------------ blogs
    Route::get('blog/search', [BlogController::class, 'search'])->name('blog.search');

    Route::prefix('blog')->group(function () {
        Route::get('', [BlogController::class, 'index'])->name('blog.index');

        // روت‌های اصلی
        Route::get('/articles', [ArticleController::class, 'index'])->name('articles.index');
        Route::get('/articles/{slug}', [ArticleController::class, 'show'])->name('articles.show');

        // روت لایک مقاله
        Route::post('/articles/like', [ArticleController::class, 'likeToggle'])->name('articles.like');

        // روت‌های نظرات
        Route::prefix('/articles')->name('articles.comments.')->group(function () {
            Route::post('/{post}/comments', [ArticleController::class, 'commentStore'])->name('store');
            Route::post('/comments/reply', [ArticleController::class, 'commentReply'])->name('reply');
            Route::post('/comments/{comment}/like', [ArticleController::class, 'commentLike'])->name('like');
        });
    });

    //    Route::resource('blog/articles', ArticleController::class)->only(['index', 'show']);
    //    Route::get('blogs/category/{category}', [BlogController::class, 'category'])->name('blogs.category');
    //    Route::get('blogs/tag/{slug}', [BlogController::class, 'tag'])->name('blogs.tag');

    // ------------------ products

    Route::get('/get-stores-cached/{productId}/{colorId}', function ($productId, $colorId) {
        $cacheKey = "stores_{$productId}_{$colorId}";

        return Cache::remember($cacheKey, 300, function () use ($productId, $colorId) {
            // فراخوانی متد get_stores
            return app()->call('App\Http\Controllers\Front\ProductController@get_stores', [
                'request' => new Request([
                    'product_id' => $productId,
                    'color_id' => $colorId,
                ]),
            ]);
        });
    })->name('get-stores-cached');

    Route::get('search/category/{category}', [ProductController::class, 'category'])->name('products.category');
    Route::get('search/category-products/{category}', [ProductController::class, 'categoryProducts'])->name('products.category-products');
    Route::get('search/category-specials/{category}', [ProductController::class, 'categorySpecials'])->name('products.category-specials');

    Route::get('product/specials', [ProductController::class, 'specials'])->name('products.specials');
    Route::get('product/discount', [ProductController::class, 'discount'])->name('products.discount');
    Route::get('product/{product}/prices', [ProductController::class, 'prices'])->name('products.prices');
    Route::get('product/compare/{product1}/{product2?}/{product3?}', [ProductController::class, 'compare'])->name('products.compare');
    Route::post('product/compare', [ProductController::class, 'similarCompare'])->name('products.similar-compare');
    Route::get('products/{price}/priceChart', [ProductController::class, 'priceChart'])->name('products.priceChart');
    Route::get('products/reviews/getComments', [ProductController::class, 'getCommentsAjax'])->name('products.getComments');
    Route::get('products/questions/getQuestions', [ProductController::class, 'getQuestionAjax'])->name('products.getQuestions');
    Route::post('product/get-stores', [ProductController::class, 'get_stores'])->name('products.get_stores');
    Route::get('p/{id}', [ProductController::class, 'shortLink'])->name('products.shortLink');
    Route::get('product/{product}', [ProductController::class, 'show'])->name('products.show');

    // FIX مقیاس: rate-limit فقط در محیط production اعمال می‌شود (در تست/سندباکس محدود نیست)
    Route::get('search', [ProductController::class, 'index'])
        ->middleware(array_filter([production_throttle('front-search')]))
        ->name('products.index');
    Route::post('search', [ProductController::class, 'ajax_search'])
        ->middleware(array_filter([production_throttle('front-ajax-search')]))
        ->name('products.ajax_search');

    // ------------------ cart
    Route::get('cart', [CartController::class, 'show'])->name('cart');
    Route::post('cart/{product}', [CartController::class, 'store'])->name('cart.store');
    Route::put('cart', [CartController::class, 'update']);
    Route::delete('cart/{id}', [CartController::class, 'destroy'])->name('cart.destroy');

    Route::post('stock-notify', [StockNotifyController::class, 'store']);

    // ------------------ فاز ۷۸: هشدار کاهش قیمت
    Route::post('price-alert', [PriceAlertController::class, 'store'])->name('price-alert.store');
    Route::delete('price-alert', [PriceAlertController::class, 'destroy'])->name('price-alert.destroy');

    // ------------------forms
    Route::get('form/{form}', [FormController::class, 'show'])->name('form.show');
    Route::post('form/submit/{form}', [FormController::class, 'submit'])->name('form.submit');

    // ------------------ pages
    Route::get('pages/{page}', [PageController::class, 'show'])->name('pages.show');

    // ------------------ brands
    Route::get('brands/{brand}', [BrandController::class, 'show'])->name('brands.show');

    // ------------------ Sitemap Routes ------------------
    Route::prefix('sitemap')->name('sitemap.')->group(function () {

        // فایل اصلی sitemap.xml
        Route::get('/xml', [SitemapController::class, 'index'])->name('index');
        Route::get('/', [SitemapController::class, 'index']); // alias

        // Sitemap محصولات (با صفحه‌بندی)
        Route::get('products-{page}.xml', [SitemapController::class, 'products'])->name('products');
        Route::get('products.xml', [SitemapController::class, 'products'])->name('products.first');

        // Sitemap مقالات (با صفحه‌بندی)
        Route::get('articles-{page}.xml', [SitemapController::class, 'articles'])->name('articles');
        Route::get('articles.xml', [SitemapController::class, 'articles'])->name('articles.first');

        // Sitemap صفحات استاتیک
        Route::get('statics.xml', [SitemapController::class, 'statics'])->name('statics');

        // Sitemap دسته‌بندی‌ها
        Route::get('product_categories.xml', [SitemapController::class, 'productCategories'])->name('product_categories');
        Route::get('article_categories.xml', [SitemapController::class, 'articleCategories'])->name('article_categories');

        // Sitemap برچسب‌ها (تگ‌ها)
        Route::get('product_tags.xml', [SitemapController::class, 'productTags'])->name('product_tags');
        Route::get('article_tags.xml', [SitemapController::class, 'articleTags'])->name('article_tags');

        // Sitemap برندها
        Route::get('brands.xml', [SitemapController::class, 'brands'])->name('brands');

        // Sitemap صفحات
        Route::get('pages.xml', [SitemapController::class, 'pages'])->name('pages');

        // Sitemap فرم‌ها
        Route::get('forms.xml', [SitemapController::class, 'forms'])->name('forms');

        // Sitemap فروشگاه‌ها
        Route::get('stores.xml', [SitemapController::class, 'stores'])->name('stores');

        // تولید دستی همه sitemap ها
        Route::get('generate', [SitemapController::class, 'generateAll'])->name('generate');
    });

    // مسیر مستقیم sitemap.xml (برای گوگل)
    Route::get('sitemap.xml', [SitemapController::class, 'index'])->name('sitemap.xml');

    // ------------------ contacts
    Route::resource('contact', ContactController::class)->only(['index', 'store']);

    // ------------------ orders
    Route::any('orders/payment/callback/{gateway}', [OrderController::class, 'verify'])->name('orders.verify');

    // ------------------ wallet
    Route::any('wallet/payment/callback/{gateway}', [WalletController::class, 'verify'])->name('wallet.verify');

    // ------------------ authentication required
    Route::group(['middleware' => ['auth', 'verified', 'CheckPasswordChange']], function () {

        // ------------------ MainController

        Route::get('checkout', [MainController::class, 'checkout'])->name('checkout');
        Route::get('checkout-prices', [MainController::class, 'getPrices'])->name('checkout.prices');
        Route::get('/checkout/delivery-dates', [MainController::class, 'getDeliveryDatesAjax'])->name('checkout.delivery-dates');
        Route::get('orderResultInfo/{order}', [MainController::class, 'orderResultInfo'])->name('orderResultInfo');
        // ------------------ discount
        Route::post('discount', [DiscountController::class, 'store'])->name('discount.store');
        Route::delete('discount', [DiscountController::class, 'destroy'])->name('discount.destroy');

        // ------------------ orders
        Route::resource('profile/orders', OrderController::class);
        Route::get('profile/orders/pay/{order}', [OrderController::class, 'pay'])->name('orders.pay');
        Route::get('profile/orders/{order}/print', [OrderController::class, 'print'])->name('orders.print');
        // ------------------ wallet
        Route::post('profile/wallet/withdraw', [WalletController::class, 'withdraw'])->name('wallet.withdraw');
        Route::resource('profile/wallet', WalletController::class)->only(['index', 'store', 'show']);

        Route::get('profile/notifications', [UserController::class, 'notifications'])->name('notifications.index');
        Route::post('profile/notifications/mark-as-read', [UserController::class, 'markAsRead'])->name('notifications.mark-as-read');
        Route::get('profile/messages', [UserController::class, 'messages'])->name('messages.index');
        Route::get('profile/messages/show/{message}', [UserController::class, 'messages_show'])->name('messages.show');

        // ─── فاز ۹۲: امکان «معرفی افراد» به پکیج باشگاه مشتریان منتقل شد ───
        // لینک‌های قدیمی /profile/referrals به صفحهٔ معرفی دوستان پکیج هدایت می‌شوند
        // (اگر پکیج/امکان خاموش باشد، خود صفحهٔ پکیج با پیام مناسب ریدایرکت می‌کند).
        // Route::get('profile/referrals', [UserController::class, 'referrals'])->name('user.referrals.index');
        Route::get('profile/referrals', fn () => redirect()->to('/profile/club/referrals'))->name('user.referrals.index');

        // ------------------ user
        Route::get('profile', [UserController::class, 'profile'])->name('user.profile');
        Route::get('profile/edit-profile', [UserController::class, 'editProfile'])->name('user.profile.edit');
        Route::put('profile', [UserController::class, 'update_profile'])->name('user.profile.update');
        Route::get('profile/change-password', [UserController::class, 'changePassword'])->name('user.password');
        Route::put('profile/change-password', [UserController::class, 'updatePassword'])->name('user.password.update');

        Route::group(['middleware' => ['EnsureForceChange']], function () {
            Route::get('profile/force-change-password', [UserController::class, 'forceChangePassword'])->name('user.force-change-password');
            Route::post('profile/force-change-password', [UserController::class, 'forceUpdatePassword'])->name('user.force-update-password');
        });

        // ------------------ products
        Route::get('products/{price}/download', [ProductController::class, 'download'])->name('products.download');
        Route::post('products/{product}/comments', [ProductController::class, 'comments'])->name('product.comments');

        // ------------------ blogs
        Route::post('blogs/{post}/comments', [BlogController::class, 'comments'])->name('post.comments');

        // ------------------ favorites
        Route::resource('profile/favorites', FavoriteController::class)->only(['index', 'store', 'destroy']);

        // ------------------ addresses
        Route::resource('profile/addresses', AddressesController::class)->only(['index', 'store', 'destroy', 'show', 'update']);
        Route::get('profile/addresses/active/{address}', [AddressesController::class, 'active_address'])->name('addresses.active');

        // ------------------ user-history
        Route::get('profile/user-history', [UserHistoryController::class, 'index'])->name('user.user-history');
        Route::delete('profile/user-history', [UserHistoryController::class, 'destroy'])->name('user.user-history.delete');

        // ------------------ comments
        Route::resource('profile/comments', CommentController::class)->only(['index', 'store', 'destroy']);
        // ------------------ tickets
        Route::resource('profile/tickets', TicketController::class)->except(['destroy']);

        Route::prefix('profile/returns')->name('returns.')->middleware(['auth', 'verified'])->group(function () {
            Route::get('/', [ReturnController::class, 'index'])->name('index');
            Route::get('/create/{order}/{orderItem}', [ReturnController::class, 'create'])->name('create');
            Route::post('/create/{order}/{orderItem}', [ReturnController::class, 'store'])->name('store');
            Route::get('/{returnRequest}', [ReturnController::class, 'show'])->name('show');
            Route::post('/{returnRequest}/cancel', [ReturnController::class, 'cancel'])->name('cancel');
            // مشتری ثبت می‌کند که محصول را ارسال کرده
            Route::post('/{returnRequest}/mark-shipped', [ReturnController::class, 'markShipped'])->name('mark_shipped');
        });

        // ------------------ reviews

        Route::resource('reviews', ReviewController::class)->only(['store', 'index']);
        Route::get('reviews/{product}', [ReviewController::class, 'show'])->name('reviews.show');
        Route::post('reviews/{review}/like', [ReviewController::class, 'like'])->name('reviews.like');
        Route::post('reviews/{review}/dislike', [ReviewController::class, 'dislike'])->name('reviews.dislike');

        // ------------------ question-answer
        Route::post('comments/{comment}/like', [ProductController::class, 'like'])->name('comments.like');
        Route::post('comments/{comment}/dislike', [ProductController::class, 'dislike'])->name('comments.dislike');

        // unsubscribe newsletter
        Route::get('newsletter/unsubscribe/{id}', [NewsletterController::class, 'unsubscribe'])->name('newsletter.unsubscribe');

    });

    // subscribe newsletter
    Route::post('newsletter/subscribe', [NewsletterController::class, 'subscribe'])->name('newsletter.subscribe');

    // ------------------ verify user routes
    Route::group(['middleware' => ['auth', 'notVerified', 'CheckPasswordChange']], function () {
        Route::get('verify', [VerifyController::class, 'showVerify'])->name('verify.showVerify');
        Route::post('verify', [VerifyController::class, 'verifyCode'])->name('verify.verifyCode')->middleware('throttle:15,1');
        Route::get('change-username', [VerifyController::class, 'showChangeUsername'])->name('verify.showChangeUsername');
        Route::post('change-username', [VerifyController::class, 'changeUsername'])->name('verify.changeUsername');
    });

    Route::post('/login', [UserController::class, 'Check_Mobile_Email'])->name('user.CheckMobileEmail');
    Route::get('/login/password', [UserController::class, 'ShowPasswordForm'])->name('user.CheckMobileEmailPassword');
    Route::post('/login/password/checkCodeRegister', [UserController::class, 'Register_Mobile'])->name('user.Register_Mobile');
    Route::post('/login/CheckPassword', [UserController::class, 'CheckPassword'])->name('user.CheckPassword');
    Route::get('/welcome', [UserController::class, 'welcome'])->name('user.welcome');

});

Route::group(['as' => 'seller.', 'prefix' => 'seller'], function () {

    // ========== صفحات عمومی فروشنده (بدون لاگین) ==========
    Route::get('/', [SellerController::class, 'index'])->name('index');

    // لاگین
    Route::get('login', [SellerController::class, 'login'])->name('login');
    Route::post('login', [SellerController::class, 'login_check'])->name('login_check');

    // ثبت‌نام
    Route::get('registration', [SellerController::class, 'registration'])->name('registration');
    Route::post('registration', [SellerController::class, 'registration_new_seller'])->name('registration_new_seller');
    Route::get('registration/mobile', [SellerController::class, 'registration_mobile'])->name('registration_mobile');
    Route::post('registration/mobile', [SellerController::class, 'registration_mobile_check'])->name('registration_mobile_check');

    // مرحله 1: اطلاعات کسب و کار
    Route::get('registration/business-details', [SellerController::class, 'registration_business_details'])->name('registration_business_details');
    Route::post('registration/business-details', [SellerController::class, 'registration_business_details_store'])->name('registration_business_details_store');

    // مرحله 2: مدارک
    Route::get('registration/documents', [SellerController::class, 'registration_documents'])->name('registration_documents');
    Route::post('registration/documents', [SellerController::class, 'registration_documents_store'])->name('registration_documents_store');
    Route::delete('registration/documents', [SellerController::class, 'registration_documents_delete'])->name('registration_documents_delete');
    Route::post('registration/documents-check', [SellerController::class, 'registration_documents_check'])->name('registration_documents_check');

    // مرحله 3: نهایی
    Route::get('registration/checkout', [SellerController::class, 'registration_checkout'])->name('registration_checkout');
    Route::post('registration/checkout', [SellerController::class, 'registration_checkout_store'])->name('registration_checkout_store');

    // دریافت کد جدید
    Route::post('/get-new-code-for-seller', [SellerController::class, 'get_new_code'])->name('get-new-code-for-seller');

    // ========== پنل فروشنده (نیاز به لاگین) ==========
    Route::group(['middleware' => ['Seller']], function () {

        // خروج
        Route::get('logout', [SellerController::class, 'logout'])->name('logout');

        // داشبورد
        Route::get('dashboard', [DashboardSellerController::class, 'index'])->name('dashboard');

        // ========== مدیریت محصولات ==========
        Route::prefix('products')->name('products.')->group(function () {
            Route::get('/', [ProductSellerController::class, 'index'])->name('index');
            Route::post('api/index', [ProductSellerController::class, 'apiIndex'])->name('apiIndex');
            Route::get('create', [ProductSellerController::class, 'create'])->name('create');
            Route::post('store', [ProductSellerController::class, 'store'])->name('store');
            Route::get('{product}/edit', [ProductSellerController::class, 'edit'])->name('edit');
            Route::put('{product}/edit', [ProductSellerController::class, 'update'])->name('update');
            Route::post('image-store', [ProductSellerController::class, 'image_store'])->name('image-store');
            Route::post('image-delete', [ProductSellerController::class, 'image_delete'])->name('image-delete');
            Route::get('find', [ProductSellerController::class, 'find'])->name('find');
            Route::post('find/api/index', [ProductSellerController::class, 'apiIndexFind'])->name('apiIndexFind');
            Route::get('{product_id}/variant', [ProductSellerController::class, 'variant'])->name('variant');
            Route::post('{product}/variant', [ProductSellerController::class, 'variant_store'])->name('variant.store');
        });

        // ========== کیف پول و کمیسیون ==========
        Route::get('commission', [WalletSellerController::class, 'commission'])->name('commission');
        // فاز ۱۰۶ الف — نام روت قبلاً 'seller.commission.guide' بود که با پیشوند گروه بیرونی
        // ('seller.') دو-پیشوندی می‌شد (seller.seller.….) و route('seller.commission.guide') می‌شکست.
        Route::get('/commission-guide', [WalletSellerController::class, 'showCommissionRates'])->name('commission.guide');

        Route::prefix('wallet')->name('wallet.')->group(function () {
            Route::get('/', [WalletSellerController::class, 'index'])->name('index');
            // فاز ۱۰۶-۱ — صورت‌حساب تسویه (گیت از تنظیمات چندفروشندگی) —
            // قبل از وایلدکارد {wallet} تا مسیرش بلعیده نشود (درس فاز ۹۱: روت ثابت قبل از وایلدکارد)
            Route::get('settlements', [WalletSellerController::class, 'settlements'])->name('settlements');
            Route::get('settlements/export', [WalletSellerController::class, 'settlementsExport'])->name('settlements.export');
            Route::get('{wallet}', [WalletSellerController::class, 'show'])->name('show');
            Route::post('store', [WalletSellerController::class, 'store'])->name('store');
            Route::post('withdraw', [WalletSellerController::class, 'withdraw'])->name('withdraw');
            Route::any('payment/callback/{gateway}', [WalletSellerController::class, 'verify'])->name('verify');
        });

        // ========== پروفایل فروشنده ==========
        Route::prefix('profile')->name('profile.')->group(function () {
            Route::get('/', [ProfileSellerController::class, 'index'])->name('index');
            Route::post('/', [ProfileSellerController::class, 'store'])->name('store');
            Route::put('{seller}/update', [ProfileSellerController::class, 'update'])->name('update');
            Route::post('set-econtract', [ProfileSellerController::class, 'set_econtract'])->name('set_econtract');
        });

        // ========== اعلان‌ها ==========
        Route::prefix('notifications')->name('notifications.')->group(function () {
            Route::get('/', [NotificationSellerController::class, 'index'])->name('index');
            Route::post('{notification}/read', [NotificationSellerController::class, 'read'])->name('read');
        });

        // ========== پرسش و پاسخ ==========
        Route::resource('questions', QuestionSellerController::class)->names([
            'index' => 'questions.index',
            'create' => 'questions.create',
            'store' => 'questions.store',
            'show' => 'questions.show',
            'edit' => 'questions.edit',
            'update' => 'questions.update',
            'destroy' => 'questions.destroy',
        ]);

        // ========== مدیریت سفارشات ==========
        Route::prefix('orders')->name('orders.')->group(function () {
            Route::get('/', [OrderSellerController::class, 'index'])->name('index');
            Route::post('api/index', [OrderSellerController::class, 'apiIndex'])->name('apiIndex');
            Route::post('api/shippings-status', [OrderSellerController::class, 'shippingsStatus'])->name('shippings-status');
            Route::post('{order}/shipping-status', [OrderSellerController::class, 'shipping_status'])->name('shipping-status');
            Route::get('{order}/print', [OrderSellerController::class, 'print'])->name('print');
            Route::delete('api/multipleDestroy', [OrderSellerController::class, 'multipleDestroy'])->name('multipleDestroy');
            Route::get('not-completed', [OrderSellerController::class, 'notCompleted'])->name('notCompleted');
            Route::get('api/printAllShippingForms', [OrderSellerController::class, 'printAllShippingForms'])->name('printAllShippingForms');
            Route::get('api/printAll', [OrderSellerController::class, 'printAll'])->name('printAll');
            Route::get('api/printAllShippingForms', [OrderSellerController::class, 'printAllShippingForms'])->name('printAllShippingForms');
            Route::get('api/printAllShippingFormsMin', [OrderSellerController::class, 'printAllShippingFormsMin'])->name('printAllShippingFormsMin');

            Route::get('export/create', [OrderSellerController::class, 'export'])->name('export');

            // Resource routes
            Route::get('{order}', [OrderSellerController::class, 'show'])->name('show');
            Route::put('{order}', [OrderSellerController::class, 'update'])->name('update');
            Route::delete('{order}', [OrderSellerController::class, 'destroy'])->name('destroy');
        });

        // ========== روش‌های ارسال ==========
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

        // ========== تیکت‌ها ==========
        Route::resource('tickets', SellerTicketController::class)->except(['destroy'])->names([
            'index' => 'tickets.index',
            'create' => 'tickets.create',
            'store' => 'tickets.store',
            'show' => 'tickets.show',
            'edit' => 'tickets.edit',
            'update' => 'tickets.update',
        ]);

        Route::prefix('holidays')->name('holidays.')->group(function () {
            Route::get('/', [HolidayController::class, 'index'])->name('index');
            Route::post('/check', [HolidayController::class, 'check'])->name('check');
            Route::post('/convert-to-jalali', [HolidayController::class, 'convertToJalali'])->name('convert-to-jalali');
            Route::post('/get-start-dates', [HolidayController::class, 'getStartDates'])->name('get-start-dates');
            Route::post('/update/{year}', [HolidayController::class, 'updateHolidays'])->name('update');
            Route::get('/get/{year}', [HolidayController::class, 'getHolidaysByYear'])->name('get');
        });

        // آمار فروشنده
        Route::prefix('statistics')->name('statistics.')->group(function () {
            Route::get('/', [SellerStatisticsController::class, 'index'])->name('index');
            Route::get('/view-counts', [SellerStatisticsController::class, 'viewCounts'])->name('viewCounts');
            Route::get('/viewer-counts', [SellerStatisticsController::class, 'viewerCounts'])->name('viewerCounts');
            // Route::get('/latest-visitors', [SellerStatisticsController::class, 'latestVisitors'])->name('latestVisitors');
        });
    });
});

// get auth user in 404 page
Route::fallback(function () {
    return response()->view('errors.404', [], 404);
});
