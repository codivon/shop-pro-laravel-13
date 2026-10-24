{{-- لیست فرم‌ها — پنل جدید (فاز ۶) — پورت ۱:۱ back/forms/index --}}
<div class="mx-auto max-w-7xl space-y-5">

    {{-- سربرگ --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap items-center gap-2">
            <span class="ap-badge bg-brand-50 text-brand-700 dark:bg-brand-500/12 dark:text-brand-400">
                <x-admin-panel::icon name="file-text" class="size-4" />
                کل فرم‌ها: <span class="fa-digits">{{ number_format($this->forms->total()) }}</span>
            </span>
        </div>
        @can('forms.create')
            <a href="{{ route('np.forms.create') }}" wire:navigate class="ap-btn">
                <x-admin-panel::icon name="plus" class="size-4" />
                فرم جدید
            </a>
        @endcan
    </div>

    {{-- نوار انتخاب گروهی --}}
    <div x-show="$wire.selected.length > 0" x-cloak
         class="ap-card flex flex-wrap items-center justify-between gap-3 border border-brand-200 bg-brand-50/60 p-4 dark:border-brand-500/20 dark:bg-brand-500/10">
        <p class="text-[13px] font-semibold text-brand-700 dark:text-brand-300">
            <span class="fa-digits" x-text="ApFormat.faDigits($wire.selected.length)"></span> فرم انتخاب شده
        </p>
        @can('filds.delete')
            {{-- گیت قدیمی multipleDestroy فرم‌ها «filds.delete» است — پورت ۱:۱ --}}
            <button type="button" wire:click="$set('confirmingBulkDelete', true)"
                    class="ap-btn bg-red-600 text-white hover:bg-red-700">
                <x-admin-panel::icon name="trash-2" class="size-4" />
                حذف همه
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
                        <th class="w-16 px-3 py-3 text-start font-bold">ID</th>
                        <th class="px-3 py-3 text-start font-bold">عنوان</th>
                        <th class="px-3 py-3 text-start font-bold">اسلاگ</th>
                        <th class="px-3 py-3 text-start font-bold">وضعیت</th>
                        <th class="px-3 py-3 text-start font-bold">فیلدها</th>
                        <th class="px-3 py-3 text-start font-bold">پاسخ‌ها</th>
                        <th class="px-3 py-3 text-start font-bold">تاریخ ایجاد</th>
                        <th class="w-28 px-3 py-3 text-start font-bold">عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->forms as $form)
                        <tr wire:key="form-{{ $form->id }}"
                            class="border-b border-line/60 transition-colors last:border-0 hover:bg-surface-2 dark:border-night-line/60 dark:hover:bg-night-3/60">
                            <td class="px-3 py-3">
                                <input type="checkbox" wire:model.live="selected" value="{{ $form->id }}"
                                       aria-label="انتخاب فرم {{ $form->title }}"
                                       class="size-4 cursor-pointer rounded border-line text-brand-600">
                            </td>
                            <td class="px-3 py-3 fa-digits font-bold">{{ number_format($form->id) }}</td>
                            <td class="max-w-56 px-3 py-3">
                                <p class="truncate font-semibold">{{ $form->title }}</p>
                                @if (Route::has('front.form.show'))
                                    <a href="{{ route('front.form.show', $form->slug) }}" target="_blank" rel="noopener"
                                       class="mt-0.5 inline-flex items-center gap-1 text-[11px] text-brand-600 hover:underline dark:text-brand-400">
                                        <x-admin-panel::icon name="external-link" class="size-3" />
                                        مشاهده در سایت
                                    </a>
                                @endif
                            </td>
                            <td class="max-w-40 px-3 py-3">
                                <code class="truncate text-[11px] text-ink-2 dark:text-night-ink-2" dir="ltr">{{ $form->slug }}</code>
                            </td>
                            <td class="px-3 py-3">
                                @if ($form->published)
                                    <span class="ap-badge bg-emerald-50 text-emerald-700 dark:bg-emerald-500/12 dark:text-emerald-400">منتشر شده</span>
                                @else
                                    <span class="ap-badge bg-red-50 text-red-600 dark:bg-red-500/12 dark:text-red-400">پیش‌نویس</span>
                                @endif
                            </td>
                            <td class="px-3 py-3 fa-digits text-ink-2 dark:text-night-ink-2">{{ number_format($form->fields_count) }} فیلد</td>
                            <td class="px-3 py-3">
                                @can('forms.submissions')
                                    <a href="{{ route('np.forms.submissions', ['form' => $form]) }}" wire:navigate
                                       class="ap-badge bg-brand-50 text-brand-700 transition-colors hover:bg-brand-100 dark:bg-brand-500/12 dark:text-brand-400">
                                        <span class="fa-digits">{{ number_format($form->submissions_count) }}</span> ارسال
                                    </a>
                                @else
                                    <span class="fa-digits text-ink-2 dark:text-night-ink-2">{{ number_format($form->submissions_count) }}</span>
                                @endcan
                            </td>
                            <td class="px-3 py-3 fa-digits whitespace-nowrap">{{ $this->jdate($form->created_at) }}</td>
                            <td class="px-3 py-3">
                                <div class="flex items-center gap-0.5">
                                    @can('forms.submissions')
                                        <a href="{{ route('np.forms.submissions', ['form' => $form]) }}" wire:navigate
                                           class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-brand-50 hover:text-brand-600 dark:hover:bg-brand-500/10"
                                           title="مشاهده پاسخ‌ها" aria-label="پاسخ‌ها">
                                            <x-admin-panel::icon name="message-circle" class="size-4" />
                                        </a>
                                    @endcan
                                    @can('forms.update')
                                        <a href="{{ route('np.forms.edit', ['form' => $form]) }}" wire:navigate
                                           class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-brand-50 hover:text-brand-600 dark:hover:bg-brand-500/10"
                                           title="ویرایش فرم" aria-label="ویرایش">
                                            <x-admin-panel::icon name="pencil" class="size-4" />
                                        </a>
                                    @endcan
                                    @if (Route::has('np.forms.preview'))
                                        @can('forms.preview')
                                            <a href="{{ route('np.forms.preview', ['form' => $form->id]) }}"
                                               class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-brand-50 hover:text-brand-600 dark:hover:bg-brand-500/10"
                                               title="پیش‌نمایش فرم" aria-label="پیش‌نمایش">
                                                <x-admin-panel::icon name="eye" class="size-4" />
                                            </a>
                                        @endcan
                                    @endif
                                    @can('forms.delete')
                                        <button type="button" wire:click="confirmDelete({{ $form->id }})"
                                                class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-500/10"
                                                title="حذف فرم" aria-label="حذف">
                                            <x-admin-panel::icon name="trash-2" class="size-4" />
                                        </button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9">
                                <div class="flex flex-col items-center gap-2 py-14 text-ink-3 dark:text-night-ink-2">
                                    <x-admin-panel::icon name="file-text" class="size-10 opacity-40" />
                                    <p class="text-sm font-medium">هنوز فرمی ساخته نشده است</p>
                                    @can('forms.create')
                                        <a href="{{ route('np.forms.create') }}" wire:navigate class="text-xs font-semibold text-brand-600 hover:underline dark:text-brand-400">
                                            ساخت اولین فرم
                                        </a>
                                    @endcan
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
                    نمایش <span class="fa-digits">{{ number_format($this->forms->firstItem() ?? 0) }}</span> تا
                    <span class="fa-digits">{{ number_format($this->forms->lastItem() ?? 0) }}</span> از
                    <span class="fa-digits">{{ number_format($this->forms->total()) }}</span> فرم
                </p>
                <nav class="flex items-center gap-1" aria-label="صفحه‌بندی">
                    @if ($this->forms->onFirstPage())
                        <span class="rounded-lg p-2 text-ink-3 opacity-40"><x-admin-panel::icon name="chevron-right" class="size-4" /></span>
                    @else
                        <button wire:click="previousPage" class="rounded-lg p-2 text-ink-2 transition-colors hover:bg-surface-3 dark:hover:bg-night-3" aria-label="صفحه قبل">
                            <x-admin-panel::icon name="chevron-right" class="size-4" />
                        </button>
                    @endif

                    @foreach ($this->forms->onEachSide(1)->toArray()['links'] as $link)
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

                    @if ($this->forms->hasMorePages())
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
            <h3 class="text-base font-bold">حذف فرم</h3>
            <p class="mt-1.5 text-[13px] leading-6 text-ink-2 dark:text-night-ink-2">
                با حذف فرم دیگر قادر به بازیابی آن نخواهید بود؛ همه فیلدها و پاسخ‌های این فرم هم حذف می‌شوند.
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
            <h3 class="text-base font-bold">حذف فرم‌های انتخاب‌شده</h3>
            <p class="mt-1.5 text-[13px] leading-6 text-ink-2 dark:text-night-ink-2">
                با حذف فرم‌ها دیگر قادر به بازیابی آنها نخواهید بود.
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
