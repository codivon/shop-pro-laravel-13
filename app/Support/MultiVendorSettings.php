<?php

namespace App\Support;

use App\Models\Seller;

/*
 * لایهٔ مرکزی تنظیمات چندفروشندگی پیشرفته (فاز ۱۰۳)
 *
 * همهٔ کلیدها در جدول options نگهداری می‌شوند (همان مکانیزم استاندارد هسته:
 * option() / option_update() + کش per-language) و از تب «چندفروشندگی» در
 * تنظیمات برنامه‌نویس (/admin/{prefix}/developer) مدیریت می‌شوند.
 *
 * قاعدهٔ طلایی مدیر پروژه: «هر امکانی که اضافه می‌شود از تنظیمات قابل
 * شخصی‌سازی باشد؛ حداقل فعال/غیرفعال کردن» — هر قابلیت فاز ۱۰۳ یک کلید
 * مستقل دارد با گیت واقعی در کد.
 *
 * ⚠️ کلید اصلیِ چندفروشندگی از قبل وجود داشت (فاز ۳۰): multi_vendor_system_status
 * با قرارداد مقدار قدیمی 'true'/'false' و در «تنظیمات دیگر» (Others.php) هم
 * سوییچ دارد. برای سازگاری کامل با تمام خواننده‌های قدیمی (میدل‌ور Seller،
 * سایدبار، Product.php و ...) این کلاس همان کلید را با همان قرارداد می‌نویسد،
 * ولی خواندنش هم 'true' و هم '1' را می‌پذیرد (مقاوم به هر دو قرارداد).
 *
 * قرارداد بقیهٔ کلیدها: '1' روشن / '0' خاموش (هم‌خوان با فازهای ۸۳/۹۹).
 */
class MultiVendorSettings
{
    /** کلید اصلی چندفروشندگی — همان کلید قدیمی فاز ۳۰ (قرارداد 'true'/'false') */
    public const KEY_ENABLED = 'multi_vendor_system_status';

    /* ================= کلید اصلی ================= */

    /**
     * روشن بودن سیستم چندفروشندگی — هم 'true' (قرارداد قدیمی) و هم '1'
     * را می‌پذیرد تا با هر نویسنده‌ای سازگار بماند.
     */
    public static function enabled(): bool
    {
        return in_array(option(self::KEY_ENABLED, 'false'), ['true', '1'], true);
    }

    /** نوشتن کلید اصلی — با قرارداد قدیمی 'true'/'false' تا خواننده‌های فعلی نشکنند */
    public static function setEnabled(bool $on): void
    {
        option_update(self::KEY_ENABLED, $on ? 'true' : 'false');
    }

    /* ================= گیت‌های فاز ۱۰۳ ================= */

    /**
     * تسویهٔ خودکار فروشندگان پس از پرداخت سفارش (هر مسیر پرداخت: درگاه/کیف پول).
     * قابلیت جدید فاز ۱۰۳: پیش از این فقط سفارش‌های «کیف پولی» سهم فروشنده را
     * واریز می‌کردند (payUsingWallet) و سفارش‌های درگاهی هرگز تسویه نمی‌شدند.
     */
    public static function autoSettlement(): bool
    {
        return option('multi_vendor_auto_settlement', '1') === '1';
    }

    /**
     * کمیسیون پیش‌فرض سراسری (درصد) وقتی هیچ دسته‌بندیِ مسیرِ محصول کمیسیون
     * نداشته باشد. null یعنی «بدون پیش‌فرض» = دقیقاً رفتار قبل از این فاز (۰).
     */
    public static function defaultCommission(): ?int
    {
        $raw = trim((string) option('multi_vendor_default_commission', ''));

        if ($raw === '') {
            return null;
        }

        if (! is_numeric($raw)) {
            return null;
        }

        return min(100, max(0, (int) $raw));
    }

    /** کمیسیون اختصاصیِ هر فروشنده (فیلد per-seller در جدول sellers) معتبر باشد؟ */
    public static function sellerOverrideEnabled(): bool
    {
        return option('multi_vendor_seller_commission_override', '1') === '1';
    }

    /** پرداخت دسته‌جمعی تسویهٔ فروشندگان (صفحهٔ «درخواستی‌ها») */
    public static function bulkSettlementEnabled(): bool
    {
        return option('multi_vendor_bulk_settlement', '1') === '1';
    }

    /** کارت آمار تسویه در داشبورد فروشنده */
    public static function dashboardStatsEnabled(): bool
    {
        return option('multi_vendor_dashboard_stats', '1') === '1';
    }

    /**
     * صورت‌حساب تسویهٔ فروشنده (فاز ۱۰۶ — شفافیت مالی): صفحهٔ ردیف‌به‌ردیفِ
     * کمیسیون/سهم هر سفارش در پنل فروشنده + خروجی اکسل. داده (seller_deposits
     * + WalletHistory) از فاز ۱۰۳ موجود است؛ این کلید فقط نمایشش را کنترل می‌کند.
     */
    public static function settlementStatementEnabled(): bool
    {
        return option('multi_vendor_settlement_statement', '1') === '1';
    }

    /**
     * گزارش فروش فروشنده (فاز ۱۰۶-۲): بخش «گزارش فروش» صفحهٔ آمار پنل
     * فروشنده — درآمد زمانی ۳۰ روز، پرفروش‌ترین‌ها و بازدید→سفارش. منبع
     * داده (order_items.seller_id روی سفارش‌های paid + Viewer) از قبل
     * موجود است؛ این کلید فقط بخش فروش را کنترل می‌کند — آمار بازدیدِ
     * همان صفحه (فاز ۱۰۶ الف) مستقل از این کلید سرو می‌شود.
     */
    public static function analyticsEnabled(): bool
    {
        return option('seller_analytics_enabled', '1') === '1';
    }

    /**
     * سیگنال‌های تقاضا (فاز ۱۰۶-۳): «X مشتری منتظر موجودی» (StockNotify) و
     * «X منتظر کاهش قیمت» (PriceAlert) از نگاه فروشنده — صفحهٔ اختصاصی پنل
     * فروشنده + آیتم منو. داده از فاز ۷۸/۸۳ موجود است و فقط ادمین می‌دید؛
     * این کلید نمایشش به فروشنده را کنترل می‌کند (موبایل مشتری ماسک می‌شود).
     */
    public static function demandSignalsEnabled(): bool
    {
        return option('seller_demand_signals', '1') === '1';
    }

    /**
     * حداقل مبلغ درخواست برداشت فروشنده (تومان) — ۰ یعنی بدون حد اضافه
     * (حداقل قدیمی ۱۰٬۰۰۰ تومانی فرم برداشت همیشه معتبر است).
     */
    public static function minWithdrawAmount(): int
    {
        return max(0, (int) option('multi_vendor_withdraw_min_amount', '0'));
    }

    /* ================= منطق کمیسیون ================= */

    /**
     * کمیسیون مؤثر یک فروشنده برای یک آیتم سفارش.
     * ترتیب اولویت: کمیسیون اختصاصی فروشنده > کمیسیون دسته‌بندی (اولین جدِ
     * غیر null در مسیر والد) > پیش‌فرض سراسری > صفر.
     *
     * $categoryInfo خروجی Order::getCategoryCommissionWithParent() است:
     * ['commission' => int, 'category_id' => ?int] — category_id فقط وقتی
     * غیر null است که کمیسیونِ صریحِ یک دسته‌بندی پیدا شده باشد.
     */
    public static function resolveCommission(?int $sellerId, array $categoryInfo): array
    {
        // کمیسیون اختصاصی فروشنده (با کش درون‌ریکوئستی — هر فروشنده یک‌بار از DB)
        if ($sellerId && self::sellerOverrideEnabled()) {
            $override = self::sellerOverride($sellerId);

            if (! is_null($override)) {
                return [
                    'commission' => min(100, max(0, (int) $override)),
                    'category_id' => null,
                    'source' => 'seller',
                ];
            }
        }

        // کمیسیون صریحِ دسته‌بندی (رفتار قبل از این فاز)
        if (! is_null($categoryInfo['category_id'] ?? null)) {
            return [
                'commission' => (int) $categoryInfo['commission'],
                'category_id' => $categoryInfo['category_id'],
                'source' => 'category',
            ];
        }

        // پیش‌فرض سراسری (جدید — فقط وقتی هیچ دسته‌بندیِ صریحی نبود)
        $default = self::defaultCommission();

        if (! is_null($default)) {
            return [
                'commission' => $default,
                'category_id' => null,
                'source' => 'default',
            ];
        }

        return [
            'commission' => (int) ($categoryInfo['commission'] ?? 0),
            'category_id' => null,
            'source' => 'category',
        ];
    }

    /** کش درون‌ریکوئستیِ کمیسیون اختصاصی فروشندگان (جلوگیری از N+1) */
    private static array $sellerOverrideCache = [];

    /** کمیسیون اختصاصی فروشنده — null یعنی «بدون override» */
    public static function sellerOverride(int $sellerId): ?int
    {
        if (! array_key_exists($sellerId, self::$sellerOverrideCache)) {
            self::$sellerOverrideCache[$sellerId] = Seller::query()
                ->whereKey($sellerId)
                ->value('commission');
        }

        $value = self::$sellerOverrideCache[$sellerId];

        return is_null($value) ? null : (int) $value;
    }

    /**
     * خالی‌کردن کش کمیسیون اختصاصی — پس از ذخیرهٔ override و در setUp تست‌ها
     * (استاتیک بین درخواست‌های تست باقی می‌ماند).
     */
    public static function flushCache(): void
    {
        self::$sellerOverrideCache = [];
    }

    /* ================= پیام‌های استاندارد گیت‌ها ================= */

    /** پیام گارد سروری وقتی سیستم چندفروشندگی خاموش است */
    public static function disabledMessage(): string
    {
        return 'سیستم چندفروشندگی از تنظیمات غیرفعال است.';
    }

    /** پیام گارد سروری پرداخت دسته‌جمعی */
    public static function bulkSettlementDisabledMessage(): string
    {
        return 'پرداخت دسته‌جمعی تسویه از تنظیمات چندفروشندگی غیرفعال شده است.';
    }
}
