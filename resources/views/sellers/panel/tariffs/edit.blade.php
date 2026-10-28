{{-- ویرایش تعرفهٔ ارسال (فاز ۱۰۸) — فیلدها عین قدیمی + @method('PUT') --}}
<div class="mx-auto max-w-2xl space-y-5">
    @include('sellers.auth.partials.sp-fetch')

    <div class="ap-card p-6 sm:p-8">
        <div class="flex items-center gap-2.5">
            <span class="flex size-10 items-center justify-center rounded-xl bg-amber-50 text-amber-600 dark:bg-amber-500/12 dark:text-amber-400">
                <x-admin-panel::icon name="pencil" class="size-5" />
            </span>
            <div>
                <h1 class="text-base font-extrabold text-ink dark:text-night-ink">ویرایش تعرفه</h1>
                <p class="text-[11px] font-semibold text-ink-3 dark:text-night-ink-2">تعرفهٔ ارسال را ویرایش کنید</p>
            </div>
        </div>

        <form method="POST" action="{{ route('seller.tariffs.update', $tariff) }}" id="sp-tariff-form" novalidate class="mt-6 space-y-4">
            @csrf
            @method('PUT')
            {{-- ⑨-۱۲ — UpdateTariffRequest حدود carrier_id را لازم دارد (فرم قدیمی هم همین
                 hidden را داشت)؛ نبودش باعث ۴۲۲ «فیلد carrier id الزامی است» می‌شد --}}
            <input type="hidden" name="carrier_id" value="{{ $tariff->carrier_id }}">

            <div>
                <label class="mb-1.5 block text-xs font-bold" for="tf-type">نوع ارسال</label>
                <select id="tf-type" name="type" required class="ap-input py-3!">
                    <option value="within_province" {{ $tariff->type === 'within_province' ? 'selected' : '' }}>داخل استان</option>
                    <option value="extra_province" {{ $tariff->type === 'extra_province' ? 'selected' : '' }}>خارج استان</option>
                </select>
                @error('type') <p class="mt-1 text-[11px] font-bold text-red-500">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="mb-1.5 block text-xs font-bold" for="tf-weight">حداکثر وزن (گرم)</label>
                <input id="tf-weight" type="number" name="max_weight" required min="1" value="{{ $tariff->max_weight }}" class="ap-input py-3! fa-digits">
                @error('max_weight') <p class="mt-1 text-[11px] font-bold text-red-500">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="mb-1.5 block text-xs font-bold" for="tf-cost">هزینهٔ ارسال (تومان)</label>
                <input id="tf-cost" type="number" name="shipping_cost" required min="0" value="{{ $tariff->shipping_cost }}" class="ap-input py-3! fa-digits">
                @error('shipping_cost') <p class="mt-1 text-[11px] font-bold text-red-500">{{ $message }}</p> @enderror
            </div>

            <div class="flex flex-wrap items-center gap-2 pt-2">
                <button type="submit" class="ap-btn-primary py-2.5!">
                    <x-admin-panel::icon name="check" class="size-4" />
                    ذخیرهٔ تغییرات
                </button>
                <a href="{{ route('seller.tariffs.index', ['carrier' => $tariff->carrier_id]) }}" wire:navigate class="ap-btn-outline py-2.5!">انصراف</a>
            </div>
        </form>
    </div>
</div>

{{-- ⑨-۱۲ — ذخیره با fetch + بازگشت به فهرست تعرفه‌های همان شرکت حمل (الگوی create).
     _method=PUT داخل FormData است و با POST اسپوف می‌شود؛ قبلاً پاسخ خام
     response('success') وسط صفحه چاپ می‌شد --}}
<script>
    (function () {
        var form = document.getElementById('sp-tariff-form');
        if (! form || form.dataset.spBound) { return; }
        form.dataset.spBound = '1';

        form.addEventListener('submit', async function (e) {
            e.preventDefault();

            var btn = form.querySelector('button[type=submit]');
            btn.disabled = true;
            btn.classList.add('opacity-60', 'pointer-events-none');

            try {
                var res = await window.spPost(@js(route('seller.tariffs.update', $tariff)), new FormData(form));

                if (res.status === 'success') {
                    window.spToast('تعرفه با موفقیت ویرایش شد.', 'success');
                    setTimeout(function () {
                        window.Livewire
                            ? Livewire.navigate(@js(route('seller.tariffs.index', ['carrier' => $tariff->carrier_id])))
                            : (window.location.href = @js(route('seller.tariffs.index', ['carrier' => $tariff->carrier_id])));
                    }, 800);
                    return;
                }

                window.spToast(res.message || 'خطا در ویرایش تعرفه.', 'error');
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
