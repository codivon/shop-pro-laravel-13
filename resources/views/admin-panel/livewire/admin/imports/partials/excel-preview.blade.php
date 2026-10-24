{{-- پیش‌نمایش زنده شبیه اکسل — فاز ۱۹ (مخصوص صفحات درون‌ریزی) --}}
{{-- متغیرهای ورودی: $fields، $filters، $label --}}
@php
    $activeFields = collect($fields)
        ->filter(fn ($f, $k) => ($filters[$k] ?? '0') === '1' || ($filters[$k] ?? false) === true)
        ->all();
@endphp
<div class="overflow-hidden rounded-2xl border border-line bg-surface shadow-card dark:border-night-line dark:bg-night-2">

    {{-- نوار ابزار پیش‌نمایش --}}
    <div class="flex flex-wrap items-center justify-between gap-2.5 border-b border-line bg-surface-2/70 px-4 py-3 dark:border-night-line dark:bg-night-3/70">
        <div class="flex items-center gap-2.5">
            <span class="flex size-8 items-center justify-center rounded-xl bg-emerald-600 text-white shadow-sm">
                <x-admin-panel::icon name="file-spreadsheet" class="size-4" />
            </span>
            <div class="leading-tight">
                <p class="ltr text-[12px] font-bold">template.xlsx</p>
                <p class="text-[10px] text-ink-3 dark:text-night-ink-2">پیش‌نمایش ساختار فایل خروجی</p>
            </div>
        </div>
        <span class="fa-digits inline-flex items-center gap-1.5 rounded-full bg-brand-50 px-2.5 py-1.5 text-[11px] font-bold text-brand-700 ring-1 ring-brand-200 dark:bg-brand-500/12 dark:text-brand-400 dark:ring-brand-500/25">
            <span class="relative flex size-2">
                <span class="absolute inline-flex size-full animate-ping rounded-full bg-brand-400 opacity-75"></span>
                <span class="relative inline-flex size-2 rounded-full bg-brand-500"></span>
            </span>
            پیش‌نمایش زنده — {{ number_format(count($activeFields)) }} ستون فعال
        </span>
    </div>

    @if (count($activeFields))
        {{-- گرید شبیه اکسل (LTR مثل خود اکسل) --}}
        <div class="overflow-x-auto">
            <table class="w-full min-w-max border-collapse text-[11px]">
                <thead>
                    {{-- ردیف حروف ستون‌ها (A، B، C…) --}}
                    <tr class="bg-slate-100 dark:bg-night-3">
                        <th class="w-9 border-b border-e border-slate-200 bg-slate-50 px-0 py-1 text-center font-mono text-[10px] font-semibold text-slate-400 dark:border-night-line dark:bg-night-3/80 dark:text-night-ink-2"></th>
                        @foreach ($activeFields as $key => $field)
                            <th class="min-w-36 border-b border-e border-slate-200 px-3 py-1 text-center font-mono text-[10px] font-semibold text-slate-500 dark:border-night-line dark:text-night-ink-2">
                                {{ $this->excelColumn($loop->index) }}
                            </th>
                        @endforeach
                    </tr>
                    {{-- ردیف ۱: عنوان ستون‌ها --}}
                    <tr class="bg-sky-50 dark:bg-sky-500/10">
                        <td class="border-b border-e border-slate-200 bg-slate-50 px-0 py-1.5 text-center font-mono text-[10px] font-semibold text-slate-400 dark:border-night-line dark:bg-night-3/80 dark:text-night-ink-2">۱</td>
                        @foreach ($activeFields as $key => $field)
                            <td class="max-w-44 truncate border-b border-e border-slate-200 px-3 py-1.5 text-center font-bold text-slate-700 dark:border-night-line dark:text-night-ink">
                                {{ $field['label'] }}
                                @if (! empty($field['required']))
                                    <span class="text-red-500">*</span>
                                @endif
                            </td>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    {{-- ردیف ۲: نمونه داده --}}
                    <tr>
                        <td class="border-b border-e border-slate-200 bg-slate-50 px-0 py-1.5 text-center font-mono text-[10px] font-semibold text-slate-400 dark:border-night-line dark:bg-night-3/80 dark:text-night-ink-2">۲</td>
                        @foreach ($activeFields as $key => $field)
                            <td class="max-w-44 truncate border-b border-e border-slate-200 px-3 py-1.5 text-slate-600 dark:border-night-line dark:text-night-ink-2" @if (! str_contains($field['sample'] ?? '', 'http')) dir="rtl" @endif>
                                {{ $field['sample'] ?? ($field['label'] . ' نمونه') }}
                            </td>
                        @endforeach
                    </tr>
                    {{-- ردیف ۳: نمونه دوم --}}
                    <tr>
                        <td class="border-b border-e border-slate-200 bg-slate-50 px-0 py-1.5 text-center font-mono text-[10px] font-semibold text-slate-400 dark:border-night-line dark:bg-night-3/80 dark:text-night-ink-2">۳</td>
                        @foreach ($activeFields as $key => $field)
                            <td class="max-w-44 truncate border-b border-e border-slate-200 px-3 py-1.5 text-slate-400 dark:border-night-line dark:text-night-ink-2" @if (! str_contains($field['sample'] ?? '', 'http')) dir="rtl" @endif>
                                {{ $field['sample'] ?? ($field['label'] . ' نمونه') }} ۲
                            </td>
                        @endforeach
                    </tr>
                    {{-- ردیف ۴: خالی --}}
                    <tr>
                        <td class="border-b border-e border-slate-200 bg-slate-50 px-0 py-1.5 text-center font-mono text-[10px] font-semibold text-slate-400 dark:border-night-line dark:bg-night-3/80 dark:text-night-ink-2">۴</td>
                        @foreach ($activeFields as $key => $field)
                            <td class="border-b border-e border-slate-200 px-3 py-1.5 dark:border-night-line">&nbsp;</td>
                        @endforeach
                    </tr>
                </tbody>
            </table>
        </div>
    @else
        <div class="flex flex-col items-center gap-2 py-12 text-ink-3 dark:text-night-ink-2">
            <x-admin-panel::icon name="file-spreadsheet" class="size-10 opacity-40" />
            <p class="text-sm font-medium">هیچ ستونی انتخاب نشده است</p>
            <p class="text-[11px]">از فهرست فیلدها حداقل یک ستون را تیک بزنید</p>
        </div>
    @endif

    {{-- راهنمای پایین پیش‌نمایش --}}
    <div class="flex items-center gap-2 border-t border-line bg-surface-2/60 px-4 py-2.5 text-[10.5px] leading-5 text-ink-3 dark:border-night-line dark:bg-night-3/60 dark:text-night-ink-2">
        <x-admin-panel::icon name="info" class="size-3.5 shrink-0" />
        با تیک‌زدن یا برداشتن فیلدها، ستون‌های فایل اکسل به‌صورت زنده تغییر می‌کنند — ترتیب ستون‌ها دقیقاً همان ترتیب نمایش است.
    </div>
</div>
