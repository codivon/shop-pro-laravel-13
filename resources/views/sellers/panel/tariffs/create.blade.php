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

        <form method="POST" action="{{ route('seller.tariffs.store') }}" class="mt-6 space-y-4">
            @csrf
            <input type="hidden" name="carrier_id" value="{{ $carrier->id }}">

            <div>
                <label class="mb-1.5 block text-xs font-bold" for="tf-type">نوع ارسال</label>
                <select id="tf-type" name="type" required class="ap-input !py-3">
                    <option value="within_province">داخل استان</option>
                    <option value="extra_province">خارج استان</option>
                </select>
                @error('type') <p class="mt-1 text-[11px] font-bold text-red-500">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="mb-1.5 block text-xs font-bold" for="tf-weight">حداکثر وزن (گرم)</label>
                <input id="tf-weight" type="number" name="max_weight" required min="1" class="ap-input !py-3 fa-digits" placeholder="مثلاً 1000">
                <p class="mt-1 text-[11px] font-semibold text-ink-3 dark:text-night-ink-2">برای هر شرکت حمل و نوع، بازه‌های وزنی نباید تکراری باشند</p>
                @error('max_weight') <p class="mt-1 text-[11px] font-bold text-red-500">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="mb-1.5 block text-xs font-bold" for="tf-cost">هزینهٔ ارسال (تومان)</label>
                <input id="tf-cost" type="number" name="shipping_cost" required min="0" class="ap-input !py-3 fa-digits" placeholder="مثلاً 45000">
                @error('shipping_cost') <p class="mt-1 text-[11px] font-bold text-red-500">{{ $message }}</p> @enderror
            </div>

            @error('carrier_id') <p class="text-[11px] font-bold text-red-500">{{ $message }}</p> @enderror

            <div class="flex flex-wrap items-center gap-2 pt-2">
                <button type="submit" class="ap-btn-primary !py-2.5">
                    <x-admin-panel::icon name="check" class="size-4" />
                    ثبت تعرفه
                </button>
                <a href="{{ route('seller.tariffs.index', ['carrier' => $carrier->id]) }}" wire:navigate class="ap-btn-outline !py-2.5">انصراف</a>
            </div>
        </form>
    </div>
</div>
