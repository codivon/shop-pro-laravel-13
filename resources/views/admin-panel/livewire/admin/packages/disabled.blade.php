{{-- فاز ۹۹ — صفحهٔ بازار وقتی کلید اصلی «packages_market_enabled» خاموش است
     پیام تمیز فارسی به‌جای لیست بازار (نه کرش/۴۰۳) — تصمیم مستند:
     مدیریت «ماژول‌های نصب‌شده» جداگانه و همیشه در دسترس می‌ماند. --}}
<div class="mx-auto max-w-3xl space-y-5">

    {{-- سربرگ --}}
    <div class="flex items-center gap-3">
        <span class="flex size-11 items-center justify-center rounded-2xl bg-brand-50 text-brand-600 dark:bg-brand-500/12 dark:text-brand-400">
            <x-admin-panel::icon name="package" class="size-6" />
        </span>
        <div>
            <h1 class="text-lg font-bold">بازار</h1>
            <p class="text-xs text-ink-3 dark:text-night-ink-2">پکیج‌ها و قالب‌ها</p>
        </div>
    </div>

    {{-- پیام غیرفعال بودن --}}
    <div class="ap-card flex flex-col items-center gap-3 p-10 text-center">
        <span class="flex size-14 items-center justify-center rounded-2xl bg-surface-2 text-ink-3 dark:bg-night-3 dark:text-night-ink-2">
            <x-admin-panel::icon name="package" class="size-7" />
        </span>
        <p class="text-base font-bold">بازارچه از تنظیمات غیرفعال شده است.</p>
        <p class="max-w-md text-[13px] leading-6 text-ink-3 dark:text-night-ink-2">
            این بخش توسط مدیر سایت از تنظیمات برنامه‌نویس (تب «بازارچه») خاموش شده است.
            ماژول‌های نصب‌شده همچنان از صفحهٔ «ماژول‌های نصب‌شده» قابل مدیریت هستند.
        </p>
        <a href="{{ route('np.packages.installed') }}" class="ap-btn-outline mt-1 !py-2">
            <x-admin-panel::icon name="package-check" class="size-4" />
            ماژول‌های نصب‌شده
        </a>
    </div>
</div>
