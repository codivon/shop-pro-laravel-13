<?php

namespace App\Livewire\Admin\Statistics;

use App\Models\Sms;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * نمایش تکی پیامک — پورت ۱:۱ SmsController@show (perm همان statistics.sms).
 */
#[Title('جزئیات پیامک')]
class SmsShow extends Component
{
    public Sms $sms;

    public function mount(Sms $sms): void
    {
        auth('adminPanel')->user()?->can('statistics.sms') ?: abort(403);

        // پورت دقیق قدیمی
        $sms->load('user');

        $this->sms = $sms;
    }

    /* ---------------- فرمت‌کننده نمایش (اعداد فارسی) ---------------- */

    public static function fa($value): string
    {
        $fa = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];

        return strtr((string) $value, array_combine(range(0, 9), $fa));
    }

    public function render()
    {
        return view('admin-panel::livewire.admin.statistics.sms-show')
            ->layout('admin-panel::layout');
    }
}
