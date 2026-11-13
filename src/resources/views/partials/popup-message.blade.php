{{-- ★ فاز ۲۲: Auth::check() روی گارد پیش‌فرض چک می‌کرد که روی مسیرهای پنل ( ResolveDefaultGuard )
   به adminPanel تغییر می‌کند → ادمین وارد شاخه می‌شد ولی auth('web')->user() null بود و صفحه ۵۰۰ می‌داد --}}
@if(auth('web')->check())
    {{-- فاز ۱۳۹ — خودمیزبانی pusher.min.js: js.pusher.com روی IPهای ایران کند است --}}
    <script src="{{ asset('vendor/pusher/pusher.min.js') }}"></script>
    <audio id="alert-sound" src="{{ asset('back/app-assets/sounds/notification.ogg') }}" preload="auto"></audio>

    {{-- فاز ۱۳۹ — خودمیزبانی jquery.qrcode: cdnjs کند روی IP ایران --}}
    <script src="{{ asset('vendor/jquery-qrcode/jquery.qrcode.min.js') }}"></script>

    {{-- استایل و منطق پاپ‌آپ در فایل‌های جدا (بدون inline) --}}
    <link rel="stylesheet" href="{{ theme_asset('css/notifications-popup.css') }}">

    @php
        $notifications = auth('web')->user()->unreadNotifications;
        $unreadCount = $notifications->count();
        $notificationsRoute = route('front.notifications.index');

        // فیلتر اعلان‌های پاپ‌آپی
        $popupNotifications = $notifications->filter(function($notification) {
            return isset($notification->popup) && $notification->popup == true;
        });
    @endphp

    {{-- جزیرهٔ داده اعلان‌ها — JS بیرونی (popup-notifications.js) این را می‌خواند --}}
    <script type="application/json" id="npop-data"
            data-notifications-route="{{ $notificationsRoute }}"
            data-mark-read-url="{{ route('front.notifications.mark-as-read') }}"
            data-pusher-key="{{ config('broadcasting.connections.pusher.key') }}"
            data-pusher-cluster="{{ config('broadcasting.connections.pusher.options.cluster') }}"
            data-user-id="{{ auth('web')->id() }}">@json($popupNotifications->values())</script>

    @if($popupNotifications->count() > 0)
        {{-- پاپ‌آپ اعلان‌های اجباری — در master سایت include کنید --}}
        <div class="npop-overlay" id="npop-overlay">
            <div class="npop" id="npop" role="dialog" aria-modal="true" aria-labelledby="npop-title">
                <span class="npop__banner"></span>
                <span class="npop__counter" id="npop-counter" style="display:none"></span>

                <div class="npop__body">
                    <div class="npop__icon"><i class="mdi mdi-bell-ring" id="npop-icon"></i></div>
                    <span class="npop__badge" id="npop-badge"></span>
                    <h3 class="npop__title" id="npop-title"></h3>
                    <p class="npop__msg" id="npop-msg"></p>
                </div>

                <div class="npop__foot">
                    <button type="button" class="npop__btn" id="npop-ack">
                        <i class="mdi mdi-check-circle-outline"></i>
                        متوجه شدم
                    </button>
                </div>
            </div>
        </div>
    @endif

    <script src="{{ theme_asset('js/popup-notifications.js') }}"></script>

@endif
