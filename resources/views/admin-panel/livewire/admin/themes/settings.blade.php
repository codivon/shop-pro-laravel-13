{{-- تنظیمات قالب — پورت back/themes/settings.blade.php — بازطراحی مدرن فاز ۱۱ --}}
{{-- فیلدها بر اساس «linebreak» (سربرگ بخش قدیمی) به کارت‌های مستقل بخش‌بندی می‌شوند --}}
@php
    $errors = $errors ?? (new \Illuminate\Support\ViewErrorBag())->put('default', new \Illuminate\Support\MessageBag());

    // بخش‌بندی: هر linebreak = آغاز بخش جدید؛ فیلدهای قبل از اولین linebreak = بخش «عمومی»
    $sections = [];
    $currentTitle = 'تنظیمات عمومی';
    $currentFields = [];

    foreach ($this->settingsFields() as $setting) {
        if (($setting['input-type'] ?? '') === 'linebreak') {
            if (! empty($currentFields)) {
                $sections[] = ['title' => $currentTitle, 'html' => null, 'fields' => $currentFields];
            }
            $currentTitle = trim(strip_tags($setting['title'] ?? '')) ?: 'تنظیمات';
            $currentHtml = $setting['html'] ?? null;
            $currentFields = [];
            // عنوان بخش با html سفارشی قدیمی هم پشتیبانی می‌شود
            $sections[] = ['title' => $currentTitle, 'html' => $currentHtml, 'fields' => null, 'header_only' => true];
            continue;
        }
        $currentFields[] = $setting;
    }
    if (! empty($currentFields)) {
        $sections[] = ['title' => $currentTitle, 'html' => null, 'fields' => $currentFields];
    }

    // ادغام header_only با بخش بعدی (سربرگ سفارشی + فیلدهایش در یک کارت)
    $final = [];
    $count = count($sections);
    for ($i = 0; $i < $count; $i++) {
        $sec = $sections[$i];
        if (! empty($sec['header_only']) && isset($sections[$i + 1]) && empty($sections[$i + 1]['header_only'])) {
            $next = $sections[$i + 1];
            $final[] = [
                'title'  => $sec['html'] ? null : $sec['title'],
                'html'   => $sec['html'],
                'fields' => $next['fields'],
            ];
            $i++;
        } elseif (empty($sec['header_only'])) {
            $final[] = $sec;
        }
    }

    $sectionIcons = ['sliders-horizontal', 'palette', 'layout-panel-top', 'shopping-bag', 'newspaper', 'phone', 'share-2', 'settings'];
@endphp
<div class="mx-auto max-w-6xl space-y-5">

    {{-- سربرگ --}}
    <div class="relative overflow-hidden rounded-3xl bg-linear-to-bl from-brand-700 via-brand-600 to-brand-500 p-5 text-white shadow-lg shadow-brand-600/20 sm:p-6">
        <span class="pointer-events-none absolute -top-10 -left-10 size-44 rounded-full bg-white/10 blur-2xl" aria-hidden="true"></span>
        <span class="pointer-events-none absolute -bottom-14 left-1/3 size-52 rounded-full bg-white/10 blur-2xl" aria-hidden="true"></span>

        <div class="relative flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-3.5">
                <a href="{{ route('np.themes.index') }}"
                   class="flex size-11 items-center justify-center rounded-2xl bg-white/15 shadow-inner backdrop-blur transition-transform hover:scale-105"
                   title="بازگشت به لیست قالب‌ها" aria-label="بازگشت به لیست قالب‌ها">
                    <x-admin-panel::icon name="arrow-right" class="size-5" />
                </a>
                <span class="flex size-13 items-center justify-center rounded-2xl bg-white/15 shadow-inner backdrop-blur">
                    <x-admin-panel::icon name="settings-2" class="size-7" />
                </span>
                <div>
                    <h1 class="text-lg font-bold sm:text-xl">تنظیمات قالب</h1>
                    <p class="mt-1 flex flex-wrap items-center gap-2 text-xs text-white/85">
                        <span>قالب فعال:</span>
                        <span class="rounded-full bg-white/15 px-2 py-0.5 text-[10px] font-bold">{{ config('general.current_theme') }}</span>
                        <span class="rounded-full bg-white/15 px-2 py-0.5 text-[10px] font-bold">
                            <span class="fa-digits">{{ number_format(count($this->settingsFields())) }}</span> فیلد
                        </span>
                    </p>
                </div>
            </div>
            <p class="hidden text-[11px] leading-6 text-white/80 sm:block sm:max-w-64">
                تغییرات پس از ذخیره، مستقیماً روی فروشگاه اعمال می‌شود.
            </p>
        </div>
    </div>

    @if (count($this->settingsFields()))
        <form wire:submit="save" class="space-y-5">

            {{-- ===== کارت‌های بخش‌بندی‌شده ===== --}}
            @foreach ($final as $si => $section)
                <section class="ap-card overflow-hidden p-0">
                    {{-- سربرگ بخش --}}
                    <div class="flex items-center gap-2.5 border-b border-line bg-surface-2/50 px-4 py-3 dark:border-night-line dark:bg-night-3/40">
                        <span class="flex size-8 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-600 dark:bg-brand-500/12 dark:text-brand-400">
                            <x-admin-panel::icon name="{{ $sectionIcons[$si % count($sectionIcons)] }}" class="size-4" />
                        </span>
                        @if ($section['html'])
                            {!! $section['html'] !!}
                        @else
                            <h2 class="text-[13px] font-bold">{{ $section['title'] }}</h2>
                        @endif
                        <span class="ms-auto rounded-full bg-surface-3 px-2 py-0.5 text-[10px] font-bold text-ink-3 dark:bg-night-3 dark:text-night-ink-2">
                            <span class="fa-digits">{{ number_format(count($section['fields'])) }}</span> فیلد
                        </span>
                    </div>

                    {{-- فیلدهای بخش --}}
                    <div class="grid grid-cols-1 gap-x-5 gap-y-4 p-4 sm:p-5 md:grid-cols-2">
                        @foreach ($section['fields'] as $setting)
                            @switch($setting['input-type'] ?? '')

                                @case('select')
                                    @php
                                        $isYesNo = count($setting['options'] ?? []) === 2
                                            && in_array('yes', array_column($setting['options'], 'value'), true)
                                            && in_array('no', array_column($setting['options'], 'value'), true);
                                    @endphp
                                    <div class="{{ ap_clean_legacy_class($setting['class'] ?? null) }} min-w-0">
                                        <label class="mb-1.5 block text-xs font-semibold">{{ $setting['title'] }}</label>

                                        @if ($isYesNo)
                                            {{-- سوییچ دوتایی بله/خیر — فاز ۱۶: عرض کامل و هم‌عرض با فیلدهای متن --}}
                                            <div class="flex w-full rounded-xl border border-line bg-surface-2 p-1 dark:border-night-line dark:bg-night-3" role="group" aria-label="{{ $setting['title'] }}">
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
                                            <select wire:model="settings.{{ $setting['key'] }}" class="ap-input w-full !py-2">
                                                @foreach (($setting['options'] ?? []) as $opt)
                                                    <option value="{{ $opt['value'] }}">{{ $opt['title'] }}</option>
                                                @endforeach
                                            </select>
                                        @endif
                                    </div>
                                    @break

                                @case('input')
                                    <div class="{{ ap_clean_legacy_class($setting['class'] ?? null) }} min-w-0">
                                        <label class="mb-1.5 block text-xs font-semibold">{{ $setting['title'] }}</label>
                                        <input type="text" wire:model="settings.{{ $setting['key'] }}" class="ap-input w-full !py-2">
                                    </div>
                                    @break

                                @case('file')
                                    {{-- فاز ۱۶: فیلدهای تصویری قالب از رسانه‌های سایت (فایل‌منیجر) انتخاب می‌شوند — هم‌راستا با درخواست «تصاویر از فایل‌های خود سایت» --}}
                                    <div class="md:col-span-2 {{ ap_clean_legacy_class($setting['class'] ?? null) }} min-w-0">
                                        <label class="mb-1.5 block text-xs font-semibold">{{ $setting['title'] }}</label>
                                        <x-admin-panel::media-picker
                                            wireModel="settings.{{ $setting['key'] }}"
                                            :value="$settings[$setting['key']] ?? ''"
                                            hint="تصویر را از رسانه‌های سایت انتخاب کنید — می‌توانید فایل جدید هم آپلود کنید"
                                            height="h-40 sm:h-48"
                                        />
                                    </div>
                                    @break

                                @case('editor')
                                @case('inline-editor')
                                    <div class="md:col-span-2 {{ ap_clean_legacy_class($setting['class'] ?? null) }}">
                                        <label class="mb-1.5 block text-xs font-semibold">{{ $setting['title'] }}</label>
                                        <textarea wire:model="settings.{{ $setting['key'] }}" rows="4" class="ap-input !py-2">{{ $settings[$setting['key']] ?? '' }}</textarea>
                                    </div>
                                    @break

                            @endswitch
                        @endforeach
                    </div>
                </section>
            @endforeach

            {{-- نوار ذخیره چسبان پایین --}}
            <div class="sticky bottom-4 z-30">
                <div class="flex items-center justify-between gap-3 rounded-2xl border border-line bg-surface/95 px-4 py-3 shadow-pop backdrop-blur sm:px-5 dark:border-night-line dark:bg-night-2/95">
                    <p class="flex items-center gap-2 text-[11px] text-ink-3 dark:text-night-ink-2">
                        <x-admin-panel::icon name="info" class="size-4 shrink-0" />
                        <span class="hidden sm:inline">تغییرات پس از ذخیره، مستقیماً روی فروشگاه اعمال می‌شود.</span>
                        <span class="sm:hidden">ذخیره = اعمال روی فروشگاه</span>
                    </p>
                    <button type="submit" wire:loading.attr="disabled" wire:target="save" class="ap-btn-primary !py-2">
                        <span wire:loading.remove wire:target="save" class="inline-flex items-center gap-2">
                            <x-admin-panel::icon name="check" class="size-4" />
                            ذخیره تغییرات
                        </span>
                        <span wire:loading wire:target="save" class="inline-flex items-center gap-2">
                            <svg class="size-4 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                <path d="M21 12a9 9 0 1 1-6.219-8.56" stroke-linecap="round"/>
                            </svg>
                            در حال ذخیره…
                        </span>
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
