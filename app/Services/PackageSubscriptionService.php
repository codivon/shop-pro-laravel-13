<?php

namespace App\Services;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * فاز ۱۵۰ — حل‌کنندهٔ پوشش اشتراک برای پکیج‌های نصب‌شده.
 *
 * گزارش کاربر: پکیج‌هایی که با اشتراک فعال نصب شده‌اند، با انقضای لایسنسِ تکی
 * (license_expires_at محلی) در صفحهٔ نصب‌شده‌ها «منقضی شده» نشان داده می‌شدند؛
 * در حالی که تا پایان اشتراکِ پوشش‌دهنده باید معتبر بمانند. پکیجِ تک‌خرید‌شده
 * هم باید فقط با لایسنس خودش قضاوت شود — منبعِ حقیقتِ «این پکیج در اشتراک هست
 * یا نه» سرور بازار است (فیلد subscription در GET /api/v1/packages/{slug}).
 *
 * قرارداد کش: پاسخ خام subscription هر اسلاگ ۳۰ دقیقه کش می‌شود (شامل پاسخ
 * منفی «در اشتراک نیست» تا هر لود صفحه N بار زنگ API نزند). خطای ارتباط کش
 * نمی‌شود تا بار بعدی دوباره تلاش کند و محلی به رفتار قدیمی (فقط لایسنس) برمی‌گردیم.
 *
 * فاز ۱۵۱ — گزارش کاربر: بج اشتراک باید برای «همهٔ» پکیج‌های داخل اشتراک نمایش
 * داده شود نه فقط منقضی‌های محلی ⇒ coverageMap دسته‌ای برای کل صفحهٔ جاری با
 * «یک» درخواست listPackages (per_page=100 و پیگیری صفحات تا سقف ۱۰) به‌جای
 * N فراخوانی تکی؛ نتیجه در همان کلیدهای کشِ هر اسلاگ نوشته می‌شود تا گیت
 * آپدیت و رندر صفحه از یک منبع مشترک بخوانند.
 */
class PackageSubscriptionService
{
    /** تایم‌اوت کوتاه مثل verifyLicense — فراخوانی درون‌صفحه‌ای است */
    private const API_TIMEOUT = 6;

    private const CACHE_TTL_MINUTES = 30;

    public function __construct(private PackageApiService $api) {}

    /**
     * آبجکت خام subscription بازار برای یک پکیج (یا null = در اشتراک نیست/داده نامشخص)
     * @return array<string,mixed>|null
     */
    public function subscriptionFor(string $slug): ?array
    {
        $slug = trim($slug);

        if ($slug === '') {
            return null;
        }

        $key = 'packages.subscription.'.md5($slug);

        $cached = Cache::get($key);

        if (is_array($cached)) {
            // نشانگر منفیِ «در اشتراک نیست» هم آرایه است ولی subscription واقعی نیست
            return $cached['subscription'] ?? null;
        }

        try {
            $package = $this->api->getPackage($slug, self::API_TIMEOUT);
            $data    = $package['data'] ?? $package;
            $sub     = $data['subscription'] ?? null;

            if (is_array($sub) && $sub !== []) {
                Cache::put($key, ['subscription' => $sub], now()->addMinutes(self::CACHE_TTL_MINUTES));

                return $sub;
            }

            // پاسخ منفی هم کش می‌شود تا هر لود صفحه زنگ نزند
            Cache::put($key, ['none' => true], now()->addMinutes(self::CACHE_TTL_MINUTES));

            return null;
        } catch (Throwable) {
            // خطای ارتباط — بدون کش منفی؛ دفعهٔ بعد دوباره تلاش می‌شود و تا آن‌موقع
            // رفتار محلی (فقط لایسنس) حفظ می‌شود.
            return null;
        }
    }

    /**
     * اگر اشتراکِ فعالِ بازار این پکیج را پوشش می‌دهد، تاریخِ پایانِ آن پوشش را
     * برمی‌گرداند؛ در غیر این صورت null — معنایی: «تا این تاریخ منقضی نشمار».
     */
    public function coverageUntil(string $slug): ?CarbonInterface
    {
        return $this->coverageFromSubscription($this->subscriptionFor($slug));
    }

    /**
     * فاز ۱۵۱ — نقشهٔ پوشش اشتراک برای دسته‌ای از اسلاگ‌ها (رندر صفحهٔ نصب‌شده‌ها).
     *
     * اول از کشِ هر اسلاگ می‌خواند؛ برای گم‌شده‌ها «یک» listPackages می‌زند
     * (per_page=100، پیگیری صفحات تا سقف ۱۰ برای پوشش کامل بازار) و بلوک
     * subscription هر پکیج را در همان کلیدهای کش تکی می‌نویسد — یعنی فراخوانی
     * بعدیِ coverageUntil در گیت آپدیت هم بدون تماس شبکه جواب می‌گیرد.
     * اسلاگ‌هایی که در هیچ صفحهٔ لیست پیدا نشوند، پاسخ منفی کش می‌شوند (همان
     * قرارداد subscriptionFor). خطای ارتباط ⇒ بدون کش، اسلاگ‌های گم‌شده null
     * (قضاوت محلی مثل قبل).
     *
     * @param  array<int, string>  $slugs
     * @return array<string, ?CarbonInterface>  slug => تاریخ پایان پوشش (یا null)
     */
    public function coverageMap(array $slugs): array
    {
        $slugs = array_values(array_unique(array_filter(
            array_map(fn ($s) => trim((string) $s), $slugs),
            fn ($s) => $s !== ''
        )));

        if ($slugs === []) {
            return [];
        }

        $map     = [];
        $missing = [];

        foreach ($slugs as $slug) {
            $cached = Cache::get('packages.subscription.'.md5($slug));

            if (is_array($cached)) {
                $map[$slug] = $this->coverageFromSubscription($cached['subscription'] ?? null);
            } else {
                $missing[] = $slug;
            }
        }

        if ($missing !== []) {
            try {
                $resolved = $this->fetchSubscriptionsFromList($missing);

                foreach ($missing as $slug) {
                    $sub = $resolved[$slug] ?? null;

                    Cache::put(
                        'packages.subscription.'.md5($slug),
                        $sub ? ['subscription' => $sub] : ['none' => true],
                        now()->addMinutes(self::CACHE_TTL_MINUTES)
                    );

                    $map[$slug] = $this->coverageFromSubscription($sub);
                }
            } catch (Throwable) {
                // خطای ارتباط — بدون کش؛ اسلاگ‌های گم‌شده null ⇒ قضاوت محلی مثل قبل
                foreach ($missing as $slug) {
                    $map[$slug] ??= null;
                }
            }
        }

        return $map;
    }

    /**
     * هستهٔ مشترک قضاوت پوشش — از بلوک خام subscription بازار.
     *
     * @param  array<string,mixed>|null  $sub
     */
    private function coverageFromSubscription(?array $sub): ?CarbonInterface
    {
        if (! $sub || empty($sub['is_free_with_subscription'])) {
            return null;
        }

        $expiresAt = $sub['subscription_expires_at'] ?? null;

        if (empty($expiresAt)) {
            // اشتراک بدون تاریخ انقضا = پوشش نامحدود تا هر زمان
            return Carbon::now()->addYears(100);
        }

        try {
            $coverage = Carbon::parse($expiresAt);
        } catch (Throwable) {
            return null;
        }

        return $coverage->isFuture() ? $coverage : null;
    }

    /**
     * واکشی دسته‌ای بلوک subscription از لیست پکیج‌های بازار — فقط اسلاگ‌های
     * خواسته‌شده. صفحات ۱۰۰تایی تا سقف ۱۰ صفحه دنبال می‌شوند؛ در عمل بازار
     * کوچک‌تر از یک صفحه است ⇒ یک درخواست.
     *
     * @param  array<int, string>  $slugs
     * @return array<string, array<string,mixed>>  slug => بلوک subscription
     */
    private function fetchSubscriptionsFromList(array $slugs): array
    {
        $resolved  = [];
        $remaining = array_fill_keys($slugs, true);

        for ($page = 1; $page <= 10 && $remaining !== []; $page++) {
            $data  = $this->api->listPackages(['per_page' => 100, 'page' => $page]);
            $items = $data['data'] ?? $data['packages'] ?? [];

            if (! is_array($items) || $items === []) {
                break;
            }

            foreach ($items as $pkg) {
                if (! is_array($pkg)) {
                    continue;
                }

                $slug = trim((string) ($pkg['slug'] ?? ''));

                if ($slug === '' || ! isset($remaining[$slug])) {
                    continue;
                }

                $sub = $pkg['subscription'] ?? null;

                $resolved[$slug] = is_array($sub) && $sub !== [] ? $sub : null;
                unset($remaining[$slug]);
            }

            /*
             * ادامه صفحه؟ اگر meta پاسخ last_page/lastPage دارد معیار همان است؛
             * وگرنه heuristic «صفحهٔ ناقص». (سرور بازار ممکن است per_page را
             * سقف‌گذاری کند — count(items) < 100 به‌تنهایی قابل اتکا نیست)
             */
            $meta     = $data['meta'] ?? $data['pagination'] ?? [];
            $meta     = is_array($meta) ? $meta : [];
            $lastPage = (int) ($meta['last_page'] ?? $meta['lastPage'] ?? 0);
            $hasMore  = $lastPage > 0 ? $page < $lastPage : count($items) >= 100;

            if (! $hasMore) {
                break;
            }
        }

        return $resolved;
    }
}
