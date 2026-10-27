{{-- پرسش و پاسخ — فهرست (فاز ۱۰۸) — پورت sellers-old/panel/questions/index.blade.php
     فیلتر وضعیت/مرتب‌سازی با همان کلیدهای قدیمی + جدول پرسش‌ها با لینک مشاهده --}}
<div>
    {{-- ================= سربرگ صفحه ================= --}}
    <div class="mb-4">
        <h1 class="flex items-center gap-2 text-lg font-extrabold text-ink-900 dark:text-night-ink">
            <x-admin-panel::icon name="message-circle" class="size-5 text-brand-600 dark:text-brand-400" />
            پرسش‌ها
        </h1>
        <p class="mt-1 text-xs text-ink-500 dark:text-night-ink-2">اینجا می‌توانید پرسش‌های کاربران را ببینید و به آنها پاسخ دهید</p>
    </div>

    {{-- ================= فیلترها (پورت فرم قدیمی — همان کلیدها status/ordering) ================= --}}
    <section class="ap-card mb-4">
        <div class="grid grid-cols-1 gap-3 p-4 sm:grid-cols-2">
            <label class="block">
                <span class="mb-1 block text-xs font-semibold text-ink-600 dark:text-night-ink-2">وضعیت پرسش</span>
                <select wire:model.live="status" class="ap-input w-full">
                    <option value="all" @selected($status === 'all')>همه</option>
                    <option value="noanswer" @selected($status === 'noanswer')>پاسخ داده ‌نشده</option>
                    <option value="unconfirmed" @selected($status === 'unconfirmed')>پاسخ رد ‌شده</option>
                    <option value="pending" @selected($status === 'pending')>پاسخ در حال بررسی</option>
                    <option value="answer_accepted" @selected($status === 'answer_accepted')>پاسخ تایید‌ شده</option>
                </select>
            </label>
            <label class="block">
                <span class="mb-1 block text-xs font-semibold text-ink-600 dark:text-night-ink-2">مرتب سازی</span>
                <select wire:model.live="ordering" class="ap-input w-full">
                    <option value="latest" @selected($ordering === 'latest')>جدیدترین</option>
                    <option value="oldest" @selected($ordering === 'oldest')>قدیمی ترین</option>
                </select>
            </label>
        </div>
    </section>

    {{-- ================= جدول پرسش‌ها ================= --}}
    <section class="ap-card overflow-hidden">
        <header class="flex items-center justify-between border-b border-line px-4 py-3 dark:border-night-line">
            <h2 class="flex items-center gap-2 text-sm font-bold">
                <x-admin-panel::icon name="list" class="size-4 text-brand-600 dark:text-brand-400" />
                لیست پرسش‌ها
            </h2>
            <span class="text-[11px] text-ink-500 dark:text-night-ink-2">
                تعداد نتایج: <span class="fa-digits font-bold">{{ number_format($questionsCount) }}</span>
            </span>
        </header>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[760px] text-right text-xs">
                <thead>
                    <tr class="border-b border-line bg-surface-2 text-ink-600 dark:border-night-line dark:bg-night-2 dark:text-night-ink-2">
                        <th class="px-3 py-3 text-center font-bold">#</th>
                        <th class="px-3 py-3 font-bold">نام</th>
                        <th class="px-3 py-3 font-bold">دیدگاه</th>
                        <th class="px-3 py-3 text-center font-bold">نوع</th>
                        <th class="px-3 py-3 text-center font-bold">وضعیت</th>
                        <th class="px-3 py-3 text-center font-bold">عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($questions as $question)
                        <tr class="border-b border-line/70 transition-colors last:border-0 hover:bg-surface-2 dark:border-night-line/70 dark:hover:bg-night-2">
                            <td class="fa-digits px-3 py-3 text-center text-ink-500 dark:text-night-ink-2">{{ $question->id }}</td>
                            <td class="px-3 py-3 font-semibold text-ink-800 dark:text-night-ink-1">
                                @if ($question->user)
                                    [کاربر]{{ $question->user ? $question->user->fullname : $question->name }}
                                @elseif ($question->admin)
                                    [مدیر سایت]
                                @elseif ($question->seller)
                                    [فروشنده]{{ $question->seller ? $question->seller->business_name : '' }}
                                @endif
                            </td>
                            <td class="max-w-72 px-3 py-3 text-ink-600 dark:text-night-ink-2">{{ short_content($question->body, 20, false) }}</td>
                            <td class="px-3 py-3 text-center">
                                @if ($question->comment_id)
                                    <span class="ap-badge bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300">پاسخ</span>
                                @else
                                    <span class="ap-badge bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300">پرسش‌</span>
                                @endif
                            </td>
                            <td class="px-3 py-3 text-center">
                                @if ($question->status == 'pending')
                                    <span class="ap-badge bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300">منتظر تایید</span>
                                @elseif ($question->status == 'noanswer')
                                    <span class="ap-badge bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300">در انتظار پاسخ</span>
                                @elseif ($question->status == 'accepted')
                                    <span class="ap-badge bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300">تایید شده</span>
                                @else
                                    <span class="ap-badge bg-red-100 text-red-600 dark:bg-red-500/15 dark:text-red-300">تایید نشده</span>
                                @endif
                            </td>
                            <td class="px-3 py-3 text-center">
                                {{-- همان شرط نمایش دکمهٔ مشاهدهٔ قدیمی --}}
                                @if ($question->status != 'accepted' && ! ($question->comment_id != null || $question->status == 'noanswer'))
                                    <span class="ap-badge bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300">منتظر تایید</span>
                                @else
                                    <a href="{{ route('seller.questions.show', ['question' => $question]) }}" wire:navigate
                                       class="ap-btn-outline inline-flex min-h-11 items-center rounded-lg px-2.5 text-[11px] font-bold">
                                        مشاهده
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-10 text-center text-sm text-ink-500 dark:text-night-ink-2">
                                <x-admin-panel::icon name="inbox" class="mx-auto mb-2 size-8 text-ink-300 dark:text-night-ink-3" />
                                چیزی برای نمایش وجود ندارد!
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <footer class="border-t border-line px-4 py-3 dark:border-night-line">
            {{ $questions->links() }}
        </footer>
    </section>
</div>
