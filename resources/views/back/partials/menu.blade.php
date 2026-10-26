{{--
    منوی پکیج باشگاه مشتریان — دو پنلی (الگوی DigiPay/MarketingMessaging)
    --------------------------------------------------------------------------
    • سایدبارها با @include($menuView, ['apPanel' => 'new|old']) کانتکست می‌دهند؛
      بدون کانتکست، ActivePanel با کوکی ap_panel تشخیص می‌دهد.
    • الزام کاربر: کل گروه منو با club_enabled کنترل می‌شود — ادمین می‌تواند
      باشگاه را از تنظیمات خاموش کند و منو پنهان شود.
--}}
@php
    use Modules\CustomerClub\Support\ActivePanel as CcActivePanel;
    use Modules\CustomerClub\Models\ClubSetting;

    $apNew = isset($apPanel) ? ($apPanel === 'new') : CcActivePanel::isNew();
    if ($apNew && !CcActivePanel::hasNewChrome()) {
        $apNew = false;
    }

    $apClubOn = ClubSetting::enabled('club_enabled');

    // ----- داده‌های منو (مشترک بین دو پنل) -----
    $apLabel = 'باشگاه مشتریان';
    $apIcon  = 'fas fa-award';
    $apItems = [
        ['perm' => 'customerclub',          'route' => 'admin.customerclub.dashboard',       'label' => 'داشبورد باشگاه',   'active' => 'admin.customerclub.dashboard*'],
        ['perm' => 'customerclub.rules',    'route' => 'admin.customerclub.rules.index',     'label' => 'قوانین امتیاز',    'active' => 'admin.customerclub.rules*'],
        ['perm' => 'customerclub.points',   'route' => 'admin.customerclub.points.index',    'label' => 'دفتر امتیازها',    'active' => 'admin.customerclub.points*'],
        ['perm' => 'customerclub.members',  'route' => 'admin.customerclub.members.index',   'label' => 'اعضای باشگاه',     'active' => 'admin.customerclub.members*'],
        ['perm' => 'customerclub.giftcards','route' => 'admin.customerclub.giftcards.index', 'label' => 'کارت‌های هدیه',    'active' => 'admin.customerclub.giftcards*'],
        ['perm' => 'customerclub.referrals','route' => 'admin.customerclub.referrals.index', 'label' => 'معرفی دوستان',     'active' => 'admin.customerclub.referrals*'],
        ['perm' => 'customerclub.settings', 'route' => 'admin.customerclub.settings.edit',   'label' => 'تنظیمات',          'active' => 'admin.customerclub.settings*'],
        // قرارداد فاز ۸۶ — آموزش طرز استفاده + «این پکیج کارش چیه»
        ['perm' => 'customerclub',          'route' => 'admin.customerclub.guide',           'label' => 'راهنما و آموزش',   'active' => 'admin.customerclub.guide*'],
    ];

    $apVisible = collect($apItems)
        ->filter(fn ($i) => empty($i['perm']) || app(\Illuminate\Contracts\Auth\Access\Gate::class)->check($i['perm']))
        ->values();
@endphp

@if ($apClubOn)
@if ($apNew)
    {{-- مارک‌آپ سایدبار پنل جدید --}}
    <ul class="space-y-0.5">
        <li x-show="search === '' || window.apMenuSearch.has(@js($apLabel), search) || @js($apVisible->pluck('label')->values()->all()).some((t) => window.apMenuSearch.has(t, search))">
            <div x-data="apMenuGroup(false)">
                <button type="button" class="ap-menu-link w-full" x-on:click="toggle()"
                        :aria-expanded="open.toString()" aria-haspopup="true">
                    <i class="{{ $apIcon }} text-[16px] opacity-80"></i>
                    <span class="flex-1 text-start">{{ $apLabel }}</span>
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
                        @foreach ($apVisible as $apItem)
                            @php
                                try { $apItemUrl = route($apItem['route']); } catch (\Throwable $apE) { $apItemUrl = '#'; }
                                $apItemActive = request()->routeIs($apItem['active']);
                            @endphp
                            <li x-show="search === '' || window.apMenuSearch.has(@js($apItem['label']), search)">
                                <a href="{{ $apItemUrl }}"
                                   class="ap-menu-link {{ $apItemActive ? 'is-active' : '' }}"
                                   @if ($apItemActive) aria-current="page" @endif>
                                    <i class="fa-solid fa-circle text-[7px] opacity-50"></i>
                                    <span class="truncate">{{ $apItem['label'] }}</span>
                                    @if ($apItemActive)
                                        <span class="ms-auto size-1.5 rounded-full bg-brand-500"></span>
                                    @endif
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </li>
    </ul>
@else
@can('customerclub')
    <li class="nav-item has-sub {{ open_class(['admin.customerclub.*']) }}">
        <a><i class="{{ $apIcon }}"></i><span class="menu-title">{{ $apLabel }}</span></a>
        <ul class="menu-content">
            @can('customerclub')
                <li class="{{ active_class('admin.customerclub.dashboard*') }}">
                    <a href="{{ route('admin.customerclub.dashboard') }}"><i class="fas fa-gauge-high"></i><span class="menu-title">داشبورد باشگاه</span></a>
                </li>
            @endcan
            @can('customerclub.rules')
                <li class="{{ active_class('admin.customerclub.rules*') }}">
                    <a href="{{ route('admin.customerclub.rules.index') }}"><i class="fas fa-list-check"></i><span class="menu-title">قوانین امتیاز</span></a>
                </li>
            @endcan
            @can('customerclub.points')
                <li class="{{ active_class('admin.customerclub.points*') }}">
                    <a href="{{ route('admin.customerclub.points.index') }}"><i class="fas fa-scroll"></i><span class="menu-title">دفتر امتیازها</span></a>
                </li>
            @endcan
            @can('customerclub.members')
                <li class="{{ active_class('admin.customerclub.members*') }}">
                    <a href="{{ route('admin.customerclub.members.index') }}"><i class="fas fa-users"></i><span class="menu-title">اعضای باشگاه</span></a>
                </li>
            @endcan
            @can('customerclub.giftcards')
                <li class="{{ active_class('admin.customerclub.giftcards*') }}">
                    <a href="{{ route('admin.customerclub.giftcards.index') }}"><i class="fas fa-gift"></i><span class="menu-title">کارت‌های هدیه</span></a>
                </li>
            @endcan
            @can('customerclub.referrals')
                <li class="{{ active_class('admin.customerclub.referrals*') }}">
                    <a href="{{ route('admin.customerclub.referrals.index') }}"><i class="fas fa-share-nodes"></i><span class="menu-title">معرفی دوستان</span></a>
                </li>
            @endcan
            @can('customerclub.settings')
                <li class="{{ active_class('admin.customerclub.settings*') }}">
                    <a href="{{ route('admin.customerclub.settings.edit') }}"><i class="fas fa-gear"></i><span class="menu-title">تنظیمات</span></a>
                </li>
            @endcan
            @can('customerclub')
                <li class="{{ active_class('admin.customerclub.guide*') }}">
                    <a href="{{ route('admin.customerclub.guide') }}"><i class="fas fa-graduation-cap"></i><span class="menu-title">راهنما و آموزش</span></a>
                </li>
            @endcan
        </ul>
    </li>
@endcan
@endif
@endif
