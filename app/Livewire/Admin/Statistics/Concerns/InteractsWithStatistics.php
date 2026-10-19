<?php

namespace App\Livewire\Admin\Statistics\Concerns;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Morilog\Jalali\Jalalian;

/**
 * ابزار مشترک صفحات آمار (پورت ۱:۱ متدهای private کنترلر قدیمی StatisticsController)
 *
 * - resolvePeriod / parseJalaliDate / buildViewsChart / buildViewersChart
 *   دقیقاً با همان منطق نسخه‌ی HTTP بازسازی شده‌اند تا نتایج یکسان بماند.
 * - تریت‌های آماری (ViewStatisticsTrait و…) با Request ساختگی صدا زده می‌شوند
 *   تا همان مسیر کد قدیمی (validateDates + getPeriodData) اجرا شود.
 */
trait InteractsWithStatistics
{
    use AuthorizesRequests;

    /**
     * تریت‌های آماری قدیمی (ViewStatisticsTrait و...) داخل متدهایشان
     * $this->authorize() صدا می‌زنند که کاربرِ گارد پیش‌فرض (web) را چک می‌کند
     * و در بستر Livewire باعث ۴۰۳ می‌شود. سطح دسترسی در mount و اکشن‌های
     * خودِ کامپوننت با گارد adminPanel چک می‌شود، پس اینجا no-op است.
     */
    public function authorize($ability, $arguments = [])
    {
        return null;
    }

    /**
     * ساخت Request ساختگی برای تریت‌های آماری قدیمی (متدها با Request کار می‌کنند)
     */
    protected function statisticsRequest(array $params): Request
    {
        return Request::create('/', 'GET', $params);
    }

    /**
     * پورت resolvePeriod / resolveViewersPeriod کنترلر قدیمی.
     * خروجی: [period نهایی, from, to]
     */
    protected function resolveStatisticsPeriod(string $period, ?string $fromDate = null, ?string $toDate = null): array
    {
        $period = in_array($period, ['daily', 'weekly', 'monthly', 'yearly', 'custom']) ? $period : 'daily';

        $from = match ($period) {
            'weekly'  => now()->subDays(6)->startOfDay(),
            'monthly' => now()->subDays(29)->startOfDay(),
            'yearly'  => now()->subDays(364)->startOfDay(),
            default   => now()->startOfDay(),
        };
        $to = now();

        if ($period === 'custom') {
            $from = $this->parseJalaliDate($fromDate);
            $to   = $this->parseJalaliDate($toDate);

            // بازه نامعتبر → برگرد به حالت روزانه (عیناً مثل قدیمی)
            if (! $from || ! $to || $from->gt($to)) {
                $period = 'daily';
                [$from, $to] = [now()->startOfDay(), now()];
            } else {
                $from = $from->startOfDay();
                $to   = $to->endOfDay();
            }
        }

        return [$period, $from, $to];
    }

    /**
     * پورت parseJalaliDate کنترلر قدیمی — تبدیل تاریخ شمسی (خروجی دیت‌پیکر) به Carbon.
     * پشتیبانی از ارقام فارسی/عربی + هر دو پکیج Verta / Morilog Jalali.
     */
    protected function parseJalaliDate(?string $value): ?Carbon
    {
        if (! $value || ! trim($value)) {
            return null;
        }

        // ارقام فارسی/عربی → انگلیسی + یکسان‌سازی جداکننده
        $value = strtr(trim($value), [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
            '-' => '/',
        ]);

        // Verta
        if (class_exists(\Verta::class)) {
            try {
                if (method_exists(\Verta::class, 'parseJalali')) {
                    return \Verta::parseJalali($value)->datetime();
                }
                [$y, $m, $d] = array_pad(preg_split('/[\/]/', $value), 3, 1);

                return \Verta::createJalali((int) $y, (int) $m, (int) $d)->datetime();
            } catch (\Throwable $e) {
            }
        }

        // Morilog Jalali
        if (class_exists(\Morilog\Jalali\Jalalian::class)) {
            try {
                return \Morilog\Jalali\Jalalian::fromFormat('Y/m/d', $value)->toCarbon();
            } catch (\Throwable $e) {
            }
        }

        return null;
    }

    /**
     * پورت buildViewsChart کنترلر قدیمی — گروه‌بندی سمت دیتابیس.
     * روزانه تا ۳۱ روز، بعد از آن ماهانه.
     * (DATE_FORMAT مخصوص MySQL است؛ روی SQLite از strftime استفاده می‌شود — همان فرمت %Y-%m-%d / %Y-%m)
     */
    protected function buildViewsChart($baseQuery, Carbon $from, Carbon $to): array
    {
        $months = [1 => 'فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور',
            'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند'];

        $daily  = $from->copy()->startOfDay()->diffInDays($to->copy()->startOfDay()) <= 31;
        $format = $daily ? '%Y-%m-%d' : '%Y-%m';

        // مهم: clone کردن baseQuery و حذف هرگونه ORDER BY
        $query = clone $baseQuery;
        $query->getQuery()->orders = [];

        $dateFn = $query->getQuery()->getConnection()->getDriverName() === 'sqlite'
            ? 'strftime'
            : 'DATE_FORMAT';

        $rows = $query
            ->selectRaw("{$dateFn}(created_at, '{$format}') AS period_key, COUNT(*) AS cnt")
            ->groupBy('period_key')
            ->orderBy('period_key', 'asc')
            ->pluck('cnt', 'period_key');

        $chart = [];
        $start = $daily ? $from->copy()->startOfDay()   : $from->copy()->startOfMonth();
        $end   = $daily ? $to->copy()->startOfDay()     : $to->copy()->startOfMonth();
        $step  = $daily ? '1 day' : '1 month';

        foreach (CarbonPeriod::create($start, $step, $end) as $date) {
            $key = $date->format($daily ? 'Y-m-d' : 'Y-m');
            $jalaliDate = jdate($date);

            if ($daily) {
                $label = (string) $jalaliDate->getDay();
                $title = substr((string) $jalaliDate, 0, 10);
            } else {
                $label = $months[(int) $jalaliDate->getMonth()] ?? '';
                $title = $label . ' ' . $jalaliDate->getYear();
            }
            $chart[] = ['label' => $label, 'title' => $title, 'total' => (int) ($rows[$key] ?? 0)];
        }

        return $chart;
    }

    /**
     * پورت buildViewersChart کنترلر قدیمی — گروه‌بندی روی کالکشن ردیف‌ها.
     */
    protected function buildViewersChart($rows, Carbon $from, Carbon $to): array
    {
        $months = [1 => 'فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور',
            'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند'];
        $chart  = [];

        if ($from->diffInDays($to) <= 31) {
            $grouped = $rows->groupBy(fn ($v) => $v->created_at->format('Y-m-d'));

            foreach (CarbonPeriod::create($from->copy()->startOfDay(), '1 day', $to->copy()->startOfDay()) as $day) {
                $jalaliDate = jdate($day);
                $chart[] = [
                    'label' => (string) $jalaliDate->getDay(),
                    'title' => substr((string) $jalaliDate, 0, 10),
                    'total' => $grouped->get($day->format('Y-m-d'))?->count() ?? 0,
                ];
            }
        } else {
            $grouped = $rows->groupBy(fn ($v) => $v->created_at->format('Y-m'));

            foreach (CarbonPeriod::create($from->copy()->startOfMonth(), '1 month', $to->copy()->startOfMonth()) as $month) {
                $jalaliDate = jdate($month);
                $chart[] = [
                    'label' => $months[(int) $jalaliDate->getMonth()] ?? '',
                    'title' => $months[(int) $jalaliDate->getMonth()] . ' ' . $jalaliDate->getYear(),
                    'total' => $grouped->get($month->format('Y-m'))?->count() ?? 0,
                ];
            }
        }

        return $chart;
    }

    /* ---------------- فرمت‌کننده‌های نمایش (اعداد فارسی) ---------------- */

    public static function jdate($date, string $format = 'Y/m/d'): string
    {
        if (! $date) {
            return '—';
        }

        return Jalalian::fromDateTime($date)->format($format);
    }

    public static function fa($value): string
    {
        $fa = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];

        return strtr((string) $value, array_combine(range(0, 9), $fa));
    }

    public static function faNum($value): string
    {
        return self::fa(number_format((float) $value));
    }
}
