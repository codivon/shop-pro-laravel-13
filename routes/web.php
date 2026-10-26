<?php

/**
 * روت‌های فرانت پکیج باشگاه مشتریان.
 *
 *  • پنل مشتری (profile/club) — نیازمند ورود: صفحهٔ باشگاه، تبدیل امتیاز،
 *    فعال‌سازی کارت هدیه، راهنمای درون‌صفحه‌ای (درس فاز ۸۹: لینک راهنما در
 *    منوی فرانت نیست).
 *  • صفحهٔ فرود دعوت (referral/{code}) — عمومی؛ ریدایرکت به فلوی ثبت‌نام
 *    هسته با ?ref= (عین هسته).
 */

use Illuminate\Support\Facades\Route;
use Modules\CustomerClub\Http\Controllers\Front\ClubController;
use Modules\CustomerClub\Http\Controllers\Front\GuideController;
use Modules\CustomerClub\Http\Controllers\Front\ReferralLandingController;

Route::prefix('profile/club')
    ->name('front.club.')
    ->middleware(['web', 'auth'])
    ->group(function () {

        Route::get('/', [ClubController::class, 'index'])->name('index');
        Route::post('redeem', [ClubController::class, 'redeem'])->name('redeem');
        Route::post('gift-card', [ClubController::class, 'redeemGiftCard'])->name('giftcard.redeem');
        Route::get('guide', [GuideController::class, 'index'])->name('guide');
    });

// صفحهٔ فرود لینک دعوت — عمومی (مثل هسته)
Route::get('referral/{code}', [ReferralLandingController::class, 'show'])
    ->middleware('web')
    ->name('front.club.referral.landing');
