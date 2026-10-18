{{-- دلایل مرجوعی — پنل جدید (فاز ۴) پورت back/returns/reasons.blade.php --}}
<div class="mx-auto max-w-5xl space-y-5">

    {{-- سربرگ --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-base font-extrabold">دلایل مرجوعی</h1>
            <p class="mt-1 text-xs text-ink-3 dark:text-night-ink-2">مدیریت گزینه‌هایی که کاربر هنگام ثبت مرجوعی انتخاب می‌کند</p>
        </div>
        <a href="{{ route('np.returns.index') }}" wire:navigate class="ap-btn-outline !py-2">
            <x-admin-panel::icon name="undo-2" class="size-4" />
            بازگشت به مرجوعی‌ها
        </a>
    </div>

    {{-- فرم افزودن دلیل --}}
    @can('returns.reasons.store')
        <div class="ap-card p-4 sm:p-5">
            <h2 class="mb-4 flex items-center gap-2 text-sm font-bold">
                <x-admin-panel::icon name="plus" class="size-4.5 text-brand-600 dark:text-brand-400" />
                افزودن دلیل جدید
            </h2>
            <form wire:submit.prevent="create" class="grid grid-cols-1 gap-3.5 sm:grid-cols-2 lg:grid-cols-4">
                <div>
                    <label class="mb-1.5 block text-xs font-semibold" for="reason-title">عنوان دلیل</label>
                    <input id="reason-title" type="text" wire:model="title" class="ap-input !py-2"
                           placeholder="مثلاً: خرابی محصول">
                    @error('title') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold" for="reason-description">توضیحات (اختیاری)</label>
                    <input id="reason-description" type="text" wire:model="description" class="ap-input !py-2"
                           placeholder="توضیح بیشتر">
                    @error('description') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div class="flex items-end">
                    <label class="flex cursor-pointer items-center gap-2 text-[13px] font-semibold">
                        <input type="checkbox" wire:model="isActive"
                               class="size-4 rounded-md border-line text-brand-600 focus:ring-brand-500/30 dark:border-night-line">
                        فعال
                    </label>
                </div>
                <div class="flex items-end">
                    <button type="submit" wire:loading.attr="disabled" class="ap-btn !py-2 w-full">
                        <x-admin-panel::icon name="plus" class="size-4" />
                        افزودن
                    </button>
                </div>
            </form>
        </div>
    @endcan

    {{-- جدول دلایل --}}
    <div class="ap-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full min-w-3xl text-start text-[13px]">
                <thead>
                    <tr class="border-b border-line bg-surface-2 text-ink-2 dark:border-night-line dark:bg-night-3">
                        <th class="w-14 px-3 py-3 text-start font-bold">#</th>
                        <th class="px-3 py-3 text-start font-bold">عنوان</th>
                        <th class="px-3 py-3 text-start font-bold">توضیحات</th>
                        <th class="px-3 py-3 text-start font-bold">وضعیت</th>
                        <th class="px-3 py-3 text-start font-bold">تعداد استفاده</th>
                        <th class="w-20 px-3 py-3 text-start font-bold">عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->reasons as $reason)
                        <tr wire:key="reason-{{ $reason->id }}"
                            class="border-b border-line/60 transition-colors last:border-0 hover:bg-surface-2 dark:border-night-line/60 dark:hover:bg-night-3/60">
                            <td class="px-3 py-3 fa-digits font-bold">{{ number_format($reason->id) }}</td>
                            <td class="px-3 py-3 font-semibold">{{ $reason->title }}</td>
                            <td class="px-3 py-3 text-ink-2 dark:text-night-ink-2">{{ $reason->description ?? '—' }}</td>
                            <td class="px-3 py-3">
                                <div class="flex items-center gap-2">
                                    <span class="ap-badge {{ $reason->is_active
                                        ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/12 dark:text-emerald-400'
                                        : 'bg-slate-100 text-slate-600 dark:bg-night-3 dark:text-night-ink-2' }}">
                                        {{ $reason->is_active ? 'فعال' : 'غیرفعال' }}
                                    </span>
                                    @can('returns.reasons.toggle')
                                        <button type="button" wire:click="toggle({{ $reason->id }})"
                                                class="relative inline-flex h-6 w-11 shrink-0 items-center rounded-full transition-colors {{ $reason->is_active ? 'bg-emerald-500' : 'bg-slate-300 dark:bg-night-3' }}"
                                                role="switch" aria-checked="{{ $reason->is_active ? 'true' : 'false' }}"
                                                aria-label="تغییر وضعیت دلیل {{ $reason->title }}">
                                            <span class="inline-block size-4 translate-x-0 rounded-full bg-white shadow transition-transform {{ $reason->is_active ? '-translate-x-6' : 'translate-x-0' }}"></span>
                                        </button>
                                    @endcan
                                </div>
                            </td>
                            <td class="px-3 py-3">
                                <span class="fa-digits inline-flex rounded-lg bg-surface-2 px-2.5 py-1 font-bold dark:bg-night-3">{{ number_format($reason->return_requests_count) }}</span>
                            </td>
                            <td class="px-3 py-3">
                                @can('returns.reasons.destroy')
                                    <button type="button" wire:click="confirmDelete({{ $reason->id }})"
                                            class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-500/10"
                                            title="حذف دلیل" aria-label="حذف">
                                        <x-admin-panel::icon name="trash-2" class="size-4" />
                                    </button>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
                                <div class="flex flex-col items-center gap-2 py-14 text-ink-3 dark:text-night-ink-2">
                                    <x-admin-panel::icon name="list" class="size-10 opacity-40" />
                                    <p class="text-sm font-medium">هنوز دلیل مرجوعی ثبت نشده است</p>
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
                    نمایش <span class="fa-digits">{{ number_format($this->reasons->firstItem() ?? 0) }}</span> تا
                    <span class="fa-digits">{{ number_format($this->reasons->lastItem() ?? 0) }}</span> از
                    <span class="fa-digits">{{ number_format($this->reasons->total()) }}</span> دلیل
                </p>
                <nav class="flex items-center gap-1" aria-label="صفحه‌بندی">
                    @if ($this->reasons->onFirstPage())
                        <span class="rounded-lg p-2 text-ink-3 opacity-40"><x-admin-panel::icon name="chevron-right" class="size-4" /></span>
                    @else
                        <button wire:click="previousPage" class="rounded-lg p-2 text-ink-2 transition-colors hover:bg-surface-3 dark:hover:bg-night-3" aria-label="صفحه قبل">
                            <x-admin-panel::icon name="chevron-right" class="size-4" />
                        </button>
                    @endif

                    @foreach ($this->reasons->onEachSide(1)->toArray()['links'] as $link)
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

                    @if ($this->reasons->hasMorePages())
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

    {{-- مودال تایید حذف --}}
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
            <h3 class="text-base font-bold">حذف دلیل مرجوعی</h3>
            <p class="mt-1.5 text-[13px] leading-6 text-ink-2 dark:text-night-ink-2">
                این دلیل حذف خواهد شد. درخواست‌های مرجوعی ثبت‌شده با این دلیل بدون دلیل می‌شوند و قابل بازیابی نیست.
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

    {{-- لودینگ --}}
    <div wire:loading wire:target="create, toggle, delete, previousPage, nextPage, gotoPage"
         class="pointer-events-none fixed bottom-6 start-6 z-[65] flex items-center gap-2 rounded-xl border border-line bg-surface px-4 py-2.5 shadow-pop dark:border-night-line dark:bg-night-3">
        <svg class="size-4 animate-spin text-brand-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
            <path d="M21 12a9 9 0 1 1-6.219-8.56" stroke-linecap="round" />
        </svg>
        <span class="text-[13px] font-semibold">در حال بارگذاری…</span>
    </div>
</div>
