{{--
    فاز ۳۴ — منوی «پکیج‌ها» در سایدبار پنل جدید
    --------------------------------------------------------------------------
    داده از خود پکیج‌ها استخراج می‌شود (App\Services\ModuleMenuExtractor):
    پارشال منوی پکیج در کانتکست قدیمی رندر و پارس می‌شود؛ سپس با مارک‌آپِ
    همین سایدبار (ap-menu-link + apMenuGroup + جستجو + is-active) رندر
    می‌گردد — یعنی «دقیقاً مثل بقیه‌ی منوها»، مستقل از نسخه‌ی نصب‌شده‌ی پکیج.
    اگر پارسِ یک پکیج شکست بخورد، همان ویو مستقیم include می‌شود (رفتار فاز ۳۲).
    لینک‌ها ?ap=new می‌گیرند تا کروم صفحه قطعی باشد (دو تب همزمان دو پنل).
--}}
@can('packages')
    @php
        $apPackages = \App\Services\ModuleMenuExtractor::all();
        $apExtractedViews = array_column($apPackages, 'view');
        $apFallbackViews = array_values(array_filter(
            \App\Services\ModuleMenuRegistry::all(),
            fn ($v) => view()->exists($v) && ! in_array($v, $apExtractedViews, true)
        ));
    @endphp

    @if (count($apPackages) > 0 || count($apFallbackViews) > 0)
        <div class="menu-group">
            <p class="ap-menu-title">پکیج‌ها</p>

            <ul class="space-y-0.5">
                {{-- پکیج‌هایی که ساختار منوشان با موفقیت استخراج شد --}}
                @foreach ($apPackages as $apPackage)
                    <li x-show="search === '' || window.apMenuSearch.has(@js($apPackage['label']), search) || @js(collect($apPackage['items'])->pluck('label')->values()->all()).some((t) => window.apMenuSearch.has(t, search))">
                        <div x-data="apMenuGroup(false)">
                            <button type="button" class="ap-menu-link w-full" x-on:click="toggle()"
                                    :aria-expanded="open.toString()" aria-haspopup="true">
                                <i class="{{ $apPackage['icon'] ?: 'fa-solid fa-cube' }} text-[16px] opacity-80"></i>
                                <span class="flex-1 text-start">{{ $apPackage['label'] }}</span>
                                <span class="ap-chevron inline-flex" :class="open ? 'is-open' : ''">
                                    <x-admin-panel::icon name="chevron-left" class="size-4 opacity-60" />
                                </span>
                            </button>
                            <div x-show="open || search !== ''"
                                 x-transition:enter="transition ease-out duration-200"
                                 x-transition:enter-start="opacity-0 -translate-y-1"
                                 x-transition:enter-end="opacity-100 translate-y-0"
                                 x-transition:leave="transition ease-in duration-150"
                                 x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
                                <ul class="mt-0.5 space-y-0.5 ms-4 border-s-2 border-line/70 ps-2.5 dark:border-night-line/70">
                                    @foreach ($apPackage['items'] as $apItem)
                                        <li x-show="search === '' || window.apMenuSearch.has(@js($apItem['label']), search)">
                                            <a href="{{ $apItem['url'] }}"
                                               class="ap-menu-link {{ $apItem['active'] ? 'is-active' : '' }}"
                                               @if ($apItem['active']) aria-current="page" @endif>
                                                <i class="fa-solid fa-circle text-[7px] opacity-50"></i>
                                                <span class="truncate">{{ $apItem['label'] }}</span>
                                                @if ($apItem['active'])
                                                    <span class="ms-auto size-1.5 rounded-full bg-brand-500"></span>
                                                @endif
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </li>
                @endforeach

                {{-- پکیج‌هایی که پارس نشدند — رندر مستقیم پارشال خودشان (fallback فاز ۳۲) --}}
                @foreach ($apFallbackViews as $apFallbackView)
                    @include($apFallbackView, ['apPanel' => 'new'])
                @endforeach
            </ul>
        </div>
    @endif
@endcan
