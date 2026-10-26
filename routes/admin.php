<?php

/**
 * روت‌های ادمین پکیج باشگاه مشتریان.
 *
 * قرارداد ماژول‌ها (الگوی DigiPay/MarketingMessaging): prefix + name +
 * middleware در همین فایل ساخته می‌شود؛ RouteServiceProvider فقط فایل را با
 * middleware('web') لود می‌کند. 'Admin' میدل‌ور هسته است (App\Http\Middleware\Admin).
 */

use Illuminate\Support\Facades\Route;
use Modules\CustomerClub\Http\Controllers\Back\DashboardController;
use Modules\CustomerClub\Http\Controllers\Back\GiftCardController;
use Modules\CustomerClub\Http\Controllers\Back\GuideController;
use Modules\CustomerClub\Http\Controllers\Back\MembersController;
use Modules\CustomerClub\Http\Controllers\Back\PointsController;
use Modules\CustomerClub\Http\Controllers\Back\ReferralsController;
use Modules\CustomerClub\Http\Controllers\Back\RulesController;
use Modules\CustomerClub\Http\Controllers\Back\SettingsController;

Route::prefix('admin/' . admin_route_prefix() . '/customerclub')
    ->name('admin.customerclub.')
    ->middleware(['Admin'])
    ->group(function () {

        // ===== داشبورد باشگاه =====
        Route::get('/', [DashboardController::class, 'index'])
            ->middleware('can:customerclub')
            ->name('dashboard');

        // ===== قوانین امتیاز =====
        Route::get('rules', [RulesController::class, 'index'])
            ->middleware('can:customerclub.rules')
            ->name('rules.index');
        Route::post('rules/{rule}', [RulesController::class, 'update'])
            ->middleware('can:customerclub.rules')
            ->name('rules.update');

        // ===== دفتر امتیازها =====
        Route::get('points', [PointsController::class, 'index'])
            ->middleware('can:customerclub.points')
            ->name('points.index');

        // ===== اعضا + تنظیم دستی امتیاز =====
        Route::get('members', [MembersController::class, 'index'])
            ->middleware('can:customerclub.members')
            ->name('members.index');
        Route::post('members/{user}/adjust', [MembersController::class, 'adjust'])
            ->middleware('can:customerclub.members')
            ->name('members.adjust');

        // ===== کارت‌های هدیه =====
        Route::get('gift-cards', [GiftCardController::class, 'index'])
            ->middleware('can:customerclub.giftcards')
            ->name('giftcards.index');
        Route::post('gift-cards', [GiftCardController::class, 'issue'])
            ->middleware('can:customerclub.giftcards')
            ->name('giftcards.issue');
        Route::post('gift-cards/{giftCard}/toggle', [GiftCardController::class, 'toggle'])
            ->middleware('can:customerclub.giftcards')
            ->name('giftcards.toggle');

        // ===== گزارش معرفی دوستان =====
        Route::get('referrals', [ReferralsController::class, 'index'])
            ->middleware('can:customerclub.referrals')
            ->name('referrals.index');

        // ===== تنظیمات =====
        Route::get('settings', [SettingsController::class, 'edit'])
            ->middleware('can:customerclub.settings')
            ->name('settings.edit');
        Route::post('settings', [SettingsController::class, 'update'])
            ->middleware('can:customerclub.settings')
            ->name('settings.update');

        // ===== راهنما و آموزش (فاز ۸۶) =====
        Route::get('guide', [GuideController::class, 'index'])
            ->middleware('can:customerclub')
            ->name('guide');
    });
