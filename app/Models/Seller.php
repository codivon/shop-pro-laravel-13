<?php

namespace App\Models;

use App\Traits\SellerScopes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use NotificationChannels\WebPush\HasPushSubscriptions;

class Seller extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use HasPushSubscriptions;
    use Notifiable;
    use SellerScopes;

    protected $guard = 'seller';

    protected $guarded = ['id'];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function getRouteKeyName()
    {
        return 'slug';
    }

    public function sluggable(): array
    {
        return [
            'slug' => [
                'source' => 'slug',
            ],
        ];
    }

    public function seller_info()
    {
        return $this->hasOne(SellerInfo::class);
    }

    public function wallet()
    {
        return $this->hasOne(Wallet::class);
    }

    public function getWallet()
    {
        return $this->wallet()->firstOrCreate(
            [],
            [
                'balance' => 0,
                'is_active' => true,
            ]
        );
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'seller_variants');
    }

    public function views()
    {
        return $this->hasMany(Viewer::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function orderItem()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function orderItems()
    {
        return $this->hasManyThrough(OrderItem::class, Order::class);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function notifications()
    {
        return $this->belongsToMany(NotificationManage::class, 'notification_manage_users')
            ->withPivot('read', 'read_at')
            ->withTimestamps();
    }

    public function unreadNotifications()
    {
        return $this->notifications()->wherePivot('read', false);
    }

    public function readNotifications()
    {
        return $this->notifications()->wherePivot('read', true);
    }

    public function markAllNotificationsAsRead()
    {
        return $this->unreadNotifications()->update([
            'notification_manage_users.read' => true,
            'notification_manage_users.read_at' => now(),
        ]);
    }

    public function markNotificationAsRead($notificationId)
    {
        return $this->notifications()
            ->wherePivot('read', false)
            ->where('notification_manage_users.notification_id', $notificationId)
            ->update([
                'notification_manage_users.read' => true,
                'notification_manage_users.read_at' => now(),
            ]);
    }

    public function unreadNotificationsCount()
    {
        return $this->unreadNotifications()->count();
    }

    public function getFullnameAttribute()
    {
        // گارد null — فروشندهٔ بدون ردیف seller_info (مثلاً رکورد تست/ناقص) نباید ۵۰۰ بدهد (فاز ۱۰۳)
        $seller = SellerInfo::where('seller_id', $this->id)->first();

        return $seller ? trim(($seller->first_name ?? '').' '.($seller->last_name ?? '')) : '';
    }

    public function getBusinessNameAttribute()
    {
        $seller = SellerInfo::where('seller_id', $this->id)->first();

        return $seller->business_name ?? '';
    }

    public function getImageUrlAttribute()
    {
        return $this->imageUrl();
    }

    public function imageUrl()
    {
        $seller = SellerInfo::where('seller_id', $this->id)->first();

        return $seller->logo ? asset($seller->logo) : asset('/back/app-assets/images/portrait/small/default.png');
    }

    public function scopeIsFullActive($query)
    {
        return $query->where('status_work', 'ACTIVE')
            ->where('status_documents', 'Accept')
            ->where('status_register', 'complete')
            ->where('status', 'ACTIVE');
    }

    // متد نمونه (non-static) - تغییر نام بدهید
    public function checkIsFullActive()  // نام متفاوت
    {
        return $this->status_work == 'ACTIVE'
            && $this->status_documents == 'Accept'
            && $this->status_register == 'complete'
            && $this->status == 'ACTIVE';
    }

    public function hasRole($role)
    {
        if (is_string($role)) {
            return $this->roles->contains('name', $role);
        }

        return $role->intersect($this->roles)->count();
    }
}
