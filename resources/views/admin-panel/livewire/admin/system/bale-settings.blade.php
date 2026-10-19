{{-- ربات بله — پنل جدید (فاز ۷-ج۳) — پورت BaleSettingsController --}}
@php
    $errors = $errors ?? (new \Illuminate\Support\ViewErrorBag())->put('default', new \Illuminate\Support\MessageBag());
@endphp
<div class="mx-auto max-w-3xl space-y-5">

    {{-- سربرگ --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-2.5">
            <span class="flex size-10 items-center justify-center rounded-xl bg-red-50 text-red-600 dark:bg-red-500/12 dark:text-red-400">
                <x-admin-panel::icon name="send" class="size-5" />
            </span>
            <div>
                <h2 class="text-base font-bold">ربات بله</h2>
                <p class="text-xs text-ink-3 dark:text-night-ink-2">اتصال ربات پیام‌رسان بله برای اطلاع‌رسانی‌های فروشگاه</p>
            </div>
        </div>
        <span class="ap-badge bg-red-50 text-red-600 dark:bg-red-500/12 dark:text-red-400">فقط سازنده</span>
    </div>

    {{-- نتیجه آخرین عملیات --}}
    @if ($lastResult)
        <div class="rounded-2xl border p-4"
             @class(['border-emerald-200 bg-emerald-50 text-emerald-800 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-300' => $lastResult['ok'],
                     'border-red-200 bg-red-50 text-red-700 dark:border-red-500/20 dark:bg-red-500/10 dark:text-red-400' => ! $lastResult['ok']])>
            <p class="flex items-center gap-2 text-[13px] font-bold">
                <x-admin-panel::icon name="{{ $lastResult['ok'] ? 'check-circle' : 'circle-x' }}" class="size-4" />
                {{ $lastResult['kind'] === 'webhook' ? 'وب‌هوک:' : 'تست اتصال:' }}
                {{ $lastResult['ok'] ? 'موفق' : 'ناموفق' }}
            </p>
            <p class="mt-1 break-all text-xs leading-6" dir="auto">{{ $lastResult['message'] }}</p>
        </div>
    @endif

    {{-- توکن + تست اتصال --}}
    <div class="ap-card p-4 sm:p-5">
        <h3 class="mb-3 flex items-center gap-2 text-sm font-bold">
            <x-admin-panel::icon name="key-round" class="size-4 text-brand-600 dark:text-brand-400" />
            توکن ربات
        </h3>
        <form wire:submit="saveToken" class="space-y-3">
            <div>
                <label class="mb-1.5 block text-xs font-semibold" for="bale-token">BALE_BOT_TOKEN</label>
                <input id="bale-token" type="text" dir="ltr" wire:model="botToken" class="ap-input font-mono" placeholder="123456:AAAAAAAA-...">
                @error('botToken') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
            </div>
            <div class="flex flex-wrap items-center gap-2.5">
                <button type="submit" class="ap-btn-primary !py-2.5">
                    <x-admin-panel::icon name="check-circle" class="size-4" />
                    ذخیره توکن
                </button>
                <button type="button" wire:click="testConnection" wire:loading.attr="disabled" wire:target="testConnection"
                        class="ap-btn-outline !py-2.5">
                    <x-admin-panel::icon name="refresh-cw" class="me-1 inline size-4" wire:loading wire:target="testConnection" />
                    تست اتصال
                </button>
            </div>
        </form>
    </div>

    {{-- وب‌هوک --}}
    <div class="ap-card p-4 sm:p-5">
        <h3 class="mb-3 flex items-center gap-2 text-sm font-bold">
            <x-admin-panel::icon name="waypoints" class="size-4 text-brand-600 dark:text-brand-400" />
            وب‌هوک
        </h3>
        <form wire:submit="setWebhook" class="space-y-3">
            <div>
                <label class="mb-1.5 block text-xs font-semibold" for="bale-webhook">آدرس وب‌هوک</label>
                <input id="bale-webhook" type="url" dir="ltr" wire:model="webhookUrl" class="ap-input font-mono" placeholder="https://site.ir/bale/webhook">
                @error('webhookUrl') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
            </div>
            <div class="flex flex-wrap items-center gap-2.5">
                <button type="submit" class="ap-btn-primary !py-2.5">
                    <x-admin-panel::icon name="waypoints" class="size-4" />
                    تنظیم وب‌هوک
                </button>
                <button type="button" wire:click="deleteWebhook" wire:confirm="وب‌هوک حذف شود؟"
                        class="rounded-xl border border-red-200 bg-red-50 px-4 py-2.5 text-[13px] font-bold text-red-700 transition-colors hover:bg-red-100 dark:border-red-500/20 dark:bg-red-500/10 dark:text-red-400">
                    <x-admin-panel::icon name="trash-2" class="me-1 inline size-4" />
                    حذف وب‌هوک
                </button>
            </div>
        </form>
    </div>
</div>
