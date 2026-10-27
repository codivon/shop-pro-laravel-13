<?php

/*
|--------------------------------------------------------------------------
| New Admin Panel (Livewire 4) Routes
|--------------------------------------------------------------------------
| پنل مدیریت جدید (فاز ۰)
| آدرس: /admin/{ADMIN_ROUTE_PREFIX}  (همان آدرس پنل قدیمی با ظاهر جدید)
| پنل قدیمی به /admin-old/{ADMIN_ROUTE_PREFIX} منتقل شده است.
| ثبت مسیرها خارج از حلقه‌ی locale انجام می‌شود تا فقط یک‌بار register شوند.
*/

use App\Http\Controllers\AdminPanel\LogoutController;
use App\Http\Controllers\AdminPanel\MediaController;
use App\Http\Controllers\Back\FormController as LegacyFormController;
use App\Http\Controllers\Back\OrderController as LegacyOrderController;
use App\Http\Controllers\Back\RequestDepositAdmin as LegacyRequestDepositController;
use App\Http\Controllers\Back\SellerControllers as LegacySellerController;
use App\Http\Controllers\Back\TagController as LegacyTagController;
use App\Http\Controllers\Back\UserController as LegacyUserController;
use App\Http\Middleware\AdminPanel;
use App\Http\Middleware\GuestAdminPanel;
use App\Livewire\Admin\Admins\AdminIndex;
use App\Livewire\Admin\Admins\PermissionIndex;
use App\Livewire\Admin\Admins\RoleIndex;
use App\Livewire\Admin\Auth\Login;
use App\Livewire\Admin\Auth\TwoFactorChallenge;
use App\Livewire\Admin\Banners\BannerForm;
use App\Livewire\Admin\Banners\BannerIndex;
use App\Livewire\Admin\Comments\CommentsIndex;
use App\Livewire\Admin\Content\SearchIndex;
use App\Livewire\Admin\Dashboard;
use App\Livewire\Admin\Discounts\DiscountForm;
use App\Livewire\Admin\Discounts\DiscountIndex;
use App\Livewire\Admin\Faqs\FaqIndex;
use App\Livewire\Admin\Finance\CurrencyIndex;
use App\Livewire\Admin\Finance\RequestDepositHistory;
use App\Livewire\Admin\Finance\RequestDepositIndex;
use App\Livewire\Admin\Finance\TransactionIndex;
use App\Livewire\Admin\Finance\TransactionSellerDeposits;
use App\Livewire\Admin\Finance\VendorCommissionIndex;
use App\Livewire\Admin\Finance\WalletHistoryIndex;
use App\Livewire\Admin\Finance\WalletHistoryShow;
use App\Livewire\Admin\Finance\WalletShow;
use App\Livewire\Admin\Finance\WalletTransactionCreate;
use App\Livewire\Admin\Forms\FormBuilder;
use App\Livewire\Admin\Forms\FormIndex; // فاز ۱۰۳
use App\Livewire\Admin\Forms\FormSubmissions;
use App\Livewire\Admin\Geo\CityIndex;
use App\Livewire\Admin\Geo\ProvinceIndex;
use App\Livewire\Admin\Imports\PostsImport;
use App\Livewire\Admin\Imports\ProductsImport;
use App\Livewire\Admin\Imports\UsersImport;
use App\Livewire\Admin\Links\LinkGroups;
use App\Livewire\Admin\Links\LinkIndex;
use App\Livewire\Admin\Menus\MenuManager;
use App\Livewire\Admin\Messages\MessageBirthday;
use App\Livewire\Admin\Messages\MessageIndex;
use App\Livewire\Admin\Messages\MessageShow;
use App\Livewire\Admin\Notifications\NotificationIndex;
use App\Livewire\Admin\Notifications\PanelNotifications;
use App\Livewire\Admin\Orders\OrderCreate;
use App\Livewire\Admin\Orders\OrderIndex;
use App\Livewire\Admin\Orders\OrderItemShow;
use App\Livewire\Admin\Orders\OrderNotCompleted;
use App\Livewire\Admin\Orders\OrderShow;
use App\Livewire\Admin\Packages\PackageIndex;
use App\Livewire\Admin\Packages\PackageInstalled;
use App\Livewire\Admin\Packages\PackagePaymentCallback;
use App\Livewire\Admin\Pages\PageForm;
use App\Livewire\Admin\Pages\PageIndex;
use App\Livewire\Admin\Posts\PostForm;
use App\Livewire\Admin\Posts\PostIndex;
use App\Livewire\Admin\PriceAlert\PriceAlertIndex;
use App\Livewire\Admin\Products\PriceGroupIndex;
use App\Livewire\Admin\Products\PriceIndex;
use App\Livewire\Admin\Products\ProductForm;
use App\Livewire\Admin\Products\ProductIndex;
use App\Livewire\Admin\Profile;
use App\Livewire\Admin\Returns\ReturnIndex;
use App\Livewire\Admin\Returns\ReturnReasons;
use App\Livewire\Admin\Returns\ReturnShow;
use App\Livewire\Admin\Reviews\ReviewIndex;
use App\Livewire\Admin\Sellers\SellerIndex;
use App\Livewire\Admin\Sellers\SellerLeaderboard;
use App\Livewire\Admin\Sellers\SellerNotifications;
use App\Livewire\Admin\Sellers\SellerOrders;
use App\Livewire\Admin\Sellers\SellerProducts;
use App\Livewire\Admin\Sellers\SellerShow;
use App\Livewire\Admin\Sellers\SellerVariants;
use App\Livewire\Admin\Sellers\SellerViews;
use App\Livewire\Admin\Sellers\Settings\SellerCommission;
use App\Livewire\Admin\Sellers\Settings\SellerEcontract;
use App\Livewire\Admin\Sellers\Settings\SellerHero;
use App\Livewire\Admin\Sellers\Settings\SellerQuestion;
use App\Livewire\Admin\Settings\Apikeys;
use App\Livewire\Admin\Settings\FloatingWidget;
use App\Livewire\Admin\Settings\Gateways;
use App\Livewire\Admin\Settings\Holidays;
use App\Livewire\Admin\Settings\Information;
use App\Livewire\Admin\Settings\Others;
use App\Livewire\Admin\Settings\Redirects;
use App\Livewire\Admin\Settings\Robots;
use App\Livewire\Admin\Settings\SmsSettings;
use App\Livewire\Admin\Settings\Socials;
use App\Livewire\Admin\Shipping\CarrierIndex;
use App\Livewire\Admin\Shipping\TariffIndex;
use App\Livewire\Admin\Sliders\SliderForm;
use App\Livewire\Admin\Sliders\SliderIndex;
use App\Livewire\Admin\Statistics\OrdersPage;
use App\Livewire\Admin\Statistics\ProductsStats;
use App\Livewire\Admin\Statistics\SmsLog;
use App\Livewire\Admin\Statistics\SmsShow;
use App\Livewire\Admin\Statistics\UsersStats;
use App\Livewire\Admin\Statistics\ViewersPage;
use App\Livewire\Admin\Statistics\ViewsList;
use App\Livewire\Admin\Statistics\ViewsPage;
use App\Livewire\Admin\StockNotify\StockNotifyIndex;
use App\Livewire\Admin\Support\ContactIndex;
use App\Livewire\Admin\Support\NewsletterIndex;
use App\Livewire\Admin\System\ActivityLogIndex;
use App\Livewire\Admin\System\BackupIndex;
use App\Livewire\Admin\System\BaleSettings;
use App\Livewire\Admin\System\Developer;
use App\Livewire\Admin\System\FileManager;
use App\Livewire\Admin\System\LogsIndex;
use App\Livewire\Admin\System\PulseIndex;
use App\Livewire\Admin\System\SessionIndex;
use App\Livewire\Admin\Taxonomy\AttributeForm;
use App\Livewire\Admin\Taxonomy\AttributeGroupAttributes;
use App\Livewire\Admin\Taxonomy\AttributeGroupIndex;
use App\Livewire\Admin\Taxonomy\BrandForm;
use App\Livewire\Admin\Taxonomy\BrandIndex;
use App\Livewire\Admin\Taxonomy\CategoryForm;
use App\Livewire\Admin\Taxonomy\CategoryTree;
use App\Livewire\Admin\Taxonomy\FildForm;
use App\Livewire\Admin\Taxonomy\FildIndex;
use App\Livewire\Admin\Taxonomy\FilterForm;
use App\Livewire\Admin\Taxonomy\FilterIndex;
use App\Livewire\Admin\Taxonomy\SizeTypeForm;
use App\Livewire\Admin\Taxonomy\SizeTypeIndex;
use App\Livewire\Admin\Taxonomy\SizeTypeValues;
use App\Livewire\Admin\Taxonomy\SpecTypeForm;
use App\Livewire\Admin\Taxonomy\SpecTypeIndex;
use App\Livewire\Admin\Taxonomy\TagIndex;
use App\Livewire\Admin\Themes\ThemeIndex;
use App\Livewire\Admin\Themes\ThemeSettings;
use App\Livewire\Admin\Tickets\TicketCreate;
use App\Livewire\Admin\Tickets\TicketIndex;
use App\Livewire\Admin\Tickets\TicketShow;
use App\Livewire\Admin\Users\UserForm;
use App\Livewire\Admin\Users\UserIndex;
use App\Livewire\Admin\Users\UserNotifications;
use App\Livewire\Admin\Users\UserShow;
use App\Livewire\Admin\Users\UserViews;
use App\Livewire\Admin\Warehouses\WarehouseBulkStock;
use App\Livewire\Admin\Warehouses\WarehouseIndex;
use App\Livewire\Admin\Warehouses\WarehouseMovements;
use App\Livewire\Admin\Warehouses\WarehouseShow;
use App\Livewire\Admin\Warehouses\WarehouseStockTake;
use App\Livewire\Admin\Warehouses\WarehouseVariations;
use App\Livewire\Admin\Widgets\PostWidgetBuilder;
use App\Livewire\Admin\Widgets\PostWidgetForm;
use App\Livewire\Admin\Widgets\PostWidgetIndex;
use App\Livewire\Admin\Widgets\WidgetBuilder;
use App\Livewire\Admin\Widgets\WidgetForm;
use App\Livewire\Admin\Widgets\WidgetIndex;
use Illuminate\Support\Facades\Route;

Route::prefix('admin/'.admin_route_prefix())->name('np.')->group(function () {

    /*
    |----------------------
    | احراز هویت
    |----------------------
    */
    Route::middleware(GuestAdminPanel::class)->group(function () {
        Route::get('login', Login::class)->name('login');

        // فاز ۴۴ — چالش ورود دومرحله‌ای مدیر (بین تأیید اعتبارنامه و ورود کامل)
        Route::get('two-factor', TwoFactorChallenge::class)->name('two-factor.challenge');
    });

    Route::get('logout', LogoutController::class)->name('logout');

    /*
    |----------------------
    | بخش‌های احراز هویت‌شده پنل
    |----------------------
    */
    Route::middleware(AdminPanel::class)->group(function () {

        // نمای کلی
        Route::get('/', Dashboard::class)->name('dashboard');

        /*
        |----------------------
        | پروفایل مدیر + پاک‌سازی کش (فاز ۲۱ — قبلاً لینک‌ها به پنل قدیمی می‌رفتند)
        |----------------------
        */
        Route::get('profile', Profile::class)->name('profile');
        Route::get('cache-clear', function () {
            if (auth('adminPanel')->id() !== 1) {
                abort(403);
            }

            Artisan::call('config:clear');
            Artisan::call('cache:clear');
            Artisan::call('route:clear');
            Artisan::call('view:clear');

            return redirect()->route('np.dashboard')->with('np-toast', [
                'type' => 'success',
                'message' => 'کش سیستم با موفقیت پاک شد.',
            ]);
        })->name('cache-clear');

        /*
        |----------------------
        | محصولات (فاز ۱)
        |----------------------
        */
        Route::get('products', ProductIndex::class)->name('products.index');
        Route::get('products/create', ProductForm::class)->name('products.create');
        Route::get('products/{product}/edit', ProductForm::class)->name('products.edit');

        /*
        |----------------------
        | سفارشات (فاز ۲)
        |----------------------
        | صفحات چاپ/برچسب از کنترلر قدیمی استفاده می‌کنند (نماهای چاپ مستقل از پنل‌اند)
        */
        Route::get('orders', OrderIndex::class)->name('orders.index');
        Route::get('orders/create', OrderCreate::class)->name('orders.create');
        Route::get('orders/not-completed', OrderNotCompleted::class)->name('orders.not-completed');
        Route::get('orders/export/create', [LegacyOrderController::class, 'export'])->name('orders.export');
        Route::get('orders/{order}', OrderShow::class)->name('orders.show');
        Route::get('order/{orderItem}', OrderItemShow::class)->name('orders.show-item');

        Route::get('orders/{order}/print', [LegacyOrderController::class, 'print'])->name('orders.print');
        Route::get('orders/{order}/shipping-form', [LegacyOrderController::class, 'shippingForm'])->name('orders.shipping-form');
        Route::get('orders/{order}/shipping-form-min', [LegacyOrderController::class, 'shippingFormMin'])->name('orders.shipping-form-min');
        Route::get('orders/api/printAll', [LegacyOrderController::class, 'printAll'])->name('orders.printAll');
        Route::get('orders/api/printAllShippingForms', [LegacyOrderController::class, 'printAllShippingForms'])->name('orders.printAllShippingForms');
        Route::get('orders/api/printAllShippingFormsMin', [LegacyOrderController::class, 'printAllShippingFormsMin'])->name('orders.printAllShippingFormsMin');

        /*
        |----------------------
        | فروشندگان (فاز ۳)
        |----------------------
        | {seller} با slug بایند می‌شود (Seller::getRouteKeyName).
        | ترتیب مهم است: مسیرهای ثابت قبل از {seller} تعریف شوند.
        */
        Route::get('sellers/orders/export/create', [LegacyOrderController::class, 'export'])->name('sellers.orders-export');
        Route::get('sellers', SellerIndex::class)->name('sellers.index');
        // فاز ۱۰۶-۷ — لیدربورد مقایسهٔ فروشندگان؛ روت ثابت قبل از {seller} (ترتیب مهم)
        Route::get('sellers/leaderboard', SellerLeaderboard::class)->name('sellers.leaderboard');
        Route::get('sellers/products', SellerProducts::class)->name('sellers.products');
        Route::get('sellers/orders', SellerOrders::class)->name('sellers.orders');
        Route::get('sellers/{seller}', SellerShow::class)->name('sellers.show');
        Route::get('sellers/{seller}/products', SellerProducts::class)->name('sellers.seller-products');
        Route::get('sellers/{seller}/variants', SellerVariants::class)->name('sellers.seller-variants');
        Route::get('sellers/{seller}/orders', SellerOrders::class)->name('sellers.seller-orders');
        Route::get('sellers/{seller}/orders/{order}/print', [LegacySellerController::class, 'printOrdersSeller'])->name('sellers.orders.print');
        Route::get('sellers/{seller}/orders/export/create', [LegacySellerController::class, 'sellerOrdersExport'])->name('sellers.orders.export');
        Route::get('sellers/{seller}/views', SellerViews::class)->name('sellers.views');
        Route::get('sellers/{seller}/notifications', SellerNotifications::class)->name('sellers.notifications');

        /*
        |----------------------
        | تنظیمات فروشندگان (فاز ۳)
        |----------------------
        */
        Route::get('settings/seller-hero', SellerHero::class)->name('settings.seller-hero');
        Route::get('settings/seller-commission', SellerCommission::class)->name('settings.seller-commission');
        Route::get('settings/seller-question', SellerQuestion::class)->name('settings.seller-question');
        Route::get('settings/seller-econtract', SellerEcontract::class)->name('settings.seller-econtract');

        /*
        |----------------------
        | کاربران (فاز ۴)
        |----------------------
        | ترتیب مهم است: مسیرهای ثابت قبل از {user} تعریف شوند.
        */
        Route::get('users', UserIndex::class)->name('users.index');
        Route::get('users/create', UserForm::class)->name('users.create');
        Route::get('users/export/create', [LegacyUserController::class, 'export'])->name('users.export');
        Route::get('users/{user}', UserShow::class)->name('users.show');
        Route::get('users/{user}/edit', UserForm::class)->name('users.edit');
        Route::get('users/{user}/views', UserViews::class)->name('users.views');
        Route::get('users/{user}/notifications', UserNotifications::class)->name('users.notifications');

        /*
        |----------------------
        | مالی / پرداخت (فاز ۴)
        |----------------------
        | ترتیب مهم است: مسیرهای ثابت قبل از {wallet} تعریف شوند.
        */
        Route::get('transactions', TransactionIndex::class)->name('transactions.index');
        Route::get('transactions/seller-deposit', TransactionSellerDeposits::class)->name('transactions.seller-deposits');

        Route::get('wallets/histories/{history}', WalletHistoryShow::class)->name('wallets.history');
        Route::get('wallets/{wallet}', WalletShow::class)->name('wallets.show');
        Route::get('wallets/{wallet}/create', WalletTransactionCreate::class)->name('wallets.create');

        Route::get('wallet-histories', WalletHistoryIndex::class)->name('wallet-histories.index');

        /*
        |----------------------
        | صف درخواست برداشت (فاز ۴)
        |----------------------
        */
        Route::get('request-deposit', RequestDepositIndex::class)->name('request-deposit.index');
        Route::get('request-deposit/histories/{history}', RequestDepositHistory::class)->name('request-deposit.history');
        Route::get('request-deposit/export/create', [LegacyRequestDepositController::class, 'export'])->name('request-deposit.export');

        /*
        |----------------------
        | پنل کمیسیون فروشندگان (فاز ۱۰۳)
        |----------------------
        */
        Route::get('vendors/commission', VendorCommissionIndex::class)->name('vendors.commission');

        /*
        |----------------------
        | مرجوعی (فاز ۴)
        |----------------------
        */
        Route::get('returns/reasons', ReturnReasons::class)->name('returns.reasons');
        Route::get('returns', ReturnIndex::class)->name('returns.index');
        Route::get('returns/{returnRequest}', ReturnShow::class)->name('returns.show');

        /*
        |----------------------
        | تخفیف‌ها (فاز ۴)
        |----------------------
        */
        Route::get('discounts', DiscountIndex::class)->name('discounts.index');
        Route::get('discounts/create', DiscountForm::class)->name('discounts.create');
        Route::get('discounts/{discount}/edit', DiscountForm::class)->name('discounts.edit');

        /*
        |----------------------
        | تکسونومی (فاز ۵)
        |----------------------
        | {category} با slug بایند می‌شود (Category::getRouteKeyName).
        | ترتیب مهم است: مسیرهای ثابت قبل از {category} تعریف شوند.
        */
        Route::get('categories/product', CategoryTree::class)->name('categories.product');
        Route::get('categories/post', CategoryTree::class)->defaults('type', 'postcat')->name('categories.post');
        Route::get('categories/{category}/edit', CategoryForm::class)->name('categories.edit');

        Route::get('brands', BrandIndex::class)->name('brands.index');
        Route::get('brands/{brand}/edit', BrandForm::class)->name('brands.edit');

        Route::get('attribute-groups', AttributeGroupIndex::class)->name('attribute-groups.index');
        Route::get('attribute-groups/{attributeGroup}/attributes', AttributeGroupAttributes::class)->name('attribute-groups.attributes');
        Route::get('attributes/create', AttributeForm::class)->name('attributes.create');
        Route::get('attributes/{attribute}/edit', AttributeForm::class)->name('attributes.edit');

        Route::get('spectypes', SpecTypeIndex::class)->name('spectypes.index');
        Route::get('spectypes/{spectype}/edit', SpecTypeForm::class)->name('spectypes.edit');

        Route::get('sizetypes', SizeTypeIndex::class)->name('sizetypes.index');
        Route::get('sizetypes/{sizetype}/values', SizeTypeValues::class)->name('sizetypes.values');
        Route::get('sizetypes/{sizetype}/edit', SizeTypeForm::class)->name('sizetypes.edit');

        Route::get('filds', FildIndex::class)->name('filds.index');
        Route::get('filds/{fild}/edit', FildForm::class)->name('filds.edit');

        Route::get('filters', FilterIndex::class)->name('filters.index');
        Route::get('filters/create', FilterForm::class)->name('filters.create');
        Route::get('filters/{filter}/edit', FilterForm::class)->name('filters.edit');

        Route::get('tags', TagIndex::class)->name('tags.index');
        Route::get('tags/export', [LegacyTagController::class, 'export'])->name('tags.export');

        /*
        |----------------------
        | وبلاگ (فاز ۶)
        |----------------------
        | {post} با slug بایند می‌شود (Post::getRouteKeyName).
        */
        Route::get('posts', PostIndex::class)->name('posts.index');
        Route::get('posts/create', PostForm::class)->name('posts.create');
        Route::get('posts/{post}/edit', PostForm::class)->name('posts.edit');

        /*
        |----------------------
        | صفحات (فاز ۶)
        |----------------------
        | {page} با slug بایند می‌شود (Page::getRouteKeyName).
        */
        Route::get('pages', PageIndex::class)->name('pages.index');
        Route::get('pages/create', PageForm::class)->name('pages.create');
        Route::get('pages/{page}/edit', PageForm::class)->name('pages.edit');

        /*
        |----------------------
        | منوها و لینک‌ها (فاز ۶)
        |----------------------
        */
        Route::get('menus', MenuManager::class)->name('menus.index');
        Route::get('links', LinkIndex::class)->name('links.index');
        Route::get('links/groups', LinkGroups::class)->name('links.groups');

        /*
        |----------------------
        | اسلایدرها و بنرها (فاز ۶)
        |----------------------
        | بایند با id (بدون getRouteKeyName اختصاصی).
        */
        Route::get('sliders', SliderIndex::class)->name('sliders.index');
        Route::get('sliders/create', SliderForm::class)->name('sliders.create');
        Route::get('sliders/{slider}/edit', SliderForm::class)->name('sliders.edit');

        Route::get('banners', BannerIndex::class)->name('banners.index');
        Route::get('banners/create', BannerForm::class)->name('banners.create');
        Route::get('banners/{banner}/edit', BannerForm::class)->name('banners.edit');

        /*
        |----------------------
        | سوالات متداول (فاز ۶)
        |----------------------
        */
        Route::get('faqs', FaqIndex::class)->name('faqs.index');

        /*
        |----------------------
        | دیدگاه‌ها / نظرات (فاز ۶)
        |----------------------
        | هر دو روت از یک کامپوننت با prop type استفاده می‌کنند.
        */
        Route::get('comments/index/products', CommentsIndex::class)->name('comments.products');
        Route::get('comments/index/posts', CommentsIndex::class)->defaults('type', 'post')->name('comments.posts');
        Route::get('reviews', ReviewIndex::class)->name('reviews.index');
        Route::get('stock-notifies', StockNotifyIndex::class)->name('stock-notifies.index');
        Route::get('price-alerts', PriceAlertIndex::class)->name('price-alerts.index');

        /*
        |----------------------
        | فرم‌ساز (فاز ۶)
        |----------------------
        | {form} و {widget} با id بایند می‌شوند.
        | پیش‌نمایش فرم از کنترلر قدیمی استفاده می‌کند (نمای مستقل).
        */
        Route::get('forms', FormIndex::class)->name('forms.index');
        Route::get('forms/create', FormBuilder::class)->name('forms.create');
        Route::get('forms/{form}/submissions', FormSubmissions::class)->name('forms.submissions');
        Route::get('forms/{form}/preview', [LegacyFormController::class, 'preview'])->name('forms.preview');
        Route::get('forms/{form}/edit', FormBuilder::class)->name('forms.edit');

        /*
        |----------------------
        | ابزارک‌ها (فاز ۶)
        |----------------------
        */
        Route::get('widgets', WidgetIndex::class)->name('widgets.index');
        Route::get('widgets/create', WidgetForm::class)->name('widgets.create');
        Route::get('widgets/builder', WidgetBuilder::class)->name('widgets.builder');
        Route::get('widgets/posts-builder', PostWidgetBuilder::class)->name('widgets.posts-builder'); // فاز ۱۱: ویجت‌ساز مقالات
        Route::post('media/upload', MediaController::class.'@store')->name('media.upload'); // آپلود تصویر CKEditor
        Route::get('widgets/{widget}/edit', WidgetForm::class)->name('widgets.edit');

        Route::get('posts-widgets', PostWidgetIndex::class)->name('posts-widgets.index');
        Route::get('posts-widgets/create', PostWidgetForm::class)->name('posts-widgets.create');
        Route::get('posts-widgets/{widget}/edit', PostWidgetForm::class)->name('posts-widgets.edit');

        /*
        |----------------------
        | ویجت شناور (فاز ۶)
        |----------------------
        */
        Route::get('settings/floating-widget', FloatingWidget::class)->name('settings.floating-widget');

        /*
        |----------------------
        | پشتیبانی (فاز ۷-الف)
        |----------------------
        */
        Route::get('tickets', TicketIndex::class)->name('tickets.index');
        Route::get('tickets/create', TicketCreate::class)->name('tickets.create');
        Route::get('tickets/{ticket}', TicketShow::class)->name('tickets.show');

        Route::get('contacts', ContactIndex::class)->name('contacts.index');

        /*
        |----------------------
        | پیام‌ها و خبرنامه و اعلان‌ها (فاز ۷-الف)
        |----------------------
        */
        Route::get('messages', MessageIndex::class)->name('messages.index');
        Route::get('messages/birthday', MessageBirthday::class)->name('messages.birthday');
        Route::get('messages/{message}', MessageShow::class)->name('messages.show');

        Route::get('newsletters', NewsletterIndex::class)->name('newsletters.index');

        Route::get('notifications', NotificationIndex::class)->name('notifications.index');

        /*
        |----------------------
        | ارز، تعطیلات، جغرافیا (فاز ۷-ب)
        |----------------------
        | {province} شهرها با query ?province={id} فیلتر می‌شوند (بدون پارامتر روت).
        */
        Route::get('currencies', CurrencyIndex::class)->name('currencies.index');
        Route::get('holidays', Holidays::class)->name('holidays.index');
        Route::get('provinces', ProvinceIndex::class)->name('provinces.index');
        Route::get('cities', CityIndex::class)->name('cities.index');

        /*
        |----------------------
        | حمل‌ونقل و جستجو (فاز ۷-ب)
        |----------------------
        | تعرفه‌ها با query ?carrier={id}.
        */
        Route::get('carriers', CarrierIndex::class)->name('carriers.index');
        Route::get('tariffs', TariffIndex::class)->name('tariffs.index');
        Route::get('searches', SearchIndex::class)->name('searches.index');

        /*
        |----------------------
        | robots / ریدایرکت / کلید API (فاز ۷-ب)
        |----------------------
        */
        Route::get('robots', Robots::class)->name('robots.index');
        Route::get('redirects', Redirects::class)->name('redirects.index');
        Route::get('apikeys', Apikeys::class)->name('apikeys.index');

        /*
        |----------------------
        | انبارها (فاز ۷-ب۳)
        |----------------------
        | ترتیب مهم است: مسیرهای ثابت قبل از {warehouse}.
        | variations با query ?product={id} فیلتر می‌شود.
        */
        Route::get('warehouses', WarehouseIndex::class)->name('warehouses.index');
        Route::get('warehouses/{warehouse}/variations', WarehouseVariations::class)->name('warehouses.variations');
        Route::get('warehouses/{warehouse}/movements', WarehouseMovements::class)->name('warehouses.movements');
        Route::get('warehouses/{warehouse}/stock-take', WarehouseStockTake::class)->name('warehouses.stock-take');
        Route::get('warehouses/{warehouse}/bulk-stock', WarehouseBulkStock::class)->name('warehouses.bulk-stock');
        Route::get('warehouses/{warehouse}', WarehouseShow::class)->name('warehouses.show');

        /*
        |----------------------
        | نشست‌ها و لاگ فعالیت (فاز ۷-ب۴)
        |----------------------
        */
        Route::get('sessions/blocked', fn () => redirect(route('np.sessions.index', ['tab' => 'blocked'])))->name('sessions.blocked');
        Route::get('sessions', SessionIndex::class)->name('sessions.index');
        Route::get('activity-log', ActivityLogIndex::class)->name('activity-log.index');

        /*
        |----------------------
        | ادمین‌ها / نقش‌ها / دسترسی‌ها (فاز ۷-ج)
        |----------------------
        */
        Route::get('admins', AdminIndex::class)->name('admins.index');
        Route::get('roles', RoleIndex::class)->name('roles.index');
        Route::get('permissions', PermissionIndex::class)->name('permissions.index');

        /*
        |----------------------
        | آمار + SMS + قیمت‌گذاری (فاز ۷-ج۲)
        |----------------------
        */
        Route::get('statistics/views', ViewsPage::class)->name('statistics.views');
        Route::get('statistics/viewers', ViewersPage::class)->name('statistics.viewers');
        Route::get('statistics/views-list', ViewsList::class)->name('statistics.views-list');
        Route::get('statistics/orders', OrdersPage::class)->name('statistics.orders');
        Route::get('statistics/orders-products', ProductsStats::class)->name('statistics.orders-products');
        Route::get('statistics/users', UsersStats::class)->name('statistics.users');
        Route::get('statistics/sms-log', SmsLog::class)->name('statistics.sms-log');
        Route::get('sms/{sms}', SmsShow::class)->name('sms.show');
        Route::get('products/prices', PriceIndex::class)->name('products.prices');
        Route::get('products/prices-group', PriceGroupIndex::class)->name('products.prices-group');

        /*
        |----------------------
        | تنظیمات + توسعه‌دهنده + بله (فاز ۷-ج۳)
        |----------------------
        */
        Route::get('settings/information', Information::class)->name('settings.information');
        Route::get('settings/socials', Socials::class)->name('settings.socials');
        Route::get('settings/gateways', Gateways::class)->name('settings.gateways');
        Route::get('settings/others', Others::class)->name('settings.others');
        Route::get('settings/sms', SmsSettings::class)->name('settings.sms');
        Route::get('developer', Developer::class)->name('developer.index');
        Route::get('bale-settings', BaleSettings::class)->name('bale-settings.index');

        /*
        |----------------------
        | پکیج‌ها / تم‌ها / ایمپورت / Pulse / بکاپ / فایل‌منیجر / اعلان‌های پنل / لاگ‌ها (فاز ۷-ج۴)
        |----------------------
        */
        Route::get('packages/payment/callback', PackagePaymentCallback::class)->name('packages.payment.callback');
        Route::get('packages/installed', PackageInstalled::class)->name('packages.installed');
        // فاز ۲۶ — تک‌صفحه packages/{slug} حذف شد؛ جزئیات پکیج در مودال لیست باز می‌شود (مثل پنل قدیمی)
        Route::get('packages', PackageIndex::class)->name('packages.index');
        Route::get('themes/settings', ThemeSettings::class)->name('themes.settings');
        Route::get('themes', ThemeIndex::class)->name('themes.index');
        Route::get('imports/posts', PostsImport::class)->name('imports.posts');
        Route::get('imports/products', ProductsImport::class)->name('imports.products');
        Route::get('imports/users', UsersImport::class)->name('imports.users');
        Route::get('pulse', PulseIndex::class)->name('pulse.index');
        Route::get('backups', BackupIndex::class)->name('backups.index');
        Route::get('file-manager', FileManager::class)->name('file-manager.index');
        Route::get('notifications/panel', PanelNotifications::class)->name('notifications.panel');
        Route::get('logs', LogsIndex::class)->name('logs.index');

        // فاز ۷ کامل شد — فاز ۸: QA نهایی، بهینه‌سازی و همگام‌سازی

    });
});
