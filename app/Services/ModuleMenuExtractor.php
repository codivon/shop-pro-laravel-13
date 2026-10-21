<?php

namespace App\Services;

use DOMDocument;
use DOMElement;
use DOMXPath;
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
 */
class ModuleMenuExtractor
{
    /** مموای هر ریکوئست (پارس DOM برای هر لود صفحه یک‌بار کافی است) */
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

        $result = [];

        foreach (ModuleMenuRegistry::all() as $view) {
            if (! view()->exists($view)) {
                continue;
            }

            foreach (static::extract($view) as $group) {
                if ($group['items'] !== []) {
                    $result[] = $group;
                }
            }
        }

        return static::$memo = $result;
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
                    // لینک‌های سایدبار جدید پارامتر ap=new می‌گیرند تا کروم صفحه
                    // حتی با دو تبِ همزمانِ دو پنل، قطعی و درست باشد (فاز ۳۴)
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

    /** افزودن ap=new به URL (بدون خراب‌کردن query و fragment موجود) */
    protected static function withPanelParam(string $url): string
    {
        if ($url === '' || $url === '#') {
            return $url;
        }

        $parts = parse_url($url);

        if ($parts === false) {
            return $url;
        }

        parse_str($parts['query'] ?? '', $query);

        if (($query['ap'] ?? null) === 'new') {
            return $url;
        }

        $query['ap'] = 'new';

        $rebuilt = $parts['path'] ?? '';

        $queryString = http_build_query($query);

        if ($queryString !== '') {
            $rebuilt .= '?'.$queryString;
        }

        if (isset($parts['fragment'])) {
            $rebuilt .= '#'.$parts['fragment'];
        }

        if (isset($parts['scheme'])) {
            // پورت هم باید حفظ شود (localhost با پورت سفارشی / استیجینگ)
            $rebuilt = $parts['scheme'].'://'.($parts['host'] ?? '')
                .(isset($parts['port']) ? ':'.$parts['port'] : '')
                .$rebuilt;
        }

        return $rebuilt;
    }
}
