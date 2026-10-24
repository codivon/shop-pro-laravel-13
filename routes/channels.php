<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| امنیتی: کانال‌های عمومی (Channel) به کانال‌های خصوصی تبدیل شدند و
| احراز هویت/مجوز واقعی دارند — هرکس فقط رویدادهای مخصوص خودش را می‌شنود.
|
*/

// رویدادهای سفارش — فقط پنل مدیریت
Broadcast::channel('orders', function () {
    return auth('adminPanel')->check();
});

// رویدادهای پیام — فقط پنل مدیریت
Broadcast::channel('send-message', function () {
    return auth('adminPanel')->check();
});

// پیام‌های تماس با ما — فقط پنل مدیریت
Broadcast::channel('contacts', function () {
    return auth('adminPanel')->check();
});

// صندوق پیام کاربر — فقط خود کاربر
Broadcast::channel('inbox-user.{userId}', function ($user, int $userId) {
    return (int) $user->id === $userId;
});
