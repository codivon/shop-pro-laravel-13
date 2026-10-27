{{-- نظرات محصولات فروشنده (فاز ۱۰۸) — پورت sellers-old/panel/reviews/index.blade.php
     ۴ کارت آمار + جدول نظرات با ستاره/توصیه/وضعیت — فقط-خواندنی --}}
<div class="mx-auto max-w-7xl space-y-5">

    {{-- ۴ کارت آمار رضایت --}}
    <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
        <div class="ap-card p-4">
            <span class="flex size-9 items-center justify-center rounded-xl bg-violet-500/12 text-violet-600 dark:text-violet-400">
                <x-admin-panel::icon name="message-square" class="size-4.5" />
            </span>
            <p class="fa-digits mt-3 text-xl font-extrabold text-ink dark:text-night-ink">{{ number_format($stats['total']) }}</p>
            <p class="mt-0.5 text-[11px] font-bold text-ink-3 dark:text-night-ink-2">کل نظرات ثبت‌شده</p>
        </div>
        <div class="ap-card p-4">
            <span class="flex size-9 items-center justify-center rounded-xl bg-amber-500/12 text-amber-600 dark:text-amber-400">
                <x-admin-panel::icon name="star" class="size-4.5" />
            </span>
            <p class="fa-digits mt-3 text-xl font-extrabold text-ink dark:text-night-ink">
                {{ is_null($stats['avg_rating']) ? '—' : number_format($stats['avg_rating'], 1) }}
                @if (! is_null($stats['avg_rating']))<small class="text-[11px] font-bold text-ink-3 dark:text-night-ink-2">از ۵</small>@endif
            </p>
            <p class="mt-0.5 text-[11px] font-bold text-ink-3 dark:text-night-ink-2">میانگین امتیاز نظرات تأییدشده</p>
        </div>
        <div class="ap-card p-4">
            <span class="flex size-9 items-center justify-center rounded-xl bg-emerald-500/12 text-emerald-600 dark:text-emerald-400">
                <x-admin-panel::icon name="thumbs-up" class="size-4.5" />
            </span>
            <p class="fa-digits mt-3 text-xl font-extrabold text-ink dark:text-night-ink">
                {{ is_null($stats['recommend_percent']) ? '—' : number_format($stats['recommend_percent'], 1).'٪' }}
            </p>
            <p class="mt-0.5 text-[11px] font-bold text-ink-3 dark:text-night-ink-2">درصد توصیه به دیگران</p>
        </div>
        <div class="ap-card p-4">
            <span class="flex size-9 items-center justify-center rounded-xl bg-sky-500/12 text-sky-600 dark:text-sky-400">
                <x-admin-panel::icon name="clock" class="size-4.5" />
            </span>
            <p class="fa-digits mt-3 text-xl font-extrabold text-ink dark:text-night-ink">{{ number_format($stats['pending']) }}</p>
            <p class="mt-0.5 text-[11px] font-bold text-ink-3 dark:text-night-ink-2">در انتظار تأیید مدیر</p>
        </div>
    </div>

    {{-- جدول نظرات --}}
    <div class="ap-card p-5">
        <h2 class="text-sm font-extrabold text-ink dark:text-night-ink">ریز نظرات</h2>
        <div class="mt-3 overflow-x-auto">
            <table class="w-full min-w-[640px] text-[12px]">
                <thead class="bg-surface-2 text-ink-2 dark:bg-night-3 dark:text-night-ink-2">
                    <tr>
                        <th class="rounded-s-xl p-3 text-start font-bold">محصول</th>
                        <th class="p-3 text-start font-bold">کاربر</th>
                        <th class="p-3 text-start font-bold">امتیاز</th>
                        <th class="p-3 text-start font-bold">توصیه</th>
                        <th class="p-3 text-start font-bold">عنوان نظر</th>
                        <th class="p-3 text-start font-bold">وضعیت</th>
                        <th class="rounded-e-xl p-3 text-start font-bold">تاریخ</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($reviews as $review)
                        <tr class="border-t border-line align-top dark:border-night-line">
                            <td class="max-w-36 p-3">
                                <p class="truncate font-bold text-ink dark:text-night-ink">{{ $review->product?->title ?? 'محصول حذف‌شده' }}</p>
                            </td>
                            <td class="p-3 font-semibold text-ink-2 dark:text-night-ink-2">
                                {{ trim(($review->user->first_name ?? '').' '.($review->user->last_name ?? '')) ?: ($review->user->username ?? 'کاربر') }}
                            </td>
                            <td class="p-3">
                                <span class="whitespace-nowrap text-[13px] leading-5 text-amber-500" aria-label="امتیاز {{ $review->rating }} از ۵">
                                    @for ($i = 1; $i <= 5; $i++)
                                        <span class="{{ $i <= $review->rating ? '' : 'text-ink-3/25 dark:text-night-ink-2/25' }}">★</span>
                                    @endfor
                                </span>
                            </td>
                            <td class="p-3">
                                <span class="rounded-lg px-2 py-0.5 text-[10px] font-bold
                                            {{ $review->suggest === 'yes' ? 'bg-emerald-500/12 text-emerald-600 dark:text-emerald-400' : ($review->suggest === 'no' ? 'bg-red-500/12 text-red-600 dark:text-red-400' : 'bg-surface-2 text-ink-3 dark:bg-night-3 dark:text-night-ink-2') }}">
                                    {{ $review->suggest === 'yes' ? 'پیشنهاد می‌کند' : ($review->suggest === 'no' ? 'پیشنهاد نمی‌کنم' : 'مطمئن نیستم') }}
                                </span>
                            </td>
                            <td class="max-w-40 p-3">
                                <p class="truncate font-bold text-ink dark:text-night-ink">{{ $review->title }}</p>
                                <p class="mt-0.5 line-clamp-2 text-[11px] font-semibold leading-5 text-ink-3 dark:text-night-ink-2">{{ Str::limit($review->comment, 90) }}</p>
                            </td>
                            <td class="p-3">
                                <span class="rounded-lg px-2 py-0.5 text-[10px] font-bold
                                            {{ $review->status === 'accepted' ? 'bg-emerald-500/12 text-emerald-600 dark:text-emerald-400' : ($review->status === 'pending' ? 'bg-amber-500/12 text-amber-600 dark:text-amber-400' : 'bg-red-500/12 text-red-600 dark:text-red-400') }}">
                                    {{ $review->status === 'accepted' ? 'تأییدشده' : ($review->status === 'pending' ? 'در انتظار تأیید' : 'ردشده') }}
                                </span>
                            </td>
                            <td class="fa-digits p-3 font-semibold text-ink-3 dark:text-night-ink-2">{{ jdate($review->created_at)->format('%Y/%m/%d') }}</td>
                        </tr>
                    @empty
                        <tr class="border-t border-line dark:border-night-line">
                            <td colspan="7" class="p-8 text-center">
                                <span class="mx-auto flex size-12 items-center justify-center rounded-2xl bg-surface-2 text-ink-3 dark:bg-night-3">
                                    <x-admin-panel::icon name="message-square-off" class="size-6" />
                                </span>
                                <p class="mt-2 text-[13px] font-bold text-ink-2 dark:text-night-ink-2">هنوز نظری برای محصولات شما ثبت نشده است</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">{{ $reviews->onEachSide(1)->links() }}</div>
    </div>
</div>
