<?php

namespace App\Models;

use App\Repositories\CategoryRepository;
use App\Traits\Languageable;
use App\Traits\Taggable;
use Illuminate\Database\Eloquent\Model;
use Cviebrock\EloquentSluggable\Sluggable;

class Category extends Model
{
    use sluggable, Taggable, CategoryRepository, Languageable;

    protected $guarded = ['id'];

    public function sluggable() : array
    {
        return [
            'slug' => [
                'source' => 'slug',
            ],
        ];
    }

    public function getRouteKeyName()
    {
        return 'slug';
    }

    public function categories()
    {
        return $this->hasMany(Category::class)->orderBy('ordering');
    }

    public function post()
    {
        return $this->belongsToMany(Post::class);
    }

    public function childrenCategories()
    {
        return $this->hasMany(Category::class)->with('categories')->orderBy('ordering');
    }

    public function allChildCategories()
    {
        $categories = $this->categories()->pluck('id')->toArray();
        $categories[] = $this->id;

        foreach ($this->categories as $category) {
            $categories = array_merge($categories, $category->allChildCategories());
        }

        return $categories;
    }

    public function allProducts()
    {
        $categories = $this->allChildCategories();

        $products = Product::whereHas('categories', function ($query) use ($categories) {
            $query->whereIn('categories.id', $categories);
        });

        return $products;
    }

    public function allPublishedProducts()
    {
        $categories = $this->allChildCategories();

        $products = Product::published()->whereHas('categories', function ($query) use ($categories) {
            $query->whereIn('categories.id', $categories)->published();
        });

        return $products;
    }

    public function productsCount()
    {
        return $this->allProducts()->count();
    }

    public function menus()
    {
        return $this->belongsToMany(Menu::class, 'menus', 'menuable_id')->where('type', 'category');
    }

    public function getFullTitleAttribute()
    {
        if ($this->category_id) {
            $category_id = $this->category_id;
            $value = [$this->title];
            do {
                $mother = Category::find($category_id);
                $value[] = $mother->title;
                $category_id = $mother->category_id;
            } while ($category_id);

            $value = array_reverse($value);

            return implode(' - ', $value);
        }

        return $this->title;
    }

    public function getLinkAttribute()
    {
        if ($this->type == 'productcat') {
            return route('front.products.category-products', ['category' => $this]);
        }

        return route('front.posts.category', ['category' => $this]);
    }

    public function parent()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function parents()
    {
        $parents = collect([]);

        $parent = $this->parent;

        while (!is_null($parent)) {
            $parents->push($parent);
            $parent = $parent->parent;
        }

        return $parents->reverse();
    }

    public function children()
    {
        return $this->hasMany(Category::class, 'category_id');
    }

    public function products()
    {
        return $this->belongsToMany(Product::class);
    }

    public function filter()
    {
        return $this->belongsTo(Filter::class);
    }

    public function getFilter()
    {
        if ($this->filter_type == 'none') {
            return null;
        }

        $filter = $this->filter;

        if (!$filter) {
            $parent = $this->parent;

            while ($parent) {
                $filter = $this->parent->filter;

                if ($filter) {
                    break;
                }

                $parent = $parent->parent;
            }
        }

        return $filter;
    }

    public function scopeGetWithChilds($query)
    {
        $categories     = $query->get();
        $all_categories = Category::get();

        $this->setCategoriesRelations($categories, $all_categories);

        return $categories;
    }

    /**
     * FIX مقیاس: قبلاً داخل حلقه، $all_categories->where(...) روی کل کالکشن
     * اجرا می‌شد = O(n²)؛ برای هزاران دسته در هر بازسازی منو سنگین بود.
     * حالا یک‌بار groupBy می‌شود = O(n).
     */
    private function setCategoriesRelations($categories, $all_categories)
    {
        $byParent = $all_categories->groupBy('category_id');

        foreach ($categories as $category) {
            $children = $byParent->get($category->id);

            if ($children && $children->count()) {
                $category->setRelation('categories', $children);
                $this->setCategoriesRelations($children, $all_categories);
            }
        }
    }

    public function scopePublished($query)
    {
        return $query->where('published', true);
    }

    public function isPublished()
    {
        return $this->published;
    }
}
