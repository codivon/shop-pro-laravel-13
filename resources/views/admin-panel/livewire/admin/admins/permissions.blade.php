{{-- مدیریت دسترسی‌ها — پنل جدید (فاز ۷-ج) — پورت ۱:۱ back/permissions/index.blade.php + PermissionController --}}
@php
    $errors = $errors ?? (new \Illuminate\Support\ViewErrorBag())->put('default', new \Illuminate\Support\MessageBag());
@endphp
<div class="mx-auto max-w-6xl space-y-5">

    {{-- سربرگ --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-base font-extrabold">مدیریت دسترسی ها</h1>
            <p class="mt-1 text-xs text-ink-3 dark:text-night-ink-2">
                فقط دسترسی‌های فعال در کل پنل (گیت‌ها و منو) در دسترس مدیران قرار می‌گیرند — این صفحه مخصوص مدیر کل است.
            </p>
        </div>
        <button type="button" wire:click="save" wire:loading.attr="disabled" wire:target="save" class="ap-btn !py-2.5">
            <x-admin-panel::icon name="check" class="size-4" />
            ذخیره تغییرات
        </button>
    </div>

    @error('permission')
        <div class="rounded-xl border border-red-200 bg-red-50 p-3 text-[13px] font-medium text-red-600 dark:border-red-500/20 dark:bg-red-500/10">
            {{ $message }}
        </div>
    @enderror

    {{-- درخت دسترسی‌ها (پورت فرم قدیمی: کارت جمع‌شونده برای هر گروه) --}}
    <div class="space-y-3">
        @foreach ($this->tree as $permission)
            <div class="ap-card overflow-hidden" x-data="{ open: @js(in_array($permission->id, array_map('intval', $this->permission))) }">
                <div class="flex items-center gap-2 p-3.5">
                    <label class="flex flex-1 cursor-pointer items-center gap-2 text-[13px] font-bold">
                        {{-- والد: مثل JS قدیمی با تغییر تیک، فرزندان هم تیک می‌خورند و کارت باز می‌شود --}}
                        <input type="checkbox"
                               :checked="$wire.permission.map(Number).includes({{ $permission->id }})"
                               x-on:change="open = $event.target.checked; $wire.toggleParent({{ $permission->id }})"
                               class="size-4 rounded-md border-line text-brand-600 focus:ring-brand-500/30 dark:border-night-line">
                        {{ $permission->title }}
                    </label>
                    @if ($permission->permissions->isNotEmpty())
                        <button type="button" class="rounded-lg p-1 text-ink-3 transition-colors hover:bg-surface-2 dark:hover:bg-night-3" x-on:click="open = !open" aria-label="باز و بسته کردن">
                            <span class="ap-chevron block" :class="open ? 'is-open' : ''">
                                <x-admin-panel::icon name="chevron-down" class="size-4" />
                            </span>
                        </button>
                    @endif
                </div>

                @if ($permission->permissions->isNotEmpty())
                    <div x-show="open" x-cloak
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
                         class="border-t border-line px-3.5 py-3.5 dark:border-night-line">
                        <div class="grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-4">
                            @foreach ($permission->permissions as $item)
                                <label class="flex cursor-pointer items-center gap-2 rounded-lg px-2 py-1.5 text-[13px] transition-colors hover:bg-surface-2 dark:hover:bg-night-3">
                                    <input type="checkbox" wire:model="permission" value="{{ $item->id }}"
                                           @checked(in_array($item->id, array_map('intval', $this->permission)))
                                           class="size-4 rounded-md border-line text-brand-600 focus:ring-brand-500/30 dark:border-night-line">
                                    <span class="truncate">{{ $item->title }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        @endforeach
    </div>

    {{-- نوار ذخیره پایین (پورت دکمه فرم قدیمی) --}}
    <div class="ap-card sticky bottom-4 z-20 flex items-center justify-between gap-3 p-3.5">
        <p class="text-xs text-ink-3 dark:text-night-ink-2">
            <span class="fa-digits">{{ number_format(count($this->permission)) }}</span> دسترسی انتخاب شده — با ذخیره، بقیه غیرفعال می‌شوند (مثل رفتار قدیمی).
        </p>
        <button type="button" wire:click="save" wire:loading.attr="disabled" wire:target="save" class="ap-btn !py-2.5">
            <x-admin-panel::icon name="check" class="size-4" />
            ذخیره تغییرات
        </button>
    </div>

    {{-- لودینگ --}}
    <div wire:loading wire:target="save, toggleParent"
         class="pointer-events-none fixed bottom-6 start-6 z-[65] flex items-center gap-2 rounded-xl border border-line bg-surface px-4 py-2.5 shadow-pop dark:border-night-line dark:bg-night-3">
        <svg class="size-4 animate-spin text-brand-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
            <path d="M21 12a9 9 0 1 1-6.219-8.56" stroke-linecap="round" />
        </svg>
        <span class="text-[13px] font-semibold">در حال بارگذاری…</span>
    </div>
</div>
