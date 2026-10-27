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
                        <h4 class="card-title">لیست روش های ارسال</h4>
                        <div>
                            <a href="{{ route('seller.carriers.create') }}" class="btn personal-info-btn waves-effect waves-light"><i class="feather icon-plus"></i> ایجاد روش ارسال</a>
                        </div>
                    </div>
                    <div class="card-content" id="main-card">
                        <div class="card-body">
                            @if ($carriers->count())
                                <div class="table-responsive">
                                    <table class="table table-striped mb-0">
                                        <thead>
                                            <tr>
                                                <th>ردیف</th>
                                                <th>عنوان</th>
                                                <th>شهر فروشگاه</th>
                                                <th>شهرهای تحت پوشش</th>
                                                <th>پس کرایه</th>
                                                <th class="text-center">وضعیت</th>
                                                <th class="text-center">عملیات</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($carriers as $carrier)

                                                <tr>
                                                    <td>{{ $loop->iteration }}</td>
                                                    <td>{{ $carrier->title }}</td>
                                                    <td>{{ $carrier->province->name }} - {{ $carrier->city->name }}</td>
                                                    <td>
                                                        @if ($carrier->covered_cities == 'all')
                                                            <span>همه</span>
                                                        @else
                                                            <abbr title="مشاهده لیست شهرها"><a class="carrier-cities-show" href="{{ route('admin.carriers.cities', ['carrier' => $carrier]) }}">لیست شهرها</a></abbr>
                                                        @endif
                                                    </td>
                                                    <td>{{ $carrier->carrige_forward ? 'بله' : 'خیر' }}</td>
                                                    <td class="text-center">
                                                        @if($carrier->is_active)
                                                            <div class="badge badge-success">فعال</div>
                                                        @else
                                                            <div class="badge badge-danger">غیر فعال</div>
                                                        @endif
                                                    </td>
                                                    <td class="text-center">
                                                        <div class="dropdown dropdown-action">
                                                            <button class="btn btn-secondary dropdown-toggle" type="button" id="dropdownMenu{{ $carrier->id }}" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                                <i class="fa-solid fa-ellipsis-vertical"></i>
                                                            </button>
                                                            <div class="dropdown-menu" aria-labelledby="dropdownMenu{{ $carrier->id }}">
                                                                @if ($carrier->carrige_forward)
                                                                    <button class="dropdown-item " ><i class="fa-solid fa-bars"></i> تعرفه ها</button>
                                                                @else
                                                                <a class="dropdown-item" href="{{ route('seller.tariffs.index', ['carrier' => $carrier]) }}"><i class="fa-solid fa-bars"></i>تعرفه ها</a>
                                                                @endif
                                                                <div class="dropdown-divider"></div>

                                                                <a class="dropdown-item" href="{{ route('seller.carriers.edit', ['carrier' => $carrier]) }}"><i class="fa-solid fa-pencil mr-1"></i>ویرایش</a>
                                                                 <div class="dropdown-divider"></div>

                                                                <button class="dropdown-item btn-delete" data-action="{{ route('seller.carriers.destroy', ['carrier' => $carrier]) }}"  data-toggle="modal" data-target="#delete-modal"><i class="fa-solid fa-trash-can mr-1"></i> حذف</button>
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

                {{ $carriers->links() }}

            </div>
        </div>
    </div>

    {{-- delete carrier modal --}}
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
                    با حذف روش ارسال دیگر قادر به بازیابی آن نخواهید بود
                </div>
                <div class="modal-footer">
                    <form action="#" id="carrier-delete-form">
                        @csrf
                        @method('delete')
                        <button type="button" class="btn personal-success-btn waves-effect waves-light" data-dismiss="modal">خیر</button>
                        <button type="submit" class="btn personal-danger-btn waves-effect waves-light">بله حذف شود</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- show Modal -->
    <div class="modal fade text-left" id="show-modal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title" id="myModalLabel21">لیست شهرهای تحت پوشش</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div id="carrier-cities-list" class="modal-body">


                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')

    <script src="{{ theme_asset('js/seller-panel/pages/carriers/index.js') }}"></script>
@endpush
