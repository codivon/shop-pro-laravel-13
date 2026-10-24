<?php

namespace App\Http\Controllers\Install;

use RachidLaasri\LaravelInstaller\Controllers\DatabaseController as VendorDatabaseController;

/**
 * فاز ۲۱ — جانشین DatabaseController پکیج نصب (rachidlaasri/laravel-installer)
 *
 * مشکل قبلی: متد database() همه‌ی migrate + seed را در «یک درخواست» اجرا می‌کرد؛
 * روی هاست‌های اشتراکی با ~۱۳۰ مایگریشن، PHP وسط کار Timeout می‌خورد (صفحه سیاه)
 * در حالی که MySQL به کار ادامه می‌داد و بعد از رفرش، پیام «جدول‌ها وجود دارند» می‌آمد.
 *
 * راه‌حل: این کنترلر فقط «صفحه‌ی پیشرفت» را رندر می‌کند؛ خودِ مایگریشن‌ها
 * یکی‌یکی با AJAX (MigrateController@step) اجرا می‌شوند — قابل توقف/ادامه (resumable).
 *
 * اتصال به روت پکیج از طریق Container Binding در AppServiceProvider انجام می‌شود.
 */
class DatabaseController extends VendorDatabaseController
{
    public function database()
    {
        return view('vendor.installer.database');
    }
}
