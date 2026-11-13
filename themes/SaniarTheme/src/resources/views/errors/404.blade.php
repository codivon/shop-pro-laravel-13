@extends('front::layouts.master', ['title' => 'خطا صفحه یافت نشد'])

@section('content')
    <!--  404-------------------------------->
    <div class="page-404">
        <h1 class="title-404">صفحه‌ای که دنبال آن بودید پیدا نشد!</h1>
        <a href="/" class="action-404">صفحه اصلی</a>
        <img src="{{ theme_asset("images/images-404.png") }}" class="images-404" alt="404">
    </div>
    <!--  404-------------------------------->

@endsection
