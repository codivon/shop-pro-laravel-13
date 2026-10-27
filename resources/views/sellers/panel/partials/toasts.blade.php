{{-- Toasts پنل فروشندگان مدرن (فاز ۱۰۸) — درایور spToast داخل layouts/panel.blade.php
     تعریف شده است (سشن toast-success/error/warning + رویداد Livewire 'toast'). --}}
<div x-data="spToast" x-init="init()" class="pointer-events-none fixed inset-x-0 top-4 z-[100] flex flex-col items-center gap-2 px-4"
     role="status" aria-live="polite">
    <template x-for="t in items" :key="t.id">
        <div x-show="t.visible" x-cloak
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 -translate-y-2"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0 -translate-y-2"
             class="pointer-events-auto flex w-full max-w-md items-start gap-2.5 rounded-2xl border p-3.5 shadow-lg backdrop-blur-md"
             x-bind:class="t.type === 'success' ? 'border-emerald-200 bg-emerald-50/95 text-emerald-800 dark:border-emerald-500/30 dark:bg-emerald-950/90 dark:text-emerald-200'
                                       : (t.type === 'error' ? 'border-red-200 bg-red-50/95 text-red-800 dark:border-red-500/30 dark:bg-red-950/90 dark:text-red-200'
                                       : (t.type === 'warning' ? 'border-amber-200 bg-amber-50/95 text-amber-800 dark:border-amber-500/30 dark:bg-amber-950/90 dark:text-amber-200'
                                       : 'border-line bg-surface/95 text-ink dark:border-night-line dark:bg-night-2/95 dark:text-night-ink'))">
            <span class="mt-0.5 shrink-0">
                <x-admin-panel::icon name="check-circle-2" class="size-5" x-show="t.type === 'success'" />
                <x-admin-panel::icon name="circle-alert" class="size-5" x-show="t.type === 'error'" x-cloak />
                <x-admin-panel::icon name="triangle-alert" class="size-5" x-show="t.type === 'warning'" x-cloak />
                <x-admin-panel::icon name="info" class="size-5" x-show="! ['success','error','warning'].includes(t.type)" x-cloak />
            </span>
            <p class="min-w-0 flex-1 text-[13px] font-bold leading-6" x-text="t.message"></p>
            <button type="button" class="shrink-0 rounded-lg p-1 opacity-60 transition-opacity hover:opacity-100"
                    x-on:click="removeById(t.id)" aria-label="بستن پیام">
                <x-admin-panel::icon name="x" class="size-4" />
            </button>
        </div>
    </template>
</div>
