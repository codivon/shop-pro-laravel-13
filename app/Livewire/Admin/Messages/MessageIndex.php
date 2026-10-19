<?php

namespace App\Livewire\Admin\Messages;

use App\Events\SendMessage;
use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * کنترلر قدیمی MessageController هیچ گیت/Policy نداشت؛ در منوی پنل، آیتم «ارسال پیام»
 * با پرمیشن settings.sms گیت شده است — همین کلید در mount و همه‌ی اکشن‌ها اعمال می‌شود.
 */
#[Title('ارسال پیام')]
class MessageIndex extends Component
{
    use WithPagination;

    public int $perPage = 15;

    /* ---------------- فرم ارسال گروهی (پورت store قدیمی) ---------------- */

    public string $title = '';

    public string $description = '';

    public bool $email = false;

    public bool $sms = false;

    public bool $notification = false;

    public bool $popup = false;

    public string $user_message_pattern_code = '';

    /** @var array<int, array{name: string, value: string}> */
    public array $variables = [];

    /** چیپ‌های گیرندگان (الگوی DiscountForm) — خالی = همه‌ی کاربران */
    public array $usersSelected = [];

    public string $usersSearch = '';

    public array $usersResults = [];

    /* ---------------- حذف ---------------- */

    public ?int $confirmingDelete = null;

    public function mount(): void
    {
        auth('adminPanel')->user()?->can('settings.sms') ?: abort(403);

        $this->user_message_pattern_code = (string) option('user_message_pattern_code');
    }

    public function updating(string $name, $value): void
    {
        if ($name === 'perPage') {
            $this->resetPage();
        }
    }

    /* ---------------- لیست پیام‌ها (پورت index قدیمی) ---------------- */

    #[Computed]
    public function messages()
    {
        // پورت دقیق: Message::detectLang()->orderby('id','desc')
        return Message::detectLang()->orderby('id', 'desc')->paginate($this->perPage);
    }

    #[Computed]
    public function messagesTotal(): int
    {
        return Message::detectLang()->count();
    }

    #[Computed]
    public function sentCount(): int
    {
        return Message::detectLang()->where('status_send', 'sent')->count();
    }

    #[Computed]
    public function pendingCount(): int
    {
        // پورت کارت آمار قدیمی: «در انتظار / ناموفق» (pending + failed)
        return Message::detectLang()->whereIn('status_send', ['pending', 'failed'])->count();
    }

    #[Computed]
    public function usersCount(): int
    {
        return User::where(['level' => 'user', 'notification' => 1])->count();
    }

    /* ---------------- چیپ‌پیکر گیرندگان (الگوی DiscountForm) ---------------- */

    public function updatedUsersSearch(): void
    {
        $term = trim($this->usersSearch);

        if (mb_strlen($term) < 2) {
            $this->usersResults = [];

            return;
        }

        $this->usersResults = User::query()
            ->where(function ($query) use ($term) {
                $query->where('username', 'like', "%{$term}%")
                    ->orWhere('first_name', 'like', "%{$term}%")
                    ->orWhere('last_name', 'like', "%{$term}%")
                    ->orWhere('mobile', 'like', "%{$term}%");
            })
            ->latest()
            ->limit(10)
            ->get(['id', 'username', 'first_name', 'last_name', 'mobile'])
            ->map(fn (User $user) => [
                'id'       => (int) $user->id,
                'label'    => trim((string) ($user->fullname ?? '')) ?: $user->username,
                'username' => (string) $user->username,
            ])
            ->values()
            ->all();
    }

    public function addUser(int $userId): void
    {
        if (collect($this->usersSelected)->pluck('id')->contains($userId)) {
            return;
        }

        if (! $user = User::find($userId)) {
            return;
        }

        $this->usersSelected[] = [
            'id'    => (int) $user->id,
            'label' => trim((string) ($user->fullname ?? '')) ?: $user->username,
            'username' => (string) $user->username,
        ];

        $this->usersSearch = '';
        $this->usersResults = [];
    }

    public function removeUser(int $userId): void
    {
        $this->usersSelected = array_values(
            array_filter($this->usersSelected, fn (array $chip) => (int) $chip['id'] !== $userId)
        );
    }

    /* ---------------- ریپیتر متغیرهای پترن (پورت create.js قدیمی) ---------------- */

    public function addVariable(): void
    {
        $this->variables[] = ['name' => '', 'value' => ''];
    }

    public function removeVariable(int $index): void
    {
        unset($this->variables[$index]);

        $this->variables = array_values($this->variables);
    }

    /* ---------------- ذخیره (پورت ۱:۱ store قدیمی) ---------------- */

    public function save()
    {
        auth('adminPanel')->user()?->can('settings.sms') ?: abort(403);

        try {
            $data = $this->validate(
                ['title' => 'required', 'description' => 'required'],
                [
                    'title.required'       => 'عنوان الزامی است.',
                    'description.required' => 'توضیحات الزامی است.',
                ],
                ['title' => 'عنوان', 'description' => 'توضیحات'],
            );
        } catch (ValidationException $e) {
            $first = collect($e->errors())->flatten()->first();

            $this->dispatch('toast', message: $first ?? 'لطفا خطاهای فرم را برطرف کنید.', type: 'error');

            throw $e;
        }

        $inputData = null;

        if ($this->sms) {
            option_update('user_message_pattern_code', $this->user_message_pattern_code);

            // مرحله ۱: پالایش جفت‌های کامل (پورت دقیق فیلتر قدیمی — جفت‌های ناقص نادیده گرفته می‌شوند)
            $filteredVariables = [];
            $filteredValues = [];

            foreach ($this->variables as $variable) {
                $name = trim((string) ($variable['name'] ?? ''));
                $value = trim((string) ($variable['value'] ?? ''));

                if ($name !== '' && $value !== '') {
                    $filteredVariables[] = $name;
                    $filteredValues[] = $value;
                }
            }

            // مرحله ۲: اعتبارسنجی روی داده‌های پالایش‌شده (پورت دقیق)
            if (count($filteredVariables) > 0) {
                Validator::make(
                    ['filtered_variables' => $filteredVariables, 'filtered_values' => $filteredValues],
                    [
                        'filtered_variables.*' => 'required',
                        'filtered_values.*'    => 'required',
                    ],
                    [
                        'filtered_variables.*.required' => 'اسم متغیر الزامی است.',
                        'filtered_values.*.required'    => 'مقدار متغیر الزامی است.',
                    ],
                )->validate();

                // مرحله ۳: ترکیب نهایی
                $inputData = array_combine($filteredVariables, $filteredValues);
            }
        }

        if ($this->usersSelected) {
            $users = User::whereIn('id', array_map('intval', array_column($this->usersSelected, 'id')))->get();
        } else {
            $users = User::where(['level' => 'user', 'notification' => 1])->get();
        }

        if ($users->isEmpty()) {
            $this->dispatch('toast', message: 'هیچ کاربری برای ارسال پیام، وجود ندارد.', type: 'error');

            return null;
        }

        $message = new Message();
        $message->title = $data['title'];
        $message->description = $data['description'];
        $message->email = $this->email ? 1 : 0;
        $message->notification = $this->notification ? 1 : 0;
        $message->popup = $this->popup ? 1 : 0;
        $message->sms = $this->sms ? 1 : 0;
        $message->status_send = 'pending';
        $message->sms_patternCode = $this->sms ? ($this->user_message_pattern_code ?: null) : null;
        $message->sms_variables = json_encode($inputData);
        $message->save();

        $message->users()->attach($users);

        event(new SendMessage($message));

        // ریست فرم برای ارسال بعدی
        $this->reset('title', 'description', 'email', 'sms', 'notification', 'popup', 'variables', 'usersSelected', 'usersSearch', 'usersResults');
        $this->user_message_pattern_code = (string) option('user_message_pattern_code');

        $this->dispatch('toast', message: 'پیام ها با موفقیت وارد صف ارسال شدند.', type: 'success');

        return null;
    }

    /* ---------------- حذف (پورت destroy قدیمی) ---------------- */

    public function confirmDelete(int $id): void
    {
        auth('adminPanel')->user()?->can('settings.sms') ?: abort(403);

        $this->confirmingDelete = $id;
    }

    public function delete(): void
    {
        auth('adminPanel')->user()?->can('settings.sms') ?: abort(403);

        $message = Message::find($this->confirmingDelete);

        if ($message) {
            // پورت destroy قدیمی (FK cascade در MySQL) — پاک‌سازی صریح ردیف‌های پیوت برای محیط بدون FK
            $message->items()->delete();

            $message->delete();
        }

        $this->confirmingDelete = null;

        $this->dispatch('toast', message: 'پیام با موفقیت حذف شد.', type: 'success');
    }

    public static function jdate($date, $format = 'Y/m/d'): string
    {
        if (! $date) {
            return '—';
        }

        return \Morilog\Jalali\Jalalian::fromDateTime($date)->format($format);
    }

    public function render()
    {
        return view('admin-panel::livewire.admin.messages.messages')
            ->layout('admin-panel::layout');
    }
}
