@extends('sellers-old.panel.layouts.master')
@push('styles')
    <link rel="stylesheet" type="text/css" href="{{ theme_asset('js/pages/sellers/notifications/style.css') }}">
@endpush
@section('content')
    <div class="c-content-page c-content-page--plain c-grid__row w-100 mb-2">
        <div class="c-grid__col">
            <div class="c-content-page__header">
                <span class="c-content-page__header-action">اعلان ها</span>
                <span class="c-content-page__header-desc">ینجا می‌توانید اعلان‌های ارسال شده را ببینید</span>
            </div>
        </div>
    </div>
    @include('sellers-old.panel.partials.sidebar')

    <div class="col-lg-9 col-md-8 col-xs-12 pull-right pr-0">
        <div class="row dashboard-steps-3">
            <div class="col-12 dashboard-steps-3-item ">
                <div class="c-card">
                    <div class="c-card__header d-flex pt-1 pb-1">
                        <h2 class="c-card__title line-height-40">لیست اعلان ها</h2>
                        <div class="line-height-40 w-15"><span class="pl-1 color-text-low-emphasis text-body-1">تعداد نتایج:</span><span class="color-text-high-emphasis text-body1-strong">{{count($notifications_count)}}</span></div>
                    </div>
                    <div class="card-content" id="main-card">
                       <div class="card-body">
                           <form id="filter-notifications-form">
                                <div class="row">


                               <div class="col-md-3">
                                   <label>وضعیت اعلان‌ها</label>
                                   <fieldset class="form-group">
                                       <select class="form-control datatable-filter" name="priority">
                                           <option value="all" {{ request('priority') == 'all' ? 'selected' : '' }}>
                                               همه
                                           </option>
                                           <option value="high" {{ request('priority') == 'high' ? 'selected' : '' }}>
                                               بسیار مهم
                                           </option>
                                           <option value="medium" {{ request('priority') == 'medium' ? 'selected' : '' }}>
                                               مهم
                                           </option>
                                           <option value="low" {{ request('priority') == 'low' ? 'selected' : '' }}>
                                               عادی
                                           </option>
                                       </select>
                                   </fieldset>
                               </div>

                               <div class="col-md-3">
                                   <label>حالت نمایش </label>
                                   <fieldset class="form-group">
                                       <select class="form-control datatable-filter" name="sort">
                                           <option value="latest" {{ request('sort') == 'latest' ? 'selected' : '' }}>
                                               جدیدترین
                                           </option>
                                           <option value="priority" {{ request('sort') == 'priority' ? 'selected' : '' }}>
                                               مهم ترین
                                           </option>
                                       </select>
                                   </fieldset>
                               </div>
                                    <div class="col-md-4 mb-2">
                                        <fieldset class="checkbox">
                                            <div class="vs-checkbox-con vs-checkbox-primary mt-3">
                                                <input type="checkbox" name="unread" {{ request('unread') == 'on' ? 'checked' : '' }}>
                                                <span class="vs-checkbox">
                                                            <span class="vs-checkbox--check">
                                                                <i class="vs-icon feather icon-check"></i>
                                                            </span>
                                                        </span>
                                                <span>خوانده نشده</span>
                                            </div>
                                        </fieldset>
                                    </div>

                           </div>
                           </form>

                           <div class="info-page-faq">
                               <div class="content-info-page">
                                   @if(count($notifications))
                                   @foreach($notifications as $notification)
                                   <div class="toggle-box">
                                       <div class="toggle-box-header">
                                           <div class="d-flex">
                                               <span><i class="far fa-bell"></i></span>
                                               <span class="ml-1"> <div class="badge badge-{{$notification->priorityText()['color']}}">{{ $notification->priorityText()['title'] }}</div></span>
                                           </div>

                                           <div>{{ jdate($notification->created_at)->format('%d %B %Y') }}</div>
                                       </div>
                                       <div class="toggle-box-active">
                                           <ul>
                                               @php $users_notifications = \Illuminate\Support\Facades\DB::table('notification_manage_users')->where(['seller_id'=>sellerID(),'notification_manage_id'=>$notification->id])->first(); @endphp
                                               <li class="has-sub">
                                                   <a data-action="{{route('seller.notifications.read',['notification'=>$notification])}}" data-read="{{$users_notifications->read ? 'yes' : 'no'}}">{{$notification->title}}</a>
                                                   <ul class="pl-1">
                                                       <li class="has-sub"><a>{!! $notification->message !!}</a></li>
                                                   </ul>
                                               </li>

                                           </ul>
                                       </div>
                                   </div>
                                   @endforeach
                                   @else
                                       <div class="card-content">
                                           <div class="card-body">
                                               <div class="card-text">
                                                   <p>چیزی برای نمایش وجود ندارد!</p>
                                               </div>
                                           </div>
                                       </div>
                                   @endif
                               </div>
                           </div>
                           {{ $notifications->links() }}
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@push('scripts')

    <script src="{{ theme_asset('js/pages/sellers/notifications/index.js') }}?v=4"></script>
@endpush
