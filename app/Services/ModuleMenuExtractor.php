<?php

namespace App\Services;

use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * استخراج ساختار منوی پکیج‌ها برای سایدبار پنل جدید — فاز ۳۴
 * --------------------------------------------------------------------------
 * تا فاز ۳۲ سایدبار جدید پارشال منوی هر پکیج را با کانتکست apPanel=new
 *
 * @include می‌کرد؛ یعنی «خروجی مارک‌آپ» به رفتار خود پکیج وابسته بود. پکیج‌های
 * قدیمیِ نصب‌شده روی سرور مارک‌آپ قدیمی (بوت‌استرپی) می‌دادند و در سایدبار
 * جدید بی‌استایل دیده می‌شدند (گزارش کاربر + اسکرین‌شات).
 *
 * حالا سایدبار «داده» را از پکیج می‌گیرد نه مارک‌آپ را:
 *   ۱. پارشال منوی پکیج در کانتکستِ «قدیمی» رندر می‌شود (هر نسخه‌ای از پکیج —
 *      قدیمی یا دو-پنلی — دقیقاً همین مارک‌آپ پایدار را می‌دهد)
 *   ۲. همان HTML با DOMDocument پارس می‌شود:
 *      li.nav-item.has-sub → گروه (آیکون + menu-title + open_class)
 *      ul.menu-content > li → آیتم (href + menu-item + active_class)
 *   ۳. سایدبار جدید با مارک‌آپ خودش (ap-menu-link + apMenuGroup + جستجو) رندر
 *      می‌کند — «دقیقاً مثل بقیه‌ی منوها» و بدون هیچ وابستگی به نسخه‌ی پکیج.
 *
 * اگر پارس شکست خورد یا خالی بود، سایدبار به @include مستقیم همان ویو
 * برمی‌گردد (رفتار فاز ۳۲) تا هیچ منویی گم نشود.
 *
 * فاز ۳۵ — کش «بین‌درخواستی» برای سرعت پنل:
 * --------------------------------------------------------------------------
 * رندر Blade + پارس DOM هر پکیج در «هر» لود صفحه (و با wire:navigate در هر
 * ناوبری SPA!) انجام می‌شد؛ با ۱۰ پکیج نصب‌شده یعنی ۱۰ رندر + ۱۰ پارس DOM +
 * ده‌ها چک پرمیشن به‌ازای هر حرکت در پنل (گزارش کاهش سرعت کاربر).
 * حالا نتیجه با کلید «نسخه + شناسه ادمین + اثر انگشت mtime فایل‌های منو» کش
 * می‌شود؛ آپدیت/نصب پکیج mtime را عوض می‌کند → کلید جدید → کش خودکار
 * باطل می‌شود. فلگ active که به ریکوئست جاری وابسته است بعد از خواندن کش،
 * با مقایسه‌ی URL محاسبه می‌شود (هیچ داده‌ی وابسته به ریکوئست از کش استفاده
 * نمی‌شود). TTL شش ساعت — حتی اگر mtime به هر دلیلی عوض نشد.
 */
class ModuleMenuExtractor
{
    /** مموای هر ریکوئست (خواندن از کش برای هر لود صفحه یک‌بار کافی است) */
    protected static array $memo = [];

    /**
     * ساختار همه‌ی گروه‌های منوی پکیج‌های ثبت‌شده (فقط ویوهای موجود)
     *
     * @return array<int, array{view: string, label: string, icon: string, open: bool, items: array<int, array{label: string, url: string, active: bool}>}>
     */
    public static function all(): array
    {
        if (static::$memo !== []) {
            return static::$memo;
        }

        $views = [];

        foreach (ModuleMenuRegistry::all() as $view) {
            if (view()->exists($view)) {
                $views[] = $view;
            }
        }

        if ($views === []) {
            return static::$memo = [];
        }

        try {
            $adminId = auth('adminPanel')->id() ?? 0;
        } catch (Throwable) {
            $adminId = 0;
        }

        $key = 'module-menu:v3:'.$adminId.':'.static::viewsFingerprint($views);

        try {
            $groups = Cache::remember($key, now()->addHours(6), function () use ($views) {
                $result = [];

                foreach ($views as $view) {
                    foreach (static::extract($view) as $group) {
                        if ($group['items'] !== []) {
                            $result[] = $group;
                        }
                    }
                }

                return $result;
            });
        } catch (Throwable) {
            $groups = null; // کش در دسترس نبود (مثلاً store خطا داد) — مستقیم رندر کن
        }

        if (! is_array($groups)) {
            $groups = [];

            foreach ($views as $view) {
                foreach (static::extract($view) as $group) {
                    if ($group['items'] !== []) {
                        $groups[] = $group;
                    }
                }
            }
        }

        // فلگ active به ریکوئست جاری وابسته است — از کش «هرگز» استفاده نشود
        foreach ($groups as &$group) {
            foreach ($group['items'] as &$item) {
                $item['active'] = static::urlIsActive($item['url'] ?? '');
            }
            unset($item);
        }
        unset($group);

        return static::$memo = $groups;
    }

    /**
     * اثر انگشت نسخه‌ی ویوهای منو — هر تغییری در فایل‌های منوی پکیج‌ها
     * (نصب، آپدیت، حذف) mtime را عوض می‌کند و کلید کش جدید ساخته می‌شود.
     *
     * @param  array<int, string>  $views
     */
    protected static function viewsFingerprint(array $views): string
    {
        $parts = [];

        foreach ($views as $view) {
            $mtime = 0;

            try {
                $path = view($view)->getPath();

                if (is_string($path)) {
                    $mtime = @filemtime($path) ?: 0;
                }
            } catch (Throwable) {
                $mtime = -1;
            }

            $parts[] = $view.':'.$mtime;
        }

        return md5(implode('|', $parts));
    }

    /**
     * آیتم منو با URL فعلیِ ریکوئست فعال است؟ (مقایسه‌ی مسیر بدون query/fragment)
     *
     * فاز ۸۶ — درخواست کاربر: «وقتی وارد صفحه‌های افزودن یا ویرایش می‌شود باید
     * منوی همان قسمت در سایدبار فعال بماند». تا فاز ۸۵ مقایسه «عینِ مسیر» بود و
     * صفحات فرعی (campaigns/create، campaigns/5، segments/…/refresh و …) آیتم
     * فهرست را غیرفعال می‌کردند و گروه آکاردئونی بسته می‌ماند. حالا عینِ مسیر
     * «یا» زیرمجموعهٔ سگمنت‌محورِ آن فعال شمرده می‌شود:
     *   admin/x/campaigns          → عینِ مسیر ✓
     *   admin/x/campaigns/create   → زیرمجموعهٔ campaigns ✓
     *   admin/x/campaigns-suffix   → ✗ (مرز سگمنت رعایت می‌شود)
     */
    protected static function urlIsActive(string $url): bool
    {
        if ($url === '' || $url === '#') {
            return false;
        }

        $path = trim(parse_url($url, PHP_URL_PATH) ?? '', '/');

        if ($path === '') {
            return false;
        }

        $current = trim(request()->decodedPath(), '/');
        // محیط‌هایی که آدرس‌ها را با index.php سرو می‌کنند
        $current = preg_replace('#^index\.php/#', '', $current) ?? $current;

        $current = rtrim($current, '/');
        $path    = rtrim($path, '/');

        return $current === $path || str_starts_with($current.'/', $path.'/');
    }

    /**
     * رندر + پارس یک پارشال منوی پکیج — خروجی: صفر یا چند گروه
     *
     * @return array<int, array{view: string, label: string, icon: string, open: bool, items: array}>
     */
    public static function extract(string $view): array
    {
        try {
            $html = view($view, ['apPanel' => 'old'])->render();
        } catch (Throwable) {
            return []; // پکیج ناقص/غیرفعال — سایدبار fallback می‌کند
        }

        if (trim($html) === '') {
            return [];
        }

        $dom = new DOMDocument;

        libxml_use_internal_errors(true);

        try {
            // ترفند UTF-8: اعلام انکودینگ در پرولوگ (بدون mb_convert_encoding)
            $dom->loadHTML('<?xml encoding="UTF-8">'.$html, LIBXML_NOWARNING | LIBXML_NOERROR);
        } catch (Throwable) {
            return [];
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors(false);
        }

        $xpath = new DOMXPath($dom);

        $has = function (string $class, string $needle): bool {
            return in_array($needle, preg_split('/\s+/', trim($class)) ?: [], true);
        };

        $groups = [];

        // --- گروه‌های آکاردئونی (الگوی فعلی همه‌ی پکیج‌ها) ---
        foreach ($xpath->query("//li[contains(concat(' ', normalize-space(@class), ' '), ' nav-item ') and contains(concat(' ', normalize-space(@class), ' '), ' has-sub ')]") as $li) {
            /** @var DOMElement $li */
            $head = $li->getElementsByTagName('a')->item(0);

            if (! $head instanceof DOMElement) {
                continue;
            }

            $label = '';

            foreach ($head->getElementsByTagName('span') as $span) {
                if ($has($span->getAttribute('class'), 'menu-title')) {
                    $label = trim($span->textContent);
                    break;
                }
            }

            if ($label === '') {
                continue;
            }

            $iconNode = $head->getElementsByTagName('i')->item(0);

            $items = [];

            foreach ($xpath->query(".//ul[contains(concat(' ', normalize-space(@class), ' '), ' menu-content ')]/li", $li) as $itemLi) {
                /** @var DOMElement $itemLi */
                $anchor = $itemLi->getElementsByTagName('a')->item(0);

                if (! $anchor instanceof DOMElement || ! $anchor->getAttribute('href')) {
                    continue;
                }

                $itemLabel = '';

                foreach ($anchor->getElementsByTagName('span') as $span) {
                    // همه‌ی پکیج‌ها menu-item می‌دهند؛ AIWriter (قدیمی) menu-title
                    // می‌گذارد — هر دو کلاس برای «لیبل آیتم» پذیرفته می‌شود
                    if ($has($span->getAttribute('class'), 'menu-item') || $has($span->getAttribute('class'), 'menu-title')) {
                        $itemLabel = trim($span->textContent);
                        break;
                    }
                }

                if ($itemLabel === '') {
                    continue;
                }

                $items[] = [
                    'label' => $itemLabel,
                    // فاز ۳۵ — لینک‌ها تمیز می‌مانند (بدون ?ap=new)؛ پنل جدید پیش‌فرض است
                    // و کوکی ap_panel تشخیص می‌دهد (هر پنل هنگام لود ستش می‌کند)
                    'url' => static::withPanelParam($anchor->getAttribute('href')),
                    'active' => $has($itemLi->getAttribute('class'), 'active') || $has($anchor->getAttribute('class'), 'active'),
                ];
            }

            if ($items === []) {
                continue; // گروه بدون آیتم مجاز (@can همه را فیلتر کرده) — نمایش نده
            }

            $groups[] = [
                'view' => $view,
                'label' => $label,
                'icon' => $iconNode?->getAttribute('class') ?: '',
                'open' => $has($li->getAttribute('class'), 'open'),
                'items' => $items,
            ];
        }

        // --- آیتم‌های تکی بدون زیرمنو (شکل احتمالی پکیج‌های آینده) ---
        if ($groups === []) {
            foreach ($xpath->query("//li[contains(concat(' ', normalize-space(@class), ' '), ' nav-item ') and not(contains(concat(' ', normalize-space(@class), ' '), ' has-sub ')) and ./a[@href]]") as $li) {
                /** @var DOMElement $li */
                $anchor = $li->getElementsByTagName('a')->item(0);

                if (! $anchor instanceof DOMElement) {
                    continue;
                }

                $label = trim($li->getElementsByTagName('span')->item(0)?->textContent ?? $anchor->textContent);

                if ($label === '') {
                    continue;
                }

                $iconNode = $anchor->getElementsByTagName('i')->item(0);

                $url = static::withPanelParam($anchor->getAttribute('href'));
                $active = $has($li->getAttribute('class'), 'active') || $has($anchor->getAttribute('class'), 'active');

                $groups[] = [
                    'view' => $view,
                    'label' => $label,
                    'icon' => $iconNode?->getAttribute('class') ?: '',
                    'open' => $active,
                    'items' => [['label' => $label, 'url' => $url, 'active' => $active]],
                ];
            }
        }

        return $groups;
    }

    /**
     * فاز ۳۵ — لینک‌ها دیگر پارامتر ap=new نمی‌گیرند.
     * ----------------------------------------------------------------------
     * تصمیم: URLهای پنل جدید باید «تمیز» باشند؛ اگر روزی علامتی لازم شد، باید
     * به لینک‌های «پنل قدیمی» اضافه شود نه پنل جدید (پنل قدیمی روزی حذف می‌شود).
     * الان هم تشخیص کروم بدون پارامتر کار می‌کند:
     *   • مسیر admin-old/…  → قطعاً قدیمی
     *   • کوکی ap_panel     → آخرین پنل بازدیدشده (هر chrome هنگام لود ست می‌کند)
     *   • پیش‌فرض           → پنل جدید
     * متد برای سازگاری نگه داشته شده و URL را دست‌نخورده برمی‌گرداند.
     */
    protected static function withPanelParam(string $url): string
    {
        return $url;
    }
}
