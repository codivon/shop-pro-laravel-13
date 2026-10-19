{{-- تنظیمات قالب — پورت back/themes/settings.blade.php (فاز ۷-ج۴) — بازطراحی مدرن فاز ۹-ه (کامپوننت ThemeSettings دست‌نخورده) --}}
@php
    $errors = $errors ?? (new \Illuminate\Support\ViewErrorBag())->put('default', new \Illuminate\Support\MessageBag());
@endphp
<div class="mx-auto max-w-6xl space-y-5">

    {{-- سربرگ --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <a href="{{ route('np.themes.index') }}" class="ap-btn-ghost !p-2" title="بازگشت به لیست قالب‌ها">
                <x-admin-panel::icon name="arrow-right" class="size-4" />
            </a>
            <span class="flex size-11 items-center justify-center rounded-2xl bg-linear-to-br from-brand-500 to-brand-700 text-white shadow-md shadow-brand-500/25">
                <x-admin-panel::icon name="settings-2" class="size-6" />
            </span>
            <div>
                <h1 class="text-lg font-bold">تنظیمات قالب</h1>
                <p class="text-xs text-ink-3 dark:text-night-ink-2">قالب فعال: {{ config('general.current_theme') }}</p>
            </div>
        </div>
        <span class="ap-badge bg-surface-3 text-ink-2 dark:bg-night-3 dark:text-night-ink-2">
            <x-admin-panel::icon name="sliders-horizontal" class="size-3.5" />
            <span class="fa-digits">{{ number_format(count($this->settingsFields())) }}</span> فیلد تنظیماتی
        </span>
    </div>

    @if (count($this->settingsFields()))
        <form wire:submit="save" class="space-y-5">

            {{-- بخش‌بندی فیلدها (linebreak قدیمی = سربرگ بخش) --}}
            <div class="ap-card overflow-hidden p-0">
                <div class="grid grid-cols-1 gap-x-5 gap-y-4 p-4 sm:p-5 md:grid-cols-2">

                    @foreach ($this->settingsFields() as $setting)
                        @switch($setting['input-type'] ?? '')

                            @case('select')
                                @php
                                    $isYesNo = count($setting['options'] ?? []) === 2
                                        && in_array('yes', array_column($setting['options'], 'value'), true)
                                        && in_array('no', array_column($setting['options'], 'value'), true);
                                @endphp
                                <div class="{{ $setting['class'] ?? '' }}">
                                    <label class="mb-1.5 block text-xs font-semibold">{{ $setting['title'] }}</label>

                                    @if ($isYesNo)
                                        {{-- سوییچ دوتایی بله/خیر (همان wire:model API — با $set) --}}
                                        <div class="inline-flex w-full rounded-xl border border-line bg-surface-2 p-1 dark:border-night-line dark:bg-night-3" role="group" aria-label="{{ $setting['title'] }}">
                                            @foreach ($setting['options'] as $opt)
                                                <button type="button"
                                                        wire:click="$set('settings.{{ $setting['key'] }}', '{{ $opt['value'] }}')"
                                                        class="flex-1 rounded-lg px-3 py-1.5 text-xs font-bold transition-all duration-200 {{ ($settings[$setting['key']] ?? '') == $opt['value']
                                                            ? 'bg-surface text-brand-600 shadow-sm dark:bg-night-2 dark:text-brand-400'
                                                            : 'text-ink-3 hover:text-ink-2 dark:text-night-ink-2' }}"
                                                        :aria-pressed="{{ ($settings[$setting['key']] ?? '') == $opt['value'] ? 'true' : 'false' }}">
                                                    {{ $opt['title'] }}
                                                </button>
                                            @endforeach
                                        </div>
                                    @else
                                        <select wire:model="settings.{{ $setting['key'] }}" class="ap-input !py-2">
                                            @foreach (($setting['options'] ?? []) as $opt)
                                                <option value="{{ $opt['value'] }}">{{ $opt['title'] }}</option>
                                            @endforeach
                                        </select>
                                    @endif
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
                                    <div class="rounded-xl border border-line bg-surface-2 p-3 transition-colors focus-within:border-brand-500/60 dark:border-night-line dark:bg-night-3/60">
                                        <input type="file" wire:model="settingFiles.{{ $setting['key'] }}"
                                               class="block w-full cursor-pointer bg-transparent text-xs text-ink-2
                                                      file:me-3 file:cursor-pointer file:rounded-lg file:border-0 file:bg-brand-600 file:px-3 file:py-1.5
                                                      file:text-xs file:font-semibold file:text-white hover:file:bg-brand-700">
                                        @if (!empty(option($setting['key'])))
                                            <p class="mt-2 flex items-center gap-2 border-t border-line pt-2 text-[11px] text-ink-3 dark:border-night-line dark:text-night-ink-2">
                                                <x-admin-panel::icon name="check-circle" class="size-3.5 shrink-0 text-emerald-500" />
                                                فایل فعلی:
                                                @if (preg_match('/\.(png|jpe?g|gif|webp|svg)$/i', (string) option($setting['key'])))
                                                    <img src="{{ asset(option($setting['key'])) }}" alt="{{ $setting['title'] }}"
                                                         class="size-9 rounded-lg border border-line object-cover dark:border-night-line">
                                                @endif
                                                <a href="{{ option($setting['key']) }}" target="_blank" rel="noopener" class="ltr truncate text-brand-600 hover:underline dark:text-brand-400">{{ option($setting['key']) }}</a>
                                            </p>
                                        @endif
                                    </div>
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
                                <div class="md:col-span-2 -my-1 flex items-center gap-3 border-t border-line pt-4 dark:border-night-line">
                                    <span class="flex size-7 items-center justify-center rounded-lg bg-brand-50 text-brand-600 dark:bg-brand-500/12 dark:text-brand-400">
                                        <x-admin-panel::icon name="settings" class="size-3.5" />
                                    </span>
                                    {!! $setting['html'] ?? '<h3 class="text-sm font-bold">' . ($setting['title'] ?? '') . '</h3>' !!}
                                    <span class="h-px flex-1 bg-line dark:bg-night-line" aria-hidden="true"></span>
                                </div>
                                @break

                        @endswitch
                    @endforeach
                </div>

                {{-- نوار ذخیره چسبان پایین --}}
                <div class="sticky bottom-0 flex items-center justify-between gap-3 border-t border-line bg-surface/90 px-4 py-3 backdrop-blur sm:px-5 dark:border-night-line dark:bg-night-2/90">
                    <p class="text-[11px] text-ink-3 dark:text-night-ink-2">تغییرات پس از ذخیره، مستقیماً روی فروشگاه اعمال می‌شود.</p>
                    <button type="submit" wire:loading.attr="disabled" wire:target="save" class="ap-btn !py-2">
                        <x-admin-panel::icon name="check" class="size-4" />
                        ذخیره تغییرات
                    </button>
                </div>
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
