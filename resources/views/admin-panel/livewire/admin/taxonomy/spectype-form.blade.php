{{-- فرم ویرایش نوع مشخصات + ریپیتر گروه‌ها/مشخصات — پنل جدید (فاز ۵) --}}
<div class="mx-auto max-w-4xl space-y-6">

    <form wire:submit="save" class="space-y-6"
          x-on:keydown.enter="if ($event.target.tagName !== 'TEXTAREA') { $event.preventDefault(); }">

        {{-- هدر --}}
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-2.5">
                <span class="flex size-10 items-center justify-center rounded-xl bg-brand-600 text-white">
                    <x-admin-panel::icon name="sliders-horizontal" class="size-5" />
                </span>
                <div>
                    <h2 class="text-base font-extrabold">ویرایش نوع مشخصات: {{ $name ?: '—' }}</h2>
                    <p class="text-[11px] text-ink-3 dark:text-night-ink-2">
                        شناسه: <span class="fa-digits">{{ number_format($spectype->id) }}</span>
                        — گروه‌ها و مشخصات این نوع از فرم محصول قابل انتخاب هستند.
                    </p>
                </div>
            </div>
            <a href="{{ route('np.spectypes.index') }}" wire:navigate class="ap-btn-ghost !py-2">
                <x-admin-panel::icon name="arrow-right" class="size-4" />
                بازگشت به لیست
            </a>
        </div>

        {{-- نام نوع مشخصات --}}
        <div class="ap-card p-5 sm:p-6">
            <label class="mb-1.5 block text-xs font-semibold" for="spectype-name">نوع مشخصات <span class="text-red-500">*</span></label>
            <input id="spectype-name" type="text" wire:model="name" class="ap-input" placeholder="مثلا گوشی موبایل">
            @error('name') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- ریپیتر گروه‌های مشخصات --}}
        <div class="ap-card p-5 sm:p-6">
            <h3 class="mb-4 flex items-center gap-2 text-sm font-bold">
                <x-admin-panel::icon name="layers" class="size-4.5 text-brand-600 dark:text-brand-400" />
                گروه‌های مشخصات
            </h3>

            <div class="space-y-4">
                @forelse ($groups as $groupIndex => $group)
                    <div wire:key="spec-group-{{ $groupIndex }}"
                         class="rounded-2xl border border-line p-4 dark:border-night-line">
                        <div class="flex flex-wrap items-end gap-3">
                            <div class="min-w-48 flex-1">
                                <label class="mb-1.5 block text-xs font-semibold" for="group-name-{{ $groupIndex }}">
                                    نام گروه <span class="text-red-500">*</span>
                                </label>
                                <input id="group-name-{{ $groupIndex }}" type="text" wire:model="groups.{{ $groupIndex }}.name"
                                       class="ap-input !py-2" placeholder="مثال: مشخصات کلی">
                            </div>
                            <button type="button" wire:click="removeGroup({{ $groupIndex }})"
                                    class="ap-btn-ghost !px-3 !py-2 text-red-600 hover:!bg-red-50 dark:hover:!bg-red-500/10"
                                    title="حذف گروه">
                                <x-admin-panel::icon name="trash-2" class="size-4" />
                            </button>
                        </div>

                        {{-- مشخصات گروه --}}
                        <div class="mt-3 space-y-2.5">
                            @forelse ($group['specifications'] as $specIndex => $specification)
                                <div wire:key="spec-{{ $groupIndex }}-{{ $specIndex }}"
                                     class="flex items-center gap-2">
                                    <div class="flex-1">
                                        <input type="text" wire:model="groups.{{ $groupIndex }}.specifications.{{ $specIndex }}.name"
                                               class="ap-input !py-2" placeholder="مثال: حافظه داخلی" aria-label="نام مشخصه">
                                    </div>
                                    <button type="button" wire:click="removeSpec({{ $groupIndex }}, {{ $specIndex }})"
                                            class="shrink-0 rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-500/10"
                                            title="حذف مشخصه" aria-label="حذف مشخصه">
                                        <x-admin-panel::icon name="x" class="size-4" />
                                    </button>
                                </div>
                            @empty
                                <p class="rounded-xl bg-surface-2 px-3 py-2.5 text-[12px] text-ink-3 dark:bg-night-3 dark:text-night-ink-2">
                                    مشخصه‌ای برای این گروه ثبت نشده — گروه‌های بدون مشخصه هنگام ذخیره نادیده گرفته می‌شوند.
                                </p>
                            @endforelse
                        </div>

                        <button type="button" wire:click="addSpec({{ $groupIndex }})"
                                class="mt-3 flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-xs font-semibold text-emerald-600 transition-colors hover:bg-emerald-50 dark:text-emerald-400 dark:hover:bg-emerald-500/10">
                            <x-admin-panel::icon name="plus" class="size-4" />
                            افزودن مشخصه
                        </button>
                    </div>
                @empty
                    <p class="rounded-xl bg-surface-2 px-3 py-3 text-[13px] text-ink-3 dark:bg-night-3 dark:text-night-ink-2">
                        هنوز گروهی ثبت نشده است. با دکمه زیر اولین گروه مشخصات را اضافه کنید.
                    </p>
                @endforelse
            </div>

            <button type="button" wire:click="addGroup"
                    class="ap-btn-outline mt-4 !py-2">
                <x-admin-panel::icon name="plus" class="size-4" />
                افزودن گروه مشخصات
            </button>
        </div>

        {{-- اکشن‌ها --}}
        <div class="flex flex-wrap items-center justify-end gap-2.5">
            <a href="{{ route('np.spectypes.index') }}" wire:navigate class="ap-btn-ghost">انصراف</a>
            <button type="submit" wire:loading.attr="disabled" wire:target="save" class="ap-btn">
                <x-admin-panel::icon name="check" class="size-4" />
                ذخیره تغییرات
            </button>
        </div>
    </form>

    {{-- لودینگ --}}
    <div wire:loading wire:target="save, addGroup, removeGroup, addSpec, removeSpec"
         class="pointer-events-none fixed bottom-6 start-6 z-[65] flex items-center gap-2 rounded-xl border border-line bg-surface px-4 py-2.5 shadow-pop dark:border-night-line dark:bg-night-3">
        <svg class="size-4 animate-spin text-brand-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
            <path d="M21 12a9 9 0 1 1-6.219-8.56" stroke-linecap="round" />
        </svg>
        <span class="text-[13px] font-semibold">در حال بارگذاری…</span>
    </div>
</div>
