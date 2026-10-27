{{-- لیست تعرفه‌های ارسال یک شرکت حمل (فاز ۱۰۸) — پورت sellers-old/panel/tariffs/index.blade.php --}}
<div class="mx-auto max-w-7xl space-y-5">

    <div class="ap-card p-5 sm:p-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-2.5">
                <span class="flex size-10 items-center justify-center rounded-xl bg-brand-50 text-brand-600 dark:bg-brand-500/12 dark:text-brand-400">
                    <x-admin-panel::icon name="truck" class="size-5" />
                </span>
                <div>
                    <h2 class="text-sm font-extrabold text-ink dark:text-night-ink">تعرفه‌های {{ $carrier->name }}</h2>
                    <p class="text-[11px] text-ink-3 dark:text-night-ink-2">هزینهٔ ارسال بر اساس بازهٔ وزن و داخل/خارج استان</p>
                </div>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('seller.carriers.index') }}" wire:navigate class="ap-btn-outline py-2! text-xs!">
                    <x-admin-panel::icon name="arrow-right" class="size-4" />
                    شرکت‌های حمل
                </a>
                <a href="{{ route('seller.tariffs.create', ['carrier' => $carrier->id]) }}" wire:navigate class="ap-btn-primary py-2! text-xs!">
                    <x-admin-panel::icon name="plus" class="size-4" />
                    تعرفهٔ جدید
                </a>
            </div>
        </div>

        <div class="mt-4 overflow-x-auto">
            <table class="w-full min-w-[480px] text-[12px]">
                <thead class="bg-surface-2 text-ink-2 dark:bg-night-3 dark:text-night-ink-2">
                    <tr>
                        <th class="rounded-s-xl p-3 text-start font-bold">نوع</th>
                        <th class="p-3 text-start font-bold">حداکثر وزن (گرم)</th>
                        <th class="p-3 text-start font-bold">هزینهٔ ارسال</th>
                        <th class="rounded-e-xl p-3 text-start font-bold">عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($tariffs as $tariff)
                        <tr class="border-t border-line dark:border-night-line">
                            <td class="p-3">
                                <span class="rounded-lg px-2 py-0.5 text-[10px] font-bold
                                            {{ $tariff->type === 'within_province' ? 'bg-emerald-500/12 text-emerald-600 dark:text-emerald-400' : 'bg-sky-500/12 text-sky-600 dark:text-sky-400' }}">
                                    {{ $tariff->type === 'within_province' ? 'داخل استان' : 'خارج استان' }}
                                </span>
                            </td>
                            <td class="fa-digits p-3 font-extrabold text-ink dark:text-night-ink">{{ number_format($tariff->max_weight) }}</td>
                            <td class="fa-digits p-3 font-extrabold text-ink dark:text-night-ink">{{ number_format($tariff->shipping_cost) }} تومان</td>
                            <td class="p-3">
                                <div class="flex items-center gap-1.5">
                                    <a href="{{ route('seller.tariffs.edit', $tariff) }}" wire:navigate
                                       class="flex min-h-9 items-center gap-1 rounded-lg px-2.5 py-1.5 text-[11px] font-bold text-brand-600 transition-colors hover:bg-brand-50 dark:text-brand-400 dark:hover:bg-brand-500/10">
                                        <x-admin-panel::icon name="pencil" class="size-3.5" />
                                        ویرایش
                                    </a>
                                    <form method="POST" action="{{ route('seller.tariffs.destroy', $tariff) }}"
                                          x-data x-on:submit.prevent="if (confirm('این تعرفه حذف شود؟')) $el.submit()">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="flex min-h-9 items-center gap-1 rounded-lg px-2.5 py-1.5 text-[11px] font-bold text-red-500 transition-colors hover:bg-red-50 dark:hover:bg-red-500/10">
                                            <x-admin-panel::icon name="trash-2" class="size-3.5" />
                                            حذف
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr class="border-t border-line dark:border-night-line">
                            <td colspan="4" class="p-8 text-center">
                                <span class="mx-auto flex size-12 items-center justify-center rounded-2xl bg-surface-2 text-ink-3 dark:bg-night-3">
                                    <x-admin-panel::icon name="receipt-percent" class="size-6" />
                                </span>
                                <p class="mt-2 text-[13px] font-bold text-ink-2 dark:text-night-ink-2">هنوز تعرفه‌ای برای این شرکت حمل ثبت نکرده‌اید</p>
                                <a href="{{ route('seller.tariffs.create', ['carrier' => $carrier->id]) }}" wire:navigate class="ap-btn-primary mt-3 py-2! text-xs!">افزودن اولین تعرفه</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">{{ $tariffs->onEachSide(1)->links() }}</div>
    </div>
</div>
