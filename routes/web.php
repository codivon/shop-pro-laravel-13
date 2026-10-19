<?php

use App\Http\Controllers\Auth\AdminLoginController;
use App\Http\Controllers\Back\ActivityLogController;
use App\Http\Controllers\Back\AdminController;
use App\Http\Controllers\Back\AdminSessionController;
use App\Http\Controllers\Back\ApikeyController;
use App\Http\Controllers\Back\AttributeController;
use App\Http\Controllers\Back\AttributeGroupController;
use App\Http\Controllers\Back\BackupController;
use App\Http\Controllers\Back\BannerController;
use App\Http\Controllers\Back\BrandController;
use App\Http\Controllers\Back\CarrierController;
use App\Http\Controllers\Back\CategoryController;
use App\Http\Controllers\Back\CityController;
use App\Http\Controllers\Back\CommentController;
use App\Http\Controllers\Back\ContactController;
use App\Http\Controllers\Back\CurrencyController;
use App\Http\Controllers\Back\DeveloperController;
use App\Http\Controllers\Back\DiscountController;
use App\Http\Controllers\Back\FaqController;
use App\Http\Controllers\Back\FildController;
use App\Http\Controllers\Back\FilterController;
use App\Http\Controllers\Back\Floatingwidgetcontroller;
use App\Http\Controllers\Back\FormController;
use App\Http\Controllers\Back\HolidayController;
use App\Http\Controllers\Back\ImportsController;
use App\Http\Controllers\Back\InstallController;
use App\Http\Controllers\Back\LinkController;
use App\Http\Controllers\Back\MainController;
use App\Http\Controllers\Back\MenuController;
use App\Http\Controllers\Back\MessageController;
use App\Http\Controllers\Back\NewsletterController;
use App\Http\Controllers\Back\NotificationController;
use App\Http\Controllers\Back\OrderController;
use App\Http\Controllers\Back\PackageController;
use App\Http\Controllers\Back\PackagePaymentController;
use App\Http\Controllers\Back\PageController;
use App\Http\Controllers\Back\PermissionController;
use App\Http\Controllers\Back\PostController;
use App\Http\Controllers\Back\PostWidgetController;
use App\Http\Controllers\Back\ProductController;
use App\Http\Controllers\Back\ProvinceController;
use App\Http\Controllers\Back\PulseController;
use App\Http\Controllers\Back\RedirectInternalController;
use App\Http\Controllers\Back\RequestDepositAdmin;
use App\Http\Controllers\Back\ReviewController;
use App\Http\Controllers\Back\RobotsController;
use App\Http\Controllers\Back\RoleController;
use App\Http\Controllers\Back\SearchController;
use App\Http\Controllers\Back\SellerControllers;
use App\Http\Controllers\Back\SettingController;
use App\Http\Controllers\Back\SizeTypeController;
use App\Http\Controllers\Back\SliderController;
use App\Http\Controllers\Back\SmsController;
use App\Http\Controllers\Back\SpecTypeController;
use App\Http\Controllers\Back\StatisticsController;
use App\Http\Controllers\Back\StockNotifyController;
use App\Http\Controllers\Back\TagController;
use App\Http\Controllers\Back\TariffController;
use App\Http\Controllers\Back\ThemeController;
use App\Http\Controllers\Back\TicketController;
use App\Http\Controllers\Back\TransactionController;
use App\Http\Controllers\Back\UserController;
use App\Http\Controllers\Back\WalletController;
use App\Http\Controllers\Back\WalletHistoryController;
use App\Http\Controllers\Back\WarehouseController;
use App\Http\Controllers\Back\WidgetController;
use App\Http\Controllers\PushSubscriptionController;
use Illuminate\Support\Facades\Route;
use Rap2hpoutre\LaravelLogViewer\LogViewerController;
use App\Http\Controllers\Back\ReturnController;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

require __DIR__ . '/auth.php';
Route::get('province/get-cities', [ProvinceController::class, 'getCities'])->name('provinces.get-cities');

Route::group(['as' => 'admin.', 'prefix' => 'admin-old/' . admin_route_prefix()], function () {

    Route::get('login', [AdminLoginController::class, 'showLoginForm'])->name('login');

    Route::post('login/submit', [AdminLoginController::class, 'login'])->name('login-submit')->middleware('throttle:10,1');
    // راه‌اندازی اولیه (ثبت اولین مدیر) — آدرس جدید: /admin-old/{prefix}/setup
    // (مسیر قدیمی register به آدرس جدید ریدایرکت می‌شود تا لینک‌های قدیمی نشکند)
    Route::get('setup', [InstallController::class, 'showRegisterForm'])->name('register')->middleware(['CheckUserNotExists']);
    Route::post('setup', [InstallController::class, 'register'])->middleware(['CheckUserNotExists']);
    Route::get('register', fn () => redirect()->route('admin.register'))->name('register.legacy');
    Route::get('logout', [AdminLoginController::class, 'logout'])->name('logout');
});

// ------------------ Admin Part Routes
Route::group(['as' => 'admin.', 'prefix' => 'admin-old/' . admin_route_prefix(), 'middleware' => ['Admin']], function () {

    // ------------------ MainController
    Route::get('/', [MainController::class, 'index'])->name('dashboard');
    Route::get('cache-clear', [MainController::class, 'cache_clear'])->name('cache-clear');
    Route::get('get-tags', [MainController::class, 'get_tags'])->name('get-tags');
    Route::get('get-labels', [MainController::class, 'getLabels'])->name('get-labels');

    Route::get('notifications/panel', [MainController::class, 'notifications'])->name('notifications');
    Route::post('notifications/read-all', [MainController::class, 'markAllRead'])->name('notifications.readAll');
    Route::post('notifications/{id}/read', [MainController::class, 'markRead'])->name('notifications.read');

    Route::get('notifications/panel', [MainController::class, 'notifications'])->name('notifications');
    //Route::resource('notifications', NotificationController::class);
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [NotificationController::class, 'index'])->name('index');
        Route::get('list', [NotificationController::class, 'list'])->name('list');
        Route::get('recipients', [NotificationController::class, 'searchRecipients'])->name('recipients');
        Route::post('/', [NotificationController::class, 'store'])->name('store');
        Route::patch('{notificationManage}', [NotificationController::class, 'update'])->name('update');
        Route::patch('{notificationManage}/toggle-popup', [NotificationController::class, 'togglePopup'])->name('togglePopup');
        Route::delete('{notificationManage}', [NotificationController::class, 'destroy'])->name('destroy');
    });
    Route::get('file-manager', [MainController::class, 'fileManager'])->name('file-manager');
    Route::get('file-manager-iframe', [MainController::class, 'fileManagerIframe'])->name('file-manager-iframe');

    // ------------------ backups
    Route::get('backups', [BackupController::class, 'index'])->name('backups.index');
    Route::post('backups/create', [BackupController::class, 'create'])->name('backups.create');
    Route::get('backups/{backup}/download', [BackupController::class, 'download'])->name('backups.download');
    Route::delete('backups/{backup}', [BackupController::class, 'destroy'])->name('backups.destroy');

    // ------------------ users
    Route::resource('users', UserController::class);
    Route::post('users/api/index', [UserController::class, 'apiIndex'])->name('users.apiIndex');
    Route::delete('users/api/multipleDestroy', [UserController::class, 'multipleDestroy'])->name('users.multipleDestroy');

    Route::get('users/export/create', [UserController::class, 'export'])->name('users.export');
    Route::get('users/{user}/views', [UserController::class, 'views'])->name('users.views');
    Route::get('users/{user}/notifications', [UserController::class, 'notifications'])->name('users.notifications');
    Route::get('users/{user}/notification/create', [UserController::class, 'notification_create'])->name('users.notification.create');
    Route::post('users/{user}/notification/store', [UserController::class, 'notification_store'])->name('users.notification.store');
    Route::get('users/{user}/notification/{notification}/show', [UserController::class, 'notification_show'])->name('users.notification.show');
    Route::put('users/{user}/notification/{notification}/update', [UserController::class, 'notification_update'])->name('users.notification.update');
    Route::get('user/profile', [UserController::class, 'showProfile'])->name('user.profile.show');
    Route::put('user/profile', [UserController::class, 'updateProfile'])->name('user.profile.update');


    // ------------------ admins
    Route::resource('admins', AdminController::class);
    Route::post('admins/api/index', [AdminController::class, 'apiIndex'])->name('admins.apiIndex');
    Route::delete('admins/api/multipleDestroy', [AdminController::class, 'multipleDestroy'])->name('admins.multipleDestroy');
    Route::get('admins/export/excel', [AdminController::class, 'export'])->name('admins.export');
    Route::get('admins/{admin}/views', [AdminController::class, 'views'])->name('admins.views');
    Route::get('admin/profile', [AdminController::class, 'showProfile'])->name('admin.profile.show');
    Route::put('admin/profile', [AdminController::class, 'updateProfile'])->name('admin.profile.update');


    // ------------------ wallets

    Route::resource('wallets', WalletController::class)->only(['show']);
    Route::get('wallets/histories/{history}', [WalletController::class, 'history'])->name('wallets.history');
    Route::get('wallets/{wallet}/create', [WalletController::class, 'create'])->name('wallets.create');
    Route::post('wallets/{wallet}', [WalletController::class, 'store'])->name('wallets.store');
    Route::post('wallets/histories/{history}/status', [WalletController::class, 'status_pay'])->name('wallets.status_pay');


    // ------------------ Request-deposit
    Route::get('request-deposit', [RequestDepositAdmin::class, 'index'])->name('request-deposit.index');
    Route::get('request-deposit/histories/{history}', [RequestDepositAdmin::class, 'history'])->name('request-deposit.history');
    Route::get('request-deposit/export/create', [RequestDepositAdmin::class, 'export'])->name('request-deposit.export');
    // ------------------ products
    Route::resource('products', ProductController::class)->except('show');
    Route::post('products/api/index', [ProductController::class, 'apiIndex'])->name('products.apiIndex');
    Route::delete('products/api/multipleDestroy', [ProductController::class, 'multipleDestroy'])->name('products.multipleDestroy');
    Route::post('products/image-store', [ProductController::class, 'image_store']);
    Route::post('products/image-delete', [ProductController::class, 'image_delete']);
    Route::get('product/categories', [ProductController::class, 'categories'])->name('products.categories.index');
    Route::post('product/slug', [ProductController::class, 'generate_slug']);
    Route::get('products/export/create', [ProductController::class, 'export'])->name('products.export');
    Route::get('product/{product}/details', [ProductController::class, 'show_details'])->name('products.show_details');

    Route::get('product/prices', [ProductController::class, 'indexPrices'])->name('product.prices.index');
    Route::put('product/prices', [ProductController::class, 'updatePrices'])->name('product.prices.update');

    Route::get('product/prices-group', [ProductController::class, 'indexPricesGroup'])->name('product.pricesGroup.index');
    Route::put('product/prices-group', [ProductController::class, 'updatePricesGroup'])->name('product.pricesGroup.update');

    Route::post('product/get-product-from-site', [ProductController::class, 'getProductFromSite'])->name('product.get-product-site');
    // ------------------ sellers

    Route::post('sellers/api/index', [SellerControllers::class, 'apiIndex'])->name('sellers.apiIndex');
    Route::get('sellers/products', [SellerControllers::class, 'products'])->name('sellers.products');
    Route::post('sellers/products/api/index', [SellerControllers::class, 'productsApiIndex'])->name('sellers.products.apiIndex');
    Route::delete('sellers/api/multipleDestroy', [SellerControllers::class, 'multipleDestroy'])->name('sellers.multipleDestroy');
    Route::get('sellers/orders', [SellerControllers::class, 'orders'])->name('sellers.orders');
    Route::post('sellers/orders/api/index', [SellerControllers::class, 'apiOrdersIndex'])->name('sellers.orders.apiIndex');
    Route::get('sellers/{seller}/orders/{order}', [SellerControllers::class, 'seller_orders_show'])->name('sellers.orders.show');
    Route::get('sellers/{seller}/views', [SellerControllers::class, 'views'])->name('sellers.views');
    Route::get('sellers/{seller}/products', [SellerControllers::class, 'seller_products'])->name('sellers.seller_products');
    Route::get('sellers/{seller}/variants', [SellerControllers::class, 'seller_variants'])->name('sellers.seller_variants');
    Route::get('sellers/{seller}/orders', [SellerControllers::class, 'seller_orders'])->name('sellers.seller_orders');
    Route::post('sellers/{seller}/orders/api/index', [SellerControllers::class, 'seller_orders_ApiIndex'])->name('sellers.seller_orders.ApiIndex');
    Route::post('sellers/image-store', [SellerControllers::class, 'image_store']);
    Route::post('sellers/image-delete', [SellerControllers::class, 'image_delete']);
    /*Route::post('seller/slug', [ProductController::class, 'generate_slug']);*/
    Route::resource('sellers', SellerControllers::class);
    Route::get('sellers/orders/export/create', [SellerControllers::class, 'exportOrders'])->name('sellers.orders.exportOrders');
    Route::get('sellers/{seller}/orders/export/create', [SellerControllers::class, 'sellerOrdersExport'])->name('seller.orders.export');
    Route::get('sellers/{seller}/orders/{order}/print', [SellerControllers::class, 'printOrdersSeller'])->name('sellers.orders.print');

    Route::get('sellers/{seller}/notifications', [SellerControllers::class, 'notifications'])->name('sellers.notifications');
    Route::get('sellers/{seller}/notification/create', [SellerControllers::class, 'notification_create'])->name('sellers.notification.create');
    Route::post('sellers/{seller}/notification/store', [SellerControllers::class, 'notification_store'])->name('sellers.notification.store');
    Route::get('sellers/{seller}/notification/{notification}/show', [SellerControllers::class, 'notification_show'])->name('sellers.notification.show');
    Route::put('sellers/{seller}/notification/{notification}/update', [SellerControllers::class, 'notification_update'])->name('sellers.notification.update');

    // ------------------ discounts
    Route::resource('discounts', DiscountController::class)->except(['show']);

    // ------------------ provinces
    Route::resource('provinces', ProvinceController::class);
    Route::post('provinces/api/index', [ProvinceController::class, 'apiIndex'])->name('provinces.apiIndex');
    Route::delete('provinces/api/multipleDestroy', [ProvinceController::class, 'multipleDestroy'])->name('provinces.multipleDestroy');
    Route::post('provinces/api/sort', [ProvinceController::class, 'sort'])->name('provinces.sort');

    // ------------------ cities
    Route::resource('cities', CityController::class)->except(['index']);
    Route::post('cities/api/{province}/index', [CityController::class, 'apiIndex'])->name('cities.apiIndex');
    Route::delete('cities/api/multipleDestroy', [CityController::class, 'multipleDestroy'])->name('cities.multipleDestroy');
    Route::post('cities/api/sort', [CityController::class, 'sort'])->name('cities.sort');

    // ------------------ brands
    Route::resource('brands', BrandController::class)->except('show');
    Route::get('brands/ajax/get', [BrandController::class, 'ajax_get']);

    // ------------------ filters
    Route::resource('filters', FilterController::class)->except('show');

    // ------------------ attributeGroups
    Route::resource('attributeGroups', AttributeGroupController::class);
    Route::get('attributeGroups/{attributeGroup}/attributes', [AttributeGroupController::class, 'attributesIndex'])->name('attributes.index');
    Route::post('attributeGroup/sort', [AttributeGroupController::class, 'sort']);

    // ------------------ attributes
    Route::resource('attributes', AttributeController::class)->except(['index', 'show']);

    // ------------------ spec types
    Route::get('spectypes/spec-type-data', [SpecTypeController::class, 'getData'])->name('spectypes.getdata');
    Route::get('spectypes/ajax/get', [SpecTypeController::class, 'ajax_get']);
    Route::resource('spectypes', SpecTypeController::class)->except(['show', 'create']);

    // ------------------ size types
    Route::resource('sizetypes', SizeTypeController::class);
    Route::get('sizetypes/{sizetype}/values', [SizeTypeController::class, 'editValues'])->name('sizetypes.editValues');
    Route::put('sizetypes/{sizetype}/values', [SizeTypeController::class, 'updateValues'])->name('sizetypes.updateValues');

    // ------------------ posts
    Route::resource('posts', PostController::class)->except(['show']);
    Route::get('post/categories', [PostController::class, 'categories'])->name('posts.categories.index');
    Route::post('post/slug', [PostController::class, 'generate_slug']);


    // ------------------ categories
    Route::resource('categories', CategoryController::class)->only(['update', 'destroy', 'store', 'edit']);
    Route::post('categories/sort', [CategoryController::class, 'sort']);
    Route::post('category/slug', [CategoryController::class, 'generate_slug']);

    // ------------------ pages
    Route::prefix('pulse')->name('pulse.')->group(function () {
        Route::get('/', [PulseController::class, 'index'])->name('index');
        Route::get('/stream', [PulseController::class, 'stream'])->name('stream');
        Route::post('/refresh', [PulseController::class, 'refresh'])->name('refresh');
    });

    Route::prefix('activity-log')->name('activity-log.')->group(function () {
        Route::get('/', [ActivityLogController::class, 'index'])->name('index');
        Route::get('/{id}', [ActivityLogController::class, 'show'])->name('show');
        Route::delete('/delete-old', [ActivityLogController::class, 'deleteOld'])->name('delete-old');
    });

    // ------------------ pages
    Route::resource('pages', PageController::class)->except(['show']);

    // ------------------ apikeys
    Route::resource('apikeys', ApikeyController::class)->except(['show']);

    // ------------------ tickets
    Route::resource('tickets', TicketController::class)->except(['edit']);
    Route::post('tickets/file/store', [TicketController::class, 'storeFile'])->name('tickets.file.store');
    Route::delete('tickets/file/destroy', [TicketController::class, 'destoryFile'])->name('tickets.file.destroy');
    Route::post('tickets/{ticket}/type', [TicketController::class, 'type'])->name('tickets.type');
    // ------------------ menus
    Route::resource('menus', MenuController::class)->except(['edit']);
    Route::post('menus/sort', [MenuController::class, 'sort']);

    // ------------------ orders
    Route::resource('orders', OrderController::class);
    Route::get('order/{orderItem}', [OrderController::class, 'showItem'])->name('orders.show-item');
    Route::post('order/{orderItem}/update-status', [OrderController::class, 'itemShippingStatus'])->name('orders.order-item.update-status');
    Route::post('order/{orderItem}/update-tracking', [OrderController::class, 'itemTrackingStatus'])->name('orders.order-item.update-tracking');
    Route::post('orders/api/shippings-status', [OrderController::class, 'shippingsStatus'])->name('orders.shippings-status');
    Route::post('orders/{order}/shipping-status', [OrderController::class, 'shipping_status'])->name('orders.shipping-status');
    Route::post('orders/{order}/set-tracking-code', [OrderController::class, 'set_tracking_code'])->name('orders.set-tracking-code');
    Route::get('orders/{order}/print', [OrderController::class, 'print'])->name('orders.print');
    Route::get('orders/{order}/shipping-form', [OrderController::class, 'shippingForm'])->name('orders.shipping-form');
    Route::get('orders/{order}/shipping-form-min', [OrderController::class, 'shippingFormMin'])->name('orders.shipping-form-min');
    Route::post('orders/api/index', [OrderController::class, 'apiIndex'])->name('orders.apiIndex');
    Route::delete('orders/api/multipleDestroy', [OrderController::class, 'multipleDestroy'])->name('orders.multipleDestroy');
    Route::get('order/not-completed/products', [OrderController::class, 'notCompleted'])->name('orders.notCompleted');
    Route::get('orders/api/userInfo', [OrderController::class, 'userInfo'])->name('orders.userInfo');
    Route::get('orders/api/productsList', [OrderController::class, 'productsList'])->name('orders.productsList');
    Route::get('orders/api/printAllShippingForms', [OrderController::class, 'printAllShippingForms'])->name('orders.printAllShippingForms');
    Route::get('orders/api/printAllShippingFormsMin', [OrderController::class, 'printAllShippingFormsMin'])->name('orders.printAllShippingFormsMin');
    Route::get('orders/api/printAll', [OrderController::class, 'printAll'])->name('orders.printAll');

    Route::get('orders/export/create', [OrderController::class, 'export'])->name('orders.export');

    Route::prefix('returns')->name('returns.')->group(function () {
        Route::get('/', [ReturnController::class, 'index'])->name('index');
        Route::get('/{returnRequest}', [ReturnController::class, 'show'])->name('show');

        // ۱. تایید اولیه (پس از بررسی → منتظر ارسال محصول توسط مشتری)
        Route::post('/{returnRequest}/approve', [ReturnController::class, 'approve'])->name('approve');

        // ۲. ثبت ارسال محصول توسط مشتری (ادمین تایید می‌کند که مشتری محصول را ارسال کرده)
        Route::post('/{returnRequest}/customer-shipped', [ReturnController::class, 'markCustomerShipped'])->name('customer_shipped');

        // ۳. محصول دریافت شد — در حال بررسی نهایی
        Route::post('/{returnRequest}/received', [ReturnController::class, 'markReceived'])->name('received');

        // ۴الف. محصول مشکلی نداشت → دوباره ارسال شود
        Route::post('/{returnRequest}/reship', [ReturnController::class, 'reship'])->name('reship');

        // ۴ب. محصول مشکل داشت → تایید نهایی + بازگشت وجه به کیف پول
        Route::post('/{returnRequest}/complete', [ReturnController::class, 'complete'])->name('complete');

        // ۵. رد درخواست
        Route::post('/{returnRequest}/reject', [ReturnController::class, 'reject'])->name('reject');
    });
    Route::prefix('return')->name('returns.')->group(function () {
        // مدیریت دلایل مرجوعی
        Route::get('/reasons', [ReturnController::class, 'reasonsIndex'])->name('reasons');
        Route::post('/reasons', [ReturnController::class, 'reasonsStore'])->name('reasons.store');
        Route::delete('/reasons/{reason}', [ReturnController::class, 'reasonsDestroy'])->name('reasons.destroy');
        Route::post('/reasons/{reason}/toggle', [ReturnController::class, 'reasonsToggle'])->name('reasons.toggle');
    });


    // ------------------ carriers
    Route::resource('carriers', CarrierController::class);
    Route::get('carriers/{carrier}/cities', [CarrierController::class, 'cities'])->name('carriers.cities');

    // ------------------ tariffs
    Route::resource('tariffs', TariffController::class);

    // ------------------ transactions
    Route::get('transactions/seller-deposit', [TransactionController::class, 'seller_deposits'])->name('transactions.seller_deposits');;
    Route::resource('transactions', TransactionController::class)->only(['index', 'show', 'destroy']);
    Route::post('transactions/api/index', [TransactionController::class, 'apiIndex'])->name('transactions.apiIndex');
    Route::delete('transactions/api/multipleDestroy', [TransactionController::class, 'multipleDestroy'])->name('transactions.multipleDestroy');

    // ------------------ wallet-histories
    Route::resource('wallet-histories', WalletHistoryController::class)->only(['index', 'show']);

    // ------------------ currencies
    Route::resource('currencies', CurrencyController::class)->except(['show']);


    //---------------------- searches
    Route::resource('searches', SearchController::class)->only(['index', 'destroy']);
    Route::post('searches/details', [SearchController::class, 'details'])->name('searches.details');
    Route::post('searches/multipleDestroy', [SearchController::class, 'multipleDestroy'])->name('searches.multipleDestroy');
    // ------------------ sliders
    Route::resource('sliders', SliderController::class)->except(['show']);
    Route::post('sliders/sort', [SliderController::class, 'sort']);

    // ------------------ banners
    Route::resource('banners', BannerController::class)->except(['show']);
    Route::post('banners/sort', [BannerController::class, 'sort']);

    // ------------------ links
    Route::resource('links', LinkController::class)->except(['show']);
    Route::post('links/sort', [LinkController::class, 'sort']);
    Route::get('links/groups', [LinkController::class, 'groups'])->name('links.groups.index');
    Route::put('links/groups/update', [LinkController::class, 'updateGroups'])->name('links.groups.update');

    // ------------------ statistics
    Route::get('statistics/viewsList', [StatisticsController::class, 'viewsList'])->name('statistics.viewsList');
    Route::get('statistics/views', [StatisticsController::class, 'views'])->name('statistics.views');
    Route::get('statistics/viewCounts', [StatisticsController::class, 'viewCounts'])->name('statistics.viewCounts');
    Route::get('statistics/viewerCounts', [StatisticsController::class, 'viewerCounts'])->name('statistics.viewerCounts');
    Route::get('statistics/viewers', [StatisticsController::class, 'viewers'])->name('statistics.viewers');

    Route::get('statistics/orders', [StatisticsController::class, 'orders'])->name('statistics.orders');
    Route::get('statistics/orderValues', [StatisticsController::class, 'orderValues'])->name('statistics.orderValues');
    Route::get('statistics/orderCounts', [StatisticsController::class, 'orderCounts'])->name('statistics.orderCounts');
    Route::get('statistics/orderUsers', [StatisticsController::class, 'orderUsers'])->name('statistics.orderUsers');
    Route::get('statistics/orderProducts', [StatisticsController::class, 'orderProducts'])->name('statistics.orderProducts');

    Route::get('statistics/orders/products', [StatisticsController::class, 'products'])->name('statistics.orders.products');
    Route::get('statistics/orders/products/get-product', [StatisticsController::class, 'productTemplate'])->name('statistics.orders.products.get_product');


    Route::get('statistics/users', [StatisticsController::class, 'users'])->name('statistics.users');
    Route::get('statistics/userCounts', [StatisticsController::class, 'userCounts'])->name('statistics.userCounts');

    Route::get('statistics/smsLog', [StatisticsController::class, 'smsLog'])->name('statistics.smsLog');

    // ------------------import
    Route::get('post/import', [ImportsController::class, 'postsExcelImport'])->name('import.posts');
    Route::post('post/import', [ImportsController::class, 'postsExcelImport_Store'])->name('import.posts.store');
    Route::post('post/import/delete-error', [ImportsController::class, 'deletePostErrorFile'])->name('import.posts.delete-error');

    Route::get('products/import', [ImportsController::class, 'productsExcelImport'])->name('import.products');
    Route::post('products/import', [ImportsController::class, 'productsExcelImport_Store'])->name('import.products.store');
    Route::post('products/import/delete-error', [ImportsController::class, 'deleteProductErrorFile'])->name('import.products.delete-error');
    Route::get('user/import', [ImportsController::class, 'usersExcelImport'])->name('import.users');
    Route::post('user/import', [ImportsController::class, 'usersExcelImport_Store'])->name('import.users.store');
    Route::post('user/import/delete-error', [ImportsController::class, 'deleteUserErrorFile'])->name('import.users.delete-error');

    Route::resource('newsletters', NewsletterController::class)->except(['create', 'edit', 'update']);
    Route::post('newsletters/multipleDestroy', [NewsletterController::class, 'multipleDestroy'])->name('newsletters.multipleDestroy');
    Route::get('newsletter/export', [NewsletterController::class, 'export'])->name('newsletters.export');

    // ------------------ sms
    Route::resource('sms', SmsController::class)->only(['show']);


    //-------------------------Forms
    Route::resource('forms', FormController::class)->except(['show']);
    // مدیریت فیلدها
    Route::post('forms/{form}/add-field', [FormController::class, 'addField'])->name('forms.add-field');
    Route::put('forms/{form}/fields/{field}', [FormController::class, 'updateField'])->name('forms.update-field');
    Route::delete('forms/{form}/fields/{field}', [FormController::class, 'deleteField'])->name('forms.delete-field');
    Route::post('forms/{form}/reorder-fields', [FormController::class, 'reorderFields'])->name('forms.reorder-fields');
    Route::post('forms/render-fields', [FormController::class, 'renderFields'])->name('forms.render-fields');
    Route::post('forms/multipleDestroy', [FormController::class, 'multipleDestroy'])->name('forms.multipleDestroy');
    // مدیریت ارسال‌ها
    Route::get('forms/{form}/submissions', [FormController::class, 'submissions'])->name('forms.submissions');
    Route::get('forms/{form}/submissions/{submission}', [FormController::class, 'showSubmission'])->name('forms.submissions.show');
    Route::delete('forms/{form}/submissions/{submission}', [FormController::class, 'deleteSubmission'])->name('forms.submissions.destroy');
    Route::post('forms/{form}/submissions/multipleDestroy', [FormController::class, 'multipleDestroySubmission'])->name('forms.submissions.multipleDestroy');

    Route::get('forms/{form}/preview', [FormController::class, 'preview'])->name('forms.preview');
    Route::post('forms/{form}/save-settings', [FormController::class, 'saveSettings'])->name('forms.save-settings');
    Route::post('forms/{form}/update-fields-display', [FormController::class, 'updateFieldsDisplay'])->name('forms.update-fields-display');

    //----------------Tags
    Route::resource('tags', TagController::class);
    Route::post('tags/multiple-destroy', [TagController::class, 'multipleDestroy'])->name('tags.multiple-destroy');
    Route::get('tags/{tag}/details', [TagController::class, 'details'])->name('tags.details');
    Route::get('tags/export', [TagController::class, 'export'])->name('tags.export');

    // ------------------ contacts
    Route::resource('contacts', ContactController::class)->only(['index', 'show', 'destroy']);

    // ------------------ stock-notifies
    Route::resource('stock-notifies', StockNotifyController::class)->only(['index', 'show', 'destroy']);

    // ------------------ comments
    Route::resource('comments', CommentController::class)->only(['show', 'destroy', 'update']);
    Route::get('comments/index/products', [CommentController::class, 'productComments'])->name('comments.products');
    Route::get('comments/index/posts', [CommentController::class, 'postComments'])->name('comments.posts');

    Route::prefix('comments')->name('comments.')->group(function () {
        Route::post('/{comment}/reply', [CommentController::class, 'reply'])->name('reply');
        Route::put('/reply/{reply}', [CommentController::class, 'updateReply'])->name('update-reply');
        Route::put('/reply/{reply}/status', [CommentController::class, 'updateReplyStatus'])->name('update-reply-status');
        Route::delete('/reply/{reply}', [CommentController::class, 'destroyReply'])->name('destroy-reply');
    });

    // ------------------ messages
    Route::resource('messages', MessageController::class)->only(['index', 'show', 'store', 'destroy', 'update']);
    Route::get('messages/birthday', [MessageController::class, 'birthday'])->name('messages.birthday');
    Route::post('messages/birthday/store', [MessageController::class, 'birthdayStore'])->name('messages.birthday.store');


    // ------------------ reviews
    Route::resource('reviews', ReviewController::class)->only(['index', 'show', 'destroy', 'update']);

    // ------------------ roles
    Route::resource('roles', RoleController::class);

    // ------------------ permissions
    Route::get('permissions', [PermissionController::class, 'index'])->name('permissions.index');
    Route::put('permissions', [PermissionController::class, 'update'])->name('permissions.update');

    // ------------------ widgets
    Route::resource('widgets', WidgetController::class)->except(['show']);
    Route::get('widgets/{key}/template', [WidgetController::class, 'template'])->name('widgets.template');
    Route::post('widget/sort', [WidgetController::class, 'sort'])->name('widgets.sort');

    // ------------------ posts widgets
    Route::resource('posts-widgets', PostWidgetController::class)->except(['show']);
    Route::get('posts-widgets/{key}/template', [PostWidgetController::class, 'template'])->name('posts-widgets.template');
    Route::post('posts-widget/sort', [PostWidgetController::class, 'sort'])->name('posts-widgets.sort');


    // مدیریت انبارها
    Route::prefix('warehouses')->name('warehouses.')->group(function () {
        // روت‌های اصلی CRUD (دستی برای کنترل بیشتر)
        Route::get('/', [WarehouseController::class, 'index'])->name('index');
        Route::get('/create', [WarehouseController::class, 'create'])->name('create');
        Route::post('/', [WarehouseController::class, 'store'])->name('store');
        Route::get('/{warehouse}/edit', [WarehouseController::class, 'edit'])->name('edit');
        Route::put('/{warehouse}', [WarehouseController::class, 'update'])->name('update');
        Route::delete('/{warehouse}', [WarehouseController::class, 'destroy'])->name('destroy');
        Route::get('/{warehouse}/export', [WarehouseController::class, 'export'])->name('export');

        Route::get('/{warehouse}/product/{product}/variations', [WarehouseController::class, 'productVariations'])->name('product-variations');
        Route::get('/{warehouse}/product/{product}/variations/{price}', [WarehouseController::class, 'getVariationData'])->name('product-variations.variation');
        Route::post('/{warehouse}/product/{product}/variation/{price}/update', [WarehouseController::class, 'updateVariation'])->name('product-variations.update');
        Route::post('/{warehouse}/product/{product}/variation/{price}/update', [WarehouseController::class, 'updateVariation'])->name('product-variations.update');
        Route::post('/{warehouse}/product/{product}/variation/store', [WarehouseController::class, 'storeVariation'])->name('product-variations.store');
        Route::delete('/{warehouse}/product/{product}/variation/{price}/destroy', [WarehouseController::class, 'destroyVariation'])->name('product-variations.destroy');

        Route::get('/{warehouse}/product/{product}/stock-history', [WarehouseController::class, 'stockHistory'])->name('product.stock-history');
        // روت‌های اضافی
        Route::get('/{warehouse}', [WarehouseController::class, 'show'])->name('show');
        Route::post('/{warehouse}/toggle-status', [WarehouseController::class, 'toggleStatus'])->name('toggle-status');
        Route::get('/{warehouse}/movements', [WarehouseController::class, 'movements'])->name('movements');
        Route::get('/{warehouse}/movements/export', [WarehouseController::class, 'exportMovements'])->name('movements.export');
        Route::get('/{warehouse}/products', [WarehouseController::class, 'products'])->name('products');
        Route::post('/{warehouse}/stock-take', [WarehouseController::class, 'stockTake'])->name('stock-take');
        Route::get('/{warehouse}/stock-take-data', [WarehouseController::class, 'stockTakeData'])->name('stock-take-data');

        Route::get('/{warehouse}/bulk-stock-data', [WarehouseController::class, 'bulkStockData'])->name('bulk-stock-data');
        Route::post('/{warehouse}/bulk-stock-update', [WarehouseController::class, 'bulkStockUpdate'])->name('bulk-stock-update');

        // دریافت اطلاعات تنوع‌ها برای AJAX
        //Route::get('/get-product-variations/{productId}', [WarehouseController::class, 'getProductVariations'])->name('get-product-variations');
    });

    Route::delete('faqs/multiple-destroy', [FaqController::class, 'multipleDestroy'])->name('faqs.multipleDestroy');
    Route::resource('faqs', FaqController::class);

    // ------------------ themes
    Route::resource('themes', ThemeController::class)->except(['edit']);

    Route::get('theme/settings', [ThemeController::class, 'showSettings'])->name('themes.settings');
    Route::post('theme/settings', [ThemeController::class, 'updateSettings']);

    // ------------------ settings
    Route::get('settings/information', [SettingController::class, 'showInformation'])->name('settings.information');
    Route::post('settings/information', [SettingController::class, 'updateInformation']);

    Route::get('settings/socials', [SettingController::class, 'showSocials'])->name('settings.socials');
    Route::post('settings/socials', [SettingController::class, 'updateSocials']);

    Route::get('settings/gateways', [SettingController::class, 'showGateways'])->name('settings.gateways');
    Route::post('settings/gateways', [SettingController::class, 'updateGateways']);

    Route::get('settings/others', [SettingController::class, 'showOthers'])->name('settings.others');
    Route::post('settings/others', [SettingController::class, 'updateOthers']);

    Route::get('settings/sms', [SettingController::class, 'showSms'])->name('settings.sms');
    Route::post('settings/sms', [SettingController::class, 'updateSms']);

    Route::prefix('settings/floating-widget')->name('settings.floating-widget.')->group(function () {
        Route::get('/', [FloatingWidgetController::class, 'index'])->name('index');
        Route::put('/save', [FloatingWidgetController::class, 'update'])->name('update');
    });


    Route::get('settings/seller-hero', [SettingController::class, 'seller_hero'])->name('settings.seller-hero');
    Route::get('settings/seller-hero/create', [SettingController::class, 'seller_hero_create'])->name('settings.seller-hero-create');
    Route::post('settings/seller-hero/store', [SettingController::class, 'seller_hero_store'])->name('settings.seller-hero-store');
    Route::get('settings/seller-hero/edit/{sellerHero}', [SettingController::class, 'seller_hero_edit'])->name('settings.seller-hero-edit');
    Route::Put('settings/seller-hero/update/{sellerHero}', [SettingController::class, 'seller_hero_update'])->name('settings.seller-hero-update');
    Route::Delete('settings/seller-hero/destroy/{sellerHero}', [SettingController::class, 'seller_hero_destroy'])->name('settings.seller-hero-destroy');

    Route::get('settings/seller-commission', [SettingController::class, 'seller_commission'])->name('settings.seller-commission');
    Route::get('settings/seller-commission/create', [SettingController::class, 'seller_commission_create'])->name('settings.seller-commission-create');
    Route::post('settings/seller-commission/store', [SettingController::class, 'seller_commission_store'])->name('settings.seller-commission-store');
    Route::get('settings/seller-commission/edit/{sellerCommission}', [SettingController::class, 'seller_commission_edit'])->name('settings.seller-commission-edit');
    Route::Put('settings/seller-commission/update/{sellerCommission}', [SettingController::class, 'seller_commission_update'])->name('settings.seller-commission-update');
    Route::Delete('settings/seller-commission/destroy/{sellerCommission}', [SettingController::class, 'seller_commission_destroy'])->name('settings.seller-commission-destroy');

    Route::get('settings/seller-question', [SettingController::class, 'seller_question'])->name('settings.seller-question');
    Route::get('settings/seller-question/create', [SettingController::class, 'seller_question_create'])->name('settings.seller-question-create');
    Route::post('settings/seller-question/store', [SettingController::class, 'seller_question_store'])->name('settings.seller-question-store');
    Route::get('settings/seller-question/edit/{sellerQuestion}', [SettingController::class, 'seller_question_edit'])->name('settings.seller-question-edit');
    Route::Put('settings/seller-question/update/{sellerQuestion}', [SettingController::class, 'seller_question_update'])->name('settings.seller-question-update');
    Route::Delete('settings/seller-question/destroy/{sellerQuestion}', [SettingController::class, 'seller_question_destroy'])->name('settings.seller-question-destroy');

    Route::get('settings/seller-econtract', [SettingController::class, 'seller_econtract'])->name('settings.seller-econtract');
    Route::post('settings/seller-econtract', [SettingController::class, 'seller_econtract_store'])->name('settings.seller-econtract-store');

    Route::delete('filds/multipleDestroy', [FildController::class, 'multipleDestroy'])->name('filds.multipleDestroy');
    Route::resource('filds', FildController::class);

    Route::delete('redirects/multipleDestroy', [RedirectInternalController::class, 'multipleDestroy'])->name('redirects.multipleDestroy');
    Route::resource('redirects', RedirectInternalController::class);
    // ------------------ sellers


    Route::prefix('sessions')->name('sessions.')->group(function () {
        Route::get('/', [AdminSessionController::class, 'index'])->name('index');
        Route::delete('/{id}', [AdminSessionController::class, 'destroy'])->name('destroy');
        Route::delete('/admin/{adminId}/all', [AdminSessionController::class, 'destroyAllAdminSessions'])->name('destroy-all-admin');
        Route::post('/clear-inactive', [AdminSessionController::class, 'clearInactive'])->name('clear-inactive');
        Route::post('/logout-other-devices', [AdminSessionController::class, 'logoutOtherDevices'])->name('logout-other-devices');

        Route::post('/{id}/block', [AdminSessionController::class, 'blockSession'])->name('block');
        Route::get('/blocked-list', [AdminSessionController::class, 'blockedList'])->name('blocked-list');
        Route::delete('/unblock/{id}', [AdminSessionController::class, 'unblockDevice'])->name('unblock');
        Route::delete('/clear-blocks/{adminId}', [AdminSessionController::class, 'clearAdminBlocks'])->name('clear-blocks');
    });


    Route::prefix('holidays')->name('holidays.')->group(function () {
        Route::get('/', [HolidayController::class, 'index'])->name('index');
        Route::post('/check', [HolidayController::class, 'check'])->name('check');
        Route::post('/convert-to-jalali', [HolidayController::class, 'convertToJalali'])->name('convert-to-jalali');
        Route::post('/get-start-dates', [HolidayController::class, 'getStartDates'])->name('get-start-dates');
        Route::post('/update/{year}', [HolidayController::class, 'updateHolidays'])->name('update');
        Route::get('/get/{year}', [HolidayController::class, 'getHolidaysByYear'])->name('get');
    });
    // ------------------ developer routes
    Route::group(['middleware' => 'CheckCreator'], function () {

        // ------------------ logs
        Route::get('logs', [LogViewerController::class, 'index'])->name('logs.index');

        // ------------------ settings
        Route::get('developer/settings', [DeveloperController::class, 'showSettings'])->name('developer.settings');
        Route::put('developer/settings', [DeveloperController::class, 'updateSettings']);

        Route::post('developer/downApplication', [DeveloperController::class, 'downApplication'])->name('developer.downApplication');
        Route::post('developer/upApplication', [DeveloperController::class, 'upApplication'])->name('developer.upApplication');

        Route::post('developer/webpushNotification', [DeveloperController::class, 'webpushNotification'])->name('developer.webpushNotification');
        Route::post('developer/clear-cache', [DeveloperController::class, 'clearCache'])->name('developer.clearCache');

        // ------------------ updater
        Route::get('developer/updater', [DeveloperController::class, 'showUpdater'])->name('developer.showUpdater');
        Route::post('developer/updater', [DeveloperController::class, 'updateApplication'])->name('developer.updateApplication');
        Route::post('developer/updaterAfter', [DeveloperController::class, 'updaterAfter'])->name('developer.updaterAfter');
        Route::get('/developer/update-status', [DeveloperController::class, 'updateStatus'])->name('developer.updateStatus');
        Route::get('/developer/check-update', [DeveloperController::class, 'checkUpdate'])->name('developer.checkUpdate');
    });


    Route::prefix('robots')->name('robots.')->group(function () {
        Route::get('/', [RobotsController::class, 'index'])->name('index');
        Route::post('/update', [RobotsController::class, 'update'])->name('update');
        Route::post('/preview', [RobotsController::class, 'preview'])->name('preview');

    });


    Route::prefix('packages')->name('packages.')->group(function () {
        // --- لیست و جزئیات ---
        Route::get('/', [PackageController::class, 'index'])->name('index');
        Route::get('installed', [PackageController::class, 'installed'])->name('installed');
        Route::get('{slug}', [PackageController::class, 'show'])->name('show');

        // --- آپدیت‌ها ---
        Route::post('check-updates', [PackageController::class, 'checkUpdates'])->name('check-updates');
        Route::post('{slug}/check-purchase', [PackageController::class, 'checkPurchase'])->name('check-purchase');
        // --- نصب ---
        Route::post('{slug}/install', [PackageController::class, 'startInstall'])->name('install');
        Route::post('{slug}/update', [PackageController::class, 'update'])->name('update');
        Route::post('{slug}/uninstall', [PackageController::class, 'uninstall'])->name('uninstall');
        Route::post('{slug}/toggle', [PackageController::class, 'toggleActivation'])->name('toggle');

        // --- وضعیت (polling) ---
        Route::get('{slug}/status', [PackageController::class, 'status'])->name('status');
    });


});


// Push Subscriptions
Route::post('subscriptions', [PushSubscriptionController::class, 'update']);
Route::post('subscriptions/delete', [PushSubscriptionController::class, 'destroy']);

// Manifest file (optional if VAPID is used)
Route::get('manifest.json', function () {
    return [
        'name' => config('app.name'),
        'gcm_sender_id' => config('webpush.gcm.sender_id')
    ];
});


/*
|--------------------------------------------------------------------------
| ربات‌گیر (Bot Trap)
|--------------------------------------------------------------------------
| مسیرهای طعمه: اسکنرهای خودکار و ربات‌های مخرب همیشه این مسیرها را پیمایش
| می‌کنند؛ هیچ کاربر واقعی‌ای به آن‌ها سر نمی‌زند. هر ضربه = مسدودسازی IP.
| این مسیرها در robots.txt هم Disallow شده‌اند (ربات‌های مطیع می‌آیند و تله می‌شوند).
*/
Route::match(['get', 'post'], 'bot-trap/hit', [\App\Http\Controllers\BotTrapController::class, 'catch'])
    ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);

foreach ([
    'wp-login.php', 'wp-admin', 'wp-admin/', 'wp-content', 'wp-includes', 'wordpress',
    'xmlrpc.php', 'phpmyadmin', 'pma', 'adminer.php', 'wp-config.php', 'wp-settings.php',
    '.env', '.git/config', 'phpinfo.php', 'info.php', 'shell.php', 'eval-stdin.php',
    'config.json', 'composer.json.bak', '.aws/credentials', 'cgi-bin', 'telescope',
    'vendor/phpunit/phpunit/src/Util/PHP/eval-stdin.php', 'actuator/gateway/logout',
] as $trapPath) {
    Route::match(['get', 'post'], $trapPath, [\App\Http\Controllers\BotTrapController::class, 'catch']);
}

// به‌روزرسانی خودکار csrf برای اسکریپت‌های داخلی — دیگر توکن به بیرون درز نمی‌کند
Route::get('refresh-csrf', function () {
    abort_unless(auth('web')->check() || auth('adminPanel')->check(), 403);

    return csrf_token();
})->name('csrf')->middleware('throttle:30,1');

Route::get('/captcha/image', [\App\Http\Controllers\CaptchaController::class, 'image'])
    ->name('captcha.image')
    ->middleware('throttle:120,1'); // ضد درخواست‌های انبوه

/*Route::post('/bale/webhook', [BaleWebhookController::class, 'handle'])
    ->name('bale.webhook');*/
Route::get('packages/payment/callback', [PackagePaymentController::class, 'callback'])->name('admin.packages.payment.callback');

