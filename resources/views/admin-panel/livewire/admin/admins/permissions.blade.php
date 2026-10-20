{{-- مدیریت دسترسی‌ها — فاز ۱۷: بازطراحی مدرن
     حالت انتخاب سمت کلاینت (Alpine) نگه‌داری می‌شود تا شمارنده/کسکید/جستجو فوری باشند؛
     هنگام ذخیره با $wire.set (deferred) همراه اکشن save ارسال می‌شود — منطق سرور دست‌نخورده --}}
<div class="mx-auto max-w-6xl space-y-5"
     x-data="apPermissions({
         initial: @js(array_map('intval', $this->permission)),
         kids: @js($this->tree->mapWithKeys(fn ($p) => [
             $p->id => $p->permissions->pluck('id')->map(fn ($id) => (int) $id)->all()
         ])),
         titles: @js($this->tree->flatMap(fn ($p) => [
             $p->id => $p->title,
             ...$p->permissions->mapWithKeys(fn ($c) => [$c->id => $c->title])
         ])),
     })">

    {{-- ============ سربرگ ============ --}}
    <div class="relative overflow-hidden rounded-2xl border border-line bg-surface p-5 dark:border-night-line dark:bg-night-2 sm:p-6">
        <div class="pointer-events-none absolute inset-0 bg-gradient-to-l from-brand-500/10 via-transparent to-violet-500/10" aria-hidden="true"></div>
        <div class="relative flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <span class="flex size-12 items-center justify-center rounded-2xl bg-gradient-to-br from-brand-500 to-violet-600 text-white shadow-lg shadow-brand-500/25">
                    <x-admin-panel::icon name="shield" class="size-6" />
                </span>
                <div>
                    <h1 class="text-lg font-extrabold">مدیریت دسترسی‌ها</h1>
                    <p class="text-xs text-ink-3 dark:text-night-ink-2">
                        فقط دسترسی‌های فعال در کل پنل (گیت‌ها و منو) در دسترس مدیران قرار می‌گیرند — این صفحه مخصوص مدیر کل است.
                    </p>
                </div>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <span class="fa-digits rounded-xl border border-line bg-surface-2 px-3 py-1.5 text-xs font-bold text-ink-2 dark:border-night-line dark:bg-night-3 dark:text-night-ink-2">
                    <span x-text="ApFormat.faDigits(perms.length)"></span>
                    دسترسی فعال
                </span>
                <button type="button" x-on:click="selectAll()" class="ap-btn-ghost !py-2">
                    <x-admin-panel::icon name="check-check" class="size-4" />
                    انتخاب همه
                </button>
                <button type="button" x-on:click="clearAll()" class="ap-btn-ghost !py-2 !text-red-600 dark:!text-red-400">
                    <x-admin-panel::icon name="square-x" class="size-4" />
                    حذف انتخاب‌ها
                </button>
            </div>
        </div>
    </div>

    @error('permission')
        <div class="rounded-xl border border-red-200 bg-red-50 p-3 text-[13px] font-medium text-red-600 dark:border-red-500/20 dark:bg-red-500/10">
            {{ $message }}
        </div>
    @enderror

    {{-- ============ جستجو ============ --}}
    <div class="ap-card p-4">
        <div class="relative">
            <x-admin-panel::icon name="search" class="pointer-events-none absolute end-3.5 top-1/2 size-4 -translate-y-1/2 text-ink-3" />
            <input type="text" x-model="q" class="ap-input !pe-10" placeholder="جستجو در دسترسی‌ها (گروه یا عنوان)…" aria-label="جستجوی دسترسی‌ها">
        </div>
    </div>

    {{-- ============ درخت دسترسی‌ها ============ --}}
    <div class="grid grid-cols-1 gap-3 lg:grid-cols-2">
        @foreach ($this->tree as $permission)
            <div class="ap-card overflow-hidden" wire:key="perm-{{ $permission->id }}"
                 x-data="{ open: @js(in_array($permission->id, array_map('intval', $this->permission))) }"
                 x-show="match(@js($permission->title)) || kids({{ $permission->id }}).some((id) => match(titles[id]))"
                 x-bind:data-group="{{ $permission->id }}">

                {{-- هدر گروه --}}
                <div class="flex items-center gap-2.5 p-4">
                    <button type="button"
                            x-on:click="toggleParent({{ $permission->id }})"
                            class="relative flex size-5.5 shrink-0 items-center justify-center rounded-md border-2 transition-all"
                            x-bind:class="isChecked({{ $permission->id }})
                                ? 'border-brand-600 bg-brand-600 text-white'
                                : 'border-line bg-surface hover:border-brand-400 dark:border-night-line dark:bg-night-3'"
                            role="checkbox" x-bind:aria-checked="isChecked({{ $permission->id }}).toString()"
                            x-bind:aria-label="@js($permission->title)">
                        <x-admin-panel::icon name="check" class="size-3.5" x-show="isChecked({{ $permission->id }})" />
                        <span class="absolute size-1.5 rounded-full bg-brand-500" x-show="! isChecked({{ $permission->id }}) && childCount({{ $permission->id }}) > 0"></span>
                    </button>

                    <button type="button" class="flex min-w-0 flex-1 items-center gap-2 text-start" x-on:click="open = ! open"
                            x-bind:disabled="kids({{ $permission->id }}).length === 0">
                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-[13px] font-bold">{{ $permission->title }}</span>
                            <span class="fa-digits text-[11px] text-ink-3 dark:text-night-ink-2">
                                <span x-text="ApFormat.faDigits(childCount({{ $permission->id }}))"></span> از <span x-text="ApFormat.faDigits(kids({{ $permission->id }}).length)"></span> فعال
                            </span>
                        </span>
                    </button>

                    @if ($permission->permissions->isNotEmpty())
                        <span class="fa-digits rounded-lg px-2 py-0.5 text-[10px] font-bold"
                              x-bind:class="childCount({{ $permission->id }}) > 0 ? 'bg-brand-50 text-brand-700 dark:bg-brand-500/12 dark:text-brand-400' : 'bg-surface-2 text-ink-3 dark:bg-night-3 dark:text-night-ink-2'"
                              x-text="ApFormat.faDigits(childCount({{ $permission->id }})) + '/' + ApFormat.faDigits(kids({{ $permission->id }}).length)"></span>
                        <button type="button" class="rounded-lg p-1 text-ink-3 transition-colors hover:bg-surface-2 dark:hover:bg-night-3"
                                x-on:click="open = ! open" aria-label="باز و بسته کردن گروه">
                            <span class="ap-chevron block" x-bind:class="open ? 'is-open' : ''">
                                <x-admin-panel::icon name="chevron-down" class="size-4" />
                            </span>
                        </button>
                    @endif
                </div>

                {{-- فرزندان --}}
                @if ($permission->permissions->isNotEmpty())
                    <div x-show="open" x-cloak
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
                         class="border-t border-line p-4 dark:border-night-line">
                        <div class="flex flex-wrap items-center gap-1.5">
                            @foreach ($permission->permissions as $item)
                                <button type="button" wire:key="perm-child-{{ $item->id }}"
                                        x-on:click="toggle({{ $item->id }})"
                                        x-show="match(@js($item->title))"
                                        class="flex items-center gap-1.5 rounded-full border px-3 py-1.5 text-[12px] font-semibold transition-all"
                                        x-bind:class="isChecked({{ $item->id }})
                                            ? 'border-brand-500/40 bg-brand-50 text-brand-700 dark:bg-brand-500/12 dark:text-brand-400'
                                            : 'border-line bg-surface text-ink-2 hover:border-brand-300 dark:border-night-line dark:bg-night-3 dark:text-night-ink-2'">
                                    <span class="flex size-3.5 items-center justify-center rounded-full transition-colors"
                                          x-bind:class="isChecked({{ $item->id }}) ? 'bg-brand-600 text-white' : 'border border-line dark:border-night-line'">
                                        <x-admin-panel::icon name="check" class="size-2.5" x-show="isChecked({{ $item->id }})" />
                                    </span>
                                    <span class="max-w-48 truncate">{{ $item->title }}</span>
                                </button>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        @endforeach
    </div>

    {{-- ============ نوار ذخیره ============ --}}
    <div class="sticky bottom-4 z-20 flex items-center justify-between gap-3 rounded-2xl border border-line bg-surface/95 p-3.5 shadow-lg backdrop-blur dark:border-night-line dark:bg-night-2/95">
        <p class="text-xs text-ink-3 dark:text-night-ink-2">
            <span class="fa-digits font-bold text-ink-2 dark:text-night-ink" x-text="ApFormat.faDigits(perms.length)"></span>
            دسترسی انتخاب شده — با ذخیره، بقیه غیرفعال می‌شوند (مثل رفتار قدیمی).
        </p>
        <button type="button" x-on:click="save()" wire:loading.attr="disabled" wire:target="save" class="ap-btn-primary !py-2.5">
            <span wire:loading.remove wire:target="save" class="flex items-center gap-2">
                <x-admin-panel::icon name="check" class="size-4" />
                ذخیره تغییرات
            </span>
            <span wire:loading wire:target="save" class="flex items-center gap-2">
                <svg class="size-4 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                    <path d="M21 12a9 9 0 1 1-6.219-8.56" stroke-linecap="round" />
                </svg>
                در حال ذخیره…
            </span>
        </button>
    </div>
</div>
