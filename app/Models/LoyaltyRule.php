<?php

namespace Modules\CustomerClub\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * قانون کسب امتیاز — هر رویداد یک ردیف.
 *
 * for() خودترمیم است (firstOrCreate): حتی بدون اجرای InstallPermissionsCommand
 * قانون‌ها از همان اولین دسترسی موجودند (تست بند ۲ بدون کامند seed را چک می‌کند).
 * is_active = کلید فعال/غیرفعال هر قانون (الزام شخصی‌سازی از تنظیمات).
 */
class LoyaltyRule extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public const EVENT_PURCHASE = 'purchase';
    public const EVENT_REGISTER = 'register';
    public const EVENT_REVIEW   = 'review';
    public const EVENT_BIRTHDAY = 'birthday';

    public const EVENTS = [
        self::EVENT_PURCHASE,
        self::EVENT_REGISTER,
        self::EVENT_REVIEW,
        self::EVENT_BIRTHDAY,
    ];

    /** مقادیر پیش‌فرض هر قانون (مرجع واحد seed و self-heal) */
    public const DEFAULTS = [
        self::EVENT_PURCHASE => [
            'title'       => 'امتیاز خرید',
            'is_active'   => true,
            'points'      => null,
            'amount_step' => 10000, // هر ۱۰٬۰۰۰ تومان = ۱ امتیاز
            'description' => 'به‌ازای هر مبلغ پله‌ای از مبلغ کل سفارشِ پرداخت‌شده امتیاز تعلق می‌گیرد.',
        ],
        self::EVENT_REGISTER => [
            'title'       => 'امتیاز ثبت‌نام',
            'is_active'   => true,
            'points'      => 50,
            'amount_step' => null,
            'description' => 'پاداش خوش‌آمدگویی بلافاصله پس از ثبت‌نام موفق.',
        ],
        self::EVENT_REVIEW => [
            'title'       => 'امتیاز ثبت نظر',
            'is_active'   => true,
            'points'      => 20,
            'amount_step' => null,
            'description' => 'پس از تأیید نظر توسط ادمین به نویسندهٔ نظر امتیاز تعلق می‌گیرد.',
        ],
        self::EVENT_BIRTHDAY => [
            'title'       => 'امتیاز تولد',
            'is_active'   => true,
            'points'      => 100,
            'amount_step' => null,
            'description' => 'سالانه در روز تولد مشتری (بر اساس تاریخ تولد پروفایل) هدیه می‌گیرد.',
        ],
    ];

    public static function for(string $event): self
    {
        return static::query()->firstOrCreate(
            ['event' => $event],
            static::DEFAULTS[$event] ?? ['title' => $event, 'is_active' => true]
        );
    }
}
