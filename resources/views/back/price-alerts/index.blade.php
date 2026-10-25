@extends('back.layouts.master')

@section('content')

    <div class="app-content content">
        <div class="content-overlay"></div>
        <div class="header-navbar-shadow"></div>
        <div class="content-wrapper">
            <div class="content-header row">
                <div class="content-header-left col-md-9 col-12 mb-2">
                    <div class="row breadcrumbs-top">
                        <div class="col-12">
                            <div class="breadcrumb-wrapper col-12">
                                <ol class="breadcrumb no-border">
                                    <li class="breadcrumb-item">مدیریت
                                    </li>
                                    <li class="breadcrumb-item active">هشدار کاهش قیمت
                                    </li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="content-body">

                @if($priceAlerts->count())
                    <section class="card">
                        <div class="card-header">
                            <h4 class="card-title">لیست هشدارهای کاهش قیمت</h4>
                        </div>
                        <div class="card-content" id="main-card">
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped mb-0">
                                        <thead>
                                            <tr>
                                                <th class="text-center">#</th>
                                                <th>محصول</th>
                                                <th>مشتری</th>
                                                <th>شماره موبایل</th>
                                                <th>راه اطلاع‌رسانی</th>
                                                <th>قیمت هدف</th>
                                                <th class="text-center">وضعیت</th>
                                                <th class="text-center">عملیات</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($priceAlerts as $priceAlert)
                                                <tr id="price_alert-{{ $priceAlert->id }}-tr">
                                                    <td class="text-center">
                                                        {{ $priceAlert->id }}
                                                    </td>
                                                    <td>{{ $priceAlert->product->title }} <a href="{{ Route::has('front.products.show') ? route('front.products.show', ['product' => $priceAlert->product]) : '' }}" target="_blank"><i class="feather icon-external-link"></i></a></td>

                                                    <td>
                                                        {{-- فاز ۸۳ — نوع مشتری + نام --}}
                                                        <span class="badge badge-light-{{ $priceAlert->user_id ? 'info' : 'secondary' }}">{{ $priceAlert->user_id ? 'کاربر عضو' : 'مهمان' }}</span>
                                                        {{ $priceAlert->name }}
                                                    </td>
                                                    <td>{{ $priceAlert->mobile }}</td>
                                                    <td>
                                                        {{-- فاز ۸۳ — کانال‌های انتخابی مشتری --}}
                                                        @if($priceAlert->notify_sms || (!$priceAlert->notify_sms && !$priceAlert->notify_profile))
                                                            <span class="badge badge-light-primary">پیامک</span>
                                                        @endif
                                                        @if($priceAlert->notify_profile)
                                                            <span class="badge badge-light-warning">اعلان پروفایل</span>
                                                        @endif
                                                    </td>
                                                    <td>{{ $priceAlert->target_price !== null ? number_format($priceAlert->target_price) . ' تومان' : '—' }}</td>
                                                    <td class="text-center">
                                                        @if($priceAlert->active)
                                                            <span class="badge badge-light-success">در انتظار افت قیمت</span>
                                                        @else
                                                            <span class="badge badge-light-secondary" title="{{ \Morilog\Jalali\Jalalian::fromDateTime($priceAlert->notified_at)->format('Y/m/d H:i') }}">اعلان شده</span>
                                                        @endif
                                                    </td>

                                                    <td class="text-center">
                                                        <div class="dropdown dropdown-action">
                                                            <button class="btn btn-secondary dropdown-toggle" type="button" id="dropdownMenu{{ $priceAlert->id }}" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                                <i class="fa-solid fa-ellipsis-vertical"></i>
                                                            </button>
                                                            <div class="dropdown-menu" aria-labelledby="dropdownMenu{{ $priceAlert->id }}">
                                                                <form action="{{ route('admin.price-alerts.destroy', $priceAlert->id) }}" method="POST" class="m-0">
                                                                    @csrf
                                                                    @method('DELETE')
                                                                    <button type="submit" class="dropdown-item text-danger"><i class="fa-solid fa-trash-can mr-1"></i> حذف</button>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach

                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </section>

                @else
                    <section class="card">
                        <div class="card-header">
                            <h4 class="card-title">لیست هشدارهای کاهش قیمت</h4>
                        </div>
                        <div class="card-content">
                            <div class="card-body">
                                <div class="card-text">
                                    <p>چیزی برای نمایش وجود ندارد!</p>
                                </div>
                            </div>
                        </div>
                    </section>
                @endif
                {{ $priceAlerts->links() }}


            </div>
        </div>
    </div>

@endsection
