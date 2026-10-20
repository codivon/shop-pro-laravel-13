@if(!auth('web')->user()->isAdmin())
    @php
        $notifications      = auth('web')->user()->unreadNotifications;
        $unreadCount        = $notifications->count();
        $notificationsRoute = route('front.notifications.index');
    @endphp

    <div class="dropdown dropdown-notification front-dropdown-notification nav-item">
        <a class="nav-link nav-link-label notification-trigger"
           href="{{ $notificationsRoute }}"
           data-toggle="dropdown"
           aria-expanded="false"
           aria-label="{{ trans('front::messages.header.new-notification') }}">
            <i class="mdi mdi-bell-outline"></i>
            <span class="badge-notif-count notifications-count"
                  data-count="{{ $unreadCount }}"
                  style="{{ $unreadCount ? '' : 'display:none' }}">
                {{ $unreadCount ?: '' }}
            </span>
        </a>

        <ul class="dropdown-menu dropdown-menu-media notif-dropdown">
            <li class="notif-dropdown-header">
                <div class="notif-header-inner">
                    <div class="notif-header-title">
                        <i class="mdi mdi-bell-ring"></i>
                        <span>{{ trans('front::messages.header.new-notification') }}</span>
                    </div>
                    <span class="notif-header-count header-count">{{ $unreadCount }}</span>
                </div>
            </li>

            <li class="scrollable-container media-list notif-list">
                @forelse($notifications->take(6) as $notification)
                    @if(isset($notification->type))
                        @if($notification->type === 'SendMessage')
                            <a class="notif-card" href="{{ $notificationsRoute }}">
                                <div class="notif-card-icon">
                                    <i class="mdi mdi-comment-text-outline"></i>
                                </div>
                                <div class="notif-card-body">
                                    <h6 class="notif-card-title">{{ $notification->data['title'] }}</h6>
                                    <p class="notif-card-text">{{ $notification->data['message'] }}</p>
                                    <span class="notif-card-time">
                                    <i class="mdi mdi-clock-outline"></i>
                                    {{ jdate($notification->created_at)->ago() }}
                                </span>
                                </div>
                                <span class="notif-dot"></span>
                            </a>
                        @endif

                    @else
                        <a class="notif-card" href="{{ $notificationsRoute }}">
                            <div class="notif-card-icon">
                                <i class="mdi mdi-comment-text-outline"></i>
                            </div>
                            <div class="notif-card-body">
                                <h6 class="notif-card-title">{{ $notification->title }}</h6>
                                <p class="notif-card-text">{{ $notification->message }}</p>
                                <span class="notif-card-time">
                                    <i class="mdi mdi-clock-outline"></i>
                                    {{ jdate($notification->created_at)->ago() }}
                                </span>
                            </div>
                            <span class="notif-dot"></span>
                        </a>
                    @endif

                @empty
                    <div class="notif-empty">
                        <div class="notif-empty-icon">
                            <i class="mdi mdi-bell-off-outline"></i>
                        </div>
                        <p class="notif-empty-text">{{ trans('front::messages.header.no-notification') ?? 'اعلانی وجود ندارد' }}</p>
                        <span class="notif-empty-sub">شما در حال حاضر هیچ پیام جدیدی ندارید</span>
                    </div>
                @endforelse
            </li>

            @if($unreadCount > 0)
                <li class="notif-dropdown-footer">
                    <a class="notif-footer-link" href="{{ $notificationsRoute }}">
                        <span>{{ trans('front::messages.header.show-all-notifications') }}</span>
                        <i class="mdi mdi-chevron-left"></i>
                    </a>
                </li>
            @endif
        </ul>
    </div>

@endif
