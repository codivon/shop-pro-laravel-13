{{-- ایجاد تعرفهٔ ارسال (فاز ۱۰۸) — فیلدها عین قدیمی: carrier_id (hidden)/type/max_weight/shipping_cost --}}
<div class="mx-auto max-w-2xl space-y-5">
    @include('sellers.auth.partials.sp-fetch')

    <div class="ap-card p-6 sm:p-8">
        <div class="flex items-center gap-2.5">
            <span class="flex size-10 items-center justify-center rounded-xl bg-brand-50 text-brand-600 dark:bg-brand-500/12 dark:text-brand-400">
                <x-admin-panel::icon name="receipt-percent" class="size-5" />
            </span>
            <div>
                <h1 class="text-base font-extrabold text-ink dark:text-night-ink">تعرفهٔ جدید — {{ $carrier->name }}</h1>
                <p class="text-[11px] font-semibold text-ink-3 dark:text-night-ink-2">هزینهٔ ارسال برای یک بازهٔ وزنی تعریف کنید</p>
            </div>
        </div>

        <form method="POST" action="{{ route('seller.tariffs.store') }}" id="sp-tariff-form" novalidate class="mt-6 space-y-4">
            @csrf
            <input type="hidden" name="carrier_id" value="{{ $carrier->id }}">

            <div>
                <label class="mb-1.5 block text-xs font-bold" for="tf-type">نوع ارسال</label>
                <select id="tf-type" name="type" required class="ap-input py-3!">
                    <option value="within_province">داخل استان</option>
                    <option value="extra_province">خارج استان</option>
                </select>
                @error('type') <p class="mt-1 text-[11px] font-bold text-red-500">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="mb-1.5 block text-xs font-bold" for="tf-weight">حداکثر وزن (گرم)</label>
                <input id="tf-weight" type="number" name="max_weight" required min="1" class="ap-input py-3! fa-digits" placeholder="مثلاً 1000">
                <p class="mt-1 text-[11px] font-semibold text-ink-3 dark:text-night-ink-2">برای هر شرکت حمل و نوع، بازه‌های وزنی نباید تکراری باشند</p>
                @error('max_weight') <p class="mt-1 text-[11px] font-bold text-red-500">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="mb-1.5 block text-xs font-bold" for="tf-cost">هزینهٔ ارسال (تومان)</label>
                <input id="tf-cost" type="number" name="shipping_cost" required min="0" class="ap-input py-3! fa-digits" placeholder="مثلاً 45000">
                @error('shipping_cost') <p class="mt-1 text-[11px] font-bold text-red-500">{{ $message }}</p> @enderror
            </div>

            @error('carrier_id') <p class="text-[11px] font-bold text-red-500">{{ $message }}</p> @enderror

            <div class="flex flex-wrap items-center gap-2 pt-2">
                <button type="submit" class="ap-btn-primary py-2.5!">
                    <x-admin-panel::icon name="check" class="size-4" />
                    ثبت تعرفه
                </button>
                <a href="{{ route('seller.tariffs.index', ['carrier' => $carrier->id]) }}" wire:navigate class="ap-btn-outline py-2.5!">انصراف</a>
            </div>
        </form>
    </div>
</div>

{{-- ⑨-۱۲ — ثبت با fetch + بازگشت به فهرست تعرفه‌های همان شرکت حمل. قبلاً POST
     خام response('success') کنترلر قدیمی وسط صفحه چاپ می‌شد (بدون toast و
     بدون بازگشت) — حالا spPost + spToast + navigate با الگوی بقیهٔ فرم‌های پنل --}}
<script>
    (function () {
        // wire:navigate هر بار DOM تازه می‌سازد و اسکریپت‌ها را اجرا می‌کند؛
        // فرم انتهای بدنه است و همین‌جا در دسترس است (DOMContentLoaded دوباره fire نمی‌شود)
        var form = document.getElementById('sp-tariff-form');
        if (! form || form.dataset.spBound) { return; }
        form.dataset.spBound = '1';

        form.addEventListener('submit', async function (e) {
            e.preventDefault();

            var btn = form.querySelector('button[type=submit]');
            btn.disabled = true;
            btn.classList.add('opacity-60', 'pointer-events-none');

            try {
                var res = await window.spPost(@js(route('seller.tariffs.store')), new FormData(form));

                if (res.status === 'success') {
                    window.spToast('تعرفه با موفقیت ایجاد شد.', 'success');
                    setTimeout(function () {
                        window.Livewire
                            ? Livewire.navigate(@js(route('seller.tariffs.index', ['carrier' => $carrier->id])))
                            : (window.location.href = @js(route('seller.tariffs.index', ['carrier' => $carrier->id])));
                    }, 800);
                    return;
                }

                window.spToast(res.message || 'خطا در ثبت تعرفه.', 'error');
                btn.disabled = false;
                btn.classList.remove('opacity-60', 'pointer-events-none');
            } catch (err) {
                window.spToast('ارتباط با سرور برقرار نشد.', 'error');
                btn.disabled = false;
                btn.classList.remove('opacity-60', 'pointer-events-none');
            }
        });
    })();
</script>
