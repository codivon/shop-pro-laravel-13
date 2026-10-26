<?php

namespace Modules\CustomerClub\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * سطح پاداش معرفی دوستان.
 * رسیدن معرف به min_referrals معرفیِ موفق (دعوت‌شدهٔ دارای خرید پرداخت‌شده)
 * = پاداش یک‌بارمصرف bonus_points (idempotent با یگانهٔ source در ledger).
 */
class ReferralTier extends Model
{
    protected $guarded = ['id'];

    /** سطوح پیش‌فرض (مرجع واحد seed و self-heal) */
    public const DEFAULTS = [
        ['title' => 'برنزی',   'min_referrals' => 1, 'bonus_points' => 50,  'color' => '#b08d57'],
        ['title' => 'نقره‌ای', 'min_referrals' => 3, 'bonus_points' => 150, 'color' => '#9aa2ad'],
        ['title' => 'طلایی',   'min_referrals' => 5, 'bonus_points' => 300, 'color' => '#d4a017'],
    ];

    /** اطمینان از seed سطوح (idempotent — از boot پکیج صدا زده می‌شود) */
    public static function ensureSeeded(): void
    {
        if (static::query()->count() > 0) {
            return;
        }

        foreach (static::DEFAULTS as $tier) {
            static::query()->firstOrCreate(
                ['min_referrals' => $tier['min_referrals']],
                $tier
            );
        }
    }

    /** بهترین سطح برای تعداد معرفی موفق داده‌شده (null = هنوز هیچ سطحی) */
    public static function bestFor(int $successfulReferrals): ?self
    {
        return static::query()
            ->where('min_referrals', '<=', $successfulReferrals)
            ->orderByDesc('min_referrals')
            ->first();
    }
}
