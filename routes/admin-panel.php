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
use App\Http\Controllers\AdminPanel\LogoutController;
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

        // سایر بخش‌ها در فازهای بعدی اضافه می‌شوند:
        // فاز ۲: سفارشات / ...

    });
});
