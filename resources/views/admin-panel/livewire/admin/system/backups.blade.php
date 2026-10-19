{{-- لیست بکاپ‌ها — پورت back/backups/index.blade.php (فاز ۷-ج۴) --}}
<div class="mx-auto max-w-6xl space-y-5">

    {{-- سربرگ --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <span class="flex size-11 items-center justify-center rounded-2xl bg-brand-50 text-brand-600 dark:bg-brand-500/12 dark:text-brand-400">
                <x-admin-panel::icon name="archive" class="size-6" />
            </span>
            <div>
                <h1 class="text-lg font-bold">لیست بکاپ‌ها</h1>
                <p class="text-xs text-ink-3 dark:text-night-ink-2">فایل‌های پشتیبان روی دیسک backup</p>
            </div>
        </div>
        @can('backups.create')
            <button type="button" wire:click="create" wire:loading.attr="disabled" wire:target="create" class="ap-btn !py-2">
                <x-admin-panel::icon name="plus" class="size-4" />
                ایجاد بکاپ جدید
            </button>
        @endcan
    </div>

    {{-- جدول --}}
    <div class="ap-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[640px] text-sm">
                <thead>
                    <tr class="border-b border-line bg-surface-2 text-xs text-ink-3 dark:border-night-line dark:bg-night-3 dark:text-night-ink-2">
                        <th class="px-4 py-3 text-start font-semibold">نام فایل</th>
                        <th class="px-4 py-3 text-start font-semibold">تاریخ ایجاد</th>
                        <th class="px-4 py-3 text-start font-semibold">اندازه فایل</th>
                        <th class="px-4 py-3 text-center font-semibold">عملیات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line dark:divide-night-line">
                    @forelse ($this->backups as $backup)
                        <tr class="transition-colors hover:bg-surface-2 dark:hover:bg-night-3">
                            <td class="ltr px-4 py-3 font-mono text-xs">{{ $backup['name'] }}</td>
                            <td class="fa-digits px-4 py-3 text-xs text-ink-2 dark:text-night-ink-2">{{ \App\Livewire\Admin\System\BackupIndex::jdate($backup['date']) }}</td>
                            <td class="ltr px-4 py-3 text-xs text-ink-2 dark:text-night-ink-2">{{ $backup['size'] }}</td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-center gap-1.5">
                                    <button type="button" wire:click="download({{ \Illuminate\Support\Js::from($backup['name'])->toHtml() }})"
                                            wire:loading.attr="disabled" wire:target="download"
                                            class="flex items-center gap-1 rounded-lg px-2.5 py-1.5 text-xs font-semibold text-emerald-600 transition-colors hover:bg-emerald-50 dark:hover:bg-emerald-500/10">
                                        <x-admin-panel::icon name="download" class="size-4" />
                                        دانلود
                                    </button>
                                    @can('backups.delete')
                                        <button type="button" wire:click="confirmDelete({{ \Illuminate\Support\Js::from($backup['name'])->toHtml() }})"
                                                class="flex items-center gap-1 rounded-lg px-2.5 py-1.5 text-xs font-semibold text-red-500 transition-colors hover:bg-red-50 dark:hover:bg-red-500/10">
                                            <x-admin-panel::icon name="trash-2" class="size-4" />
                                            حذف
                                        </button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-12 text-center">
                                <span class="mx-auto mb-2 flex size-12 items-center justify-center rounded-2xl bg-surface-2 text-ink-3 dark:bg-night-3">
                                    <x-admin-panel::icon name="archive" class="size-6" />
                                </span>
                                <p class="text-sm font-bold">چیزی برای نمایش وجود ندارد!</p>
                                <p class="mt-1 text-xs text-ink-3 dark:text-night-ink-2">هنوز بکاپی ساخته نشده است.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- مودال تایید حذف بکاپ (پورت delete-modal قدیمی) --}}
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
            <h3 class="text-base font-bold">آیا مطمئن هستید؟</h3>
            <p class="mt-1.5 text-[13px] leading-6 text-ink-2 dark:text-night-ink-2">
                با حذف بکاپ دیگر قادر به بازیابی آن نخواهید بود.
            </p>
            @if ($confirmingDelete)
                <p class="ltr mt-2 rounded-lg bg-surface-2 px-2.5 py-1.5 text-center font-mono text-[11px] dark:bg-night-3">{{ $confirmingDelete }}</p>
            @endif
            <div class="mt-5 flex gap-2.5">
                <button type="button" wire:click="delete" wire:loading.attr="disabled"
                        class="ap-btn flex-1 bg-red-600 text-white hover:bg-red-700">
                    بله حذف شود
                </button>
                <button type="button" x-on:click="open=false" class="ap-btn-ghost flex-1">خیر</button>
            </div>
        </div>
    </div>

    {{-- لودینگ --}}
    <div wire:loading wire:target="create, delete, download"
         class="pointer-events-none fixed bottom-6 start-6 z-[65] flex items-center gap-2 rounded-xl border border-line bg-surface px-4 py-2.5 shadow-pop dark:border-night-line dark:bg-night-3">
        <svg class="size-4 animate-spin text-brand-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
            <path d="M21 12a9 9 0 1 1-6.219-8.56" stroke-linecap="round" />
        </svg>
        <span class="text-[13px] font-semibold">در حال بارگذاری…</span>
    </div>
</div>
