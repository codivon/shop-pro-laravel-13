<?php

namespace App\Livewire\Admin\Packages;

use App\Livewire\Admin\Packages\Concerns\InteractsWithPackages;
use App\Models\InstalledModule;
use App\Models\ModuleInstallLog;
use App\Models\PackageCache;
use App\Models\PackagePurchase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
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
        'shop'         => 'فروشگاه',
        'payment'      => 'پرداخت',
        'notification' => 'اعلان',
        'seo'          => 'سئو',
    ];

    #[Url(as: 'sort', except: '')]
    public string $sort = '';

    /** صفحه‌بندی سرور دست‌ساز بر اساس meta پاسخ API (قدیمی pagination سمت API بود) */
    public int $page = 1;

    /* ---------------- دیتای صفحه ---------------- */

    public array $packages = [];

    public array $paginationMeta = [];

    public array $installedMap = [];

    public array $purchasedSlugs = [];

    public array $pendingSlugs = [];

    public ?array $subscriptionSummary = null;

    public ?string $apiError = null;

    /** نتیجه‌ی callback پرداخت (session payment_result قدیمی → مودال) */
    public ?array $paymentResult = null;

    /** نتیجه بررسی آپدیت‌ها (پورت checkUpdates قدیمی) */
    public array $updateResults = [];

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

    /* ---------------- لیست پکیج‌ها از API (پورت ۱:۱ index قدیمی) ---------------- */

    public function loadPackages(): void
    {
        $query = array_filter([
            'page'     => $this->page,
            'search'   => $this->search,
            'category' => $this->category,
            'sort'     => $this->sort,
        ], fn ($v) => $v !== '' && $v !== null);

        $cacheKey = 'packages.list.' . md5(json_encode($query));
        $this->apiError = null;

        try {
            $data = Cache::remember($cacheKey, now()->addMinutes(config('packages.cache.list_ttl')), function () use ($query) {
                return $this->api()->listPackages($query);
            });

            $packages = $data['data'] ?? $data['packages'] ?? [];
            $pagination = $data['meta'] ?? $data['pagination'] ?? [];

            // محاسبه کمترین قیمت و تشخیص رایگان بودن برای هر پکیج (پورت دقیق)
            $packages = array_map(function ($pkg) {
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

                // is_free فقط اگر خود API گفته باشه؛ has_free_plan: حداقل یک پلن رایگان
                $pkg['min_price'] = $minPrice;
                $pkg['plans'] = $plans;
                $pkg['has_free_plan'] = $minPrice === 0;

                if (!empty($pkg['subscription']['subscription_expires_at'])) {
                    $exp = \Carbon\Carbon::parse($pkg['subscription']['subscription_expires_at']);
                    $pkg['subscription']['days_remaining'] = max(0, (int) round(now()->diffInDays($exp)));
                    if ($exp->isPast()) {
                        $pkg['subscription']['is_free_with_subscription'] = false;
                    }
                }

                return $pkg;
            }, $packages);

            // ★ خلاصه اشتراک فعال پروژه (برای بنر صفحه ایندکس)
            $subscriptionSummary = $data['subscription_summary'] ?? null;
            if (is_array($subscriptionSummary)) {
                if (!empty($subscriptionSummary['expires_at'])) {
                    $exp = \Carbon\Carbon::parse($subscriptionSummary['expires_at']);
                    $subscriptionSummary['days_remaining'] = max(0, (int) round(now()->diffInDays($exp)));
                    $subscriptionSummary['has_active_subscription'] =
                        ($subscriptionSummary['has_active_subscription'] ?? false) && $exp->isFuture();
                }
            } else {
                $subscriptionSummary = null;
            }

            // sync با جدول packages_cache (پورت syncCache قدیمی)
            $this->syncCache($packages);

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

            $this->packages = $packages;
            $this->paginationMeta = $pagination;
            $this->subscriptionSummary = $subscriptionSummary;
        } catch (RuntimeException $e) {
            $this->packages = [];
            $this->paginationMeta = [];
            $this->installedMap = [];
            $this->subscriptionSummary = null;
            $this->apiError = $e->getMessage();
        }
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

    public function resetFilters(): void
    {
        $this->search = '';
        $this->category = '';
        $this->sort = '';
        $this->page = 1;
        $this->loadPackages();
    }

    /* ---------------- نصب و پیشرفت (فاز ۱۷ — تجربه قدیمی) ---------------- */

    /** پس از صف‌شدن نصب، کارت‌ها فوراً به حالت «در صف نصب» می‌روند */
    public function refreshAfterInstall(?string $slug = null): void
    {
        $this->loadPackages();
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
                $this->dispatch('toast', message: 'نصب «' . ($module->name ?: $slug) . '» با موفقیت کامل شد.', type: 'success');
            } else {
                $lastError = ModuleInstallLog::where('module_slug', $slug)
                    ->where('status', ModuleInstallLog::STATUS_FAILED)
                    ->orderByDesc('created_at')
                    ->value('message');

                $this->dispatch('toast', message: 'نصب «' . $slug . '» ناموفق بود.' . ($lastError ? ' (' . \Illuminate\Support\Str::limit($lastError, 120) . ')' : ''), type: 'error');
            }
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
        if ($months === 0) return 'نامحدود';
        if ($months < 12) return "{$months} ماه";
        $years = $months / 12;

        return ($years === floor($years) ? floor($years) : $years) . ' سال';
    }

    /** ارزان‌ترین پلن (برای انتخاب پیش‌فرض مودال تأیید نصب) */
    public static function cheapestPlanId(array $plans): ?int
    {
        if (empty($plans)) return null;

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
        if ($installedVersion === null) return false;

        $latest = $pkg['latest_version']['version'] ?? ($pkg['version'] ?? '');

        return $latest !== '' && version_compare($latest, (string) $installedVersion, '>');
    }

    /** برچسب جلالی تاریخ انقضا برای مودال لایسنس/اشتراک */
    public static function jexpiry(?string $date): ?string
    {
        if (empty($date)) return null;

        try {
            return jdate(\Carbon\Carbon::parse($date))->format('Y/m/d');
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
                        'slug'      => $module->slug,
                        'name'      => $module->name,
                        'current'   => $module->version,
                        'latest'    => $result['latest_version'],
                        'changelog' => $result['changelog'] ?? null,
                    ];
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::warning("Update check failed for {$module->slug}: " . $e->getMessage());
            }
        }

        $this->updateResults = $updates;

        if (empty($updates)) {
            $this->dispatch('toast', message: 'همه چیز به‌روز است — هیچ آپدیتی برای ماژول‌های نصب‌شده موجود نیست.', type: 'success');
        } else {
            $this->dispatch('toast', message: count($updates) . ' بروزرسانی برای ماژول‌های نصب‌شده یافت شد.', type: 'info');
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
                            'name'           => $pkg['name'] ?? $pkg['slug'],
                            'description'    => $pkg['description'] ?? null,
                            'latest_version' => $pkg['latest_version'] ? $pkg['latest_version']['version'] : ($pkg['version'] ?? ''),
                            'author'         => $pkg['author'] ?? null,
                            'category'       => $pkg['category'] ?? null,
                            'thumbnail'      => $pkg['thumbnail'] ?? null,
                            'price'          => $pkg['price'] ?? 0,
                            'is_free'        => $pkg['is_free'] ?? false,
                            'meta'           => $pkg,
                            'versions'       => $pkg['versions'] ?? null,
                            'fetched_at'     => now(),
                        ]
                    );
                }
            });
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('Packages cache sync failed', ['error' => $e->getMessage()]);
        }
    }

    /* ---------------- ابزار ویو ---------------- */

    public static function priceLabel(array $pkg): string
    {
        if (!empty($pkg['is_free'])) {
            return 'رایگان';
        }

        return number_format($pkg['min_price'] ?? 0) . ' تومان';
    }

    public function render()
    {
        return view('admin-panel::livewire.admin.packages.index')
            ->layout('admin-panel::layout');
    }
}
