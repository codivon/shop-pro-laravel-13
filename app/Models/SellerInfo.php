<?php

namespace App\Models;

use App\Traits\SellerScopes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use NotificationChannels\WebPush\HasPushSubscriptions;

class SellerInfo extends Model
{
    use HasApiTokens;
    use HasFactory;
    use Notifiable;
    use HasPushSubscriptions;
    use SellerScopes;
    protected $table="sellers_info";

    /**
     * فاز ۳ — الگوی همسان با Seller/SellerVariant:
     * همه ستون‌ها جز id قابل mass-assignment باشند تا firstOrCreate/updateOrCreate
     * (مثل sellerInfo() در SellerShow پنل جدید و جریان ثبت‌نام فرانت) کرش نکند.
     */
    protected $guarded = ['id'];

    public function seller()
    {
        return $this->belongsTo(Seller::class);
    }
    public function getFullnameAttribute()
    {
        return $this->first_name . ' ' . $this->last_name;
    }
    public function cities()
    {
        return $this->belongsToMany(City::class);
    }

    public function province()
    {
        return $this->belongsTo(Province::class,'state_id');
    }

    public function city()
    {
        return $this->belongsTo(City::class);
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
                'balance'   => 0,
                'is_active' => true
            ]
        );
    }
}
