{{-- ماژول‌های نصب‌شده — پورت back/packages/installed.blade.php (فاز ۷-ج۴) --}}
<div class="mx-auto max-w-7xl space-y-5">

    {{-- سربرگ --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <span class="flex size-11 items-center justify-center rounded-2xl bg-brand-50 text-brand-600 dark:bg-brand-500/12 dark:text-brand-400">
                <x-admin-panel::icon name="package-check" class="size-6" />
            </span>
            <div>
                <h1 class="text-lg font-bold">ماژول‌های نصب‌شده</h1>
                <p class="text-xs text-ink-3 dark:text-night-ink-2">مدیریت، آپدیت و حذف ماژول‌های نصب‌شده</p>
            </div>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            {{-- فاز ۱۲۲ — بازیابی رکوردهای گم‌شدهٔ installed_modules از پوشهٔ Modules/ (بعد از ریست دیتابیس هاست) --}}
            <button type="button" wire:click="recoverFromDisk"
                    wire:loading.attr="disabled" wire:target="recoverFromDisk"
                    class="ap-btn-outline !py-2"
                    title="اسکن پوشهٔ ماژول‌ها و بازسازی رکوردهای گم‌شدهٔ دیتابیس (برای بعد از ریست/بازیابی دیتابیس هاست)">
                <x-admin-panel::icon name="history" class="size-4" />
                بازیابی از پوشهٔ ماژول‌ها
            </button>
            <a href="{{ route('np.packages.index') }}" class="ap-btn-outline !py-2">
                <x-admin-panel::icon name="package" class="size-4" />
                بازار
            </a>
        </div>
    </div>

    {{-- جدول --}}
    <div class="ap-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[720px] text-start text-sm">
                <thead>
                    <tr class="border-b border-line bg-surface-2 text-xs text-ink-3 dark:border-night-line dark:bg-night-3 dark:text-night-ink-2">
                        <th class="px-4 py-3 text-start font-semibold">ماژول</th>
                        <th class="px-4 py-3 text-start font-semibold">نسخه نصب‌شده</th>
                        <th class="px-4 py-3 text-start font-semibold">آخرین نسخه</th>
                        <th class="px-4 py-3 text-start font-semibold">وضعیت</th>
                        <th class="px-4 py-3 text-start font-semibold">لایسنس</th>
                        <th class="px-4 py-3 text-start font-semibold">تاریخ نصب</th>
                        <th class="px-4 py-3 text-start font-semibold">عملیات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line dark:divide-night-line">
                    @forelse ($this->modules as $module)
                        <tr class="transition-colors hover:bg-surface-2 dark:hover:bg-night-3">
                            <td class="px-4 py-3">
                                {{-- فاز ۸۶ — نمایش «عنوان» ماژول (module.json پکیج → کش بازار → نام فنی) --}}
                                <p class="font-bold">{{ $module->displayName() }}</p>
                                <p class="ltr text-xs text-ink-3 dark:text-night-ink-2">{{ $module->slug }}</p>
                            </td>
                            <td class="fa-digits px-4 py-3">{{ $module->version }}</td>
                            <td class="fa-digits px-4 py-3">
                                @if ($module->cache)
                                    {{ $module->cache->latest_version }}
                                    @if ($module->isUpdatable())
                                        <span class="ap-badge bg-amber-50 text-amber-700 dark:bg-amber-500/12 dark:text-amber-400">آپدیت موجود</span>
                                    @endif
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @php
                                    $statusMeta = [
                                        'installed' => ['نصب‌شده', 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/12 dark:text-emerald-400'],
                                        'updating'  => ['در حال بروزرسانی', 'bg-amber-50 text-amber-700 dark:bg-amber-500/12 dark:text-amber-400'],
                                        'failed'    => ['ناموفق', 'bg-red-50 text-red-700 dark:bg-red-500/12 dark:text-red-400'],
                                    ][$module->status] ?? [$module->status, 'bg-surface-2 text-ink-2 dark:bg-night-3 dark:text-night-ink-2'];
                                @endphp
                                <span class="ap-badge {{ $statusMeta[1] }}">{{ $statusMeta[0] }}</span>
                                @if ($module->last_error)
                                    <p class="mt-1 max-w-[220px] truncate text-[11px] text-red-500" title="{{ $module->last_error }}">{{ $module->last_error }}</p>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-xs">
                                @if ($module->isExpired())
                                    <span class="ap-badge bg-red-50 text-red-700 dark:bg-red-500/12 dark:text-red-400">منقضی شده</span>
                                @elseif ($module->license_expires_at)
                                    {{ \App\Livewire\Admin\Packages\PackageInstalled::jdate($module->license_expires_at, 'Y/m/d') }}
                                @else
                                    نامحدود
                                @endif
                            </td>
                            <td class="px-4 py-3 text-xs text-ink-2 dark:text-night-ink-2">
                                {{ \App\Livewire\Admin\Packages\PackageInstalled::jdate($module->installed_at, 'Y/m/d') }}
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap items-center gap-1.5">
                                    <button type="button" wire:click="toggleActivation({{ \Illuminate\Support\Js::from($module->slug)->toHtml() }})"
                                            wire:loading.attr="disabled" wire:target="toggleActivation"
                                            class="rounded-lg p-1.5 {{ $module->is_active ? 'text-emerald-600 hover:bg-emerald-50 dark:hover:bg-emerald-500/10' : 'text-ink-3 hover:bg-surface-3 dark:hover:bg-night-3' }}"
                                            title="{{ $module->is_active ? 'غیرفعال‌سازی' : 'فعال‌سازی' }}">
                                        <x-admin-panel::icon name="{{ $module->is_active ? 'circle-check' : 'circle-x' }}" class="size-4.5" />
                                    </button>
                                    {{-- فاز ۹۹ — گیت آپدیت: با خاموشی کلید اصلی/allow_install دکمهٔ آپدیت مخفی --}}
                                    @if (\App\Support\MarketplaceSettings::allowInstall())
                                        <button type="button" wire:click="updateModule({{ \Illuminate\Support\Js::from($module->slug)->toHtml() }})"
                                                wire:loading.attr="disabled" wire:target="updateModule"
                                                class="rounded-lg p-1.5 text-brand-600 hover:bg-brand-50 dark:hover:bg-brand-500/10"
                                                title="آپدیت ماژول">
                                            <x-admin-panel::icon name="refresh-cw" class="size-4.5" />
                                        </button>
                                    @endif
                                    @can('packages.installed')
                                        {{-- فاز ۹۹ — گیت حذف: با خاموشی allow_uninstall دکمهٔ حذف مخفی --}}
                                        @if (\App\Support\MarketplaceSettings::allowUninstall())
                                            <button type="button" wire:click="confirmUninstall({{ \Illuminate\Support\Js::from($module->slug)->toHtml() }})"
                                                    class="rounded-lg p-1.5 text-red-500 hover:bg-red-50 dark:hover:bg-red-500/10"
                                                    title="حذف ماژول">
                                                <x-admin-panel::icon name="trash-2" class="size-4.5" />
                                            </button>
                                        @endif
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-12 text-center">
                                <span class="mx-auto mb-2 flex size-12 items-center justify-center rounded-2xl bg-surface-2 text-ink-3 dark:bg-night-3">
                                    <x-admin-panel::icon name="package" class="size-6" />
                                </span>
                                <p class="text-sm font-bold">ماژولی نصب نشده است</p>
                                <p class="mt-1 text-xs text-ink-3 dark:text-night-ink-2">پکیج‌های موردنظر را از بازار نصب کنید.</p>
                                {{-- فاز ۱۲۲ — اگر پوشهٔ ماژول‌ها پر است ولی جدول خالی (ریست دیتابیس هاست)، یک‌کلیک بازیابی --}}
                                <button type="button" wire:click="recoverFromDisk"
                                        wire:loading.attr="disabled" wire:target="recoverFromDisk"
                                        class="ap-btn mx-auto mt-4 !py-2">
                                    <x-admin-panel::icon name="history" class="size-4" />
                                    بازیابی از پوشهٔ ماژول‌ها
                                </button>
                                <p class="mt-2 text-[11px] text-ink-3 dark:text-night-ink-2">اگر بعد از ریست/تمدید دیتابیس، پوشهٔ ماژول‌ها سر جای خودش است، با یک کلیک رکوردها و منوها بازمی‌گردند.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- صفحه‌بندی --}}
        @if ($this->modules->hasPages())
            <div class="border-t border-line px-4 py-3 dark:border-night-line">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <p class="text-xs text-ink-3 dark:text-night-ink-2">
                        نمایش <span class="fa-digits">{{ number_format($this->modules->firstItem() ?? 0) }}</span> تا
                        <span class="fa-digits">{{ number_format($this->modules->lastItem() ?? 0) }}</span> از
                        <span class="fa-digits">{{ number_format($this->modules->total()) }}</span> ماژول
                    </p>
                    <nav class="flex items-center gap-1" aria-label="صفحه‌بندی">
                        @if ($this->modules->onFirstPage())
                            <span class="rounded-lg p-2 text-ink-3 opacity-40"><x-admin-panel::icon name="chevron-right" class="size-4" /></span>
                        @else
                            <button wire:click="previousPage" class="rounded-lg p-2 text-ink-2 transition-colors hover:bg-surface-3 dark:hover:bg-night-3" aria-label="صفحه قبل">
                                <x-admin-panel::icon name="chevron-right" class="size-4" />
                            </button>
                        @endif

                        @foreach ($this->modules->onEachSide(1)->toArray()['links'] as $link)
                            @if ($link['label'] === '...')
                                <span class="px-2 text-xs text-ink-3">…</span>
                            @elseif (! is_numeric(trim($link['label'])))
                            @elseif ($link['active'])
                                <span class="fa-digits flex size-8 items-center justify-center rounded-lg bg-brand-600 text-[13px] font-bold text-white">{{ $link['label'] }}</span>
                            @else
                                <button wire:click="gotoPage({{ trim($link['label']) }})"
                                        class="fa-digits flex size-8 items-center justify-center rounded-lg text-[13px] font-semibold text-ink-2 transition-colors hover:bg-surface-3 dark:text-night-ink-2 dark:hover:bg-night-3">
                                    {{ $link['label'] }}
                                </button>
                            @endif
                        @endforeach

                        @if ($this->modules->hasMorePages())
                            <button wire:click="nextPage" class="rounded-lg p-2 text-ink-2 transition-colors hover:bg-surface-3 dark:hover:bg-night-3" aria-label="صفحه بعد">
                                <x-admin-panel::icon name="chevron-left" class="size-4" />
                            </button>
                        @else
                            <span class="rounded-lg p-2 text-ink-3 opacity-40"><x-admin-panel::icon name="chevron-left" class="size-4" /></span>
                        @endif
                    </nav>
                </div>
            </div>
        @endif
    </div>

    {{-- مودال تایید حذف ماژول --}}
    <div x-data="{ open: @entangle('confirmingUninstall').live }"
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
            <h3 class="text-base font-bold">حذف ماژول</h3>
            <p class="mt-1.5 text-[13px] leading-6 text-ink-2 dark:text-night-ink-2">
                با حذف ماژول <b class="ltr">{{ \App\Models\InstalledModule::where('slug', $confirmingUninstall)->first()?->displayName() ?? $confirmingUninstall }}</b> فایل‌ها و تنظیمات آن از پروژه پاک می‌شود و قابل بازگشت نیست.
            </p>
            <div class="mt-5 flex gap-2.5">
                <button type="button" wire:click="uninstall" wire:loading.attr="disabled"
                        class="ap-btn flex-1 bg-red-600 text-white hover:bg-red-700">
                    بله حذف شود
                </button>
                <button type="button" x-on:click="open=false" class="ap-btn-ghost flex-1">انصراف</button>
            </div>
        </div>
    </div>

    {{-- لودینگ --}}
    <div wire:loading wire:target="updateModule, uninstall, toggleActivation, recoverFromDisk, previousPage, nextPage, gotoPage"
         class="pointer-events-none fixed bottom-6 start-6 z-[65] flex items-center gap-2 rounded-xl border border-line bg-surface px-4 py-2.5 shadow-pop dark:border-night-line dark:bg-night-3">
        <svg class="size-4 animate-spin text-brand-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
            <path d="M21 12a9 9 0 1 1-6.219-8.56" stroke-linecap="round" />
        </svg>
        <span class="text-[13px] font-semibold">در حال بارگذاری…</span>
    </div>
</div>
