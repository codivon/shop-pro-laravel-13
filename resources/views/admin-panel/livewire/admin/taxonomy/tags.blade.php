{{-- لیست تگ‌ها — پنل جدید (فاز ۵) | پورت back/tags/index + tag-details --}}
<div class="mx-auto max-w-7xl space-y-5">

    {{-- سربرگ --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-2.5">
            <span class="flex size-10 items-center justify-center rounded-xl bg-brand-600 text-white">
                <x-admin-panel::icon name="tags" class="size-5" />
            </span>
            <div>
                <h2 class="text-base font-extrabold">تگ‌ها</h2>
                <p class="text-[11px] text-ink-3 dark:text-night-ink-2">برچسب‌های استفاده‌شده در محصولات و نوشته‌ها</p>
            </div>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <button type="button" wire:click="export" class="ap-btn-outline !py-2">
                <x-admin-panel::icon name="download" class="size-4" />
                خروجی CSV
            </button>
            @can('tags.create')
                <button type="button" wire:click="$set('showCreateModal', true)" class="ap-btn-primary !py-2">
                    <x-admin-panel::icon name="plus" class="size-4" />
                    ایجاد تگ جدید
                </button>
            @endcan
        </div>
    </div>

    {{-- آمار (پورت index statistics) --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="ap-card p-4 sm:p-5">
            <div class="flex items-center justify-between">
                <p class="text-[12px] font-semibold text-ink-2 dark:text-night-ink-2">کل تگ‌ها</p>
                <span class="ap-stat-icon bg-brand-50 text-brand-600 dark:bg-brand-500/12 dark:text-brand-400">
                    <x-admin-panel::icon name="tags" class="size-4.5" />
                </span>
            </div>
            <p class="fa-digits mt-2 text-2xl font-extrabold">{{ number_format($this->statistics['total']) }}</p>
        </div>
        <div class="ap-card p-4 sm:p-5">
            <div class="flex items-center justify-between">
                <p class="text-[12px] font-semibold text-ink-2 dark:text-night-ink-2">کل بازدیدها</p>
                <span class="ap-stat-icon bg-emerald-50 text-emerald-600 dark:bg-emerald-500/12 dark:text-emerald-400">
                    <x-admin-panel::icon name="eye" class="size-4.5" />
                </span>
            </div>
            <p class="fa-digits mt-2 text-2xl font-extrabold">{{ number_format($this->statistics['total_views']) }}</p>
        </div>
        <div class="ap-card p-4 sm:p-5">
            <div class="flex items-center justify-between">
                <p class="text-[12px] font-semibold text-ink-2 dark:text-night-ink-2">پربکارترین تگ</p>
                <span class="ap-stat-icon bg-amber-50 text-amber-600 dark:bg-amber-500/12 dark:text-amber-400">
                    <x-admin-panel::icon name="star" class="size-4.5" />
                </span>
            </div>
            <p class="mt-2 truncate text-lg font-extrabold">{{ $this->statistics['most_used']?->name ?? '—' }}</p>
            @if ($this->statistics['most_used'])
                <p class="text-[11px] text-ink-3 dark:text-night-ink-2">
                    <span class="fa-digits">{{ number_format($this->statistics['most_used']->taggables_count) }}</span> بار استفاده
                </p>
            @endif
        </div>
        <div class="ap-card p-4 sm:p-5">
            <div class="flex items-center justify-between">
                <p class="text-[12px] font-semibold text-ink-2 dark:text-night-ink-2">بیشترین بازدید</p>
                <span class="ap-stat-icon bg-sky-50 text-sky-600 dark:bg-sky-500/12 dark:text-sky-400">
                    <x-admin-panel::icon name="trending-up" class="size-4.5" />
                </span>
            </div>
            <p class="mt-2 truncate text-lg font-extrabold">{{ $this->statistics['most_viewed']?->name ?? '—' }}</p>
            @if ($this->statistics['most_viewed'])
                <p class="fa-digits text-[11px] text-ink-3 dark:text-night-ink-2">
                    {{ number_format($this->statistics['most_viewed']->view_count) }} بازدید
                </p>
            @endif
        </div>
    </div>

    {{-- کارت جستجو --}}
    <div class="ap-card p-4 sm:p-5" x-data="{ open: false }">
        <button type="button" class="flex w-full items-center justify-between text-start" x-on:click="open = !open">
            <span class="flex items-center gap-2 text-sm font-bold">
                <x-admin-panel::icon name="search" class="size-4.5 text-brand-600 dark:text-brand-400" />
                جستجوی تگ‌ها
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
            <form wire:submit.prevent="applyFilters" class="grid grid-cols-1 gap-3.5 sm:grid-cols-3">
                <div class="sm:col-span-2">
                    <label class="mb-1.5 block text-xs font-semibold">عنوان یا اسلاگ</label>
                    <input type="text" wire:model.live.debounce.400ms="search" class="ap-input !py-2" placeholder="عنوان یا اسلاگ تگ…">
                </div>
                <div class="flex items-end">
                    <button type="submit" class="ap-btn-outline !py-2">
                        <x-admin-panel::icon name="rotate-ccw" class="size-4" />
                        پاک کردن جستجو
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- نوار عملیات گروهی --}}
    <div x-show="$wire.selected.length > 0" x-cloak
         class="sticky top-20 z-20 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-brand-200 bg-brand-50/95 px-4 py-2.5 shadow-card backdrop-blur dark:border-brand-500/25 dark:bg-brand-950/80">
        <span class="text-[13px] font-bold text-brand-800 dark:text-brand-300">
            <span class="fa-digits" x-text="ApFormat.faDigits($wire.selected.length)"></span> تگ انتخاب شده
        </span>
        @can('tags.delete')
            <button type="button" wire:click="$set('confirmingBulkDelete', true)"
                    class="ap-btn !px-3 !py-1.5 text-[13px] bg-red-600 text-white hover:bg-red-700">
                <x-admin-panel::icon name="trash-2" class="size-4" />
                حذف موارد انتخاب شده
            </button>
        @endcan
    </div>

    {{-- جدول تگ‌ها --}}
    <div class="ap-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full min-w-4xl text-start text-[13px]">
                <thead>
                    <tr class="border-b border-line bg-surface-2 text-ink-2 dark:border-night-line dark:bg-night-3">
                        <th class="w-10 px-3 py-3">
                            <input type="checkbox" wire:model.live="selectAll"
                                   class="size-4 rounded-md border-line text-brand-600 focus:ring-brand-500/30 dark:border-night-line"
                                   aria-label="انتخاب همه">
                        </th>
                        <th class="w-16 px-2 py-3 text-start font-bold">شناسه</th>
                        <th class="px-3 py-3 text-start font-bold">عنوان تگ</th>
                        <th class="px-3 py-3 text-start font-bold">اسلاگ</th>
                        <th class="px-3 py-3 text-center font-bold">تعداد استفاده</th>
                        <th class="px-3 py-3 text-center font-bold">بازدید</th>
                        <th class="px-3 py-3 text-start font-bold">تاریخ ایجاد</th>
                        <th class="w-28 px-3 py-3 text-center font-bold">عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->tags as $tag)
                        <tr wire:key="tag-{{ $tag->id }}"
                            class="border-b border-line/60 transition-colors last:border-0 hover:bg-surface-2 dark:border-night-line/60 dark:hover:bg-night-3/60">
                            <td class="px-3 py-3">
                                <input type="checkbox" wire:model.live="selected" value="{{ $tag->id }}"
                                       class="size-4 rounded-md border-line text-brand-600 focus:ring-brand-500/30 dark:border-night-line"
                                       aria-label="انتخاب تگ {{ $tag->id }}">
                            </td>
                            <td class="px-2 py-3 fa-digits font-bold">{{ number_format($tag->id) }}</td>
                            <td class="px-3 py-3 font-semibold">{{ $tag->name }}</td>
                            <td class="px-3 py-3"><code class="text-[11px] text-ink-2 dark:text-night-ink-2">{{ $tag->slug }}</code></td>
                            <td class="px-3 py-3 text-center">
                                <span class="fa-digits ap-badge bg-brand-50 text-brand-700 dark:bg-brand-500/12 dark:text-brand-400">
                                    {{ number_format($tag->taggables_count) }} بار
                                </span>
                            </td>
                            <td class="px-3 py-3 text-center fa-digits">{{ number_format($tag->view_count) }}</td>
                            <td class="px-3 py-3 fa-digits whitespace-nowrap">{{ $this->jdate($tag->created_at) }}</td>
                            <td class="px-3 py-3">
                                <div class="flex items-center justify-center gap-0.5">
                                    @can('tags.show')
                                        <button type="button" wire:click="openDetails({{ $tag->id }})"
                                                class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-brand-50 hover:text-brand-600 dark:hover:bg-brand-500/10"
                                                title="جزئیات" aria-label="جزئیات">
                                            <x-admin-panel::icon name="info" class="size-4" />
                                        </button>
                                    @endcan
                                    @can('tags.update')
                                        <button type="button" wire:click="editTag({{ $tag->id }})"
                                                class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-brand-50 hover:text-brand-600 dark:hover:bg-brand-500/10"
                                                title="ویرایش" aria-label="ویرایش">
                                            <x-admin-panel::icon name="pencil" class="size-4" />
                                        </button>
                                    @endcan
                                    @can('tags.delete')
                                        <button type="button" wire:click="confirmDelete({{ $tag->id }})"
                                                class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-500/10"
                                                title="حذف" aria-label="حذف">
                                            <x-admin-panel::icon name="trash-2" class="size-4" />
                                        </button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8">
                                <div class="flex flex-col items-center gap-2 py-14 text-ink-3 dark:text-night-ink-2">
                                    <x-admin-panel::icon name="tags" class="size-10 opacity-40" />
                                    <p class="text-sm font-medium">هیچ تگی یافت نشد</p>
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
                    نمایش <span class="fa-digits">{{ number_format($this->tags->firstItem() ?? 0) }}</span> تا
                    <span class="fa-digits">{{ number_format($this->tags->lastItem() ?? 0) }}</span> از
                    <span class="fa-digits">{{ number_format($this->tags->total()) }}</span> تگ
                </p>
                <nav class="flex items-center gap-1" aria-label="صفحه‌بندی">
                    @if ($this->tags->onFirstPage())
                        <span class="rounded-lg p-2 text-ink-3 opacity-40"><x-admin-panel::icon name="chevron-right" class="size-4" /></span>
                    @else
                        <button wire:click="previousPage" class="rounded-lg p-2 text-ink-2 transition-colors hover:bg-surface-3 dark:hover:bg-night-3" aria-label="صفحه قبل">
                            <x-admin-panel::icon name="chevron-right" class="size-4" />
                        </button>
                    @endif

                    @foreach ($this->tags->onEachSide(1)->toArray()['links'] as $link)
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

                    @if ($this->tags->hasMorePages())
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

    {{-- مودال ایجاد تگ (پورت createTagModal) --}}
    <div x-data="{ open: @entangle('showCreateModal').live }"
         x-show="open" x-cloak
         class="fixed inset-0 z-[60] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-950/50 backdrop-blur-sm" x-show="open" x-on:click="open=false" x-transition.opacity aria-hidden="true"></div>
        <div x-show="open" x-on:click.stop
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
             class="relative w-full max-w-md rounded-2xl border border-line bg-surface p-6 shadow-pop dark:border-night-line dark:bg-night-2"
             role="dialog" aria-modal="true">
            <h3 class="text-base font-bold">ایجاد تگ جدید</h3>
            <form wire:submit="store" class="mt-4 space-y-4">
                <div>
                    <label class="mb-1.5 block text-xs font-semibold" for="tag-name">عنوان تگ</label>
                    <input id="tag-name" type="text" wire:model="name" class="ap-input" placeholder="مثال: پرفروش">
                    @error('name') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                    <p class="mt-1.5 text-[11px] text-ink-3 dark:text-night-ink-2">اسلاگ به طور خودکار ایجاد می‌شود</p>
                </div>
                <div class="flex gap-2.5">
                    <button type="submit" class="ap-btn-primary flex-1" wire:loading.attr="disabled">ذخیره</button>
                    <button type="button" x-on:click="open=false" class="ap-btn-ghost flex-1">انصراف</button>
                </div>
            </form>
        </div>
    </div>

    {{-- مودال ویرایش تگ (پورت editTagModal) --}}
    <div x-data="{ open: @entangle('editingTag').live }"
         x-show="open" x-cloak
         class="fixed inset-0 z-[60] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-950/50 backdrop-blur-sm" x-show="open" x-on:click="open=null" x-transition.opacity aria-hidden="true"></div>
        <div x-show="open" x-on:click.stop
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
             class="relative w-full max-w-md rounded-2xl border border-line bg-surface p-6 shadow-pop dark:border-night-line dark:bg-night-2"
             role="dialog" aria-modal="true">
            <h3 class="text-base font-bold">ویرایش تگ</h3>
            <form wire:submit="update" class="mt-4 space-y-4">
                <div>
                    <label class="mb-1.5 block text-xs font-semibold" for="tag-edit-name">عنوان تگ</label>
                    <input id="tag-edit-name" type="text" wire:model="editingName" class="ap-input">
                    @error('editingName') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>
                <div class="flex gap-2.5">
                    <button type="submit" class="ap-btn-primary flex-1" wire:loading.attr="disabled">به‌روزرسانی</button>
                    <button type="button" x-on:click="open=null" class="ap-btn-ghost flex-1">انصراف</button>
                </div>
            </form>
        </div>
    </div>

    {{-- مودال جزئیات تگ (پورت detailsModal/tag-details) --}}
    <div x-data="{ open: @entangle('showingDetails').live }"
         x-show="open" x-cloak
         class="fixed inset-0 z-[60] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-950/50 backdrop-blur-sm" x-show="open" x-on:click="open=false" x-transition.opacity aria-hidden="true"></div>
        <div x-show="open" x-on:click.stop
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
             class="relative max-h-[85vh] w-full max-w-2xl overflow-y-auto rounded-2xl border border-line bg-surface p-6 shadow-pop dark:border-night-line dark:bg-night-2"
             role="dialog" aria-modal="true">
            <div class="flex items-center justify-between">
                <h3 class="text-base font-bold">جزئیات تگ</h3>
                <button type="button" x-on:click="open=false"
                        class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-surface-2 dark:hover:bg-night-3" aria-label="بستن">
                    <x-admin-panel::icon name="x" class="size-4" />
                </button>
            </div>

            <div class="mt-4 grid grid-cols-1 gap-3 rounded-2xl border border-line p-4 dark:border-night-line sm:grid-cols-2">
                <p class="text-[13px]"><span class="font-bold">عنوان تگ:</span> <span class="ap-badge bg-brand-50 text-brand-700 dark:bg-brand-500/12 dark:text-brand-400">{{ $detailTag['name'] ?? '—' }}</span></p>
                <p class="text-[13px]"><span class="font-bold">اسلاگ:</span> <code class="text-[11px]">{{ $detailTag['slug'] ?? '—' }}</code></p>
                <p class="text-[13px]"><span class="font-bold">تعداد کل استفاده:</span> <span class="fa-digits ap-badge bg-sky-50 text-sky-700 dark:bg-sky-500/12 dark:text-sky-400">{{ number_format($detailTotalUsage) }} بار</span></p>
                <p class="text-[13px]"><span class="font-bold">بازدید کل:</span> <span class="fa-digits ap-badge bg-emerald-50 text-emerald-700 dark:bg-emerald-500/12 dark:text-emerald-400">{{ number_format($detailTag['view_count'] ?? 0) }} بار</span></p>
                <p class="fa-digits text-[13px]"><span class="font-bold">تاریخ ایجاد:</span> {{ $detailTag['created_at'] ?? '—' }}</p>
            </div>

            {{-- آمار استفاده در بخش‌های مختلف --}}
            <div class="mt-4">
                <p class="mb-2 text-sm font-bold">آمار استفاده در بخش‌های مختلف</p>
                @forelse ($detailUsage as $detail)
                    <div class="mb-2 flex items-center justify-between rounded-xl border border-line px-4 py-2.5 dark:border-night-line">
                        <span class="flex items-center gap-2 text-[13px] font-semibold">
                            <x-admin-panel::icon name="{{ $detail['model'] === 'Post' ? 'file-text' : ($detail['model'] === 'Product' ? 'box' : 'tags') }}" class="size-4 text-brand-600 dark:text-brand-400" />
                            {{ \App\Livewire\Admin\Taxonomy\TagIndex::TYPE_LABELS[$detail['model']] ?? $detail['model'] }}
                        </span>
                        <span class="fa-digits ap-badge bg-sky-50 text-sky-700 dark:bg-sky-500/12 dark:text-sky-400">{{ number_format($detail['count']) }} بار استفاده</span>
                    </div>
                @empty
                    <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-center text-[13px] font-medium text-amber-700 dark:border-amber-500/25 dark:bg-amber-500/10 dark:text-amber-400">
                        این تگ هنوز در هیچ بخشی استفاده نشده است
                    </div>
                @endforelse
            </div>

            {{-- لیست موارد استفاده --}}
            @if (count($detailItems))
                <div class="mt-4">
                    <p class="mb-2 text-sm font-bold">لیست موارد استفاده</p>
                    @foreach ($detailItems as $group)
                        <p class="mb-1.5 mt-3 text-[13px] font-bold text-ink-2 dark:text-night-ink-2">{{ $group['type_fa'] }}:</p>
                        <ul class="divide-y divide-line/60 rounded-xl border border-line dark:divide-night-line/60 dark:border-night-line">
                            @foreach ($group['items'] as $item)
                                <li class="flex items-center justify-between px-4 py-2 text-[13px]">
                                    <span class="font-semibold">{{ $item['title'] ?: '—' }}</span>
                                    <span class="fa-digits text-[11px] text-ink-3 dark:text-night-ink-2">شناسه: {{ number_format($item['id']) }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    {{-- مودال تایید حذف تکی --}}
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
            <h3 class="text-base font-bold">حذف تگ</h3>
            <p class="mt-1.5 text-[13px] leading-6 text-ink-2 dark:text-night-ink-2">
                با حذف این تگ، اطلاعات آن برای همیشه پاک خواهد شد.
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

    {{-- مودال تایید حذف گروهی --}}
    <div x-data="{ open: @entangle('confirmingBulkDelete').live }"
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
            <h3 class="text-base font-bold">حذف گروهی تگ‌ها</h3>
            <p class="mt-1.5 text-[13px] leading-6 text-ink-2 dark:text-night-ink-2">
                <span class="fa-digits font-bold" x-text="ApFormat.faDigits($wire.selected.length)"></span> تگ انتخاب شده است. با حذف تگ‌ها، اطلاعات آنها برای همیشه پاک خواهد شد.
            </p>
            <div class="mt-5 flex gap-2.5">
                <button type="button" wire:click="bulkDelete" wire:loading.attr="disabled"
                        class="ap-btn flex-1 bg-red-600 text-white hover:bg-red-700">
                    بله حذف شود
                </button>
                <button type="button" x-on:click="open=false" class="ap-btn-ghost flex-1">انصراف</button>
            </div>
        </div>
    </div>

    {{-- لودینگ --}}
    <div wire:loading wire:target="search, store, update, delete, bulkDelete, openDetails, export, previousPage, nextPage, gotoPage"
         class="pointer-events-none fixed bottom-6 start-6 z-[65] flex items-center gap-2 rounded-xl border border-line bg-surface px-4 py-2.5 shadow-pop dark:border-night-line dark:bg-night-3">
        <svg class="size-4 animate-spin text-brand-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
            <path d="M21 12a9 9 0 1 1-6.219-8.56" stroke-linecap="round" />
        </svg>
        <span class="text-[13px] font-semibold">در حال بارگذاری…</span>
    </div>

    {{-- شنونده open-url برای خروجی CSV --}}
    <div x-data x-on:open-url.window="window.open($event.detail.url, '_blank')" class="hidden"></div>
</div>
