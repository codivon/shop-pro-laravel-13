@extends('front::layouts.master', ['title' => 'خطا صفحه یافت نشد'])

@section('content')
    {{-- 404 — سانیار content-box --}}
    <div class="content">
        <div class="container-fluid py-4">
            <div class="content-box">
                <div class="container-fluid">
                    <div class="page-404">
                        <h2 class="h1 mb-1 text-center">صفحه‌ای که دنبال آن بودید پیدا نشد!</h2>
                        <img src="{{ theme_asset('image/404-Error.svg') }}" class="img-fluid" height="150" alt="404">
                    </div>
                    <div class="text-center">
                        <a href="{{ route('front.index') }}" class="btn border-0 py-3 px-5 fs-6 btn-lg main-color-three-bg rounded-pill">صفحه اصلی</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
