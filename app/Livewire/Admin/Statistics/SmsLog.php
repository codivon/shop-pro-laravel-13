<?php

namespace App\Livewire\Admin\Statistics;

use App\Models\Sms;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * لاگ پیامک‌های ارسالی — پورت ۱:۱ StatisticsController@smsLog.
 * (اجازه‌ی statistics.sms مثل کنترلر قدیمی؛ در اسپک «statistics.smsLog»
 *  نوشته شده بود اما این نام در درخت دسترسی وجود ندارد — انحراف مستند.)
 */
#[Title('لاگ پیامک‌های ارسالی')]
class SmsLog extends Component
{
    use WithPagination;

    /* ---------------- فیلترها (State در URL) ---------------- */

    #[Url(as: 'mobile', except: '')]
    public string $mobile = '';

    /** today | week | month | '' */
    #[Url(as: 'period', except: '')]
    public string $period = '';

    public int $perPage = 20;

    public function mount(): void
    {
        auth('adminPanel')->user()?->can('statistics.sms') ?: abort(403);
    }

    /* ---------------- فرمت‌کننده نمایش (اعداد فارسی) ---------------- */

    public static function fa($value): string
    {
        $fa = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];

        return strtr((string) $value, array_combine(range(0, 9), $fa));
    }

    public function updating(string $name, $value): void
    {
        if (in_array($name, ['mobile', 'period', 'perPage'])) {
            $this->resetPage();
        }
    }

    /* ---------------- اعمال فیلتر (پورت فرم GET قدیمی) ---------------- */

    public function applyFilters(): void
    {
        auth('adminPanel')->user()?->can('statistics.sms') ?: abort(403);

        $this->resetPage();
    }

    public function resetFilters(): void
    {
        auth('adminPanel')->user()?->can('statistics.sms') ?: abort(403);

        $this->mobile = '';
        $this->period = '';
        $this->resetPage();
    }

    /* ---------------- لیست (پورت دقیق smsLog قدیمی) ---------------- */

    #[Computed]
    public function sms()
    {
        $query = Sms::query();

        if (trim($this->mobile) !== '') {
            $query->where('mobile', 'like', '%' . trim($this->mobile) . '%');
        }

        switch ($this->period) {
            case 'today': $query->whereDate('created_at', today()); break;
            case 'week':  $query->where('created_at', '>=', now()->subDays(7)); break;
            case 'month': $query->where('created_at', '>=', now()->subDays(30)); break;
        }

        return $query->latest()->paginate($this->perPage);
    }

    /** آمار مستقل از فیلتر (پورت دقیق) */
    #[Computed(persist: true, seconds: 30)]
    public function stats(): array
    {
        return [
            'total' => Sms::count(),
            'today' => Sms::whereDate('created_at', today())->count(),
            'week'  => Sms::where('created_at', '>=', now()->subDays(7))->count(),
            'month' => Sms::where('created_at', '>=', now()->subDays(30))->count(),
        ];
    }

    /* ---------------- لاگ مشاهده (پورت دقیق) ---------------- */

    public function logView(): void
    {
        $adminName = auth('adminPanel')->user()->full_name ?? auth('adminPanel')->user()->name ?? 'مدیر';

        activity()
            ->causedBy(auth('adminPanel')->user())
            ->event('view')
            ->withProperties([
                'action'    => 'view_sms_log',
                'sms_count' => $this->sms->total(),
                'filters'   => array_filter(['mobile' => trim($this->mobile), 'period' => $this->period]),
                'ip'        => request()->ip(),
            ])
            ->log("مدیر {$adminName} لاگ ارسال پیامک‌ها را مشاهده کرد ({$this->sms->total()} پیامک)");
    }

    public function render()
    {
        return view('admin-panel::livewire.admin.statistics.sms-log')
            ->layout('admin-panel::layout');
    }
}
