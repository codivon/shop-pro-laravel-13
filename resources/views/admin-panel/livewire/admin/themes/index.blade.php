{{-- لیست قالب‌ها — پورت back/themes/index.blade.php (فاز ۷-ج۴) — بازطراحی مدرن فاز ۹-ه (کامپوننت ThemeIndex دست‌نخورده) --}}
@php
    $errors = $errors ?? (new \Illuminate\Support\ViewErrorBag())->put('default', new \Illuminate\Support\MessageBag());
@endphp
<div class="mx-auto max-w-7xl space-y-5">

    {{-- سربرگ --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <span class="flex size-11 items-center justify-center rounded-2xl bg-linear-to-br from-brand-500 to-brand-700 text-white shadow-md shadow-brand-500/25">
                <x-admin-panel::icon name="layout-template" class="size-6" />
            </span>
            <div>
                <h1 class="text-lg font-bold">مدیریت قالب‌ها</h1>
                <p class="text-xs text-ink-3 dark:text-night-ink-2">قالب‌های نصب‌شده فروشگاه</p>
            </div>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            @can('themes.create')
                <button type="button" wire:click="$toggle('showUpload')" class="ap-btn-outline !py-2">
                    <x-admin-panel::icon name="upload" class="size-4" />
                    آپلود قالب جدید
                </button>
            @endcan
            <a href="{{ route('np.themes.settings') }}" wire:navigate class="ap-btn-outline !py-2">
                <x-admin-panel::icon name="settings-2" class="size-4" />
                تنظیمات قالب
            </a>
        </div>
    </div>

    {{-- کارت آپلود قالب (فاز ۱۹ — ادغام صفحه‌ی «آپلود قالب جدید» در همین صفحه) --}}
    @can('themes.create')
        <div x-data="{ open: @entangle('showUpload') }" x-show="open" x-cloak
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
             class="overflow-hidden rounded-2xl border border-brand-200/70 bg-linear-to-br from-brand-50/80 via-surface to-brand-50/40 shadow-card dark:border-brand-500/20 dark:from-brand-500/5 dark:via-night-2 dark:to-night-2">
            <div class="flex items-center justify-between gap-3 border-b border-brand-200/60 bg-surface/60 px-4 py-3 dark:border-brand-500/15 dark:bg-night-2/60">
                <h2 class="flex items-center gap-2 text-sm font-bold">
                    <span class="flex size-8 items-center justify-center rounded-xl bg-linear-to-br from-brand-500 to-brand-700 text-white shadow-md shadow-brand-500/25">
                        <x-admin-panel::icon name="upload" class="size-4" />
                    </span>
                    نصب قالب جدید
                </h2>
                <button type="button" x-on:click="open = false" class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-surface-3 dark:hover:bg-night-3" aria-label="بستن">
                    <x-admin-panel::icon name="x" class="size-4" />
                </button>
            </div>

            <form wire:submit="store" class="p-4 sm:p-5">
                {{-- ناحیه‌ی drag & drop --}}
                <div x-data="{
                            dragging: false,
                            drop(e) {
                                this.dragging = false;
                                const files = e.dataTransfer?.files;
                                if (! files?.length) { return; }
                                const input = this.$refs.fileInput;
                                input.files = files;
                                input.dispatchEvent(new Event('change'));
                            }
                        }"
                     x-on:dragover.prevent="dragging = true"
                     x-on:dragleave.prevent="if (! $event.relatedTarget || ! $el.contains($event.relatedTarget)) { dragging = false; }"
                     x-on:drop.prevent="drop($event)"
                     x-on:click="$refs.fileInput.click()"
                     role="button" tabindex="0"
                     x-on:keydown.enter.prevent="$refs.fileInput.click()"
                     :aria-label="'انتخاب فایل قالب'"
                     class="relative flex cursor-pointer flex-col items-center justify-center gap-2.5 rounded-2xl border-2 border-dashed p-6 text-center transition-all duration-200 sm:p-8"
                     :class="dragging
                                ? 'border-brand-500 bg-brand-50/70 scale-[1.01] dark:bg-brand-500/10'
                                : 'border-line bg-surface/70 hover:border-brand-500/60 hover:bg-brand-50/40 dark:border-night-line dark:bg-night-3/40 dark:hover:bg-brand-500/5'">

                    <input type="file" accept=".zip" wire:model="file" x-ref="fileInput" class="sr-only" aria-label="فایل zip قالب">

                    <span class="flex size-14 items-center justify-center rounded-2xl bg-linear-to-br from-brand-500 to-brand-700 text-white shadow-lg shadow-brand-500/25 transition-transform duration-200"
                          :class="dragging ? 'scale-110' : ''">
                        <x-admin-panel::icon name="upload" class="size-7" />
                    </span>

                    <p class="text-sm font-bold">
                        فایل قالب را اینجا رها کنید یا <span class="text-brand-600 underline decoration-brand-300 underline-offset-4 dark:text-brand-400">انتخاب کنید</span>
                    </p>
                    <p class="text-[11px] text-ink-3 dark:text-night-ink-2">
                        فقط فایل با فرمت <code class="ltr rounded bg-surface-3 px-1.5 py-0.5 font-bold dark:bg-night-3">.zip</code> — حداقل شامل پوشه‌ی src و config قالب
                    </p>

                    @error('file') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- فایل انتخاب‌شده --}}
                @if ($file)
                    <p class="mt-3 flex items-center gap-2 rounded-xl border border-brand-200 bg-brand-50/70 px-3.5 py-2.5 text-xs font-semibold text-brand-700 dark:border-brand-500/25 dark:bg-brand-500/10 dark:text-brand-400">
                        <x-admin-panel::icon name="file-archive" class="size-4 shrink-0" />
                        فایل انتخاب‌شده:
                        <span class="ltr truncate">{{ $file->getClientOriginalName() }}</span>
                    </p>
                @endif

                <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
                    <p class="flex items-start gap-2 rounded-xl bg-sky-50 p-3 text-[11px] leading-5 text-sky-700 dark:bg-sky-500/10 dark:text-sky-400">
                        <x-admin-panel::icon name="info" class="mt-0.5 size-3.5 shrink-0" />
                        پس از آپلود، قالب استخراج، نصب و فعال می‌شود (dump-autoload، publish و shop:link خودکار اجرا می‌شود).
                    </p>
                    <button type="submit" wire:loading.attr="disabled" wire:target="store" class="ap-btn shrink-0 !py-2.5">
                        <span wire:loading.remove wire:target="store" class="flex items-center gap-2">
                            <x-admin-panel::icon name="plus" class="size-4" />
                            افزودن قالب
                        </span>
                        <span wire:loading wire:target="store" class="flex items-center gap-2">
                            <svg class="size-4 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                                <path d="M21 12a9 9 0 1 1-6.219-8.56" stroke-linecap="round" />
                            </svg>
                            در حال نصب…
                        </span>
                    </button>
                </div>
            </form>
        </div>
    @endcan

    @if (count($themes))
        <div class="grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-3">
            @foreach ($themes as $theme)
                @continue(!$theme['config'])
                @php
                    $isActive = ($this->currentTheme['name'] ?? null) === $theme['name'];
                    $previewImage = ! empty($theme['config']['asset_path']) && ! empty($theme['config']['demo']['image'])
                        ? asset($theme['config']['asset_path'] . $theme['config']['demo']['image'])
                        : null;
                @endphp
                <div wire:key="theme-{{ $theme['name'] }}"
                     class="ap-card group relative overflow-hidden p-0 transition-all duration-300 hover:-translate-y-1.5 hover:shadow-xl hover:shadow-slate-950/10 dark:hover:shadow-black/40 {{ $isActive ? 'ring-2 ring-brand-500 ring-offset-2 ring-offset-surface dark:ring-offset-night-1' : '' }}">
                    {{-- پیش‌نمایش قالب --}}
                    <div class="relative aspect-video w-full overflow-hidden bg-surface-2 dark:bg-night-3">
                        @if ($previewImage)
                            <img src="{{ $previewImage }}" alt="{{ $theme['config']['demo']['name'] ?? $theme['name'] }}"
                                 class="size-full object-cover transition-transform duration-500 group-hover:scale-105"
                                 onerror="this.classList.add('hidden'); this.nextElementSibling?.classList.remove('hidden');">
                            <span class="absolute inset-0 hidden place-items-center text-ink-3 dark:text-night-ink-2">
                                <x-admin-panel::icon name="image" class="size-12" />
                            </span>
                        @else
                            {{-- placeholder گرادیانی با آیکون palette --}}
                            <span class="absolute inset-0 grid place-items-center bg-linear-to-br from-brand-50 via-surface-2 to-brand-100 text-brand-500/70 dark:from-night-3 dark:via-night-2 dark:to-night-3 dark:text-brand-400/50">
                                <x-admin-panel::icon name="layers" class="size-14" />
                            </span>
                        @endif

                        {{-- بج فعال با pulse --}}
                        @if ($isActive)
                            <span class="absolute top-3 start-3 inline-flex items-center gap-1.5 rounded-full bg-brand-500 px-2.5 py-1 text-[11px] font-bold text-white shadow-lg shadow-brand-500/40">
                                <span class="relative flex size-2">
                                    <span class="absolute inline-flex size-full animate-ping rounded-full bg-white opacity-75"></span>
                                    <span class="relative inline-flex size-2 rounded-full bg-white"></span>
                                </span>
                                قالب فعال
                            </span>
                        @endif
                    </div>

                    {{-- بدنه --}}
                    <div class="p-4">
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0">
                                <h3 class="truncate text-sm font-bold">{{ $theme['config']['demo']['name'] ?? $theme['name'] }}</h3>
                                <p class="ltr mt-0.5 text-[11px] text-ink-3 dark:text-night-ink-2">{{ $theme['name'] }}</p>
                            </div>
                            <span class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-surface-2 text-ink-3 transition-colors group-hover:bg-brand-50 group-hover:text-brand-600 dark:bg-night-3 dark:text-night-ink-2 dark:group-hover:bg-brand-500/10 dark:group-hover:text-brand-400">
                                <x-admin-panel::icon name="package" class="size-4.5" />
                            </span>
                        </div>
                        <p class="mt-2 line-clamp-2 min-h-10 text-xs leading-5 text-ink-3 dark:text-night-ink-2">{{ $theme['config']['demo']['description'] ?? '' }}</p>

                        @unless ($isActive)
                            <div class="mt-3.5 flex items-center gap-2 border-t border-line pt-3.5 dark:border-night-line">
                                <button type="button" wire:click="activate({{ \Illuminate\Support\Js::from($theme['name'])->toHtml() }})"
                                        wire:loading.attr="disabled" wire:target="activate"
                                        class="ap-btn !py-2 !text-xs">
                                    <x-admin-panel::icon name="check-circle" class="size-3.5" />
                                    تنظیم به عنوان قالب
                                </button>
                                @can('themes.delete')
                                    <button type="button" wire:click="confirmDelete({{ \Illuminate\Support\Js::from($theme['name'])->toHtml() }})"
                                            class="ap-btn-ghost !py-2 !text-xs text-red-600 hover:!bg-red-50 dark:hover:!bg-red-500/10"
                                            title="حذف {{ $theme['name'] }}">
                                        <x-admin-panel::icon name="trash-2" class="size-3.5" />
                                        حذف
                                    </button>
                                @endcan
                            </div>
                        @else
                            <div class="mt-3.5 flex items-center gap-2 rounded-xl bg-brand-50/70 px-3 py-2 text-[11px] font-semibold text-brand-700 dark:bg-brand-500/10 dark:text-brand-400">
                                <x-admin-panel::icon name="check-circle" class="size-3.5 shrink-0" />
                                این قالب در حال حاضر روی فروشگاه فعال است.
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
            @can('themes.create')
                <button type="button" wire:click="$toggle('showUpload')" class="ap-btn mt-2 !py-2">آپلود قالب جدید</button>
            @endcan
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
