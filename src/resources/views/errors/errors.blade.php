@extends('front::layouts.master', ['title' => 'خطایی رخ داده است'])

@section('content')
    {{-- صفحه خطای عمومی — سانیار content-box (الگوی 503) --}}
    <div class="content">
        <div class="container-fluid py-4">
            <div class="content-box">
                <div class="container-fluid">
                    <div class="error-page text-center py-5">
                        <div class="mb-4">
                            <i class="bi bi-exclamation-triangle main-color-one-color font-30"></i>
                        </div>
                        <h3 class="mb-4">{{ $message }}</h3>
                        <a href="{{ route('front.index') }}" class="btn border-0 py-3 px-5 fs-6 btn-lg main-color-three-bg rounded-pill">برو به صفحه اصلی</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
