{{--
    فاز ۱۱۴ — انتخابگر آیکون (Icon Picker)
    دکمه‌ای کنار فیلد آیکون که مودال بزرگ نمایش آیکون‌ها را باز می‌کند:
      • تب‌ها: Font Awesome / Material Design (MDI) / Lucide
      • جستجوی زنده در هر تب
      • کلیک روی هر آیکون → بخش دوم (رنگ + اندازه + پیش‌نمایش)
      • تایید → مقدار نهایی در فیلد مقصد نوشته و رخداد input/change منتشر می‌شود

    متغیرها:
      for   : id فیلد متنی مقصد (اجباری)
      mode  : class | html  (پیش‌فرض class)
              class → «کلاس» یا «کلاس|style»  (فیلدهای کلاس‌محور: منوها، MDI …)
              html  → SVG کامل Lucide یا <i> فونت‌آیکون (فیلدهای HTML-محور: لندینگ فروشندگان)

    ⚠️ سوییچ برنامه‌نویس: admin_icon_picker (تب چندفروشندگی تنظیمات برنامه‌نویس)
       خاموش → این کامپوننت هیچ چیزی رندر نمی‌کند و فیلد متن ساده می‌ماند.
--}}
@php
    $for = (string) $for;
    $mode = ($mode ?? 'class') === 'html' ? 'html' : 'class';
    $pickerOn = \App\Support\IconRegistry::pickerEnabled();
@endphp
@if ($pickerOn && $for !== '')
    <button type="button"
            x-data
            x-on:click="$dispatch('open-ap-icon-picker', { target: '{{ $for }}', mode: '{{ $mode }}' })"
            class="ap-btn-outline inline-flex shrink-0 items-center gap-1.5 rounded-xl px-3! text-xs font-bold"
            aria-haspopup="dialog" aria-label="انتخاب آیکون">
        <x-admin-panel::icon name="shapes" class="size-4" />
        انتخاب
    </button>
@endif

@if ($pickerOn && ! \App\View\Components\AdminPanel\IconPickerModal::$rendered)
    @php \App\View\Components\AdminPanel\IconPickerModal::$rendered = true; @endphp
    <x-admin-panel::icon-picker-modal />
@endif
