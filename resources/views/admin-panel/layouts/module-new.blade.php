{{--
    لایه‌ی «پل» — صفحات کلاسیک پکیج‌ها (Blade) داخل کروم پنل جدید (Livewire)
    --------------------------------------------------------------------------
    پکیج‌ها با @extends شرطی بین این لایه و back.layouts.master سوییچ می‌کنند:
        @extends(\Modules\X\Support\ActivePanel::useNewPanel() ? 'admin-panel::layouts.module' : 'back.layouts.master')
    تشخیص پنل فعال: کوکی ap_panel (هر پنل هنگام لود ست می‌کند) + آدرس admin-old.
    --------------------------------------------------------------------------
    ساختار:
      • کروم پنل جدید: سایدبار + تاپ‌بار + فوتر + توست‌ها (دقیقاً مثل layout.blade.php)
      • استک کامل CSS/JS پنل قدیمی: محتوای بوت‌استرپی پکیج‌ها بدون هیچ تغییری رندر می‌شود
      • ترتیب CSS — فاز ۳۶ (Cascade Layers): @layer ap-early, theme, base, legacy, ap-late
            کل CSS پنل قدیمی با @import داخل لایه‌ی «legacy» لود می‌شود (بعد از base/preflight
            و قبل از components/utilities تیلویند):
              − reboot بوت‌استرپ روی preflight غالب می‌ماند (رفتار پنل قدیمی حفظ می‌شود)
              − کلاس‌های تیلویندِ کروم بر قواعد عنصریِ بوت‌استرپ (aside{display:block}،
                input{font-size:inherit}، button{border-radius:0} و…) برنده می‌شوند
              − قواعد !important قدیمی (اسکیل فاصله px-4=3.5rem!important و…) هنوز بر
                utilityهای عادی غالب‌اند → خنثی‌سازی‌شان در لایه «ap-early» (برای
                !important لایه‌ی جلوتر برنده است) و بازسازی‌های عادی در «ap-late»
--}}
@php
    $apSiteTitle = option('info_site_title', config('app.name'));
@endphp
<!DOCTYPE html>
<html lang="fa" dir="rtl" class="h-full {{ request()->cookie('admin-theme') === 'dark' ? 'dark' : '' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="admin-prefix" content="{{ admin_route_prefix() }}">

    {{-- کوکی «پنل فعال» — منوها و صفحات پکیج با همین کوکی بین دو پنل سوییچ می‌کنند --}}
    <script>document.cookie = 'ap_panel=new; path=/; max-age=31536000; SameSite=Lax';</script>

    {{-- فاز ۳۶ — رزرو ترتیب لایه‌ها قبل از هر CSSی: theme/base (مربوط به تیلویند) باید
        «قبل از» legacy رزرو شوند تا preflight زیر reboot بوت‌استرپ بماند؛ components و
        utilities خودشان بعد از این پرولود، انتهای صف می‌آیند (بعد از legacy و ap-late) --}}
    <style>@layer ap-early, theme, base, legacy, ap-late;</style>

    {{-- فاز ۳۷ — لایه‌ی ap-early: خنثی‌سازهای !important. دو نکته‌ی حیاتی که در فاز ۳۶
        اشتباه بود و «پدینگ اضافی منو» را می‌ساخت:
        ۱) این قواعد باید «داخل» لایه‌ی ap-early باشند. برای !important لایه‌ی جلوتر
           برنده است — اما قواعد «بی‌لایه» در جنگ !important از «هر» لایه‌ای می‌بازند
           (ترتیب لایه‌ها برای !important معکوس می‌شود و بی‌لایه = آخرین گروه است).
           فاز ۳۶ این بلوک را بی‌لایه گذاشته بود → اسکیل فاصله‌ی قدیمی (p-3=3rem!important،
           px-3=padding-left/right:3rem!important و…) می‌برد.
        ۲) خنثی‌سازها باید با «لانگ‌هند فیزیکی» نوشته شوند (padding-left/right/top/bottom).
           padding-inline/logical در برابر اعلان فیزیکیِ هم‌ارزش می‌بازد؛ اسکیل قدیمی
           فیزیکی است (bootstrap.css: .pr-3,.px-3{padding-left:3rem!important}) →
           هر خنثی‌سازِ padding-inline می‌باخت.
        مقیاس قدیمی (bootstrap.css خط ~8168-8500): p/px/py/pt/pr/pb/pl و m/mx/my/mt/mr/mb/ml
        × {0,1,2,3,4,5} با !important — مقدار تیلویندی هر کلاس (N×0.25rem) برش می‌گردد. --}}
    {{-- فاز ۳۸ — data-ap-bridge-style: Livewire در wire:navigate استایل‌های <head> را هرگز حذف
        نمی‌کند (assetها فقط انباشته می‌شوند) → این سه بلوک پل روی صفحات پنل می‌ماندند و
        اسکیل !important قدیمی (px-3=3rem!important و…) که فقط داخل ap-chrome خنثی شده بود
        مستقیم به سایدبار/تاپ‌بار پنل ضربه می‌زد. اسکریپت پاک‌سازی انتهای <head> آن‌ها را
        بعد از هر ناوبری که به صفحه‌ی غیرپل می‌رسد حذف می‌کند. --}}
    <style data-ap-bridge-style>
    @layer ap-early {
        /* فونت اجباری iranyekan قدیمی نباید داخل کروم اعمال شود — fa-* مستثنا */
        :where(#ap-chrome-side, #ap-chrome-top, .ap-footer, .ap-toasts) span:not([class*="fa-"]) { font-family: inherit !important; }
        :where(#ap-chrome-side, #ap-chrome-top, .ap-footer, .ap-toasts) :is(input, textarea, select, button) { font-family: inherit !important; }

        /* ---- padding: اسکیل کامل قدیمی × مقادیر تیلویند — همه فیزیکی ---- */
        :is(#ap-chrome-side, #ap-chrome-top, .ap-footer, .ap-toasts) .p-0 { padding: 0 !important; }
        :is(#ap-chrome-side, #ap-chrome-top, .ap-footer, .ap-toasts) .p-1 { padding: .25rem !important; }
        :is(#ap-chrome-side, #ap-chrome-top, .ap-footer, .ap-toasts) .p-2 { padding: .5rem !important; }
        :is(#ap-chrome-side, #ap-chrome-top, .ap-footer, .ap-toasts) .p-3 { padding: .75rem !important; }
        :is(#ap-chrome-side, #ap-chrome-top, .ap-footer, .ap-toasts) .p-4 { padding: 1rem !important; }
        :is(#ap-chrome-side, #ap-chrome-top, .ap-footer, .ap-toasts) .p-5 { padding: 1.25rem !important; }
        :is(#ap-chrome-side, #ap-chrome-top, .ap-footer, .ap-toasts) .px-0 { padding-left: 0 !important; padding-right: 0 !important; }
        :is(#ap-chrome-side, #ap-chrome-top, .ap-footer, .ap-toasts) .px-1 { padding-left: .25rem !important; padding-right: .25rem !important; }
        :is(#ap-chrome-side, #ap-chrome-top, .ap-footer, .ap-toasts) .px-2 { padding-left: .5rem !important; padding-right: .5rem !important; }
        :is(#ap-chrome-side, #ap-chrome-top, .ap-footer, .ap-toasts) .px-3 { padding-left: .75rem !important; padding-right: .75rem !important; }
        :is(#ap-chrome-side, #ap-chrome-top, .ap-footer, .ap-toasts) .px-4 { padding-left: 1rem !important; padding-right: 1rem !important; }
        :is(#ap-chrome-side, #ap-chrome-top, .ap-footer, .ap-toasts) .px-5 { padding-left: 1.25rem !important; padding-right: 1.25rem !important; }
        :is(#ap-chrome-side, #ap-chrome-top, .ap-footer, .ap-toasts) .py-0 { padding-top: 0 !important; padding-bottom: 0 !important; }
        :is(#ap-chrome-side, #ap-chrome-top, .ap-footer, .ap-toasts) .py-1 { padding-top: .25rem !important; padding-bottom: .25rem !important; }
        :is(#ap-chrome-side, #ap-chrome-top, .ap-footer, .ap-toasts) .py-2 { padding-top: .5rem !important; padding-bottom: .5rem !important; }
        :is(#ap-chrome-side, #ap-chrome-top, .ap-footer, .ap-toasts) .py-3 { padding-top: .75rem !important; padding-bottom: .75rem !important; }
        :is(#ap-chrome-side, #ap-chrome-top, .ap-footer, .ap-toasts) .py-4 { padding-top: 1rem !important; padding-bottom: 1rem !important; }
        :is(#ap-chrome-side, #ap-chrome-top, .ap-footer, .ap-toasts) .py-5 { padding-top: 1.25rem !important; padding-bottom: 1.25rem !important; }
        :is(#ap-chrome-side, #ap-chrome-top, .ap-footer, .ap-toasts) .pt-0 { padding-top: 0 !important; }
        :is(#ap-chrome-side, #ap-chrome-top, .ap-footer, .ap-toasts) .pt-1 { padding-top: .25rem !important; }
        :is(#ap-chrome-side, #ap-chrome-top, .ap-footer, .ap-toasts) .pt-2 { padding-top: .5rem !important; }
        :is(#ap-chrome-side, #ap-chrome-top, .ap-footer, .ap-toasts) .pt-3 { padding-top: .75rem !important; }
        :is(#ap-chrome-side, #ap-chrome-top, .ap-footer, .ap-toasts) .pt-4 { padding-top: 1rem !important; }
        :is(#ap-chrome-side, #ap-chrome-top, .ap-footer, .ap-toasts) .pt-5 { padding-top: 1.25rem !important; }
        :is(#ap-chrome-side, #ap-chrome-top, .ap-footer, .ap-toasts) .pb-0 { padding-bottom: 0 !important; }
        :is(#ap-chrome-side, #ap-chrome-top, .ap-footer, .ap-toasts) .pb-1 { padding-bottom: .25rem !important; }
        :is(#ap-chrome-side, #ap-chrome-top, .ap-footer, .ap-toasts) .pb-2 { padding-bottom: .5rem !important; }
        :is(#ap-chrome-side, #ap-chrome-top, .ap-footer, .ap-toasts) .pb-3 { padding-bottom: .75rem !important; }
        :is(#ap-chrome-side, #ap-chrome-top, .ap-footer, .ap-toasts) .pb-4 { padding-bottom: 1rem !important; }
        :is(#ap-chrome-side, #ap-chrome-top, .ap-footer, .ap-toasts) .pb-5 { padding-bottom: 1.25rem !important; }
        /* pl/pr قدیمی فیزیکی هستند (pr-3=padding-left، pl-3=padding-right) — تیلویند هم فیزیکی */
        :is(#ap-chrome-side, #ap-chrome-top, .ap-footer, .ap-toasts) .pl-0 { padding-left: 0 !important; }
        :is(#ap-chrome-side, #ap-chrome-top, .ap-footer, .ap-toasts) .pl-1 { padding-left: .25rem !important; }
        :is(#ap-chrome-side, #ap-chrome-top, .ap-footer, .ap-toasts) .pl-2 { padding-left: .5rem !important; }
        :is(#ap-chrome-side, #ap-chrome-top, .ap-footer, .ap-toasts) .pl-3 { padding-left: .75rem !important; }
        :is(#ap-chrome-side, #ap-chrome-top, .ap-footer, .ap-toasts) .pl-4 { padding-left: 1rem !important; }
        :is(#ap-chrome-side, #ap-chrome-top, .ap-footer, .ap-toasts) .pl-5 { padding-left: 1.25rem !important; }
        :is(#ap-chrome-side, #ap-chrome-top, .ap-footer, .ap-toasts) .pr-0 { padding-right: 0 !important; }
        :is(#ap-chrome-side, #ap-chrome-top, .ap-footer, .ap-toasts) .pr-1 { padding-right: .25rem !important; }
        :is(#ap-chrome-side, #ap-chrome-top, .ap-footer, .ap-toasts) .pr-2 { padding-right: .5rem !important; }
        :is(#ap-chrome-side, #ap-chrome-top, .ap-footer, .ap-toasts) .pr-3 { padding-right: .75rem !important; }
        :is(#ap-chrome-side, #ap-chrome-top, .ap-footer, .ap-toasts) .pr-4 { padding-right: 1rem !important; }
        :is(#ap-chrome-side, #ap-chrome-top, .ap-footer, .ap-toasts) .pr-5 { padding-right: 1.25rem !important; }

        /* ---- margin: همان اسکیل کامل ---- */
        :is(#ap-chrome-side, #ap-chrome-top, .ap-footer, .ap-toasts) .m-0 { margin: 0 !important; }
        :is(#ap-chrome-side, #ap-chrome-top, .ap-footer, .ap-toasts) .m-1 { margin: .25rem !important; }
        :is(#ap-chrome-side, #ap-chrome-top, .ap-footer, .ap-toasts) .m-2 { margin: .5rem !important; }
        :is(#ap-chrome-side, #ap-chrome-top, .ap-footer, .ap-toasts) .m-3 { margin: .75rem !important; }
        :is(#ap-chrome-side, #ap-chrome-top, .ap-footer, .ap-toasts) .m-4 { margin: 1rem !important; }
        :is(#ap-chrome-side, #ap-chrome-top, .ap-footer, .ap-toasts) .m-5 { margin: 1.25rem !important; }
        :is(#ap-chrome-side, #ap-chrome-top, .ap-footer, .ap-toasts) .mx-0 { margin-left: 0 !important; margin-right: 0 !important; }
        :is(#ap-chrome-side, #ap-chrome-top, .ap-footer, .ap-toasts) .mx-1 { margin-left: .25rem !important; margin-right: .25rem !important; }
        :is(#ap-chrome-side, #ap-chrome-top, .ap-footer, .ap-toasts) .mx-2 { margin-left: .5rem !important; margin-right: .5rem !important; }
        :is(#ap-chrome-side, #ap-chrome-top, .ap-footer, .ap-toasts) .mx-3 { margin-left: .75rem !important; margin-right: .75rem !important; }
        :is(#ap-chrome-side, #ap-chrome-top, .ap-footer, .ap-toasts) .mx-4 { margin-left: 1rem !important; margin-right: 1rem !important; }
        :is(#ap-chrome-side, #ap-chrome-top, .ap-footer, .ap-toasts) .mx-5 { margin-left: 1.25rem !important; margin-right: 1.25rem !important; }
        :is(#ap-chrome-side, #ap-chrome-top, .ap-footer, .ap-toasts) .my-0 { margin-top: 0 !important; margin-bottom: 0 !important; }
        :is(#ap-chrome-side, #ap-chrome-top, .ap-footer, .ap-toasts) .my-1 { margin-top: .25rem !important; margin-bottom: .25rem !important; }
        :is(#ap-chrome-side, #ap-chrome-top, .ap-footer, .ap-toasts) .my-2 { margin-top: .5rem !important; margin-bottom: .5rem !important; }
        :is(#ap-chrome-side, #ap-chrome-top, .ap-footer, .ap-toasts) .my-3 { margin-top: .75rem !important; margin-bottom: .75rem !important; }
        :is(#ap-chrome-side, #ap-chrome-top, .ap-footer, .ap-toasts) .my-4 { margin-top: 1rem !important; margin-bottom: 1rem !important; }
        :is(#ap-chrome-side, #ap-chrome-top, .ap-footer, .ap-toasts) .my-5 { margin-top: 1.25rem !important; margin-bottom: 1.25rem !important; }
        :is(#ap-chrome-side, #ap-chrome-top, .ap-footer, .ap-toasts) .mt-0 { margin-top: 0 !important; }
        :is(#ap-chrome-side, #ap-chrome-top, .ap-footer, .ap-toasts) .mt-1 { margin-top: .25rem !important; }
        :is(#ap-chrome-side, #ap-chrome-top, .ap-footer, .ap-toasts) .mt-2 { margin-top: .5rem !important; }
        :is(#ap-chrome-side, #ap-chrome-top, .ap-footer, .ap-toasts) .mt-3 { margin-top: .75rem !important; }
        :is(#ap-chrome-side, #ap-chrome-top, .ap-footer, .ap-toasts) .mt-4 { margin-top: 1rem !important; }
        :is(#ap-chrome-side, #ap-chrome-top, .ap-footer, .ap-toasts) .mt-5 { margin-top: 1.25rem !important; }
        :is(#ap-chrome-side, #ap-chrome-top, .ap-footer, .ap-toasts) .mb-0 { margin-bottom: 0 !important; }
        :is(#ap-chrome-side, #ap-chrome-top, .ap-footer, .ap-toasts) .mb-1 { margin-bottom: .25rem !important; }
        :is(#ap-chrome-side, #ap-chrome-top, .ap-footer, .ap-toasts) .mb-2 { margin-bottom: .5rem !important; }
        :is(#ap-chrome-side, #ap-chrome-top, .ap-footer, .ap-toasts) .mb-3 { margin-bottom: .75rem !important; }
        :is(#ap-chrome-side, #ap-chrome-top, .ap-footer, .ap-toasts) .mb-4 { margin-bottom: 1rem !important; }
        :is(#ap-chrome-side, #ap-chrome-top, .ap-footer, .ap-toasts) .mb-5 { margin-bottom: 1.25rem !important; }
        :is(#ap-chrome-side, #ap-chrome-top, .ap-footer, .ap-toasts) .ml-0 { margin-left: 0 !important; }
        :is(#ap-chrome-side, #ap-chrome-top, .ap-footer, .ap-toasts) .ml-1 { margin-left: .25rem !important; }
        :is(#ap-chrome-side, #ap-chrome-top, .ap-footer, .ap-toasts) .ml-2 { margin-left: .5rem !important; }
        :is(#ap-chrome-side, #ap-chrome-top, .ap-footer, .ap-toasts) .ml-3 { margin-left: .75rem !important; }
        :is(#ap-chrome-side, #ap-chrome-top, .ap-footer, .ap-toasts) .ml-4 { margin-left: 1rem !important; }
        :is(#ap-chrome-side, #ap-chrome-top, .ap-footer, .ap-toasts) .ml-5 { margin-left: 1.25rem !important; }
        :is(#ap-chrome-side, #ap-chrome-top, .ap-footer, .ap-toasts) .mr-0 { margin-right: 0 !important; }
        :is(#ap-chrome-side, #ap-chrome-top, .ap-footer, .ap-toasts) .mr-1 { margin-right: .25rem !important; }
        :is(#ap-chrome-side, #ap-chrome-top, .ap-footer, .ap-toasts) .mr-2 { margin-right: .5rem !important; }
        :is(#ap-chrome-side, #ap-chrome-top, .ap-footer, .ap-toasts) .mr-3 { margin-right: .75rem !important; }
        :is(#ap-chrome-side, #ap-chrome-top, .ap-footer, .ap-toasts) .mr-4 { margin-right: 1rem !important; }
        :is(#ap-chrome-side, #ap-chrome-top, .ap-footer, .ap-toasts) .mr-5 { margin-right: 1.25rem !important; }

        /* قدیمی: .rounded-lg { border-radius: .6rem !important } — تیلویند: .5rem */
        :is(#ap-chrome-side, #ap-chrome-top, .ap-footer, .ap-toasts) .rounded-lg { border-radius: .5rem !important; }

        /* قدیمی: .border { border: 1px solid #EDEDED !important } (bootstrap.css) — کلاس
           border تیلویند فقط width می‌دهد و رنگ را به border-line/dark:border-night-line
           می‌سپارد؛ شورت‌هند قدیمی رنگ #EDEDED را هم !important می‌کند (در دارک‌مود
           مرز روشن می‌ماند) — رنگ با توکن پنل بازسازی می‌شود */
        :is(#ap-chrome-side, #ap-chrome-top, .ap-footer, .ap-toasts) .border { border-width: 1px !important; border-color: var(--color-line) !important; }
        .dark :is(#ap-chrome-side, #ap-chrome-top, .ap-footer, .ap-toasts) .border { border-color: var(--color-night-line) !important; }
    }
    </style>

    <title>@yield('title', $title ?? $apSiteTitle) | پنل مدیریت {{ $apSiteTitle }}</title>

    {{-- ۱) CSS پنل جدید — با لایه‌ها دیگر ترتیبِ «زودتر لود شدن» اهمیت specificity ندارد --}}
    @vite(['resources/admin-panel/css/admin.css', 'resources/admin-panel/js/admin.js'])
    @livewireStyles

    {{-- ۲) متا --}}
    @include('back.partials.meta')

    {{-- ۳) کل CSS پنل قدیمی داخل لایه‌ی legacy — همان ترتیب قبلی لینک‌ها (فاز ۳۸: پاک‌سازی‌پذیر) --}}
    <style data-ap-bridge-style>
        @import url("{{ asset('back/app-assets/vendors/css/vendors-rtl.min.css') }}") layer(legacy);
        @import url("{{ asset('back/app-assets/vendors/css/ui/prism.min.css') }}") layer(legacy);
        @import url("{{ asset('back/app-assets/vendors/css/extensions/toastr.css') }}") layer(legacy);
        @import url("{{ asset('back/app-assets/vendors/css/forms/select/select2.min.css') }}") layer(legacy);
        @import url("{{ asset('back/app-assets/plugins/select2totree/select2totree.css') }}") layer(legacy);
        @import url("https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.3.0/css/all.min.css") layer(legacy);
        @import url("{{ asset('back/app-assets/css-rtl/bootstrap.css') }}") layer(legacy);
        @import url("{{ asset('back/app-assets/css-rtl/bootstrap-extended.css') }}") layer(legacy);
        @import url("{{ asset('back/app-assets/css-rtl/colors.css') }}") layer(legacy);
        @import url("{{ asset('back/app-assets/css-rtl/components.css') }}") layer(legacy);
        @import url("{{ asset('back/app-assets/css-rtl/themes/dark-layout.css') }}") layer(legacy);
        @import url("{{ asset('back/app-assets/css-rtl/themes/semi-dark-layout.css') }}") layer(legacy);
        @import url("{{ asset('back/app-assets/css-rtl/plugins/extensions/toastr.css') }}") layer(legacy);
        @import url("{{ asset('back/app-assets/css-rtl/plugins/animate/animate.min.css') }}") layer(legacy);
        @import url("{{ asset('back/assets/css/new-style.css') }}") layer(legacy);
        @import url("{{ asset('back/app-assets/css-rtl/core/menu/menu-types/vertical-menu.css') }}") layer(legacy);
        @import url("{{ asset('back/app-assets/css-rtl/custom-rtl.css') }}") layer(legacy);
        @import url("{{ asset('back/assets/css/style-rtl.css') }}?v=11") layer(legacy);
        @if (user_option('theme_font', 'iranyekan') == 'iranyekan')
            @import url("{{ asset('back/assets/fonts/iranyekan/style.css') }}") layer(legacy);
        @elseif (user_option('theme_font', 'iranyekan') == 'Yekan')
            @import url("{{ asset('back/assets/fonts/yekan/style.css') }}") layer(legacy);
        @elseif (user_option('theme_font', 'iranyekan') == 'Vazir')
            @import url("{{ asset('back/assets/fonts/vazir/style.css') }}") layer(legacy);
        @else
            @import url("{{ asset('back/assets/fonts/iransansdn/style.css') }}") layer(legacy);
        @endif
    </style>

    {{-- فاز ۳۸ — استایل‌های خود صفحه‌ی پکیج (@stack('styles')) از <head> به بدنه منتقل شد
        (فقط وسط، بین کروم و <main>): ۱) unlayered بودن‌شان حفظ می‌شود (آخرین حرف مثل پنل قدیمی)،
        ۲) با replaceWith(newBody) در هر ناوبری خودبه‌خود می‌روند و دیگر روی صفحات پنل نمی‌مانند،
        ۳) چون قبل از <main> هستند، محتوای پکیج تا لودِ استایل‌هایش رندر نمی‌شود (بدون FOUC). --}}

    {{-- ۴) کمپات لایه‌ی ap-late — بازسازی‌های «عادی» (لایه‌ی بعدتر از legacy برنده است).
        فاز ۳۵: نشت CSS پنل قدیمی به کروم پنل جدید خنثی می‌شود (فاز ۳۸: پاک‌سازی‌پذیر) --}}
    <style data-ap-bridge-style>
    @layer ap-late {
        /* ۱) ریشه: پنل جدید برای root=16px طراحی شده؛ components.css پنل قدیمی
              html را 14px و line-height 1.15 و letter-spacing می‌گذارد و همه‌ی
              سایزهای rem کروم (سایدبار 17rem، تاپ‌بار h-16 و…) را ۱۲.۵٪ کوچک می‌کند */
        html { font-size: 16px; line-height: 1.5; letter-spacing: normal; }

        /* ۲) بدنه: قاعده‌ی body پنل جدید (bg-surface-2 text-ink) — components.css
              با «html body» (0,0,2) پس‌زمینه #F8F8F8 می‌گذارد؛ همان اولویت و بعدتر */
        html body { background-color: var(--color-surface-2); color: var(--color-ink); }
        html.dark body { background-color: var(--color-night); color: var(--color-night-ink); }

        /* ۳) کانتینرهای کروم: فونت/رنگ/سطرِ پنل جدید — iranyekan !important پنل
              قدیمی روی body از راه ارث‌بری به کروم هم می‌رسد */
        #ap-chrome-side, #ap-chrome-top, .ap-footer, .ap-toasts {
            font-family: var(--font-sans, "Vazirmatn", "Tahoma", "Segoe UI", ui-sans-serif, system-ui, sans-serif);
            color: var(--color-ink);
            line-height: 1.5;
        }
        .dark #ap-chrome-side, .dark #ap-chrome-top, .dark .ap-footer, .dark .ap-toasts {
            color: var(--color-night-ink);
        }


        /* ۵) خنثی‌سازی reboot بوت‌استرپ داخل کروم */
        :where(#ap-chrome-side, #ap-chrome-top, .ap-footer, .ap-toasts) p { margin: 0; }
        :where(#ap-chrome-side, #ap-chrome-top, .ap-footer, .ap-toasts) h1,
        :where(#ap-chrome-side, #ap-chrome-top, .ap-footer, .ap-toasts) h2,
        :where(#ap-chrome-side, #ap-chrome-top, .ap-footer, .ap-toasts) h3,
        :where(#ap-chrome-side, #ap-chrome-top, .ap-footer, .ap-toasts) h4,
        :where(#ap-chrome-side, #ap-chrome-top, .ap-footer, .ap-toasts) h5,
        :where(#ap-chrome-side, #ap-chrome-top, .ap-footer, .ap-toasts) h6 { margin: 0; }
        /* هدینگ‌های کروم مثل preflight تیلویند behave کنند (text-* کلاسی همیشه برنده می‌ماند) */
        :where(#ap-chrome-side, #ap-chrome-top, .ap-footer, .ap-toasts) h1,
        :where(#ap-chrome-side, #ap-chrome-top, .ap-footer, .ap-toasts) h2,
        :where(#ap-chrome-side, #ap-chrome-top, .ap-footer, .ap-toasts) h3,
        :where(#ap-chrome-side, #ap-chrome-top, .ap-footer, .ap-toasts) h4,
        :where(#ap-chrome-side, #ap-chrome-top, .ap-footer, .ap-toasts) h5,
        :where(#ap-chrome-side, #ap-chrome-top, .ap-footer, .ap-toasts) h6 { font-size: inherit; font-weight: inherit; line-height: inherit; }
        :where(#ap-chrome-side, #ap-chrome-top, .ap-footer, .ap-toasts) ul,
        :where(#ap-chrome-side, #ap-chrome-top, .ap-footer, .ap-toasts) ol { margin: 0; padding: 0; list-style: none; }
        :where(#ap-chrome-side, #ap-chrome-top, .ap-footer, .ap-toasts) a { text-decoration: none; color: inherit; }
        :where(#ap-chrome-side, #ap-chrome-top, .ap-footer, .ap-toasts) a:hover,
        :where(#ap-chrome-side, #ap-chrome-top, .ap-footer, .ap-toasts) a:focus,
        :where(#ap-chrome-side, #ap-chrome-top, .ap-footer, .ap-toasts) a:active { text-decoration: none; color: inherit; }
        :where(#ap-chrome-side) label { margin-bottom: 0; }
        :where(#ap-chrome-side, #ap-chrome-top, .ap-footer, .ap-toasts) img { vertical-align: middle; }

        /* ۶) کلاس «hidden» پنل قدیمی (bootstrap-extended: display:none + visibility:hidden
              و بعضی پکیج‌ها با !important) utilityهای ریسپانسیوِ لایه‌دار تیلویند را
              می‌شکند — در کروم با همان بریک‌پوینت‌ها بازیابی می‌شوند */
        @media (min-width: 640px) {
            :where(#ap-chrome-side, #ap-chrome-top, .ap-footer, .ap-toasts) .sm\:block { display: block !important; }
        }
        @media (min-width: 1024px) {
            :where(#ap-chrome-side, #ap-chrome-top, .ap-footer, .ap-toasts) .lg\:hidden { display: none !important; }
        }
        html.dark :where(#ap-chrome-side, #ap-chrome-top, .ap-footer, .ap-toasts) .dark\:block { display: block !important; }
        /* ۷) پنل قدیمی برای فاصله‌گیری از سایدبار ثابت خودش به .content مقدار
              بی‌قیدِ margin-right:260px می‌دهد (components.css)؛ در کروم پنل جدید
              ستون اصلی همین کار را انجام می‌دهد — این حاشیه باید صفر شود وگرنه
              محتوای پکیج در همه‌ی سایزها باریک و کج رندر می‌شود */
        html body .content { margin-right: 0; margin-left: 0; }

        /* visibility:hiddenِ باقیمانده روی عناصر hidden sm:block/dark:block */
        :where(#ap-chrome-side, #ap-chrome-top, .ap-footer, .ap-toasts) .sm\:block,
        :where(#ap-chrome-side, #ap-chrome-top, .ap-footer, .ap-toasts) .dark\:block { visibility: visible; }

        /* ۸) سایدبار: aside بوت‌استرپ display:block می‌دهد — کلاس flex تیلویند (لایه‌ی
              utilities بعد از legacy) خودش برنده است، این قاعده فقط بیمه‌ی پشتیبان است */
        #ap-chrome-side > aside { display: flex; }

        /* ۱۰) content-wrapper قدیمی برای ناوبارِ ثابتِ پنل قدیمی margin-top:6rem و
              پدینگ 1.8rem/2.2rem می‌گیرد (components.css)؛ در کروم پنل جدید تاپ‌بار
              در جریانِ سند است و main خودش px/py دارد → فاصله‌ی مضاعفِ بالای صفحه
              («پدینگ زیادِ صفحه») — صفر می‌شود */
        html body .content .content-wrapper { margin-top: 0; padding: 0; }

    }
    </style>

    <!-- Scripts -->
    <script>
        window.Laravel = {!! json_encode([
            'user' => auth('adminPanel')->user(),
            'csrfToken' => csrf_token(),
            'vapidPublicKey' => config('webpush.vapid.public_key'),
            'pusher' => [
                'key' => config('broadcasting.connections.pusher.key'),
                'cluster' => config('broadcasting.connections.pusher.options.cluster'),
            ],
        ]) !!};
    </script>

    {{-- فاز ۳۸ — پاک‌سازی استایل‌های پل بعد از SPA-navigate. Livewire (mergeNewHead) استایل‌های
        <head> را هرگز حذف نمی‌کند؛ بلوک‌های data-ap-bridge-style این صفحه بعد از ناوبری به
        صفحات خود پنل (که #ap-chrome-side ندارند) باید حذف شوند وگرنه اسکیل !important قدیمی
        و بازسازی‌های ap-late روی سایدبار/تاپ‌بار پنل اعمال می‌شوند (بهم‌ریختگی بعد از بازگشت
        از پکیج). جهت معکوس (پنل ← پکیج) لود کامل است و نیازی به این اسکریپت ندارد؛ همین
        نسخه‌ی عیناً یکسان در layout.blade.php هم هست تا در mergeNewHead dedupe شود و فقط
        یک بار در هر سند اجرا شود. --}}
    <script>
        document.addEventListener('livewire:navigated', function () {
            if (! document.getElementById('ap-chrome-side')) {
                document.head.querySelectorAll('[data-ap-bridge-style]').forEach(function (el) { el.remove(); });
            }
        });
    </script>

</head>

{{-- بدنه با درایور Alpine سایدبار (مثل layout.blade.php) --}}
<body class="min-h-screen">
<div x-data="apSidebar" class="min-h-screen flex flex-col">

    {{-- پوشش موبایل --}}
    <div x-show="open" x-cloak x-transition.opacity.duration.200ms
         class="fixed inset-0 z-40 bg-slate-950/50 backdrop-blur-sm lg:hidden"
         x-on:click="open = false" aria-hidden="true"></div>

    <div class="flex flex-1">
        <div id="ap-chrome-side">
            <x-admin-panel::sidebar />
        </div>

        {{-- ستون اصلی --}}
        <div class="flex min-w-0 flex-1 flex-col lg:mr-[17rem]">
            <div id="ap-chrome-top">
                {{-- پکیج‌ها title ست نمی‌کنند — به‌جای «داشبورد» نام فروشگاه نشان داده شود --}}
                <x-admin-panel::topbar :title="($title ?? $apSiteTitle)" />
            </div>

            {{-- محتوای صفحه‌ی پکیج (بوت‌استرپ قدیمی) --}}
            {{-- فاز ۳۶: px-4 به px-[16px] تبدیل شد — bootstrap.css اسکیل فاصله‌ی خودش را
                با !important تعریف می‌کند (px-4 = 3.5rem !important) و کلاس تیلویندی
                px-4 را می‌رباید؛ مقدار دلخواه px-[16px] در آن اسکیل وجود ندارد --}}
            {{-- فاز ۳۸ — استایل‌های خود صفحه‌ی پکیج، منتقل‌شده از <head> به بدنه (بعد از کروم،
                قبل از <main>): ۱) unlayered می‌ماند (آخرین حرف مثل پنل قدیمی)، ۲) با
                replaceWith(newBody) در هر ناوبری خودبه‌خود می‌رود و دیگر روی صفحات پنل
                نمی‌ماند، ۳) چون قبل از <main> است، محتوای پکیج تا لود استایل‌هایش
                رندر نمی‌شود (بدون FOUC). --}}
            @stack('styles')

            <main class="flex-1 px-[16px] py-6 sm:px-6 lg:px-8">
                @yield('content')
            </main>

            {{-- فاز ۳۶: فوتر هم مثل main با مقادیر دلخواه — کلاس‌های خودِ فوتر با
                ترکیب‌کننده‌ی فرزند (:is(...) .px-4) پوشش داده نمی‌شوند --}}
            <footer class="ap-footer px-[16px] py-[16px] sm:px-[24px] lg:px-[32px]">
                <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-2 text-xs text-ink-3 dark:text-night-ink-2">
                    <span>پنل مدیریت {{ $apSiteTitle }} — نسخه جدید (Livewire 4)</span>
                    <span class="fa-digits">© {{ date('Y') }}</span>
                </div>
            </footer>
        </div>
    </div>

    {{-- Toasts --}}
    <x-admin-panel::toasts />
</div>

{{-- مودال‌های عمومی مستر قدیمی — اسکریپت‌های مشترک (scripts.js و…) به آن‌ها وابسته‌اند --}}
<div class="modal fade text-left" id="main-errors-modal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">متن خطا</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
            <div class="modal-body ltr text-right">
                <div class="px-1">
                    <div class="error-content"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade text-left" id="password-confirm-modal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-sm" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">تایید رمز عبور</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
            <div class="modal-body">
                <p>مدت زمان فعالیت شما بیشتر از {{ config('auth.password_timeout') / 60 }} دقیقه است لطفا رمز عبور خود را مجدد وارد کنید</p>
                <form id="password-confirm-form" method="POST" action="{{ route('password.confirm') }}">
                    @csrf

                    <fieldset class="form-label-group position-relative has-icon-left">
                        <input type="password" class="form-control" name="password" required autocomplete="current-password" placeholder="گذرواژه">
                        <div class="form-control-position">
                            <i class="feather icon-lock"></i>
                        </div>
                        <label for="user-password">گذرواژه</label>
                    </fieldset>
                    <button type="submit" class="btn btn-primary float-right btn-inline">تایید</button>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- استک کامل JS پنل قدیمی — دقیقاً مثل back.layouts.master --}}
<!-- BEGIN: Vendor JS-->
<script src="{{ asset('back/app-assets/vendors/js/vendors.min.js') }}"></script>
<script src="{{ asset('back/app-assets/vendors/js/forms/select/select2.full.min.js') }}"></script>
<script src="{{ asset('back/app-assets/plugins/select2totree/select2totree.js') }}"></script>
<script src="{{ asset('back/app-assets/vendors/js/extensions/toastr.min.js') }}"></script>
<script src="{{ asset('back/app-assets/vendors/js/extensions/sweetalert2.all.min.js') }}"></script>
<script src="{{ asset('back/app-assets/plugins/json-viewer/jquery.json-editor.min.js') }}"></script>
<!-- BEGIN Vendor JS-->

<!-- BEGIN: Page Vendor JS-->
<script src="{{ asset('back/app-assets/vendors/js/ui/prism.min.js') }}"></script>
<!-- END: Page Vendor JS-->

<!-- BEGIN: Theme JS-->
<script src="{{ asset('back/app-assets/js/core/app-menu.js') }}"></script>
<script src="{{ asset('back/app-assets/js/core/app.js') }}"></script>

@stack('plugin-scripts')

<script src="{{ asset('back/assets/js/helpers.js') }}?v=8"></script>
<script src="{{ asset('back/assets/js/customToast.js') }}?v=8"></script>
<script src="{{ asset('back/assets/js/scripts.js') }}?v=12"></script>

<!-- END: Theme JS-->

<script>
    var BASE_URL = "{{ route('admin.dashboard') }}";
    var FRONT_URL = "{{ Route::has('front.index') ? route('front.index') : url('/') }}";
    var WEB_PUSH_NOTIFICATION = {{ auth()->user() && method_exists(auth()->user(), 'pushSubscriptions') && auth()->user()->pushSubscriptions()->first() ? 'true' : 'false' }};
    var APP_FONT_FAMILY = "{{ user_option('theme_font', 'Vazir') }}";
</script>

<!-- BEGIN: Page JS-->
@stack('scripts')
<!-- END: Page JS-->

@if (file_exists(public_path('mix-manifest.json')))
    <script src="{{ asset(mix('js/app.js')) }}"></script>
@endif
<script src="{{ asset('back/assets/js/echo.js') }}"></script>
<script src="{{ asset('back/assets/js/web-push.js') }}"></script>

@if (session('toast-success'))
    <script>
        showCustomToast('{{ session('toast-success') }}','success')
    </script>
@endif
@if (session('toast-error'))
    <script>
        showCustomToast('{{ session('toast-error') }}','error')
    </script>
@endif
@if (session('toast-warning'))
    <script>
        showCustomToast('{{ session('toast-warning') }}','warning')
    </script>
@endif

@livewireScripts

</body>
</html>
@php session()->forget('toast-success'); @endphp
@php session()->forget('toast-error'); @endphp
@php session()->forget('toast-warning'); @endphp
