<?php

namespace App\Http\Controllers\Install;

use Illuminate\Routing\Controller;
use RachidLaasri\LaravelInstaller\Events\LaravelInstallerFinished;
use RachidLaasri\LaravelInstaller\Helpers\EnvironmentManager;
use RachidLaasri\LaravelInstaller\Helpers\InstalledFileManager;

/**
 * فاز ۲۶ — کنترلر مرحله‌ی «نصب کامل شد» — جایگزین FinalController پکیج نصب‌کننده.
 *
 * چرا جایگزین شد؟ نسخه‌ی پکیج در runFinal() روی «هر» بازدید از /install/final
 * دستور «key:generate --force» را اجرا می‌کرد؛ یعنی APP_KEY فروشگاه بعد از
 * هر بازدید عوض می‌شد و پیامد آن:
 *   - همه‌ی کوکی‌های رمزشده (سشن/CSRF) قبلی نامعتبر می‌شدند (خروج اجباری همه)؛
 *   - هش اختصاصی endpointهای Livewire (که از APP_KEY ساخته می‌شود) تغییر می‌کرد
 *     و چون route:cache در همان لحظه هش قبلی را «فریز» کرده بود، فایل
 *     livewire.min.js و آپدیت‌های Livewire 404 می‌شدند و کل پنل از کار می‌افتاد.
 *
 * APP_KEY از قبل (wizard / bootstrap) تولید و در .env ذخیره شده است؛ اینجا
 * فقط فایل نصب‌شده ثبت، رویداد آماده‌سازی نهایی ارسال و ویو نمایش داده می‌شود.
 */
class FinalController extends Controller
{
    public function finish(InstalledFileManager $fileManager, EnvironmentManager $environment)
    {
        $finalMessages = 'نصب با موفقیت انجام شد.';
        $finalStatusMessage = $fileManager->update();
        $finalEnvFile = $environment->getEnvContent();

        event(new LaravelInstallerFinished);

        return view('vendor.installer.finished', compact('finalMessages', 'finalStatusMessage', 'finalEnvFile'));
    }
}
