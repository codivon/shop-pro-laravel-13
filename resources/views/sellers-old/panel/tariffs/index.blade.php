@extends('sellers-old.panel.layouts.master')

@section('content')
    <div class="c-content-page c-content-page--plain c-grid__row w-100 mb-2">
        <div class="c-grid__col">
            <div class="c-content-page__header">
                <span class="c-content-page__header-action"> حمل و نقل</span>
                <span class="c-content-page__header-desc">برای تعیین هزینه ارسال، فعال یا غیرفعال کردن سرویس‌های پستی و تنظیم محدوده ارسال از این قسمت استفاده نمایید.</span>

            </div>
        </div>
    </div>
    @include('sellers-old.panel.partials.sidebar')
    <div class="app-content content">
        <div class="">
            <div class="content-body">

                <section class="card">
                    <div class="card-header">
                        <h4 class="card-title">لیست تعرفه ها</h4>
                        <div>
                            <a href="{{ route('seller.tariffs.create', ['carrier' => $carrier]) }}" class="btn personal-info-btn waves-effect waves-light"><i class="feather icon-plus"></i> ایجاد تعرفه</a>
                        </div>
                    </div>

                    <div class="card-content" id="main-card">
                        <div class="card-body">
                            @if ($tariffs->count())
                                <div class="table-responsive">
                                    <table class="table table-striped mb-0">
                                        <thead>
                                            <tr>
                                                <th>ردیف</th>
                                                <th>نوع منطقه ارسالی</th>
                                                <th>حداکثر وزن (گرم)</th>
                                                <th>هزینه ارسال (تومان)</th>
                                                <th class="text-center" style='width: 150px'>عملیات</th>
                                            </tr>
                                        </thead>

                                        <tbody>
                                            @foreach ($tariffs as $tariff)
                                                <tr>
                                                    <td>{{ $loop->iteration }}</td>
                                                    <td>{{ $tariff->type() }}</td>
                                                    <td>{{ number_format($tariff->max_weight) }}</td>
                                                    <td>{{ number_format($tariff->shipping_cost) }}</td>

                                                    <td class="text-center">
                                                        <div class="dropdown dropdown-action">
                                                            <button class="btn btn-secondary dropdown-toggle" type="button" id="dropdownMenu{{ $tariff->id }}" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                                <i class="fa-solid fa-ellipsis-vertical"></i>
                                                            </button>
                                                            <div class="dropdown-menu" aria-labelledby="dropdownMenu{{ $tariff->id }}">

                                                                <a class="dropdown-item" href="{{ route('seller.tariffs.edit', ['tariff' => $tariff]) }}"><i class="fa-solid fa-pencil mr-1"></i>ویرایش</a>
                                                                <div class="dropdown-divider"></div>

                                                                <button class="dropdown-item btn-delete" data-action="{{ route('seller.tariffs.destroy', ['tariff' => $tariff]) }}"  data-toggle="modal" data-target="#delete-modal"><i class="fa-solid fa-trash-can mr-1"></i> حذف</button>
                                                            </div>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="card-text">
                                    <p>چیزی برای نمایش وجود ندارد!</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </section>

                {{ $tariffs->links() }}

            </div>
        </div>
    </div>

    {{-- delete tariff modal --}}
    <div class="modal fade text-left" id="delete-modal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel19" style="display: none;" aria-hidden="true">
        <div class="modal-dialog modal-sm" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title" id="myModalLabel19">آیا مطمئن هستید؟</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">
                    آیا میخواهید این تعرفه را حذف کنید؟
                </div>
                <div class="modal-footer">
                    <form action="#" id="tariff-delete-form">
                        @csrf
                        @method('delete')
                        <button type="button" class="btn personal-success-btn waves-effect waves-light" data-dismiss="modal">خیر</button>
                        <button type="submit" class="btn personal-danger-btn waves-effect waves-light">بله حذف شود</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script src="{{ asset('back/assets/js/pages/tariffs/index.js') }}"></script>
@endpush
