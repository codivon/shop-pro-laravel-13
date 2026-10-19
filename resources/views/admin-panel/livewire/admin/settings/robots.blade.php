{{-- مدیریت robots.txt — پورت back/settings/robots (فاز ۷-ب۲) — فقط سازنده --}}
<div class="mx-auto max-w-6xl space-y-5">

    {{-- سربرگ --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-2.5">
            <span class="flex size-10 items-center justify-center rounded-xl bg-brand-50 text-brand-600 dark:bg-brand-500/12 dark:text-brand-400">
                <x-admin-panel::icon name="bot" class="size-5" />
            </span>
            <div>
                <h2 class="text-base font-bold">مدیریت robots.txt</h2>
                <p class="text-xs text-ink-3 dark:text-night-ink-2">کنترل دسترسی موتورهای جستجو به بخش‌های مختلف سایت</p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <span class="ap-badge bg-surface-3 text-ink-2 dark:bg-night-3 dark:text-night-ink-2">
                @if ($mode === 'production')
                    حالت تولید
                @elseif ($mode === 'development')
                    حالت توسعه
                @else
                    غیرفعال
                @endif
            </span>
            <a href="{{ config('app.url') }}/robots.txt" target="_blank" class="ap-btn-outline !py-2 text-xs" rel="noopener">
                <x-admin-panel::icon name="external-link" class="size-4" />
                مشاهده فایل زنده
            </a>
        </div>
    </div>

    {{-- هشدار (پورت rb-alert قدیمی) --}}
    <div class="rounded-2xl border border-red-200 bg-red-50 p-4 dark:border-red-500/20 dark:bg-red-500/10">
        <div class="flex items-start gap-3">
            <span class="mt-0.5 flex size-9 shrink-0 items-center justify-center rounded-xl bg-red-100 text-red-600 dark:bg-red-500/15 dark:text-red-400">
                <x-admin-panel::icon name="shield" class="size-5" />
            </span>
            <div class="text-[13px] leading-6 text-red-700 dark:text-red-300">
                <p class="font-bold">هشدار مهم!</p>
                <p>تنظیمات <strong>robots.txt</strong> مستقیماً روی ایندکس شدن سایت توسط گوگل تأثیر می‌گذارد.</p>
                <ul class="mt-1 list-inside list-disc space-y-0.5 text-xs">
                    <li>❌ <strong>Disallow: /</strong> یعنی کل سایت از ایندکس خارج می‌شود</li>
                    <li>✅ مسیرهای پیش‌فرض برای سایت‌های فروشگاهی بهینه هستند</li>
                    <li>📋 قبل از ذخیره، حتماً <strong>پیش‌نمایش</strong> را بررسی کنید</li>
                </ul>
            </div>
        </div>
    </div>

    {{-- حالت عملکرد (پورت rb-mode-grid قدیمی) --}}
    <div class="ap-card p-4 sm:p-5">
        <h3 class="mb-3 flex items-center gap-2 text-sm font-bold">
            <x-admin-panel::icon name="settings" class="size-4 text-brand-600 dark:text-brand-400" />
            حالت عملکرد
        </h3>
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
            @foreach ([
                'production' => ['label' => 'تولید (Production)', 'icon' => 'circle-check', 'badge' => 'پیش‌فرض', 'desc' => 'موتورهای جستجو اجازه کراول دارند. مسیرهای ممنوع احترام گذاشته می‌شوند.'],
                'development' => ['label' => 'توسعه (Development)', 'icon' => 'code-2', 'badge' => 'توسعه', 'desc' => 'همه بات‌ها مسدود می‌شوند. برای محیط‌های staging و dev مناسب است.'],
                'disabled' => ['label' => 'غیرفعال (Disabled)', 'icon' => 'shield-off', 'badge' => 'هشدار', 'desc' => 'تمام سایت از ایندکس خارج می‌شود. فقط در موارد ضروری استفاده کنید.'],
            ] as $value => $info)
                <label class="relative cursor-pointer rounded-2xl border p-4 transition-colors"
                       :class="$wire.mode === '{{ $value }}' ? 'border-brand-500 bg-brand-50/60 dark:bg-brand-500/10' : 'border-line hover:bg-surface-2 dark:border-night-line dark:hover:bg-night-3'">
                    <input type="radio" value="{{ $value }}" wire:model.live="mode" class="absolute start-3 top-3 size-3.5 accent-brand-600" aria-label="{{ $info['label'] }}">
                    <div class="flex items-center gap-2 ps-5">
                        <x-admin-panel::icon name="{{ $info['icon'] }}" class="size-4 text-brand-600 dark:text-brand-400" />
                        <span class="text-[13px] font-bold">{{ $info['label'] }}</span>
                    </div>
                    <span class="fa-digits mt-2 inline-block rounded-full bg-surface-3 px-2 py-0.5 text-[10px] font-bold text-ink-2 dark:bg-night-3 dark:text-night-ink-2">{{ $info['badge'] }}</span>
                    <p class="mt-1.5 text-[11px] leading-5 text-ink-3 dark:text-night-ink-2">{{ $info['desc'] }}</p>
                </label>
            @endforeach
        </div>
        @error('mode') <p class="mt-2 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
    </div>

    {{-- مسیرهای ممنوع / استثنا (پورت rb-two-col قدیمی) --}}
    <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
        <div class="ap-card p-4 sm:p-5">
            <h3 class="mb-3 flex items-center justify-between text-sm font-bold">
                <span class="flex items-center gap-2">
                    <x-admin-panel::icon name="shield-off" class="size-4 text-red-500" />
                    مسیرهای ممنوع
                </span>
                <span class="text-[10px] font-semibold text-ink-3">Disallow</span>
            </h3>
            <div class="space-y-2">
                @foreach ($disallow as $i => $path)
                    <div class="flex items-center gap-2">
                        <span class="shrink-0 rounded-lg bg-surface-3 px-2 py-1.5 font-mono text-[10px] font-bold text-ink-3 dark:bg-night-3">Disallow:</span>
                        <input type="text" wire:model.live.debounce.500ms="disallow.{{ $i }}" dir="ltr"
                               class="ap-input !py-1.5 font-mono text-xs" placeholder="/admin/">
                        <button type="button" wire:click="removeDisallow({{ $i }})" wire:key="disallow-{{ $i }}"
                                class="shrink-0 rounded-lg p-2 text-ink-3 transition-colors hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-500/10"
                                title="حذف" aria-label="حذف مسیر">
                            <x-admin-panel::icon name="x" class="size-3.5" />
                        </button>
                    </div>
                @endforeach
            </div>
            <button type="button" wire:click="addDisallow" class="mt-3 ap-btn-outline !py-1.5 text-xs">
                <x-admin-panel::icon name="plus" class="size-3.5" />
                افزودن مسیر ممنوع
            </button>
            <div class="mt-3 flex flex-wrap gap-1.5">
                @foreach (['/admin/', '/panel/', '/login', '/register', '/cart', '/checkout', '/api/', '/storage/'] as $s)
                    <button type="button" wire:click="addDisallow('{{ $s }}')" class="font-mono rounded-full bg-surface-2 px-2.5 py-1 text-[10px] font-bold text-ink-2 transition-colors hover:bg-brand-50 hover:text-brand-600 dark:bg-night-3 dark:text-night-ink-2 dark:hover:bg-brand-500/10">
                        {{ $s }}
                    </button>
                @endforeach
            </div>
            @error('disallow') <p class="mt-2 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="ap-card p-4 sm:p-5">
            <h3 class="mb-3 flex items-center justify-between text-sm font-bold">
                <span class="flex items-center gap-2">
                    <x-admin-panel::icon name="circle-check" class="size-4 text-emerald-500" />
                    مسیرهای استثنا (اختیاری)
                </span>
                <span class="text-[10px] font-semibold text-ink-3">Allow</span>
            </h3>
            @if (count($allow))
                <div class="space-y-2">
                    @foreach ($allow as $i => $path)
                        <div class="flex items-center gap-2">
                            <span class="shrink-0 rounded-lg bg-surface-3 px-2 py-1.5 font-mono text-[10px] font-bold text-ink-3 dark:bg-night-3">Allow:</span>
                            <input type="text" wire:model.live.debounce.500ms="allow.{{ $i }}" dir="ltr"
                                   class="ap-input !py-1.5 font-mono text-xs" placeholder="/admin/images/">
                            <button type="button" wire:click="removeAllow({{ $i }})" wire:key="allow-{{ $i }}"
                                    class="shrink-0 rounded-lg p-2 text-ink-3 transition-colors hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-500/10"
                                    title="حذف" aria-label="حذف استثنا">
                                <x-admin-panel::icon name="x" class="size-3.5" />
                            </button>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="mb-3 rounded-xl border border-line bg-surface-2 p-3 text-[11px] leading-5 text-ink-3 dark:border-night-line dark:bg-night-3">
                    نیازی به تنظیم Allow نیست. فقط برای استثناهای Disallow استفاده کنید.
                </p>
            @endif
            <button type="button" wire:click="addAllow" class="mt-1 ap-btn-outline !py-1.5 text-xs">
                <x-admin-panel::icon name="plus" class="size-3.5" />
                افزودن استثنا
            </button>
            <div class="mt-3 flex flex-wrap gap-1.5">
                @foreach (['/admin/images/', '/admin/css/', '/admin/js/'] as $s)
                    <button type="button" wire:click="addAllow('{{ $s }}')" class="font-mono rounded-full bg-surface-2 px-2.5 py-1 text-[10px] font-bold text-ink-2 transition-colors hover:bg-brand-50 hover:text-brand-600 dark:bg-night-3 dark:text-night-ink-2 dark:hover:bg-brand-500/10">
                        {{ $s }}
                    </button>
                @endforeach
            </div>
            @error('allow') <p class="mt-2 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
        </div>
    </div>

    {{-- Crawl Delay + Sitemap (پورت rb-two-col دوم) --}}
    <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
        <div class="ap-card p-4 sm:p-5">
            <h3 class="mb-3 flex items-center gap-2 text-sm font-bold">
                <x-admin-panel::icon name="hourglass" class="size-4 text-brand-600 dark:text-brand-400" />
                Crawl Delay
            </h3>
            <label class="mb-1.5 block text-xs font-semibold">تأخیر کراول برای همه ربات‌ها (ثانیه)</label>
            <div class="flex items-center gap-3">
                <input type="range" min="0" max="60" step="1" wire:model.live="crawlDelay"
                       class="h-2 w-full cursor-pointer appearance-none rounded-full bg-surface-3 accent-brand-600 dark:bg-night-3"
                       aria-label="تأخیر کراول">
                <span class="fa-digits w-12 shrink-0 rounded-lg bg-surface-2 py-1.5 text-center text-xs font-bold dark:bg-night-3">{{ $crawlDelay }}s</span>
            </div>
            <p class="mt-2 text-[11px] text-ink-3 dark:text-night-ink-2">
                ۰ = بدون محدودیت · ۱–۵ = توصیه‌شده · بیش از ۱۰ = کند شدن کراول
            </p>
            @error('crawlDelay') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror

            <table class="mt-4 w-full text-start text-[11px]">
                <thead>
                    <tr class="border-b border-line text-ink-2 dark:border-night-line">
                        <th class="py-2 text-start font-bold">ربات</th>
                        <th class="py-2 text-start font-bold">تأخیر پیش‌فرض</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ([['Googlebot', '1s'], ['Bingbot', '2s']] as $bot)
                        <tr class="border-b border-line/60 last:border-0 dark:border-night-line/60">
                            <td class="py-2 font-bold">{{ $bot[0] }}</td>
                            <td class="py-2 fa-digits">{{ $bot[1] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="ap-card p-4 sm:p-5">
            <h3 class="mb-3 flex items-center gap-2 text-sm font-bold">
                <x-admin-panel::icon name="map" class="size-4 text-brand-600 dark:text-brand-400" />
                Sitemap
            </h3>
            <label class="mb-1.5 block text-xs font-semibold">آدرس فایل Sitemap</label>
            <input type="url" wire:model.live.debounce.500ms="sitemap" dir="ltr"
                   class="ap-input !py-2 font-mono text-xs" placeholder="https://example.com/sitemap.xml">
            <p class="mt-1.5 text-[11px] text-ink-3 dark:text-night-ink-2">این آدرس در robots.txt معرفی می‌شود تا گوگل آن را کشف کند.</p>
            @error('sitemap') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
            <div class="mt-3">
                <p class="mb-1 text-[10px] font-bold text-ink-3">آدرس‌های رایج</p>
                <div class="space-y-1">
                    @foreach ([config('app.url') . '/sitemap.xml', config('app.url') . '/sitemap_index.xml'] as $sm)
                        <button type="button" wire:click="$set('sitemap', '{{ $sm }}')" class="block w-full truncate rounded-lg bg-surface-2 px-2.5 py-1.5 text-start font-mono text-[10px] text-brand-600 transition-colors hover:bg-brand-50 dark:bg-night-3 dark:text-brand-400 dark:hover:bg-brand-500/10" dir="ltr">
                            {{ $sm }}
                        </button>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- پیش‌نمایش زنده (پورت rb-preview قدیمی) --}}
    <div class="ap-card overflow-hidden">
        <div class="flex items-center justify-between border-b border-line px-4 py-3 dark:border-night-line">
            <h3 class="flex items-center gap-2 text-sm font-bold">
                <x-admin-panel::icon name="eye" class="size-4 text-brand-600 dark:text-brand-400" />
                پیش‌نمایش زنده robots.txt
            </h3>
            <button type="button" wire:click="$refresh" class="ap-btn-ghost !py-1.5 text-xs">
                <x-admin-panel::icon name="refresh-cw" class="size-3.5" />
                بروزرسانی پیش‌نمایش
            </button>
        </div>
        <pre class="max-h-96 overflow-auto bg-slate-950 p-4 font-mono text-xs leading-6 text-emerald-300" dir="ltr">{{ $this->preview }}</pre>
    </div>

    {{-- نوار اکشن --}}
    <div class="ap-card flex flex-wrap items-center justify-between gap-3 p-4">
        <p class="text-[11px] text-ink-3 dark:text-night-ink-2">
            پس از ذخیره، فایل <code class="font-mono">public/robots.txt</code> بازتولید می‌شود.
        </p>
        <button type="button" wire:click="save" wire:loading.attr="disabled" wire:target="save"
                class="ap-btn">
            <x-admin-panel::icon name="check-circle" class="size-4" />
            ذخیره و اعمال
        </button>
    </div>

    {{-- لودینگ --}}
    <div wire:loading wire:target="save"
         class="pointer-events-none fixed bottom-6 start-6 z-[65] flex items-center gap-2 rounded-xl border border-line bg-surface px-4 py-2.5 shadow-pop dark:border-night-line dark:bg-night-3">
        <svg class="size-4 animate-spin text-brand-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
            <path d="M21 12a9 9 0 1 1-6.219-8.56" stroke-linecap="round" />
        </svg>
        <span class="text-[13px] font-semibold">در حال بارگذاری…</span>
    </div>
</div>
