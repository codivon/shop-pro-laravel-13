{{-- مگا منوی سانیار — دسکتاپ --}}
<div class="mega-menu d-lg-block d-none">
    <div class="container-fluid">
        <div class="top-menu-parent">
            <div class="row align-items-center">
                <div class="col-lg-9">
                    <div class="top-menu-menu">
                        <ul class="navbar-nav align-items-center">
                            @foreach($menus as $menu)
                                @include('front::partials.menu.child-menu')
                            @endforeach
                        </ul>
                    </div>
                </div>
                <div class="col-lg-3">
                    <div class="d-flex align-items-center justify-content-end">
                        @if(option('social_whatsapp'))
                            <a href="{{ option('social_whatsapp') }}"
                               class="main-color-three-bg btn border-0 d-flex align-items-center rounded-pill">{{ option('info_tel', '') }}<i
                                    class="bi bi-whatsapp text-white ms-2"></i></a>
                        @endif
                        @if(option('info_tel'))
                            <a href="tel:{{ option('info_tel', '') }}"
                               class="main-color-two-bg btn border-0 d-flex align-items-center {{ option('social_whatsapp') ? 'ms-3' : '' }} rounded-pill">{{ option('info_tel', '') }}<i
                                    class="bi bi-telephone-fill ms-2 text-white"></i></a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
