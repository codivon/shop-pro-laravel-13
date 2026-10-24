{{-- لیست نوشته‌های وبلاگ — پنل جدید (فاز ۶) | پورت back/posts/index --}}
<div class="mx-auto max-w-7xl space-y-5">

    {{-- سربرگ --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="flex items-center gap-2.5 text-lg font-black">
                <span class="flex size-10 shrink-0 items-center justify-center rounded-2xl bg-linear-to-br from-brand-500 to-brand-700 text-white shadow-md shadow-brand-500/25">
                    <x-admin-panel::icon name="notebook-pen" class="size-5.5" />
                </span>
                مدیریت وبلاگ</h1>
            <p class="fa-digits mt-0.5 text-xs text-ink-3 dark:text-night-ink-2">
                <span class="fa-digits">{{ number_format($this->totalCount) }}</span> نوشته در زبان فعلی
            </p>
        </div>
        @can('posts.create')
            <a href="{{ route('np.posts.create') }}" wire:navigate class="ap-btn-primary">
                <x-admin-panel::icon name="plus" class="size-4" />
                نوشته جدید
            </a>
        @endcan
    </div>

    {{-- کارت فیلترها --}}
    <div class="ap-card p-4 sm:p-5" x-data="{ open: true }">
        <button type="button" class="flex w-full items-center justify-between text-start" x-on:click="open = !open">
            <span class="flex items-center gap-2 text-sm font-bold">
                <x-admin-panel::icon name="filter" class="size-4.5 text-brand-600 dark:text-brand-400" />
                فیلتر نوشته‌ها
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
                <div class="lg:col-span-2">
                    <label class="mb-1.5 block text-xs font-semibold">جستجو</label>
                    <input type="text" wire:model.live.debounce.400ms="search" class="ap-input !py-2" placeholder="عنوان یا اسلاگ…">
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

    {{-- نوار عملیات گروهی --}}
    <div x-show="$wire.selected.length > 0" x-cloak
         class="sticky top-20 z-20 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-brand-200 bg-brand-50/95 px-4 py-2.5 shadow-card backdrop-blur dark:border-brand-500/25 dark:bg-brand-950/80">
        <span class="text-[13px] font-bold text-brand-800 dark:text-brand-300">
            <span class="fa-digits" x-text="ApFormat.faDigits($wire.selected.length)"></span> نوشته انتخاب شده
        </span>
        @can('posts.delete')
            <button type="button" wire:click="$set('confirmingBulkDelete', true)"
                    class="ap-btn !px-3 !py-1.5 text-[13px] bg-red-600 text-white hover:bg-red-700">
                <x-admin-panel::icon name="trash-2" class="size-4" />
                حذف همه
            </button>
        @endcan
    </div>

    {{-- جدول --}}
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
                        <th class="w-16 px-3 py-3 text-start font-bold">تصویر</th>
                        <th class="px-3 py-3 text-start font-bold">عنوان</th>
                        <th class="px-3 py-3 text-start font-bold">اسلاگ</th>
                        <th class="px-3 py-3 text-start font-bold">نوع</th>
                        <th class="px-3 py-3 text-start font-bold">دسته‌بندی‌ها</th>
                        <th class="px-3 py-3 text-start font-bold">وضعیت</th>
                        <th class="w-14 px-3 py-3 text-center font-bold" title="انتخاب سردبیر">
                            <x-admin-panel::icon name="star" class="mx-auto size-4 text-amber-500" />
                        </th>
                        <th class="px-3 py-3 text-start font-bold">تاریخ ایجاد</th>
                        <th class="px-3 py-3 text-start font-bold">دیدگاه‌ها</th>
                        <th class="w-24 px-3 py-3 text-start font-bold">عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->posts as $post)
                        <tr wire:key="post-{{ $post->id }}"
                            class="border-b border-line/60 transition-colors last:border-0 hover:bg-surface-2 dark:border-night-line/60 dark:hover:bg-night-3/60">
                            <td class="px-3 py-3">
                                <input type="checkbox" wire:model.live="selected" value="{{ $post->id }}"
                                       class="size-4 rounded-md border-line text-brand-600 focus:ring-brand-500/30 dark:border-night-line"
                                       aria-label="انتخاب نوشته {{ $post->id }}">
                            </td>
                            <td class="px-3 py-3">
                                <img src="{{ $post->image ? asset($post->image) : asset('/empty.svg') }}"
                                     alt="{{ $post->title }}" loading="lazy"
                                     class="size-10 rounded-xl border border-line object-cover dark:border-night-line">
                            </td>
                            <td class="max-w-56 px-3 py-3">
                                <div class="flex items-center gap-1.5">
                                    <p class="truncate font-semibold">{{ $post->title }}</p>
                                    @if (\Illuminate\Support\Facades\Route::has('front.articles.show'))
                                        <a href="{{ route('front.articles.show', $post) }}" target="_blank"
                                           class="shrink-0 text-ink-3 transition-colors hover:text-brand-600 dark:text-night-ink-2"
                                           title="مشاهده در سایت" aria-label="مشاهده در سایت">
                                            <x-admin-panel::icon name="external-link" class="size-3.5" />
                                        </a>
                                    @endif
                                </div>
                            </td>
                            <td class="max-w-40 px-3 py-3">
                                <code class="truncate text-[11px] text-ink-2 dark:text-night-ink-2" dir="ltr">{{ $post->slug ?? '—' }}</code>
                            </td>
                            <td class="px-3 py-3">
                                @if ($post->post_type === 'video')
                                    <span class="ap-badge bg-violet-50 text-violet-700 dark:bg-violet-500/12 dark:text-violet-400">{{ \App\Livewire\Admin\Posts\PostIndex::typeLabel($post->post_type) }}</span>
                                @elseif ($post->post_type === 'podcast')
                                    <span class="ap-badge bg-amber-50 text-amber-700 dark:bg-amber-500/12 dark:text-amber-400">{{ \App\Livewire\Admin\Posts\PostIndex::typeLabel($post->post_type) }}</span>
                                @else
                                    <span class="ap-badge bg-sky-50 text-sky-700 dark:bg-sky-500/12 dark:text-sky-400">{{ \App\Livewire\Admin\Posts\PostIndex::typeLabel($post->post_type) }}</span>
                                @endif
                            </td>
                            <td class="max-w-48 px-3 py-3">
                                @forelse ($post->Categories->take(2) as $category)
                                    <span class="ap-badge mr-1 bg-surface-3 text-ink-2 dark:bg-night-3 dark:text-night-ink-2">{{ $category->title }}</span>
                                @empty
                                    <span class="text-ink-3 dark:text-night-ink-2">—</span>
                                @endforelse
                                @if ($post->Categories->count() > 2)
                                    <span class="fa-digits mr-1 text-[11px] font-bold text-ink-3 dark:text-night-ink-2" dir="ltr">+{{ $post->Categories->count() - 2 }}</span>
                                @endif
                            </td>
                            <td class="px-3 py-3">
                                @if ($post->published)
                                    <span class="ap-badge bg-emerald-50 text-emerald-700 dark:bg-emerald-500/12 dark:text-emerald-400">منتشر شده</span>
                                @else
                                    <span class="ap-badge bg-surface-3 text-ink-3 dark:bg-night-3 dark:text-night-ink-2">پیش‌نویس</span>
                                @endif
                            </td>
                            <td class="px-3 py-3 text-center">
                                @if ($post->is_editor_pick)
                                    <x-admin-panel::icon name="star" class="mx-auto size-4 text-amber-500" title="منتخب سردبیر" />
                                @else
                                    <span class="text-ink-3 dark:text-night-ink-2">—</span>
                                @endif
                            </td>
                            <td class="px-3 py-3 fa-digits whitespace-nowrap">{{ $this->jdate($post->created_at) }}</td>
                            <td class="px-3 py-3">
                                <span class="fa-digits inline-flex items-center gap-1 text-ink-2 dark:text-night-ink-2">
                                    <x-admin-panel::icon name="message-square" class="size-3.5" />
                                    {{ number_format($post->comments_count) }}
                                </span>
                            </td>
                            <td class="px-3 py-3">
                                <div class="flex items-center gap-0.5">
                                    @can('posts.update')
                                        <a href="{{ route('np.posts.edit', ['post' => $post]) }}" wire:navigate
                                           class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-brand-50 hover:text-brand-600 dark:hover:bg-brand-500/10"
                                           title="ویرایش نوشته" aria-label="ویرایش">
                                            <x-admin-panel::icon name="pencil" class="size-4" />
                                        </a>
                                    @endcan
                                    @can('posts.delete')
                                        <button type="button" wire:click="confirmDelete({{ $post->id }})"
                                                class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-500/10"
                                                title="حذف نوشته" aria-label="حذف">
                                            <x-admin-panel::icon name="trash-2" class="size-4" />
                                        </button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11">
                                <div class="flex flex-col items-center gap-2 py-14 text-ink-3 dark:text-night-ink-2">
                                    <x-admin-panel::icon name="file-text" class="size-10 opacity-40" />
                                    <p class="text-sm font-medium">چیزی برای نمایش وجود ندارد!</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- صفحه‌بندی (دست‌ساز — بدون wire:navigate) --}}
        <div class="border-t border-line px-4 py-3 dark:border-night-line">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <p class="text-xs text-ink-3 dark:text-night-ink-2">
                    نمایش <span class="fa-digits">{{ number_format($this->posts->firstItem() ?? 0) }}</span> تا
                    <span class="fa-digits">{{ number_format($this->posts->lastItem() ?? 0) }}</span> از
                    <span class="fa-digits">{{ number_format($this->posts->total()) }}</span> نوشته
                </p>
                <nav class="flex items-center gap-1" aria-label="صفحه‌بندی">
                    @if ($this->posts->onFirstPage())
                        <span class="rounded-lg p-2 text-ink-3 opacity-40"><x-admin-panel::icon name="chevron-right" class="size-4" /></span>
                    @else
                        <button wire:click="previousPage" class="rounded-lg p-2 text-ink-2 transition-colors hover:bg-surface-3 dark:hover:bg-night-3" aria-label="صفحه قبل">
                            <x-admin-panel::icon name="chevron-right" class="size-4" />
                        </button>
                    @endif

                    @foreach ($this->posts->onEachSide(1)->toArray()['links'] as $link)
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

                    @if ($this->posts->hasMorePages())
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
            <h3 class="text-base font-bold">حذف نوشته</h3>
            <p class="mt-1.5 text-[13px] leading-6 text-ink-2 dark:text-night-ink-2">
                با حذف نوشته دیگر قادر به بازیابی آن نخواهید بود؛ تصویر شاخص حذف و دسته‌بندی‌ها و تگ‌های نوشته جدا می‌شوند.
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
            <h3 class="text-base font-bold">حذف نوشته‌های انتخاب‌شده</h3>
            <p class="mt-1.5 text-[13px] leading-6 text-ink-2 dark:text-night-ink-2">
                <span class="fa-digits font-bold" x-text="ApFormat.faDigits($wire.selected.length)"></span> نوشته انتخاب شده است. با حذف، تصویر شاخص و پیوند دسته‌ها و تگ‌ها هم پاک می‌شود.
            </p>
            <div class="mt-5 flex gap-2.5">
                <button type="button" wire:click="bulkDelete" wire:loading.attr="disabled"
                        class="ap-btn flex-1 bg-red-600 text-white hover:bg-red-700">
                    بله حذف شود
                </button>
                <button type="button" x-on:click="open=false" class="ap-btn-ghost flex-1">خیر</button>
            </div>
        </div>
    </div>

    {{-- لودینگ --}}
    <div wire:loading wire:target="search, previousPage, nextPage, gotoPage, delete, bulkDelete"
         class="pointer-events-none fixed bottom-6 start-6 z-[65] flex items-center gap-2 rounded-xl border border-line bg-surface px-4 py-2.5 shadow-pop dark:border-night-line dark:bg-night-3">
        <svg class="size-4 animate-spin text-brand-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
            <path d="M21 12a9 9 0 1 1-6.219-8.56" stroke-linecap="round" />
        </svg>
        <span class="text-[13px] font-semibold">در حال بارگذاری…</span>
    </div>
</div>
