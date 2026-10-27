{{-- روش‌های ارسال — فهرست (فاز ۱۰۸) — پورت sellers-old/panel/carriers/index.blade.php
     جدول روش‌ها + حذف با فرم کلاسیک @method('DELETE') به seller.carriers.destroy --}}
<div x-data="{ deleteUrl: '' }">
    {{-- ================= سربرگ صفحه ================= --}}
    <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="flex items-center gap-2 text-lg font-extrabold text-ink-900 dark:text-night-ink">
                <x-admin-panel::icon name="truck" class="size-5 text-brand-600 dark:text-brand-400" />
                حمل و نقل
            </h1>
            <p class="mt-1 text-xs text-ink-500 dark:text-night-ink-2">برای تعیین هزینه ارسال، فعال یا غیرفعال کردن سرویس‌های پستی و تنظیم محدوده ارسال از این قسمت استفاده نمایید.</p>
        </div>
        <a href="{{ $createUrl }}" wire:navigate
           class="ap-btn-primary inline-flex min-h-11 items-center gap-1.5 rounded-lg px-3 text-xs font-bold">
            <x-admin-panel::icon name="plus" class="size-4" />
            ایجاد روش ارسال
        </a>
    </div>

    {{-- ================= جدول روش‌های ارسال ================= --}}
    <section class="ap-card overflow-hidden">
        <header class="border-b border-line px-4 py-3 dark:border-night-line">
            <h2 class="text-sm font-bold">لیست روش های ارسال</h2>
        </header>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[820px] text-right text-xs">
                <thead>
                    <tr class="border-b border-line bg-surface-2 text-ink-600 dark:border-night-line dark:bg-night-2 dark:text-night-ink-2">
                        <th class="px-3 py-3 font-bold">ردیف</th>
                        <th class="px-3 py-3 font-bold">عنوان</th>
                        <th class="px-3 py-3 font-bold">شهر فروشگاه</th>
                        <th class="px-3 py-3 font-bold">شهرهای تحت پوشش</th>
                        <th class="px-3 py-3 text-center font-bold">پس کرایه</th>
                        <th class="px-3 py-3 text-center font-bold">وضعیت</th>
                        <th class="px-3 py-3 text-center font-bold">عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($carriers as $carrier)
                        <tr class="border-b border-line/70 transition-colors last:border-0 hover:bg-surface-2 dark:border-night-line/70 dark:hover:bg-night-2">
                            <td class="fa-digits px-3 py-3 text-ink-500 dark:text-night-ink-2">{{ $loop->iteration }}</td>
                            <td class="px-3 py-3 font-semibold text-ink-800 dark:text-night-ink-1">{{ $carrier->title }}</td>
                            <td class="px-3 py-3 text-ink-600 dark:text-night-ink-2">{{ $carrier->province?->name }} - {{ $carrier->city?->name }}</td>
                            <td class="px-3 py-3 text-ink-600 dark:text-night-ink-2">
                                @if ($carrier->covered_cities == 'all')
                                    همه
                                @else
                                    <span class="fa-digits" title="{{ $carrier->cities->pluck('name')->implode('، ') }}">
                                        {{ number_format($carrier->cities->count()) }} شهر
                                    </span>
                                @endif
                            </td>
                            <td class="px-3 py-3 text-center">{{ $carrier->carrige_forward ? 'بله' : 'خیر' }}</td>
                            <td class="px-3 py-3 text-center">
                                @if ($carrier->is_active)
                                    <span class="ap-badge bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300">فعال</span>
                                @else
                                    <span class="ap-badge bg-red-100 text-red-600 dark:bg-red-500/15 dark:text-red-300">غیر فعال</span>
                                @endif
                            </td>
                            <td class="px-3 py-3">
                                <div class="flex items-center justify-center gap-1.5">
                                    {{-- تعرفه‌ها — فقط وقتی پس کرایه نیست (هم‌الگوی قدیم) --}}
                                    @if (! $carrier->carrige_forward)
                                        <a href="{{ route('seller.tariffs.index', ['carrier' => $carrier]) }}" wire:navigate
                                           class="ap-btn inline-flex min-h-11 items-center gap-1 rounded-lg bg-emerald-100 px-2.5 text-[11px] font-bold text-emerald-800 hover:bg-emerald-200 dark:bg-emerald-500/15 dark:text-emerald-300"
                                           title="تعرفه‌ها">
                                            <x-admin-panel::icon name="coins" class="size-3.5" />
                                            تعرفه‌ها
                                        </a>
                                    @endif
                                    <a href="{{ route('seller.carriers.edit', ['carrier' => $carrier]) }}" wire:navigate
                                       class="ap-btn-outline inline-flex min-h-11 items-center gap-1 rounded-lg px-2.5 text-[11px] font-bold" title="ویرایش">
                                        <x-admin-panel::icon name="pencil" class="size-3.5" />
                                        ویرایش
                                    </a>
                                    <button type="button"
                                            class="inline-flex min-h-11 min-w-11 items-center justify-center rounded-lg text-red-500 transition-colors hover:bg-red-500/10"
                                            x-on:click="deleteUrl = @js(route('seller.carriers.destroy', ['carrier' => $carrier])); $dispatch('open-carrier-delete')"
                                            aria-label="حذف روش ارسال {{ $carrier->title }}" title="حذف">
                                        <x-admin-panel::icon name="trash-2" class="size-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-10 text-center text-sm text-ink-500 dark:text-night-ink-2">
                                <x-admin-panel::icon name="inbox" class="mx-auto mb-2 size-8 text-ink-300 dark:text-night-ink-3" />
                                چیزی برای نمایش وجود ندارد!
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <footer class="border-t border-line px-4 py-3 dark:border-night-line">
            {{ $carriers->links() }}
        </footer>
    </section>

    {{-- ================= مودال تأیید حذف (پورت delete-modal قدیمی) + فرم کلاسیک DELETE ================= --}}
    <div x-data="{
            loading: false,
            // حذف با فرم کلاسیک DELETE — کنترلر قدیمی response('success') می‌دهد؛ رفرش فهرست
            async deleteCarrier() {
                this.loading = true;
                try {
                    const res = await fetch(this.$refs.deleteForm.action, {
                        method: 'POST',
                        body: new FormData(this.$refs.deleteForm),
                        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                    });
                    if (res.ok) {
                        this.$refs.dialog.close();
                        this.$dispatch('toast', { message: 'روش ارسال با موفقیت حذف شد.', type: 'success' });
                        setTimeout(() => window.Livewire ? Livewire.navigate(window.location.href) : window.location.reload(), 600);
                        return;
                    }
                    this.$dispatch('toast', { message: 'خطا در حذف روش ارسال.', type: 'error' });
                } catch (e) {
                    this.$dispatch('toast', { message: 'ارتباط با سرور برقرار نشد.', type: 'error' });
                } finally {
                    this.loading = false;
                }
            },
         }"
         x-on:open-carrier-delete.window="$refs.dialog.showModal()"
         x-cloak>
        <dialog x-ref="dialog" class="m-auto w-full max-w-sm rounded-2xl bg-transparent backdrop:bg-black/50">
            <form x-ref="deleteForm" method="POST" x-bind:action="deleteUrl" class="ap-card" x-on:submit.prevent="deleteCarrier()">
                @csrf
                @method('delete')

                <header class="border-b border-line px-4 py-3 dark:border-night-line">
                    <h3 class="text-sm font-bold">آیا مطمئن هستید؟</h3>
                </header>
                <div class="px-4 py-4 text-xs leading-relaxed text-ink-600 dark:text-night-ink-2">
                    با حذف روش ارسال دیگر قادر به بازیابی آن نخواهید بود
                </div>
                <footer class="flex items-center justify-start gap-2 border-t border-line px-4 py-3 dark:border-night-line">
                    <button type="button" x-on:click="$refs.dialog.close()"
                            class="ap-btn-outline inline-flex min-h-11 items-center rounded-lg px-4 text-xs font-bold">خیر</button>
                    <button type="submit"
                            class="inline-flex min-h-11 items-center gap-1.5 rounded-lg bg-red-500 px-4 text-xs font-bold text-white transition-colors hover:bg-red-600"
                            :disabled="loading" x-bind:class="loading && 'opacity-60'">
                        <x-admin-panel::icon name="loader-circle" class="size-4" x-show="loading" x-cloak />
                        بله حذف شود
                    </button>
                </footer>
            </form>
        </dialog>
    </div>
</div>
