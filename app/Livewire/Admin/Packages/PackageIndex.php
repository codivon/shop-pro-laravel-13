<?php

namespace App\Livewire\Admin\Packages;

use App\Jobs\UpdatePackageJob;
use App\Livewire\Admin\Packages\Concerns\InteractsWithPackages;
use App\Models\InstalledModule;
use App\Models\ModuleInstallLog;
use App\Models\PackageCache;
use App\Models\PackagePurchase;
use App\Services\PackageInstallerService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use RuntimeException;

#[Title('بازار پکیج‌ها')]
class PackageIndex extends Component
{
    use InteractsWithPackages;

    /* ---------------- فیلترها (State در URL — پورت query های index قدیمی) ---------------- */

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(as: 'category', except: '')]
    public string $category = '';

    /** گزینه‌های دسته‌بندی مثل قدیمی (index.blade.php قدیمی) */
    public static array $categoryOptions = [
        'shop' => 'فروشگاه',
        'payment' => 'پرداخت',
        'notification' => 'اعلان',
        'seo' => 'سئو',
    ];

    #[Url(as: 'sort', except: '')]
    public string $sort = '';

    /**
     * فیلتر وضعیت قیمت — رایگان بودن (درخواست کاربر):
     *  '' = همه | 'free' = رایگان | 'paid' = پولی
     * API مرکزی این پارامتر را نمی‌شناسد، پس فیلترینگ سمت پنل انجام می‌شود
     * (همه‌ی صفحات API جمع‌ شده و بر اساس وضعیت قیمت هر پکیج فیلتر می‌شود).
     */
    #[Url(as: 'free', except: '')]
    public string $free = '';

    /** گزینه‌های فیلتر رایگان/پولی */
    public static array $freeOptions = [
        'free' => 'رایگان',
        'paid' => 'پولی',
    ];

    /** صفحه‌بندی سرور دست‌ساز بر اساس meta پاسخ API (قدیمی pagination سمت API بود) */
    public int $page = 1;

    /** حداکثر صفحاتی که در حالت فیلتر رایگان/پولی از API جمع می‌شود (ضد حلقه‌ی بی‌پایان) */
    protected const MAX_AGGREGATE_PAGES = 25;

    /* ---------------- دیتای صفحه ---------------- */

    public array $packages = [];

    public array $paginationMeta = [];

    /**
     * آمار رایگان/پولی برای کارت‌های آماری — همیشه «بازار» را توصیف می‌کند نه برشِ فعلی:
     * در حالت عادی = همان صفحه‌ی جاری (با per_page=12 کل بازار را پوشش می‌دهد)
     * در حالت فیلتر رایگان/پولی = کل نتایج تجمیع‌شده‌ی همه صفحات API
     * (قبل از اعمال فیلتر قیمت) تا با کلیک روی فیلترها، عددها نپَرند.
     */
    public array $priceCounts = ['free' => 0, 'paid' => 0, 'total' => 0];

    public array $installedMap = [];

    public array $purchasedSlugs = [];

    public array $pendingSlugs = [];

    public ?array $subscriptionSummary = null;

    public ?string $apiError = null;

    /** نتیجه‌ی callback پرداخت (session payment_result قدیمی → مودال) */
    public ?array $paymentResult = null;

    /** نتیجه بررسی آپدیت‌ها (پورت checkUpdates قدیمی) */
    public array $updateResults = [];

    /* ---------------- فاز ۲۶ — مودال جزئیات پکیج (جایگزین تک‌صفحه packages/{slug}) ---------------- */

    public bool $detailOpen = false;

    public bool $detailLoading = false;

    public ?string $detailError = null;

    /** داده‌ی پکیج + enrichهای نصب (پورت renderDetail در modal.js قدیمی) */
    public ?array $detail = null;

    public array $detailLogs = [];

    /** paid | license | subscription — نتیجه‌ی checkPurchaseState */
    public string $detailMode = 'paid';

    public ?string $detailWarning = null;

    /** پلن انتخاب‌شده داخل مودال (پیش‌فرض: ارزان‌ترین مثل قدیمی) */
    public ?int $detailPlanId = null;

    /** تأیید حذف داخل مودال */
    public bool $detailConfirmingUninstall = false;

    /* ---------------- فاز ۲۶ — مودال نوار پیشرفت نصب (پورت progress.js قدیمی) ---------------- */

    public ?string $progressSlug = null;

    public ?string $progressName = null;

    /** وضعیت لحظه‌ای نصب: percent/step/status/message/error */
    public array $progressState = [];

    /** مراحل نمایش داده‌شده در نوار پیشرفت (پورت steps آرایه‌ی progress.js قدیمی) */
    public static array $progressSteps = [
        ['key' => 'download',  'label' => 'دانلود',       'icon' => 'download'],
        ['key' => 'verify',    'label' => 'تأیید',        'icon' => 'shield'],
        ['key' => 'extract',   'label' => 'استخراج',      'icon' => 'archive'],
        ['key' => 'migrate',   'label' => 'دیتابیس',      'icon' => 'database'],
        ['key' => 'publish',   'label' => 'انتشار',       'icon' => 'cloud-upload'],
        ['key' => 'finalize',  'label' => 'نهایی‌سازی',  'icon' => 'check-circle'],
    ];

    public function mount(): void
    {
        auth('adminPanel')->user()?->can('packages.index') ?: abort(403);

        // نتیجه پرداخت از callback (پورت session('payment_result') در ویوی قدیمی)
        $this->paymentResult = session('payment_result');

        $this->loadPackages();
    }

    public function updatingSearch(): void
    {
        $this->page = 1;
    }

    public function updatingCategory(): void
    {
        $this->page = 1;
    }

    public function updatingSort(): void
    {
        $this->page = 1;
    }

    public function updatingFree(): void
    {
        $this->page = 1;
    }

    /*
     * فیلترهای زنده — تا الان search/category/sort با wire:model.live فقط
     * URL را عوض می‌کردند و لیست با Enter (submit فرم) تازه می‌شد؛ اکنون
     * با هر تغییر، لیست بلافاصله دوباره خوانده می‌شود.
     */
    public function updatedSearch(): void
    {
        $this->page = 1;
        $this->loadPackages();
    }

    public function updatedCategory(): void
    {
        $this->page = 1;
        $this->loadPackages();
    }

    public function updatedSort(): void
    {
        $this->page = 1;
        $this->loadPackages();
    }

    public function updatedFree(): void
    {
        $this->page = 1;
        $this->loadPackages();
    }

    /* ---------------- لیست پکیج‌ها از API (پورت ۱:۱ index قدیمی) ---------------- */

    /**
     * فیوز ارتباط با API بازار — بعد از یک خطای ارتباط، برای این مدت (ثانیه)
     * اصلاً به سرور پکیج‌ها تلاش نمی‌کنیم تا هر بار لود صفحه تا ۳۰ ثانیه هنگ نکند
     * (سرور پکیج‌ها ممکن است موقتاً کند/غیرفعال باشد؛ کاربر نباید جریمه شود).
     */
    private const API_FUSE_TTL_SECONDS = 90;

    private function apiFuseKey(): string
    {
        return 'packages.api.fuse';
    }

    /** اگر فیوز فعال است پیام خطای ذخیره‌شده را برمی‌گرداند، وگرنه null */
    private function apiFuseError(): ?string
    {
        return Cache::get($this->apiFuseKey());
    }

    /** روشن کردن فیوز بعد از یک خطای ارتباط با API */
    private function tripApiFuse(string $message): void
    {
        Cache::put($this->apiFuseKey(), $message, now()->addSeconds(self::API_FUSE_TTL_SECONDS));
    }

    public function loadPackages(): void
    {
        $this->apiError = null;

        // فیوز فعال؟ → بدون تماس با API، سریع خطای قبلی را نشان بده
        if ($fuseMessage = $this->apiFuseError()) {
            $this->packages = [];
            $this->paginationMeta = [];
            $this->installedMap = [];
            $this->subscriptionSummary = null;
            $this->apiError = $fuseMessage;

            return;
        }

        // فیلتر رایگان/پولی: API این فیلتر را نمی‌شناسد → همه صفحات جمع و سمت پنل فیلتر می‌شود
        if ($this->free !== '') {
            $this->loadPackagesWithPriceFilter();

            return;
        }

        $query = array_filter([
            'page' => $this->page,
            'search' => $this->search,
            'category' => $this->category,
            'sort' => $this->sort,
        ], fn ($v) => $v !== '' && $v !== null);

        $cacheKey = 'packages.list.'.md5(json_encode($query));

        try {
            $data = Cache::remember($cacheKey, now()->addMinutes(config('packages.cache.list_ttl')), function () use ($query) {
                return $this->api()->listPackages($query);
            });

            $packages = $data['data'] ?? $data['packages'] ?? [];
            $pagination = $data['meta'] ?? $data['pagination'] ?? [];

            $packages = $this->enrichPackages($packages);

            // ★ خلاصه اشتراک فعال پروژه (برای بنر صفحه ایندکس)
            $subscriptionSummary = $this->normalizeSubscriptionSummary($data['subscription_summary'] ?? null);

            // sync با جدول packages_cache (پورت syncCache قدیمی)
            $this->syncCache($packages);

            $this->syncInstalledState();

            $this->packages = $packages;
            $this->paginationMeta = $pagination;
            $this->subscriptionSummary = $subscriptionSummary;

            // آمار کارت‌ها از همین صفحه (حالت بدون فیلتر قیمت)
            $freeCount = collect($packages)->filter(fn ($p) => self::isEffectivelyFree($p))->count();
            $this->priceCounts = [
                'free' => $freeCount,
                'paid' => count($packages) - $freeCount,
                'total' => count($packages),
            ];
        } catch (RuntimeException $e) {
            $this->tripApiFuse($e->getMessage());
            $this->packages = [];
            $this->paginationMeta = [];
            $this->installedMap = [];
            $this->subscriptionSummary = null;
            $this->apiError = $e->getMessage();
        }
    }

    /**
     * حالت فیلتر رایگان/پولی — همه‌ی صفحات API (با احترام به search/category/sort)
     * جمع شده، پکیج‌ها بر اساس وضعیت قیمت فیلتر و سپس سمت پنل صفحه‌بندی می‌شوند.
     * کشِ هر صفحه‌ی API جداگانه است، پس ناوبری‌های بعدی هزینه‌ی شبکه‌ای ندارند.
     */
    protected function loadPackagesWithPriceFilter(): void
    {
        // فیوز فعال؟ → بدون تماس با API، سریع خطای قبلی را نشان بده
        if ($fuseMessage = $this->apiFuseError()) {
            $this->packages = [];
            $this->paginationMeta = [];
            $this->installedMap = [];
            $this->subscriptionSummary = null;
            $this->priceCounts = ['free' => 0, 'paid' => 0, 'total' => 0];
            $this->apiError = $fuseMessage;

            return;
        }

        try {
            $perPage = 12;
            $allPackages = [];
            $subscriptionSummary = null;
            $page = 1;
            $lastPage = 1;

            do {
                $query = array_filter([
                    'page' => $page,
                    'search' => $this->search,
                    'category' => $this->category,
                    'sort' => $this->sort,
                ], fn ($v) => $v !== '' && $v !== null);

                $cacheKey = 'packages.list.'.md5(json_encode($query));

                $data = Cache::remember($cacheKey, now()->addMinutes(config('packages.cache.list_ttl')), function () use ($query) {
                    return $this->api()->listPackages($query);
                });

                $packages = $data['data'] ?? $data['packages'] ?? [];
                $pagination = $data['meta'] ?? $data['pagination'] ?? [];

                if ($subscriptionSummary === null) {
                    $subscriptionSummary = $data['subscription_summary'] ?? null;
                }

                $lastPage = (int) ($pagination['last_page'] ?? $pagination['lastPage'] ?? 1);
                $perPage = (int) ($pagination['per_page'] ?? $pagination['perPage'] ?? $perPage);
                if ($perPage < 1) {
                    $perPage = 12;
                }

                $allPackages = array_merge($allPackages, $packages);
                $page++;
            } while ($page <= min($lastPage, self::MAX_AGGREGATE_PAGES) && ! empty($packages));

            // Enrique → غنی‌سازی (min_price / has_free_plan / اشتراک) مثل حالت عادی
            $allPackages = $this->enrichPackages($allPackages);

            // sync با جدول packages_cache مثل حالت عادی
            $this->syncCache($allPackages);

            // آمار کارت‌ها از کل نتایج تجمیع‌شده (قبل از فیلتر قیمت) — تا عددهای
            // کارت‌های آماری با تغییر فیلتر نپَرند و همیشه کل بازار را نشان بدهند
            $aggregateFreeCount = collect($allPackages)->filter(fn ($p) => self::isEffectivelyFree($p))->count();
            $this->priceCounts = [
                'free' => $aggregateFreeCount,
                'paid' => count($allPackages) - $aggregateFreeCount,
                'total' => count($allPackages),
            ];

            // فیلتر قیمت — تعریف «رایگان» (درخواست کاربر): فقط پکیج‌هایی که
            // is_free = true دارند «کلاً رایگان»‌اند. پکیج‌هایی که فقط پلن رایگان
            // (مثل طرح رایگان یک‌ماهه) یا نصب با اشتراک دارند، جزو «پولی‌ها» می‌مانند.
            $freeWanted = $this->free === 'free';
            $allPackages = array_values(array_filter(
                $allPackages,
                fn (array $pkg) => self::isEffectivelyFree($pkg) === $freeWanted
            ));

            // صفحه‌بندی سمت پنل روی نتایج فیلترشده
            $total = count($allPackages);
            $syntheticLastPage = max(1, (int) ceil($total / $perPage));
            $this->page = min(max(1, $this->page), $syntheticLastPage);
            $offset = ($this->page - 1) * $perPage;

            $this->packages = array_slice($allPackages, $offset, $perPage);
            $this->paginationMeta = [
                'current_page' => $this->page,
                'last_page' => $syntheticLastPage,
                'per_page' => $perPage,
                'total' => $total,
            ];

            $this->subscriptionSummary = $this->normalizeSubscriptionSummary($subscriptionSummary);
            $this->syncInstalledState();
        } catch (RuntimeException $e) {
            $this->tripApiFuse($e->getMessage());
            $this->packages = [];
            $this->paginationMeta = [];
            $this->installedMap = [];
            $this->subscriptionSummary = null;
            $this->priceCounts = ['free' => 0, 'paid' => 0, 'total' => 0];
            $this->apiError = $e->getMessage();
        }
    }

    /**
     * تشخیص «رایگان بودن» یک پکیج — فقط بر اساس is_free خود API (درخواست کاربر):
     * is_free = true یعنی پکیج «کلاً رایگان» است → جزو «رایگان‌ها».
     * پکیج‌هایی که فقط یک پلن رایگان دارند (مثل طرح رایگان یک‌ماهه) یا با اشتراکِ فعال
     * پروژه قابل نصب‌اند، «کلاً رایگان» نیستند → جزو «پولی‌ها» نمایش داده می‌شوند.
     * (قانون قدیمی min_price=0 = رایگان حذف شد — باعث می‌شد پکیج‌های پولیِ دارای
     * پلن رایگانِ یک‌ماهه در فیلتر «رایگان» بیایند و فیلتر «پولی» خالی بماند.)
     * این متد مشترکِ فیلتر رایگان/پولی و شمارنده‌ی کارت آماری است تا هر دو با
     * نشانِ (badge) کارت‌ها که از is_free ساخته می‌شود هم‌خوان بماند.
     */
    public static function isEffectivelyFree(array $pkg): bool
    {
        return self::normalizeIsFree($pkg['is_free'] ?? null);
    }

    /**
     * نرمال‌سازی مقادیر is_free با هر شکلی که API بفرستد:
     * true / 1 / "1" / "true" / "yes" / "on" → رایگان
     * false / 0 / "0" / "false" / null / نبودن فیلد → رایگان نیست
     * (empty() خام، رشته‌ی "false" را هم رایگان می‌گرفت)
     */
    public static function normalizeIsFree(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value) || is_float($value)) {
            return (int) $value === 1;
        }

        if (is_string($value)) {
            return in_array(strtolower(trim($value)), ['1', 'true', 'yes', 'on'], true);
        }

        return false;
    }

    /** غنی‌سازی هر پکیج: min_price + has_free_plan + اصلاح اعتبار اشتراک (پورت دقیق قدیمی) */
    protected function enrichPackages(array $packages): array
    {
        return array_map(function ($pkg) {
            $plans = $pkg['active_pricing_plans'] ?? $pkg['pricing_plans'] ?? $pkg['plans'] ?? [];

            // محاسبه قیمت نهایی هر پلن (با احتساب تخفیف)
            $planPrices = [];
            foreach ($plans as $plan) {
                $finalPrice = $plan['discount_price'] ?? $plan['price'] ?? 0;
                $planPrices[] = (int) $finalPrice;
            }

            // اگر هیچ پلنی نبود، از default_price استفاده کن
            if (empty($planPrices)) {
                $minPrice = (int) ($pkg['default_price'] ?? $pkg['price'] ?? 0);
            } else {
                $minPrice = min($planPrices);
            }

            // is_free فقط اگر خود API گفته باشه (با هر شکلی نرمال می‌شود تا نشان رایگان/فیلتر همیشه درست باشد)
            $pkg['is_free'] = self::normalizeIsFree($pkg['is_free'] ?? null);
            // has_free_plan: حداقل یک پلن رایگان
            $pkg['min_price'] = $minPrice;
            $pkg['plans'] = $plans;
            $pkg['has_free_plan'] = $minPrice === 0;

            if (! empty($pkg['subscription']['subscription_expires_at'])) {
                $exp = Carbon::parse($pkg['subscription']['subscription_expires_at']);
                $pkg['subscription']['days_remaining'] = max(0, (int) round(now()->diffInDays($exp)));
                if ($exp->isPast()) {
                    $pkg['subscription']['is_free_with_subscription'] = false;
                }
            }

            return $pkg;
        }, $packages);
    }

    /** نرمال‌سازی خلاصه اشتراک (از loadPackages جدا شد تا در حالت فیلتر هم استفاده شود) */
    protected function normalizeSubscriptionSummary(mixed $subscriptionSummary): ?array
    {
        if (! is_array($subscriptionSummary)) {
            return null;
        }

        if (! empty($subscriptionSummary['expires_at'])) {
            $exp = Carbon::parse($subscriptionSummary['expires_at']);
            $subscriptionSummary['days_remaining'] = max(0, (int) round(now()->diffInDays($exp)));
            $subscriptionSummary['has_active_subscription'] =
                ($subscriptionSummary['has_active_subscription'] ?? false) && $exp->isFuture();
        }

        return $subscriptionSummary;
    }

    /** نقشه‌ی نصب‌شده‌ها + خریدها + درحال‌نصب‌ها (از loadPackages جدا شد) */
    protected function syncInstalledState(): void
    {
        // دریافت ماژول‌های نصب‌شده برای مقایسه نسخه
        $this->installedMap = InstalledModule::pluck('version', 'slug')->toArray();
        $this->purchasedSlugs = PackagePurchase::query()
            ->where('status', PackagePurchase::STATUS_PAID)
            ->pluck('package_slug')
            ->all();

        // ماژول‌های در حال نصب/آپدیت (پورت pendingSlugs قدیمی)
        $pendingSlugs = [];
        $pendingModules = InstalledModule::where('status', InstalledModule::STATUS_UPDATING)
            ->where('updated_at', '>', now()->subMinutes(10))
            ->get();

        if ($pendingModules->isNotEmpty()) {
            $runningSlugs = ModuleInstallLog::whereIn('module_slug', $pendingModules->pluck('slug'))
                ->where('status', ModuleInstallLog::STATUS_RUNNING)
                ->where('created_at', '>', now()->subMinutes(10))
                ->pluck('module_slug')
                ->unique()
                ->flip()
                ->all();

            foreach ($pendingModules as $m) {
                $pendingSlugs[$m->slug] = isset($runningSlugs[$m->slug]) ? 'running' : 'queued';
            }
        }
        $this->pendingSlugs = $pendingSlugs;
    }

    /* ---------------- صفحه‌بندی سرور دست‌ساز (meta پاسخ API) ---------------- */

    public function gotoPage(int $page): void
    {
        $this->page = max(1, $page);
        $this->loadPackages();
    }

    public function nextPage(): void
    {
        $this->page++;
        $this->loadPackages();
    }

    public function previousPage(): void
    {
        $this->page = max(1, $this->page - 1);
        $this->loadPackages();
    }

    public function applyFilters(): void
    {
        $this->page = 1;
        $this->loadPackages();
    }

    /** تغییر سریع فیلتر قیمت از کارت‌های آماری/بخش فیلتر */
    public function setFreeFilter(string $value): void
    {
        $this->free = in_array($value, array_keys(self::$freeOptions), true) ? $value : '';
        $this->page = 1;
        $this->loadPackages();
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->category = '';
        $this->sort = '';
        $this->free = '';
        $this->page = 1;
        $this->loadPackages();
    }

    /* ---------------- نصب و پیشرفت (فاز ۱۷ + ۲۶ — تجربه قدیمی) ---------------- */

    /**
     * فاز ۲۶ — پس از صف‌شدن نصب: لیست رفرش + مودال نوار پیشرفت باز می‌شود.
     * کارت پکیج از همین لحظه «در حال نصب» است تا وضعیت صف مشخص شود (نصب/خطا).
     */
    public function refreshAfterInstall(?string $slug = null): void
    {
        $this->loadPackages();

        if ($slug !== null) {
            $this->progressSlug = $slug;
            $this->progressName = InstalledModule::where('slug', $slug)->value('name') ?: $slug;
            $this->pollProgress();
        }
    }

    /**
     * polling وضعیت نصب‌های در جریان (پورت progress.js قدیمی)
     * وقتی نصب کامل/ناموفق شد، پیام نتیجه + رفرش لیست
     */
    public function refreshPending(): void
    {
        if (empty($this->pendingSlugs)) {
            return;
        }

        $watched = array_keys($this->pendingSlugs);
        $this->loadPackages();

        foreach ($watched as $slug) {
            if (isset($this->pendingSlugs[$slug])) {
                continue; // هنوز در جریان است
            }

            $module = InstalledModule::where('slug', $slug)->first();

            if ($module && $module->status === InstalledModule::STATUS_INSTALLED) {
                $this->dispatch('toast', message: 'نصب «'.($module->name ?: $slug).'» با موفقیت کامل شد.', type: 'success');

                // درخواست کاربر — رفرش صفحه تا پکیج تازه‌نصب در منوی سایدبار بیاید
                $this->js('setTimeout(() => window.location.reload(), 1500)');
            } else {
                $lastError = ModuleInstallLog::where('module_slug', $slug)
                    ->where('status', ModuleInstallLog::STATUS_FAILED)
                    ->orderByDesc('created_at')
                    ->value('message');

                $this->dispatch('toast', message: 'نصب «'.$slug.'» ناموفق بود.'.($lastError ? ' ('.Str::limit($lastError, 120).')' : ''), type: 'error');
            }
        }
    }

    /* ---------------- فاز ۲۶ — polling مودال پیشرفت (پورت progress.js قدیمی) ---------------- */

    /**
     * هر ۲ ثانیه از blade با wire:poll صدا زده می‌شود (فقط وقتی مودال پیشرفت باز است).
     * وضعیت InstalledModule + آخرین لاگ → درصد و مرحله؛ پایان = toast + بستن مودال.
     */
    public function pollProgress(): void
    {
        if (empty($this->progressSlug)) {
            return;
        }

        $slug = $this->progressSlug;
        $module = InstalledModule::where('slug', $slug)->first();
        $lastLog = ModuleInstallLog::where('module_slug', $slug)->latest()->first();

        $status = $module?->status;
        $percent = 15;
        $stepIndex = 0; // دانلود
        $done = false;
        $failed = false;
        $message = 'در حال نصب — لطفاً صبر کنید…';

        if ($lastLog) {
            if ($lastLog->status === ModuleInstallLog::STATUS_RUNNING) {
                $percent = max($percent, 55);
                $stepIndex = 2; // استخراج
            } elseif ($lastLog->status === ModuleInstallLog::STATUS_SUCCESS) {
                $percent = 100;
            } elseif ($lastLog->status === ModuleInstallLog::STATUS_FAILED) {
                $percent = 100;
                $failed = true;
                $message = $lastLog->message ?: 'خطا در نصب پکیج';
            }
        }

        if ($status === InstalledModule::STATUS_UPDATING) {
            $percent = max($percent, 35);
        } elseif ($status === InstalledModule::STATUS_INSTALLED) {
            $percent = 100;
            $done = true;
        } elseif ($status === InstalledModule::STATUS_FAILED) {
            $percent = 100;
            $failed = true;
            $message = $module->last_error ?: $message;
        }

        if ($done) {
            $stepIndex = count(self::$progressSteps); // همه مراحل کامل
            $message = 'نصب با موفقیت کامل شد';
        }

        $this->progressState = [
            'percent' => $percent,
            'step' => $stepIndex,
            'status' => $failed ? 'failed' : ($done ? 'installed' : 'installing'),
            'message' => $message,
            'error' => $failed ? $message : null,
        ];

        if ($done || $failed) {
            $name = $this->progressName ?: $slug;

            $this->dispatch('toast', message: $done
                ? "نصب «{$name}» با موفقیت کامل شد."
                : "نصب «{$name}» ناموفق بود.".($failed && $message ? ' ('.Str::limit($message, 120).')' : ''),
                type: $done ? 'success' : 'error');

            /*
             * درخواست کاربر — پس از نصب کامل، صفحه رفرش شود تا پکیج تازه‌نصب
             * در منوی «پکیج‌ها»ی سایدبار ظاهر شود (منو سمت سرور از InstalledModule
             * ساخته می‌شود و با Livewire آپدیت نمی‌شود). تأخیر کوتاه تا toast دیده شود.
             */
            if ($done) {
                $this->js('setTimeout(() => window.location.reload(), 1500)');
            }

            $this->progressSlug = null;
            $this->progressName = null;
            $this->progressState = [];
            $this->loadPackages();
        }
    }

    /** بستن دستی نوار پیشرفت (بدون قطع polling وضعیت‌های دیگر — polling با خالی شدن progressSlug متوقف می‌شود) */
    public function dismissProgress(): void
    {
        $this->progressSlug = null;
        $this->progressName = null;
        $this->progressState = [];
    }

    /* ---------------- فاز ۲۶ — مودال جزئیات (جایگزین تک‌صفحه packages/{slug}) ---------------- */

    /**
     * باز کردن مودال جزئیات — دقیقاً مثل مودال پنل قدیمی (modal.js):
     * جزئیات کامل + گالری + توضیحات + تاریخچه نسخه + پیش‌نیاز + وضعیت نصب + لاگ‌ها + پلن‌ها
     */
    public function openDetail(string $slug): void
    {
        auth('adminPanel')->user()?->can('packages.index') ?: abort(403);

        $this->detailOpen = true;
        $this->detailLoading = true;
        $this->detailError = null;
        $this->detail = null;
        $this->detailLogs = [];
        $this->detailWarning = null;
        $this->detailMode = 'paid';
        $this->detailPlanId = null;
        $this->detailConfirmingUninstall = false;

        try {
            $cacheKey = 'packages.detail.'.$slug;

            $data = Cache::remember($cacheKey, now()->addMinutes(config('packages.cache.detail_ttl')), function () use ($slug) {
                return $this->api()->getPackage($slug);
            });

            $package = $data['data'] ?? $data;

            // نرمال‌سازی is_free (هر شکلی از API → بولین تمیز برای نشان «رایگان» مودال)
            $package['is_free'] = self::normalizeIsFree($package['is_free'] ?? null);

            // اصلاح اعتبار اشتراک (به دلیل کش شدن detail — پورت show قدیمی)
            if (! empty($package['subscription']['subscription_expires_at'])) {
                $exp = Carbon::parse($package['subscription']['subscription_expires_at']);
                $package['subscription']['days_remaining'] = max(0, (int) round(now()->diffInDays($exp)));
                if ($exp->isPast()) {
                    $package['subscription']['is_free_with_subscription'] = false;
                }
            }

            // خرید قبلی معتبر؟ (پورت purchased قدیمی)
            $package['purchased'] = PackagePurchase::where('package_slug', $slug)
                ->where('status', PackagePurchase::STATUS_PAID)
                ->whereNotNull('license_key')
                ->exists();

            // اطلاعات نصب محلی
            $installed = InstalledModule::where('slug', $slug)->first();
            $activeVersions = $package['active_versions'] ?? [];

            if (! empty($activeVersions)) {
                usort($activeVersions, fn ($a, $b) => version_compare($b['version'], $a['version']));
                $latestVersion = $activeVersions[0]['version'];
            } else {
                $latestVersion = $package['latest_version']['version'] ?? $package['version'] ?? '';
            }

            $package['installed'] = $installed ? [
                'version' => $installed->version,
                'status' => $installed->status,
                'is_active' => $installed->is_active,
                'is_expired' => $installed->isExpired(),
                'license_expires_at' => $installed->license_expires_at?->toDateTimeString(),
                'license_expires_human' => $this->jexpiry($installed->license_expires_at?->toDateTimeString()),
                'last_error' => $installed->last_error,
            ] : null;
            $package['latestVersion'] = $latestVersion;
            $package['has_update'] = $installed && $latestVersion !== ''
                && version_compare($latestVersion, (string) $installed->version, '>');

            // لاگ‌های اخیر (پورت show قدیمی)
            $this->detailLogs = ModuleInstallLog::where('module_slug', $slug)
                ->latest()
                ->limit(10)
                ->get()
                ->map(fn ($log) => [
                    'id' => $log->id,
                    'action' => $log->action,
                    'from_version' => $log->from_version,
                    'to_version' => $log->to_version,
                    'status' => $log->status,
                    'message' => $log->message,
                    'created_at' => $log->created_at->diffForHumans(),
                ])
                ->all();

            $this->detail = $package;

            // پلن پیش‌فرض: ارزان‌ترین (پورت modal.js قدیمی)
            $plans = $package['active_pricing_plans'] ?? $package['pricing_plans'] ?? $package['plans'] ?? [];
            $this->detailPlanId = self::cheapestPlanId($plans);

            // وضعیت خرید/اشتراک (پورت checkModalLicense/applyModalSubscriptionMode قدیمی)
            $subscription = $package['subscription'] ?? null;

            if (! $installed && ($subscription['is_free_with_subscription'] ?? false)) {
                $this->detailMode = 'subscription';
            } else {
                $state = $this->checkPurchaseState($slug);

                if (($state['mode'] ?? 'paid') === 'license') {
                    $this->detailMode = 'license';
                } else {
                    $this->detailMode = 'paid';
                    $this->detailWarning = $state['warning'] ?? null;
                }
            }
        } catch (RuntimeException $e) {
            $this->detailError = $e->getMessage();
        } catch (\Throwable $e) {
            report($e);
            $this->detailError = 'خطا در دریافت اطلاعات پکیج.';
        } finally {
            $this->detailLoading = false;
        }
    }

    public function closeDetail(): void
    {
        $this->detailOpen = false;
        $this->detailConfirmingUninstall = false;
    }

    /** انتخاب پلن داخل مودال (پورت کلیک روی pkg-plan-card قدیمی) */
    public function setDetailPlan(int $planId): void
    {
        $this->detailPlanId = $planId;
        $this->detailWarning = null;
    }

    /**
     * دکمه‌ی نصب داخل مودال جزئیات — مسیر همان confirmInstall مودال تأیید است؛
     * بعد از موفقیت مودال جزئیات بسته و مودال پیشرفت باز می‌شود.
     */
    public function installFromDetail(): void
    {
        if (empty($this->detail['slug'])) {
            return;
        }

        $slug = $this->detail['slug'];

        // جلوگیری از نصب تکراری وقتی در حال نصب است
        $installed = InstalledModule::where('slug', $slug)->first();
        if ($installed && $installed->status === InstalledModule::STATUS_UPDATING) {
            $this->dispatch('toast', message: 'نصب این پکیج همچنان در جریان است.', type: 'error');

            return;
        }

        match ($this->detailMode) {
            'subscription' => $this->startInstall($slug, null, true, false),
            'license' => $this->startInstall($slug, null, false, true),
            default => $this->startInstall($slug, $this->detailPlanId),
        };

        /*
         * اگر نصب آغاز شده باشد (رکورد updating ساخته شده) یا مسیر پرداخت فعال باشد،
         * مودال بسته می‌شود و مودال پیشرفت/درگاه جای آن را می‌گیرد؛ اگر خطا رخ داده
         * باشد مودال باز می‌ماند تا پیام خطا دیده شود.
         */
        $pending = InstalledModule::where('slug', $slug)
            ->where('status', InstalledModule::STATUS_UPDATING)
            ->exists();

        if ($pending || $this->detailMode !== 'paid') {
            $this->closeDetail();
        }
    }

    /** آپدیت از داخل مودال جزئیات (پورت modal-btn-update قدیمی) */
    public function updateFromDetail(): void
    {
        if (empty($this->detail['slug'])) {
            return;
        }

        $slug = $this->detail['slug'];

        $installed = InstalledModule::where('slug', $slug)->first();

        if (! $installed) {
            $this->dispatch('toast', message: 'ماژول نصب نشده است.', type: 'error');

            return;
        }

        if ($installed->status === InstalledModule::STATUS_UPDATING) {
            $this->dispatch('toast', message: 'آپدیت قبلی همچنان در حال اجراست.', type: 'error');

            return;
        }

        if ($installed->isExpired()) {
            $this->dispatch('toast', message: 'لایسنس ماژول منقضی شده است. ابتدا آن را تمدید کنید.', type: 'error');

            return;
        }

        // فاز ۲۶ — آپدیت هم از لحظه‌ی درخواست «در حال نصب» نمایش داده شود
        $this->markUpdatingBeforeDispatch($slug);

        UpdatePackageJob::dispatch($slug, auth('adminPanel')->id());

        $this->closeDetail();
        $this->refreshAfterInstall($slug);
    }

    /** فعال/غیرفعال از داخل مودال (پورت modal-btn-toggle قدیمی) */
    public function toggleFromDetail(): void
    {
        if (empty($this->detail['slug'])) {
            return;
        }

        $slug = $this->detail['slug'];

        try {
            $installed = app(PackageInstallerService::class)->toggleActivation($slug);

            $this->dispatch('toast', message: $installed->is_active ? 'ماژول فعال شد.' : 'ماژول غیرفعال شد.', type: 'success');
            $this->openDetail($slug); // رفرش داده‌های مودال
        } catch (\Throwable $e) {
            $this->dispatch('toast', message: $e->getMessage(), type: 'error');
        }
    }

    /** تأیید دو مرحله‌ای حذف داخل مودال (پورت Swal confirm قدیمی) */
    public function confirmDetailUninstall(): void
    {
        $this->detailConfirmingUninstall = true;
    }

    public function cancelDetailUninstall(): void
    {
        $this->detailConfirmingUninstall = false;
    }

    public function uninstallFromDetail(): void
    {
        if (empty($this->detail['slug'])) {
            return;
        }

        $slug = $this->detail['slug'];
        $this->detailConfirmingUninstall = false;

        try {
            app(PackageInstallerService::class)->uninstall($slug, auth('adminPanel')->id());

            $this->dispatch('toast', message: 'ماژول با موفقیت حذف شد.', type: 'success');
            $this->closeDetail();
            $this->loadPackages();
        } catch (\Throwable $e) {
            $this->dispatch('toast', message: 'حذف ناموفق بود: '.$e->getMessage(), type: 'error');
        }
    }

    /* ---------------- کمکی‌های پلن‌ها (پورت index.js قدیمی) ---------------- */

    public static function planFinalPrice(array $plan): int
    {
        return (int) ($plan['discount_price'] ?? $plan['price'] ?? 0);
    }

    public static function planDurationLabel(array $plan): string
    {
        $months = (int) ($plan['duration_months'] ?? 0);
        if ($months === 0) {
            return 'نامحدود';
        }
        if ($months < 12) {
            return "{$months} ماه";
        }
        $years = $months / 12;

        return ($years === floor($years) ? floor($years) : $years).' سال';
    }

    /** ارزان‌ترین پلن (برای انتخاب پیش‌فرض مودال تأیید نصب) */
    public static function cheapestPlanId(array $plans): ?int
    {
        if (empty($plans)) {
            return null;
        }

        $cheapest = null;
        foreach ($plans as $plan) {
            if ($cheapest === null || self::planFinalPrice($plan) < self::planFinalPrice($cheapest)) {
                $cheapest = $plan;
            }
        }

        return $cheapest ? (int) ($cheapest['id'] ?? 0) ?: null : null;
    }

    /** وضعیت آپدیت هر پکیج — برای دکمه «آپدیت» کارت (پورت hasUpdate قدیمی) */
    public function hasUpdate(string $slug, array $pkg, $installedVersion): bool
    {
        if ($installedVersion === null) {
            return false;
        }

        $latest = $pkg['latest_version']['version'] ?? ($pkg['version'] ?? '');

        return $latest !== '' && version_compare($latest, (string) $installedVersion, '>');
    }

    /** برچسب جلالی تاریخ انقضا برای مودال لایسنس/اشتراک */
    public static function jexpiry(?string $date): ?string
    {
        if (empty($date)) {
            return null;
        }

        try {
            return jdate(Carbon::parse($date))->format('Y/m/d');
        } catch (\Throwable $e) {
            return null;
        }
    }

    /* ---------------- بررسی آپدیت همه ماژول‌ها (پورت checkUpdates قدیمی) ---------------- */

    public function checkUpdates(): void
    {
        auth('adminPanel')->user()?->can('packages.index') ?: abort(403);

        $modules = InstalledModule::all();
        $updates = [];

        foreach ($modules as $module) {
            try {
                $result = $this->api()->checkUpdate($module->slug, $module->version);
                if ($result['has_update'] ?? false) {
                    $updates[] = [
                        'slug' => $module->slug,
                        'name' => $module->name,
                        'current' => $module->version,
                        'latest' => $result['latest_version'],
                        'changelog' => $result['changelog'] ?? null,
                    ];
                }
            } catch (\Exception $e) {
                Log::warning("Update check failed for {$module->slug}: ".$e->getMessage());
            }
        }

        $this->updateResults = $updates;

        if (empty($updates)) {
            $this->dispatch('toast', message: 'همه چیز به‌روز است — هیچ آپدیتی برای ماژول‌های نصب‌شده موجود نیست.', type: 'success');
        } else {
            $this->dispatch('toast', message: count($updates).' بروزرسانی برای ماژول‌های نصب‌شده یافت شد.', type: 'info');
        }
    }

    /* ---------------- Private Helpers (پورت syncCache قدیمی) ---------------- */

    private function syncCache(array $packages): void
    {
        try {
            DB::transaction(function () use ($packages) {
                foreach ($packages as $pkg) {
                    PackageCache::updateOrCreate(
                        ['slug' => $pkg['slug']],
                        [
                            'name' => $pkg['name'] ?? $pkg['slug'],
                            'description' => $pkg['description'] ?? null,
                            'latest_version' => $pkg['latest_version'] ? $pkg['latest_version']['version'] : ($pkg['version'] ?? ''),
                            'author' => $pkg['author'] ?? null,
                            'category' => $pkg['category'] ?? null,
                            'thumbnail' => $pkg['thumbnail'] ?? null,
                            'price' => $pkg['price'] ?? 0,
                            'is_free' => $pkg['is_free'] ?? false,
                            'meta' => $pkg,
                            'versions' => $pkg['versions'] ?? null,
                            'fetched_at' => now(),
                        ]
                    );
                }
            });
        } catch (\Exception $e) {
            Log::warning('Packages cache sync failed', ['error' => $e->getMessage()]);
        }
    }

    /* ---------------- ابزار ویو ---------------- */

    public static function priceLabel(array $pkg): string
    {
        if (! empty($pkg['is_free'])) {
            return 'رایگان';
        }

        return number_format($pkg['min_price'] ?? 0).' تومان';
    }

    public function render()
    {
        return view('admin-panel::livewire.admin.packages.index')
            ->layout('admin-panel::layout');
    }
}
