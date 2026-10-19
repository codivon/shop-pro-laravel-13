{{-- تعطیلات رسمی: نمایش ۳ سال + ویرایشگر JSON سال + ابزار چک/تبدیل — پنل جدید (فاز ۷-ب۱) --}}
@php
    $errors = $errors ?? (new \Illuminate\Support\ViewErrorBag())->put('default', new \Illuminate\Support\MessageBag());
@endphp
<div class="mx-auto max-w-7xl space-y-5">

    {{-- سربرگ --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap items-center gap-2">
            <span class="ap-badge bg-brand-50 text-brand-700 dark:bg-brand-500/12 dark:text-brand-400">
                سال جاری: <span class="fa-digits">{{ \Morilog\Jalali\Jalalian::now()->getYear() }}</span>
            </span>
            <span class="ap-badge bg-surface-3 text-ink-2 dark:bg-night-3 dark:text-night-ink-2">جمعه‌ها همیشه تعطیل هستند</span>
        </div>
        <p class="text-xs text-ink-3 dark:text-night-ink-2">منبع: فایل‌های <code class="font-mono text-[11px]" dir="ltr">storage/app/holidays/{year}.json</code></p>
    </div>

    {{-- کارت‌های سال‌ها (پورت index قدیمی: خواندن JSON سه سال) --}}
    <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
        @foreach ($years as $year => $info)
            <div class="ap-card overflow-hidden" x-data="{ open: true }">
                <div class="flex items-center justify-between p-4">
                    <div class="flex items-center gap-2">
                        <span class="flex size-9 items-center justify-center rounded-xl bg-brand-50 text-brand-600 dark:bg-brand-500/10 dark:text-brand-400">
                            <x-admin-panel::icon name="calendar-days" class="size-4.5" />
                        </span>
                        <div>
                            <p class="fa-digits text-sm font-bold">سال {{ $year }}</p>
                            <p class="fa-digits text-[11px] text-ink-3 dark:text-night-ink-2">
                                {{ count($info['holidaysOnly']) }} تعطیلی ثبت‌شده
                                @if ($info['exists'])
                                    • فایل موجود است
                                @else
                                    • فایلی وجود ندارد
                                @endif
                            </p>
                        </div>
                    </div>
                    <button type="button" class="ap-chevron" :class="open ? 'is-open' : ''" x-on:click="open = !open" aria-label="باز و بسته کردن">
                        <x-admin-panel::icon name="chevron-down" class="size-4 text-ink-3" />
                    </button>
                </div>

                <div x-show="open" class="border-t border-line dark:border-night-line">
                    @if (count($info['holidaysOnly']))
                        <ul class="max-h-72 divide-y divide-line/60 overflow-y-auto dark:divide-night-line/60">
                            @foreach ($info['holidaysOnly'] as $holiday)
                                <li class="flex items-center justify-between gap-2 px-4 py-2 text-[13px]">
                                    <span class="fa-digits whitespace-nowrap font-semibold text-brand-700 dark:text-brand-400" dir="ltr">{{ $holiday['date'] ?? '—' }}</span>
                                    <span class="truncate text-ink-2 dark:text-night-ink-2">{{ $holiday['holidayDesription'] ?? 'تعطیل رسمی' }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="px-4 py-8 text-center text-[13px] text-ink-3 dark:text-night-ink-2">
                            تعطیلاتی برای این سال ثبت نشده است — از ویرایشگر زیر استفاده کنید.
                        </p>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    {{-- ویرایشگر سال (پورت updateHolidays قدیمی) --}}
    <div class="ap-card overflow-hidden">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line p-4 sm:p-5 dark:border-night-line">
            <div class="flex items-center gap-2">
                <x-admin-panel::icon name="pencil" class="size-4.5 text-brand-600 dark:text-brand-400" />
                <h2 class="text-sm font-bold">به‌روزرسانی تعطیلات سال</h2>
            </div>
            <div class="flex items-center gap-2">
                <label class="text-xs font-semibold" for="holidays-editor-year">سال</label>
                <select id="holidays-editor-year" wire:model.live="editorYear" class="ap-input !w-28 !py-2">
                    @foreach (array_keys($years) as $year)
                        <option value="{{ $year }}">{{ $year }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <form wire:submit="updateYear" class="p-4 sm:p-5">
            <div class="space-y-3">
                @foreach ($rows as $index => $row)
                    <div class="flex flex-wrap items-end gap-2 rounded-xl border border-line bg-surface-2 p-3 dark:border-night-line dark:bg-night-3"
                         wire:key="holiday-row-{{ $index }}">
                        <div class="w-40">
                            <label class="mb-1.5 block text-xs font-semibold">تاریخ <span class="text-red-500">*</span></label>
                            <input type="text" wire:model="rows.{{ $index }}.date" data-jdp data-jdp-only-date="true"
                                   autocomplete="off" class="ap-input !py-2 fa-digits text-center" placeholder="۱۴۰۵/۰۱/۰۱">
                            @error('rows.' . $index . '.date') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div class="min-w-48 flex-1">
                            <label class="mb-1.5 block text-xs font-semibold">شرح تعطیلی</label>
                            <input type="text" wire:model="rows.{{ $index }}.holidayDesription" class="ap-input !py-2" placeholder="مثال: عید نوروز">
                            @error('rows.' . $index . '.holidayDesription') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <button type="button" wire:click="removeRow({{ $index }})"
                                class="rounded-lg p-2 text-ink-3 transition-colors hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-500/10"
                                title="حذف ردیف" aria-label="حذف ردیف">
                            <x-admin-panel::icon name="trash-2" class="size-4" />
                        </button>
                    </div>
                @endforeach
            </div>

            @error('rows') <p class="mt-2 text-xs font-medium text-red-600">{{ $message }}</p> @enderror

            <div class="mt-4 flex flex-wrap items-center gap-2">
                <button type="button" wire:click="addRow" class="ap-btn-outline !py-2">
                    <x-admin-panel::icon name="plus" class="size-4" />
                    افزودن تعطیلی
                </button>
                <button type="submit" wire:loading.attr="disabled" wire:target="updateYear" class="ap-btn !py-2">
                    <x-admin-panel::icon name="check" class="size-4" />
                    ذخیره تعطیلات سال
                </button>
                <p class="text-[11px] text-ink-3 dark:text-night-ink-2">فایل سال بازنویسی می‌شود؛ ردیف‌های غیرتعطیل فایل موجود حفظ می‌شوند.</p>
            </div>
        </form>
    </div>

    {{-- ابزارهای کمکی (پورت check / convert-to-jalali قدیمی) --}}
    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">

        {{-- چک تعطیلی تاریخ شمسی --}}
        <div class="ap-card p-4 sm:p-5">
            <div class="flex items-center gap-2">
                <x-admin-panel::icon name="circle-check" class="size-4.5 text-brand-600 dark:text-brand-400" />
                <h2 class="text-sm font-bold">بررسی تعطیل بودن یک روز (تاریخ شمسی)</h2>
            </div>
            <form wire:submit="checkHoliday" class="mt-4 flex items-end gap-2">
                <div class="flex-1">
                    <label class="mb-1.5 block text-xs font-semibold" for="holidays-check-date">تاریخ</label>
                    <input id="holidays-check-date" type="text" wire:model="checkDate" data-jdp data-jdp-only-date="true"
                           autocomplete="off" class="ap-input !py-2 fa-digits text-center" placeholder="۱۴۰۵/۰۱/۰۱">
                    @error('checkDate') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>
                <button type="submit" wire:loading.attr="disabled" wire:target="checkHoliday" class="ap-btn !py-2">بررسی</button>
            </form>

            @if ($checkResult !== null)
                <div class="mt-4 rounded-xl border p-3.5 text-[13px] {{ $checkResult ? 'border-red-200 bg-red-50 text-red-700 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-400' : 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-400' }}">
                    <p class="font-bold">{{ $checkResult ? 'این روز تعطیل است' : 'این روز تعطیل نیست' }}</p>
                    @if ($checkDescription)
                        <p class="mt-1">علت: {{ $checkDescription }}</p>
                    @endif
                </div>
            @endif
        </div>

        {{-- تبدیل تاریخ میلادی به شمسی --}}
        <div class="ap-card p-4 sm:p-5">
            <div class="flex items-center gap-2">
                <x-admin-panel::icon name="refresh-cw" class="size-4.5 text-brand-600 dark:text-brand-400" />
                <h2 class="text-sm font-bold">تبدیل تاریخ میلادی به شمسی</h2>
            </div>
            <form wire:submit="convertDateAction" class="mt-4 flex items-end gap-2">
                <div class="flex-1">
                    <label class="mb-1.5 block text-xs font-semibold" for="holidays-convert-date">تاریخ میلادی</label>
                    <input id="holidays-convert-date" type="date" wire:model="convertDate" autocomplete="off"
                           class="ap-input !py-2 fa-digits text-center" dir="ltr">
                    @error('convertDate') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>
                <button type="submit" wire:loading.attr="disabled" wire:target="convertDateAction" class="ap-btn !py-2">تبدیل</button>
            </form>

            @if ($convertResult)
                <div class="mt-4 space-y-1.5 rounded-xl border border-line bg-surface-2 p-3.5 text-[13px] dark:border-night-line dark:bg-night-3">
                    <p><span class="text-ink-3">شمسی:</span> <span class="fa-digits font-bold text-brand-700 dark:text-brand-400">{{ $convertResult['jalali'] }}</span></p>
                    <p><span class="text-ink-3">نمایش:</span> <span class="fa-digits font-semibold">{{ $convertResult['jalali_display'] }}</span></p>
                    <p><span class="text-ink-3">روز هفته:</span> <span class="font-semibold">{{ \App\Livewire\Admin\Settings\Holidays::persianDayName($convertResult['day_name']) }}</span></p>
                </div>
            @endif
        </div>
    </div>

    {{-- لودینگ --}}
    <div wire:loading wire:target="updateYear, checkHoliday, convertDateAction, addRow, removeRow"
         class="pointer-events-none fixed bottom-6 start-6 z-[65] flex items-center gap-2 rounded-xl border border-line bg-surface px-4 py-2.5 shadow-pop dark:border-night-line dark:bg-night-3">
        <svg class="size-4 animate-spin text-brand-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
            <path d="M21 12a9 9 0 1 1-6.219-8.56" stroke-linecap="round" />
        </svg>
        <span class="text-[13px] font-semibold">در حال بارگذاری…</span>
    </div>
</div>
