{{-- Toast های پنل جدید (Alpine) — رویداد ap-toast (JS) و toast (dispatch لایووایر) --}}
<div x-data="apToast"
     x-on:toast.window="ApToast.show($event.detail.message ?? '', { type: $event.detail.type ?? 'info' })"
     class="pointer-events-none fixed bottom-4 start-4 z-[70] flex w-[min(22rem,calc(100vw-2rem))] flex-col gap-2"
     aria-live="polite" aria-atomic="true">
    <template x-for="toast in items" :key="toast.id">
        <div x-show="toast.visible"
             x-transition:enter="transition ease-out duration-250"
             x-transition:enter-start="opacity-0 translate-y-3 scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 scale-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0 translate-x-2"
             class="pointer-events-auto flex items-start gap-3 rounded-2xl border p-3.5 shadow-pop backdrop-blur"
             :class="{
                'border-brand-200 bg-brand-50/95 text-brand-800 dark:border-brand-500/25 dark:bg-brand-950/80 dark:text-brand-200': toast.type === 'success',
                'border-red-200 bg-red-50/95 text-red-700 dark:border-red-500/25 dark:bg-red-950/80 dark:text-red-300': toast.type === 'error',
                'border-line bg-surface/95 text-ink dark:border-night-line dark:bg-night-3/95 dark:text-night-ink': toast.type === 'info',
             }"
             role="status">
            <span class="mt-1.5 size-2 shrink-0 rounded-full"
                  :class="{
                    'bg-brand-500': toast.type === 'success',
                    'bg-red-500': toast.type === 'error',
                    'bg-amber-500': toast.type === 'info',
                  }"></span>
            <span class="min-w-0 flex-1 text-[13px] font-medium leading-5" x-text="toast.message"></span>
            <button type="button" class="shrink-0 rounded-lg p-1 opacity-60 transition-opacity hover:opacity-100"
                    x-on:click="remove(toast)" aria-label="بستن اعلان">
                <x-admin-panel::icon name="x" class="size-4" />
            </button>
        </div>
    </template>
</div>
