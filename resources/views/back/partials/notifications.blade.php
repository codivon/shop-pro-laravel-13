<li class="dropdown dropdown-notification nav-item"><a class="nav-link nav-link-label"
        data-toggle="dropdown"><i class="ficon feather icon-bell"></i><span
            class="badge badge-primary badge-up">{{ $notifications->count() ?: '' }}</span></a>
    <ul class="dropdown-menu dropdown-menu-media dropdown-menu-right">
        <li class="dropdown-menu-header">
            <div class="dropdown-header m-0 p-2">
                <h4 class="white">{{ $notifications->count() }} اعلان جدید</h4>
            </div>
        </li>
        <li class="scrollable-container media-list">
            @foreach ($notifications as $notification)
                @if($notification->type == 'OrderPaid')
                    @can('orders.view')
                    <a class="d-flex justify-content-between" href="{{ route('admin.notifications') }}">
                        <div class="media d-flex align-items-start">
                            <div class="media-left"><i class="feather icon-plus-square font-medium-5 primary"></i></div>
                            <div class="media-body">
                                <h6 class="primary media-heading">سفارش جدید ثبت شد</h6><small
                                    class="notification-text">{{ $notification->data['message'] }}</small>
                            </div><small>
                                <time class="media-meta">{{ jdate($notification->created_at)->ago() }}</time></small>
                        </div>
                    </a>
                    @endcan
                @elseif($notification->type == 'SellerRegistered')
                    @can('sellers.view')
                    <a class="d-flex justify-content-between" href="{{ route('admin.notifications') }}">
                        <div class="media d-flex align-items-start">
                            <div class="media-left"><i class="feather icon-user font-medium-5 success"></i></div>
                            <div class="media-body">
                                <h6 class="success media-heading">فروشنده جدید ثبت نام کرد</h6><small
                                    class="notification-text">{{ $notification->data['message'] }}</small>
                            </div><small>
                                <time class="media-meta">{{ jdate($notification->created_at)->ago() }}</time></small>
                        </div>
                    </a>
                    @endcan
                @elseif($notification->type == 'SellerEditProfile')
                    @can('sellers.update')
                    <a class="d-flex justify-content-between" href="{{ route('admin.notifications') }}">
                        <div class="media d-flex align-items-start">
                            <div class="media-left"><i class="feather icon-user font-medium-5 success"></i></div>
                            <div class="media-body">
                                <h6 class="success media-heading">فروشنده اطلاعات خود را ویرایش کرد</h6><small
                                    class="notification-text">{{ $notification->data['message'] }}</small>
                            </div><small>
                                <time class="media-meta">{{ jdate($notification->created_at)->ago() }}</time></small>
                        </div>
                    </a>
                    @endcan
                @elseif($notification->type == 'UserRegistered')
                    @can('users.view')
                    <a class="d-flex justify-content-between" href="{{ route('admin.notifications') }}">
                        <div class="media d-flex align-items-start">
                            <div class="media-left"><i class="feather icon-user font-medium-5 success"></i></div>
                            <div class="media-body">
                                <h6 class="success media-heading">کاربر جدید ثبت نام کرد</h6><small
                                    class="notification-text">{{ $notification->data['message'] }}</small>
                            </div><small>
                                <time class="media-meta">{{ jdate($notification->created_at)->ago() }}</time></small>
                        </div>
                    </a>
                    @endcan
                @elseif($notification->type == 'ContactCreated')
                    @can('contacts.index')
                    <a class="d-flex justify-content-between" href="{{ route('admin.notifications') }}">
                        <div class="media d-flex align-items-start">
                            <div class="media-left"><i class="feather icon-message-square font-medium-5 info"></i></div>
                            <div class="media-body">
                                <h6 class="info media-heading">پیام جدید دریافت شد</h6><small
                                    class="notification-text">{{ $notification->data['message'] }}</small>
                            </div><small>
                                <time class="media-meta">{{ jdate($notification->created_at)->ago() }}</time></small>
                        </div>
                    </a>
                    @endcan
                @elseif($notification->type == 'TicketCreated')
                    @can('tickets.show')
                    <a class="d-flex justify-content-between" href="{{ route('admin.notifications') }}">
                        <div class="media d-flex align-items-start">
                            <div class="media-left"><i class="feather icon-message-square font-medium-5 info"></i></div>
                            <div class="media-body">
                                <h6 class="info media-heading">تیکت جدید دریافت شد</h6><small
                                    class="notification-text">{{ $notification->data['message'] }}</small>
                            </div><small>
                                <time class="media-meta">{{ jdate($notification->created_at)->ago() }}</time></small>
                        </div>
                    </a>
                    @endcan

                @elseif($notification->type == 'CommentPostCreated')
                    @can('comments.index')
                    <a class="d-flex justify-content-between" href="{{ route('admin.notifications') }}">
                        <div class="media d-flex align-items-start">
                            <div class="media-left"><i class="feather icon-message-square font-medium-5 info"></i></div>
                            <div class="media-body">
                                <h6 class="info media-heading">پیام جدید دریافت شد</h6><small
                                    class="notification-text">{{ $notification->data['message'] }}</small>
                            </div><small>
                                <time class="media-meta">{{ jdate($notification->created_at)->ago() }}</time></small>
                        </div>
                    </a>
                    @endcan
                @elseif($notification->type == 'CommentProductCreated')
                    @can('comments.index')
                    <a class="d-flex justify-content-between" href="{{ route('admin.notifications') }}">
                        <div class="media d-flex align-items-start">
                            <div class="media-left"><i class="feather icon-message-square font-medium-5 info"></i></div>
                            <div class="media-body">
                                <h6 class="info media-heading">پیام جدید دریافت شد</h6><small
                                    class="notification-text">{{ $notification->data['message'] }}</small>
                            </div><small>
                                <time class="media-meta">{{ jdate($notification->created_at)->ago() }}</time></small>
                        </div>
                    </a>
                    @endcan
                @elseif($notification->type == 'UserRequestDeposit')
                    @can('request_deposit')
                    <a class="d-flex justify-content-between" href="{{ route('admin.notifications') }}">
                        <div class="media d-flex align-items-start">
                            <div class="media-left"><i class="feather icon-message-square font-medium-5 info"></i></div>
                            <div class="media-body">
                                <h6 class="info media-heading">پیام جدید دریافت شد</h6><small
                                    class="notification-text">{{ $notification->data['message'] }}</small>
                            </div><small>
                                <time class="media-meta">{{ jdate($notification->created_at)->ago() }}</time></small>
                        </div>
                    </a>
                    @endcan
                @elseif($notification->type == 'SellerRequestDeposit')
                    @can('request_deposit')
                    <a class="d-flex justify-content-between" href="{{ route('admin.notifications') }}">
                        <div class="media d-flex align-items-start">
                            <div class="media-left"><i class="feather icon-message-square font-medium-5 info"></i></div>
                            <div class="media-body">
                                <h6 class="info media-heading">پیام جدید دریافت شد</h6><small
                                    class="notification-text">{{ $notification->data['message'] }}</small>
                            </div><small>
                                <time class="media-meta">{{ jdate($notification->created_at)->ago() }}</time></small>
                        </div>
                    </a>
                    @endcan
                @elseif($notification->type == 'QuestionProductCreated')
                    @can('comments.index')
                    <a class="d-flex justify-content-between" href="{{ route('admin.notifications') }}">
                        <div class="media d-flex align-items-start">
                            <div class="media-left"><i class="feather icon-message-square font-medium-5 info"></i></div>
                            <div class="media-body">
                                <h6 class="info media-heading">پیام جدید دریافت شد</h6><small
                                    class="notification-text">{{ $notification->data['message'] }}</small>
                            </div><small>
                                <time class="media-meta">{{ jdate($notification->created_at)->ago() }}</time></small>
                        </div>
                    </a>
                    @endcan
                @elseif($notification->type == 'SellerProductCreated')
                    @can('sellers.products')
                    <a class="d-flex justify-content-between" href="{{ route('admin.notifications') }}">
                        <div class="media d-flex align-items-start">
                            <div class="media-left"><i class="fa-solid fa-cart-shopping"></i></div>
                            <div class="media-body">
                                <h6 class="info media-heading">محصول جدید ثبت شد</h6><small
                                    class="notification-text">{{ $notification->data['message'] }}</small>
                            </div><small>
                                <time class="media-meta">{{ jdate($notification->created_at)->ago() }}</time></small>
                        </div>
                    </a>
                    @endcan
                @elseif($notification->type == 'SellerProductUpdate')
                    @can('sellers.products')
                    <a class="d-flex justify-content-between" href="{{ route('admin.notifications') }}">
                        <div class="media d-flex align-items-start">
                            <div class="media-left"><i class="fa-solid fa-cart-shopping"></i></div>
                            <div class="media-body">
                                <h6 class="info media-heading">محصول ویرایش شد</h6><small
                                    class="notification-text">{{ $notification->data['message'] }}</small>
                            </div><small>
                                <time class="media-meta">{{ jdate($notification->created_at)->ago() }}</time></small>
                        </div>
                    </a>
                    @endcan
                {{-- فاز ۸۱ — هشدار کاهش قیمت --}}
                @elseif($notification->type == 'PriceAlertsSent')
                    @can('products.price-alert')
                    <a class="d-flex justify-content-between" href="{{ route('admin.notifications') }}">
                        <div class="media d-flex align-items-start">
                            <div class="media-left"><i class="fa-solid fa-bell"></i></div>
                            <div class="media-body">
                                <h6 class="danger media-heading">هشدار کاهش قیمت ارسال شد</h6><small
                                    class="notification-text">{{ $notification->data['message'] }}</small>
                            </div><small>
                                <time class="media-meta">{{ jdate($notification->created_at)->ago() }}</time></small>
                        </div>
                    </a>
                    @endcan
                @endif
            @endforeach
        </li>
        <li class="dropdown-menu-footer">
            <a class="dropdown-item p-1 text-center" href="{{ route('admin.notifications') }}">نمایش همه اعلان ها</a>
        </li>
    </ul>
</li>
