@extends('front::layouts.master')

@push('meta')
    <meta name="description" content="{{ option('info_short_description') }}">
    <meta name="keywords" content="{{ option('info_tags') }}">

    <link rel="canonical" href="{{ url('/') }}"/>

    @php
        $ldData = [
            "@context" => "https://schema.org",
            "@type" => "WebSite",
            "url" => route('front.index'),
            "name" => option('info_site_title'),
            "logo" => option('info_logo') ? asset(option('info_logo')) : theme_asset('image/logo.png'),
            "potentialAction" => [
                "@type" => "SearchAction",
                "target" => route('front.products.index') . "/?q={search_term_string}",
                "query-input" => "required name=search_term_string"
            ]
        ];
    @endphp

    <script type="application/ld+json">
        {!! json_encode($ldData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) !!}
    </script>
@endpush

@section('content')
    @foreach ($widgets as $widget)
        {{-- wrapper ابزارک برای ویجت‌ساز پنل (هایلایت/اسکرول) — بدون تغییر ظاهری --}}
        <div id="widget-{{ $widget->id }}">
            @php
                // اول چک کن آیا ماژولی این ویجت رو ثبت کرده
                $moduleView = \App\Services\WidgetRegistry::getView($widget->key);
                $moduleHasWidget = $moduleView && view()->exists($moduleView);
            @endphp

            @if ($moduleHasWidget)
                {{-- ویجت از ماژول لود میشه --}}
                @include($moduleView)
            @else
                @switch($widget->key)
                    @case('main-slider')
                        @include('front::widgets.main-slider')
                        @break

                    @case('products-slider')
                    @case('products-default-block')
                    @case('products-moment-block')
                    @case('products-colorful-block')
                        @include('front::widgets.products-slider')
                        @break

                    @case('middle-banners')
                        @include('front::widgets.middle-banners')
                        @break

                    @case('middle-banners-2')
                        @include('front::widgets.middle-banners-2')
                        @break

                    @case('middle-banners-4')
                        @include('front::widgets.middle-banners-4')
                        @break

                    @case('categories')
                        @include('front::widgets.categories')
                        @break

                    @case('posts')
                        @include('front::widgets.posts')
                        @break

                    @case('coworker-sliders')
                        @include('front::widgets.coworker-sliders')
                        @break

                    @case('sevices-sliders')
                        @include('front::widgets.sevices-sliders')
                        @break

                    @case('faqs')
                        @include('front::widgets.faqs')
                        @break
                @endswitch
            @endif
        </div>
    @endforeach
@endsection

@push('scripts')
    @if (option('dt_index_popup_type') == 'image')
        <div id="image-popup-modal" class="modal fade" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" btn-close data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <a href="{{ option('dt_index_popup_link') }}">
                            <div class="d-none d-md-block">
                                <img src="{{ asset(option('dt_index_popup_image')) }}" class="img-fluid w-100" alt="پیشنهاد ویژه">
                            </div>
                            <div class="d-block d-md-none">
                                <img src="{{ asset(option('dt_index_popup_image_mobile')) }}" class="img-fluid w-100" alt="پیشنهاد ویژه">
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <script>
            $(function () { $('#image-popup-modal').modal('show'); });
        </script>
    @elseif (option('dt_index_popup_type') == 'text')
        <div id="text-popup-modal" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body pt-0">
                        <p>{!! option('dt_index_popup_text') !!}</p>
                    </div>
                </div>
            </div>
        </div>
        <script>
            $(function () { $('#text-popup-modal').modal('show'); });
        </script>
    @endif

    {{-- اسکریپت ویجت‌ساز پنل (فاز ۹-ه) — فقط در حالت پیش‌نمایش ?ap-preview=1؛ بدون هیچ تاثیری در بازدید عادی --}}
    @if (request()->get('ap-preview'))
        <style>
            [data-ap-outline] {
                animation: apWidgetPulse 1.6s ease-in-out infinite;
            }
            @keyframes apWidgetPulse {
                0%, 100% { outline-color: rgba(245, 158, 11, .95); outline-offset: 4px; }
                50% { outline-color: rgba(245, 158, 11, .35); outline-offset: 9px; }
            }
            .ap-widget-guide {
                outline: 1px dashed rgba(148, 163, 184, .8) !important;
                outline-offset: 2px;
            }
        </style>
        <script>
            (function () {
                function clearHighlight() {
                    document.querySelectorAll('[data-ap-outline]').forEach(function (node) {
                        node.style.outline = '';
                        node.removeAttribute('data-ap-outline');
                    });
                }

                function highlightWidget(id) {
                    var el = document.getElementById('widget-' + id);
                    if (!el) { return; }
                    clearHighlight();
                    el.setAttribute('data-ap-outline', '1');
                    el.style.outline = '2px dashed rgba(245, 158, 11, .95)';
                    el.style.outlineOffset = '4px';
                    el.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }

                function setGuides(on) {
                    document.querySelectorAll('[id^="widget-"]').forEach(function (node) {
                        node.classList.toggle('ap-widget-guide', !!on);
                    });
                }

                window.addEventListener('message', function (event) {
                    var data = event.data || {};
                    if (data.apWidgetId) { highlightWidget(data.apWidgetId); }
                    if (typeof data.apShowGuides !== 'undefined') { setGuides(data.apShowGuides); }
                });
            })();
        </script>
    @endif
@endpush
