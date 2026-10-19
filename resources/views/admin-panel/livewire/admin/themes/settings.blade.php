{{-- تنظیمات قالب — پورت back/themes/settings.blade.php (فاز ۷-ج۴) --}}
<div class="mx-auto max-w-6xl space-y-5">

    {{-- سربرگ --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <a href="{{ route('np.themes.index') }}" class="ap-btn-ghost !p-2" title="بازگشت به لیست قالب‌ها">
                <x-admin-panel::icon name="arrow-right" class="size-4" />
            </a>
            <span class="flex size-11 items-center justify-center rounded-2xl bg-brand-50 text-brand-600 dark:bg-brand-500/12 dark:text-brand-400">
                <x-admin-panel::icon name="settings-2" class="size-6" />
            </span>
            <div>
                <h1 class="text-lg font-bold">تنظیمات قالب</h1>
                <p class="text-xs text-ink-3 dark:text-night-ink-2">قالب فعال: {{ config('general.current_theme') }}</p>
            </div>
        </div>
    </div>

    @if (count($this->settingsFields()))
        <form wire:submit="save" class="ap-card p-4 sm:p-5">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                @foreach ($this->settingsFields() as $setting)
                    @switch($setting['input-type'] ?? '')

                        @case('select')
                            <div class="{{ $setting['class'] ?? '' }}">
                                <label class="mb-1.5 block text-xs font-semibold">{{ $setting['title'] }}</label>
                                <select wire:model="settings.{{ $setting['key'] }}" class="ap-input !py-2">
                                    @foreach (($setting['options'] ?? []) as $opt)
                                        <option value="{{ $opt['value'] }}">{{ $opt['title'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            @break

                        @case('input')
                            <div class="{{ $setting['class'] ?? '' }}">
                                <label class="mb-1.5 block text-xs font-semibold">{{ $setting['title'] }}</label>
                                <input type="text" wire:model="settings.{{ $setting['key'] }}" class="ap-input !py-2">
                            </div>
                            @break

                        @case('file')
                            <div class="{{ $setting['class'] ?? '' }}">
                                <label class="mb-1.5 block text-xs font-semibold">{{ $setting['title'] }}</label>
                                <input type="file" wire:model="settingFiles.{{ $setting['key'] }}"
                                       class="block w-full cursor-pointer rounded-xl border border-line bg-transparent text-xs text-ink-2
                                              file:me-3 file:cursor-pointer file:rounded-lg file:border-0 file:bg-brand-600 file:px-3 file:py-1.5
                                              file:text-xs file:font-semibold file:text-white hover:file:bg-brand-700 dark:border-night-line">
                                @if (!empty(option($setting['key'])))
                                    <p class="mt-1 text-[11px] text-ink-3 dark:text-night-ink-2">
                                        فایل فعلی:
                                        <a href="{{ option($setting['key']) }}" target="_blank" rel="noopener" class="ltr text-brand-600 hover:underline">{{ option($setting['key']) }}</a>
                                    </p>
                                @endif
                            </div>
                            @break

                        @case('editor')
                        @case('inline-editor')
                            <div class="md:col-span-2 {{ $setting['class'] ?? '' }}">
                                <label class="mb-1.5 block text-xs font-semibold">{{ $setting['title'] }}</label>
                                <textarea wire:model="settings.{{ $setting['key'] }}" rows="4" class="ap-input !py-2">{{ $settings[$setting['key']] ?? '' }}</textarea>
                            </div>
                            @break

                        @case('linebreak')
                            {{-- جداکننده بخش‌ها (پورت linebreak قدیمی) --}}
                            <div class="md:col-span-2 -my-1 border-t border-line pt-1 dark:border-night-line">
                                {!! $setting['html'] ?? '<h3 class="text-sm font-bold">' . ($setting['title'] ?? '') . '</h3>' !!}
                            </div>
                            @break

                    @endswitch
                @endforeach
            </div>

            <div class="mt-5 flex items-center justify-end gap-3 border-t border-line pt-4 dark:border-night-line">
                <button type="submit" wire:loading.attr="disabled" wire:target="save" class="ap-btn !py-2">
                    <x-admin-panel::icon name="check" class="size-4" />
                    ذخیره تغییرات
                </button>
            </div>
        </form>
    @else
        <div class="ap-card flex flex-col items-center justify-center gap-2 p-10 text-center">
            <span class="flex size-12 items-center justify-center rounded-2xl bg-surface-2 text-ink-3 dark:bg-night-3">
                <x-admin-panel::icon name="settings-2" class="size-6" />
            </span>
            <p class="text-sm font-bold">چیزی برای نمایش وجود ندارد!</p>
            <p class="text-xs text-ink-3 dark:text-night-ink-2">قالب فعال هیچ فیلد تنظیماتی تعریف نکرده است.</p>
        </div>
    @endif
</div>
