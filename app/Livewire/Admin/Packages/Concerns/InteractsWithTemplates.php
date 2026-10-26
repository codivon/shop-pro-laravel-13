<?php

namespace App\Livewire\Admin\Packages\Concerns;

use App\Jobs\InstallThemeJob;
use App\Models\InstalledTheme;
use App\Models\PackagePurchase;
use App\Services\PackageApiService;
use App\Services\PackageInstallerService;
use App\Support\MarketplaceSettings;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

/*
 * منطق خرید/نصب قالب‌ها در بازار — فاز ۴۴
 *
 * قرارداد API §5.2: قالب‌ها لایسنس ندارند؛ دو نوع خرید (normal/exclusive).
 * جریان: خرید → payment_url (پولی) یا download_token (خرید مجدده/رایگان)
 * → callback (type=template) → verify → InstallThemeJob.
 */
trait InteractsWithTemplates
{
    /* نکته: متد api() از تریت InteractsWithPackages روی همان کامپوننت می‌آید. */

    /**
     * آدرس callback پرداخت قالب — همان روت پکیج‌ها با type=template.
     */
    protected function templateCallbackUrl(): string
    {
        if (\Illuminate\Support\Facades\Route::has('np.packages.payment.callback')) {
            return route('np.packages.payment.callback', ['type' => 'template']);
        }

        return route(config('packages.payment.callback_route'), ['type' => 'template']);
    }

    /**
     * شروع خرید قالب — از مودال جزئیات (purchaseType: normal|exclusive).
     *  - خرید مجددهٔ normal → پاسخ همان لحظه download_token می‌دهد → نصب فوری
     *  - خرید پولی → ذخیرهٔ رکورد + ریدایرکت به درگاه
     */
    public function startTemplatePurchase(): void
    {
        auth('adminPanel')->user()?->can('packages.index') ?: abort(403);

        if (empty($this->tDetail['slug'])) {
            return;
        }

        /*
         * فاز ۹۹ — گیت تنظیمات بازارچه (گارد سروری): خرید قالب به نصب منتهی می‌شود
         * ⇒ زیرمجموعهٔ کلید allow_install (و کلید اصلی).
         */
        if (! MarketplaceSettings::marketEnabled() || ! MarketplaceSettings::allowInstall()) {
            $this->dispatch('toast', message: MarketplaceSettings::installDisabledMessage(), type: 'error');

            return;
        }

        $slug = $this->tDetail['slug'];

        /*
         * فاز ۶۶ — قالب رایگان همیشه «خرید عادی» است: سرور نباید اجازه دهد
         * حالت انتخاب‌شدهٔ exclusive روی قالب رایگان اعمال شود (UI هم بخش
         * انتخاب نوع خرید را برای قالب رایگان اصلاً نشان نمی‌دهد).
         */
        $tIsFree = ! empty($this->tDetail['is_free'])
            || (int) ($this->tDetail['discount_price'] ?? $this->tDetail['price'] ?? 0) === 0;
        $purchaseType = (! $tIsFree && $this->tDetailPurchaseType === 'exclusive') ? 'exclusive' : 'normal';

        // در حال نصب؟
        if (InstalledTheme::where('slug', $slug)->where('status', InstalledTheme::STATUS_UPDATING)->exists()) {
            $this->dispatch('toast', message: 'نصب این قالب همچنان در جریان است.', type: 'error');

            return;
        }

        /*
         * فاز ۶۴ — قفل نصب همزمان: اگر نصب دیگری (پکیج/قالب) در جریان است،
         * خریدِ جدید که به نصب منتهی می‌شود شروع نشود.
         */
        if ($this->hasOtherInstallationInProgress($slug)) {
            $this->dispatch('toast', message: $this->installationLockMessage(), type: 'error');

            return;
        }

        // هشدار اختصاصی — تأیید صریح کاربر لازم است
        if ($purchaseType === 'exclusive' && ! $this->tExclusiveConfirmed) {
            $this->tExclusiveConfirmed = true;

            return;
        }

        try {
            $purchase = $this->api()->createTemplatePurchase(
                $slug,
                $purchaseType,
                $this->templateCallbackUrl(),
                auth('adminPanel')->id()
            );

            // خرید مجدد normal / قالب رایگان → توکن دانلود مستقیم
            if (! empty($purchase['download_token']) || ($purchase['is_free'] ?? false)) {
                $this->dispatchTemplateInstall($slug, $purchase, $purchaseType);

                $this->closeTemplateDetail();

                return;
            }

            if (empty($purchase['payment_url'])) {
                $this->dispatch('toast', message: 'درخواست خرید ثبت شد اما آدرس درگاه پرداخت دریافت نشد.', type: 'error');

                return;
            }

            // خرید پولی — رکورد خرید با type=template (برای callback)
            PackagePurchase::create([
                'type'          => 'template',
                'admin_id'      => auth('adminPanel')->id(),
                'package_slug'  => $slug,
                'package_name'  => $this->tDetail['name'] ?? $slug,
                'version'       => (string) ($this->tDetail['latest_version']['version'] ?? ''),
                'amount'        => $purchase['amount'] ?? 0,
                'gateway'       => $purchase['gateway'] ?? null,
                'transaction_id'=> $purchase['transaction_id'] ?? null,
                'payment_url'   => $purchase['payment_url'] ?? null,
                'status'        => PackagePurchase::STATUS_PENDING,
                'meta'          => array_merge($purchase, ['purchase_type' => $purchaseType]),
            ]);

            $this->closeTemplateDetail();
            $this->redirect($purchase['payment_url']);
        } catch (RuntimeException $e) {
            $this->dispatch('toast', message: $e->getMessage(), type: 'error');
        } catch (Throwable $e) {
            Log::error('Template purchase failed', ['slug' => $slug, 'error' => $e->getMessage()]);
            $this->dispatch('toast', message: 'خطا در ارتباط با سرور قالب‌ها.', type: 'error');
        }
    }

    /** صف‌کردن نصب قالب با توکن دانلود (خرید رایگان/مجدد) */
    protected function dispatchTemplateInstall(string $slug, array $purchase, string $purchaseType): void
    {
        $version = $this->tDetail['latest_version']['version'] ?? $purchase['version'] ?? null;

        InstalledTheme::markPending($slug, $this->tDetail['name'] ?? $slug, $version, $purchaseType, auth('adminPanel')->id());

        InstallThemeJob::dispatch(
            $slug,
            $purchase['download_token'] ?? null,
            $purchase['signature'] ?? null,
            $purchaseType,
            auth('adminPanel')->id(),
            null
        );

        $this->dispatch('toast', message: 'نصب قالب آغاز شد — تا پایان، وضعیت «در حال نصب» نمایش داده می‌شود.', type: 'success');

        // فاز ۶۴ — مودال پیشرفت بلوکه‌شده برای نصب قالب هم باز می‌شود (الگوی afterInstallQueued)
        if (method_exists($this, 'refreshAfterTemplateInstall')) {
            $this->refreshAfterTemplateInstall($slug);
        }
    }

    /* ---------------- مودال جزئیات قالب ---------------- */

    /**
     * فاز ۶۷ — استخراج خودِ قالب از هر شکلِ پاسخِ ممکن:
     * {data:{...}} | {template:{...}} | {result:{...}} | {...} | wrapper دوتایی
     * بازگشت null = پاسخ قابل استفاده نیست (بدون slug/name).
     */
    protected function resolveTemplatePayload(mixed $data): ?array
    {
        $node = $data;

        for ($depth = 0; $depth < 3; $depth++) {
            if (! is_array($node)) {
                return null;
            }

            if (array_key_exists('slug', $node) || array_key_exists('name', $node)) {
                return $node;
            }

            $next = $node['data'] ?? $node['template'] ?? $node['result'] ?? null;

            if (! is_array($next)) {
                return null;
            }

            $node = $next;
        }

        return null;
    }

    /* ---------------- نرمال‌سازی فیلدها (فاز ۶۹) ---------------- */

    /**
     * فاز ۶۹ — خطای لایو کاربر: «htmlspecialchars(): Argument #1 ($string) must
     * be of type string, array given». API واقعی برخی فیلدها را آبجکت/آرایه می‌دهد
     * (مثلاً author: {name}, category: {title}, thumbnail_url: {url} یا description
     * به شکل آرایهٔ پاراگراف‌ها) در حالی که ویو آن‌ها را رشته رندر می‌کند.
     * همهٔ فیلدهای مصرفی مودال اینجا به شکل امن نرمال می‌شوند تا رندر هرگز نترکد.
     */
    protected function normalizeTemplateDetail(array $template): array
    {
        // متن‌های سادهٔ نمایشی — آبجکت‌ها با کلید نام/عنوان به رشته تبدیل می‌شوند
        foreach (['name', 'slug', 'author', 'category', 'version'] as $key) {
            if (array_key_exists($key, $template)) {
                $template[$key] = $this->templateScalar($template[$key], ['name', 'title', 'label']);
            }
        }

        // تصویر شاخص و فیلدهای تصویری — رشته | {url|src|path|...}
        foreach (['thumbnail_url', 'thumbnail', 'cover', 'preview_image', 'image'] as $key) {
            if (array_key_exists($key, $template)) {
                $template[$key] = $this->templateScalar($template[$key], ['url', 'src', 'path', 'large', 'original']);
            }
        }

        // متن‌های HTML — رشته | {'html': ...} | آرایهٔ پاراگراف‌ها
        foreach (['short_description', 'long_description', 'description', 'content'] as $key) {
            if (array_key_exists($key, $template)) {
                $template[$key] = $this->templateRichText($template[$key]);
            }
        }

        // فیلدهای عددی/بولی — مقدار غیراسکالر → null تا ویو fallback خودش را بگیرد
        foreach (['price', 'discount_price', 'exclusive_price', 'min_price', 'downloads', 'purchases_count', 'is_free', 'is_purchased', 'is_sold_exclusively'] as $key) {
            if (array_key_exists($key, $template) && ! is_scalar($template[$key])) {
                $template[$key] = null;
            }
        }

        // گالری — هر فیلد ممکن + هر شکل آیتم → لیست URL رشته‌ای
        $template['gallery'] = $this->templateGalleryUrls($template);

        return $template;
    }

    /** تبدیل امن هر مقدار به رشتهٔ نمایشی (آبجکت با کلید شناخته‌شده یا اولین رشته) */
    protected function templateScalar(mixed $value, array $keys = ['name', 'title', 'label']): ?string
    {
        if ($value === null || (is_array($value) && $value === [])) {
            return null;
        }

        if (is_scalar($value)) {
            $v = trim((string) $value);

            return $v === '' ? null : $v;
        }

        if (is_array($value)) {
            foreach ($keys as $key) {
                if (isset($value[$key]) && is_string($value[$key]) && trim($value[$key]) !== '') {
                    return trim($value[$key]);
                }
            }

            foreach ($value as $item) {
                if (is_string($item) && trim($item) !== '') {
                    return trim($item);
                }
            }
        }

        return null;
    }

    /** تبدیل امن متن HTML — رشته | {'html': ...} | آرایهٔ پاراگراف‌ها */
    protected function templateRichText(mixed $value): ?string
    {
        if (is_string($value)) {
            return $value !== '' ? $value : null;
        }

        if (is_array($value)) {
            foreach (['html', 'content', 'body', 'text', 'description', 'long_description', 'short_description'] as $key) {
                if (isset($value[$key]) && is_string($value[$key]) && trim($value[$key]) !== '') {
                    return $value[$key];
                }
            }

            $strings = array_values(array_filter($value, 'is_string'));

            if ($strings !== []) {
                return implode("\n", $strings);
            }
        }

        return null;
    }

    /** گالری — هر فیلد ممکن + هر شکل آیتم (رشته | {url|src|path|...}) → لیست URL */
    protected function templateGalleryUrls(array $template): array
    {
        $urls = [];

        foreach (['gallery', 'images', 'screenshots', 'preview_images', 'previews', 'media'] as $key) {
            $value = $template[$key] ?? null;

            if (empty($value)) {
                continue;
            }

            foreach ((array) $value as $item) {
                if (is_string($item) && trim($item) !== '') {
                    $urls[] = trim($item);
                } elseif (is_array($item)) {
                    $found = $this->templateScalar($item, ['url', 'src', 'path', 'full', 'large', 'original', 'image']);

                    if ($found !== null) {
                        $urls[] = $found;
                    }
                }
            }

            if ($urls !== []) {
                break;
            }
        }

        return array_values(array_unique($urls));
    }

    public function openTemplateDetail(string $slug): void
    {
        auth('adminPanel')->user()?->can('packages.index') ?: abort(403);

        $this->tDetailOpen = true;
        $this->tDetailLoading = true;
        $this->tDetailError = null;
        $this->tDetail = null;
        $this->tDetailSlug = $slug;
        $this->tExclusiveConfirmed = false;
        $this->tDetailPurchaseType = 'normal';

        $cacheKey = 'templates.detail.'.$slug;

        try {
            $data = \Illuminate\Support\Facades\Cache::remember(
                $cacheKey,
                now()->addMinutes(config('packages.cache.detail_ttl', 10)),
                fn () => $this->api()->getTemplate($slug)
            );

            $template = $this->resolveTemplatePayload($data);

            if ($template === null) {
                /*
                 * فاز ۶۷ — پاسخ بی‌داده هرگز کش نمی‌ماند (وگرنه تا انقضای TTL
                 * همهٔ کلیک‌ها مودال خالی می‌دهند) + خطای واضح با دکمهٔ تلاش دوباره.
                 */
                \Illuminate\Support\Facades\Cache::forget($cacheKey);
                \Illuminate\Support\Facades\Log::warning('Template detail payload unusable', [
                    'slug' => $slug,
                    'type' => gettype($data),
                    'keys' => is_array($data) ? array_slice(array_keys($data), 0, 15) : null,
                ]);
                $this->tDetailError = 'پاسخ سرور قالب‌ها ناقص بود (دادهٔ قالب برگردانده نشد). دوباره تلاش کنید؛ اگر تکرار شد وضعیت اتصال به سرور بازار را بررسی کنید.';

                return;
            }

            // فاز ۶۹ — نرمال‌سازی فیلدها (author/category/تصاویر/متن‌ها ممکن است آبجکت باشند)
            $template = $this->normalizeTemplateDetail($template);

            // وضعیت نصب محلی (فقط روی آرایهٔ معتبر — بدون auto-vivification)
            $installed = InstalledTheme::where('slug', $slug)->first();
            $template['installed'] = $installed ? [
                'name'    => $installed->name,
                'version' => $installed->version,
                'status'  => $installed->status,
                'is_active' => $installed->isActive(),
            ] : null;

            $latestVersion = $template['latest_version']['version']
                ?? collect($template['active_versions'] ?? [])->first()['version']
                ?? null;

            $template['latestVersion'] = $latestVersion;
            $template['has_update'] = $installed && $latestVersion
                && version_compare((string) $latestVersion, (string) $installed->version, '>');

            $this->tDetail = $template;
        } catch (RuntimeException $e) {
            \Illuminate\Support\Facades\Cache::forget($cacheKey);
            $this->tDetailError = $e->getMessage();
        } catch (Throwable $e) {
            \Illuminate\Support\Facades\Cache::forget($cacheKey);
            report($e);
            $this->tDetailError = 'خطا در دریافت اطلاعات قالب.';
        } finally {
            $this->tDetailLoading = false;
        }
    }

    public function closeTemplateDetail(): void
    {
        $this->tDetailOpen = false;
        $this->tExclusiveConfirmed = false;
    }

    public function setTemplatePurchaseType(string $type): void
    {
        $this->tDetailPurchaseType = $type === 'exclusive' ? 'exclusive' : 'normal';
        $this->tExclusiveConfirmed = false;
    }

    /* ---------------- فعال‌سازی / حذف / نصب مجدد ---------------- */

    /** فعال‌سازی قالب نصب‌شده از کارت/مودال بازار */
    public function activateTemplate(string $slug): void
    {
        auth('adminPanel')->user()?->can('themes.update') ?: abort(403);

        $installed = InstalledTheme::where('slug', $slug)->first();

        if (! $installed) {
            $this->dispatch('toast', message: 'این قالب هنوز نصب نشده است.', type: 'error');

            return;
        }

        try {
            app(PackageInstallerService::class)->activateTheme($installed->name);

            $this->dispatch('toast', message: 'قالب «'.$installed->name.'» به عنوان قالب فعال تنظیم شد.', type: 'success');
            $this->loadTemplates();
        } catch (Throwable $e) {
            $this->dispatch('toast', message: $e->getMessage(), type: 'error');
        }
    }

    /** نصب مجدد قالب خریداری‌شده (خرید مجددهٔ normal بدون پرداخت انجام می‌شود) */
    public function reinstallTemplate(string $slug): void
    {
        auth('adminPanel')->user()?->can('packages.index') ?: abort(403);

        // فاز ۹۹ — گیت نصب: نصب مجدد قالب هم دانلود از سرور بازار است
        if (! MarketplaceSettings::marketEnabled() || ! MarketplaceSettings::allowInstall()) {
            $this->dispatch('toast', message: MarketplaceSettings::installDisabledMessage(), type: 'error');

            return;
        }

        if (InstalledTheme::where('slug', $slug)->where('status', InstalledTheme::STATUS_UPDATING)->exists()) {
            $this->dispatch('toast', message: 'نصب این قالب همچنان در جریان است.', type: 'error');

            return;
        }

        // فاز ۶۴ — قفل نصب همزمان: اگر نصب دیگری (پکیج/قالب) در جریان است، شروع نشود
        if ($this->hasOtherInstallationInProgress($slug)) {
            $this->dispatch('toast', message: $this->installationLockMessage(), type: 'error');

            return;
        }

        try {
            // برای خریدِ مجدده، endpoint خرید مستقیم download_token برمی‌گرداند
            $purchase = $this->api()->createTemplatePurchase(
                $slug,
                'normal',
                $this->templateCallbackUrl(),
                auth('adminPanel')->id()
            );

            if (empty($purchase['download_token'])) {
                $this->dispatch('toast', message: $purchase['error'] ?? 'دریافت توکن دانلود ناموفق بود؛ این قالب ممکن است اختصاصی خریداری شده باشد.', type: 'error');

                return;
            }

            $this->tDetail = ['slug' => $slug, 'name' => InstalledTheme::where('slug', $slug)->value('name') ?: $slug, 'latest_version' => null];
            $this->dispatchTemplateInstall($slug, $purchase, 'normal');
        } catch (RuntimeException $e) {
            $this->dispatch('toast', message: $e->getMessage(), type: 'error');
        } catch (Throwable $e) {
            Log::error('Template reinstall failed', ['slug' => $slug, 'error' => $e->getMessage()]);
            $this->dispatch('toast', message: 'خطا در ارتباط با سرور قالب‌ها.', type: 'error');
        }
    }

    /** حذف قالب (غیرفعال) از بازار */
    public function uninstallTemplate(string $slug): void
    {
        auth('adminPanel')->user()?->can('themes.delete') ?: abort(403);

        // فاز ۹۹ — گیت حذف (گارد سروری، نه فقط UI)
        if (! MarketplaceSettings::marketEnabled() || ! MarketplaceSettings::allowUninstall()) {
            $this->dispatch('toast', message: MarketplaceSettings::uninstallDisabledMessage(), type: 'error');

            return;
        }

        try {
            app(PackageInstallerService::class)->uninstallTheme($slug);

            $this->dispatch('toast', message: 'قالب با موفقیت حذف شد.', type: 'success');
            $this->loadTemplates();
        } catch (Throwable $e) {
            $this->dispatch('toast', message: $e->getMessage(), type: 'error');
        }
    }
}
