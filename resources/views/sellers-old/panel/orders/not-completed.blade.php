@extends('sellers-old.panel.layouts.master')

@section('content')

    <div class="c-content-page c-content-page--plain c-grid__row w-100 mb-2">
        <div class="c-grid__col">
            <div class="c-content-page__header">
                <span class="c-content-page__header-action">محصولات منتظر ارسال</span>
                <span class="c-content-page__header-desc">اینجا می‌توانید تمام محصولاتی که ار انتظار ارسال هستند را ببینید.</span>
            </div>
        </div>
    </div>

    @include('sellers-old.panel.partials.sidebar')

    <div class="col-lg-9 col-md-8 col-xs-12 pull-right pr-0">
        <div class="row dashboard-steps-3">
            <div class="col-12 dashboard-steps-3-item ">
                <div class="c-card">
                    <div class="c-card__header d-flex pt-1 pb-1">
                        <h2 class="c-card__title line-height-40">محصولات منتظر ارسال</h2>
                    </div>
                    <div class="content-body">



                                <div class="card-content" id="main-card">
                                    @if($prices->count())
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-striped mb-0">
                                                <thead>
                                                <tr>
                                                    <th class="text-center"></th>
                                                    <th class="text-center"></th>
                                                    <th>عنوان</th>
                                                    <th class="text-center">تعداد</th>
                                                </tr>
                                                </thead>
                                                <tbody>
                                                @foreach ($prices as $price)
                                                    <tr>
                                                        <td class="text-center">
                                                            {{ $loop->iteration }}
                                                        </td>
                                                        <td>
                                                            <img class="post-thumb" src="{{ $price->product->imageUrl() }}" alt="image">
                                                        </td>
                                                        <td>
                                                            <a href="{{ $price->product->link() }}" target="_blank">
                                                                {{ $price->product->title }}
                                                            </a>
                                                            <p>
                                                                {{ $price->getAttributesName() }}
                                                            </p>

                                                        </td>
                                                        <td class="text-center">{{ $price->pendingToSend() }}</td>

                                                    </tr>
                                                @endforeach

                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                    @else
                                        <div class="card-content">
                                            <div class="card-body">
                                                <div class="card-text">
                                                    <p>چیزی برای نمایش وجود ندارد!</p>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                        <div class="card-body">
                                    {{ $prices->links() }}
                                        </div>
                                </div>










                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
