<?php

namespace Modules\CustomerClub\Console\Commands;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Modules\CustomerClub\Models\ClubSetting;
use Modules\CustomerClub\Models\LoyaltyPoint;
use Modules\CustomerClub\Models\LoyaltyRule;
use Modules\CustomerClub\Services\LoyaltyService;

/**
 * پاداش سالانهٔ تولد مشتریان — زمان‌بندی روزانه ۰۷:۰۰.
 *
 * فرمت users.birth_date در این فروشگاه «شمسی» است (YYYY/MM/DD از فرم ادمین).
 * برای تطبیق با امروز، ماه/روز تولدِ نوشته‌شده هم با «امروزِ شمسی» و هم با
 * «امروزِ میلادی» مقایسه می‌شود (پوشش هر دو حالت دادهٔ قدیمی/میلادی) — با
 * مبدل جلالی خودکفا (بدون وابستگی پکیج بیرونی).
 *
 * idempotent سالانه: یگانهٔ (user, birthday, سالِ جاری) در دفتر امتیاز.
 */
class BirthdayPointsCommand extends Command
{
    protected $signature   = 'customerclub:birthday-points';
    protected $description = 'پاداش امتیاز تولد به مشتریانی که امروز تولدشان است (سالانه یک‌بار)';

    public function handle(): int
    {
        if (!LoyaltyService::engineEnabled()) {
            $this->info('موتور امتیاز غیرفعال است — پاداش تولد اجرا نشد.');

            return self::SUCCESS;
        }

        $rule = LoyaltyRule::for(LoyaltyRule::EVENT_BIRTHDAY);

        if (!$rule->is_active) {
            $this->info('قانون امتیاز تولد غیرفعال است.');

            return self::SUCCESS;
        }

        $points = (int) ($rule->points ?? 0);

        if ($points <= 0) {
            $this->info('امتیاز قانون تولد صفر است.');

            return self::SUCCESS;
        }

        // ماه/روز امروز در هر دو تقویم
        $todayGregorian = [now()->format('n'), now()->format('j')];
        [$jy, $jm, $jd] = static::gregorianToJalali(
            (int) now()->format('Y'), (int) now()->format('n'), (int) now()->format('j')
        );
        $todayJalali = [$jm, $jd];

        $year = (int) now()->format('Y'); // یکتایی سالانه (میلادی)

        $users = User::query()
            ->whereNotNull('birth_date')
            ->where('birth_date', '!=', '')
            ->get();

        $awarded = 0;

        foreach ($users as $user) {
            $md = static::monthDayOf($user->birth_date);

            if ($md === null) {
                continue;
            }

            if ($md !== $todayGregorian && $md !== $todayJalali) {
                continue;
            }

            $created = LoyaltyService::award(
                $user->id,
                $points,
                LoyaltyPoint::TYPE_AWARD,
                "تولدت مبارک! پاداش سالانهٔ باشگاه مشتریان",
                'birthday',
                $year,
                null,
                true,
                LoyaltyPoint::EVENT_BIRTHDAY
            );

            if ($created) {
                $awarded++;
            }
        }

        $this->info("✅ پاداش تولد به {$awarded} مشتری داده شد.");

        return self::SUCCESS;
    }

    /** استخراج [ماه، روز] از فرمت‌های YYYY/MM/DD یا Y-m-d (شمسی یا میلادی) */
    private static function monthDayOf(?string $birthDate): ?array
    {
        if (!preg_match('/(\d{1,4})\D+(\d{1,2})\D+(\d{1,2})/', (string) $birthDate, $m)) {
            return null;
        }

        $month = (int) $m[2];
        $day   = (int) $m[3];

        if ($month < 1 || $month > 12 || $day < 1 || $day > 31) {
            return null;
        }

        return [$month, $day];
    }

    /* ================= مبدل جلالی خودکفا (الگوریتم استاندارد عمومی) ================= */

    public static function gregorianToJalali(int $gy, int $gm, int $gd): array
    {
        $g_d_m = [0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334];
        $jy    = ($gy <= 1600) ? 0 : 979;
        $gy   -= ($gy <= 1600) ? 621 : 1600;
        $gy2  = ($gm > 2) ? ($gy + 1) : $gy;
        $days = (365 * $gy) + intdiv($gy2 + 3, 4) - intdiv($gy2 + 99, 100)
            + intdiv($gy2 + 399, 400) - 80 + $gd + $g_d_m[$gm - 1];
        $jy   += 33 * intdiv($days, 12053);
        $days %= 12053;
        $jy   += 4 * intdiv($days, 1461);
        $days %= 1461;

        if ($days > 365) {
            $jy  += intdiv($days - 1, 365);
            $days = ($days - 1) % 365;
        }

        $jm = ($days < 186) ? 1 + intdiv($days, 31) : 7 + intdiv($days - 186, 30);
        $jd = 1 + (($days < 186) ? ($days % 31) : (($days - 186) % 30));

        return [$jy, $jm, $jd];
    }
}
