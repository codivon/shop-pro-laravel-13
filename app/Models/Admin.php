<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\HasApiTokens;
use NotificationChannels\WebPush\HasPushSubscriptions;

class Admin extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use Notifiable;
    use HasPushSubscriptions;
    protected $guard='adminPanel';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'username',
        'email',
        'mobile',
        'two_factor_disabled',
        'level',
        'password',
        'instagram',
        'whatsapp',
        'eitaa',
        'telegram',
        'twitter',
        'facebook',
        'rubika',
        'bale',
    ];

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
    public function getImageUrlAttribute()
    {
        return $this->imageUrl();
    }

    public function imageUrl()
    {
        return $this->image ? asset($this->image) : asset('/back/app-assets/images/portrait/small/default.png');
    }

    public function getFullnameAttribute()
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    public function getProfilePhotoUrlAttribute()
    {
        return $this->image ? asset($this->image) : asset('back/app-assets/images/portrait/small/default.png');
    }


    public function orderItems()
    {
        return $this->hasManyThrough(OrderItem::class, Order::class);
    }

    public function address()
    {
        return $this->hasOne(Address::class);
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function views()
    {
        return $this->hasMany(Viewer::class);
    }

    public function favorites()
    {
        return $this->hasMany(Favorite::class);
    }

    public function favoriteProducts()
    {
        return $this->hasManyThrough(Product::class, Favorite::class);
    }

    public function wallet()
    {
        return $this->hasOne(Wallet::class);
    }

    //------------- end relations

    /**
     * فاز ۱۶ — رفع خطای «Call to undefined method Admin::hasBoughtProduct()»:
     * قالب فروشگاهی روی auth()->user()->hasBoughtProduct() صدا می‌زند؛ اگر سشنِ
     * مدیر روی آن مسیر resolve شود، مدون باید متد را داشته باشد. مدیران سفارش
     * واقعی ندارند؛ بنابراین رابطه orders به‌صورت «همیشه خالی» تعریف می‌شود تا
     * hasBought/hasBoughtProduct بدون خطا false برگردانند.
     */
    public function orders()
    {
        return $this->hasMany(Order::class, 'user_id')->whereRaw('1 = 0');
    }

    public function hasBought(Price $price)
    {
        $orders = $this->orders()->where('status', 'paid')->pluck('id');

        $bought = DB::table('order_items')->whereIn('order_id', $orders)->where('price_id', $price->id)->exists();

        return $bought;
    }

    /** امضای هم‌نام با User::hasBoughtProduct — برای مدیران همیشه false */
    public function hasBoughtProduct(Product $product)
    {
        $orders = $this->orders()->where('status', 'paid')->pluck('id');

        return DB::table('order_items')->whereIn('order_id', $orders)->where('product_id', $product->id)->exists();
    }

    public function hasRole($role)
    {
        if (is_string($role)) {
            return $this->roles->contains('name', $role);
        }

        return $role->intersect($this->roles)->count();
    }

    public function isAdmin()
    {
        return $this->level == 'admin' || $this->level == 'creator';
    }

    public function isCreator()
    {
        return $this->level == 'creator';
    }



    //------------- start scopes

    public function scopeFilter($query, $request)
    {
        if ($fullname = $request->input('query.fullname')) {
            $query->WhereRaw("concat(first_name, ' ', last_name) like '%{$fullname}%' ");
        }

        if ($email = $request->input('query.email')) {
            $query->where('email', 'like', '%' . $email . '%');
        }

        if ($username = $request->input('query.username')) {
            $query->where('username', 'like', '%' . $username . '%');
        }

        if ($level = $request->input('query.level')) {
            switch ($level) {
                case "admin": {
                    $query->where('level', 'admin');
                    break;
                }
                case "user": {
                    $query->where('level', 'user');
                    break;
                }
            }
        }

        if ($request->sort) {
            switch ($request->sort['field']) {
                case 'fullname': {
                    $query->orderBy('first_name', $request->sort['sort'])->orderBy('last_name', $request->sort['sort']);
                    break;
                }
                default: {
                    if ($this->getConnection()->getSchemaBuilder()->hasColumn($this->getTable(), $request->sort['field'])) {
                        $query->orderBy($request->sort['field'], $request->sort['sort']);
                    }
                }
            }
        }

        return $query;
    }

    public function scopeCustomPaginate($query, $request)
    {
        $paginate = $request->paginate;
        $paginate = ($paginate && is_numeric($paginate)) ? $paginate : 10;

        if ($request->paginate == 'all') {
            $paginate = $query->count();
        }

        return $query->paginate($paginate);
    }

    public function scopeExcludeCreator($query)
    {
        return $query->where('level', '!=', 'creator');
    }

    //------------- end scopes

    public function posts()
    {
        return $this->hasMany(Post::class, 'admin_id'); // اگر فیلد خارجی admin_id است
    }
    public function getPublishedPostsCountAttribute()
    {
        return $this->posts()->published()->count();
    }

    public function getSocialLink($platform)
    {
        $value = $this->$platform;
        if (empty($value)) return null;

        $links = [
            'instagram' => fn($v) => $v,
            'whatsapp' => fn($v) => 'https://wa.me/' . ltrim($v, '+'),
            'eitaa' => fn($v) => 'https://eitaa.com/' . ltrim($v, '@'),
            'telegram' => fn($v) => filter_var($v, FILTER_VALIDATE_URL) ? $v : 'https://t.me/' . ltrim($v, '@'),
            'twitter' => fn($v) => $v,
            'facebook' => fn($v) => $v,
            'rubika' => fn($v) => 'https://rubika.ir/' . ltrim($v, '@'),
            'bale' => fn($v) => 'https://bale.ai/' . ltrim($v, '@'),
        ];

        return isset($links[$platform]) ? $links[$platform]($value) : $value;
    }


}
