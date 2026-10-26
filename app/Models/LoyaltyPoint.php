<?php

namespace Modules\CustomerClub\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * لات امتیاز وفاداری.
 *
 * سطرهای مثبت = لات کسب امتیاز (remaining = موجودی زندهٔ همان لات).
 * سطرهای منفی = سطر audit دفتر کل (redeem/expire) با remaining = 0.
 */
class LoyaltyPoint extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    /* ===== انواع سطر ===== */
    public const TYPE_AWARD   = 'award';   // کسب خودکار از رویدادها
    public const TYPE_MANUAL  = 'manual';  // تست/سرویس
    public const TYPE_ADJUST  = 'adjust';  // تنظیم دستی ادمین
    public const TYPE_REDEEM  = 'redeem';  // کسر (audit منفی)
    public const TYPE_EXPIRE  = 'expire';  // انقضا (audit منفی)

    /* ===== رویدادها ===== */
    public const EVENT_PURCHASE      = 'purchase';
    public const EVENT_REGISTER      = 'register';
    public const EVENT_REVIEW        = 'review';
    public const EVENT_BIRTHDAY      = 'birthday';
    public const EVENT_REFERRAL_TIER = 'referral_tier';

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    /** لات‌های زنده (باقی‌ماندهٔ مثبت) */
    public function scopeLots(Builder $query): Builder
    {
        return $query->where('remaining', '>', 0);
    }

    /** ترتیب FIFO کسر: نزدیک‌ترین انقضا اول؛ NULLها آخر؛ سپس قدیمی‌تر */
    public function scopeFifo(Builder $query): Builder
    {
        return $query->orderByRaw('expires_at IS NULL ASC')
            ->orderBy('expires_at')
            ->orderBy('id');
    }

    /** موجودی زندهٔ کاربر = مجموع باقی‌ماندهٔ لات‌ها */
    public static function balanceOf(int $userId): int
    {
        return (int) static::query()
            ->where('user_id', $userId)
            ->where('remaining', '>', 0)
            ->sum('remaining');
    }
}
