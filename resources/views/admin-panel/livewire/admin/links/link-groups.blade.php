{{-- گروه‌های لینک — ویرایش عنوان‌ها (پورت updateGroups با option_update) + افزودن/حذف گروه افزوده‌شده --}}
<div class="mx-auto max-w-4xl space-y-5">

    {{-- سربرگ --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-2">
            <span class="ap-badge bg-brand-50 text-brand-700 dark:bg-brand-500/12 dark:text-brand-400">
                تعداد گروه‌ها: <span class="fa-digits">{{ number_format(count($this->groups)) }}</span>
            </span>
        </div>
        <a href="{{ route('np.links.index') }}" wire:navigate class="ap-btn-ghost !py-2">
            <x-admin-panel::icon name="arrow-right" class="size-4" />
            بازگشت به لینک‌ها
        </a>
    </div>

    {{-- فرم عناوین گروه‌ها (پورت ۱:۱ فرم قدیمی groups[key]) --}}
    <div class="ap-card overflow-hidden">
        <div class="border-b border-line px-4 py-3 dark:border-night-line">
            <h2 class="flex items-center gap-2 text-sm font-bold">
                <x-admin-panel::icon name="layers" class="size-4.5 text-brand-600 dark:text-brand-400" />
                عناوین گروه‌ها
            </h2>
        </div>
        <form wire:submit="save" class="p-4 sm:p-5">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                @foreach ($this->groups as $group)
                    @php
                        $isConfigGroup = collect(config('front.linkGroups', []))->pluck('key')->contains($group['key']);
                    @endphp
                    <div wire:key="group-title-{{ $group['key'] }}">
                        <label class="mb-1.5 flex items-center justify-between gap-2 text-xs font-semibold" for="group-title-{{ $group['key'] }}">
                            <span>
                                عنوان گروه {{ $group['key'] }}
                                @if ($isConfigGroup)
                                    <span class="text-[10px] font-normal text-ink-3 dark:text-night-ink-2">(گروه ثابت قالب)</span>
                                @endif
                            </span>
                            @can('links.groups')
                                @unless ($isConfigGroup)
                                    <button type="button" wire:click="confirmDeleteGroup({{ $group['key'] }})"
                                            class="rounded-lg p-1 text-ink-3 transition-colors hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-500/10"
                                            title="حذف گروه" aria-label="حذف گروه {{ $group['key'] }}">
                                        <x-admin-panel::icon name="trash-2" class="size-3.5" />
                                    </button>
                                @endunless
                            @endcan
                        </label>
                        <input id="group-title-{{ $group['key'] }}" type="text"
                               wire:model="groupTitles.{{ $group['key'] }}"
                               class="ap-input !py-2" placeholder="عنوان گروه…">
                        @error('groupTitles.' . $group['key']) <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>
                @endforeach
            </div>

            <div class="mt-5 flex items-center justify-between gap-3 border-t border-line pt-4 dark:border-night-line">
                <p class="text-[11px] text-ink-3 dark:text-night-ink-2">
                    عنوان‌ها در جدول options با کلید «link_groups_{key}» ذخیره می‌شوند (مثل پنل قدیمی).
                </p>
                <button type="submit" wire:loading.attr="disabled" wire:target="save" class="ap-btn-primary !py-2.5">
                    <x-admin-panel::icon name="check" class="size-4" />
                    ذخیره تغییرات
                </button>
            </div>
        </form>
    </div>

    {{-- افزودن گروه جدید (فقط با options — قرارداد option_update حفظ شده) --}}
    @can('links.groups')
        <div class="ap-card p-4 sm:p-5">
            <h2 class="mb-4 flex items-center gap-2 text-sm font-bold">
                <x-admin-panel::icon name="plus" class="size-4.5 text-brand-600 dark:text-brand-400" />
                گروه جدید
            </h2>
            <form wire:submit="addGroup" class="flex flex-wrap items-end gap-3">
                <div class="min-w-56 flex-1">
                    <label class="mb-1.5 block text-xs font-semibold" for="new-group-title">عنوان گروه جدید</label>
                    <input id="new-group-title" type="text" wire:model="newGroupTitle" class="ap-input !py-2" placeholder="مثال: گروه چهارم">
                    @error('newGroupTitle') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>
                <button type="submit" wire:loading.attr="disabled" wire:target="addGroup" class="ap-btn !py-2">
                    <x-admin-panel::icon name="plus" class="size-4" />
                    افزودن گروه
                </button>
            </form>
            <p class="mt-2 text-[11px] leading-5 text-ink-3 dark:text-night-ink-2">
                گروه‌های ثابت قالب (از config) حذف‌شدنی نیستند؛ فقط گروه‌های افزوده‌شده حذف می‌شوند.
            </p>
        </div>
    @endcan

    {{-- مودال تایید حذف گروه --}}
    <div x-data="{ open: @entangle('confirmingDelete').live }"
         x-show="open" x-cloak
         class="fixed inset-0 z-[60] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-950/50 backdrop-blur-sm" x-show="open" x-on:click="open=false" x-transition.opacity aria-hidden="true"></div>
        <div x-show="open" x-on:click.stop
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
             class="relative w-full max-w-sm rounded-2xl border border-line bg-surface p-6 shadow-pop dark:border-night-line dark:bg-night-2"
             role="dialog" aria-modal="true">
            <div class="mb-3 flex size-12 items-center justify-center rounded-2xl bg-red-50 text-red-600 dark:bg-red-500/10 dark:text-red-400">
                <x-admin-panel::icon name="trash-2" class="size-6" />
            </div>
            <h3 class="text-base font-bold">حذف گروه لینک</h3>
            <p class="mt-1.5 text-[13px] leading-6 text-ink-2 dark:text-night-ink-2">
                با حذف گروه، عنوان آن از تنظیمات حذف می‌شود؛ لینک‌های گروه را اول منتقل کنید.
            </p>
            <div class="mt-5 flex gap-2.5">
                <button type="button" wire:click="deleteGroup" wire:loading.attr="disabled"
                        class="ap-btn flex-1 bg-red-600 text-white hover:bg-red-700">
                    بله حذف شود
                </button>
                <button type="button" x-on:click="open=false" class="ap-btn-ghost flex-1">انصراف</button>
            </div>
        </div>
    </div>

    {{-- لودینگ --}}
    <div wire:loading wire:target="save, addGroup, deleteGroup"
         class="pointer-events-none fixed bottom-6 start-6 z-[65] flex items-center gap-2 rounded-xl border border-line bg-surface px-4 py-2.5 shadow-pop dark:border-night-line dark:bg-night-3">
        <svg class="size-4 animate-spin text-brand-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
            <path d="M21 12a9 9 0 1 1-6.219-8.56" stroke-linecap="round" />
        </svg>
        <span class="text-[13px] font-semibold">در حال بارگذاری…</span>
    </div>
</div>
