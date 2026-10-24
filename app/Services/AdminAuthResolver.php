<?php

namespace App\Services;

use App\Models\Admin;
use Illuminate\Support\Facades\Auth;

/*
 * یافتن مدیر با username یا email + تأیید رمز — بدون ورود کامل به سشن
 *
 * چرا؟ SessionGuard::validate() فقط bool برمی‌گرداند؛ در جریان ورود دومرحله‌ای
 * (فاز ۴۴) به خودِ مدل Admin نیاز داریم تا کد 2FA برایش ارسال شود، بدون این‌که
 * guard هنوز login شده باشد.
 */
class AdminAuthResolver
{
    public static function resolve(string $username, string $password): ?Admin
    {
        $provider = Auth::guard('adminPanel')->getProvider();

        // فاز ۷۲ — علاوه بر username و email، با «شماره موبایل» هم می‌توان وارد شد
        foreach (['username', 'email', 'mobile'] as $field) {
            /** @var Admin|null $admin */
            $admin = $provider->retrieveByCredentials([$field => $username, 'password' => $password]);

            if ($admin && $provider->validateCredentials($admin, ['password' => $password])) {
                return $admin;
            }
        }

        return null;
    }
}
