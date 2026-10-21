<?php

namespace App\Http\Middleware;

use Illuminate\Cookie\Middleware\EncryptCookies as Middleware;

class EncryptCookies extends Middleware
{
    /**
     * The names of the cookies that should not be encrypted.
     *
     * @var array
     */
    protected $except = [
        // تم پنل ادمین: سمت کلاینت با document.cookie ست می‌شود و layout
        // سمت سرور آن را می‌خواند — نباید رمزنگاری شود.
        'admin-theme',
        // پنل فعال (old/new): هر دو layout پنل با document.cookie ست می‌کنند و
        // صفحات/منوهای پکیج‌ها (ActivePanel ماژول‌ها) سمت سرور آن را می‌خوانند —
        // کوکی خام JS است و اگر رمزنگاری‌شده انتظار برود، از درخواست حذف می‌شود.
        'ap_panel',
    ];
}
