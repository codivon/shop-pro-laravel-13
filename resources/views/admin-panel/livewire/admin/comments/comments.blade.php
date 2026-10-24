{{-- مدیریت دیدگاه‌ها (پرسش و پاسخ‌ها) — پورت back/comments/{index,show}.blade.php — پنل جدید (فاز ۶) --}}
@php
    $errors = $errors ?? (new \Illuminate\Support\ViewErrorBag())->put('default', new \Illuminate\Support\MessageBag());
@endphp
<div class="mx-auto max-w-7xl space-y-5">

    {{-- سربرگ --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap items-center gap-2">
            <span class="ap-badge bg-brand-50 text-brand-700 dark:bg-brand-500/12 dark:text-brand-400">
                کل دیدگاه‌ها: <span class="fa-digits">{{ number_format($this->comments->total()) }}</span>
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
                فیلتر دیدگاه‌ها
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
                    <label class="mb-1.5 block text-xs font-semibold" for="comments-filter-status">وضعیت</label>
                    <select id="comments-filter-status" wire:model.live="status" class="ap-input !py-2">
                        <option value="">همه</option>
                        <option value="pending">منتظر تایید</option>
                        <option value="accepted">تایید شده</option>
                        <option value="unconfirmed">تایید نشده</option>
                    </select>
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold" for="comments-filter-ordering">مرتب‌سازی</label>
                    <select id="comments-filter-ordering" wire:model.live="ordering" class="ap-input !py-2">
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
                        <th class="px-3 py-3 text-start font-bold">نویسنده</th>
                        <th class="px-3 py-3 text-start font-bold">دیدگاه</th>
                        <th class="px-3 py-3 text-start font-bold">{{ $type === 'post' ? 'نوشته' : 'محصول' }}</th>
                        <th class="w-24 px-3 py-3 text-center font-bold">تعداد پاسخ</th>
                        <th class="w-28 px-3 py-3 text-center font-bold">وضعیت</th>
                        <th class="w-28 px-3 py-3 text-start font-bold">تاریخ</th>
                        <th class="w-20 px-3 py-3 text-start font-bold">عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->comments as $comment)
                        <tr wire:key="comment-{{ $comment->id }}"
                            class="border-b border-line/60 transition-colors last:border-0 hover:bg-surface-2 dark:border-night-line/60 dark:hover:bg-night-3/60">
                            <td class="px-3 py-3 fa-digits font-bold">{{ number_format($comment->id) }}</td>
                            <td class="px-3 py-3">
                                <span class="flex items-center gap-1.5 font-semibold">
                                    @if ($comment->user)
                                        <x-admin-panel::icon name="user" class="size-4 text-brand-600 dark:text-brand-400" />
                                        {{ $comment->user->fullname ?? $comment->user->name ?? $comment->UserName() }}
                                    @elseif ($comment->admin)
                                        <x-admin-panel::icon name="shield" class="size-4 text-violet-600 dark:text-violet-400" />
                                        {{ $comment->admin->fullname ?? '' }}
                                    @elseif ($comment->seller)
                                        <x-admin-panel::icon name="store" class="size-4 text-amber-600 dark:text-amber-400" />
                                        {{ $comment->seller->business_name ?? '' }}
                                    @else
                                        {{ $comment->name ?? 'مهمان' }}
                                    @endif
                                </span>
                            </td>
                            <td class="max-w-48 px-3 py-3">
                                <p class="truncate text-ink-2 dark:text-night-ink-2">{{ short_content($comment->body, 20, false) }}</p>
                            </td>
                            <td class="max-w-40 px-3 py-3">
                                @if ($comment->commentable)
                                    @if ($comment->commentable_type === \App\Models\Product::class && Route::has('np.products.edit'))
                                        <a href="{{ route('np.products.edit', ['product' => $comment->commentable]) }}" wire:navigate
                                           class="flex items-center gap-1 truncate font-semibold text-brand-700 transition-colors hover:text-brand-600 dark:text-brand-400"
                                           title="ویرایش محصول">
                                            <span class="truncate">{{ $comment->commentable->title ?? $comment->commentable->name }}</span>
                                            <x-admin-panel::icon name="external-link" class="size-3.5 shrink-0" />
                                        </a>
                                    @elseif (Route::has('np.posts.edit'))
                                        <a href="{{ route('np.posts.edit', ['post' => $comment->commentable]) }}" wire:navigate
                                           class="flex items-center gap-1 truncate font-semibold text-brand-700 transition-colors hover:text-brand-600 dark:text-brand-400"
                                           title="ویرایش نوشته">
                                            <span class="truncate">{{ $comment->commentable->title ?? $comment->commentable->name }}</span>
                                            <x-admin-panel::icon name="external-link" class="size-3.5 shrink-0" />
                                        </a>
                                    @else
                                        <p class="truncate font-semibold">{{ $comment->commentable->title ?? $comment->commentable->name }}</p>
                                    @endif
                                @else
                                    <span class="ap-badge bg-red-50 text-red-600 dark:bg-red-500/12 dark:text-red-400">حذف شده</span>
                                @endif
                            </td>
                            <td class="px-3 py-3 text-center">
                                <span class="ap-badge bg-sky-50 text-sky-700 dark:bg-sky-500/12 dark:text-sky-400">
                                    <span class="fa-digits">{{ number_format($comment->replies_count) }}</span> پاسخ
                                </span>
                            </td>
                            <td class="px-3 py-3 text-center">
                                <span class="ap-badge {{ $this->badgeClass($comment->status) }}">{{ $this->badgeLabel($comment->status) }}</span>
                            </td>
                            <td class="px-3 py-3 fa-digits whitespace-nowrap">{{ $this->jdate($comment->created_at) }}</td>
                            <td class="px-3 py-3">
                                <div class="flex items-center gap-0.5">
                                    <button type="button" wire:click="openDetail({{ $comment->id }})"
                                            class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-brand-50 hover:text-brand-600 dark:hover:bg-brand-500/10"
                                            title="مشاهده جزئیات" aria-label="مشاهده جزئیات">
                                        <x-admin-panel::icon name="eye" class="size-4" />
                                    </button>
                                    <button type="button" wire:click="confirmDelete({{ $comment->id }})"
                                            class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-500/10"
                                            title="حذف دیدگاه" aria-label="حذف">
                                        <x-admin-panel::icon name="trash-2" class="size-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8">
                                <div class="flex flex-col items-center gap-2 py-14 text-ink-3 dark:text-night-ink-2">
                                    <x-admin-panel::icon name="message-square" class="size-10 opacity-40" />
                                    <p class="text-sm font-medium">دیدگاهی برای نمایش وجود ندارد</p>
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
                    نمایش <span class="fa-digits">{{ number_format($this->comments->firstItem() ?? 0) }}</span> تا
                    <span class="fa-digits">{{ number_format($this->comments->lastItem() ?? 0) }}</span> از
                    <span class="fa-digits">{{ number_format($this->comments->total()) }}</span> دیدگاه
                </p>
                <nav class="flex items-center gap-1" aria-label="صفحه‌بندی">
                    @if ($this->comments->onFirstPage())
                        <span class="rounded-lg p-2 text-ink-3 opacity-40"><x-admin-panel::icon name="chevron-right" class="size-4" /></span>
                    @else
                        <button wire:click="previousPage" class="rounded-lg p-2 text-ink-2 transition-colors hover:bg-surface-3 dark:hover:bg-night-3" aria-label="صفحه قبل">
                            <x-admin-panel::icon name="chevron-right" class="size-4" />
                        </button>
                    @endif

                    @foreach ($this->comments->onEachSide(1)->toArray()['links'] as $link)
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

                    @if ($this->comments->hasMorePages())
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

    {{-- مودال جزئیات + ویرایش + درخت پاسخ‌ها — پورت back/comments/show.blade.php --}}
    <div x-data="{ open: @entangle('showDetail').live }"
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
                    <x-admin-panel::icon name="message-square" class="size-5 text-brand-600 dark:text-brand-400" />
                    جزئیات دیدگاه
                </h3>
                <button type="button" wire:click="closeDetail"
                        class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-surface-2 dark:hover:bg-night-3" aria-label="بستن">
                    <x-admin-panel::icon name="x" class="size-4" />
                </button>
            </div>

            @if ($this->detailComment)
                @php $comment = $this->detailComment; @endphp

                <div class="text-[13px]">
                    {{-- نویسنده --}}
                    <div class="flex items-start justify-between gap-4 border-b border-line/60 py-2.5 dark:border-night-line/60">
                        <span class="shrink-0 text-xs font-bold text-ink-3 dark:text-night-ink-2">نویسنده</span>
                        <span class="text-end font-semibold">
                            @if ($comment->user)
                                <span class="flex items-center justify-end gap-1.5">
                                    <x-admin-panel::icon name="user" class="size-4 text-brand-600 dark:text-brand-400" />
                                    {{ $comment->user->fullname ?? $comment->user->name ?? '' }}
                                    @if (Route::has('np.users.show'))
                                        <a href="{{ route('np.users.show', ['user' => $comment->user]) }}" wire:navigate
                                           class="inline-flex items-center align-middle text-brand-600 transition-colors hover:text-brand-500 dark:text-brand-400" title="نمایش کاربر">
                                            <x-admin-panel::icon name="external-link" class="size-3.5" />
                                        </a>
                                    @endif
                                </span>
                            @elseif ($comment->admin)
                                <span class="flex items-center justify-end gap-1.5">
                                    <x-admin-panel::icon name="shield" class="size-4 text-violet-600 dark:text-violet-400" />
                                    {{ $comment->admin->fullname ?? '' }}
                                </span>
                            @elseif ($comment->seller)
                                <span class="flex items-center justify-end gap-1.5">
                                    <x-admin-panel::icon name="store" class="size-4 text-amber-600 dark:text-amber-400" />
                                    {{ $comment->seller->business_name ?? '' }}
                                    @if (Route::has('np.sellers.show'))
                                        <a href="{{ route('np.sellers.show', ['seller' => $comment->seller]) }}" wire:navigate
                                           class="inline-flex items-center align-middle text-brand-600 transition-colors hover:text-brand-500 dark:text-brand-400" title="نمایش فروشنده">
                                            <x-admin-panel::icon name="external-link" class="size-3.5" />
                                        </a>
                                    @endif
                                </span>
                            @else
                                {{ $comment->name ?? 'مهمان' }}
                            @endif
                        </span>
                    </div>

                    {{-- مقصد --}}
                    @if ($comment->commentable)
                        <div class="flex items-start justify-between gap-4 border-b border-line/60 py-2.5 dark:border-night-line/60">
                            <span class="shrink-0 text-xs font-bold text-ink-3 dark:text-night-ink-2">در</span>
                            <span class="text-end font-semibold">
                                {{ $comment->commentable->title ?? $comment->commentable->name ?? '' }}
                                @if ($comment->commentable->link())
                                    <a href="{{ $comment->commentable->link() }}" target="_blank" rel="noopener"
                                       class="ms-1 inline-flex items-center align-middle text-brand-600 transition-colors hover:text-brand-500 dark:text-brand-400" title="مشاهده در فروشگاه">
                                        <x-admin-panel::icon name="external-link" class="size-3.5" />
                                    </a>
                                @endif
                            </span>
                        </div>
                    @endif

                    {{-- متن + ویرایش (پورت update) --}}
                    <div class="border-b border-line/60 py-2.5 dark:border-night-line/60">
                        <div class="flex items-start justify-between gap-4">
                            <span class="shrink-0 text-xs font-bold text-ink-3 dark:text-night-ink-2">متن دیدگاه</span>
                            @if ($editingComment !== $comment->id)
                                <button type="button" wire:click="openEdit({{ $comment->id }})"
                                        class="inline-flex items-center gap-1 text-xs font-semibold text-brand-600 transition-colors hover:text-brand-500 dark:text-brand-400">
                                    <x-admin-panel::icon name="pencil" class="size-3.5" />
                                    ویرایش
                                </button>
                            @endif
                        </div>

                        @if ($editingComment === $comment->id)
                            <form wire:submit="update" class="mt-2.5 space-y-2.5">
                                <textarea wire:model="editBody" rows="4" class="ap-input !py-2" placeholder="متن دیدگاه…"></textarea>
                                @error('editBody') <p class="text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                                <div class="flex flex-wrap items-center gap-2">
                                    <select wire:model="editStatus" class="ap-input !w-auto !py-2">
                                        <option value="pending">منتظر تایید</option>
                                        <option value="accepted">تایید شده</option>
                                        <option value="unconfirmed">تایید نشده</option>
                                    </select>
                                    <button type="submit" wire:loading.attr="disabled" wire:target="update"
                                            class="ap-btn !px-3 !py-2 text-xs">
                                        <x-admin-panel::icon name="check" class="size-4" />
                                        ذخیره
                                    </button>
                                    <button type="button" wire:click="$set('editingComment', null)"
                                            class="ap-btn-ghost !px-3 !py-2 text-xs">انصراف</button>
                                </div>
                            </form>
                        @else
                            <p class="mt-1.5 leading-6 text-ink-2 dark:text-night-ink-2">{{ $comment->body }}</p>
                        @endif
                    </div>

                    {{-- پاسخ‌ها (درخت) --}}
                    @if ($comment->replies->count() > 0)
                        <div class="border-b border-line/60 py-2.5 dark:border-night-line/60">
                            <span class="text-xs font-bold text-ink-3 dark:text-night-ink-2">پاسخ‌ها</span>
                            <div class="mt-2.5 max-h-72 space-y-2.5 overflow-y-auto pe-1">
                                @foreach ($comment->replies as $reply)
                                    <div class="rounded-xl border border-line bg-surface-2 p-3 dark:border-night-line dark:bg-night-3" wire:key="reply-{{ $reply->id }}">
                                        <div class="flex flex-wrap items-start justify-between gap-2">
                                            <div>
                                                <span class="flex items-center gap-1.5 font-semibold">
                                                    @if ($reply->admin)
                                                        <x-admin-panel::icon name="shield" class="size-4 text-violet-600 dark:text-violet-400" />
                                                        {{ $reply->admin->fullname ?? '' }}
                                                    @elseif ($reply->user)
                                                        <x-admin-panel::icon name="user" class="size-4 text-brand-600 dark:text-brand-400" />
                                                        {{ $reply->user->fullname ?? $reply->user->name ?? '' }}
                                                    @elseif ($reply->seller)
                                                        <x-admin-panel::icon name="store" class="size-4 text-amber-600 dark:text-amber-400" />
                                                        {{ $reply->seller->business_name ?? '' }}
                                                    @else
                                                        {{ $reply->name ?? 'مهمان' }}
                                                    @endif
                                                </span>
                                                <span class="fa-digits mt-0.5 block text-[11px] text-ink-3 dark:text-night-ink-2">
                                                    {{ $this->jdate($reply->created_at, 'Y/m/d | H:i') }}
                                                </span>
                                            </div>
                                            <div class="flex flex-wrap items-center gap-1">
                                                <select wire:change="changeReplyStatus({{ $reply->id }}, $event.target.value)"
                                                        class="ap-input !w-auto !py-1.5 text-xs" aria-label="تغییر وضعیت پاسخ">
                                                    <option value="pending" @selected($reply->status === 'pending')>⏳ منتظر تایید</option>
                                                    <option value="accepted" @selected($reply->status === 'accepted')>✓ تایید شده</option>
                                                    <option value="unconfirmed" @selected($reply->status === 'unconfirmed')>✗ تایید نشده</option>
                                                </select>
                                                <button type="button" wire:click="openEditReply({{ $reply->id }})"
                                                        class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-brand-50 hover:text-brand-600 dark:hover:bg-brand-500/10"
                                                        title="ویرایش پاسخ" aria-label="ویرایش پاسخ">
                                                    <x-admin-panel::icon name="pencil" class="size-4" />
                                                </button>
                                                <button type="button" wire:click="confirmDeleteReply({{ $reply->id }})"
                                                        class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-500/10"
                                                        title="حذف پاسخ" aria-label="حذف پاسخ">
                                                    <x-admin-panel::icon name="trash-2" class="size-4" />
                                                </button>
                                            </div>
                                        </div>

                                        @if ($editingReply === $reply->id)
                                            <form wire:submit="updateReply" class="mt-2.5 space-y-2">
                                                <textarea wire:model="replyEditBody" rows="3" class="ap-input !py-2"></textarea>
                                                @error('replyEditBody') <p class="text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                                                <div class="flex items-center gap-2">
                                                    <button type="submit" wire:loading.attr="disabled" wire:target="updateReply"
                                                            class="ap-btn !px-3 !py-1.5 text-xs">ذخیره</button>
                                                    <button type="button" wire:click="$set('editingReply', null)"
                                                            class="ap-btn-ghost !px-3 !py-1.5 text-xs">انصراف</button>
                                                </div>
                                            </form>
                                        @else
                                            <p class="mt-2 leading-6 text-ink-2 dark:text-night-ink-2">{{ $reply->body }}</p>
                                        @endif

                                        <div class="mt-2">
                                            <span class="ap-badge {{ $this->badgeClass($reply->status) }}">{{ $this->badgeLabel($reply->status) }}</span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- پاسخ جدید (پورت reply/replay) --}}
                    <form wire:submit="addReply" class="py-2.5">
                        <label class="mb-1.5 block text-xs font-bold text-ink-3 dark:text-night-ink-2">پاسخ جدید</label>
                        <textarea wire:model="replyBody" rows="3" class="ap-input !py-2" placeholder="پاسخ خود را بنویسید…"></textarea>
                        @error('replyBody') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                        <button type="submit" wire:loading.attr="disabled" wire:target="addReply"
                                class="ap-btn mt-2.5 !px-3 !py-2 text-xs">
                            <x-admin-panel::icon name="send" class="size-4" />
                            ارسال پاسخ
                        </button>
                    </form>

                    {{-- تاریخ ارسال --}}
                    <div class="flex items-start justify-between gap-4 border-t border-line/60 py-2.5 dark:border-night-line/60">
                        <span class="shrink-0 text-xs font-bold text-ink-3 dark:text-night-ink-2">تاریخ ارسال</span>
                        <span class="fa-digits text-end font-semibold">
                            {{ $this->jdate($comment->created_at, 'Y/m/d | H:i') }}
                        </span>
                    </div>
                </div>
            @else
                <div class="py-10 text-center text-sm text-ink-3 dark:text-night-ink-2">دیدگاهی یافت نشد</div>
            @endif
        </div>
    </div>

    {{-- مودال تایید حذف پاسخ --}}
    <div x-data="{ open: @entangle('confirmingReplyDelete').live }"
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
            <h3 class="text-base font-bold">حذف پاسخ</h3>
            <p class="mt-1.5 text-[13px] leading-6 text-ink-2 dark:text-night-ink-2">
                آیا از حذف این پاسخ مطمئن هستید؟ این عمل قابل بازگشت نیست.
            </p>
            <div class="mt-5 flex gap-2.5">
                <button type="button" wire:click="destroyReply" wire:loading.attr="disabled" wire:target="destroyReply"
                        class="ap-btn flex-1 bg-red-600 text-white hover:bg-red-700">
                    بله حذف شود
                </button>
                <button type="button" x-on:click="open=false" class="ap-btn-ghost flex-1">انصراف</button>
            </div>
        </div>
    </div>

    {{-- مودال تایید حذف دیدگاه --}}
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
            <h3 class="text-base font-bold">حذف دیدگاه</h3>
            <p class="mt-1.5 text-[13px] leading-6 text-ink-2 dark:text-night-ink-2">
                با حذف دیدگاه دیگر قادر به بازیابی آن نخواهید بود و تمامی پاسخ‌های آن نیز حذف خواهند شد.
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
    <div wire:loading wire:target="status, ordering, previousPage, nextPage, gotoPage, update, addReply, updateReply, changeReplyStatus, destroyReply, delete, applyFilters"
         class="pointer-events-none fixed bottom-6 start-6 z-[65] flex items-center gap-2 rounded-xl border border-line bg-surface px-4 py-2.5 shadow-pop dark:border-night-line dark:bg-night-3">
        <svg class="size-4 animate-spin text-brand-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
            <path d="M21 12a9 9 0 1 1-6.219-8.56" stroke-linecap="round" />
        </svg>
        <span class="text-[13px] font-semibold">در حال بارگذاری…</span>
    </div>
</div>
