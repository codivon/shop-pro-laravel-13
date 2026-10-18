<?php

namespace App\Livewire\Admin\Links;

use App\Models\Link;
use App\Models\Option;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Illuminate\Validation\ValidationException;

/**
 * گروه‌های لینک فوتر — پورت LinkController@groups + updateGroups.
 *
 * قرارداد قدیمی حفظ شد: فهرست گروه‌ها از config('front.linkGroups') (آرایه‌ای از
 * ['name' => ..., 'key' => ...]) می‌آید و عنوانِ نمایشی هر گروه در جدول options با کلید
 * «link_groups_{key}» و lang=زبان فعلی ذخیره می‌شود (option_update).
 *
 * افزودنی طبق اسپک (فقط با options — دست به کانفیگ قالب نمی‌زند):
 * - گروه جدید: کلید بعدی (بزرگ‌ترین کلید + ۱) با همان قرارداد option_update ذخیره می‌شود
 *   و چون ردیف options ساخته می‌شود، در فهرست گروه‌ها (config + options) ظاهر می‌شود.
 * - حذف گروه: فقط گروه‌های افزوده‌شده (خارج از کانفیگ قالب) حذف می‌شوند؛ گروه‌های
 *   ثابت قالب حذف‌شدنی نیستند (خطا) و گروه دارای لینک هم حذف نمی‌شود.
 */
#[Layout('admin-panel::layout')]
#[Title('گروه‌های لینک')]
class LinkGroups extends Component
{
    /** @var array<string|int, string> عنوان گروه‌ها بر اساس کلید */
    public array $groupTitles = [];

    /* گروه جدید */
    public string $newGroupTitle = '';

    public ?int $confirmingDelete = null;

    public function mount(): void
    {
        auth('adminPanel')->user()?->can('links.groups') ?: abort(403);

        $this->fillGroupTitles();
    }

    /* ============================ فهرست گروه‌ها ============================ */

    /**
     * فهرست کامل گروه‌ها: config('front.linkGroups') + گروه‌های افزوده‌شده
     * (ردیف‌های options با کلید link_groups_{n} که در کانفیگ نیستند).
     * ساختار خروجی مثل کانفیگ قدیمی: [['name' => ..., 'key' => ...], ...]
     */
    public static function allGroups(): array
    {
        $configGroups = collect(config('front.linkGroups', []))
            ->filter(fn ($group) => isset($group['key']))
            ->values();

        $configKeys = $configGroups->pluck('key')->all();

        $extraGroups = Option::query()
            ->where('option_name', 'like', 'link_groups_%')
            ->where('lang', app()->getLocale())
            ->get()
            ->map(function (Option $option) {
                return [
                    'key'  => (int) str_replace('link_groups_', '', $option->option_name),
                    'name' => (string) $option->option_value,
                ];
            })
            ->filter(fn ($group) => ! in_array($group['key'], $configKeys, true))
            ->values();

        return $configGroups->merge($extraGroups)
            ->unique('key')
            ->values()
            ->all();
    }

    /** عنوان نمایشی گروه (option یا نام کانفیگ — مثل option() در ویوهای قدیمی) */
    public static function groupTitle(int|string $key, ?string $fallback = null): string
    {
        $configName = collect(config('front.linkGroups', []))
            ->first(fn ($group) => (string) ($group['key'] ?? '') === (string) $key)['name'] ?? null;

        return (string) option('link_groups_' . $key, $fallback ?? $configName ?? 'گروه ' . $key);
    }

    protected function fillGroupTitles(): void
    {
        $titles = [];

        foreach (self::allGroups() as $group) {
            $titles[$group['key']] = self::groupTitle($group['key']);
        }

        $this->groupTitles = $titles;
    }

    /* ============================ ذخیره (پورت ۱:۱ updateGroups) ============================ */

    public function save(): void
    {
        auth('adminPanel')->user()?->can('links.groups') ?: abort(403);

        try {
            $this->validate([
                'groupTitles'   => 'required|array',
                'groupTitles.*' => 'required|string|max:191',
            ]);
        } catch (ValidationException $e) {
            $first = collect($e->errors())->flatten()->first();

            $this->dispatch('toast', message: $first ?? 'لطفا خطاهای فرم را برطرف کنید.', type: 'error');

            throw $e;
        }

        // پورت دقیق updateGroups: foreach ($request->groups as $key => $value)
        foreach ($this->groupTitles as $key => $value) {
            option_update('link_groups_' . $key, $value);
        }

        $this->dispatch('toast', message: 'گروه‌های لینک با موفقیت ذخیره شد.', type: 'success');
    }

    /* ============================ افزودن گروه (افزنی — فقط با options) ============================ */

    public function addGroup(): void
    {
        auth('adminPanel')->user()?->can('links.groups') ?: abort(403);

        try {
            $this->validate([
                'newGroupTitle' => 'required|string|max:191',
            ], [], [
                'newGroupTitle' => 'عنوان گروه جدید',
            ]);
        } catch (ValidationException $e) {
            $first = collect($e->errors())->flatten()->first();

            $this->dispatch('toast', message: $first ?? 'لطفا خطاهای فرم را برطرف کنید.', type: 'error');

            throw $e;
        }

        $newKey = self::nextGroupKey();

        option_update('link_groups_' . $newKey, $this->newGroupTitle);

        $this->newGroupTitle = '';
        $this->fillGroupTitles();

        $this->dispatch('toast', message: 'گروه «' . self::groupTitle($newKey) . '» اضافه شد.', type: 'success');
    }

    /** بزرگ‌ترین کلید موجود (کانفیگ + options) + ۱ */
    protected static function nextGroupKey(): int
    {
        $keys = array_merge(
            collect(config('front.linkGroups', []))->pluck('key')->map(fn ($key) => (int) $key)->all(),
            Option::query()
                ->where('option_name', 'like', 'link_groups_%')
                ->where('lang', app()->getLocale())
                ->get()
                ->map(fn (Option $option) => (int) str_replace('link_groups_', '', $option->option_name))
                ->all(),
        );

        return ($keys === [] ? 0 : max($keys)) + 1;
    }

    /* ============================ حذف گروه ============================ */

    public function confirmDeleteGroup(int $key): void
    {
        auth('adminPanel')->user()?->can('links.groups') ?: abort(403);

        $this->confirmingDelete = $key;
    }

    public function deleteGroup(): void
    {
        auth('adminPanel')->user()?->can('links.groups') ?: abort(403);

        $key = $this->confirmingDelete;

        if ($key === null) {
            return;
        }

        $configKeys = collect(config('front.linkGroups', []))->pluck('key')->map(fn ($k) => (int) $k)->all();

        if (in_array($key, $configKeys, true)) {
            $this->confirmingDelete = null;

            // گروه‌های کانفیگ قالب حذف‌شدنی نیستند (کانفیگ دوباره می‌سازدشان)
            $this->dispatch('toast', message: 'گروه‌های ثابت قالب قابل حذف نیستند؛ فقط عنوان آن‌ها قابل تغییر است.', type: 'error');

            return;
        }

        if (Link::where('link_group_id', $key)->exists()) {
            $this->confirmingDelete = null;

            $this->dispatch('toast', message: 'به این گروه لینکی اختصاص داده شده؛ اول لینک‌های آن را منتقل یا حذف کنید.', type: 'error');

            return;
        }

        $name = self::groupTitle($key);

        Option::where('option_name', 'link_groups_' . $key)
            ->where('lang', app()->getLocale())
            ->delete();

        Cache::forget('options.' . app()->getLocale() . '.link_groups_' . $key);

        $this->confirmingDelete = null;
        $this->fillGroupTitles();

        $this->dispatch('toast', message: 'گروه «' . $name . '» حذف شد.', type: 'success');
    }

    /* ============================ نمایش ============================ */

    #[Computed]
    public function groups(): array
    {
        return self::allGroups();
    }

    public function render()
    {
        return view('admin-panel::livewire.admin.links.link-groups');
    }
}
