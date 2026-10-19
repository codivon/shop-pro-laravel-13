<?php

namespace App\Livewire\Admin\Messages;

use App\Models\User;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * تنظیمات پیام تبریک تولد — پورت ۱:۱ birthday/birthdayStore قدیمی.
 * کنترلر قدیمی گیت نداشت — پرمیشن منوی پنل (settings.sms) اعمال می‌شود.
 */
#[Title('تنظیمات پیام تبریک تولد')]
class MessageBirthday extends Component
{
    public bool $user_birthday_sms_send = false;

    public string $user_birthday_pattern_code = '';

    /** چیپ‌های کاربران تولددار فعال (notification=1) */
    public array $usersSelected = [];

    public string $usersSearch = '';

    public array $usersResults = [];

    public function mount(): void
    {
        auth('adminPanel')->user()?->can('settings.sms') ?: abort(403);

        $this->user_birthday_sms_send = (bool) option('user_birthday_sms_send');
        $this->user_birthday_pattern_code = (string) option('user_birthday_pattern_code');

        // پیش‌فرض: کاربرانی که فعال هستند (notification=1) — پورت تیک پیش‌فرض چک‌باکس‌های قدیمی
        $this->usersSelected = $this->birthdayUsers()
            ->filter(fn (User $user) => (bool) $user->notification)
            ->map(fn (User $user) => $this->userChip($user))
            ->values()
            ->all();
    }

    /* ---------------- لیست کاربران تولددار (پورت birthday قدیمی) ---------------- */

    #[Computed]
    public function birthdayUsers()
    {
        // ستون واقعی جدول users، birth_date است (فرمت جلالی 'Y/m/d' از فرم کاربران)؛
        // کنترلر قدیمی whereNotNull('birthday') می‌زد که ستون وجود ندارد و صفحه ۵۰۰ می‌داد — رفع شد
        return User::whereNotNull('birth_date')
            ->whereNotNull('mobile')
            ->where('level', 'user')
            ->get(['id', 'username', 'first_name', 'last_name', 'birth_date', 'mobile', 'notification']);
    }

    protected function userChip(User $user): array
    {
        return [
            'id'       => (int) $user->id,
            'label'    => trim((string) ($user->fullname ?? '')) ?: $user->username,
            'username' => (string) $user->username,
            'mobile'   => (string) $user->mobile,
            // birth_date از قبل جلالی است (الگوی UserController: year/month/day) — بدون تبدیل نمایش داده می‌شود
            'birthday' => (string) $user->birth_date,
        ];
    }

    /* ---------------- جستجوی کاربران (چیپ‌پیکر) ---------------- */

    public function updatedUsersSearch(): void
    {
        $term = trim($this->usersSearch);

        if (mb_strlen($term) < 2) {
            $this->usersResults = [];

            return;
        }

        $this->usersResults = $this->birthdayUsers()
            ->filter(function (User $user) use ($term) {
                return str_contains((string) $user->username, $term)
                    || str_contains((string) $user->first_name.$user->last_name, $term)
                    || str_contains((string) $user->mobile, $term);
            })
            ->map(fn (User $user) => $this->userChip($user))
            ->take(10)
            ->values()
            ->all();
    }

    public function addUser(int $userId): void
    {
        if (collect($this->usersSelected)->pluck('id')->contains($userId)) {
            return;
        }

        if (! $user = $this->birthdayUsers()->firstWhere('id', $userId)) {
            return;
        }

        $this->usersSelected[] = $this->userChip($user);

        $this->usersSearch = '';
        $this->usersResults = [];
    }

    public function removeUser(int $userId): void
    {
        $this->usersSelected = array_values(
            array_filter($this->usersSelected, fn (array $chip) => (int) $chip['id'] !== $userId)
        );
    }

    /* ---------------- ذخیره (پورت ۱:۱ birthdayStore قدیمی) ---------------- */

    public function save()
    {
        auth('adminPanel')->user()?->can('settings.sms') ?: abort(403);

        try {
            $this->validate(
                ['user_birthday_pattern_code' => 'required'],
                ['user_birthday_pattern_code.required' => 'کد پترن تبریک تولد الزامی است.'],
                ['user_birthday_pattern_code' => 'کد پترن تبریک تولد'],
            );
        } catch (ValidationException $e) {
            $first = collect($e->errors())->flatten()->first();

            $this->dispatch('toast', message: $first ?? 'لطفا خطاهای فرم را برطرف کنید.', type: 'error');

            throw $e;
        }

        $userBirthdaySmsSend = $this->user_birthday_sms_send ? 1 : 0;

        option_update('user_birthday_sms_send', $userBirthdaySmsSend);
        option_update('user_birthday_pattern_code', $this->user_birthday_pattern_code);

        // آپدیت جمعی notification: همه‌ی کاربران تولددار → 0 و انتخابی‌ها → 1 (پورت دقیق)
        User::whereNotNull('birth_date')
            ->whereNotNull('mobile')
            ->where('level', 'user')
            ->update(['notification' => 0]);

        if ($this->usersSelected) {
            User::whereIn('id', array_map('intval', array_column($this->usersSelected, 'id')))
                ->update(['notification' => 1]);
        }

        $this->dispatch('toast', message: 'تنظیمات با موفقیت ذخیره شد.', type: 'success');

        return null;
    }

    public function render()
    {
        return view('admin-panel::livewire.admin.messages.message-birthday')
            ->layout('admin-panel::layout');
    }
}
