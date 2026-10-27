<!DOCTYPE html>
<html class="loading" lang="en" data-textdirection="rtl">
<!-- BEGIN: Head-->

<head>
    @include('sellers-old.panel.partials.meta')
    <title>
        @isset($title)
            {{  $title }}
        @else
            {{ option('info_site_title', 'او پی شاپ') }}
        @endisset
    </title>

    <!-- BEGIN: Vendor CSS-->
    <link rel="stylesheet" type="text/css" href="{{ asset('back/app-assets/vendors/css/vendors-rtl.min.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('back/app-assets/vendors/css/ui/prism.min.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('back/app-assets/vendors/css/extensions/toastr.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('back/app-assets/vendors/css/forms/select/select2.min.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('back/app-assets/plugins/select2totree/select2totree.css') }}">


    <!-- END: Vendor CSS-->

    <!-- BEGIN: Theme CSS-->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.3.0/css/all.min.css" integrity="sha512-SzlrxWUlpfuzQ+pcUCosxcglQRNAq/DZjVsC0lE40xsADsfeQoEypE+enwcOiGjk/bSuGGKHEyjSoQ1zVisanQ==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <link rel="stylesheet" type="text/css" href="{{ asset('back/app-assets/css-rtl/bootstrap.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('back/app-assets/css-rtl/bootstrap-extended.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('back/app-assets/css-rtl/colors.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('back/app-assets/css-rtl/components.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('back/app-assets/css-rtl/plugins/extensions/toastr.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('back/app-assets/css-rtl/plugins/animate/animate.min.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('back/assets/css/new-style.css') }}">

    <!-- BEGIN: Page CSS-->
    <link rel="stylesheet" type="text/css" href="{{ asset('back/app-assets/css-rtl/core/menu/menu-types/vertical-menu.css') }}">
    @stack('styles')
    <!-- END: Page CSS-->

    @include('sellers-old.panel.partials.global-css')
    <link rel="stylesheet" type="text/css" href="{{ theme_asset('css/seller-panel/styles.css') }}">
    <!-- Scripts -->
    <script>
        window.Laravel = {!! json_encode([
            'user' => auth()->user(),
            'csrfToken' => csrf_token(),
            'vapidPublicKey' => config('webpush.vapid.public_key'),
            'pusher' => [
                'key' => config('broadcasting.connections.pusher.key'),
                'cluster' => config('broadcasting.connections.pusher.options.cluster'),
            ],
        ]) !!};
    </script>

</head>
<!-- END: Head-->

<!-- BEGIN: Body-->

<body class="vertical-layout vertical-menu-modern 2-columns  navbar-floating footer-static {{ user_option('menu_type') == 'collapsed' ? 'menu-collapsed' : '' }}  {{ user_option('theme_color') == 'light' ? '' : 'semi-dark-layout' }}" data-open="click" data-menu="vertical-menu-modern" data-col="2-columns" data-layout="semi-dark-layout">

    <!-- BEGIN: Header-->
    @include('sellers-old.panel.partials.header')
    <!-- END: Header-->


    <!-- BEGIN: Main Menu-->
    @include('sellers-old.panel.partials.menu-sidebar')
    <!-- END: Main Menu-->
    <div class="app-content-wrapper ">
        <div class="content-overlay"></div>
        @if(seller()->status_documents=="Waiting")
            <div class="abilityPostToolTip w-100 mb-2" style="    background: rgb(233 186 53 / 29%);">
                <i>
                    <svg class="icon">
                        <use xlink:href="#lamp">
                            <symbol id="lamp" enable-background="new 0 0 24 24" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="m12 3.457c-.414 0-.75-.336-.75-.75v-1.957c0-.414.336-.75.75-.75s.75.336.75.75v1.957c0 .414-.336.75-.75.75z"></path>
                                <path
                                    d="m18.571 6.179c-.192 0-.384-.073-.53-.22-.293-.293-.293-.768 0-1.061l1.384-1.384c.293-.293.768-.293 1.061 0s.293.768 0 1.061l-1.384 1.384c-.147.146-.339.22-.531.22z"></path>
                                <path
                                    d="m23.25 12.75h-1.957c-.414 0-.75-.336-.75-.75s.336-.75.75-.75h1.957c.414 0 .75.336.75.75s-.336.75-.75.75z"></path>
                                <path
                                    d="m19.955 20.705c-.192 0-.384-.073-.53-.22l-1.384-1.384c-.293-.293-.293-.768 0-1.061s.768-.293 1.061 0l1.384 1.384c.293.293.293.768 0 1.061-.147.147-.339.22-.531.22z"></path>
                                <path
                                    d="m4.045 20.705c-.192 0-.384-.073-.53-.22-.293-.293-.293-.768 0-1.061l1.384-1.384c.293-.293.768-.293 1.061 0s.293.768 0 1.061l-1.384 1.384c-.147.147-.339.22-.531.22z"></path>
                                <path
                                    d="m2.707 12.75h-1.957c-.414 0-.75-.336-.75-.75s.336-.75.75-.75h1.957c.414 0 .75.336.75.75s-.336.75-.75.75z"></path>
                                <path
                                    d="m5.429 6.179c-.192 0-.384-.073-.53-.22l-1.384-1.384c-.293-.293-.293-.768 0-1.061s.768-.293 1.061 0l1.384 1.384c.293.293.293.768 0 1.061-.148.146-.339.22-.531.22z"></path>
                                <path d="m15 21v1.25c0 .96-.79 1.75-1.75 1.75h-2.5c-.84 0-1.75-.64-1.75-2.04v-.96z"></path>
                                <path
                                    d="m16.41 6.56c-1.64-1.33-3.8-1.85-5.91-1.4-2.65.55-4.8 2.71-5.35 5.36-.56 2.72.46 5.42 2.64 7.07.59.44 1 1.12 1.14 1.91v.01c.02-.01.05-.01.07-.01h6c.02 0 .03 0 .05.01v-.01c.14-.76.59-1.46 1.28-2 1.69-1.34 2.67-3.34 2.67-5.5 0-2.12-.94-4.1-2.59-5.44zm-.66 5.94c-.41 0-.75-.34-.75-.75 0-1.52-1.23-2.75-2.75-2.75-.41 0-.75-.34-.75-.75s.34-.75.75-.75c2.34 0 4.25 1.91 4.25 4.25 0 .41-.34.75-.75.75z"></path>
                                <path d="m8.93 19.5h.07c-.02 0-.05 0-.07.01z"></path>
                                <path d="m15.05 19.5v.01c-.02-.01-.03-.01-.05-.01z"></path>
                            </symbol>
                        </use>
                    </svg>
                </i>
                <p class="m-0">فروشنده گرامی وضعیت مدارک شما در حال برسی می باشد! </p>
            </div>
        @elseif(seller()->status_documents=="Reject")
            <div class="abilityPostToolTip w-100 mb-2">
                <i>
                    <svg class="icon">
                        <use xlink:href="#lamp">
                            <symbol id="lamp" enable-background="new 0 0 24 24" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="m12 3.457c-.414 0-.75-.336-.75-.75v-1.957c0-.414.336-.75.75-.75s.75.336.75.75v1.957c0 .414-.336.75-.75.75z"></path>
                                <path
                                    d="m18.571 6.179c-.192 0-.384-.073-.53-.22-.293-.293-.293-.768 0-1.061l1.384-1.384c.293-.293.768-.293 1.061 0s.293.768 0 1.061l-1.384 1.384c-.147.146-.339.22-.531.22z"></path>
                                <path
                                    d="m23.25 12.75h-1.957c-.414 0-.75-.336-.75-.75s.336-.75.75-.75h1.957c.414 0 .75.336.75.75s-.336.75-.75.75z"></path>
                                <path
                                    d="m19.955 20.705c-.192 0-.384-.073-.53-.22l-1.384-1.384c-.293-.293-.293-.768 0-1.061s.768-.293 1.061 0l1.384 1.384c.293.293.293.768 0 1.061-.147.147-.339.22-.531.22z"></path>
                                <path
                                    d="m4.045 20.705c-.192 0-.384-.073-.53-.22-.293-.293-.293-.768 0-1.061l1.384-1.384c.293-.293.768-.293 1.061 0s.293.768 0 1.061l-1.384 1.384c-.147.147-.339.22-.531.22z"></path>
                                <path
                                    d="m2.707 12.75h-1.957c-.414 0-.75-.336-.75-.75s.336-.75.75-.75h1.957c.414 0 .75.336.75.75s-.336.75-.75.75z"></path>
                                <path
                                    d="m5.429 6.179c-.192 0-.384-.073-.53-.22l-1.384-1.384c-.293-.293-.293-.768 0-1.061s.768-.293 1.061 0l1.384 1.384c.293.293.293.768 0 1.061-.148.146-.339.22-.531.22z"></path>
                                <path d="m15 21v1.25c0 .96-.79 1.75-1.75 1.75h-2.5c-.84 0-1.75-.64-1.75-2.04v-.96z"></path>
                                <path
                                    d="m16.41 6.56c-1.64-1.33-3.8-1.85-5.91-1.4-2.65.55-4.8 2.71-5.35 5.36-.56 2.72.46 5.42 2.64 7.07.59.44 1 1.12 1.14 1.91v.01c.02-.01.05-.01.07-.01h6c.02 0 .03 0 .05.01v-.01c.14-.76.59-1.46 1.28-2 1.69-1.34 2.67-3.34 2.67-5.5 0-2.12-.94-4.1-2.59-5.44zm-.66 5.94c-.41 0-.75-.34-.75-.75 0-1.52-1.23-2.75-2.75-2.75-.41 0-.75-.34-.75-.75s.34-.75.75-.75c2.34 0 4.25 1.91 4.25 4.25 0 .41-.34.75-.75.75z"></path>
                                <path d="m8.93 19.5h.07c-.02 0-.05 0-.07.01z"></path>
                                <path d="m15.05 19.5v.01c-.02-.01-.03-.01-.05-.01z"></path>
                            </symbol>
                        </use>
                    </svg>
                </i>
                <p class="m-0">فروشنده گرامی مدارک شما مورد تایید نمی باشد.لطفا مجددا بارگذاری کنید! </p>
            </div>
        @endif
            @yield('content')

    </div>
    <!-- BEGIN: Content-->

    <!-- END: Content-->

    <div class="sidenav-overlay"></div>
    <div class="drag-target"></div>

    <!-- BEGIN: Footer-->
    @include('sellers-old.panel.partials.footer')
    <!-- END: Footer-->

    <audio id="notification-sound" class="d-none">
        <source src="{{ asset('back/app-assets/sounds/notification.ogg') }}" type="audio/ogg">
        Your browser does not support the audio element.
    </audio>

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

    @foreach(seller()->notifications()->where(['popup'=>1,'read'=>0])->get() as $notification)
        <div class="modal fade text-left " id="notification-confirm-modal-{{$notification->id}}" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-md" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">×</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class=" text-center">
                            <img style="margin: 0 auto 20px" src="{{theme_asset('images/a2fa83da.svg')}}">
                        </div>
                        <h4 class="text-center mb-1">{{$notification->title}}</h4>
                       <p>
                           {!! $notification->message !!}
                       </p>
                    </div>
                </div>
            </div>
        </div>
    @endforeach

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


    <script src="{{ asset('back/assets/js/customToast.js') }}?v=8"></script>
    <script src="{{ asset('back/assets/js/helpers.js') }}?v=8"></script>
    <script src="{{ asset('back/assets/js/scripts.js') }}?v=11"></script>
    <script src="{{ theme_asset('js/seller-panel/scripts.js') }}?v=11"></script>
    <!-- END: Theme JS-->

    <script>
        var BASE_URL = "{{ route('seller.index') }}";
        var APP_FONT_FAMILY = "{{ user_option('theme_font', 'iranyekan') }}";
        @foreach(seller()->notifications()->where('popup',1)->get() as $notification)
        $('#notification-confirm-modal-{{$notification->id}}').modal('show');
        @endforeach
    </script>

    <!-- BEGIN: Page JS-->
    @stack('scripts')

    <!-- END: Page JS-->

    <script src="{{ asset(mix('js/app.js')) }}"></script>
    <script src="{{ asset('back/assets/js/echo.js') }}"></script>
    <script src="{{ asset('back/assets/js/web-push.js') }}"></script>

    @if(session('toast-success'))
        <script>
            showCustomToast('{{session('toast-success')}}','success')
        </script>
    @endif
    @if(session('toast-error'))
        <script>
            toastr.error('{{session('toast-error')}}', null,{ positionClass: 'toast-bottom-left', containerId: 'toast-bottom-left' });
        </script>
    @endif
    @if(session('toast-warning'))
        <script>
            showCustomToast('{{session('toast-warning')}}','warning');
        </script>
    @endif
</body>
<!-- END: Body-->

</html>

@php session()->forget('toast-success'); @endphp
@php session()->forget('toast-error'); @endphp
@php session()->forget('toast-warning'); @endphp
