{{-- پشتیبان‌گیری و بازیابی — فاز ۱۸: بازطراحی کامل مثل نمونه‌ی مرجع + مودال انتخابی تمام موارد
     منطق: SiteBackupService (بدون صف، بدون باینری خارجی) + بازیابی skip-if-exists با گزارش --}}
<div class="mx-auto max-w-6xl space-y-5">

    {{-- سربرگ --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <span class="flex size-11 items-center justify-center rounded-2xl bg-brand-50 text-brand-600 dark:bg-brand-500/12 dark:text-brand-400">
                <x-admin-panel::icon name="archive" class="size-6" />
            </span>
            <div>
                <h1 class="text-lg font-bold">پشتیبان‌گیری و بازیابی</h1>
                <p class="text-xs text-ink-3 dark:text-night-ink-2">از همه‌ی اطلاعات — دیتابیس و فایل‌ها — یک فایل ZIP بگیرید و هر زمان همان فایل را بازیابی کنید تا همه‌چیز سر جای خودش برگردد.</p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <span class="ap-badge bg-surface-2 text-ink-2 dark:bg-night-3 dark:text-night-ink-2">
                <x-admin-panel::icon name="database" class="size-3.5" />
                دیتابیس: <span class="ltr">{{ $this->stats['driver'] }}</span>
            </span>
            <span class="ap-badge bg-surface-2 text-ink-2 dark:bg-night-3 dark:text-night-ink-2">
                <x-admin-panel::icon name="file-archive" class="size-3.5" />
                <span class="fa-digits">{{ number_format(count($this->backups)) }} فایل پشتیبان</span>
            </span>
        </div>
    </div>

    {{-- ============ دو کارت اصلی ============ --}}
    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">

        {{-- ساخت نسخه پشتیبان جدید --}}
        <div class="ap-card flex flex-col p-5">
            <div class="mb-3 flex items-start justify-between gap-3">
                <div>
                    <h2 class="text-sm font-bold">ساخت نسخه پشتیبان جدید</h2>
                    <p class="mt-1 text-xs leading-6 text-ink-3 dark:text-night-ink-2">
                        کل دیتابیس + تصاویر و فایل‌ها در یک فایل ZIP بایگانی می‌شود و در فهرست «فایل‌های پشتیبان» قابل دانلود و حذف است.
                    </p>
                </div>
                <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-500/12 dark:text-emerald-400">
                    <x-admin-panel::icon name="archive" class="size-5" />
                </span>
            </div>

            <div class="mb-4 flex flex-wrap items-center gap-1.5">
                <span class="ap-badge bg-surface-2 text-ink-2 dark:bg-night-3 dark:text-night-ink-2">
                    <x-admin-panel::icon name="table" class="size-3.5" />
                    <span class="fa-digits">{{ number_format($this->stats['table_count']) }} جدول</span>
                </span>
                <span class="ap-badge bg-surface-2 text-ink-2 dark:bg-night-3 dark:text-night-ink-2">
                    <x-admin-panel::icon name="list" class="size-3.5" />
                    <span class="fa-digits">{{ number_format($this->stats['row_count']) }} رکورد</span>
                </span>
                <span class="ap-badge bg-surface-2 text-ink-2 dark:bg-night-3 dark:text-night-ink-2">
                    <x-admin-panel::icon name="image" class="size-3.5" />
                    <span class="fa-digits">{{ number_format($this->stats['file_count']) }} فایل ({{ \App\Livewire\Admin\System\BackupIndex::faSize($this->stats['file_size']) }})</span>
                </span>
            </div>

            <div class="mt-auto flex items-center justify-between gap-3 border-t border-line pt-3.5 dark:border-night-line">
                <span class="text-[11px] text-ink-3 dark:text-night-ink-2">انتخاب موارد در مودال بعدی — پیش‌فرض همه انتخاب شده‌اند.</span>
                @can('backups.create')
                    <button type="button" x-data x-on:click="$dispatch('open-backup-create')"
                            class="ap-btn-primary !py-2.5">
                        <x-admin-panel::icon name="archive" class="size-4" />
                        ساخت نسخه پشتیبان
                    </button>
                @endcan
            </div>
        </div>

        {{-- بازیابی نسخه پشتیبان --}}
        <div class="ap-card flex flex-col p-5">
            <div class="mb-3 flex items-start justify-between gap-3">
                <div>
                    <h2 class="text-sm font-bold">بازیابی نسخه پشتیبان</h2>
                    <p class="mt-1 text-xs leading-6 text-ink-3 dark:text-night-ink-2">
                        فایل ZIP پشتیبان را آپلود کنید تا دیتابیس و فایل‌ها به‌کامل به سر جای‌شان برگردند؛ در پایان گزارش کامل نمایش داده می‌شود.
                    </p>
                </div>
                <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-amber-50 text-amber-600 dark:bg-amber-500/12 dark:text-amber-400">
                    <x-admin-panel::icon name="upload" class="size-5" />
                </span>
            </div>

            <div class="mb-3.5 flex items-start gap-2 rounded-xl border border-amber-200 bg-amber-50 p-3 text-[11px] leading-5 text-amber-800 dark:border-amber-500/20 dark:bg-amber-500/10 dark:text-amber-300">
                <x-admin-panel::icon name="triangle-alert" class="mt-0.5 size-4 shrink-0" />
                <span>
                    <b>هیچ داده‌ای حذف یا بازنویسی نمی‌شود.</b>
                    هر رکورد و فایلی بررسی می‌شود؛ اگر از قبل وجود داشته باشد رد می‌شود و فقط موارد جدید درج می‌گردند.
                </span>
            </div>

            @can('backups.create')
                <form wire:submit="restoreRun" class="mt-auto space-y-3">
                    <label class="block text-xs font-semibold" for="restore-file">آپلود فایل پشتیبان (ZIP)</label>
                    <input id="restore-file" type="file" accept=".zip"
                           wire:model="restoreFile"
                           class="block w-full cursor-pointer rounded-xl border border-line bg-surface-2 p-2.5 text-xs file:me-3 file:cursor-pointer file:rounded-lg file:border-0 file:bg-brand-600 file:px-3 file:py-1.5 file:text-xs file:font-bold file:text-white dark:border-night-line dark:bg-night-3">

                    @error('restoreFile') <p class="text-xs font-medium text-red-600">{{ $message }}</p> @enderror

                    @if ($restoreFile)
                        <div class="flex items-center gap-2 rounded-xl border border-line bg-surface-2 px-3 py-2 text-xs dark:border-night-line dark:bg-night-3">
                            <x-admin-panel::icon name="file-archive" class="size-4 text-emerald-500" />
                            <span class="ltr truncate font-mono">{{ $restoreFile->getClientOriginalName() }}</span>
                            <span class="fa-digits ms-auto shrink-0 text-ink-3">({{ number_format($restoreFile->getSize() / 1048576, 1) }} مگابایت)</span>
                        </div>
                    @endif

                    @if (count($this->incoming))
                        <div class="rounded-xl border border-line p-2.5 dark:border-night-line">
                            <p class="mb-1.5 flex items-center gap-1.5 text-[11px] font-bold text-ink-2 dark:text-night-ink-2">
                                <x-admin-panel::icon name="folder-open" class="size-3.5" />
                                فایل‌های آماده در <span class="ltr">storage/app/backups/incoming</span>
                            </p>
                            <div class="max-h-28 space-y-1 overflow-y-auto">
                                @foreach ($this->incoming as $incoming)
                                    <div class="flex items-center gap-2 rounded-lg bg-surface-2 px-2.5 py-1.5 text-[11px] dark:bg-night-3" wire:key="inc-{{ $incoming['name'] }}">
                                        <span class="ltr min-w-0 flex-1 truncate font-mono">{{ $incoming['name'] }}</span>
                                        <span class="fa-digits shrink-0 text-ink-3">{{ \App\Livewire\Admin\System\BackupIndex::faSize($incoming['size']) }}</span>
                                        <button type="button" wire:click="restoreIncoming('{{ $incoming['name'] }}')"
                                                wire:loading.attr="disabled" wire:target="restoreIncoming"
                                                class="shrink-0 rounded-lg bg-emerald-600 px-2.5 py-1 text-[10px] font-bold text-white transition-colors hover:bg-emerald-700">
                                            بازیابی
                                        </button>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <div class="flex items-center justify-between gap-3">
                        <span class="text-[10px] leading-4 text-ink-3 dark:text-night-ink-2">فایل حجیم (بیش از ~۵۰۰ مگابایت) را از FTP/فایل‌منیجر در مسیر storage/app/backups/incoming بگذارید تا از فهرست بالا بازیابی شود.</span>
                        <button type="submit" wire:loading.attr="disabled" wire:target="restoreRun"
                                class="ap-btn-primary !py-2.5">
                            <span wire:loading.remove wire:target="restoreRun" class="flex items-center gap-2">
                                <x-admin-panel::icon name="upload" class="size-4" />
                                بازیابی اطلاعات
                            </span>
                            <span wire:loading wire:target="restoreRun" class="flex items-center gap-2">
                                <svg class="size-4 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                                    <path d="M21 12a9 9 0 1 1-6.219-8.56" stroke-linecap="round" />
                                </svg>
                                در حال بازیابی…
                            </span>
                        </button>
                    </div>
                </form>
            @endcan
        </div>
    </div>

    {{-- ============ گزارش آخرین بازیابی ============ --}}
    @if ($report = $this->report)
        <div class="ap-card overflow-hidden" wire:key="restore-report">
            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-line px-4 py-3.5 dark:border-night-line">
                <div class="flex items-center gap-2.5">
                    <span class="flex size-8 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600 dark:bg-emerald-500/12 dark:text-emerald-400">
                        <x-admin-panel::icon name="circle-check" class="size-4.5" />
                    </span>
                    <h2 class="text-sm font-bold">گزارش آخرین بازیابی</h2>
                </div>
                <div class="text-[11px] text-ink-3 dark:text-night-ink-2">
                    فایل: <span class="ltr font-mono">{{ $report['file'] }}</span>
                    <span class="fa-digits mx-1">— {{ $report['date_jalali'] ?? \App\Livewire\Admin\System\BackupIndex::jdate($report['date'] ?? null) }}</span>
                </div>
            </div>

            <div class="border-b border-line px-4 py-3 dark:border-night-line">
                <div class="flex flex-wrap items-center gap-1.5">
                    <span class="ap-badge bg-emerald-50 text-emerald-700 dark:bg-emerald-500/12 dark:text-emerald-400">
                        <span class="fa-digits">{{ number_format($report['summary']['rows_added']) }} رکورد افزوده شد</span>
                    </span>
                    <span class="ap-badge bg-sky-50 text-sky-700 dark:bg-sky-500/12 dark:text-sky-400">
                        <span class="fa-digits">{{ number_format($report['summary']['rows_skipped']) }} رکورد از قبل موجود (رد شد)</span>
                    </span>
                    <span class="ap-badge bg-surface-2 text-ink-2 dark:bg-night-3 dark:text-night-ink-2">
                        <span class="fa-digits">{{ number_format($report['summary']['files_added']) }} فایل بازیابی شد</span>
                    </span>
                    <span class="ap-badge bg-surface-2 text-ink-2 dark:bg-night-3 dark:text-night-ink-2">
                        <span class="fa-digits">{{ number_format($report['summary']['files_skipped']) }} فایل تکراری (رد شد)</span>
                    </span>
                    @if ($report['summary']['tables_created'] > 0)
                        <span class="ap-badge bg-violet-50 text-violet-700 dark:bg-violet-500/12 dark:text-violet-400">
                            <span class="fa-digits">{{ number_format($report['summary']['tables_created']) }} جدول ساخته شد</span>
                        </span>
                    @endif
                    @if ($report['summary']['tables_skipped'] > 0)
                        <span class="ap-badge bg-amber-50 text-amber-700 dark:bg-amber-500/12 dark:text-amber-400">
                            <span class="fa-digits">{{ number_format($report['summary']['tables_skipped']) }} جدول موجود (رد شد)</span>
                        </span>
                    @endif
                    @if (($report['summary']['rows_failed'] + $report['summary']['files_failed'] + $report['summary']['tables_failed']) > 0)
                        <span class="ap-badge bg-red-50 text-red-600 dark:bg-red-500/12 dark:text-red-400">
                            <span class="fa-digits">{{ number_format($report['summary']['rows_failed'] + $report['summary']['files_failed'] + $report['summary']['tables_failed']) }} مورد ناموفق</span>
                        </span>
                    @endif
                </div>
            </div>

            @if (count($report['tables']))
                <div class="max-h-80 overflow-y-auto">
                    <table class="w-full min-w-[560px] text-sm">
                        <thead class="sticky top-0 z-[1]">
                            <tr class="border-b border-line bg-surface-2 text-xs text-ink-3 dark:border-night-line dark:bg-night-3 dark:text-night-ink-2">
                                <th class="px-4 py-2.5 text-start font-semibold">جدول</th>
                                <th class="px-4 py-2.5 text-center font-semibold">افزوده‌شده</th>
                                <th class="px-4 py-2.5 text-center font-semibold">رد شده (موجود)</th>
                                <th class="px-4 py-2.5 text-center font-semibold">اصلاح‌شده</th>
                                <th class="px-4 py-2.5 text-center font-semibold">ناموفق</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line dark:divide-night-line">
                            @foreach ($report['tables'] as $row)
                                <tr class="transition-colors hover:bg-surface-2 dark:hover:bg-night-3">
                                    <td class="ltr px-4 py-2.5 font-mono text-xs">{{ $row['name'] }}</td>
                                    <td class="fa-digits px-4 py-2.5 text-center text-xs font-bold {{ $row['added'] > 0 ? 'text-emerald-600' : 'text-ink-3' }}">{{ number_format($row['added']) }}</td>
                                    <td class="fa-digits px-4 py-2.5 text-center text-xs {{ $row['skipped'] > 0 ? 'text-sky-600' : 'text-ink-3' }}">{{ number_format($row['skipped']) }}</td>
                                    <td class="fa-digits px-4 py-2.5 text-center text-xs text-ink-3">{{ number_format($row['modified']) }}</td>
                                    <td class="fa-digits px-4 py-2.5 text-center text-xs {{ $row['failed'] > 0 ? 'font-bold text-red-500' : 'text-ink-3' }}">{{ number_format($row['failed']) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            @if (count($report['skipped_tables']))
                <div class="flex flex-wrap items-center gap-2 border-t border-line bg-amber-50/60 px-4 py-3 dark:border-night-line dark:bg-amber-500/10">
                    <span class="flex items-center gap-1.5 text-xs font-bold text-amber-800 dark:text-amber-300">
                        <x-admin-panel::icon name="triangle-alert" class="size-4" />
                        <span class="fa-digits">{{ count($report['skipped_tables']) }} جدول از بازیابی رد شد:</span>
                    </span>
                    <div class="flex flex-wrap gap-1">
                        @foreach ($report['skipped_tables'] as $skipped)
                            <span class="ltr rounded-md bg-surface px-2 py-0.5 font-mono text-[10px] dark:bg-night-2">{{ $skipped }}</span>
                        @endforeach
                    </div>
                </div>
            @endif

            @if (count($report['failed_notes']))
                <div class="border-t border-line bg-red-50/50 px-4 py-3 dark:border-night-line dark:bg-red-500/10">
                    <p class="mb-1 text-xs font-bold text-red-700 dark:text-red-400">یادداشت‌های خطا:</p>
                    <ul class="space-y-0.5 text-[11px] leading-5 text-red-600 dark:text-red-400">
                        @foreach ($report['failed_notes'] as $note)
                            <li class="ltr font-mono">• {{ $note }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    @endif

    {{-- ============ فایل‌های پشتیبان و عملیات آخر ============ --}}
    <div class="ap-card overflow-hidden">
        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-line px-4 py-3.5 dark:border-night-line">
            <div class="flex items-center gap-2.5">
                <span class="flex size-8 items-center justify-center rounded-lg bg-brand-50 text-brand-600 dark:bg-brand-500/12 dark:text-brand-400">
                    <x-admin-panel::icon name="archive" class="size-4.5" />
                </span>
                <div>
                    <h2 class="text-sm font-bold">فایل‌های پشتیبان و عملیات آخر</h2>
                    <p class="text-[11px] text-ink-3 dark:text-night-ink-2">نسخه‌های ساخته‌شده را دانلود یا حذف کنید</p>
                </div>
            </div>
        </div>

        <div class="divide-y divide-line dark:divide-night-line">
            @forelse ($this->backups as $backup)
                <div class="flex flex-wrap items-center gap-3 px-4 py-3 transition-colors hover:bg-surface-2 dark:hover:bg-night-3" wire:key="backup-{{ $backup['name'] }}">
                    <span class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-amber-50 text-amber-600 dark:bg-amber-500/12 dark:text-amber-400">
                        <x-admin-panel::icon name="upload" class="size-4" />
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="ltr truncate font-mono text-xs font-bold">{{ $backup['name'] }}</p>
                        <p class="fa-digits mt-0.5 text-[10px] text-ink-3 dark:text-night-ink-2">تاریخ: {{ \App\Livewire\Admin\System\BackupIndex::jdate($backup['date']) }}</p>
                    </div>
                    <span class="ap-badge bg-emerald-50 text-emerald-700 dark:bg-emerald-500/12 dark:text-emerald-400">تکمیل شد</span>
                    <span class="ltr text-[11px] text-ink-3 dark:text-night-ink-2">{{ $backup['size'] }}</span>
                    <div class="flex items-center gap-1.5">
                        <button type="button" wire:click="download('{{ $backup['name'] }}')"
                                wire:loading.attr="disabled" wire:target="download"
                                class="flex items-center gap-1.5 rounded-lg bg-amber-50 px-3 py-1.5 text-xs font-bold text-amber-700 transition-colors hover:bg-amber-100 dark:bg-amber-500/12 dark:text-amber-400">
                            <x-admin-panel::icon name="download" class="size-3.5" />
                            دانلود
                        </button>
                        @can('backups.delete')
                            <button type="button" wire:click="confirmDelete('{{ $backup['name'] }}')"
                                    class="flex items-center gap-1.5 rounded-lg bg-red-50 px-3 py-1.5 text-xs font-bold text-red-600 transition-colors hover:bg-red-100 dark:bg-red-500/12 dark:text-red-400">
                                <x-admin-panel::icon name="trash-2" class="size-3.5" />
                                حذف
                            </button>
                        @endcan
                    </div>
                </div>
            @empty
                <div class="px-4 py-12 text-center">
                    <span class="mx-auto mb-2 flex size-12 items-center justify-center rounded-2xl bg-surface-2 text-ink-3 dark:bg-night-3">
                        <x-admin-panel::icon name="archive" class="size-6" />
                    </span>
                    <p class="text-sm font-bold">چیزی برای نمایش وجود ندارد!</p>
                    <p class="mt-1 text-xs text-ink-3 dark:text-night-ink-2">هنوز نسخه پشتیبانی ساخته نشده است — از کارت «ساخت نسخه پشتیبان جدید» شروع کنید.</p>
                </div>
            @endforelse
        </div>
    </div>

    {{-- ============ مودال ساخت بکاپ انتخابی (تمام موارد) ============ --}}
    @can('backups.create')
        <div x-data="apBackupCreate({
                    tables: @js($this->tables),
                    groups: @js($this->fileGroups['groups']),
                })"
             x-on:open-backup-create.window="open = true"
             x-on:keydown.escape.window="open = false"
             class="contents">

            <div x-show="open" x-cloak
                 class="fixed inset-0 z-[100] flex items-center justify-center p-2 sm:p-6"
                 role="dialog" aria-modal="true" aria-label="ساخت نسخه پشتیبان انتخابی">
                <div class="absolute inset-0 bg-slate-950/55 backdrop-blur-sm" x-show="open" x-on:click="open = false" x-transition.opacity aria-hidden="true"></div>

                <div x-show="open"
                     x-on:click.stop
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 scale-95"
                     x-transition:enter-end="opacity-100 scale-100"
                     class="relative flex h-full w-full max-w-3xl flex-col overflow-hidden rounded-2xl border border-line bg-surface shadow-pop dark:border-night-line dark:bg-night-2">

                    {{-- سربرگ --}}
                    <div class="flex items-center justify-between gap-3 border-b border-line px-4 py-3.5 dark:border-night-line">
                        <div class="flex items-center gap-2.5">
                            <span class="flex size-9 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-500/12 dark:text-emerald-400">
                                <x-admin-panel::icon name="archive" class="size-4.5" />
                            </span>
                            <div>
                                <h3 class="text-sm font-bold">ساخت نسخه پشتیبان انتخابی</h3>
                                <p class="text-[11px] text-ink-3 dark:text-night-ink-2">تمام موارد انتخاب‌شده به ZIP اضافه می‌شوند — پیش‌فرض همه‌چیز تیک خورده است</p>
                            </div>
                        </div>
                        <button type="button" x-on:click="open = false" class="ap-btn-ghost !p-2" title="بستن">
                            <x-admin-panel::icon name="x" class="size-4.5" />
                        </button>
                    </div>

                    {{-- تب‌ها --}}
                    <div class="flex gap-1 overflow-x-auto border-b border-line bg-surface-2/60 px-3 pt-2 dark:border-night-line dark:bg-night-3/50" role="tablist">
                        <button type="button" class="ap-tab whitespace-nowrap" x-on:click="tab = 'tables'"
                                x-bind:class="tab === 'tables' ? 'is-active' : ''" role="tab">
                            <x-admin-panel::icon name="database" class="size-4" />
                            جداول دیتابیس
                            <span class="fa-digits rounded-md bg-surface-3 px-1.5 py-0.5 text-[9px] font-bold text-ink-3 dark:bg-night-2 dark:text-night-ink-2" x-text="tables.length"></span>
                        </button>
                        <button type="button" class="ap-tab whitespace-nowrap" x-on:click="tab = 'uploads'"
                                x-bind:class="tab === 'uploads' ? 'is-active' : ''" role="tab">
                            <x-admin-panel::icon name="image" class="size-4" />
                            آپلودهای سایت
                            <span class="fa-digits rounded-md bg-surface-3 px-1.5 py-0.5 text-[9px] font-bold text-ink-3 dark:bg-night-2 dark:text-night-ink-2" x-text="groups.filter(g => g.root === 'public/uploads').length"></span>
                        </button>
                        <button type="button" class="ap-tab whitespace-nowrap" x-on:click="tab = 'media'"
                                x-bind:class="tab === 'media' ? 'is-active' : ''" role="tab">
                            <x-admin-panel::icon name="folder" class="size-4" />
                            رسانه‌های مدیریت
                            <span class="fa-digits rounded-md bg-surface-3 px-1.5 py-0.5 text-[9px] font-bold text-ink-3 dark:bg-night-2 dark:text-night-ink-2" x-text="groups.filter(g => g.root === 'storage/app/public').length"></span>
                        </button>
                    </div>

                    {{-- بدنه --}}
                    <div class="flex-1 overflow-y-auto p-4">

                        {{-- تب جداول --}}
                        <div x-show="tab === 'tables'" x-cloak>
                            <div class="mb-3 flex flex-wrap items-center gap-2">
                                <div class="relative min-w-44 flex-1">
                                    <x-admin-panel::icon name="search" class="absolute start-3 top-1/2 size-3.5 -translate-y-1/2 text-ink-3" />
                                    <input type="text" x-model="tableSearch" placeholder="جستجوی جدول…"
                                           class="ap-input !py-2 ps-8 text-xs" aria-label="جستجوی جدول">
                                </div>
                                <label class="flex cursor-pointer items-center gap-2 rounded-xl border px-3 py-2 text-xs font-bold transition-colors"
                                       :class="allTablesSelected ? 'border-brand-500 bg-brand-50 dark:bg-brand-500/10' : 'border-line dark:border-night-line'">
                                    <input type="checkbox" class="size-4" x-on:click.stop
                                           x-bind:checked="allTablesSelected"
                                           x-on:change="toggleAllTables($event.target.checked)">
                                    همه (<span class="fa-digits" x-text="tables.length"></span>)
                                </label>
                            </div>

                            <div class="max-h-80 space-y-1.5 overflow-y-auto rounded-xl border border-line p-2 dark:border-night-line ap-scroll">
                                <template x-for="table in filteredTables" :key="table.name">
                                    <label class="flex cursor-pointer items-center gap-2.5 rounded-lg px-2.5 py-2 transition-colors hover:bg-surface-2 dark:hover:bg-night-3"
                                           :class="selected.tables[table.name] ? 'bg-brand-50/50 dark:bg-brand-500/5' : ''">
                                        <input type="checkbox" class="size-4 cursor-pointer rounded"
                                               x-bind:checked="selected.tables[table.name]"
                                               x-on:change="selected.tables[table.name] = $event.target.checked">
                                        <span class="ltr flex-1 truncate font-mono text-xs font-semibold" x-text="table.name"></span>
                                        <span class="fa-digits shrink-0 text-[10px] text-ink-3 dark:text-night-ink-2">
                                            <span x-text="Number(table.rows).toLocaleString('fa-IR')"></span> رکورد
                                        </span>
                                    </label>
                                </template>
                                <p x-show="filteredTables.length === 0" class="py-6 text-center text-xs text-ink-3">جدولی یافت نشد.</p>
                            </div>
                        </div>

                        {{-- تب فایل‌ها (دو ریشه — حلقه سمت سرور تا `:attr`ها PHP معتبر باشند) --}}
                        <div x-show="tab !== 'tables'" x-cloak class="space-y-5">
                            @foreach ([
                                ['tab' => 'uploads', 'id' => 'public/uploads', 'label' => 'آپلودهای سایت', 'path' => 'public/uploads', 'icon' => 'image'],
                                ['tab' => 'media', 'id' => 'storage/app/public', 'label' => 'رسانه‌های مدیریت', 'path' => 'storage/app/public', 'icon' => 'folder'],
                            ] as $rootDef)
                                <div x-show="tab === @js($rootDef['tab'])" wire:key="root-{{ $loop->index }}">
                                    <div class="mb-2.5 flex flex-wrap items-center justify-between gap-2">
                                        <p class="flex items-center gap-1.5 text-[13px] font-bold">
                                            <x-admin-panel::icon name="{{ $rootDef['icon'] }}" class="size-4 text-brand-600 dark:text-brand-400" />
                                            {{ $rootDef['label'] }} — <span class="ltr">{{ $rootDef['path'] }}</span>
                                        </p>
                                        <label class="flex cursor-pointer items-center gap-2 rounded-xl border px-3 py-1.5 text-xs font-bold transition-colors"
                                               :class="allGroupsSelected(@js($rootDef['id'])) ? 'border-brand-500 bg-brand-50 dark:bg-brand-500/10' : 'border-line dark:border-night-line'">
                                            <input type="checkbox" class="size-4" x-on:click.stop
                                                   x-bind:checked="allGroupsSelected(@js($rootDef['id']))"
                                                   x-on:change="toggleAllGroups(@js($rootDef['id']), $event.target.checked)">
                                            همه
                                        </label>
                                    </div>

                                    <div class="max-h-72 space-y-1.5 overflow-y-auto rounded-xl border border-line p-2 dark:border-night-line">
                                        <template x-for="group in groups.filter(g => g.root === @js($rootDef['id']))" :key="group.id">
                                            <label class="flex cursor-pointer items-center gap-2.5 rounded-lg px-2.5 py-2 transition-colors hover:bg-surface-2 dark:hover:bg-night-3"
                                                   :class="selected.groups[group.id] ? 'bg-brand-50/50 dark:bg-brand-500/5' : ''">
                                                <input type="checkbox" class="size-4 cursor-pointer rounded"
                                                       x-bind:checked="selected.groups[group.id]"
                                                       x-on:change="selected.groups[group.id] = $event.target.checked">
                                                <span class="flex size-8 shrink-0 items-center justify-center rounded-lg"
                                                      :class="group.images > 0 && group.images === group.files ? 'bg-pink-50 text-pink-500 dark:bg-pink-500/12' : 'bg-sky-50 text-sky-600 dark:bg-sky-500/12 dark:text-sky-400'">
                                                    <span x-show="group.images > 0 && group.images === group.files"><x-admin-panel::icon name="image" class="size-4" /></span>
                                                    <span x-show="!(group.images > 0 && group.images === group.files)" x-cloak><x-admin-panel::icon name="file" class="size-4" /></span>
                                                </span>
                                                <span class="flex-1 truncate text-xs font-semibold">
                                                    <span x-text="group.label"></span>
                                                    <span class="ltr ms-1 text-[9px] text-ink-3" x-text="group.id"></span>
                                                </span>
                                                <span class="fa-digits shrink-0 text-[10px] text-ink-3 dark:text-night-ink-2">
                                                    <span x-text="Number(group.files).toLocaleString('fa-IR')"></span> فایل
                                                    • <span x-text="faSize(group.size)"></span>
                                                </span>
                                            </label>
                                        </template>
                                        <p x-show="groups.filter(g => g.root === @js($rootDef['id'])).length === 0" class="py-6 text-center text-xs text-ink-3">
                                            این ریشه موجود نیست یا فایلی ندارد.
                                        </p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- فوتر: خلاصه + دکمه ساخت --}}
                    <div class="flex flex-wrap items-center justify-between gap-3 border-t border-line bg-surface-2/60 px-4 py-3.5 dark:border-night-line dark:bg-night-3/60">
                        <div class="text-[11px] leading-5 text-ink-2 dark:text-night-ink-2">
                            <span class="fa-digits">
                                <b x-text="Number(selectedTableCount).toLocaleString('fa-IR')"></b> جدول
                                • <b x-text="Number(selectedGroupCount).toLocaleString('fa-IR')"></b> گروه فایل
                                • <b x-text="Number(selectedFileCount).toLocaleString('fa-IR')"></b> فایل
                                (حدود <b x-text="faSize(selectedSize)"></b>)
                            </span>
                            <span class="block text-ink-3 dark:text-night-ink-2">دیتابیس و فایل‌ها با هم در یک ZIP ذخیره می‌شوند؛ ساخت بکاپ ممکن است چند لحظه طول بکشد.</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <button type="button" x-on:click="open = false" class="ap-btn-ghost">انصراف</button>
                            <button type="button" x-on:click="submit()"
                                    wire:loading.attr="disabled" wire:target="create"
                                    class="ap-btn-primary !py-2.5">
                                <span wire:loading.remove wire:target="create" class="flex items-center gap-2">
                                    <x-admin-panel::icon name="archive" class="size-4" />
                                    ساخت نسخه پشتیبان
                                </span>
                                <span wire:loading wire:target="create" class="flex items-center gap-2">
                                    <svg class="size-4 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                                        <path d="M21 12a9 9 0 1 1-6.219-8.56" stroke-linecap="round" />
                                    </svg>
                                    در حال ساخت…
                                </span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endcan

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
                با حذف فایل پشتیبان دیگر قادر به بازیابی آن نخواهید بود.
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

    {{-- لودینگ سراسری اکشن‌ها --}}
    <div wire:loading wire:target="delete, download, restoreIncoming"
         class="pointer-events-none fixed bottom-6 start-6 z-[65] flex items-center gap-2 rounded-xl border border-line bg-surface px-4 py-2.5 shadow-pop dark:border-night-line dark:bg-night-3">
        <svg class="size-4 animate-spin text-brand-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
            <path d="M21 12a9 9 0 1 1-6.219-8.56" stroke-linecap="round" />
        </svg>
        <span class="text-[13px] font-semibold">در حال انجام…</span>
    </div>
</div>

{{-- کامپوننت Alpine مودال انتخابی — کلاینت‌ساید و بدون رفت‌وبرگشت (درس فاز ۱۷-د) --}}
@once
    @push('scripts')
        <script>
            document.addEventListener('alpine:init', () => {
                Alpine.data('apBackupCreate', (data) => ({
                    open: false,
                    tab: 'tables',
                    tableSearch: '',
                    tables: Array.isArray(data.tables) ? data.tables : [],
                    groups: Array.isArray(data.groups) ? data.groups : [],
                    selected: { tables: {}, groups: {} },

                    init() {
                        // پیش‌فرض: همه‌چیز تیک خورده
                        const tables = {};
                        this.tables.forEach((t) => { tables[t.name] = true; });
                        const groups = {};
                        this.groups.forEach((g) => { groups[g.id] = true; });
                        this.selected = { tables, groups };
                    },

                    get filteredTables() {
                        const q = (this.tableSearch || '').trim().toLowerCase();
                        if (!q) return this.tables;
                        return this.tables.filter((t) => t.name.toLowerCase().includes(q));
                    },

                    get allTablesSelected() {
                        return this.tables.length > 0 && this.tables.every((t) => this.selected.tables[t.name]);
                    },

                    toggleAllTables(checked) {
                        const tables = {};
                        this.tables.forEach((t) => { tables[t.name] = !!checked; });
                        this.selected.tables = tables;
                    },

                    allGroupsSelected(root) {
                        const list = this.groups.filter((g) => g.root === root);
                        return list.length > 0 && list.every((g) => this.selected.groups[g.id]);
                    },

                    toggleAllGroups(root, checked) {
                        const groups = Object.assign({}, this.selected.groups);
                        this.groups.filter((g) => g.root === root).forEach((g) => { groups[g.id] = !!checked; });
                        this.selected.groups = groups;
                    },

                    get selectedTableCount() {
                        return this.tables.filter((t) => this.selected.tables[t.name]).length;
                    },

                    get selectedGroupCount() {
                        return this.groups.filter((g) => this.selected.groups[g.id]).length;
                    },

                    get selectedFileCount() {
                        return this.groups.reduce((sum, g) => sum + (this.selected.groups[g.id] ? g.files : 0), 0);
                    },

                    get selectedSize() {
                        return this.groups.reduce((sum, g) => sum + (this.selected.groups[g.id] ? g.size : 0), 0);
                    },

                    faSize(bytes) {
                        if (bytes >= 1048576) return (bytes / 1048576).toLocaleString('fa-IR', { maximumFractionDigits: 1 }) + ' مگابایت';
                        if (bytes >= 1024) return Math.round(bytes / 1024).toLocaleString('fa-IR') + ' کیلوبایت';
                        return Number(bytes).toLocaleString('fa-IR') + ' بایت';
                    },

                    submit() {
                        if (this.selectedTableCount === 0) {
                            window.ApToast?.error('حداقل یک جدول باید انتخاب شود.');
                            return;
                        }
                        const tables = this.tables.filter((t) => this.selected.tables[t.name]).map((t) => t.name);
                        const groups = this.groups.filter((g) => this.selected.groups[g.id]).map((g) => g.id);
                        this.$wire.create(tables, groups);
                        this.open = false;
                    },
                }));
            });
        </script>
    @endpush
@endonce
