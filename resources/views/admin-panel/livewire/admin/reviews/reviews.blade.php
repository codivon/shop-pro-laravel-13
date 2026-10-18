{{-- مدیریت نظرات محصولات — پورت back/reviews/{index,show}.blade.php — پنل جدید (فاز ۶) --}}
<div class="mx-auto max-w-7xl space-y-5">

    {{-- سربرگ --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap items-center gap-2">
            <span class="ap-badge bg-brand-50 text-brand-700 dark:bg-brand-500/12 dark:text-brand-400">
                کل نظرات: <span class="fa-digits">{{ number_format($this->reviews->total()) }}</span>
            </span>
            @if ($this->pendingCount > 0)
                <span class="ap-badge bg-amber-50 text-amber-700 dark:bg-amber-500/12 dark:text-amber-400">
                    در انتظار تایید: <span class="fa-digits">{{ number_format($this->pendingCount) }}</span>
                </span>
            @endif
        </div>
    </div>

    {{-- کارت فیلترها (پورت فیلتر index قدیمی) --}}
    <div class="ap-card p-4 sm:p-5" x-data="{ open: false }">
        <button type="button" class="flex w-full items-center justify-between text-start" x-on:click="open = !open">
            <span class="flex items-center gap-2 text-sm font-bold">
                <x-admin-panel::icon name="filter" class="size-4.5 text-brand-600 dark:text-brand-400" />
                فیلتر نظرات
                @if ($status || $ordering !== 'latest')
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
                    <label class="mb-1.5 block text-xs font-semibold" for="reviews-filter-status">وضعیت</label>
                    <select id="reviews-filter-status" wire:model.live="status" class="ap-input !py-2">
                        <option value="">همه</option>
                        <option value="pending">منتظر تایید</option>
                        <option value="accepted">تایید شده</option>
                        <option value="rejected">تایید نشده</option>
                    </select>
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold" for="reviews-filter-ordering">مرتب‌سازی</label>
                    <select id="reviews-filter-ordering" wire:model.live="ordering" class="ap-input !py-2">
                        <option value="latest">جدیدترین</option>
                        <option value="oldest">قدیمی‌ترین</option>
                    </select>
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
                        <th class="px-3 py-3 text-start font-bold">محصول</th>
                        <th class="px-3 py-3 text-start font-bold">کاربر</th>
                        <th class="w-32 px-3 py-3 text-start font-bold">امتیاز</th>
                        <th class="w-28 px-3 py-3 text-center font-bold">وضعیت</th>
                        <th class="w-32 px-3 py-3 text-center font-bold">توصیه به خرید</th>
                        <th class="w-28 px-3 py-3 text-start font-bold">تاریخ</th>
                        <th class="w-20 px-3 py-3 text-start font-bold">عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->reviews as $review)
                        <tr wire:key="review-{{ $review->id }}"
                            class="border-b border-line/60 transition-colors last:border-0 hover:bg-surface-2 dark:border-night-line/60 dark:hover:bg-night-3/60">
                            <td class="px-3 py-3 fa-digits font-bold">{{ number_format($review->id) }}</td>
                            <td class="max-w-44 px-3 py-3">
                                <span class="flex items-center gap-1">
                                    <span class="truncate font-semibold">{{ $review->product->title ?? "محصول #{$review->product_id}" }}</span>
                                    @if ($review->product && Route::has('front.products.show'))
                                        <a href="{{ route('front.products.show', ['product' => $review->product]) }}" target="_blank" rel="noopener"
                                           class="shrink-0 text-brand-600 transition-colors hover:text-brand-500 dark:text-brand-400" title="مشاهده در فروشگاه">
                                            <x-admin-panel::icon name="external-link" class="size-3.5" />
                                        </a>
                                    @endif
                                </span>
                            </td>
                            <td class="max-w-36 px-3 py-3">
                                @if ($review->user)
                                    <span class="flex items-center gap-1 font-semibold">
                                        <x-admin-panel::icon name="user" class="size-4 shrink-0 text-brand-600 dark:text-brand-400" />
                                        <span class="truncate">{{ $review->user->fullname ?? $review->user->name }}</span>
                                    </span>
                                @else
                                    <span class="ap-badge bg-red-50 text-red-600 dark:bg-red-500/12 dark:text-red-400">حذف شده</span>
                                @endif
                            </td>
                            <td class="px-3 py-3">
                                <span class="flex items-center gap-0.5" dir="ltr" title="{{ $review->rating }} از 5">
                                    @for ($i = 1; $i <= 5; $i++)
                                        <x-admin-panel::icon name="star"
                                                             class="size-3.5 {{ $i <= $review->rating ? 'fill-amber-400 text-amber-400' : 'text-ink-3/40' }}" />
                                    @endfor
                                </span>
                            </td>
                            <td class="px-3 py-3 text-center">
                                <span class="ap-badge {{ $this->statusBadgeClass($review->status) }}">{{ $this->statusBadgeLabel($review->status) }}</span>
                            </td>
                            <td class="px-3 py-3 text-center text-xs font-semibold">{{ $this->suggestLabel($review->suggest) }}</td>
                            <td class="px-3 py-3 fa-digits whitespace-nowrap">{{ $this->jdate($review->created_at) }}</td>
                            <td class="px-3 py-3">
                                <div class="flex items-center gap-0.5">
                                    <button type="button" wire:click="openDetail({{ $review->id }})"
                                            class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-brand-50 hover:text-brand-600 dark:hover:bg-brand-500/10"
                                            title="مشاهده نظر" aria-label="مشاهده">
                                        <x-admin-panel::icon name="eye" class="size-4" />
                                    </button>
                                    <button type="button" wire:click="openEdit({{ $review->id }})"
                                            class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-brand-50 hover:text-brand-600 dark:hover:bg-brand-500/10"
                                            title="ویرایش نظر" aria-label="ویرایش">
                                        <x-admin-panel::icon name="pencil" class="size-4" />
                                    </button>
                                    <button type="button" wire:click="confirmDelete({{ $review->id }})"
                                            class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-500/10"
                                            title="حذف نظر" aria-label="حذف">
                                        <x-admin-panel::icon name="trash-2" class="size-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8">
                                <div class="flex flex-col items-center gap-2 py-14 text-ink-3 dark:text-night-ink-2">
                                    <x-admin-panel::icon name="star" class="size-10 opacity-40" />
                                    <p class="text-sm font-medium">نظری برای نمایش وجود ندارد</p>
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
                    نمایش <span class="fa-digits">{{ number_format($this->reviews->firstItem() ?? 0) }}</span> تا
                    <span class="fa-digits">{{ number_format($this->reviews->lastItem() ?? 0) }}</span> از
                    <span class="fa-digits">{{ number_format($this->reviews->total()) }}</span> نظر
                </p>
                <nav class="flex items-center gap-1" aria-label="صفحه‌بندی">
                    @if ($this->reviews->onFirstPage())
                        <span class="rounded-lg p-2 text-ink-3 opacity-40"><x-admin-panel::icon name="chevron-right" class="size-4" /></span>
                    @else
                        <button wire:click="previousPage" class="rounded-lg p-2 text-ink-2 transition-colors hover:bg-surface-3 dark:hover:bg-night-3" aria-label="صفحه قبل">
                            <x-admin-panel::icon name="chevron-right" class="size-4" />
                        </button>
                    @endif

                    @foreach ($this->reviews->onEachSide(1)->toArray()['links'] as $link)
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

                    @if ($this->reviews->hasMorePages())
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

    {{-- مودال جزئیات — پورت back/reviews/show.blade.php --}}
    <div x-data="{ open: @entangle('showDetail').live }"
         x-show="open" x-cloak
         class="fixed inset-0 z-[60] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-950/50 backdrop-blur-sm" x-show="open" x-on:click="open=false" x-transition.opacity aria-hidden="true"></div>
        <div x-show="open" x-on:click.stop
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
             class="relative max-h-[85vh] w-full max-w-xl overflow-y-auto rounded-2xl border border-line bg-surface p-6 shadow-pop dark:border-night-line dark:bg-night-2"
             role="dialog" aria-modal="true">
            <div class="mb-4 flex items-center justify-between">
                <h3 class="flex items-center gap-2 text-base font-bold">
                    <x-admin-panel::icon name="star" class="size-5 text-brand-600 dark:text-brand-400" />
                    مشاهده نظر
                </h3>
                <button type="button" x-on:click="open=false"
                        class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-surface-2 dark:hover:bg-night-3" aria-label="بستن">
                    <x-admin-panel::icon name="x" class="size-4" />
                </button>
            </div>

            @if ($this->detailReview)
                @php $review = $this->detailReview; @endphp

                <div class="text-[13px]">
                    {{-- کاربر --}}
                    <div class="flex items-start justify-between gap-4 border-b border-line/60 py-2.5 dark:border-night-line/60">
                        <span class="shrink-0 text-xs font-bold text-ink-3 dark:text-night-ink-2">کاربر</span>
                        <span class="text-end font-semibold">
                            @if ($review->user)
                                {{ $review->user->fullname ?? $review->user->name }}
                                @if (Route::has('np.users.show'))
                                    <a href="{{ route('np.users.show', ['user' => $review->user]) }}" wire:navigate
                                       class="ms-1 inline-flex items-center align-middle text-brand-600 transition-colors hover:text-brand-500 dark:text-brand-400" title="نمایش کاربر">
                                        <x-admin-panel::icon name="external-link" class="size-3.5" />
                                    </a>
                                @endif
                            @else
                                <span class="ap-badge bg-red-50 text-red-600 dark:bg-red-500/12 dark:text-red-400">حذف شده</span>
                            @endif
                        </span>
                    </div>

                    {{-- تاریخ ارسال --}}
                    <div class="flex items-start justify-between gap-4 border-b border-line/60 py-2.5 dark:border-night-line/60">
                        <span class="shrink-0 text-xs font-bold text-ink-3 dark:text-night-ink-2">تاریخ ارسال</span>
                        <span class="fa-digits text-end font-semibold">{{ $this->jdate($review->created_at, 'Y/m/d | H:i') }}</span>
                    </div>

                    {{-- محصول --}}
                    <div class="flex items-start justify-between gap-4 border-b border-line/60 py-2.5 dark:border-night-line/60">
                        <span class="shrink-0 text-xs font-bold text-ink-3 dark:text-night-ink-2">محصول</span>
                        <span class="text-end font-semibold">
                            {{ $review->product->title ?? "#{$review->product_id}" }}
                            @if ($review->product && Route::has('front.products.show'))
                                <a href="{{ route('front.products.show', ['product' => $review->product]) }}" target="_blank" rel="noopener"
                                   class="ms-1 inline-flex items-center align-middle text-brand-600 transition-colors hover:text-brand-500 dark:text-brand-400" title="مشاهده در فروشگاه">
                                    <x-admin-panel::icon name="external-link" class="size-3.5" />
                                </a>
                            @endif
                        </span>
                    </div>

                    {{-- عنوان --}}
                    <div class="flex items-start justify-between gap-4 border-b border-line/60 py-2.5 dark:border-night-line/60">
                        <span class="shrink-0 text-xs font-bold text-ink-3 dark:text-night-ink-2">عنوان</span>
                        <span class="text-end font-semibold">{{ $review->title }}</span>
                    </div>

                    {{-- امتیاز --}}
                    <div class="flex items-start justify-between gap-4 border-b border-line/60 py-2.5 dark:border-night-line/60">
                        <span class="shrink-0 text-xs font-bold text-ink-3 dark:text-night-ink-2">امتیاز</span>
                        <span class="flex items-center gap-0.5" dir="ltr">
                            @for ($i = 1; $i <= 5; $i++)
                                <x-admin-panel::icon name="star"
                                                     class="size-4 {{ $i <= $review->rating ? 'fill-amber-400 text-amber-400' : 'text-ink-3/40' }}" />
                            @endfor
                        </span>
                    </div>

                    {{-- وضعیت --}}
                    <div class="flex items-start justify-between gap-4 border-b border-line/60 py-2.5 dark:border-night-line/60">
                        <span class="shrink-0 text-xs font-bold text-ink-3 dark:text-night-ink-2">وضعیت</span>
                        <span class="ap-badge {{ $this->statusBadgeClass($review->status) }}">{{ $this->statusBadgeLabel($review->status) }}</span>
                    </div>

                    {{-- توصیه به خرید --}}
                    <div class="flex items-start justify-between gap-4 border-b border-line/60 py-2.5 dark:border-night-line/60">
                        <span class="shrink-0 text-xs font-bold text-ink-3 dark:text-night-ink-2">توصیه به خرید</span>
                        <span class="text-end font-semibold">{{ $this->suggestLabel($review->suggest) }}</span>
                    </div>

                    {{-- متن دیدگاه --}}
                    <div class="border-b border-line/60 py-2.5 dark:border-night-line/60">
                        <span class="text-xs font-bold text-ink-3 dark:text-night-ink-2">متن دیدگاه</span>
                        <p class="mt-1.5 leading-6 text-ink-2 dark:text-night-ink-2">{{ $review->body }}</p>
                    </div>

                    {{-- نقاط قوت / ضعف (ReviewPoint با type positive/negative) --}}
                    <div class="grid grid-cols-1 gap-3 py-2.5 sm:grid-cols-2">
                        <div>
                            <span class="text-xs font-bold text-emerald-600 dark:text-emerald-400">نقاط قوت</span>
                            @if ($review->points->where('type', 'positive')->count())
                                <ul class="mt-1.5 space-y-1">
                                    @foreach ($review->points->where('type', 'positive') as $point)
                                        <li wire:key="point-pos-{{ $point->id }}" class="flex items-center gap-1.5 text-ink-2 dark:text-night-ink-2">
                                            <x-admin-panel::icon name="plus" class="size-3.5 shrink-0 text-emerald-500" />
                                            {{ $point->text }}
                                        </li>
                                    @endforeach
                                </ul>
                            @else
                                <p class="mt-1.5 text-xs text-ink-3 dark:text-night-ink-2">چیزی ثبت نشده است</p>
                            @endif
                        </div>
                        <div>
                            <span class="text-xs font-bold text-red-500 dark:text-red-400">نقاط ضعف</span>
                            @if ($review->points->where('type', 'negative')->count())
                                <ul class="mt-1.5 space-y-1">
                                    @foreach ($review->points->where('type', 'negative') as $point)
                                        <li wire:key="point-neg-{{ $point->id }}" class="flex items-center gap-1.5 text-ink-2 dark:text-night-ink-2">
                                            <x-admin-panel::icon name="x" class="size-3.5 shrink-0 text-red-500" />
                                            {{ $point->text }}
                                        </li>
                                    @endforeach
                                </ul>
                            @else
                                <p class="mt-1.5 text-xs text-ink-3 dark:text-night-ink-2">چیزی ثبت نشده است</p>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="mt-4 flex gap-2.5">
                    <button type="button" wire:click="openEdit({{ $review->id }})" class="ap-btn flex-1">
                        <x-admin-panel::icon name="pencil" class="size-4" />
                        ویرایش نظر
                    </button>
                    <button type="button" x-on:click="open=false" class="ap-btn-ghost flex-1">بستن</button>
                </div>
            @else
                <div class="py-10 text-center text-sm text-ink-3 dark:text-night-ink-2">نظری یافت نشد</div>
            @endif
        </div>
    </div>

    {{-- مودال ویرایش — پورت update کامل (title/body/rating/suggest/status + نقاط قوت/ضعف) --}}
    <div x-data="{ open: @entangle('showEdit').live }"
         x-show="open" x-cloak
         class="fixed inset-0 z-[60] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-950/50 backdrop-blur-sm" x-show="open" x-on:click="open=false" x-transition.opacity aria-hidden="true"></div>
        <div x-show="open" x-on:click.stop
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
             class="relative max-h-[85vh] w-full max-w-2xl overflow-y-auto rounded-2xl border border-line bg-surface p-6 shadow-pop dark:border-night-line dark:bg-night-2"
             role="dialog" aria-modal="true">
            <div class="mb-4 flex items-center justify-between">
                <h3 class="flex items-center gap-2 text-base font-bold">
                    <x-admin-panel::icon name="pencil" class="size-5 text-brand-600 dark:text-brand-400" />
                    ویرایش نظر
                </h3>
                <button type="button" x-on:click="open=false; $wire.closeEdit()"
                        class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-surface-2 dark:hover:bg-night-3" aria-label="بستن">
                    <x-admin-panel::icon name="x" class="size-4" />
                </button>
            </div>

            @if ($editingReview)
                <form wire:submit="updateReview" class="space-y-4 text-[13px]">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="mb-1.5 block text-xs font-semibold" for="review-edit-title">عنوان <span class="text-red-500">*</span></label>
                            <input id="review-edit-title" type="text" wire:model="editTitle" class="ap-input !py-2">
                            @error('editTitle') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-semibold" for="review-edit-rating">امتیاز <span class="text-red-500">*</span></label>
                            <select id="review-edit-rating" wire:model="editRating" class="ap-input !py-2">
                                <option value="1">خیلی بد (1)</option>
                                <option value="2">بد (2)</option>
                                <option value="3">معمولی (3)</option>
                                <option value="4">خوب (4)</option>
                                <option value="5">خیلی خوب (5)</option>
                            </select>
                            @error('editRating') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-semibold" for="review-edit-status">وضعیت</label>
                            <select id="review-edit-status" wire:model="editStatus" class="ap-input !py-2">
                                <option value="pending">منتظر تایید</option>
                                <option value="accepted">تایید شده</option>
                                <option value="rejected">تایید نشده</option>
                            </select>
                            @error('editStatus') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-semibold" for="review-edit-suggest">پیشنهاد</label>
                            <select id="review-edit-suggest" wire:model="editSuggest" class="ap-input !py-2">
                                <option value="">—</option>
                                <option value="yes">پیشنهاد می‌کنم</option>
                                <option value="not_sure">مطمئن نیستم</option>
                                <option value="no">پیشنهاد نمی‌کنم</option>
                            </select>
                            @error('editSuggest') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div>
                        <label class="mb-1.5 block text-xs font-semibold" for="review-edit-body">متن دیدگاه <span class="text-red-500">*</span></label>
                        <textarea id="review-edit-body" wire:model="editBody" rows="4" class="ap-input !py-2" placeholder="متن نظر…"></textarea>
                        @error('editBody') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        {{-- چیپ‌پیکر نقاط قوت --}}
                        <div>
                            <label class="mb-1.5 block text-xs font-semibold">نقاط قوت</label>
                            <div class="flex flex-wrap items-center gap-1.5 rounded-xl border border-line bg-surface p-2.5 dark:border-night-line dark:bg-night-3">
                                @foreach ($advantages as $index => $advantage)
                                    <span wire:key="advantage-chip-{{ $index }}"
                                          class="inline-flex items-center gap-1 rounded-lg bg-emerald-50 py-1 pe-1.5 ps-2.5 text-xs font-semibold text-emerald-700 dark:bg-emerald-500/12 dark:text-emerald-400">
                                        {{ $advantage }}
                                        <button type="button" wire:click="removeAdvantage({{ $index }})"
                                                class="rounded-md p-0.5 transition-colors hover:bg-red-100 hover:text-red-600 dark:hover:bg-red-500/15"
                                                aria-label="حذف {{ $advantage }}">
                                            <x-admin-panel::icon name="x" class="size-3.5" />
                                        </button>
                                    </span>
                                @endforeach
                                <input type="text" wire:model="advantageDraft" wire:keydown.enter.prevent="addAdvantage"
                                       placeholder="مزیت را بنویسید و Enter بزنید…" class="min-w-36 flex-1 bg-transparent text-[13px] outline-none placeholder:text-ink-3">
                            </div>
                            <button type="button" wire:click="addAdvantage" class="ap-btn-outline mt-2 !px-3 !py-1.5 text-xs">
                                <x-admin-panel::icon name="plus" class="size-3.5" />
                                افزودن نقطه قوت
                            </button>
                            @error('advantages.*') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                        </div>

                        {{-- چیپ‌پیکر نقاط ضعف --}}
                        <div>
                            <label class="mb-1.5 block text-xs font-semibold">نقاط ضعف</label>
                            <div class="flex flex-wrap items-center gap-1.5 rounded-xl border border-line bg-surface p-2.5 dark:border-night-line dark:bg-night-3">
                                @foreach ($disadvantages as $index => $disadvantage)
                                    <span wire:key="disadvantage-chip-{{ $index }}"
                                          class="inline-flex items-center gap-1 rounded-lg bg-red-50 py-1 pe-1.5 ps-2.5 text-xs font-semibold text-red-600 dark:bg-red-500/12 dark:text-red-400">
                                        {{ $disadvantage }}
                                        <button type="button" wire:click="removeDisadvantage({{ $index }})"
                                                class="rounded-md p-0.5 transition-colors hover:bg-red-100 hover:text-red-600 dark:hover:bg-red-500/15"
                                                aria-label="حذف {{ $disadvantage }}">
                                            <x-admin-panel::icon name="x" class="size-3.5" />
                                        </button>
                                    </span>
                                @endforeach
                                <input type="text" wire:model="disadvantageDraft" wire:keydown.enter.prevent="addDisadvantage"
                                       placeholder="نقص را بنویسید و Enter بزنید…" class="min-w-36 flex-1 bg-transparent text-[13px] outline-none placeholder:text-ink-3">
                            </div>
                            <button type="button" wire:click="addDisadvantage" class="ap-btn-outline mt-2 !px-3 !py-1.5 text-xs">
                                <x-admin-panel::icon name="plus" class="size-3.5" />
                                افزودن نقطه ضعف
                            </button>
                            @error('disadvantages.*') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="flex gap-2.5 border-t border-line pt-4 dark:border-night-line">
                        <button type="submit" wire:loading.attr="disabled" wire:target="updateReview" class="ap-btn flex-1">
                            <x-admin-panel::icon name="check" class="size-4" />
                            ذخیره تغییرات
                        </button>
                        <button type="button" x-on:click="open=false; $wire.closeEdit()" class="ap-btn-ghost flex-1">انصراف</button>
                    </div>
                </form>
            @else
                <div class="py-10 text-center text-sm text-ink-3 dark:text-night-ink-2">نظری یافت نشد</div>
            @endif
        </div>
    </div>

    {{-- مودال تایید حذف --}}
    <div x-data="{ open: @entangle('confirmingDelete').live }"
         x-show="open" x-cloak
         class="fixed inset-0 z-[70] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-950/50 backdrop-blur-sm" x-show="open" x-on:click="open=false" x-transition.opacity aria-hidden="true"></div>
        <div x-show="open" x-on:click.stop
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
             class="relative w-full max-w-sm rounded-2xl border border-line bg-surface p-6 shadow-pop dark:border-night-line dark:bg-night-2"
             role="dialog" aria-modal="true">
            <div class="mb-3 flex size-12 items-center justify-center rounded-2xl bg-red-50 text-red-600 dark:bg-red-500/10 dark:text-red-400">
                <x-admin-panel::icon name="trash-2" class="size-6" />
            </div>
            <h3 class="text-base font-bold">حذف نظر</h3>
            <p class="mt-1.5 text-[13px] leading-6 text-ink-2 dark:text-night-ink-2">
                با حذف نظر دیگر قادر به بازیابی آن نخواهید بود؛ امتیاز کلی محصول (rating) مجدداً محاسبه می‌شود.
            </p>
            <div class="mt-5 flex gap-2.5">
                <button type="button" wire:click="delete" wire:loading.attr="disabled" wire:target="delete"
                        class="ap-btn flex-1 bg-red-600 text-white hover:bg-red-700">
                    بله حذف شود
                </button>
                <button type="button" x-on:click="open=false" class="ap-btn-ghost flex-1">انصراف</button>
            </div>
        </div>
    </div>

    {{-- لودینگ --}}
    <div wire:loading wire:target="status, ordering, previousPage, nextPage, gotoPage, updateReview, delete, applyFilters, addAdvantage, addDisadvantage, removeAdvantage, removeDisadvantage"
         class="pointer-events-none fixed bottom-6 start-6 z-[65] flex items-center gap-2 rounded-xl border border-line bg-surface px-4 py-2.5 shadow-pop dark:border-night-line dark:bg-night-3">
        <svg class="size-4 animate-spin text-brand-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
            <path d="M21 12a9 9 0 1 1-6.219-8.56" stroke-linecap="round" />
        </svg>
        <span class="text-[13px] font-semibold">در حال بارگذاری…</span>
    </div>
</div>
