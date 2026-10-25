<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;

class Price extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($price) {
            $price->attribute_hash = $price->generateAttributeHash();
        });

        static::updating(function ($price) {
            $price->attribute_hash = $price->generateAttributeHash();
        });
    }

    public function generateAttributeHash()
    {
        $attributes = $this->get_attributes()->pluck('id')->sort()->values()->toArray();
        return md5(json_encode([
            'seller_id' => $this->seller_id,
            'attributes' => $attributes
        ]));
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
    public function seller()
    {
        return $this->belongsTo(Seller::class);
    }

    public function file()
    {
        return $this->morphOne(File::class, 'fileable');
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function get_attributes()
    {
        return $this->belongsToMany(Attribute::class, 'attribute_price')
            ->withPivot('seller_id', 'product_id')
            ->select('attributes.*'); // مهم: فقط فیلدهای جدول attributes
    }

    public function group()
    {
        return $this->belongsTo(AttributeGroup::class, 'attribute_group_id');
    }
    public function get_sellers()
    {
        return $this->belongsToMany(Seller::class,'attribute_price');
    }

    public function getAttributesName()
    {
        if ($this->product->isDownload()) {
            return $this->file->title;
        }

        $title = '';
        $attributes = $this->get_attributes;

        foreach ($attributes as $attribute) {
            $title .= ' ' . $attribute->group->name . ' : ' . $attribute->name . ($attributes->last() == $attribute ? '' : '،');
        }

        return $title;
    }

    public function getDiscountExpireAtAttribute($value)
    {
        return $value ? Carbon::parse($value) : null;
    }

    public function price()
    {
        return (float) $this->price;
    }

    public function tomanPrice()
    {
        if ($this->product->currency) {
            return $this->price * $this->product->currency->amount;
        }

        return $this->price;
    }

    public function changes()
    {
        return $this->hasMany(PriceChange::class, 'price_id');
    }

    public function createChange($new_price, $new_discount, $new_stock = null, $old_price = null, $old_discount = null, $old_stock = null)
    {
        if ($this->product->currency) {
            $new_price = $new_price * $this->product->currency->amount;
        }

        if ($new_stock === null) {
            $new_stock = $this->stock;
        }

        if ($old_price) {
            if ($this->product->currency) {
                $old_price = $old_price * $this->product->currency->amount;
            }
        } else {
            $old_price = $this->tomanPrice();
        }

        if ($old_discount === null) {
            $old_discount = $this->discount;
        }

        if ($old_stock === null) {
            $old_stock = $this->stock;
        }

        $create_change = false;

        if ($new_price != $old_price) {
            $create_change = true;
        }

        if ($new_discount != $old_discount) {
            $create_change = true;
        }

        if ($this->discount_price != $this->discountPrice()) {
            $create_change = true;
        }

        $last_change = $this->changes()->latest()->first();

        if (!$last_change || ($last_change->is_available && $new_stock <= 0) || (!$last_change->is_available && $new_stock > 0)) {
            $create_change = true;
        }

        if ($create_change) {
            $this->changes()->create([
                'product_id'     => $this->product_id,
                'price'          => $new_price,
                'discount'       => $new_discount,
                'is_available'   => $new_stock > 0
            ]);
        }
    }

    public function createFile($title, $file, $status)
    {
        $filename = date("Y-m-d") . '/' . uniqid() . '.' . $file->getClientOriginalExtension();
        $file->storeAs('product-files', $filename, 'downloads');

        $this->file()->create([
            'title'    => $title,
            'file'     => $filename,
            'disk'     => 'downloads',
            'size'     => $file->getSize(),
            'status'   => $status,
        ]);
    }

    public function updateFile($title, $file, $status)
    {
        if ($file) {
            $filename = date("Y-m-d") . '/' . uniqid() . '.' . $file->getClientOriginalExtension();
            $file->storeAs('product-files', $filename, 'downloads');
            $size = $file->getSize();
        } else {
            $filename = $this->file->file;
            $size     = $this->file->size;
        }

        $this->file()->update([
            'title'    => $title,
            'file'     => $filename,
            'disk'     => 'downloads',
            'size'     => $size,
            'status'   => $status,
        ]);
    }

    public function hasStock($quantity, $with_attributes = false)
    {
        if ($this->product->isDownload()) {
            return [
                'status'  => true,
                'message' => 'ok'
            ];
        }

        if ($this->cart_min !== null && $this->cart_min > $quantity && $this->stock > $quantity) {
            if ($with_attributes) {
                return [
                    'status'  => false,
                    'message' => 'حداقل تعداد برای محصول "' . $this->product->title . '"' . $this->getAttributesName() . ' "' . $this->cart_min . '" میباشد'
                ];
            }

            return [
                'status'  => false,
                'message' => 'لطفا تعداد بیشتر از یا مساوی ' . $this->cart_min . ' انتخاب کنید.'
            ];
        }

        if ($this->stock < $quantity || ($this->cart_max !== null && $this->cart_max < $quantity)) {
            if ($with_attributes) {
                return [
                    'status'  => false,
                    'message' => 'موجودی محصول "' . $this->product->title . ' ' . $this->getAttributesName() . '" کافی نیست.'
                ];
            }

            return [
                'status'  => false,
                'message' => 'موجودی محصول کافی نمی باشد'
            ];
        }

        if (!$this->published){
            return [
                'status'  => false,
                'message' => 'این محصول آماده فروش نمی باشد'
            ];
        }

        return [
            'status'  => true,
            'message' => 'ok'
        ];
    }

    public function isDownloadable()
    {
        if ($this->file && $this->file->status == 'inactive') {
            return false;
        }

        if ($this->price == 0) {
            return true;
        }

        if (auth()->check()) {

            return auth()->user()->hasBought($this) || auth()->user()->can('products.update');
        }

        return false;
    }

    public function downloadLink()
    {
        $time = Carbon::now()->addHours(5)->getTimestamp();

        $hash = Hash::make(config('app.key') . $time . $this->id);

        $link = Route::has('front.products.download') ? route('front.products.download', ['price' => $this]) : '#';

        $link .= "?mac=$hash&time=$time";

        return $link;
    }

    public function hasDiscount()
    {
        return $this->discount && (is_null($this->discount_expire_at) || $this->discount_expire_at->gt(now()));
    }

    public function discountPrice()
    {
        return $this->toRoundPrice($this->discount_price);
    }

    public function regularPrice()
    {
        return $this->toRoundPrice($this->regular_price);
    }

    public function discount()
    {
        return $this->hasDiscount() ? $this->discount : 0;
    }
    public function salePrice()
    {
        return $this->hasDiscount() ? $this->discountPrice() : $this->regularPrice();
    }

    public function toRoundPrice($price)
    {
        // فاز ۸۹ — گارد قیمت یتیم: اگر ردیف قیمت محصولِ والد نداشته باشد
        // (محصول حذف‌شده یا ردیف ناقص)، دسترسی به property روی null کل فرانت
        // (مثل product-block-2) را با «Attempt to read property on null» می‌شکند.
        // در این حالت گردکردن اعمال نمی‌شود و قیمت خام برمی‌گردد.
        if (!$this->product) {
            return (float) $price;
        }

        $rounding_amount = $this->product->rounding_amount;

        if ($rounding_amount == 'default') {
            $rounding_amount = option('default_rounding_amount', 'no');
        }

        $rounding_type = $this->product->rounding_type;

        if ($rounding_type == 'default') {
            $rounding_type = option('default_rounding_type', 'close');
        }

        switch ($rounding_amount) {
            case "100":
            case "1000":
            case "10000":
            case "100000": {
                if ($rounding_type == 'up') {
                    $price = ceil($price / $rounding_amount) * $rounding_amount;
                } else if ($rounding_type == 'down') {
                    $price = floor($price / $rounding_amount) * $rounding_amount;
                } else {
                    $price = round($price / $rounding_amount) * $rounding_amount;
                }
                break;
            }
        }

        return (float) $price;
    }


    public function pendingToSend()
    {
        return $this
            ->orderItems()
            ->whereHas('order', function ($q) {
                $q->notCompleted();
            })
            ->whereHas('product', function ($q3) {
                $q3->physical();
            })
            ->sum('order_items.quantity');
    }

    public function scopeInStock($query)
    {
        return $query->where('stock', '>', 0);
    }

    // دریافت ویژگی‌ها به صورت آرایه
    public function getAttributesArray()
    {
        $result = [];
        foreach ($this->attributes as $attribute) {
            $result[] = [
                'name' => $attribute->name,
                'value' => $attribute->value,
                'type' => $attribute->type ?? null,
                'color_code' => ($attribute->type ?? '') == 'color' ? $attribute->value : null
            ];
        }
        return $result;
    }

    // دریافت قیمت با تخفیف
    public function getDiscountPriceAttribute()
    {
        if ($this->discount && $this->discount > 0) {
            $discountAmount = ($this->price * $this->discount) / 100;
            return $this->price - $discountAmount;
        }
        return $this->price;
    }
    public function attributes()
    {
        return $this->belongsToMany(Attribute::class, 'attribute_price', 'price_id', 'attribute_id')
            ->withTimestamps();
    }



    //انبارداری
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class);
    }

// متدهای مدیریت موجودی
    public function getAvailableStockAttribute()
    {
        return $this->stock - $this->reserved_stock;
    }

    public function reserveStock($quantity)
    {
        if ($this->available_stock < $quantity) {
            throw new \Exception("موجودی کافی نیست");
        }

        $this->increment('reserved_stock', $quantity);

        $this->logMovement('reserve', $quantity, $this->stock, $this->stock);

        return true;
    }



    public function decrementStock($quantity, $orderId = null, $orderItemId = null)
    {
        $before = $this->stock;
        $this->decrement('stock', $quantity);
        $this->decrement('reserved_stock', $quantity);
        $this->increment('sold_count', $quantity);
        $this->update(['last_stock_update' => now()]);

        $this->logMovement('out', -$quantity, $before, $this->stock, $orderId, $orderItemId);
    }

    public function incrementStock($quantity, $orderId = null, $orderItemId = null)
    {
        $before = $this->stock;
        $this->increment('stock', $quantity);
        $this->update(['last_stock_update' => now()]);

        $this->logMovement('in', $quantity, $before, $this->stock, $orderId, $orderItemId);
    }

    private function logMovement($type, $quantity, $before, $after, $orderId = null, $orderItemId = null)
    {
        StockMovement::create([
            'product_id' => $this->product->id,
            'price_id' => $this->id,
            'warehouse_id' => $this->warehouse_id,
            'order_id' => $orderId,
            'order_item_id' => $orderItemId,
            'type' => $type,
            'quantity' => abs($quantity),
            'before_stock' => $before,
            'after_stock' => $after,
            'operator_type' => auth()->check() ? (auth()->user()->is_admin ? 'admin' : 'seller') : 'system',
            'operator_id' => auth()->id()
        ]);
    }

/*    public function unreserveStock($quantity)
    {
        $this->decrement('reserved_stock', $quantity);

        $this->logMovement('unreserve', -$quantity, $this->stock, $this->stock);
    }*/
    public function unreserveStock($quantity, $orderId = null, $orderItemId = null, $description = null)
    {
        return DB::transaction(function () use ($quantity, $orderId, $orderItemId, $description) {
            $beforeReserved = $this->reserved_stock;
            $afterReserved = max(0, $beforeReserved - $quantity);

            $this->decrement('reserved_stock', $quantity);

            return StockMovement::create([
                'product_id' => $this->product->id,
                'price_id' => $this->id,
                'warehouse_id' => $this->warehouse_id,
                'order_id' => $orderId,
                'order_item_id' => $orderItemId,
                'type' => 'unreserve',
                'quantity' => $quantity,
                'before_stock' => $this->stock,
                'after_stock' => $this->stock,
                'description' => $description ?? "آزادسازی رزرو {$quantity} عدد",
                'operator_type' => 'system',
                'operator_id' => null,
            ]);
        });
    }
}
