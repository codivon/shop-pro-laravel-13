{{-- مقادیر راهنمای سایز (ماتریس گروه×سایز) — پنل جدید (فاز ۵) | پورت back/sizetypes/values --}}
<div class="mx-auto max-w-7xl space-y-6">
    <form wire:submit="save" x-on:keydown.enter="if ($event.target.tagName !== 'TEXTAREA') { $event.preventDefault(); }">

        {{-- هدر --}}
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-2.5">
                <span class="flex size-10 items-center justify-center rounded-xl bg-brand-600 text-white">
                    <x-admin-panel::icon name="sliders-horizontal" class="size-5" />
                </span>
                <div>
                    <h2 class="text-base font-extrabold">مقادیر راهنمای سایز: {{ $sizetype->title }}</h2>
                    <p class="text-[11px] text-ink-3 dark:text-night-ink-2">
                        هر ردیف یک «گروه سایز» است و ستون‌ها مشخصه‌های سایز را نشان می‌دهند (مثلا: قد در سایز S).
                    </p>
                </div>
            </div>
            <a href="{{ route('np.sizetypes.index') }}" wire:navigate class="ap-btn-ghost !py-2">
                <x-admin-panel::icon name="arrow-right" class="size-4" />
                بازگشت به لیست
            </a>
        </div>

        @if ($this->sizes->isEmpty())
            {{-- راهنمای سایز هنوز مشخصه‌ای ندارد --}}
            <div class="ap-card flex flex-col items-center gap-3 p-10 text-center">
                <x-admin-panel::icon name="circle-alert" class="size-10 text-amber-500" />
                <p class="text-sm font-bold">این راهنمای سایز هیچ مشخصه‌ای ندارد</p>
                <p class="max-w-md text-[13px] leading-6 text-ink-2 dark:text-night-ink-2">
                    ابتدا از صفحه ویرایش، مشخصه‌های سایز (مانند قد و دور سینه) را تعریف کنید؛ سپس برای ثبت مقادیر به این صفحه برگردید.
                </p>
                <a href="{{ route('np.sizetypes.edit', ['sizetype' => $sizetype]) }}" wire:navigate class="ap-btn-outline !py-2">
                    <x-admin-panel::icon name="pencil" class="size-4" />
                    ویرایش مشخصه‌های سایز
                </a>
            </div>
        @else
            {{-- ماتریس مقادیر --}}
            <div class="ap-card overflow-hidden">
                <div class="border-b border-line bg-surface-2 px-5 py-3 dark:border-night-line dark:bg-night-3/50">
                    <p class="text-sm font-bold">ماتریس مقادیر سایز</p>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full min-w-4xl text-start text-[13px]">
                        <thead>
                            <tr class="border-b border-line text-ink-2 dark:border-night-line">
                                <th class="w-14 px-3 py-3 text-center font-bold">گروه</th>
                                @foreach ($this->sizes as $size)
                                    <th class="px-3 py-3 text-center font-bold" wire:key="size-col-{{ $size->id }}">{{ $size->title }}</th>
                                @endforeach
                                <th class="w-28 px-3 py-3 text-center font-bold">عملیات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($valueGroups as $gi => $groupValues)
                                <tr wire:key="value-group-{{ $gi }}"
                                    class="border-b border-line/60 transition-colors last:border-0 hover:bg-surface-2 dark:border-night-line/60 dark:hover:bg-night-3/60">
                                    <td class="px-3 py-3 text-center">
                                        <span class="fa-digits inline-flex size-7 items-center justify-center rounded-lg bg-brand-50 font-bold text-brand-700 dark:bg-brand-500/12 dark:text-brand-400">
                                            {{ $gi + 1 }}
                                        </span>
                                    </td>
                                    @foreach ($this->sizes as $size)
                                        <td class="px-3 py-3" wire:key="value-cell-{{ $gi }}-{{ $size->id }}">
                                            <label class="sr-only" for="vg-{{ $gi }}-{{ $size->id }}">{{ $size->title }} در گروه {{ $gi + 1 }}</label>
                                            <input id="vg-{{ $gi }}-{{ $size->id }}" type="text" wire:model="valueGroups.{{ $gi }}.{{ $size->id }}"
                                                   class="ap-input !py-2 text-center" placeholder="{{ $size->title }}…">
                                        </td>
                                    @endforeach
                                    <td class="px-3 py-3">
                                        <div class="flex items-center justify-center gap-0.5">
                                            <button type="button" wire:click="moveGroupUp({{ $gi }})"
                                                    class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-brand-50 hover:text-brand-600 disabled:opacity-30 dark:hover:bg-brand-500/10"
                                                    @if ($gi === 0) disabled @endif title="انتقال به بالا" aria-label="انتقال به بالا">
                                                <x-admin-panel::icon name="chevron-up" class="size-4" />
                                            </button>
                                            <button type="button" wire:click="moveGroupDown({{ $gi }})"
                                                    class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-brand-50 hover:text-brand-600 disabled:opacity-30 dark:hover:bg-brand-500/10"
                                                    @if ($gi === count($valueGroups) - 1) disabled @endif title="انتقال به پایین" aria-label="انتقال به پایین">
                                                <x-admin-panel::icon name="chevron-down" class="size-4" />
                                            </button>
                                            <button type="button" wire:click="removeGroup({{ $gi }})"
                                                    class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-500/10"
                                                    title="حذف گروه" aria-label="حذف گروه">
                                                <x-admin-panel::icon name="trash-2" class="size-4" />
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="flex justify-center border-t border-line px-5 py-4 dark:border-night-line">
                    <button type="button" wire:click="addGroup" class="ap-btn-outline !py-2 text-[13px]">
                        <x-admin-panel::icon name="plus" class="size-4" />
                        افزودن مقدار جدید (گروه)
                    </button>
                </div>
            </div>
        @endif

        {{-- نوار ذخیره (چسبان) --}}
        <div class="ap-card sticky bottom-4 z-20 flex flex-wrap items-center justify-between gap-3 p-4 shadow-pop sm:p-5">
            <p class="text-[12px] text-ink-3 dark:text-night-ink-2">ترتیب ردیف‌ها همان ترتیب گروه‌ها در نمایش اندازه‌های فرانت است.</p>
            <div class="flex items-center gap-2">
                <a href="{{ route('np.sizetypes.index') }}" wire:navigate class="ap-btn-ghost !py-2">انصراف</a>
                <button type="submit" class="ap-btn-primary !py-2.5" wire:loading.attr="disabled" @if ($this->sizes->isEmpty()) disabled @endif>
                    <span wire:loading.remove wire:target="save">ذخیره تغییرات</span>
                    <span wire:loading wire:target="save" class="flex items-center gap-2">
                        <svg class="size-4 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M21 12a9 9 0 1 1-6.219-8.56" stroke-linecap="round"/></svg>
                        در حال ذخیره…
                    </span>
                </button>
            </div>
        </div>
    </form>
</div>
