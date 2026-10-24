{{-- درون‌ریزی کاربران از اکسل — بازطراحی کامل فاز ۱۹ (پورت back/imports/usersExcelImport) --}}
@php
    $errors = $errors ?? (new \Illuminate\Support\ViewErrorBag())->put('default', new \Illuminate\Support\MessageBag());
@endphp
<div class="mx-auto max-w-7xl space-y-5">

    {{-- سربرگ قهرمان --}}
    <div class="relative overflow-hidden rounded-3xl bg-linear-to-l from-sky-600 via-sky-600 to-cyan-700 p-6 text-white shadow-xl shadow-sky-600/20">
        <div class="absolute -top-16 -start-10 size-52 rounded-full bg-white/10 blur-2xl" aria-hidden="true"></div>
        <div class="absolute -bottom-20 end-10 size-44 rounded-full bg-white/10 blur-2xl" aria-hidden="true"></div>
        <div class="relative flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <span class="flex size-14 items-center justify-center rounded-2xl bg-white/15 ring-1 ring-white/25 backdrop-blur">
                    <x-admin-panel::icon name="users" class="size-7" />
                </span>
                <div>
                    <h1 class="text-lg font-extrabold sm:text-xl">درون‌ریزی کاربران از اکسل</h1>
                    <p class="mt-1 text-[13px] text-white/80">فایل اکسل کاربران را آماده کنید، ستون‌ها را انتخاب و به‌صورت گروهی وارد کنید</p>
                </div>
            </div>
            <span class="fa-digits inline-flex items-center gap-1.5 rounded-xl bg-white/12 px-3 py-2 text-xs font-bold ring-1 ring-white/20">
                <x-admin-panel::icon name="list-checks" class="size-4" />
                <span class="fa-digits">{{ number_format($this->activeFieldsCount) }}</span> ستون فعال
            </span>
        </div>
    </div>

    {{-- ردیف اصلی: فیلدها + پیش‌نمایش زنده اکسل --}}
    <div class="grid grid-cols-1 gap-5 lg:grid-cols-5">
        <div class="lg:col-span-2">
            @include('admin-panel::livewire.admin.imports.partials.import-form', [
                'fields' => $fields,
                'filters' => $filters,
                'label' => 'کاربران',
                'showWarehouse' => false,
            ])
        </div>

        <div class="space-y-5 lg:col-span-3">
            @include('admin-panel::livewire.admin.imports.partials.excel-preview', [
                'fields' => $fields,
                'filters' => $filters,
                'label' => 'کاربران',
            ])
            @include('admin-panel::livewire.admin.imports.partials.uploaded-files')
        </div>
    </div>

    {{-- گزارش خطاها --}}
    @include('admin-panel::livewire.admin.imports.partials.error-panel', ['label' => 'کاربران'])
</div>
