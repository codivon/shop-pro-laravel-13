{{-- پاسخ‌های فرم — پنل جدید (فاز ۶) — پورت ۱:۱ back/forms/submissions + partials/submission-detail --}}
<div class="mx-auto max-w-7xl space-y-5">

    {{-- سربرگ --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-2">
            <a href="{{ route('np.forms.index') }}" wire:navigate
               class="rounded-lg p-2 text-ink-3 transition-colors hover:bg-surface-3 dark:hover:bg-night-3"
               aria-label="بازگشت به فرم‌ها" title="بازگشت">
                <x-admin-panel::icon name="arrow-right" class="size-4.5" />
            </a>
            <div>
                <h1 class="text-base font-bold sm:text-lg">پاسخ‌های فرم: {{ $form->title }}</h1>
                <p class="mt-0.5 text-xs text-ink-3 dark:text-night-ink-2">
                    <span class="fa-digits">{{ number_format($this->submissions->total()) }}</span> پاسخ ثبت شده
                </p>
            </div>
        </div>
    </div>

    {{-- نوار انتخاب گروهی --}}
    <div x-show="$wire.selected.length > 0" x-cloak
         class="ap-card flex flex-wrap items-center justify-between gap-3 border border-brand-200 bg-brand-50/60 p-4 dark:border-brand-500/20 dark:bg-brand-500/10">
        <p class="text-[13px] font-semibold text-brand-700 dark:text-brand-300">
            <span class="fa-digits" x-text="ApFormat.faDigits($wire.selected.length)"></span> پاسخ انتخاب شده
        </p>
        @can('forms.delete')
            <button type="button" wire:click="$set('confirmingBulkDelete', true)"
                    class="ap-btn bg-red-600 text-white hover:bg-red-700">
                <x-admin-panel::icon name="trash-2" class="size-4" />
                حذف موارد انتخاب شده
            </button>
        @endcan
    </div>

    {{-- جدول --}}
    <div class="ap-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full min-w-4xl text-start text-[13px]">
                <thead>
                    <tr class="border-b border-line bg-surface-2 text-ink-2 dark:border-night-line dark:bg-night-3">
                        <th class="w-12 px-3 py-3">
                            <input type="checkbox" wire:model.live="selectAll" aria-label="انتخاب همه"
                                   class="size-4 cursor-pointer rounded border-line text-brand-600">
                        </th>
                        <th class="w-16 px-3 py-3 text-start font-bold">شناسه</th>
                        <th class="px-3 py-3 text-start font-bold">اطلاعات ارسال</th>
                        <th class="px-3 py-3 text-center font-bold">فایل</th>
                        <th class="px-3 py-3 text-center font-bold">IP</th>
                        <th class="px-3 py-3 text-center font-bold">تاریخ ارسال</th>
                        <th class="w-24 px-3 py-3 text-center font-bold">عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->submissions as $submission)
                        <tr wire:key="submission-{{ $submission->id }}"
                            class="border-b border-line/60 transition-colors last:border-0 hover:bg-surface-2 dark:border-night-line/60 dark:hover:bg-night-3/60">
                            <td class="px-3 py-3">
                                <input type="checkbox" wire:model.live="selected" value="{{ $submission->id }}"
                                       aria-label="انتخاب پاسخ {{ $submission->id }}"
                                       class="size-4 cursor-pointer rounded border-line text-brand-600">
                            </td>
                            <td class="px-3 py-3 fa-digits font-bold">{{ number_format($submission->id) }}</td>
                            <td class="max-w-72 px-3 py-3">
                                <div class="flex items-center gap-2">
                                    <span class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-brand-600 dark:bg-brand-500/12 dark:text-brand-400">
                                        <x-admin-panel::icon name="file-text" class="size-4" />
                                    </span>
                                    <p class="truncate" title="{{ $this->submissionSummary($submission) }}">
                                        {{ $this->submissionSummary($submission) }}
                                    </p>
                                </div>
                            </td>
                            <td class="px-3 py-3 text-center">
                                @if ($submission->has_photo)
                                    <span class="ap-badge bg-amber-50 text-amber-700 dark:bg-amber-500/12 dark:text-amber-400">
                                        <x-admin-panel::icon name="image" class="size-3.5" />
                                        تصویر
                                    </span>
                                @else
                                    <span class="text-ink-3 dark:text-night-ink-2">—</span>
                                @endif
                            </td>
                            <td class="px-3 py-3 text-center">
                                <code class="text-[11px] text-ink-2 dark:text-night-ink-2" dir="ltr">{{ $submission->ip_address ?? '—' }}</code>
                            </td>
                            <td class="px-3 py-3 fa-digits whitespace-nowrap text-center">
                                {{ $this->jdate($submission->submitted_at, 'Y/m/d H:i') }}
                            </td>
                            <td class="px-3 py-3">
                                <div class="flex items-center justify-center gap-0.5">
                                    <button type="button" wire:click="openDetail({{ $submission->id }})"
                                            class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-brand-50 hover:text-brand-600 dark:hover:bg-brand-500/10"
                                            title="جزئیات پاسخ" aria-label="جزئیات">
                                        <x-admin-panel::icon name="eye" class="size-4" />
                                    </button>
                                    @can('forms.delete')
                                        <button type="button" wire:click="confirmDelete({{ $submission->id }})"
                                                class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-500/10"
                                                title="حذف پاسخ" aria-label="حذف">
                                            <x-admin-panel::icon name="trash-2" class="size-4" />
                                        </button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">
                                <div class="flex flex-col items-center gap-2 py-14 text-ink-3 dark:text-night-ink-2">
                                    <x-admin-panel::icon name="message-circle" class="size-10 opacity-40" />
                                    <p class="text-sm font-medium">هیچ پاسخی برای این فرم ثبت نشده است</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- صفحه‌بندی --}}
        <div class="border-t border-line px-4 py-3 dark:border-night-line">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <p class="text-xs text-ink-3 dark:text-night-ink-2">
                    نمایش <span class="fa-digits">{{ number_format($this->submissions->firstItem() ?? 0) }}</span> تا
                    <span class="fa-digits">{{ number_format($this->submissions->lastItem() ?? 0) }}</span> از
                    <span class="fa-digits">{{ number_format($this->submissions->total()) }}</span> پاسخ
                </p>
                <nav class="flex items-center gap-1" aria-label="صفحه‌بندی">
                    @if ($this->submissions->onFirstPage())
                        <span class="rounded-lg p-2 text-ink-3 opacity-40"><x-admin-panel::icon name="chevron-right" class="size-4" /></span>
                    @else
                        <button wire:click="previousPage" class="rounded-lg p-2 text-ink-2 transition-colors hover:bg-surface-3 dark:hover:bg-night-3" aria-label="صفحه قبل">
                            <x-admin-panel::icon name="chevron-right" class="size-4" />
                        </button>
                    @endif

                    @foreach ($this->submissions->onEachSide(1)->toArray()['links'] as $link)
                        @if ($link['label'] === '...')
                            <span class="px-2 text-xs text-ink-3">…</span>
                        @elseif (! is_numeric(trim($link['label'])))
                            {{-- دکمه‌های قبلی/بعدی بالاتر مدیریت شده‌اند --}}
                        @elseif ($link['active'])
                            <span class="fa-digits flex size-8 items-center justify-center rounded-lg bg-brand-600 text-[13px] font-bold text-white">{{ $link['label'] }}</span>
                        @else
                            <button wire:click="gotoPage({{ trim($link['label']) }})"
                                    class="fa-digits flex size-8 items-center justify-center rounded-lg text-[13px] font-semibold text-ink-2 transition-colors hover:bg-surface-3 dark:text-night-ink-2 dark:hover:bg-night-3">
                                {{ $link['label'] }}
                            </button>
                        @endif
                    @endforeach

                    @if ($this->submissions->hasMorePages())
                        <button wire:click="nextPage" class="rounded-lg p-2 text-ink-2 transition-colors hover:bg-surface-3 dark:hover:bg-night-3" aria-label="صفحه بعد">
                            <x-admin-panel::icon name="chevron-left" class="size-4" />
                        </button>
                    @else
                        <span class="rounded-lg p-2 text-ink-3 opacity-40"><x-admin-panel::icon name="chevron-left" class="size-4" /></span>
                    @endif
                </nav>
            </div>
        </div>
    </div>

    {{-- مودال جزئیات پاسخ (پورت submission-detail partial) --}}
    <div x-data="{ open: @entangle('viewingSubmission').live, close() { this.open = false } }"
         x-show="open" x-cloak
         class="fixed inset-0 z-[60] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-950/50 backdrop-blur-sm" x-show="open" x-on:click="close()" x-transition.opacity aria-hidden="true"></div>
        <div x-show="open" x-on:click.stop
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
             class="relative flex max-h-[88vh] w-full max-w-xl flex-col overflow-hidden rounded-2xl border border-line bg-surface shadow-pop dark:border-night-line dark:bg-night-2"
             role="dialog" aria-modal="true">
            <div class="flex items-center justify-between gap-2 border-b border-line p-4 dark:border-night-line">
                <h3 class="flex items-center gap-2 text-sm font-bold">
                    <x-admin-panel::icon name="file-text" class="size-4.5 text-brand-600 dark:text-brand-400" />
                    جزئیات پاسخ فرم
                </h3>
                <button type="button" x-on:click="close()"
                        class="rounded-lg p-2 text-ink-3 transition-colors hover:bg-surface-3 dark:hover:bg-night-3" aria-label="بستن">
                    <x-admin-panel::icon name="x" class="size-4" />
                </button>
            </div>

            <div class="flex-1 overflow-y-auto p-4 sm:p-5">
                @if ($this->viewingData)
                    @php $detail = $this->viewingData; $detailSubmission = $detail['submission']; @endphp
                    <dl class="divide-y divide-line/70 dark:divide-night-line/70">
                        <div class="flex flex-wrap items-center justify-between gap-2 py-2.5 text-[13px]">
                            <dt class="font-bold text-ink-2 dark:text-night-ink-2">شناسه پاسخ:</dt>
                            <dd class="fa-digits">#{{ number_format($detailSubmission->id) }}</dd>
                        </div>
                        <div class="flex flex-wrap items-center justify-between gap-2 py-2.5 text-[13px]">
                            <dt class="font-bold text-ink-2 dark:text-night-ink-2">فرم مرتبط:</dt>
                            <dd><span class="ap-badge bg-brand-50 text-brand-700 dark:bg-brand-500/12 dark:text-brand-400">{{ $form->title }}</span></dd>
                        </div>
                        <div class="py-2.5 text-[13px]">
                            <dt class="mb-2 font-bold text-ink-2 dark:text-night-ink-2">اطلاعات ارسال شده:</dt>
                            <dd>
                                <div class="space-y-2 rounded-xl border border-line bg-surface-2 p-3 dark:border-night-line dark:bg-night-3">
                                    @forelse ($detail['data'] as $key => $value)
                                        <div class="flex flex-wrap items-start justify-between gap-2 border-b border-line/50 pb-2 last:border-0 last:pb-0 dark:border-night-line/50">
                                            <span class="min-w-32 text-xs font-bold text-ink-2 dark:text-night-ink-2" dir="ltr">{{ $key }}:</span>
                                            <span class="flex-1 break-all text-ink-3 dark:text-night-ink-2">
                                                @if ($key === 'image' && $value)
                                                    <a href="{{ asset($value) }}" target="_blank" rel="noopener">
                                                        <img src="{{ asset($value) }}" alt="تصویر پیوست" class="max-h-40 rounded-lg border border-line object-contain dark:border-night-line">
                                                    </a>
                                                @elseif (is_array($value))
                                                    <pre class="overflow-x-auto whitespace-pre-wrap break-all text-[11px]" dir="ltr">{{ json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                                @else
                                                    {{ $value ?? '—' }}
                                                @endif
                                            </span>
                                        </div>
                                    @empty
                                        <p class="text-xs text-ink-3">داده‌ای ثبت نشده است</p>
                                    @endforelse
                                </div>
                            </dd>
                        </div>
                        <div class="flex flex-wrap items-center justify-between gap-2 py-2.5 text-[13px]">
                            <dt class="font-bold text-ink-2 dark:text-night-ink-2">IP ارسال کننده:</dt>
                            <dd><code class="text-[11px]" dir="ltr">{{ $detailSubmission->ip_address ?? '—' }}</code></dd>
                        </div>
                        <div class="flex flex-wrap items-center justify-between gap-2 py-2.5 text-[13px]">
                            <dt class="font-bold text-ink-2 dark:text-night-ink-2">مرورگر:</dt>
                            <dd>
                                @if ($detailSubmission->user_agent)
                                    <span class="ap-badge bg-brand-50 text-brand-700 dark:bg-brand-500/12 dark:text-brand-400">
                                        {{ \App\Models\Newsletter::getBrowser($detailSubmission->user_agent) }}
                                    </span>
                                    /
                                    <span class="ap-badge bg-surface-3 text-ink-2 dark:bg-night-3 dark:text-night-ink-2">
                                        {{ \App\Models\Newsletter::getOS($detailSubmission->user_agent) }}
                                    </span>
                                @else
                                    —
                                @endif
                            </dd>
                        </div>
                        <div class="flex flex-wrap items-center justify-between gap-2 py-2.5 text-[13px]">
                            <dt class="font-bold text-ink-2 dark:text-night-ink-2">User Agent:</dt>
                            <dd class="max-w-72 break-all text-[11px] text-ink-3 dark:text-night-ink-2" dir="ltr">{{ $detailSubmission->user_agent ?? '—' }}</dd>
                        </div>
                        <div class="flex flex-wrap items-center justify-between gap-2 py-2.5 text-[13px]">
                            <dt class="font-bold text-ink-2 dark:text-night-ink-2">تاریخ ارسال:</dt>
                            <dd class="fa-digits">{{ $this->jdate($detailSubmission->submitted_at, 'Y/m/d H:i:s') }}</dd>
                        </div>
                        <div class="flex flex-wrap items-center justify-between gap-2 py-2.5 text-[13px]">
                            <dt class="font-bold text-ink-2 dark:text-night-ink-2">تاریخ ثبت در سیستم:</dt>
                            <dd class="fa-digits">{{ $this->jdate($detailSubmission->created_at, 'Y/m/d H:i:s') }}</dd>
                        </div>
                    </dl>
                @else
                    <p class="py-10 text-center text-sm text-ink-3 dark:text-night-ink-2">پاسخی یافت نشد</p>
                @endif
            </div>

            <div class="border-t border-line p-4 dark:border-night-line">
                <button type="button" x-on:click="close()" class="ap-btn-ghost w-full">بستن</button>
            </div>
        </div>
    </div>

    {{-- مودال تایید حذف تکی --}}
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
            <h3 class="text-base font-bold">حذف پاسخ</h3>
            <p class="mt-1.5 text-[13px] leading-6 text-ink-2 dark:text-night-ink-2">
                با حذف این پاسخ، دیگر قادر به بازیابی آن نخواهید بود؛ اگر پاسخ تصویر داشته باشد فایل آن هم حذف می‌شود.
            </p>
            <div class="mt-5 flex gap-2.5">
                <button type="button" wire:click="delete" wire:loading.attr="disabled"
                        class="ap-btn flex-1 bg-red-600 text-white hover:bg-red-700">
                    بله حذف شود
                </button>
                <button type="button" x-on:click="open=false" class="ap-btn-ghost flex-1">انصراف</button>
            </div>
        </div>
    </div>

    {{-- مودال تایید حذف گروهی --}}
    <div x-data="{ open: @entangle('confirmingBulkDelete').live }"
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
            <h3 class="text-base font-bold">حذف پاسخ‌های انتخاب‌شده</h3>
            <p class="mt-1.5 text-[13px] leading-6 text-ink-2 dark:text-night-ink-2">
                با حذف این پاسخ‌ها دیگر قادر به بازیابی آنها نخواهید بود.
            </p>
            <div class="mt-5 flex gap-2.5">
                <button type="button" wire:click="bulkDelete" wire:loading.attr="disabled"
                        class="ap-btn flex-1 bg-red-600 text-white hover:bg-red-700">
                    بله حذف شود
                </button>
                <button type="button" x-on:click="open=false" class="ap-btn-ghost flex-1">انصراف</button>
            </div>
        </div>
    </div>
</div>
