{{-- سوییچ مدرن (Toggle) پنل جدید — فاز ۴۵
     جایگزین چک‌باکس خام مرورگر. ورودی واقعی checkbox با sr-only نگه داشته می‌شود
     (سازگاری کامل با wire:model و فرم‌ها) و ظاهر با peer-checked ساخته می‌شود —
     یعنی بازخورد بصری «بدون رفت‌وبرگشت سرور» همان لحظه تغییر می‌کند.

     استفاده:
         <x-admin-panel::toggle wire:model="someBoolProperty" />
     کلاس/اتریبوت اضافه روی «ورودی» می‌نشیند (aria-label و ...) و برچسب کل،
     ناحیه‌ی کلیک است. اندازه: پیش‌فرض md — برای نواحی فشرده size="sm". --}}
@props([
    'disabled' => false,
    'size' => 'md',
])

<label class="inline-flex shrink-0 cursor-pointer items-center {{ $disabled ? 'pointer-events-none opacity-45' : '' }}">
    <input
        type="checkbox"
        @if ($disabled) disabled @endif
        {{ $attributes->merge(['class' => 'peer sr-only']) }}
    >
    <span class="relative inline-flex {{ $size === 'sm' ? 'h-5 w-9' : 'h-6 w-11' }} items-center rounded-full bg-ink-2/20 transition-colors duration-300 peer-checked:bg-brand-600 peer-focus-visible:ring-2 peer-focus-visible:ring-brand-500/40 peer-focus-visible:ring-offset-2 ring-offset-surface dark:bg-night-line dark:ring-offset-night-2" aria-hidden="true">
        <span class="absolute top-1/2 -translate-y-1/2 start-0.5 {{ $size === 'sm' ? 'size-4' : 'size-5' }} rounded-full bg-white shadow-md shadow-slate-900/15 ring-1 ring-slate-900/5 transition-[inset-inline-start] duration-300 ease-[cubic-bezier(0.34,1.56,0.64,1)] {{ $size === 'sm' ? 'peer-checked:start-[calc(100%_-_1.125rem)]' : 'peer-checked:start-[calc(100%_-_1.375rem)]' }}"></span>
    </span>
</label>
