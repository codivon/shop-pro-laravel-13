<div>
    {{-- پنل کمیسیون فروشندگان — فاز ۱۰۳ (الگوی ap-* مطابق صفحات مالی) --}}

    {{-- آمار سربرگ --}}
    <div class="mb-4 grid grid-cols-2 gap-3 lg:grid-cols-4">
        <div class="ap-card p-4">
            <p class="text-[11px] font-semibold text-ink-3 dark:text-night-ink-2">کمیسیون کل (موفق)</p>
            <p class="fa-digits mt-1.5 text-lg font-bold">{{ number_format($this->statistics['total']) }} <span class="text-[11px] font-semibold text-ink-3">تومان</span></p>
        </div>
        <div class="ap-card p-4">
            <p class="text-[11px] font-semibold text-ink-3 dark:text-night-ink-2">کمیسیون این ماه</p>
            <p class="fa-digits mt-1.5 text-lg font-bold">{{ number_format($this->statistics['month']) }} <span class="text-[11px] font-semibold text-ink-3">تومان</span></p>
        </div>
        <div class="ap-card p-4">
            <p class="text-[11px] font-semibold text-ink-3 dark:text-night-ink-2">سفارش‌های دارای کمیسیون</p>
            <p class="fa-digits mt-1.5 text-lg font-bold">{{ number_format($this->statistics['orders']) }}</p>
        </div>
        <div class="ap-card p-4">
            <p class="text-[11px] font-semibold text-ink-3 dark:text-night-ink-2">فروشندگان کاملاً فعال</p>
            <p class="fa-digits mt-1.5 text-lg font-bold">{{ number_format($this->statistics['sellers']) }}</p>
        </div>
    </div>

    {{-- راهنمای اولویت --}}
    <div class="mb-4 flex items-start gap-2 rounded-xl border border-sky-200 bg-sky-50 p-3.5 text-sky-800 dark:border-sky-500/20 dark:bg-sky-500/10 dark:text-sky-300">
        <x-admin-panel::icon name="info" class="mt-0.5 size-4 shrink-0" />
        <div class="text-xs leading-6">
            <p class="font-bold">اولویت کمیسیون مؤثر: کمیسیون اختصاصی فروشنده ← کمیسیون دسته‌بندی ← پیش‌فرض سراسری</p>
            <p>
                پیش‌فرض سراسری و گیتِ اعتبار کمیسیون اختصاصی در
                <a href="{{ route('np.developer.index') }}" wire:navigate class="font-bold underline">تنظیمات برنامه‌نویس ← تب «چندفروشندگی»</a>
                مدیریت می‌شود. کمیسیون هر دسته‌بندی هم از فرم خود دسته‌بندی تنظیم می‌شود.
                لیست واریزی‌های کمیسیون: <a href="{{ route('np.transactions.seller-deposits') }}" wire:navigate class="font-bold underline">لیست کمیسیون فروشندگان</a>.
            </p>
        </div>
    </div>

    {{-- جدول --}}
    <div class="ap-card overflow-hidden">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line px-4 py-3 dark:border-night-line">
            <h2 class="flex items-center gap-2 text-sm font-bold">
                <x-admin-panel::icon name="percent" class="size-4.5 text-brand-600 dark:text-brand-400" />
                کمیسیون فروشندگان
            </h2>
            <input type="search" wire:model.live.debounce.400ms="search" placeholder="جست‌وجو (نام کسب‌وکار، ایمیل، موبایل…)"
                   class="ap-input !w-64 !py-2" aria-label="جست‌وجوی فروشنده">
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-3xl text-start text-[13px]">
                <thead>
                    <tr class="border-b border-line bg-surface-2 text-ink-2 dark:border-night-line dark:bg-night-3">
                        <th class="w-16 px-3 py-3 text-start font-bold">ID</th>
                        <th class="px-3 py-3 text-start font-bold">فروشنده</th>
                        <th class="px-3 py-3 text-start font-bold">کمیسیون اختصاصی</th>
                        <th class="px-3 py-3 text-start font-bold">کمیسیون مؤثر</th>
                        <th class="px-3 py-3 text-center font-bold">وضعیت</th>
                        <th class="w-24 px-3 py-3 text-start font-bold">عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->items as $item)
                        <tr wire:key="vendor-commission-{{ $item->id }}"
                            class="border-b border-line/60 transition-colors last:border-0 hover:bg-surface-2 dark:border-night-line/60 dark:hover:bg-night-3/60">
                            <td class="px-3 py-3 fa-digits font-bold">{{ number_format($item->id) }}</td>
                            <td class="max-w-44 px-3 py-3">
                                <a href="{{ route('np.sellers.show', $item) }}" wire:navigate
                                   class="block truncate font-semibold text-brand-700 transition-colors hover:text-brand-600 dark:text-brand-400"
                                   title="نمایش فروشنده">
                                    {{ $item->seller_info?->business_name ?? $item->seller_info?->fullname ?? $item->slug }}
                                </a>
                                <span class="block truncate text-[11px] text-ink-3 dark:text-night-ink-2" dir="ltr">{{ $item->email }}</span>
                            </td>
                            <td class="px-3 py-3">
                                @if (! is_null($item->commission))
                                    <span class="ap-badge bg-brand-600/10 fa-digits text-brand-700 dark:bg-brand-500/15 dark:text-brand-400">{{ (int) $item->commission }}٪</span>
                                @else
                                    <span class="text-[11px] text-ink-3 dark:text-night-ink-2">— تنظیم نشده</span>
                                @endif
                            </td>
                            <td class="px-3 py-3 text-[12px] font-semibold">{{ $this->effectiveCommissionLabel($item) }}</td>
                            <td class="px-3 py-3 text-center">
                                @if ($item->status == 'ACTIVE' && $item->status_work == 'ACTIVE' && $item->status_register == 'complete' && $item->status_documents == 'Accept')
                                    <span class="ap-badge bg-emerald-50 text-emerald-700 dark:bg-emerald-500/12 dark:text-emerald-400">کاملاً فعال</span>
                                @else
                                    <span class="ap-badge bg-amber-50 text-amber-700 dark:bg-amber-500/12 dark:text-amber-400">در حال تکمیل</span>
                                @endif
                            </td>
                            <td class="px-3 py-3">
                                @if (\App\Support\MultiVendorSettings::sellerOverrideEnabled())
                                    <button type="button" wire:click="editCommission({{ $item->id }})"
                                            class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-brand-50 hover:text-brand-600 dark:hover:bg-brand-500/10"
                                            title="ویرایش کمیسیون اختصاصی" aria-label="ویرایش کمیسیون">
                                        <x-admin-panel::icon name="pencil" class="size-4" />
                                    </button>
                                @else
                                    <span class="text-[11px] text-ink-3 dark:text-night-ink-2" title="گیت از تنظیمات چندفروشندگی">غیرفعال</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
                                <div class="flex flex-col items-center gap-2 py-14 text-ink-3 dark:text-night-ink-2">
                                    <x-admin-panel::icon name="store" class="size-10 opacity-40" />
                                    <p class="text-sm font-medium">فروشنده‌ای یافت نشد!</p>
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
                    نمایش <span class="fa-digits">{{ number_format($this->items->firstItem() ?? 0) }}</span> تا
                    <span class="fa-digits">{{ number_format($this->items->lastItem() ?? 0) }}</span> از
                    <span class="fa-digits">{{ number_format($this->items->total()) }}</span> فروشنده
                </p>
                <nav class="flex items-center gap-1" aria-label="صفحه‌بندی">
                    @if ($this->items->onFirstPage())
                        <span class="rounded-lg p-2 text-ink-3 opacity-40"><x-admin-panel::icon name="chevron-right" class="size-4" /></span>
                    @else
                        <button wire:click="previousPage" class="rounded-lg p-2 text-ink-2 transition-colors hover:bg-surface-3 dark:hover:bg-night-3" aria-label="صفحه قبل">
                            <x-admin-panel::icon name="chevron-right" class="size-4" />
                        </button>
                    @endif

                    @foreach ($this->items->onEachSide(1)->toArray()['links'] as $link)
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

                    @if ($this->items->hasMorePages())
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

    {{-- مودال ویرایش کمیسیون اختصاصی --}}
    <div x-data="{ open: @entangle('editing').live }"
         x-show="open" x-cloak
         class="fixed inset-0 z-[60] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-950/50 backdrop-blur-sm" x-show="open" x-on:click="open=false" x-transition.opacity aria-hidden="true"></div>
        <div x-show="open" x-on:click.stop
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
             class="relative w-full max-w-sm rounded-2xl border border-line bg-surface p-6 shadow-pop dark:border-night-line dark:bg-night-2"
             role="dialog" aria-modal="true">
            <div class="mb-3 flex size-12 items-center justify-center rounded-2xl bg-brand-600/10 text-brand-600 dark:bg-brand-500/15 dark:text-brand-400">
                <x-admin-panel::icon name="percent" class="size-6" />
            </div>
            <h3 class="text-base font-bold">کمیسیون اختصاصی فروشنده</h3>
            <p class="mt-1.5 text-[13px] leading-6 text-ink-2 dark:text-night-ink-2">
                <span class="font-bold">{{ $editingLabel }}</span> — خالی گذاشتن یعنی حذف درجهٔ اختصاصی و برگشت به کمیسیون دسته‌بندی/پیش‌فرض.
            </p>
            <div class="mt-4">
                <label class="mb-1.5 block text-xs font-semibold" for="vendor-commission-value">درصد کمیسیون (۰ تا ۱۰۰)</label>
                <input id="vendor-commission-value" type="number" min="0" max="100" wire:model="editingValue" class="ap-input fa-digits text-center">
                @error('editingValue')
                    <p class="mt-1.5 text-xs font-medium text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>
            <div class="mt-5 flex gap-2.5">
                <button type="button" wire:click="saveCommission" wire:loading.attr="disabled" wire:target="saveCommission"
                        class="ap-btn flex-1 bg-brand-600 text-white hover:bg-brand-700">
                    ذخیره
                </button>
                <button type="button" x-on:click="open=false" class="ap-btn-ghost flex-1">انصراف</button>
            </div>
        </div>
    </div>
</div>
