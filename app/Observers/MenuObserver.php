<?php

namespace App\Observers;

use App\Models\Menu;
use Illuminate\Support\Facades\Cache;

/**
 * فاز ۱۱۷ — باطل‌سازی کش درخت منوی فرانت.
 *
 * قبلاً «front.menu.data» (کش ۵ دقیقه‌ای کامپوزر تم) هیچ‌وقت باطل نمی‌شد؛
 * ادمین منو می‌ساخت/ویرایش می‌کرد ولی فرانت تا ۵ دقیقه همان درخت قبلی را
 * نشان می‌داد («منوهایی که انتخاب می‌کنم را نمی‌آورد»).
 * این آبزروبر همهٔ مسیرهای تغییر منو (پنل جدید لایووایر، کنترلر قدیمی، سیدر) را پوشش می‌دهد.
 */
class MenuObserver
{
    public function saved(Menu $menu): void
    {
        $this->forget();
    }

    public function deleted(Menu $menu): void
    {
        $this->forget();
    }

    protected function forget(): void
    {
        Cache::forget('front.menu.data');
    }
}
