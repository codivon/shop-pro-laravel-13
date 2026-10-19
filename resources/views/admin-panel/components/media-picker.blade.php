{{--
    انتخابگر رسانه (File Manager) — فاز ۱۴
    پورت تجربه قدیمی: کلیک روی ناحیه → باز شدن مدیریت فایل‌های سایت (Alexusmai FM) →
    انتخاب/آپلود تصویر → مقدار در فیلد ذخیره و پیش‌نمایش فوراً نمایش داده می‌شود.

    متغیرها:
      wireModel : نام پراپرتی Livewire (اجباری)
      value     : مقدار فعلی (مسیر نسبی یا URL کامل) — برای پیش‌نمایش اولیه
      label     : برچسب بالای انتخابگر
      hint      : متن راهنما
      required  : نمایش نشان «ضروری»
      errorKey  : کلید خطای اعتبارسنجی (پیش‌فرض = wireModel)
      fmUrl     : آدرس فایل‌منیجر (پیش‌فرض fm.fm-button)
      height    : کلاس ارتفاع ناحیه انتخاب (پیش‌فرض h-44 sm:h-52)
      aspect    : نسبت تصویر پیش‌نمایش (contain | cover)
--}}
@php
    $wireModel = $wireModel ?? 'image';
    $value     = (string) ($value ?? '');
    $label     = $label ?? '';
    $hint      = $hint ?? '';
    $required  = (bool) ($required ?? false);
    $errorKey  = $errorKey ?? $wireModel;
    $height    = $height ?? 'h-44 sm:h-52';
    $cover     = ($aspect ?? 'cover') === 'cover';
    $fmUrl     = $fmUrl
        ?? (\Illuminate\Support\Facades\Route::has('fm.fm-button')
            ? route('fm.fm-button')
            : url('/file-manager/fm-button'));
@endphp

<div x-data="apMediaPicker(@js($value))"
     @ap-media-selected.window="
        if (window.ApMedia.active !== uid || $event.detail.target !== uid) return;
        window.ApMedia.active = null;
        value = $event.detail.url;
        open = false;
     "
     class="ap-media-picker"
>
    {{-- ورودی مخفی متصل به Livewire — مقدارش با انتخاب FM پر می‌شود --}}
    <input type="hidden" wire:model="{{ $wireModel }}" x-ref="input" value="{{ $value }}">

    @if ($label)
        <div class="mb-2 flex items-center justify-between gap-2">
            <label class="text-xs font-semibold">{{ $label }}</label>
            @if ($required)
                <span class="ap-badge bg-red-50 text-red-600 dark:bg-red-500/12 dark:text-red-400">ضروری</span>
            @endif
        </div>
    @endif
    @if ($hint)
        <p class="mb-2 text-[11px] text-ink-3 dark:text-night-ink-2">{{ $hint }}</p>
    @endif

    <div class="{{ $height }} w-full" x-ref="box">
        {{-- حالت خالی: ناحیه کلیک برای باز کردن فایل‌منیجر --}}
        <button type="button"
                x-show="!value"
                x-on:click="openManager()"
                class="group flex h-full w-full cursor-pointer flex-col items-center justify-center gap-2 rounded-2xl border-2 border-dashed border-line bg-surface-2 transition-colors hover:border-brand-400 hover:bg-brand-50/40 dark:border-night-line dark:bg-night-3 dark:hover:border-brand-500/50 dark:hover:bg-brand-500/5"
                aria-label="انتخاب از رسانه‌های سایت">
            <span class="flex size-11 items-center justify-center rounded-2xl bg-surface-3 text-ink-3 transition-transform group-hover:scale-110 dark:bg-night-2">
                <x-admin-panel::icon name="cloud-upload" class="size-5" />
            </span>
            <span class="text-xs font-semibold">برای انتخاب از رسانه‌های سایت کلیک کنید</span>
            <span class="ap-badge bg-surface-3 text-ink-3 dark:bg-night-2 dark:text-night-ink-2">
                <x-admin-panel::icon name="folder-open" class="size-3" />
                File Manager
            </span>
        </button>

        {{-- حالت پر: پیش‌نمایش تصویر + دکمه‌های تغییر/حذف --}}
        <div x-show="value"
             x-cloak
             class="group relative h-full w-full overflow-hidden rounded-2xl border border-line bg-surface-2 dark:border-night-line dark:bg-night-3">
            <img x-bind:src="window.ApMedia.url(value)"
                 x-show="value && !imgError"
                 x-on:error="imgError = true"
                 alt="پیش‌نمایش"
                 class="size-full {{ $cover ? 'object-cover' : 'object-contain p-2' }}">

            {{-- فایل پیدا نشد (مسیر قدیمی/حذف‌شده) --}}
            <div x-show="imgError" x-cloak class="flex h-full w-full flex-col items-center justify-center gap-1.5 text-ink-3 dark:text-night-ink-2">
                <x-admin-panel::icon name="image-off" class="size-6" />
                <span class="ltr max-w-[90%] truncate font-mono text-[10px]" x-text="value" dir="ltr"></span>
                <span class="text-[11px]">فایل یافت نشد — تصویر را جایگزین کنید</span>
            </div>

            {{-- پوشش دکمه‌ها --}}
            <div class="absolute inset-0 flex items-center justify-center gap-2 bg-slate-950/45 opacity-0 transition-opacity group-hover:opacity-100">
                <button type="button" x-on:click="openManager()"
                        class="flex items-center gap-1.5 rounded-xl bg-white/95 px-3 py-1.5 text-xs font-bold text-slate-800 shadow hover:bg-white">
                    <x-admin-panel::icon name="pencil" class="size-3.5" />
                    تغییر
                </button>
                <button type="button" x-on:click="remove()"
                        class="flex items-center gap-1.5 rounded-xl bg-red-600/95 px-3 py-1.5 text-xs font-bold text-white shadow hover:bg-red-600">
                    <x-admin-panel::icon name="trash-2" class="size-3.5" />
                    حذف
                </button>
            </div>
        </div>
    </div>

    @error($errorKey)
        <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p>
    @enderror

    {{-- مودال فایل‌منیجر (iframe) --}}
    {{-- فاز ۱۶: lg:pr-[17rem] — مودال زیر سایدبار ثابت راست نرود (دکمه بستن همیشه در دسترس) --}}
    <div x-show="open"
         x-cloak
         class="fixed inset-0 z-[70] flex items-center justify-center p-2 sm:p-6 lg:pr-[17rem]"
         role="dialog"
         aria-modal="true"
         aria-label="مدیریت فایل‌ها"
         x-on:keydown.escape.window="open = false">
        <div class="absolute inset-0 bg-slate-950/55 backdrop-blur-sm" x-on:click="open = false" x-transition.opacity aria-hidden="true"></div>

        <div x-show="open"
             x-on:click.stop
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             class="relative flex h-full w-full max-w-6xl flex-col overflow-hidden rounded-2xl border border-line bg-surface shadow-pop dark:border-night-line dark:bg-night-2">

            {{-- سربرگ مودال --}}
            <div class="flex items-center justify-between gap-3 border-b border-line px-4 py-3 dark:border-night-line">
                <div class="flex items-center gap-2">
                    <span class="flex size-9 items-center justify-center rounded-xl bg-brand-50 text-brand-600 dark:bg-brand-500/12 dark:text-brand-400">
                        <x-admin-panel::icon name="folder-open" class="size-4.5" />
                    </span>
                    <div>
                        <h3 class="text-sm font-bold">مدیریت فایل‌های سایت</h3>
                        <p class="text-[11px] text-ink-3 dark:text-night-ink-2">فایل را انتخاب کنید یا از دکمه‌های بالای فایل‌منیجر آپلود کنید</p>
                    </div>
                </div>
                <button type="button" x-on:click="open = false" class="ap-btn-ghost !p-2" title="بستن">
                    <x-admin-panel::icon name="x" class="size-4.5" />
                </button>
            </div>

            {{-- بدنه — فایل‌منیجر --}}
            <div class="relative flex-1">
                <div x-show="open && loading" class="absolute inset-0 z-10 flex flex-col items-center justify-center gap-3 bg-surface dark:bg-night-2">
                    <svg class="size-7 animate-spin text-brand-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                        <path d="M21 12a9 9 0 1 1-6.219-8.56" stroke-linecap="round" />
                    </svg>
                    <p class="text-xs font-semibold text-ink-3 dark:text-night-ink-2">در حال باز کردن مدیریت فایل‌ها…</p>
                </div>

                <iframe x-bind:src="open ? @js($fmUrl) : ''"
                        x-on:load="loading = false"
                        title="File Manager"
                        class="h-full min-h-[420px] w-full bg-white"
                        referrerpolicy="same-origin"></iframe>
            </div>
        </div>
    </div>
</div>
