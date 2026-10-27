@extends('sellers-old.panel.layouts.master')

@section('content')
    <div class="c-content-page c-content-page--plain c-grid__row w-100 mb-2">
        <div class="c-grid__col">
            <div class="c-content-page__header">
                <span class="c-content-page__header-action">درخواست‌های شما</span>
                <span class="c-content-page__header-desc">اینجا می‌توانید تمام درخواست‌های خود را ببینید.</span>
            </div>
        </div>
    </div>

    @include('sellers-old.panel.partials.sidebar')

    <div class="col-lg-9 col-md-8 col-xs-12 pull-right pr-0">
        <div class="row dashboard-steps-3">
            <div class="col-12 dashboard-steps-3-item ">
                <div class="c-card">
                    <div class="c-card__header d-flex pt-1 pb-1">
                        <h2 class="c-card__title line-height-40">لیست درخواست ها</h2>
                        <a href="{{route('seller.tickets.create')}}"><div class="c-mega-campaigns__btns-green-plus uk-margin-remove">
                                ثبت درخواست پشتیبانی
                                <i class="fa-solid fa-plus mr-0-5"></i>
                            </div>
                        </a>
                    </div>
                    <div class="card-content" id="main-card">
                        @if($tickets->count())
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped mb-0">
                                    <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>شماره درخواست</th>
                                        <th>موضوع</th>
                                        <th>تاریخ ثبت درخواست</th>
                                        <th>اولویت</th>
                                        <th>وضعیت</th>
                                        <th class="text-center">جزییات</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach ($tickets as $ticket)
                                        <tr>
                                            <td>{{ $loop->iteration}}</td>
                                            <td class="text-info">{{ $ticket->id }}</td>
                                            <td>{{ $ticket->subject }}</td>
                                            <td>{{ jdate($ticket->created_at)->format('%d %B %Y') }}</td>
                                            <td>{{ $ticket->priorityText() }}</td>
                                            <td>
                                                {{ $ticket->statusText() }}
                                            </td>
                                            <td class="details-link text-center">
                                                <a href="{{ route('seller.tickets.show', ['ticket' => $ticket]) }}" class="btn waves-effect waves-light c-ui-btn--add-similar display-inline-block mt-1">نمایش جزئیات</a>
                                            </td>
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
                            {{ $tickets->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
