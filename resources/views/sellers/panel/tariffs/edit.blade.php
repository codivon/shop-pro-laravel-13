{{-- ویرایش تعرفهٔ ارسال (فاز ۱۰۸) — فیلدها عین قدیمی + @method('PUT') --}}
<div class="mx-auto max-w-2xl space-y-5">

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

        <form method="POST" action="{{ route('seller.tariffs.update', $tariff) }}" class="mt-6 space-y-4">
            @csrf
            @method('PUT')

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
