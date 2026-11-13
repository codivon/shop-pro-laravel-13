{{-- فیلترهای دسته‌بندی — سایدبار دسکتاپ (قالب سانیار) + آفسکانس موبایل --}}
@php
    $agent = new \Jenssegers\Agent\Agent();
@endphp

@if($agent->isMobile())

    {{-- start filter in mobile --}}
    <div class="col-12 custom-filter d-lg-none d-block">
        <button class="btn btn-filter-float border-0 main-color-two-bg shadow-box px-3 rounded-3 position-fixed"
                style="z-index: 999;bottom:80px;" type="button" data-bs-toggle="offcanvas"
                data-bs-target="#offcanvasRight" aria-controls="offcanvasRight">
            <i class="bi bi-funnel font-20 fw-bold text-white"></i>
            <span class="d-block font-14 text-white">فیلتر</span>
        </button>

        <div class="offcanvas offcanvas-start" tabindex="-1" id="offcanvasRight"
             aria-labelledby="offcanvasRightLabel">
            <div class="offcanvas-header">
                <h5 class="offcanvas-title" id="offcanvasRightLabel">فیلتر ها</h5>
                <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="بستن"></button>
            </div>
            <div class="offcanvas-body">
                <div class="filter-items position-sticky top-0">
                    <div class="container-fluid">
                        @include('front::products.partials.category-filters-body', ['scope' => 'm-'])
                    </div>
                </div>
            </div>
        </div>
    </div>
    {{-- end filter in mobile --}}

@else

    {{-- start filter in desktop --}}
    <div class="col-lg-3 d-lg-block d-none">
        <div class="filter-items position-sticky top-0">
            <div class="container-fluid">
                @include('front::products.partials.category-filters-body', ['scope' => 'd-'])
            </div>
        </div>
    </div>
    {{-- end filter in desktop --}}

@endif
