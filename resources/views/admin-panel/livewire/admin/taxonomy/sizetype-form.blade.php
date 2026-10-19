{{-- ویرایش راهنمای سایز — پنل جدید (فاز ۵) | پورت back/sizetypes/edit --}}
@php
    $errors = $errors ?? (new \Illuminate\Support\ViewErrorBag())->put('default', new \Illuminate\Support\MessageBag());
@endphp
<div class="mx-auto max-w-5xl">
    <form wire:submit="save" class="space-y-6" x-on:keydown.enter="if ($event.target.tagName !== 'TEXTAREA') { $event.preventDefault(); }">

        {{-- هدر --}}
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-2.5">
                <span class="flex size-10 items-center justify-center rounded-xl bg-brand-600 text-white">
                    <x-admin-panel::icon name="ruler" class="size-5" />
                </span>
                <div>
                    <h2 class="text-base font-extrabold">ویرایش راهنمای سایز: {{ $title ?: '—' }}</h2>
                    <p class="text-[11px] text-ink-3 dark:text-night-ink-2">
                        شناسه: <span class="fa-digits">{{ number_format($sizetype->id) }}</span>
                    </p>
                </div>
            </div>
            <a href="{{ route('np.sizetypes.index') }}" wire:navigate class="ap-btn-ghost !py-2">
                <x-admin-panel::icon name="arrow-right" class="size-4" />
                بازگشت به لیست
            </a>
        </div>

        {{-- فرم --}}
        <div class="ap-card space-y-5 p-5 sm:p-6">
            <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-xs font-semibold" for="stf-title">عنوان</label>
                    <input id="stf-title" type="text" wire:model="title" class="ap-input" placeholder="مثلا پیراهن">
                    @error('title') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold" for="stf-description">توضیحات (اختیاری)</label>
                    <textarea id="stf-description" rows="2" wire:model="description" class="ap-input" placeholder="توضیحات راهنمای سایز…"></textarea>
                    @error('description') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- ریپیتر مشخصه‌های سایز --}}
            <div class="space-y-2.5 rounded-2xl border border-line p-4 dark:border-night-line">
                <div class="flex items-center justify-between">
                    <p class="text-[13px] font-bold">مشخصه‌های سایز</p>
                    <p class="text-[11px] text-ink-3 dark:text-night-ink-2">ترتیب نمایش از بالا به پایین؛ سایزهای حذف‌شده پس از ذخیره پاک می‌شوند.</p>
                </div>
                @error('sizes') <p class="text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                <div class="space-y-2">
                    @foreach ($sizes as $i => $row)
                        <div class="flex items-center gap-2" wire:key="size-row-{{ $i }}">
                            <input type="text" wire:model="sizes.{{ $i }}.title" class="ap-input flex-1" placeholder="مثال: قد">
                            <input type="hidden" wire:model="sizes.{{ $i }}.id">
                            <button type="button" wire:click="removeSizeRow({{ $i }})"
                                    class="rounded-lg p-2 text-ink-3 transition-colors hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-500/10"
                                    title="حذف مشخصه" aria-label="حذف مشخصه">
                                <x-admin-panel::icon name="trash-2" class="size-4" />
                            </button>
                        </div>
                        @error("sizes.{$i}.title") <p class="text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                    @endforeach
                </div>
                <button type="button" wire:click="addSizeRow" class="ap-btn-outline !py-1.5 text-[13px]">
                    <x-admin-panel::icon name="plus" class="size-4" />
                    افزودن مشخصه سایز
                </button>
            </div>
        </div>

        {{-- نوار ذخیره (چسبان) --}}
        <div class="ap-card sticky bottom-4 z-20 flex flex-wrap items-center justify-between gap-3 p-4 shadow-pop sm:p-5">
            <p class="text-[12px] text-ink-3 dark:text-night-ink-2">
                برای ویرایش «مقادیر» هر گروه سایز، از صفحه <a href="{{ route('np.sizetypes.values', ['sizetype' => $sizetype]) }}" wire:navigate class="font-bold text-brand-600 hover:underline dark:text-brand-400">مقادیر سایز</a> استفاده کنید.
            </p>
            <div class="flex items-center gap-2">
                <a href="{{ route('np.sizetypes.index') }}" wire:navigate class="ap-btn-ghost !py-2">انصراف</a>
                <button type="submit" class="ap-btn-primary !py-2.5" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="save">ذخیره تغییرات</span>
                    <span wire:loading wire:target="save" class="flex items-center gap-2">
                        <svg class="size-4 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M21 12a9 9 0 1 1-6.219-8.56" stroke-linecap="round"/></svg>
                        در حال ذخیره…
                    </span>
                </button>
            </div>
        </div>
    </form>
</div>
