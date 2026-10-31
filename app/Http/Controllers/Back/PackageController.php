<?php

namespace App\Http\Controllers\Back;

use App\Http\Controllers\Controller;
use App\Jobs\InstallPackageJob;
use App\Jobs\UpdatePackageJob;
use App\Models\InstalledModule;
use App\Models\ModuleInstallLog;
use App\Models\PackageCache;
use App\Models\PackagePurchase;
use App\Services\LicenseVerifier;
use App\Services\PackageApiService;
use App\Services\PackageInstallerService;
use App\Support\SubscriptionDisplay;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class PackageController extends Controller
{
    public function __construct(
        private PackageApiService $api,
        private LicenseVerifier $licenseVerifier
    ) {}

    /* ===================================================================
     *  لیست پکیج‌های موجود در API
     * =================================================================== */
    public function index(Request $request)
    {
        $query = $request->only(['page', 'search', 'category', 'sort']);
        $cacheKey = 'packages.list.'.md5(json_encode($query));
        $errors = null;
        $purchasedSlugs = null;
        $pendingSlugs = [];
        try {
            $data = Cache::remember($cacheKey, now()->addMinutes(config('packages.cache.list_ttl')), function () use ($query) {
                return $this->api->listPackages($query);
            });
            $packages = $data['data'] ?? $data['packages'] ?? [];
            $pagination = $data['meta'] ?? $data['pagination'] ?? [];

            // محاسبه کمترین قیمت و تشخیص رایگان بودن برای هر پکیج
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

                // is_free فقط اگر خود API گفته باشه (پکیج کاملاً رایگان)
                // has_free_plan: اگر حداقل یک پلن رایگان وجود داره
                $pkg['min_price'] = $minPrice;
                $pkg['plans'] = $plans;
                $pkg['has_free_plan'] = $minPrice === 0;
                // is_free از API حفظ می‌شه (اگه API گفته false، همون false می‌مونه)

                // فاز ۱۰۰ — کلیدهای نمایشی «کمتر از ۱ روز» با SubscriptionDisplay
                if (! empty($pkg['subscription']['subscription_expires_at'])) {
                    $pkg['subscription'] = SubscriptionDisplay::annotate($pkg['subscription'], 'subscription_expires_at');
                    if (Carbon::parse($pkg['subscription']['subscription_expires_at'])->isPast()) {
                        $pkg['subscription']['is_free_with_subscription'] = false;
                    }
                }

                return $pkg;
            }, $packages);

            // ★ خلاصه اشتراک فعال پروژه (برای بنر صفحه ایندکس)
            $subscriptionSummary = $data['subscription_summary'] ?? null;
            if (is_array($subscriptionSummary)) {
                if (! empty($subscriptionSummary['expires_at'])) {
                    // فاز ۱۰۰ — کلیدهای نمایشی «کمتر از ۱ روز» + «منقضی‌شده»
                    $subscriptionSummary = SubscriptionDisplay::annotate($subscriptionSummary, 'expires_at');
                    $exp = Carbon::parse($subscriptionSummary['expires_at']);
                    $subscriptionSummary['has_active_subscription'] =
                        ($subscriptionSummary['has_active_subscription'] ?? false) && $exp->isFuture();
                }
            } else {
                $subscriptionSummary = null;
            }

            // sync با جدول packages_cache (در background)
            $this->syncCache($packages);

            // دریافت ماژول‌های نصب‌شده برای مقایسه نسخه
            $installedMap = InstalledModule::pluck('version', 'slug')->toArray();
            $purchasedSlugs = PackagePurchase::query()
                ->where('status', PackagePurchase::STATUS_PAID)
                // ->where('admin_id', auth('admin')->id())
                ->pluck('package_slug')
                ->all();

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

        } catch (RuntimeException $e) {
            $packages = [];
            $pagination = [];
            $installedMap = [];
            $subscriptionSummary = [];
            $errors = $e->getMessage();
            session()->flash('error', $e->getMessage());
        }

        return view('back.packages.index', compact('packages', 'pagination', 'purchasedSlugs', 'installedMap', 'errors', 'subscriptionSummary', 'pendingSlugs'));
    }

    /* ===================================================================
     *  جزئیات یک پکیج (AJAX - برای نمایش در مدال)
     * =================================================================== */
    public function show(Request $request, string $slug): JsonResponse
    {
        $cacheKey = 'packages.detail.'.$slug;

        try {
            $data = Cache::remember($cacheKey, now()->addMinutes(config('packages.cache.detail_ttl')), function () use ($slug) {
                return $this->api->getPackage($slug);
            });
            $package = $data['data'] ?? $data;
            // ★ اشتراک — اصلاح اعتبار و روزها (به دلیل کش شدن detail)
            // فاز ۱۰۰ — کلیدهای نمایشی «کمتر از ۱ روز» با SubscriptionDisplay
            if (! empty($package['subscription']['subscription_expires_at'])) {
                $package['subscription'] = SubscriptionDisplay::annotate($package['subscription'], 'subscription_expires_at');
                if (Carbon::parse($package['subscription']['subscription_expires_at'])->isPast()) {
                    $package['subscription']['is_free_with_subscription'] = false;
                }
            }
            $package['purchased'] = PackagePurchase::where('package_slug', $slug)
                ->where('status', PackagePurchase::STATUS_PAID)
                ->whereNotNull('license_key')
                ->exists();
            // enrich: اطلاعات نصب محلی
            $installed = InstalledModule::where('slug', $slug)->first();
            $activeVersions = $package['active_versions'] ?? [];

            if (! empty($activeVersions)) {
                // مرتب‌سازی بر اساس نسخه به صورت نزولی
                usort($activeVersions, function ($a, $b) {
                    return version_compare($b['version'], $a['version']);
                });

                $latestVersion = $activeVersions[0]['version'];
            } else {
                $latestVersion = $package['version'] ?? '';
            }
            $hasUpdate = $installed && version_compare($latestVersion, $installed->version, '>');

            $package['installed'] = $installed ? [
                'version' => $installed->version,
                'status' => $installed->status,
                'is_active' => $installed->is_active,
                'is_expired' => $installed->isExpired(),
                'license_expires_at' => $installed->license_expires_at?->toDateTimeString(),
                'license_expires_human' => $installed->license_expires_at?->diffForHumans(),
                'last_error' => $installed->last_error,
            ] : null;
            $package['has_update'] = $hasUpdate;
            $package['latestVersion'] = $latestVersion;

            // enrich: لاگ‌های اخیر
            $logs = ModuleInstallLog::where('module_slug', $slug)
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
                ]);

            return response()->json([
                'success' => true,
                'data' => $package,
                'logs' => $logs,
            ]);
        } catch (RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /* ===================================================================
     *  ماژول‌های نصب‌شده
     * =================================================================== */
    public function installed()
    {
        $modules = InstalledModule::with('cache')
            ->latest('installed_at')
            ->paginate(20);

        return view('back.packages.installed', compact('modules'));
    }

    /* ===================================================================
     *  شروع فرآیند نصب (ریدایرکت به درگاه)
     *  - پکیج رایگان: مستقیم به install
     *  - پکیج پولی: ایجاد purchase با pricing_plan_id → ریدایرکت به درگاه
     * =================================================================== */
    public function startInstall(Request $request, string $slug)
    {
        $request->validate([
            'version' => 'nullable|string|max:50',
            'pricing_plan_id' => 'nullable|integer',
        ]);

        try {
            $package = $this->api->getPackage($slug);
            $package = $package['data'] ?? $package;
            $version = $package['active_versions'] ? $package['active_versions'][0]['version'] : null;
            $pricingPlanId = $request->pricing_plan_id;
            $selectedPlan = collect($package['active_pricing_plans'])
                ->firstWhere('id', $pricingPlanId);
            $isFree = false;
            if ($selectedPlan) {
                $isFree = ($selectedPlan['price'] == 0);
            } else {
                // اگر پلن پیدا نشد، از مقدار is_free پکیج استفاده کن
                $isFree = (bool) ($package['is_free'] ?? false);
            }

            // ★ نصب با اشتراک فعال (بدون پرداخت)
            if ($request->boolean('use_subscription')) {
                return $this->installWithSubscription($slug, $request);
            }

            // اگر قبلاً نصب شده، جلوگیری شود
            if (InstalledModule::where('slug', $slug)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'این پکیج قبلاً نصب شده است.',
                ], 422);
            }
            if ($isFree) {
                // پکیج رایگان: مستقیم به job
                return $this->dispatchInstall($slug, $version, null, $pricingPlanId);
            }

            // ★ نصب مجدد با لایسنس قبلی (خریداری‌شده — بدون پرداخت)
            if ($request->boolean('use_license')) {
                return $this->reinstallWithExistingLicense($slug);
            }

            // پکیج پولی: ایجاد درخواست خرید با pricing_plan_id
            $callbackUrl = route(config('packages.payment.callback_route', route('admin.packages.payment.callback')));

            $purchase = $this->api->createPurchase($slug, $callbackUrl, $request->user('adminPanel')->id ?? null, $pricingPlanId);

            // ذخیره رکورد خرید
            $purchaseRecord = PackagePurchase::create([
                'admin_id' => $request->user('adminPanel')->id ?? null,
                'package_slug' => $slug,
                'package_name' => $package['name'] ?? $slug,
                'version' => $version,
                'amount' => $purchase['amount'] ?? ($package['price'] ?? 0),
                'gateway' => $purchase['gateway'] ?? null,
                'transaction_id' => $purchase['transaction_id'] ?? null,
                'payment_url' => isset($purchase['payment_url']) ? $purchase['payment_url'] : null,
                'status' => PackagePurchase::STATUS_PENDING,
                'meta' => $purchase,
            ]);

            return response()->json([
                'success' => true,
                'redirect_url' => isset($purchase['payment_url']) ? $purchase['payment_url'] : null,
                'purchase_id' => $purchaseRecord->id,
            ]);

        } catch (RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /* ===================================================================
     *  آپدیت ماژول نصب‌شده
     * =================================================================== */
    public function update(Request $request, string $slug)
    {
        $installed = InstalledModule::where('slug', $slug)->first();

        if (! $installed) {
            return response()->json([
                'success' => false,
                'message' => 'ماژول نصب نشده است.',
            ], 404);
        }

        if ($installed->status === InstalledModule::STATUS_UPDATING) {
            return response()->json([
                'success' => false,
                'message' => 'آپدیت قبلی همچنان در حال اجراست.',
            ], 422);
        }

        if ($installed->isExpired()) {
            return response()->json([
                'success' => false,
                'message' => 'لایسنس ماژول منقضی شده است. ابتدا آن را تمدید کنید.',
            ], 422);
        }
        UpdatePackageJob::dispatch($slug, $request->user('adminPanel')->id ?? null);

        return response()->json([
            'success' => true,
            'message' => 'آپدیت در صف پردازش قرار گرفت.',
        ]);
    }

    /* ===================================================================
     *  حذف ماژول
     * =================================================================== */
    public function uninstall(Request $request, string $slug)
    {
        try {
            app(PackageInstallerService::class)->uninstall(
                $slug,
                $request->user('adminPanel')->id ?? null
            );

            return response()->json([
                'success' => true,
                'message' => 'ماژول با موفقیت حذف شد.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حذف ناموفق بود: '.$e->getMessage(),
            ], 500);
        }
    }

    /* ===================================================================
     *  فعال/غیرفعال‌سازی
     * =================================================================== */
    public function toggleActivation(Request $request, string $slug)
    {
        try {
            $installed = app(PackageInstallerService::class)->toggleActivation($slug);

            return response()->json([
                'success' => true,
                'is_active' => $installed->is_active,
                'message' => $installed->is_active
                    ? 'ماژول فعال شد.'
                    : 'ماژول غیرفعال شد.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /* ===================================================================
     *  بررسی وضعیت نصب (برای polling)
     * =================================================================== */
    public function status(string $slug): JsonResponse
    {
        $installed = InstalledModule::where('slug', $slug)->first();
        $latestLog = ModuleInstallLog::where('module_slug', $slug)
            ->latest()
            ->first();
        $phase = null;
        if ($installed && $installed->status === InstalledModule::STATUS_UPDATING) {
            $phase = ($latestLog
                && $latestLog->status === ModuleInstallLog::STATUS_RUNNING
                && $latestLog->created_at && $latestLog->created_at->gt(now()->subMinutes(10)))
                ? 'running'
                : 'queued';
        }

        return response()->json([
            'phase' => $phase,
            'installed' => $installed ? [
                'version' => $installed->version,
                'status' => $installed->status,
                'is_active' => $installed->is_active,
                'error' => $installed->last_error,
                'updated_at' => $installed->updated_at?->diffForHumans(),
            ] : null,
            'last_log' => $latestLog ? [
                'action' => $latestLog->action,
                'status' => $latestLog->status,
                'message' => $latestLog->message,
                'created_at' => $latestLog->created_at->diffForHumans(),
                'details' => $latestLog->details,
            ] : null,
        ]);
    }

    /* ===================================================================
     *  چک آپدیت همه ماژول‌ها
     * =================================================================== */
    public function checkUpdates(): JsonResponse
    {
        $modules = InstalledModule::all();
        $updates = [];

        foreach ($modules as $module) {
            try {
                $result = $this->api->checkUpdate($module->slug, $module->version);
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

        return response()->json([
            'success' => true,
            'updates' => $updates,
            'total' => $modules->count(),
            'update_count' => count($updates),
        ]);
    }

    /* ===================================================================
     *  فاز ۱۳۳ — بررسی نسخه جدید «یک» ماژول (مودال آپدیت صفحهٔ نصب‌شده‌ها)
     *
     *  جریان کاربر: کلیک روی «بررسی نسخه جدید» در مودال آپدیت → اتصال به API
     *  بازار → اگر نسخهٔ جدید بود، دکمه به «بروزرسانی» تبدیل می‌شود.
     *
     *  پاسخ متا (نام/تصویر) اختیاری است: شکستِ getPackage نباید جلوی
     *  نتیجهٔ قطعی checkUpdate را بگیرد (تصویر/نام از کش بازار جایگزین می‌شود).
     * =================================================================== */
    public function checkVersion(string $slug): JsonResponse
    {
        $installed = InstalledModule::with('cache')->where('slug', $slug)->first();

        if (! $installed) {
            return response()->json([
                'success' => false,
                'message' => 'ماژول نصب نشده است.',
            ], 404);
        }

        try {
            $check = $this->api->checkUpdate($slug, $installed->version);

            $hasUpdate = (bool) ($check['has_update'] ?? false);
            $latestVersion = $hasUpdate
                ? (string) ($check['latest_version'] ?? $installed->version)
                : $installed->version;

            // متای جانبی (نام/تصویر) — fail-safe
            $meta = null;
            try {
                $fresh = $this->api->getPackage($slug);
                $fresh = $fresh['data'] ?? $fresh;
                $meta = [
                    'name'          => $fresh['name'] ?? null,
                    'thumbnail_url' => $fresh['thumbnail_url'] ?? ($fresh['thumbnail'] ?? null),
                ];
            } catch (\Throwable $metaError) {
                Log::info('Update modal: package meta fetch skipped', [
                    'slug'  => $slug,
                    'error' => $metaError->getMessage(),
                ]);
            }

            return response()->json([
                'success'         => true,
                'has_update'      => $hasUpdate,
                'current_version' => $installed->version,
                'latest_version'  => $latestVersion,
                'changelog'       => $check['changelog'] ?? null,
                'package'         => $meta,
            ]);
        } catch (RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /* ===================================================================
     *  Private Helpers
     * =================================================================== */
    private function dispatchInstall(string $slug, ?string $version, ?string $licenseKey, ?int $pricingPlanId): JsonResponse
    {
        // برای پکیج رایگان، licenseKey برابر null است و download_token مستقیم از API گرفته می‌شود
        $callbackUrl = route(config('packages.payment.callback_route'));
        $purchase = $this->api->createPurchase($slug, $callbackUrl, auth('adminPanel')->id(), $pricingPlanId);
        $licenseKey = $purchase['license_key'] ?? null;
        try {
            // برای پکیج رایگان، ابتدا یک درخواست "purchase" آزاد به API می‌زنیم
            if (! $licenseKey) {
                $callbackUrl = route(config('packages.payment.callback_route'));
                $purchase = $this->api->createPurchase($slug, $callbackUrl, auth('adminPanel')->id(), $pricingPlanId);
                $licenseKey = $purchase['license_key'] ?? null;
            }

            InstallPackageJob::dispatch(
                $slug,
                $licenseKey,
                auth('adminPanel')->id(),
                null, // purchaseId در callback پر می‌شود
                null  // downloadToken از داخل job گرفته می‌شود
            );

            // برای پکیج رایگان، redirect_url برنمی‌گردونیم تا JS progress bar رو نشون بده
            return response()->json([
                'success' => true,
                'message' => 'پکیج در صف نصب قرار گرفت.',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

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

    /**
     * بررسی خرید قبلی و اعتبار لایسنس (قبل از باز شدن مودال نصب)
     */
    public function checkPurchase(string $slug)
    {
        $subscriptionNotice = null;

        // ★ اولویت ۱: اشتراک فعال — از API (داده تازه، بدون کش)
        try {
            $apiPackage = $this->api->getPackage($slug);
            $apiPackage = $apiPackage['data'] ?? $apiPackage;
            $subscription = $apiPackage['subscription'] ?? null;

            if ($subscription && ($subscription['is_free_with_subscription'] ?? false)) {
                $expiresAt = $subscription['subscription_expires_at'] ?? null;
                $active = true;
                if (! empty($expiresAt)) {
                    try {
                        $active = Carbon::parse($expiresAt)->isFuture();
                    } catch (\Throwable $e) {
                        $active = false;
                    }
                }

                if ($active) {
                    return response()->json([
                        'purchased' => true,   // برای سازگاری با JS فعلی
                        'valid' => true,
                        'mode' => 'subscription',
                        'plan_name' => $subscription['plan_name'] ?? null,
                        'plan_slug' => $subscription['plan_slug'] ?? null,
                        'expires_at' => $expiresAt,
                        'days_remaining' => $subscription['days_remaining'] ?? null,
                        'license_key' => $subscription['license_key'] ?? null,
                    ]);
                }
                $subscriptionNotice = 'اشتراک شما منقضی شده است؛ برای نصب، پلن موردنظر را انتخاب کنید.';
            }
        } catch (\Throwable $e) {
            // API در دسترس نیست → ادامه با بررسی خرید محلی
        }

        // اولویت ۲: خرید مستقیم قبلی
        $purchase = PackagePurchase::query()
            ->where('package_slug', $slug)
            ->where('status', PackagePurchase::STATUS_PAID)
            ->whereNotNull('license_key')
            ->orderByDesc('paid_at')
            ->first();

        if (! $purchase) {
            return response()->json(['purchased' => false]);
        }

        try {
            $verify = $this->api->verifyLicense($slug, $purchase->license_key);
        } catch (\Throwable $e) {
            return response()->json([
                'purchased' => true,
                'valid' => false,
                'message' => 'بررسی آنلاین لایسنس ناموفق بود. لطفاً مجدد تلاش کنید.',
            ]);
        }

        if (! ($verify['valid'] ?? false) ||
            ! $this->licenseHasTime($verify['expires_at'] ?? null, $purchase->license_expires_at)) {
            return response()->json([
                'purchased' => true,
                'valid' => false,
                'mode' => 'purchase',
                'message' => $verify['message']
                    ?? ($subscriptionNotice ?? 'لایسنس قبلی شما منقضی شده است. برای نصب مجدد، ابتدا پلن را انتخاب و تمدید کنید.'),
            ]);
        }

        return response()->json([
            'purchased' => true,
            'valid' => true,
            'mode' => 'purchase',
            'expires_at' => $verify['expires_at'] ?? optional($purchase->license_expires_at)->toDateString(),
            'version' => $verify['version'] ?? null,
        ]);
    }

    /**
     * نصب مجدد پکیج با لایسنس قبلی (بدون پرداخت)
     */
    private function reinstallWithExistingLicense(string $slug)
    {
        $purchase = PackagePurchase::query()
            ->where('package_slug', $slug)
            ->where('status', PackagePurchase::STATUS_PAID)
            ->whereNotNull('license_key')
            ->orderByDesc('paid_at')
            ->first();

        if (! $purchase) {
            return response()->json([
                'success' => false,
                'needs_payment' => true,
                'message' => 'خرید معتبری برای این پکیج یافت نشد.',
            ]);
        }

        try {
            $verify = $this->api->verifyLicense($slug, $purchase->license_key);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'بررسی لایسنس ناموفق بود: '.$e->getMessage(),
            ]);
        }

        if (! ($verify['valid'] ?? false)) {
            return response()->json([
                'success' => false,
                'needs_payment' => true,
                'message' => $verify['message'] ?? 'لایسنس شما منقضی شده است. برای ادامه، یکی از پلن‌ها را انتخاب کنید.',
            ]);
        }

        InstallPackageJob::dispatch(
            $slug,
            $purchase->license_key,          // متد install() سرویس خودش verifyLicense و download_token را می‌گیرد
            $purchase->admin_id,
            $purchase->id,
            $verify['download_token'] ?? null
        );

        Log::info('Package reinstall with existing license', [
            'slug' => $slug,
            'purchase_id' => $purchase->id,
        ]);

        return response()->json(['success' => true]);
    }

    /**
     * آیا لایسنس هنوز زمان (اعتبار) دارد؟
     * خالی = لایسنس مادام‌العمر (duration_months=0) → دارای اعتبار
     */
    private function licenseHasTime($apiExpiresAt = null, $dbExpiresAt = null): bool
    {
        $date = $apiExpiresAt ?: $dbExpiresAt;

        if (empty($date)) {
            return true; // نامحدود
        }

        try {
            return Carbon::parse($date)->isFuture();
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * نصب پکیج با اشتراک فعال (بدون پرداخت) — اعتبار همیشه سمت سرور چک می‌شود
     */
    private function installWithSubscription(string $slug, Request $request)
    {
        try {
            $package = $this->api->getPackage($slug);
            $package = $package['data'] ?? $package;
        } catch (RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => 'بررسی اشتراک ناموفق بود: '.$e->getMessage(),
            ], 500);
        }

        $subscription = $package['subscription'] ?? null;
        $eligible = false;

        if ($subscription && ($subscription['is_free_with_subscription'] ?? false)) {
            $expiresAt = $subscription['subscription_expires_at'] ?? null;
            if (empty($expiresAt)) {
                $eligible = true; // اشتراک نامحدود
            } else {
                try {
                    $eligible = Carbon::parse($expiresAt)->isFuture();
                } catch (\Throwable $e) {
                    $eligible = false;
                }
            }
        }

        if (! $eligible) {
            return response()->json([
                'success' => false,
                'needs_payment' => true,
                'message' => 'اشتراک فعال شما این پکیج را پوشش نمی‌دهد یا منقضی شده است. برای نصب، پلن را انتخاب کنید.',
            ]);
        }

        $licenseKey = $subscription['license_key'] ?? null;
        $downloadToken = null;

        // ★★★ لایسنس صادرنشده (مشتری پکیج را جداگانه نخریده، فقط اشتراک دارد)
        //     → درخواست به purchase → مسیر اشتراکِ API بدون درگاه لایسنس صادر می‌کند
        if (empty($licenseKey)) {
            try {
                $callbackUrl = route(config('packages.payment.callback_route'));
                $purchase = $this->api->createPurchase(
                    $slug,
                    $callbackUrl,
                    $request->user('adminPanel')->id ?? null,
                    null   // pricing_plan_id لازم نیست — مسیر اشتراک قبل از چک پلن اجرا می‌شود
                );
            } catch (RuntimeException $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'صدور لایسنس از اشتراک ناموفق بود: '.$e->getMessage(),
                ], 500);
            }

            // اطمینان: پاسخ واقعاً از مسیر اشتراک باشد، نه شروع یک خرید پولی
            if (empty($purchase['via_subscription']) || empty($purchase['license_key'])) {
                return response()->json([
                    'success' => false,
                    'needs_payment' => true,
                    'message' => 'دسترسی به این پکیج از طریق اشتراک ممکن نشد. لطفاً پلن موردنظر را خریداری کنید.',
                ]);
            }

            $licenseKey = $purchase['license_key'];
            $downloadToken = $purchase['download_token'] ?? null;
        }
        $version = $package['latest_version']['version']
            ?? collect($package['active_versions'] ?? [])->first()['version']
            ?? null;
        InstalledModule::markPending(
            $slug,
            $package['name'] ?? null,
            $version,
            $licenseKey
        );

        InstallPackageJob::dispatch(
            $slug,
            $licenseKey,
            $request->user('adminPanel')->id ?? null,
            null,
            $downloadToken   // ★ اگر token گرفتیم، install() مرحله verifyLicense را skip می‌کند
        );

        Log::info('Package install via subscription', [
            'slug' => $slug,
            'had_license' => ! empty($subscription['license_key']),
            'issued_new' => $downloadToken !== null || ! empty($purchase['license_key'] ?? null),
        ]);

        return response()->json(['success' => true]);
    }
}
