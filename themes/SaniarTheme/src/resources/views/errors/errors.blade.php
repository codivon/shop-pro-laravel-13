@extends('front::layouts.master', ['title' => 'خطایی رخ داده است'])

@section('content')
    <!-- Start main-content -->
    <main class="main-content dt-sl mt-4 mb-3">
        <div class="container main-container">

            <div class="row">
                <div class="col-12">
                    <div class="dt-sl dt-sn pt-3 pb-5 py-5">
                        <div class="error-page text-center py-5">
                            <h3 class="mb-5">{{ $message }}</h3>
                            <a href="{{ route('front.index') }}" class="btn-primary-cm">برو به صفحه اصلی</a>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </main>
    <!-- End main-content -->
@endsection
