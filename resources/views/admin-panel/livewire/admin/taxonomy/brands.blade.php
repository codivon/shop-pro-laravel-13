{{-- لیست برندها + کارت ایجاد سریع — پنل جدید (فاز ۵) --}}
<div class="mx-auto max-w-7xl space-y-5">

    {{-- سربرگ --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap items-center gap-2">
            <span class="ap-badge bg-brand-50 text-brand-700 dark:bg-brand-500/12 dark:text-brand-400">
                کل برندها: <span class="fa-digits">{{ number_format($this->totalCount) }}</span>
            </span>
        </div>
    </div>

    {{-- کارت ایجاد برند جدید (پورت create قدیمی به‌صورت کارت درون‌صفحه‌ای) --}}
    <div class="ap-card overflow-hidden" x-data="{ open: false }">
        <button type="button" class="flex w-full items-center justify-between p-4 text-start sm:p-5" x-on:click="open = !open">
            <span class="flex items-center gap-2 text-sm font-bold">
                <x-admin-panel::icon name="plus" class="size-4.5 text-brand-600 dark:text-brand-400" />
                برند جدید
            </span>
            <span class="ap-chevron" :class="open ? 'is-open' : ''">
                <x-admin-panel::icon name="chevron-left" class="size-4 text-ink-3" />
            </span>
        </button>

        <div x-show="open" x-cloak
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
             class="border-t border-line p-4 sm:p-5 dark:border-night-line">
            <form wire:submit="createBrand" class="space-y-4">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold" for="brand-create-name">نام <span class="text-red-500">*</span></label>
                        <input id="brand-create-name" type="text" wire:model="createName" class="ap-input !py-2" placeholder="مثال: سامسونگ">
                        @error('createName') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold" for="brand-create-slug">آدرس (slug) <span class="text-red-500">*</span></label>
                        <div class="flex gap-2">
                            <input id="brand-create-slug" type="text" wire:model="createSlug" dir="ltr"
                                   class="ap-input !py-2 font-mono" placeholder="samsung">
                            <button type="button" wire:click="generateCreateSlug" class="ap-btn-outline shrink-0 !px-3 !py-2"
                                    title="تولید خودکار از نام">
                                <x-admin-panel::icon name="wand-2" class="size-4" />
                            </button>
                        </div>
                        @error('createSlug') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold" for="brand-create-image">تصویر</label>
                        <input id="brand-create-image" type="file" accept="image/*" wire:model="createImage"
                               class="block w-full cursor-pointer rounded-xl border border-line bg-transparent text-xs text-ink-2
                                      file:me-3 file:cursor-pointer file:rounded-lg file:border-0 file:bg-brand-600 file:px-3 file:py-1.5
                                      file:text-xs file:font-semibold file:text-white hover:file:bg-brand-700 dark:border-night-line">
                        @error('createImage') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="flex items-end">
                        <button type="submit" wire:loading.attr="disabled" wire:target="createBrand" class="ap-btn w-full !py-2">
                            <x-admin-panel::icon name="plus" class="size-4" />
                            ایجاد برند
                        </button>
                    </div>
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold" for="brand-create-description">توضیحات</label>
                    <textarea id="brand-create-description" wire:model="createDescription" rows="3" class="ap-input !py-2" placeholder="توضیح اختیاری برند…"></textarea>
                    @error('createDescription') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>
                @if ($createImage)
                    <div class="flex items-center gap-3 rounded-xl border border-line bg-surface-2 p-3 dark:border-night-line dark:bg-night-3">
                        <img src="{{ $createImage->temporaryUrl() }}" alt="پیش‌نمایش تصویر" class="size-12 rounded-lg border border-line object-cover dark:border-night-line">
                        <div class="text-xs">
                            <p class="font-semibold">تصویر انتخاب‌شده</p>
                            <button type="button" wire:click="$set('createImage', null)" class="mt-0.5 flex items-center gap-1 font-semibold text-red-500 hover:underline">
                                <x-admin-panel::icon name="x" class="size-3.5" />
                                حذف فایل انتخاب‌شده
                            </button>
                        </div>
                    </div>
                @endif
            </form>
        </div>
    </div>

    {{-- کارت فیلترها --}}
    <div class="ap-card p-4 sm:p-5" x-data="{ open: false }">
        <button type="button" class="flex w-full items-center justify-between text-start" x-on:click="open = !open">
            <span class="flex items-center gap-2 text-sm font-bold">
                <x-admin-panel::icon name="filter" class="size-4.5 text-brand-600 dark:text-brand-400" />
                فیلتر برندها
                @if ($search)
                    <span class="size-2 rounded-full bg-brand-500"></span>
                @endif
            </span>
            <span class="ap-chevron" :class="open ? 'is-open' : ''">
                <x-admin-panel::icon name="chevron-left" class="size-4 text-ink-3" />
            </span>
        </button>

        <div x-show="open" x-cloak
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
             class="pt-4">
            <form wire:submit.prevent="applyFilters" class="grid grid-cols-1 gap-3.5 sm:grid-cols-2 lg:grid-cols-4">
                <div>
                    <label class="mb-1.5 block text-xs font-semibold">جستجو</label>
                    <input type="text" wire:model.live.debounce.400ms="search" class="ap-input !py-2" placeholder="نام، نام انگلیسی یا slug…">
                </div>
                <div class="flex items-end">
                    <button type="submit" class="ap-btn-outline !py-2">
                        <x-admin-panel::icon name="rotate-ccw" class="size-4" />
                        بازنشانی فیلترها
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- جدول --}}
    <div class="ap-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full min-w-4xl text-start text-[13px]">
                <thead>
                    <tr class="border-b border-line bg-surface-2 text-ink-2 dark:border-night-line dark:bg-night-3">
                        <th class="w-16 px-3 py-3 text-start font-bold">ID</th>
                        <th class="w-16 px-3 py-3 text-start font-bold">تصویر</th>
                        <th class="px-3 py-3 text-start font-bold">نام</th>
                        <th class="px-3 py-3 text-start font-bold">اسلاگ</th>
                        <th class="px-3 py-3 text-start font-bold">توضیحات</th>
                        <th class="px-3 py-3 text-start font-bold">تاریخ ثبت</th>
                        <th class="w-20 px-3 py-3 text-start font-bold">عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->brands as $brand)
                        <tr wire:key="brand-{{ $brand->id }}"
                            class="border-b border-line/60 transition-colors last:border-0 hover:bg-surface-2 dark:border-night-line/60 dark:hover:bg-night-3/60">
                            <td class="px-3 py-3 fa-digits font-bold">{{ number_format($brand->id) }}</td>
                            <td class="px-3 py-3">
                                <img src="{{ $brand->image ? asset($brand->image) : asset('/empty.svg') }}"
                                     alt="{{ $brand->name }}"
                                     class="size-10 rounded-xl border border-line object-cover dark:border-night-line">
                            </td>
                            <td class="max-w-40 px-3 py-3">
                                <p class="truncate font-semibold">{{ $brand->name }}</p>
                            </td>
                            <td class="max-w-40 px-3 py-3">
                                <code class="truncate text-[11px] text-ink-2 dark:text-night-ink-2" dir="ltr">{{ $brand->slug ?? '—' }}</code>
                            </td>
                            <td class="max-w-56 px-3 py-3">
                                <p class="truncate text-ink-2 dark:text-night-ink-2">{{ \Illuminate\Support\Str::limit(strip_tags((string) $brand->description), 80) ?: '—' }}</p>
                            </td>
                            <td class="px-3 py-3 fa-digits whitespace-nowrap">{{ $this->jdate($brand->created_at) }}</td>
                            <td class="px-3 py-3">
                                <div class="flex items-center gap-0.5">
                                    <a href="{{ route('np.brands.edit', ['brand' => $brand]) }}" wire:navigate
                                       class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-brand-50 hover:text-brand-600 dark:hover:bg-brand-500/10"
                                       title="ویرایش برند" aria-label="ویرایش">
                                        <x-admin-panel::icon name="pencil" class="size-4" />
                                    </a>
                                    <button type="button" wire:click="confirmDelete({{ $brand->id }})"
                                            class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-500/10"
                                            title="حذف برند" aria-label="حذف">
                                        <x-admin-panel::icon name="trash-2" class="size-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">
                                <div class="flex flex-col items-center gap-2 py-14 text-ink-3 dark:text-night-ink-2">
                                    <x-admin-panel::icon name="tags" class="size-10 opacity-40" />
                                    <p class="text-sm font-medium">برندی برای نمایش وجود ندارد</p>
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
                    نمایش <span class="fa-digits">{{ number_format($this->brands->firstItem() ?? 0) }}</span> تا
                    <span class="fa-digits">{{ number_format($this->brands->lastItem() ?? 0) }}</span> از
                    <span class="fa-digits">{{ number_format($this->brands->total()) }}</span> برند
                </p>
                <nav class="flex items-center gap-1" aria-label="صفحه‌بندی">
                    @if ($this->brands->onFirstPage())
                        <span class="rounded-lg p-2 text-ink-3 opacity-40"><x-admin-panel::icon name="chevron-right" class="size-4" /></span>
                    @else
                        <button wire:click="previousPage" class="rounded-lg p-2 text-ink-2 transition-colors hover:bg-surface-3 dark:hover:bg-night-3" aria-label="صفحه قبل">
                            <x-admin-panel::icon name="chevron-right" class="size-4" />
                        </button>
                    @endif

                    @foreach ($this->brands->onEachSide(1)->toArray()['links'] as $link)
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

                    @if ($this->brands->hasMorePages())
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
            <h3 class="text-base font-bold">حذف برند</h3>
            <p class="mt-1.5 text-[13px] leading-6 text-ink-2 dark:text-night-ink-2">
                با حذف برند دیگر قادر به بازیابی آن نخواهید بود؛ تصویر برند هم حذف می‌شود و برند محصولات به «بدون برند» تغییر می‌کند.
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
    <div wire:loading wire:target="search, previousPage, nextPage, gotoPage, createBrand, delete, generateCreateSlug"
         class="pointer-events-none fixed bottom-6 start-6 z-[65] flex items-center gap-2 rounded-xl border border-line bg-surface px-4 py-2.5 shadow-pop dark:border-night-line dark:bg-night-3">
        <svg class="size-4 animate-spin text-brand-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
            <path d="M21 12a9 9 0 1 1-6.219-8.56" stroke-linecap="round" />
        </svg>
        <span class="text-[13px] font-semibold">در حال بارگذاری…</span>
    </div>
</div>
