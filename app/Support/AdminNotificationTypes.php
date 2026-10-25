<?php

namespace App\Support;

class AdminNotificationTypes
{
    public static function meta(?string $type): array
    {
        $map = [
            'OrderPaid'              => ['title' => 'سفارش جدید ثبت شد',                'icon' => 'icon-shopping-cart',   'c1' => '#60A5FA', 'c2' => '#2563EB', 'can' => 'orders.view'],
            'SellerRegistered'       => ['title' => 'فروشنده جدید ثبت‌نام کرد',          'icon' => 'icon-user-plus',       'c1' => '#34D399', 'c2' => '#059669', 'can' => 'sellers.view'],
            'SellerEditProfile'      => ['title' => 'فروشنده اطلاعات خود را ویرایش کرد', 'icon' => 'icon-user-check',      'c1' => '#2DD4BF', 'c2' => '#0D9488', 'can' => 'sellers.update'],
            'UserRegistered'         => ['title' => 'کاربر جدید ثبت‌نام کرد',            'icon' => 'icon-user',            'c1' => '#38BDF8', 'c2' => '#0284C7', 'can' => 'users.view'],
            'ContactCreated'         => ['title' => 'پیام جدید از فرم تماس',            'icon' => 'icon-mail',            'c1' => '#818CF8', 'c2' => '#4F46E5', 'can' => 'contacts.index'],
            'TicketCreated'          => ['title' => 'تیکت جدید دریافت شد',              'icon' => 'icon-life-buoy',       'c1' => '#38BDF8', 'c2' => '#0369A1', 'can' => 'tickets.show'],
            'CommentPostCreated'     => ['title' => 'دیدگاه جدید روی مقاله',            'icon' => 'icon-message-square',  'c1' => '#A78BFA', 'c2' => '#7C3AED', 'can' => 'comments.index'],
            'CommentProductCreated'  => ['title' => 'دیدگاه جدید روی محصول',            'icon' => 'icon-message-circle',  'c1' => '#A78BFA', 'c2' => '#7C3AED', 'can' => 'comments.index'],
            'QuestionProductCreated' => ['title' => 'پرسش جدید روی محصول',              'icon' => 'icon-help-circle',     'c1' => '#C084FC', 'c2' => '#9333EA', 'can' => 'comments.index'],
            'UserRequestDeposit'     => ['title' => 'درخواست تسویه کاربر',              'icon' => 'icon-credit-card',     'c1' => '#FB923C', 'c2' => '#EA580C', 'can' => 'request_deposit'],
            'SellerRequestDeposit'   => ['title' => 'درخواست تسویه فروشنده',            'icon' => 'icon-credit-card',     'c1' => '#FB923C', 'c2' => '#EA580C', 'can' => 'request_deposit'],
            'SellerProductCreated'   => ['title' => 'محصول جدید ثبت شد',                'icon' => 'icon-package',         'c1' => '#FBBF24', 'c2' => '#D97706', 'can' => 'sellers.products'],
            'SellerProductUpdate'    => ['title' => 'محصولی ویرایش شد',                 'icon' => 'icon-refresh-cw',      'c1' => '#FBBF24', 'c2' => '#D97706', 'can' => 'sellers.products'],
            'PriceAlertsSent'        => ['title' => 'هشدار کاهش قیمت ارسال شد',         'icon' => 'icon-bell',            'c1' => '#F87171', 'c2' => '#DC2626', 'can' => 'products.price-alert'],
        ];

        return $map[$type] ?? [
            'title' => 'اعلان سیستم', 'icon' => 'icon-bell',
            'c1' => '#94A3B8', 'c2' => '#64748B', 'can' => null,
        ];
    }

    /**
     * فاز ۱۶ — لینک هر اعلان به «پنل جدید» (np.*) — جایگزین ورود به پنل قدیمی
     * از هدر. اگر معادل پنل جدید وجود نداشته باشد، لینک قدیمی برمی‌گردد.
     */
    public static function npLink($notification): ?string
    {
        $data = $notification->data ?? [];
        $type = $notification->type;

        try {
            switch ($type) {
                case 'OrderPaid':
                    if (! empty($data['order_id'])) {
                        return route('np.orders.show', ['order' => $data['order_id']]);
                    }
                    return route('np.orders.index');

                case 'UserRegistered':
                    if (! empty($data['user_id'])) {
                        return route('np.users.show', ['user' => $data['user_id']]);
                    }
                    return route('np.users.index');

                case 'SellerRegistered':
                case 'SellerEditProfile':
                    if (! empty($data['seller_id'])) {
                        $seller = \App\Models\Seller::find($data['seller_id']);
                        if ($seller) {
                            return route('np.sellers.show', ['seller' => $seller->slug]);
                        }
                    }
                    return route('np.sellers.index');

                case 'UserRequestDeposit':
                case 'SellerRequestDeposit':
                    return route('np.request-deposit.index');

                case 'TicketCreated':
                    if (! empty($data['ticket_id'])) {
                        return route('np.tickets.show', ['ticket' => $data['ticket_id']]);
                    }
                    return route('np.tickets.index');

                case 'CommentPostCreated':
                    return route('np.comments.posts');

                case 'CommentProductCreated':
                    return route('np.reviews.index');

                case 'QuestionProductCreated':
                    return route('np.comments.products');

                case 'SellerProductCreated':
                case 'SellerProductUpdate':
                    return route('np.sellers.products');

                case 'ContactCreated':
                    return route('np.contacts.index');

                case 'PriceAlertsSent':
                    // فاز ۸۱ — هشدار کاهش قیمت: لینک به صفحهٔ هشدارهای قیمت پنل
                    return route('np.price-alerts.index');
            }
        } catch (\Throwable) {
            // روت فعلی در دسترس نیست — به صفحه اعلان‌های پنل برمی‌گردیم
        }

        try {
            return route('np.notifications.panel');
        } catch (\Throwable) {
            return null;
        }
    }
}
