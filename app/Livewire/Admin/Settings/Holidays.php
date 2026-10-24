<?php

namespace App\Livewire\Admin\Settings;

use App\Services\HolidayService;
use Illuminate\Support\Facades\File;
use Livewire\Attributes\Title;
use Livewire\Component;
use Morilog\Jalali\Jalalian;

#[Title('تعطیلات رسمی')]
class Holidays extends Component
{
    /** تعطیلات هر سال (فقط ردیف‌های isHoliday=true مثل سرویس قدیمی) — پر در mount */
    public array $years = [];

    /* ---------------- ویرایشگر سال (پورت updateHolidays قدیمی) ---------------- */

    public int $editorYear = 0;

    /** ردیف‌های ادیتور: [ ['date' => '1405/01/01', 'holidayDesription' => '...'], ... ] */
    public array $rows = [];

    /* ---------------- ابزار «چک تعطیلی» (پورت check قدیمی) ---------------- */

    public string $checkDate = '';

    public ?bool $checkResult = null;

    public ?string $checkDescription = null;

    /* ---------------- ابزار «تبدیل تاریخ» (پورت convertToJalali قدیمی) ---------------- */

    public string $convertDate = '';

    public ?array $convertResult = null;

    public function mount(): void
    {
        // پورت index قدیمی: تعطیلات ۳ سال (جاری تا +۲) از storage/app/holidays/{year}.json
        // فایل نبودن صفحه را نمی‌شکند — حالت خالی نمایش داده می‌شود
        $currentYear = Jalalian::now()->getYear();

        for ($i = 0; $i <= 2; $i++) {
            $year  = $currentYear + $i;
            $holidays = $this->readYearFile($year);

            $this->years[$year] = [
                'year'      => $year,
                'exists'    => $this->yearFileExists($year),
                'holidays'  => $holidays,
                'holidaysOnly' => array_values(array_filter($holidays, fn ($h) => ($h['isHoliday'] ?? false) === true)),
            ];
        }

        $this->editorYear = $currentYear;
        $this->loadEditorRows();
    }

    /* ---------------- خواندن فایل سال ---------------- */

    protected function yearFilePath(int $year): string
    {
        return storage_path("app/holidays/{$year}.json");
    }

    protected function yearFileExists(int $year): bool
    {
        return file_exists($this->yearFilePath($year));
    }

    /**
     * داده کامل فایل یک سال (پورت index/getHolidaysByYear قدیمی)
     */
    protected function readYearFile(int $year): array
    {
        $path = $this->yearFilePath($year);

        if (file_exists($path)) {
            $content = file_get_contents($path);
            $data    = json_decode($content, true);

            return $data['data'] ?? [];
        }

        return [];
    }

    /**
     * بارگذاری ردیف‌های ادیتور برای سال انتخاب‌شده (فقط تعطیلات)
     */
    public function updatedEditorYear(): void
    {
        $this->loadEditorRows();
    }

    protected function loadEditorRows(): void
    {
        $holidays = array_values(array_filter(
            $this->readYearFile($this->editorYear),
            fn ($h) => ($h['isHoliday'] ?? false) === true
        ));

        $this->rows = collect($holidays)
            ->map(fn ($h) => [
                'date'              => (string) ($h['date'] ?? ''),
                'holidayDesription' => (string) ($h['holidayDesription'] ?? ''),
            ])
            ->values()
            ->all();

        if (! $this->rows) {
            $this->rows = [['date' => '', 'holidayDesription' => '']];
        }
    }

    public function addRow(): void
    {
        $this->rows[] = ['date' => '', 'holidayDesription' => ''];
    }

    public function removeRow(int $index): void
    {
        unset($this->rows[$index]);

        $this->rows = array_values($this->rows);

        if (! $this->rows) {
            $this->rows = [['date' => '', 'holidayDesription' => '']];
        }
    }

    /**
     * پورت ۱:۱ updateHolidays قدیمی: اعتبارسنجی + ساخت data + ذخیره JSON + ریفرش سرویس
     * (تایپوی کلید «holidayDesription» عیناً حفظ شده است)
     */
    public function updateYear(): void
    {
        // نرمال‌سازی جداکننده تاریخ دیت‌پیکر پنل (خط تیره) به فرمت فایل‌های JSON (اسلش)
        $this->rows = collect($this->rows)
            ->map(fn ($row) => ['date' => str_replace('-', '/', (string) ($row['date'] ?? '')), 'holidayDesription' => (string) ($row['holidayDesription'] ?? '')])
            ->all();

        $validated = $this->validate([
            'rows'                        => 'present|array',
            'rows.*.date'                 => 'required|string',
            'rows.*.holidayDesription'    => 'nullable|string',
        ], [], [
            'rows.*.date'              => 'تاریخ',
            'rows.*.holidayDesription' => 'شرح',
        ]);

        $year = (int) $this->editorYear;

        // payload قدیمی: holidays.*.date + holidays.*.isHoliday + holidays.*.holidayDesription
        // ردیف‌های «غیرتعطیل» فایل موجود حفظ می‌شوند (فایل‌های منبع سال کامل ۳۶۵ روزه‌اند)
        $existing = $this->readYearFile($year);
        $editorDates = collect($validated['rows'])->pluck('date')->all();

        $data = [];

        foreach ($existing as $entry) {
            if (! in_array($entry['date'] ?? null, $editorDates, true)) {
                $data[] = [
                    'date'              => $entry['date'] ?? '',
                    'shamsiDate'        => $entry['shamsiDate'] ?? ($entry['date'] ?? ''),
                    'isHoliday'         => false,
                    'holidayDesription' => null,
                ];
            }
        }

        foreach ($validated['rows'] as $row) {
            $data[] = [
                'date'              => $row['date'],
                'shamsiDate'        => $row['date'],
                'isHoliday'         => true,
                'holidayDesription' => $row['holidayDesription'] !== '' ? $row['holidayDesription'] : null,
            ];
        }

        $payload = [
            'year' => $year,
            'data' => $data,
        ];

        $path = $this->yearFilePath($year);

        // قدیمی مستقیم file_put_contents می‌زد (در نصب تازه بدون پوشه با warning بی‌فایل تمام می‌شد)
        File::ensureDirectoryExists(dirname($path));

        file_put_contents($path, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        // ریفرش سرویس تعطیلات — عیناً مثل قدیمی
        app(HolidayService::class)->refresh();

        // به‌روزرسانی نمایش همان سال
        $holidays = $this->readYearFile($year);

        $this->years[$year] = [
            'year'      => $year,
            'exists'    => true,
            'holidays'  => $holidays,
            'holidaysOnly' => array_values(array_filter($holidays, fn ($h) => ($h['isHoliday'] ?? false) === true)),
        ];

        $this->loadEditorRows();

        $this->dispatch('toast', message: "تعطیلات سال {$year} با موفقیت ذخیره شد", type: 'success');
    }

    /* ---------------- ابزار چک تعطیلی (پورت check قدیمی) ---------------- */

    public function checkHoliday(): void
    {
        // قدیمی: date required|string (تاریخ شمسی) — جمعه‌ها همیشه تعطیل
        $this->validate(['checkDate' => 'required|string'], [], ['checkDate' => 'تاریخ']);

        $holidayService = app(HolidayService::class);

        // دیت‌پیکر پنل Y-m-d می‌دهد؛ سرویس Y/m/d می‌خواهد
        $date = str_replace('-', '/', $this->checkDate);

        $isHoliday = $holidayService->isHoliday($date);

        $this->checkResult      = $isHoliday;
        $this->checkDescription = $isHoliday ? $holidayService->getHolidayDescription($date) : null;
    }

    public function resetCheck(): void
    {
        $this->reset(['checkDate', 'checkResult', 'checkDescription']);
    }

    /* ---------------- ابزار تبدیل تاریخ (پورت convertToJalali قدیمی) ---------------- */

    public function convertDateAction(): void
    {
        // قدیمی: date required|date (تاریخ میلادی)
        $this->validate(['convertDate' => 'required|date'], [], ['convertDate' => 'تاریخ']);

        $jalali = Jalalian::fromDateTime($this->convertDate);

        $this->convertResult = [
            'jalali'         => $jalali->format('Y/m/d'),
            'jalali_display' => $jalali->format('j F Y'),
            'day_name'       => $jalali->format('l'),
        ];
    }

    public function resetConvert(): void
    {
        $this->reset(['convertDate', 'convertResult']);
    }

    /* ---------------- ابزار ---------------- */

    public static function jdate($date, $format = 'Y/m/d'): string
    {
        if (! $date) {
            return '—';
        }

        return Jalalian::fromDateTime($date)->format($format);
    }

    /** نام روز انگلیسی به فارسی (پورت getPersianDayName قدیمی) */
    public static function persianDayName(string $dayNameEn): string
    {
        $days = [
            'Saturday'  => 'شنبه',
            'Sunday'    => 'یکشنبه',
            'Monday'    => 'دوشنبه',
            'Tuesday'   => 'سه‌شنبه',
            'Wednesday' => 'چهارشنبه',
            'Thursday'  => 'پنجشنبه',
            'Friday'    => 'جمعه',
        ];

        return $days[$dayNameEn] ?? $dayNameEn;
    }

    public function render()
    {
        return view('admin-panel::livewire.admin.settings.holidays')
            ->layout('admin-panel::layout');
    }
}
