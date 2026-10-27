<?php

namespace App\Providers;

use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        Login::class => [
            'App\Listeners\UserLogin\GetCart',
        ],

        Registered::class => [
            'App\Listeners\User\Registered',
            'App\Listeners\User\AddGiftCredit',
            // ─── فاز ۹۲: امکان «معرفی افراد» به پکیج باشگاه مشتریان منتقل شد ───
            // Listener پکیج: Modules\CustomerClub\Listeners\CreateReferralRewards
            // (تنظیماتش: پنل ادمین → باشگاه مشتریان → تنظیمات).
            // 'App\Listeners\User\CreateReferralDiscounts',
        ],

        'App\Events\SendMessage' => [
            'App\Listeners\SendMessage',
        ],

        'App\Events\OrderCreated' => [
            'App\Listeners\OrderCreated\SaveAddress',
            'App\Listeners\OrderCreated\ChangePrices',
        ],

        'App\Events\OrderPaid' => [
            'App\Listeners\OrderPaid',
            // فاز ۱۰۳ — تسویهٔ خودکار فروشندگان (گیت‌دار از تنظیمات؛ ایدمپوتنت)
            'App\Listeners\SettleVendorsOnOrderPaid',
        ],

        'App\Events\SellerCreated' => [
            'App\Listeners\Seller\Registered',
        ],

        'App\Events\WalletAmountIncreased' => [
            'App\Listeners\Wallet\SendWalletIncreasedSms',
        ],

        'App\Events\WalletAmountDecreased' => [
            'App\Listeners\Wallet\SendWalletDecreasedSms',
        ],

        'App\Events\ProductPricesChanged' => [
            'App\Listeners\Product\CreatePricesChange',
        ],

        'App\Events\ContactCreated' => [],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
