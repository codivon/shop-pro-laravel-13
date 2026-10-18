<?php

namespace App\Models;

use App\Traits\HasProductId;
use App\Traits\Languageable;
use App\Traits\ProductScopes;
use App\Traits\Taggable;
use Carbon\Carbon;
use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

class Product extends Model
{
    use HasFactory,HasProductId, sluggable, Taggable, ProductScopes, Languageable;

    protected $guarded = ['id'];

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

    //------------- start relations

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class);
    }


    public function gallery()
    {
        return $this->morphMany(Gallery::class, 'galleryable');
    }

    public function comments()
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }
    public function viewer()
    {
        return $this->belongsTo(Viewer::class);
    }

    public function specificationGroups()
    {
        return $this->belongsToMany(SpecificationGroup::class, 'product_specification')->withPivot(['specification_group_id', 'group_ordering', 'specification_ordering', 'value', 'special'])->withTimestamps()->orderBy('group_ordering');
    }

    public function specifications()
    {
        return $this->belongsToMany(Specification::class, 'product_specification')
            ->withPivot(['specification_group_id', 'group_ordering', 'specification_ordering', 'value', 'special'])
            ->withTimestamps()
            ->orderBy('group_ordering')
            ->orderBy('specification_ordering');
    }
    public function specialSpecifications()
    {
        return $this->specifications()->where('special', true)->get();
    }

    public function specType()
    {
        return $this->belongsTo(SpecType::class);
    }

    public function prices()
    {
        return $this->hasMany(Price::class);
    }
    public function seller()
    {
        return $this->belongsTo(Seller::class)->with('seller_info');
    }

    public function prices_seller()
    {
        return $this->hasMany(Price::class)->where('seller_id', seller_info()->seller_id);
    }

    public function lowestPrice()
    {
        return $this->hasOne(Price::class)
            ->where('stock', '>', 0)
            ->orderByRaw('CASE WHEN (discount_expire_at is null or date(discount_expire_at) > "' . now()->format('Y-m-d H:i:s') . '") THEN discount_price ELSE regular_price END');
    }
/*    public function lowestPrice()
    {
        return $this->hasOne(Price::class)
            ->where('stock', '>', 0)
            ->orderBy('discount_price');
    }*/

/*    public function getPrices()
    {
        return $this->hasMany(Price::class)
            ->where('stock', '>', 0)->where('published', 1)
            ->orderBy('discount_price');
    }*/
    public function getPrices()
    {
        if (option('multi_vendor_system_status','false')=="false"){
            return $this->hasMany(Price::class)
                ->where('stock', '>', 0)->where('seller_id', null)
                ->orderByRaw('if((discount_expire_at is null or date(discount_expire_at) > "' . now()->format('Y-m-d H:i:s') . '"), discount_price, regular_price)');
        }

        return $this->hasMany(Price::class)
            ->with('seller')
            ->where('stock', '>', 0)
            ->where(function ($query) {
                $query->whereNull('seller_id')  // قیمت‌های سیستمی
                ->orWhereExists(function ($q) {
                    $q->select(DB::raw(1))
                        ->from('sellers')
                        ->whereColumn('sellers.id', 'prices.seller_id')
                        ->whereIn('sellers.id', Seller::isFullActive()->pluck('id'));
                });
            })
            ->orderByRaw('if((discount_expire_at is null or date(discount_expire_at) > "' . now()->format('Y-m-d H:i:s') . '"), discount_price, regular_price)');
    }


    public function carts()
    {
        return $this->belongsToMany(Cart::class);
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function priceChanges()
    {
        return $this->hasMany(PriceChange::class);
    }

    public function files()
    {
        return $this->hasManyThrough(File::class, Price::class, 'product_id', 'fileable_id')
            ->where(
                'fileable_type',
                Price::class
            );
    }

    public function orders()
    {
        return $this->belongsToMany(Order::class, 'order_items', 'product_id', 'order_id');
    }

    public function orderItems()
    {
        return $this->hasMany(\App\Models\OrderItem::class, 'product_id');
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }

    public function relatedProducts()
    {
        if ($this->category) {
            return $this->category->allPublishedProducts()->where('id', '!=', $this->id);
        }

        return Product::query()->where('id', '!=', $this->id);
    }

    public function labels()
    {
        return $this->morphToMany(Label::class, 'labelable')->withTimestamps();
    }

    //------------- end relations

    public function getDiscountPriceAttribute()
    {
        if ($this->discount) {
            return $this->price - ($this->price * ($this->discount / 100));
        }

        return $this->price;
    }

    public function getSpecialEndDateAttribute($value)
    {
        return $value ? Carbon::parse($value) : null;
    }

    public function link()
    {
        return Route::has('front.products.show') ? route('front.products.show', ['product' => $this]) : '#';
    }

    public function isPhysical()
    {
        return $this->type == 'physical';
    }

    public function isDownload()
    {
        return $this->type == 'download';
    }

    public function isSpecial()
    {
        return $this->special && ($this->special_end_date == null || $this->special_end_date->gt(now()));
    }

    public function addableToCart()
    {
        if ($this->type != 'physical') {
            return true;
        }

        if ($this->price_type == "multiple-price" && !$this->prices()->where('stock', '>', 0)->where('published', 1)->first()) {
            return false;
        }

        return true;
    }

    public function isPublished()
    {
        if ($this->category && !$this->category->isPublished()) {
            return false;
        }
        if ($this->status!="Accept"){
            return false;
        }

        return ($this->published && (!$this->publish_date || $this->publish_date <= Carbon::now()));
    }

    public function isShowable()
    {
        if ($this->isPublished()) {
            return true;
        }

        if (auth()->check() && auth('adminPanel')->user()->can('products')) {
            return true;
        }

        return false;
    }

    public function isComparable()
    {
        return $this->spec_type_id != null;
    }

    public function isUserFavorite()
    {
        return auth()->check() && auth()->user()->favorites()->where('product_id', $this->id)->first();
    }

    public function getLowestPrice($numeric = false)
    {
        $price = $this->lowestPrice;

        if ($this->isDownload()) {
            return $numeric ? null : 'محصول دانلودی';
        }

        if ($price && $price->stock) {
            return $numeric ? $price->discountPrice() : trans('messages.currency.prefix') . number_format($price->discountPrice()) . trans('messages.currency.suffix');
        }

        return $numeric ? null : 'ناموجود';
    }

    public function getLowestDiscount($numeric = false)
    {
        $price = $this->lowestPrice;

        if ($price && $price->hasDiscount()) {
            return $numeric ? $price->regularPrice() : trans('messages.currency.prefix') . number_format($price->regularPrice()) . trans('messages.currency.suffix');
        }

        return null;
    }
    public function getLowestPriceSeller($seller_id=null,$numeric = false)
    {
        $price = $this->prices()->where('seller_id',$seller_id)->orderby('price','asc')->first();
        if ($this->isDownload()) {
            return $numeric ? null : 'محصول دانلودی';
        }

        if ($price && $price->stock) {
            return $numeric ? $price->discountPrice() : trans('messages.currency.prefix') . number_format($price->discountPrice()) . trans('messages.currency.suffix');
        }

        return $numeric ? null : 'ناموجود';
    }

    public function getLowestSellerDiscount($seller_id=null,$numeric = false)
    {
        $price = $this->prices()->where('seller_id',$seller_id)->orderby('price','asc')->first();

        if ($price && $price->hasDiscount()) {
            return $numeric ? $price->regularPrice() : trans('messages.currency.prefix') . number_format($price->regularPrice()) . trans('messages.currency.suffix');
        }

        return null;
    }

    public function getDiscountAttribute()
    {
        $price = $this->lowestPrice;

        if ($price && $price->hasDiscount()) {
            return $price->discount();
        }

        return null;
    }

    public function getDiscount()
    {
        $price = $this->lowestPrice;

        if ($price && $price->hasDiscount()) {
            return $price->discount();
        }

        return null;
    }

    public function get_attributes($attributeGroup, $prev_attribute, $groups, $attributes_id)
    {
        $prices = $this->getPrices()->pluck('id');

        $group_attributes = $attributeGroup->get_attributes()->pluck('id');
        $attributes       = DB::table('attribute_price')->whereIn('price_id', $prices)->whereIn('attribute_id', $group_attributes);

        if ($groups) {
            $group_prices = $this->getPrices();

            foreach ($attributes_id as $att) {
                $group_prices->whereHas('get_attributes', function ($q) use ($att) {
                    $q->where('attribute_id', $att);
                });
            }

            $group_prices = $group_prices->pluck('id');

            $attributes->whereIn('price_id', $group_prices);
        }

        if ($prev_attribute) {
            $prices_have_this_attribute = $this->prices()->whereHas('get_attributes', function ($q) use ($prev_attribute) {
                $q->where('attribute_id', $prev_attribute->id);
            })->pluck('id');

            $this_price_attributes = DB::table('attribute_price')->whereIn('price_id', $prices_have_this_attribute)->pluck('attribute_id');

            $attributes->whereIn('attribute_id', $this_price_attributes);
        }

        $attributes = $attributes->pluck('attribute_id');

        if ($attributes->count()) {
            return Attribute::whereIn('id', $attributes)->get();
        }

        return null;
    }

    public function getPriceWithAttributes($attributes_id)
    {
        foreach ($this->getPrices as $price) {
            $price_attributes = $price->get_attributes()->pluck('attributes.id')->toArray();

            sort($price_attributes);
            sort($attributes_id);

            if ($price_attributes == $attributes_id) {
                return $price;
            }
        }
    }

    public function imageUrl($default = '/empty.svg')
    {
        if ($this->image) {
            return asset($this->image);
        }

        return $default == '/empty.svg' ? asset($default) : $default;
    }

    public function getUnit()
    {
        return $this->unit;
    }

    public static function clearCache()
    {
        $cache_keys = config('front.cache-forget.products');

        if ($cache_keys) {
            foreach ($cache_keys as $key) {
                Cache::forget($key);
            }
        }

        $cache_keys = self::cacheKeys();

        foreach ($cache_keys as $key) {
            Cache::forget($key);
        }
    }

    public function increaseViewCount()
    {
        $this->increment('view');
    }

    public function getLabels()
    {
        return implode(',', $this->labels()->pluck('title')->toArray());
    }

    public function isSinglePrice()
    {
        return $this->getPrices()->count() == 1;
    }

    public static function cacheKeys()
    {
        return [
            'admin.products_count'
        ];
    }

    public function refreshRating()
    {
        $rating = $this->reviews()->accepted()->sum('rating') / ($this->reviews()->accepted()->count() ?: 1);

        $this->update([
            'rating'        => $rating,
            'reviews_count' => $this->reviews()->accepted()->count()
        ]);
    }

    public function suggestionCount()
    {
        return $this->reviews()->accepted()->where('suggest', 'yes')->count();
    }

    public function suggestionPercent()
    {
        return ($this->suggestionCount() * 100) / $this->reviews()->accepted()->count();
    }

    public function sizeType()
    {
        return $this->belongsTo(SizeType::class);
    }

    public function sizes()
    {
        return $this->belongsToMany(Size::class)
            ->withPivot(['value', 'group', 'ordering'])
            ->withTimestamps()
            ->orderBy('group')
            ->orderBy('ordering');
    }

    public function shippingNatureText()
    {
        switch ($this->shipping_nature) {
            case "small": {
                return 'کوچک';
            }
            case "medium": {
                return 'متوسط';
            }
            case "large": {
                return 'بزرگ';
            }
        }
    }

    public function scopeFilter($query, $request, $products_id = null)
    {
        if ($products_id && is_array($products_id)) {
            $query->whereIn('id', $products_id);
        }

        if ($request->filters && is_array($request->filters)) {


            foreach ($request->filters as $key => $values) {
                if (!is_array($values) || empty($values)) {
                    continue;
                }

                // اگر key عددی نیست، رد کن
                if (!is_numeric($key)) {
                    continue;
                }

                // پیدا کردن filter از جدول filterables شرط title = seller
                $filterable = DB::table('filterables')
                    ->where('id', $key)
                    ->where('active', 1)
                    ->whereExists(function ($query) {
                        $query->select(DB::raw(1))
                            ->from('filters')
                            ->whereColumn('filters.id', 'filterables.filter_id')
                            ->where('filters.is_seller', true);
                    })
                    ->first();

                if (!$filterable) {
                    continue;
                }

                // استخراج values
                $values = array_keys($values);

                // بر اساس نوع filterable
                if (str_contains($filterable->filterable_type, 'AttributeGroup')) {
                    // فیلتر AttributeGroup
                    $query->whereHas('prices', function ($q1) use ($values, $request) {
                        $q1->whereHas('get_attributes', function ($q2) use ($values) {
                            $q2->whereIn('attribute_id', $values);
                        });

                        if ($request->in_stock == "on") {
                            $q1->where('stock', '>', 0);
                        }
                    });
                }
                elseif (str_contains($filterable->filterable_type, 'Specification')) {
                    // فیلتر Specification
                    $specification = Specification::find($filterable->filterable_id);
                    $query->whereHas('specifications', function ($q3) use ($values, $specification,$filterable) {
                        if ($specification && $specification->separator) {
                            $q3->where('product_specification.specification_id', $filterable->filterable_id)
                                ->where(function ($q4) use ($values) {
                                    $first = true;
                                    foreach ($values as $item) {
                                        if ($first) {
                                            $q4->where('value', 'like', '%' . $item . '%');
                                            $first = false;
                                        } else {
                                            $q4->orWhere('value', 'REGEXP', '%' . $item . '%');
                                        }
                                    }
                                });
                        } else {
                            $q3->where('product_specification.specification_id', $filterable->filterable_id)
                                ->whereIn('value', $values);
                        }
                    });
                }
                elseif (str_contains($filterable->filterable_type, 'StaticFilter')) {
                    // فیلتر StaticFilter
                    $staticFilter = StaticFilter::find($filterable->filterable_id);
                    if ($staticFilter) {
                        switch ($staticFilter->type) {
                            case 'brand':
                                $query->whereIn('brand_id', $values);
                                break;
                            case 'child_category':
                                $categories = [];
                                foreach ($values as $value) {
                                    $category = Category::find($value);
                                    if ($category && method_exists($category, 'allChildCategories')) {
                                        $categories = array_merge($categories, $category->allChildCategories());
                                    }
                                }
                                if (!empty($categories)) {
                                    $query->whereIn('category_id', $categories);
                                }
                                break;
                        }
                    }
                }
            }

            // فیلتر برند (brands)
            if (isset($request->filters['brands']) && is_array($request->filters['brands'])) {
                $brandIds = array_keys($request->filters['brands']);
                if (!empty($brandIds)) {
                    $query->whereIn('brand_id', $brandIds);
                }
            }
        }

        // فیلتر قیمت
        if ($request->price_filter == "on" && isset($request->min_price, $request->max_price)) {
            $priceProductIds = Price::whereBetween('price', [$request->min_price, $request->max_price])
                ->whereIn('product_id', $products_id ?? [])
                ->when($request->in_stock == "on", function ($q) {
                    $q->where('stock', '>', 0);
                })
                ->pluck('product_id')
                ->unique()
                ->toArray();

            if (!empty($priceProductIds)) {
                $query->whereIn('id', $priceProductIds);
            } else {
                $query->whereIn('id', []);
            }
        }

        // فیلتر جستجو
        if ($request->filled('s')) {
            $search = $request->s;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('title_en', 'like', "%{$search}%");
            });
        }

        // مرتب‌سازی
        switch ($request->sort_type) {
            case "view":
                $query->orderBy('view', 'asc');
                break;
            case "sale":
                if (method_exists($query->getModel(), 'scopeOrderBySale')) {
                    $query->orderBySale('desc');
                }
                break;
            case "cheapest":
                if (method_exists($query->getModel(), 'scopeOrderByPrice')) {
                    $query->orderByPrice('asc');
                }
                break;
            case "expensivest":
                if (method_exists($query->getModel(), 'scopeOrderByPrice')) {
                    $query->orderByPrice('desc');
                }
                break;
            default:
                $query->latest();
                break;
        }

        return $query;
    }

    public function hasAttributeStock(Attribute $attribute, $attributes_id = null)
    {

        $query = $this->getPrices()
            ->whereHas('get_attributes', function ($q) use ($attribute) {
                $q->where('attribute_id', $attribute->id);
            })->inStock();

        if ($attributes_id) {
            foreach ($attributes_id as $att) {
                $query->whereHas('get_attributes', function ($q) use ($att) {
                    $q->where('attribute_id', $att);
                });
            }
        }

        return $query->exists();
    }
}
