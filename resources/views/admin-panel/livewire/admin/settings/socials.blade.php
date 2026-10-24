{{-- شبکه‌های اجتماعی — فاز ۱۸: بازطراحی کامل + همه شبکه‌ها (کاتالوگ ۱۴گانه + شبکه‌های تم)
     پورت ۱:۱ منطق ذخیره (Socials::save) — کلیدهای option همان social_* قدیمی --}}
<div class="mx-auto max-w-6xl space-y-5"
     x-data="{
        q: '',
        filter: 'all',
        total: @js(count($this->socialsList())),
        vals: @js($this->values),
        get filledCount() { return Object.values(this.vals).filter(v => String(v || '').trim() !== '').length; },
        isFilled(k) { return String(this.vals[k] || '').trim() !== ''; },
     }">

    {{-- سربرگ --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-2.5">
            <span class="flex size-10 items-center justify-center rounded-xl bg-brand-50 text-brand-600 dark:bg-brand-500/12 dark:text-brand-400">
                <x-admin-panel::icon name="share-2" class="size-5" />
            </span>
            <div>
                <h2 class="text-base font-bold">شبکه‌های اجتماعی</h2>
                <p class="text-xs text-ink-3 dark:text-night-ink-2">لینک شبکه‌های اجتماعی سایت — نمایش در فوتر، تماس با ما و صفحات تم</p>
            </div>
        </div>
        <span class="ap-badge bg-emerald-50 text-emerald-700 dark:bg-emerald-500/12 dark:text-emerald-400">
            <x-admin-panel::icon name="check-circle" class="size-3.5" />
            <span class="fa-digits"><span x-text="filledCount"></span> از <span x-text="total"></span> تکمیل شده</span>
        </span>
    </div>

    <form wire:submit="save" class="space-y-5"
          x-on:keydown.enter="if ($event.target.tagName !== 'TEXTAREA') { $event.preventDefault(); }">

        {{-- نوار ابزار: جستجو + فیلتر --}}
        <div class="ap-card flex flex-wrap items-center gap-2.5 p-3">
            <div class="relative min-w-52 flex-1">
                <x-admin-panel::icon name="search" class="absolute start-3 top-1/2 size-4 -translate-y-1/2 text-ink-3" />
                <input type="text" x-model="q" placeholder="جستجوی شبکه…" aria-label="جستجوی شبکه"
                       class="ap-input ps-9">
            </div>
            <div class="flex items-center gap-1.5">
                @foreach (['all' => 'همه', 'filled' => 'پرشده', 'empty' => 'خالی'] as $f => $label)
                    <button type="button" class="ap-btn !py-1.5 !px-3 text-xs"
                            x-on:click="filter = '{{ $f }}'"
                            x-bind:class="filter === '{{ $f }}' ? '!bg-brand-600 !text-white !border-brand-600' : ''">
                        {{ $label }}
                        <span class="fa-digits opacity-70"
                              x-text="'{{ $f }}' === 'all' ? total : ('{{ $f }}' === 'filled' ? filledCount : total - filledCount)"></span>
                    </button>
                @endforeach
            </div>
        </div>

        {{-- کارت شبکه‌ها --}}
        <div class="grid grid-cols-1 gap-3.5 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($this->socialsList() as $social)
                <div class="ap-card group p-4 transition-shadow hover:shadow-card"
                     wire:key="social-{{ $social['key'] }}"
                     x-data="{ clear() { const i = document.getElementById(@js('social-' . $social['key'])); if (i) { i.value = ''; i.dispatchEvent(new Event('input', { bubbles: true })); } $wire.set('values.{{ $social['key'] }}', '', false); } }"
                     x-show="(filter === 'all' || (filter === 'filled' ? isFilled(@js($social['key'])) : ! isFilled(@js($social['key']))))
                             && (q.trim() === '' || @js($social['name']).includes(q.trim()) || @js($social['key']).includes(q.trim()))">

                    <div class="mb-3 flex items-center gap-2.5">
                        <span class="flex size-10 shrink-0 items-center justify-center rounded-xl text-white shadow-sm transition-transform group-hover:scale-105"
                              style="background-color: {{ $social['color'] }}">
                            <x-admin-panel::icon name="{{ $social['icon'] }}" class="size-5" />
                        </span>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-1.5">
                                <p class="truncate text-[13px] font-bold">{{ $social['name'] }}</p>
                                @if (! empty($social['from_theme']))
                                    <span class="ap-badge !px-1.5 !py-0 bg-violet-50 text-[9px] text-violet-600 dark:bg-violet-500/12 dark:text-violet-400">تم</span>
                                @endif
                            </div>
                            <p class="ltr truncate text-[10px] text-ink-3 dark:text-night-ink-2">{{ $social['key'] }}</p>
                        </div>
                        <span class="flex size-5 shrink-0 items-center justify-center rounded-full transition-all"
                              x-bind:class="isFilled(@js($social['key'])) ? 'bg-emerald-500' : 'bg-surface-3 dark:bg-night-3'">
                            <x-admin-panel::icon name="check" class="size-3 text-white" x-show="isFilled('{{ $social['key'] }}')" x-cloak />
                        </span>
                    </div>

                    <div class="relative">
                        <input id="social-{{ $social['key'] }}" type="text" dir="ltr"
                               wire:model="values.{{ $social['key'] }}"
                               x-on:input="vals['{{ $social['key'] }}'] = $event.target.value"
                               class="ap-input pe-8 font-mono text-xs"
                               placeholder="{{ $social['placeholder'] }}"
                               autocomplete="off">
                        <button type="button" title="پاک کردن" aria-label="پاک کردن {{ $social['name'] }}"
                                x-show="isFilled('{{ $social['key'] }}')" x-cloak
                                x-on:click="clear()"
                                class="absolute end-2 top-1/2 -translate-y-1/2 rounded-md p-1 text-ink-3 transition-colors hover:bg-red-50 hover:text-red-500 dark:hover:bg-red-500/10">
                            <x-admin-panel::icon name="x" class="size-3.5" />
                        </button>
                    </div>
                </div>
            @endforeach
        </div>

        <p class="flex items-start gap-2 rounded-xl border border-sky-200 bg-sky-50 p-3 text-[12px] leading-6 text-sky-800 dark:border-sky-500/20 dark:bg-sky-500/10 dark:text-sky-300">
            <x-admin-panel::icon name="info" class="mt-0.5 size-4 shrink-0" />
            <span>
                شبکه‌های دارای لینک در سایت نمایش داده می‌شوند؛ برای مخفی‌کردن یک شبکه، فیلدش را خالی بگذارید. شبکه‌هایی که نشان «تم» دارند از تنظیمات قالب فعال می‌شوند و در فوتر سایت هم می‌آیند.
            </span>
        </p>

        {{-- نوار ذخیره --}}
        <div class="sticky bottom-4 z-10 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-line bg-surface/95 p-3.5 shadow-lg backdrop-blur dark:border-night-line dark:bg-night-2/95">
            <span class="flex items-center gap-1.5 text-[11px] text-ink-3 dark:text-night-ink-2">
                <x-admin-panel::icon name="info" class="size-3.5" />
                لینک‌ها را کامل و با https وارد کنید.
            </span>
            <button type="submit" wire:loading.attr="disabled" wire:target="save" class="ap-btn-primary !py-2.5">
                <span wire:loading.remove wire:target="save" class="flex items-center gap-2">
                    <x-admin-panel::icon name="check-circle" class="size-4" />
                    ذخیره تغییرات
                </span>
                <span wire:loading wire:target="save" class="flex items-center gap-2">
                    <svg class="size-4 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                        <path d="M21 12a9 9 0 1 1-6.219-8.56" stroke-linecap="round" />
                    </svg>
                    در حال ذخیره…
                </span>
            </button>
        </div>
    </form>
</div>
