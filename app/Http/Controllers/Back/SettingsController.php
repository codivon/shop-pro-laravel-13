<?php

namespace Modules\CustomerClub\Http\Controllers\Back;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\CustomerClub\Models\ClubSetting;

class SettingsController extends Controller
{
    /**
     * تنظیمات باشگاه — همهٔ امکانات با کلید فعال/غیرفعال مستقل
     * (الزام کاربر: هر قابلیت حداقل قابل خاموش/روشن کردن باشد).
     */
    public function edit()
    {
        return view('customer-club::back.settings.index', [
            'settings' => $this->current(),
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'club_enabled'             => ['required', 'boolean'],
            'loyalty_enabled'          => ['required', 'boolean'],
            'giftcards_enabled'        => ['required', 'boolean'],
            'referral_program_enabled' => ['required', 'boolean'],
            'referral_bonus_enabled'   => ['required', 'boolean'],
            'points_expiry_days'       => ['required', 'integer', 'min:0', 'max:36500'],
            'redeem_rate_toman'        => ['required', 'integer', 'min:1', 'max:10000000'],
            'redeem_min_points'        => ['required', 'integer', 'min:1', 'max:1000000'],
        ]);

        foreach ($data as $key => $value) {
            ClubSetting::set($key, is_bool($value) ? ($value ? '1' : '0') : (string) $value);
        }

        session()->put('toast-success', 'تنظیمات باشگاه مشتریان ذخیره شد.');

        return redirect()->route('admin.customerclub.settings.edit');
    }

    private function current(): array
    {
        $keys = [
            'club_enabled', 'loyalty_enabled', 'giftcards_enabled',
            'referral_program_enabled', 'referral_bonus_enabled',
            'points_expiry_days', 'redeem_rate_toman', 'redeem_min_points',
        ];

        return collect($keys)->mapWithKeys(fn ($key) => [$key => ClubSetting::get($key, '0')])->all();
    }
}
