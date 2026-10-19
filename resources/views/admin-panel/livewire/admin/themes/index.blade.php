{{-- لیست قالب‌ها — پورت back/themes/index.blade.php (فاز ۷-ج۴) --}}
<div class="mx-auto max-w-7xl space-y-5">

    {{-- سربرگ --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <span class="flex size-11 items-center justify-center rounded-2xl bg-brand-50 text-brand-600 dark:bg-brand-500/12 dark:text-brand-400">
                <x-admin-panel::icon name="layout-template" class="size-6" />
            </span>
            <div>
                <h1 class="text-lg font-bold">مدیریت قالب‌ها</h1>
                <p class="text-xs text-ink-3 dark:text-night-ink-2">قالب‌های نصب‌شده فروشگاه</p>
            </div>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('np.themes.create') }}" class="ap-btn-outline !py-2">
                <x-admin-panel::icon name="upload" class="size-4" />
                آپلود قالب جدید
            </a>
            <a href="{{ route('np.themes.settings') }}" class="ap-btn-outline !py-2">
                <x-admin-panel::icon name="settings-2" class="size-4" />
                تنظیمات قالب
            </a>
        </div>
    </div>

    @if (count($themes))
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
            @foreach ($themes as $theme)
                @continue(!$theme['config'])
                @php
                    $isActive = ($this->currentTheme['name'] ?? null) === $theme['name'];
                @endphp
                <div class="ap-card overflow-hidden {{ $isActive ? 'ring-2 ring-brand-500' : '' }}">
                    <div class="aspect-video w-full overflow-hidden bg-surface-2 dark:bg-night-3">
                        <img src="{{ asset($theme['config']['asset_path'] . $theme['config']['demo']['image']) }}"
                             alt="{{ $theme['config']['demo']['name'] }}"
                             class="size-full object-cover"
                             onerror="this.style.display='none'">
                    </div>
                    <div class="p-4">
                        <div class="flex items-center justify-between gap-2">
                            <h3 class="text-sm font-bold">{{ $theme['config']['demo']['name'] }}</h3>
                            @if ($isActive)
                                <span class="ap-badge bg-brand-50 text-brand-700 dark:bg-brand-500/12 dark:text-brand-400">
                                    <x-admin-panel::icon name="check" class="size-3" />
                                    قالب فعال
                                </span>
                            @endif
                        </div>
                        <p class="mt-1 text-xs leading-5 text-ink-3 dark:text-night-ink-2">{{ $theme['config']['demo']['description'] }}</p>
                        <p class="ltr mt-1 text-[11px] text-ink-3 dark:text-night-ink-2">{{ $theme['name'] }}</p>

                        @unless ($isActive)
                            <div class="mt-3 flex items-center justify-between gap-2">
                                <button type="button" wire:click="activate({{ \Illuminate\Support\Js::from($theme['name'])->toHtml() }})"
                                        wire:loading.attr="disabled" wire:target="activate"
                                        class="ap-btn !py-1.5 !text-xs">
                                    <x-admin-panel::icon name="check-circle" class="size-3.5" />
                                    تنظیم به عنوان قالب
                                </button>
                                @can('themes.delete')
                                    <button type="button" wire:click="confirmDelete({{ \Illuminate\Support\Js::from($theme['name'])->toHtml() }})"
                                            class="ap-btn-ghost !py-1.5 !text-xs text-red-600 hover:!bg-red-50 dark:hover:!bg-red-500/10">
                                        <x-admin-panel::icon name="trash-2" class="size-3.5" />
                                        حذف
                                    </button>
                                @endcan
                            </div>
                        @endunless
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="ap-card flex flex-col items-center justify-center gap-2 p-10 text-center">
            <span class="flex size-12 items-center justify-center rounded-2xl bg-surface-2 text-ink-3 dark:bg-night-3">
                <x-admin-panel::icon name="layout-template" class="size-6" />
            </span>
            <p class="text-sm font-bold">چیزی برای نمایش وجود ندارد!</p>
            <a href="{{ route('np.themes.create') }}" class="ap-btn mt-2 !py-2">آپلود قالب جدید</a>
        </div>
    @endif

    {{-- مودال تایید حذف قالب (پورت delete-modal قدیمی) --}}
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
                با حذف قالب دیگر قادر به بازیابی آن نخواهید بود.
            </p>
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
    <div wire:loading wire:target="activate, delete"
         class="pointer-events-none fixed bottom-6 start-6 z-[65] flex items-center gap-2 rounded-xl border border-line bg-surface px-4 py-2.5 shadow-pop dark:border-night-line dark:bg-night-3">
        <svg class="size-4 animate-spin text-brand-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
            <path d="M21 12a9 9 0 1 1-6.219-8.56" stroke-linecap="round" />
        </svg>
        <span class="text-[13px] font-semibold">در حال بارگذاری…</span>
    </div>
</div>
