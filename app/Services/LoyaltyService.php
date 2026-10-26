<?php

namespace Modules\CustomerClub\Services;

use App\Models\User;
use App\Models\WalletHistory;
use Illuminate\Support\Facades\DB;
use Modules\CustomerClub\Models\ClubSetting;
use Modules\CustomerClub\Models\LoyaltyPoint;
use Modules\CustomerClub\Models\LoyaltyRule;

/**
 * موتور امتیاز وفاداری — مدل «لات» با کسر FIFO.
 * --------------------------------------------------------------------------
 *  • award        : افزودن لات (idempotent با یگانهٔ منبع) — لاتِ فاسدپذیر
 *                   یا بدون انقضا؛ انقضای سراسری از تنظیمات خوانده می‌شود.
 *  • balance      : موجودی = SUM(remaining) روی لات‌های زنده.
 *  • deduct       : کسر FIFO (نزدیک‌ترین انقضا اول، NULLها آخر) + یک سطر
 *                   audit منفی در دفتر کل.
 *  • redeemToWallet: تبدیل امتیاز به کیف پول هسته (نرخ/حداقل قابل تنظیم)
 *                   با WalletHistory — همان زنجیرهٔ audit که همهٔ هسته دارد.
 *  • expireDue    : انقضای لات‌های سررسیده + سطر audit.
 *
 * تمام عملیات تغییرموجود داخل DB::transaction با lockForUpdate انجام می‌شود
 * (ضد رقابت همزمانی روی موجودی).
 */
class LoyaltyService
{
    /* ================= خواندن ================= */

    /** موجودی امتیاز زندهٔ کاربر */
    public static function balance(int $userId): int
    {
        return LoyaltyPoint::balanceOf($userId);
    }

    /** امتیاز ثابت یک رویداد از قانون فعال آن (۰ = خاموش/تعریف‌نشده) */
    public static function fixedPointsFor(string $event): int
    {
        $rule = LoyaltyRule::for($event);

        if (!$rule || !$rule->is_active) {
            return 0;
        }

        return (int) ($rule->points ?? 0);
    }

    /** آیا موتور امتیاز از تنظیمات روشن است؟ (الزام شخصی‌سازی) */
    public static function engineEnabled(): bool
    {
        return ClubSetting::enabled('club_enabled')
            && ClubSetting::enabled('loyalty_enabled');
    }

    /* ================= نوشتن ================= */

    /**
     * افزودن لات امتیاز.
     *
     * @param  User|int  $user
     * @param  int       $points            امتیاز (فقط مثبت)
     * @param  string    $type              نوع سطر (award|manual|adjust|...)
     * @param  string    $description       توضیح دفتر کل
     * @param  ?string   $sourceType        نوع منبع (order|user|review|...)
     * @param  ?int      $sourceId          شناسهٔ منبع — با sourceType یگانه؛
     *                                      تکرار ⇒ null (idempotent)
     * @param  ?\Carbon\CarbonInterface $expiresAt  انقضای صریح لات
     * @param  bool      $applyGlobalExpiry اگر true و expiresAt خالی، از
     *                                      تنظیمات points_expiry_days پیروی کن
     * @param  ?string   $event             برچسب رویداد (purchase|register|…)
     * @return ?LoyaltyPoint لات ساخته‌شده یا null (تکراری/نامعتبر)
     */
    public static function award(
        $user,
        int $points,
        string $type = LoyaltyPoint::TYPE_AWARD,
        string $description = '',
        ?string $sourceType = null,
        ?int $sourceId = null,
        ?\Carbon\CarbonInterface $expiresAt = null,
        bool $applyGlobalExpiry = true,
        ?string $event = null,
    ): ?LoyaltyPoint {
        if ($points <= 0) {
            return null;
        }

        $userId = $user instanceof User ? $user->id : (int) $user;

        return DB::transaction(function () use ($userId, $points, $type, $description, $sourceType, $sourceId, $expiresAt, $applyGlobalExpiry, $event) {
            // idempotency: یک منبع = یک لات (حتی اگر مصرف/منقضی شده باشد)
            if ($sourceType !== null && $sourceId !== null) {
                $exists = LoyaltyPoint::query()
                    ->where('user_id', $userId)
                    ->where('source_type', $sourceType)
                    ->where('source_id', $sourceId)
                    ->lockForUpdate()
                    ->exists();

                if ($exists) {
                    return null;
                }
            }

            // انقضای سراسری (اگر لات انقضای صریح ندارد و ادمین خاموش نکرده)
            if ($expiresAt === null && $applyGlobalExpiry) {
                $days = (int) ClubSetting::get('points_expiry_days', '0');

                if ($days > 0) {
                    $expiresAt = now()->addDays($days);
                }
            }

            return LoyaltyPoint::query()->create([
                'user_id'     => $userId,
                'points'      => $points,
                'remaining'   => $points,
                'type'        => $type,
                'event'       => $event ?? $type,
                'source_type' => $sourceType,
                'source_id'   => $sourceId,
                'description' => $description,
                'expires_at'  => $expiresAt,
            ]);
        });
    }

    /**
     * کسر امتیاز با FIFO + یک سطر audit منفی.
     *
     * @param  int    $userId
     * @param  int    $points      مبلغ کسر (مثبت)
     * @param  string $type        نوع audit (redeem|adjust|…)
     * @param  string $sourceType  برچسب منبع کسر
     * @param  string $description توضیح
     * @param  ?int   $sourceId    شناسهٔ منبع کسر (اختیاری)
     * @return bool آیا کسر انجام شد
     */
    public static function deduct(
        int $userId,
        int $points,
        string $type = LoyaltyPoint::TYPE_REDEEM,
        string $sourceType = LoyaltyPoint::TYPE_REDEEM,
        string $description = '',
        ?int $sourceId = null,
    ): bool {
        if ($points <= 0) {
            return false;
        }

        return DB::transaction(function () use ($userId, $points, $type, $sourceType, $description, $sourceId) {
            $lots = LoyaltyPoint::query()
                ->where('user_id', $userId)
                ->where('remaining', '>', 0)
                ->fifo()
                ->lockForUpdate()
                ->get();

            $available = (int) $lots->sum('remaining');

            if ($available < $points) {
                return false; // بدون سطر audit — موجودی کافی نیست
            }

            $left = $points;

            foreach ($lots as $lot) {
                if ($left <= 0) {
                    break;
                }

                $take = min($left, $lot->remaining);
                $lot->decrement('remaining', $take);
                $left -= $take;
            }

            LoyaltyPoint::query()->create([
                'user_id'     => $userId,
                'points'      => -$points,
                'remaining'   => 0,
                'type'        => $type,
                'event'       => $type,
                'source_type' => $sourceType,
                'source_id'   => $sourceId,
                'description' => $description,
                'expires_at'  => null,
            ]);

            return true;
        });
    }

    /**
     * تبدیل امتیاز به کیف پول هسته.
     *
     * @param  User $user
     * @param  int  $points
     * @return array{0: bool, 1: string, 2: ?WalletHistory}
     */
    public static function redeemToWallet(User $user, int $points): array
    {
        if (!self::engineEnabled()) {
            return [false, 'امتیاز وفاداری در حال حاضر غیرفعال است.', null];
        }

        if ($points <= 0) {
            return [false, 'مقدار امتیاز نامعتبر است.', null];
        }

        $min = max(1, (int) ClubSetting::get('redeem_min_points', '100'));

        if ($points < $min) {
            return [false, "حداقل امتیاز قابل تبدیل {$min} امتیاز است.", null];
        }

        $rate = max(1, (int) ClubSetting::get('redeem_rate_toman', '1000'));

        return DB::transaction(function () use ($user, $points, $rate) {
            if (self::balance($user->id) < $points) {
                return [false, 'موجودی امتیاز شما کافی نیست.', null];
            }

            $amount = $points * $rate;

            if (!self::deduct(
                $user->id,
                $points,
                LoyaltyPoint::TYPE_REDEEM,
                LoyaltyPoint::TYPE_REDEEM,
                "تبدیل {$points} امتیاز به ".number_format($amount).' تومان اعتبار کیف پول'
            )) {
                return [false, 'موجودی امتیاز شما کافی نیست.', null];
            }

            $wallet = $user->getWallet();

            $wallet->increment('balance', $amount);

            $history = WalletHistory::query()->create([
                'wallet_id'   => $wallet->id,
                'type'        => 'deposit',
                'amount'      => $amount,
                'source'      => 'user',
                'status'      => 'success',
                'description' => "باشگاه مشتریان — تبدیل ".number_format($points)." امتیاز به اعتبار",
            ]);

            return [true, number_format($points)." امتیاز با موفقیت به ".number_format($amount)." تومان اعتبار کیف پول تبدیل شد.", $history];
        });
    }

    /**
     * انقضای لات‌های سررسیده — خروجی: مجموع امتیازِ منقضی‌شده.
     * (کامند شبانه ۰۳:۱۵ + قابل فراخوانی از پنل)
     */
    public static function expireDue(): int
    {
        return (int) DB::transaction(function () {
            $dueLots = LoyaltyPoint::query()
                ->where('remaining', '>', 0)
                ->whereNotNull('expires_at')
                ->where('expires_at', '<=', now())
                ->lockForUpdate()
                ->get();

            $total = 0;

            foreach ($dueLots as $lot) {
                $amount = (int) $lot->remaining;

                $lot->update(['remaining' => 0]);

                LoyaltyPoint::query()->create([
                    'user_id'     => $lot->user_id,
                    'points'      => -$amount,
                    'remaining'   => 0,
                    'type'        => LoyaltyPoint::TYPE_EXPIRE,
                    'event'       => LoyaltyPoint::TYPE_EXPIRE,
                    'source_type' => null,
                    'source_id'   => null,
                    'description' => "انقضای {$amount} امتیاز (لات #{$lot->id})",
                    'expires_at'  => null,
                ]);

                $total += $amount;
            }

            return $total;
        });
    }

    /** محاسبهٔ امتیاز خرید یک سفارش طبق قانون purchase (هر amount_step تومان = ۱ امتیاز) */
    public static function purchasePointsForOrder($order): int
    {
        $rule = LoyaltyRule::for(LoyaltyRule::EVENT_PURCHASE);

        if (!$rule || !$rule->is_active) {
            return 0;
        }

        $step = max(1, (int) ($rule->amount_step ?? 0));

        if ($step < 1) {
            return 0;
        }

        $total = max(0, (int) ($order->price ?? 0)); // مبلغ کل سفارش پرداخت‌شده

        return intdiv($total, $step);
    }
}
