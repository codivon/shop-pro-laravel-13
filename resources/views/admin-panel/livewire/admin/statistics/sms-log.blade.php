{{-- لاگ پیامک‌های ارسالی — پورت sms/sms-log قدیمی (فاز ۷-ج۲) --}}
<div class="mx-auto max-w-7xl space-y-5">

    {{-- کارت‌های آمار (مستقل از فیلتر — پورت دقیق) --}}
    <section class="stagger grid grid-cols-2 gap-4 xl:grid-cols-4">
        <div class="ap-card p-5">
            <div class="flex items-center gap-4">
                <span class="ap-stat-icon bg-sky-50 text-sky-600 dark:bg-sky-500/12 dark:text-sky-400">
                    <x-admin-panel::icon name="send" class="size-5.5" />
                </span>
                <div class="min-w-0">
                    <p class="text-[13px] font-medium text-ink-2 dark:text-night-ink-2">کل پیامک‌های ارسالی</p>
                    <p class="fa-digits mt-0.5 text-xl font-extrabold">{{ $this->fa($this->stats['total']) }}</p>
                </div>
            </div>
        </div>
        <div class="ap-card p-5">
            <div class="flex items-center gap-4">
                <span class="ap-stat-icon bg-brand-50 text-brand-600 dark:bg-brand-500/12 dark:text-brand-400">
                    <x-admin-panel::icon name="calendar" class="size-5.5" />
                </span>
                <div class="min-w-0">
                    <p class="text-[13px] font-medium text-ink-2 dark:text-night-ink-2">ارسال‌های امروز</p>
                    <p class="fa-digits mt-0.5 text-xl font-extrabold">{{ $this->fa($this->stats['today']) }}</p>
                </div>
            </div>
        </div>
        <div class="ap-card p-5">
            <div class="flex items-center gap-4">
                <span class="ap-stat-icon bg-amber-50 text-amber-600 dark:bg-amber-500/12 dark:text-amber-400">
                    <x-admin-panel::icon name="chart-line" class="size-5.5" />
                </span>
                <div class="min-w-0">
                    <p class="text-[13px] font-medium text-ink-2 dark:text-night-ink-2">۷ روز اخیر</p>
                    <p class="fa-digits mt-0.5 text-xl font-extrabold">{{ $this->fa($this->stats['week']) }}</p>
                </div>
            </div>
        </div>
        <div class="ap-card p-5">
            <div class="flex items-center gap-4">
                <span class="ap-stat-icon bg-indigo-50 text-indigo-600 dark:bg-indigo-500/12 dark:text-indigo-400">
                    <x-admin-panel::icon name="chart-column" class="size-5.5" />
                </span>
                <div class="min-w-0">
                    <p class="text-[13px] font-medium text-ink-2 dark:text-night-ink-2">۳۰ روز اخیر</p>
                    <p class="fa-digits mt-0.5 text-xl font-extrabold">{{ $this->fa($this->stats['month']) }}</p>
                </div>
            </div>
        </div>
    </section>

    {{-- فیلترها (پورت فرم GET قدیمی) --}}
    <div class="ap-card p-4 sm:p-5">
        <form wire:submit="applyFilters" class="flex flex-wrap items-end gap-3">
            <div class="min-w-52 flex-1">
                <label class="mb-1.5 block text-xs font-semibold" for="sms-mobile">جستجوی شماره موبایل</label>
                <input id="sms-mobile" type="text" wire:model="mobile" dir="ltr" class="ap-input !py-2 font-mono"
                       placeholder="09xxxxxxxxx">
            </div>
            <div>
                <label class="mb-1.5 block text-xs font-semibold" for="sms-period">بازه زمانی</label>
                <select id="sms-period" wire:model.live="period" class="ap-input !py-2 min-w-36">
                    <option value="">همه زمان‌ها</option>
                    <option value="today">امروز</option>
                    <option value="week">۷ روز اخیر</option>
                    <option value="month">۳۰ روز اخیر</option>
                </select>
            </div>
            <button type="submit" wire:loading.attr="disabled" wire:target="applyFilters" class="ap-btn !py-2">
                <x-admin-panel::icon name="search" class="size-4" />
                اعمال
            </button>
            @if ($mobile || $period)
                <button type="button" wire:click="resetFilters" wire:loading.attr="disabled" wire:target="resetFilters"
                        class="ap-btn-ghost !py-2" title="حذف فیلترها">
                    <x-admin-panel::icon name="x" class="size-4" />
                    حذف فیلترها
                </button>
            @endif
        </form>
    </div>

    {{-- جدول پیامک‌ها --}}
    <div class="ap-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full min-w-3xl text-start text-[13px]">
                <thead>
                    <tr class="border-b border-line bg-surface-2 text-ink-2 dark:border-night-line dark:bg-night-3">
                        <th class="w-16 px-3 py-3 text-start font-bold">#</th>
                        <th class="px-3 py-3 text-start font-bold">شماره همراه</th>
                        <th class="px-3 py-3 text-start font-bold">نوع</th>
                        <th class="px-3 py-3 text-start font-bold">زمان ارسال</th>
                        <th class="w-20 px-3 py-3 text-start font-bold">عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->sms as $item)
                        <tr wire:key="sms-{{ $item->id }}"
                            class="border-b border-line/60 transition-colors last:border-0 hover:bg-surface-2 dark:border-night-line/60 dark:hover:bg-night-3/60">
                            <td class="fa-digits px-3 py-3 font-bold text-ink-3 dark:text-night-ink-2">
                                {{ $this->fa($this->sms->firstItem() + $loop->index) }}
                            </td>
                            <td class="px-3 py-3"><code class="font-mono text-[12px]" dir="ltr">{{ $item->mobile }}</code></td>
                            <td class="px-3 py-3">
                                <span class="ap-badge bg-sky-50 text-sky-700 dark:bg-sky-500/12 dark:text-sky-400">
                                    {{ $item->type() }}
                                </span>
                            </td>
                            <td class="fa-digits whitespace-nowrap px-3 py-3 text-ink-2 dark:text-night-ink-2">
                                {{ jdate($item->created_at)->format('Y/m/d - H:i') }}
                            </td>
                            <td class="px-3 py-3">
                                @if (Route::has('np.sms.show'))
                                    <a href="{{ route('np.sms.show', ['sms' => $item]) }}" wire:navigate
                                       class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-brand-50 hover:text-brand-600 dark:hover:bg-brand-500/10"
                                       title="مشاهده جزئیات" aria-label="مشاهده">
                                        <x-admin-panel::icon name="eye" class="size-4" />
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">
                                <div class="flex flex-col items-center gap-2 py-14 text-ink-3 dark:text-night-ink-2">
                                    <x-admin-panel::icon name="message-square" class="size-10 opacity-40" />
                                    <p class="text-sm font-medium">پیامکی برای نمایش وجود ندارد</p>
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
                    نمایش <span class="fa-digits">{{ number_format($this->sms->firstItem() ?? 0) }}</span> تا
                    <span class="fa-digits">{{ number_format($this->sms->lastItem() ?? 0) }}</span> از
                    <span class="fa-digits">{{ number_format($this->sms->total()) }}</span> پیامک
                </p>
                <nav class="flex items-center gap-1" aria-label="صفحه‌بندی">
                    @if ($this->sms->onFirstPage())
                        <span class="rounded-lg p-2 text-ink-3 opacity-40"><x-admin-panel::icon name="chevron-right" class="size-4" /></span>
                    @else
                        <button wire:click="previousPage" class="rounded-lg p-2 text-ink-2 transition-colors hover:bg-surface-3 dark:hover:bg-night-3" aria-label="صفحه قبل">
                            <x-admin-panel::icon name="chevron-right" class="size-4" />
                        </button>
                    @endif

                    @foreach ($this->sms->onEachSide(1)->toArray()['links'] as $link)
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

                    @if ($this->sms->hasMorePages())
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
</div>
