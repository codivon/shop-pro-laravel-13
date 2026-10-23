{{-- نتیجه پرداخت پکیج (کال‌بک درگاه) — پورت PackagePaymentController::callback --}}
<div class="mx-auto max-w-md py-16">
    <div class="ap-card p-8 text-center">
        <span class="mx-auto mb-4 flex size-14 items-center justify-center rounded-2xl bg-brand-50 text-brand-600 dark:bg-brand-500/12 dark:text-brand-400">
            <x-admin-panel::icon name="loader-circle" class="size-7 animate-spin" />
        </span>
        <h1 class="text-base font-bold">در حال بررسی نتیجه پرداخت…</h1>
        <p class="mt-2 text-[13px] leading-6 text-ink-2 dark:text-night-ink-2">
            نتیجه پرداخت بررسی و به بازار منتقل می‌شود.
        </p>
        @if ($result)
            <p class="mt-4 rounded-xl bg-surface-2 p-3 text-xs text-ink-2 dark:bg-night-3 dark:text-night-ink-2">
                {{ $result['title'] ?? '' }} — {{ $result['message'] ?? '' }}
            </p>
        @endif
    </div>
</div>
