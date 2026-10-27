{{-- گام ۴ ثبت‌نام — بارگذاری مدارک (فاز ۱۰۸). مکانیزم عین قدیمی:
     POST multipart با فیلد imageFor (نام فیلد تصویر) به seller.registration_documents_store
     و حذف با DELETE به seller.registration_documents_delete؛ پایان با POST seller.registration_documents_check
     (فیلد vat_free: 1=دارای ارزش افزوده، 2=بدون) --}}
<div class="space-y-5"
     x-data="{
        busy: false,
        vatFree: '2',
        images: @js($images),
        uploading: '',
        async upload(field, input) {
            if (! input.files || ! input.files[0]) { return; }
            uploading = field;
            var fd = new FormData();
            fd.append('imageFor', field);
            fd.append(field, input.files[0]);
            try {
                var res = await fetch('{{ route('seller.registration_documents_store') }}', {
                    method: 'POST',
                    body: fd,
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                });
                var data = await res.json().catch(function () { return { status: 'success' }; });
                if (res.ok) {
                    images[field] = URL.createObjectURL(input.files[0]);
                    spToast('تصویر بارگذاری شد', 'success');
                } else {
                    spToast(data.message || 'بارگذاری ناموفق بود', 'error');
                }
            } catch (e) { spToast('خطا در برقراری ارتباط', 'error'); }
            uploading = '';
        },
        async removeImage(field) {
            uploading = field;
            var fd = new FormData();
            fd.append('imageFor', field);
            fd.append('_method', 'DELETE');
            try {
                await fetch('{{ route('seller.registration_documents_delete') }}', {
                    method: 'POST',
                    body: fd,
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                });
                images[field] = '';
                spToast('تصویر حذف شد', 'success');
            } catch (e) { spToast('خطا در برقراری ارتباط', 'error'); }
            uploading = '';
        },
        async finish() {
            busy = true;
            var fd = new FormData();
            fd.append('vat_free', vatFree);
            try {
                var data = await spPost('{{ route('seller.registration_documents_check') }}', fd);
                if (data.redirect) { window.location.href = data.redirect; }
                else { spToast(data.message || 'بررسی مدارک ناموفق بود', 'error'); busy = false; }
            } catch (e) { spToast('خطا در برقراری ارتباط', 'error'); busy = false; }
        }
     }">
    @include('sellers.auth.partials.sp-fetch')
    @include('sellers.auth.partials.steps', ['active' => 4])

    <div class="ap-card p-6 sm:p-8">
        <h1 class="text-lg font-extrabold text-ink dark:text-night-ink">بارگذاری مدارک</h1>
        <p class="mt-1 text-xs font-semibold leading-6 text-ink-3 dark:text-night-ink-2">
            تصاویر کارت ملی (و در صورت داشتن پروانهٔ کسب، گواهی ارزش افزوده) را بارگذاری کنید.
        </p>

        {{-- نوع ثبت --}}
        <div class="mt-5 grid grid-cols-1 gap-3 sm:grid-cols-2">
            <label class="flex min-h-11 cursor-pointer items-center justify-center gap-2 rounded-xl border px-3 py-2.5 text-[13px] font-bold transition-all border-line dark:border-night-line"
                   x-bind:class="vatFree === '1' ? 'border-brand-500 bg-brand-50/60 text-brand-700 dark:bg-brand-500/10 dark:text-brand-300' : 'text-ink-2 dark:text-night-ink-2'">
                <input type="radio" name="vat_free" value="1" x-model="vatFree" class="sr-only">
                دارای گواهی ارزش افزوده
            </label>
            <label class="flex min-h-11 cursor-pointer items-center justify-center gap-2 rounded-xl border px-3 py-2.5 text-[13px] font-bold transition-all border-line dark:border-night-line"
                   x-bind:class="vatFree === '2' ? 'border-brand-500 bg-brand-50/60 text-brand-700 dark:bg-brand-500/10 dark:text-brand-300' : 'text-ink-2 dark:text-night-ink-2'">
                <input type="radio" name="vat_free" value="2" x-model="vatFree" class="sr-only">
                بدون ارزش افزوده
            </label>
        </div>

        {{-- کارت‌های بارگذاری --}}
        <div class="mt-5 space-y-4">
            @foreach ([
                ['field' => 'card_image', 'title' => 'تصویر کارت ملی (رو)', 'required' => true, 'icon' => 'credit-card'],
                ['field' => 'card_image_back', 'title' => 'تصویر کارت ملی (پشت)', 'required' => true, 'icon' => 'credit-card'],
                ['field' => 'vat_image', 'title' => 'گواهی ارزش افزوده', 'required' => false, 'icon' => 'file-badge', 'only' => '1'],
            ] as $doc)
                <div class="rounded-2xl border border-line p-4 dark:border-night-line"
                     x-show="{{ ! empty($doc['only']) ? "vatFree === '1'" : 'true' }}" x-cloak="{{ ! empty($doc['only']) ? 'x-cloak' : '' }}">
                    <div class="flex items-center justify-between gap-3">
                        <p class="flex items-center gap-2 text-[13px] font-extrabold text-ink dark:text-night-ink">
                            <x-admin-panel::icon name="{{ $doc['icon'] }}" class="size-4 text-brand-600 dark:text-brand-400" />
                            {{ $doc['title'] }}
                            @if ($doc['required'])<span class="text-red-500">*</span>@endif
                        </p>
                        @if (! $doc['required'])
                            <span class="rounded-lg bg-surface-2 px-2 py-0.5 text-[10px] font-bold text-ink-3 dark:bg-night-3 dark:text-night-ink-2">فقط حالت ارزش افزوده</span>
                        @endif
                    </div>

                    <div class="mt-3 flex items-center gap-3">
                        <img x-show="images['{{ $doc['field'] }}']" x-bind:src="images['{{ $doc['field'] }}']"
                             alt="{{ $doc['title'] }}" class="size-16 rounded-xl border border-line object-cover dark:border-night-line">
                        <label class="ap-btn-outline relative min-h-11 cursor-pointer !py-2.5"
                               x-bind:class="uploading === '{{ $doc['field'] }}' ? 'pointer-events-none opacity-60' : ''">
                            <x-admin-panel::icon name="upload" class="size-4" />
                            بارگذاری تصویر
                            <input type="file" accept="image/*" name="{{ $doc['field'] }}" class="sr-only"
                                   x-on:change="upload('{{ $doc['field'] }}', $event.target)">
                        </label>
                        <button type="button" x-show="images['{{ $doc['field'] }}']" x-cloak
                                class="flex min-h-11 items-center gap-1.5 rounded-xl px-3 py-2.5 text-xs font-bold text-red-500 transition-colors hover:bg-red-50 dark:hover:bg-red-500/10"
                                x-on:click="removeImage('{{ $doc['field'] }}')">
                            <x-admin-panel::icon name="trash-2" class="size-4" />
                            حذف
                        </button>
                        <svg x-show="uploading === '{{ $doc['field'] }}'" x-cloak class="size-4 animate-spin text-brand-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M21 12a9 9 0 1 1-6.219-8.56" stroke-linecap="round"/></svg>
                    </div>
                </div>
            @endforeach
        </div>

        <button type="button" class="ap-btn-primary mt-6 w-full !py-3" x-on:click="finish()" :disabled="busy" x-bind:class="busy ? 'opacity-60' : ''">
            <span x-show="!busy" class="flex items-center justify-center gap-2">
                بررسی و ادامه
                <x-admin-panel::icon name="chevron-left" class="size-4" />
            </span>
            <span x-show="busy" x-cloak class="flex items-center justify-center gap-2">
                <svg class="size-4 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M21 12a9 9 0 1 1-6.219-8.56" stroke-linecap="round"/></svg>
                در حال بررسی مدارک…
            </span>
        </button>
    </div>
</div>
