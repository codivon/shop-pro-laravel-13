<?php

namespace Modules\CustomerClub\Support;

use Illuminate\Support\Facades\View;

/**
 * تشخیص «پنل فعال» برای صفحات و منوی پکیج (سازگاری دو-پنلی — الگوی DigiPay).
 *
 * نکته: ویوهای back ماژول با @extends('back.layouts.master') خودکار از
 * App\Support\ActivePanel هسته پیروی می‌کنند؛ این کلاس فقط برای پارشال منو
 * (که خارج از layout رندر می‌شود) لازم است.
 */
class ActivePanel
{
    public const COOKIE = 'ap_panel';

    public static function isNew(): bool
    {
        $cookie = (string) request()->cookie(self::COOKIE);

        if ($cookie === 'new') {
            return true;
        }

        if ($cookie === 'old') {
            return false;
        }

        return !request()->is('admin-old/*');
    }

    public static function hasNewChrome(): bool
    {
        return View::exists('admin-panel::layouts.module');
    }

    /** تصمیم نهایی @extends برای صفحات پکیج */
    public static function useNewPanel(): bool
    {
        return self::isNew() && self::hasNewChrome();
    }
}
