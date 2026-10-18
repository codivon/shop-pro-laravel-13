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

use App\Livewire\Admin\Dashboard;
use App\Livewire\Admin\Auth\Login;
use App\Livewire\Admin\Products\ProductIndex;
use App\Livewire\Admin\Products\ProductForm;
use App\Livewire\Admin\Orders\OrderIndex;
use App\Livewire\Admin\Orders\OrderShow;
use App\Livewire\Admin\Orders\OrderItemShow;
use App\Livewire\Admin\Orders\OrderCreate;
use App\Livewire\Admin\Orders\OrderNotCompleted;
use App\Http\Controllers\AdminPanel\LogoutController;
use App\Http\Controllers\Back\OrderController as LegacyOrderController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin/' . admin_route_prefix())->name('np.')->group(function () {

    /*
    |----------------------
    | احراز هویت
    |----------------------
    */
    Route::middleware(\App\Http\Middleware\GuestAdminPanel::class)->group(function () {
        Route::get('login', Login::class)->name('login');
    });

    Route::get('logout', LogoutController::class)->name('logout');

    /*
    |----------------------
    | بخش‌های احراز هویت‌شده پنل
    |----------------------
    */
    Route::middleware(\App\Http\Middleware\AdminPanel::class)->group(function () {

        // نمای کلی
        Route::get('/', Dashboard::class)->name('dashboard');

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

        // سایر بخش‌ها در فازهای بعدی اضافه می‌شوند:
        // فاز ۳: فروشندگان / ...

    });
});
