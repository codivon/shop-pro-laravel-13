{{-- پرونده کاربر — پنل جدید (فاز ۴) --}}
<div class="mx-auto max-w-7xl space-y-5">

    {{-- سربرگ --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <img src="{{ $user->imageUrl() }}" alt="آواتار {{ $user->fullname ?? 'کاربر' }}"
                 class="size-12 rounded-2xl border border-line object-cover dark:border-night-line">
            <div>
                <h1 class="text-lg font-black">{{ $user->fullname ?? 'کاربر #' . $user->id }}</h1>
                <p class="mt-0.5 flex flex-wrap items-center gap-2 text-xs text-ink-3 dark:text-night-ink-2">
                    <span class="fa-digits">شناسه: {{ number_format($user->id) }}</span>
                    <span class="ap-badge {{ $user->level === 'admin' ? 'bg-brand-50 text-brand-700 dark:bg-brand-500/12 dark:text-brand-400' : 'bg-surface-2 text-ink-2 dark:bg-night-3 dark:text-night-ink-2' }}">
                        {{ $user->level === 'admin' ? 'مدیر' : 'کاربر عادی' }}
                    </span>
                    <span class="ap-badge {{ $user->verified_at ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/12 dark:text-emerald-400' : 'bg-amber-50 text-amber-700 dark:bg-amber-500/12 dark:text-amber-400' }}">
                        {{ $user->verified_at ? 'شماره تایید شده' : 'شماره تایید نشده' }}
                    </span>
                </p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('np.users.index') }}" wire:navigate class="ap-btn-outline !py-2">
                <x-admin-panel::icon name="arrow-right" class="size-4" />
                بازگشت به لیست
            </a>
            @can('users.update')
                <a href="{{ route('np.users.edit', $user) }}" wire:navigate class="ap-btn-primary !py-2">
                    <x-admin-panel::icon name="pencil" class="size-4" />
                    ویرایش
                </a>
            @endcan
            @can('users.delete')
                <button type="button" wire:click="delete"
                        wire:confirm="با حذف کاربر دیگر قادر به بازیابی آن نخواهید بود. مطمئن هستید؟"
                        class="ap-btn !py-2 bg-red-600 text-white hover:bg-red-700">
                    <x-admin-panel::icon name="trash-2" class="size-4" />
                    حذف
                </button>
            @endcan
        </div>
    </div>

    {{-- کارت‌های آماری — پورت user-statistics-card های قدیمی --}}
    <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
        <div class="ap-card flex items-center justify-between gap-3 p-4">
            <div class="min-w-0">
                <p class="text-[11px] font-semibold text-ink-3 dark:text-night-ink-2">موجودی کیف پول</p>
                <p class="fa-digits mt-1 truncate text-xl font-black">{{ number_format($this->walletBalance) }} <span class="text-[11px] font-medium text-ink-3">تومان</span></p>
                <a href="{{ route('np.wallets.show', ['wallet' => $user->getWallet()]) }}" wire:navigate
                   class="mt-1 inline-flex items-center gap-1 text-[11px] font-bold text-brand-600 hover:underline dark:text-brand-400">
                    تاریخچه کیف پول
                    <x-admin-panel::icon name="external-link" class="size-3" />
                </a>
            </div>
            <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-600 dark:bg-brand-500/12 dark:text-brand-400">
                <x-admin-panel::icon name="wallet" class="size-5" />
            </span>
        </div>

        <div class="ap-card flex items-center justify-between gap-3 p-4">
            <div class="min-w-0">
                <p class="text-[11px] font-semibold text-ink-3 dark:text-night-ink-2">سفارشات</p>
                <p class="fa-digits mt-1 text-xl font-black">{{ number_format($this->ordersCount) }}</p>
                <a href="{{ route('np.orders.index', ['username' => $user->username]) }}" wire:navigate
                   class="mt-1 inline-flex items-center gap-1 text-[11px] font-bold text-brand-600 hover:underline dark:text-brand-400">مشاهده همه</a>
            </div>
            <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-violet-50 text-violet-600 dark:bg-violet-500/12 dark:text-violet-400">
                <x-admin-panel::icon name="shopping-bag" class="size-5" />
            </span>
        </div>

        <div class="ap-card flex items-center justify-between gap-3 p-4">
            <div class="min-w-0">
                <p class="text-[11px] font-semibold text-ink-3 dark:text-night-ink-2">ارزش سفارشات موفق</p>
                <p class="fa-digits mt-1 truncate text-xl font-black" title="{{ number_format($this->paidOrdersSum) }} تومان">{{ \formatPriceUnits($this->paidOrdersSum) }} <span class="text-[11px] font-medium text-ink-3">تومان</span></p>
                <a href="{{ route('np.orders.index', ['username' => $user->username]) }}" wire:navigate
                   class="mt-1 inline-flex items-center gap-1 text-[11px] font-bold text-brand-600 hover:underline dark:text-brand-400">مشاهده همه</a>
            </div>
            <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-500/12 dark:text-emerald-400">
                <x-admin-panel::icon name="banknote" class="size-5" />
            </span>
        </div>

        <div class="ap-card flex items-center justify-between gap-3 p-4">
            <div class="min-w-0">
                <p class="text-[11px] font-semibold text-ink-3 dark:text-night-ink-2">نظرات ثبت‌شده</p>
                <p class="fa-digits mt-1 text-xl font-black">{{ number_format($this->commentsCount) }}</p>
            </div>
            <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-amber-50 text-amber-600 dark:bg-amber-500/12 dark:text-amber-400">
                <x-admin-panel::icon name="message-circle" class="size-5" />
            </span>
        </div>

        <div class="ap-card flex items-center justify-between gap-3 p-4">
            <div class="min-w-0">
                <p class="text-[11px] font-semibold text-ink-3 dark:text-night-ink-2">محصولات سبد خرید</p>
                <p class="fa-digits mt-1 text-xl font-black">{{ number_format($this->cartProductsCount) }}</p>
            </div>
            <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-600 dark:bg-brand-500/12 dark:text-brand-400">
                <x-admin-panel::icon name="shopping-cart" class="size-5" />
            </span>
        </div>

        <div class="ap-card flex items-center justify-between gap-3 p-4">
            <div class="min-w-0">
                <p class="text-[11px] font-semibold text-ink-3 dark:text-night-ink-2">بازدید امروز</p>
                <p class="fa-digits mt-1 truncate text-xl font-black" title="کل: {{ number_format($this->viewsTotal) }}">{{ number_format($this->viewsToday) }}</p>
                <a href="{{ route('np.users.views', $user) }}" wire:navigate
                   class="mt-1 inline-flex items-center gap-1 text-[11px] font-bold text-brand-600 hover:underline dark:text-brand-400">مشاهده بازدیدها</a>
            </div>
            <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-amber-50 text-amber-600 dark:bg-amber-500/12 dark:text-amber-400">
                <x-admin-panel::icon name="eye" class="size-5" />
            </span>
        </div>

        <div class="ap-card flex items-center justify-between gap-3 p-4">
            <div class="min-w-0">
                <p class="text-[11px] font-semibold text-ink-3 dark:text-night-ink-2">اعلان‌های ارسالی</p>
                <p class="fa-digits mt-1 text-xl font-black">{{ number_format($this->notifications->count()) }}</p>
                <a href="{{ route('np.users.notifications', $user) }}" wire:navigate
                   class="mt-1 inline-flex items-center gap-1 text-[11px] font-bold text-brand-600 hover:underline dark:text-brand-400">مشاهده همه</a>
            </div>
            <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-500/12 dark:text-emerald-400">
                <x-admin-panel::icon name="bell" class="size-5" />
            </span>
        </div>
    </div>

    {{-- مشخصات کاربر --}}
    <div class="ap-card p-5 sm:p-6">
        <h2 class="mb-4 flex items-center gap-2 text-sm font-bold">
            <x-admin-panel::icon name="user" class="size-4.5 text-brand-600 dark:text-brand-400" />
            مشخصات کاربر
        </h2>
        <div class="grid grid-cols-1 gap-x-8 gap-y-1 sm:grid-cols-2 lg:grid-cols-3">
            <div class="flex items-center justify-between gap-3 border-b border-line/60 py-2.5 text-[13px] dark:border-night-line/60">
                <span class="text-ink-3 dark:text-night-ink-2">نام</span>
                <span class="font-semibold">{{ $user->first_name ?? '—' }}</span>
            </div>
            <div class="flex items-center justify-between gap-3 border-b border-line/60 py-2.5 text-[13px] dark:border-night-line/60">
                <span class="text-ink-3 dark:text-night-ink-2">نام خانوادگی</span>
                <span class="font-semibold">{{ $user->last_name ?? '—' }}</span>
            </div>
            <div class="flex items-center justify-between gap-3 border-b border-line/60 py-2.5 text-[13px] dark:border-night-line/60">
                <span class="text-ink-3 dark:text-night-ink-2">نام کاربری</span>
                <span class="fa-digits font-semibold">{{ $user->username }}</span>
            </div>
            <div class="flex items-center justify-between gap-3 border-b border-line/60 py-2.5 text-[13px] dark:border-night-line/60">
                <span class="text-ink-3 dark:text-night-ink-2">ایمیل</span>
                <span class="font-semibold" dir="ltr">{{ $user->email ?: '—' }}</span>
            </div>
            <div class="flex items-center justify-between gap-3 border-b border-line/60 py-2.5 text-[13px] dark:border-night-line/60">
                <span class="text-ink-3 dark:text-night-ink-2">کد ملی</span>
                <span class="fa-digits font-semibold" dir="ltr">{{ $user->national_code ?: '—' }}</span>
            </div>
            <div class="flex items-center justify-between gap-3 border-b border-line/60 py-2.5 text-[13px] dark:border-night-line/60">
                <span class="text-ink-3 dark:text-night-ink-2">شماره کارت</span>
                <span class="fa-digits font-semibold" dir="ltr">{{ $user->card_number ?: '—' }}</span>
            </div>
            <div class="flex items-center justify-between gap-3 border-b border-line/60 py-2.5 text-[13px] dark:border-night-line/60">
                <span class="text-ink-3 dark:text-night-ink-2">تاریخ تولد</span>
                <span class="fa-digits font-semibold">{{ $user->birth_date ?: '—' }}</span>
            </div>
            <div class="flex items-center justify-between gap-3 border-b border-line/60 py-2.5 text-[13px] dark:border-night-line/60">
                <span class="text-ink-3 dark:text-night-ink-2">تاریخ ثبت نام</span>
                <span class="fa-digits font-semibold">{{ $this->jdate($user->created_at) }}</span>
            </div>
            <div class="flex items-center justify-between gap-3 border-b border-line/60 py-2.5 text-[13px] dark:border-night-line/60">
                <span class="text-ink-3 dark:text-night-ink-2">کد معرف</span>
                <span class="font-semibold" dir="ltr">{{ $user->referral_code ?: '—' }}</span>
            </div>
            <div class="flex items-center justify-between gap-3 py-2.5 text-[13px]">
                <span class="text-ink-3 dark:text-night-ink-2">نقش‌ها</span>
                <span class="flex flex-wrap justify-end gap-1.5">
                    @forelse ($user->roles as $role)
                        <span class="ap-badge bg-brand-50 text-brand-700 dark:bg-brand-500/12 dark:text-brand-400">{{ $role->title ?: $role->slug }}</span>
                    @empty
                        <span class="text-ink-3 dark:text-night-ink-2">—</span>
                    @endforelse
                </span>
            </div>
        </div>
    </div>

    {{-- آخرین اعلان‌های ارسال‌شده به کاربر --}}
    <div class="ap-card overflow-hidden">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line p-4 dark:border-night-line">
            <h2 class="flex items-center gap-2 text-sm font-bold">
                <x-admin-panel::icon name="bell" class="size-4.5 text-brand-600 dark:text-brand-400" />
                اعلان‌های ارسال‌شده به کاربر
            </h2>
            <a href="{{ route('np.users.notifications', $user) }}" wire:navigate
               class="inline-flex items-center gap-1 text-[12px] font-bold text-brand-600 hover:underline dark:text-brand-400">
                مشاهده همه
                <x-admin-panel::icon name="chevron-left" class="size-3.5" />
            </a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-3xl text-start text-[13px]">
                <thead>
                    <tr class="border-b border-line bg-surface-2 text-ink-2 dark:border-night-line dark:bg-night-3">
                        <th class="w-14 px-3 py-3 text-start font-bold">#</th>
                        <th class="px-3 py-3 text-start font-bold">عنوان</th>
                        <th class="px-3 py-3 text-start font-bold">پیام</th>
                        <th class="px-3 py-3 text-start font-bold">اولویت</th>
                        <th class="px-3 py-3 text-center font-bold">پاپ‌آپ</th>
                        <th class="px-3 py-3 text-center font-bold">وضعیت بازدید</th>
                        <th class="px-3 py-3 text-start font-bold">تاریخ</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->notifications as $notification)
                        @php $p = $notification->priorityText(); @endphp
                        <tr wire:key="n-{{ $notification->id }}"
                            class="border-b border-line/60 transition-colors last:border-0 hover:bg-surface-2 dark:border-night-line/60 dark:hover:bg-night-3/60">
                            <td class="px-3 py-3 text-ink-3">{{ $loop->iteration }}</td>
                            <td class="max-w-44 px-3 py-3"><p class="truncate font-semibold">{{ $notification->title ?: 'بدون عنوان' }}</p></td>
                            <td class="max-w-80 px-3 py-3 text-ink-2 dark:text-night-ink-2"><p class="truncate">{{ \Illuminate\Support\Str::limit(strip_tags($notification->message), 120) }}</p></td>
                            <td class="px-3 py-3">
                                <span class="ap-badge {{ $p['color'] === 'success' ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/12 dark:text-emerald-400' : ($p['color'] === 'warning' ? 'bg-amber-50 text-amber-700 dark:bg-amber-500/12 dark:text-amber-400' : 'bg-red-50 text-red-600 dark:bg-red-500/12 dark:text-red-400') }}">{{ $p['title'] }}</span>
                            </td>
                            <td class="px-3 py-3 text-center">
                                @if ($notification->popup)
                                    <span class="ap-badge bg-brand-50 text-brand-700 dark:bg-brand-500/12 dark:text-brand-400">پاپ‌آپ</span>
                                @else
                                    <span class="text-ink-3 dark:text-night-ink-2">—</span>
                                @endif
                            </td>
                            <td class="px-3 py-3 text-center">
                                @if ($this->notifRead($notification))
                                    <span class="ap-badge bg-emerald-50 text-emerald-700 dark:bg-emerald-500/12 dark:text-emerald-400">خوانده شد</span>
                                @else
                                    <span class="ap-badge bg-slate-100 text-slate-600 dark:bg-night-3 dark:text-night-ink-2">خوانده نشده</span>
                                @endif
                            </td>
                            <td class="px-3 py-3 fa-digits whitespace-nowrap">{{ $this->jdate($notification->created_at, 'Y/m/d H:i') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">
                                <div class="flex flex-col items-center gap-2 py-14 text-ink-3 dark:text-night-ink-2">
                                    <x-admin-panel::icon name="bell" class="size-10 opacity-40" />
                                    <p class="text-sm font-medium">هنوز اعلانی برای این کاربر ارسال نشده است</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
