<?php

namespace App\Traits;

use App\Models\AttributeGroup;
use App\Models\Category;
use App\Models\SellerInfo;
use App\Models\Specification;
use App\Models\StaticFilter;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use function Assert\that;

trait SellerScopes
{
    public function scopePublished($query)
    {
        $query->where(function ($q) {
            $q->where('category_id', null)->orWhereHas('category', function ($q2) {
                $q2->published();
            });
        });

        $query->where('published', true)->where(function ($q) {
            $q->where('publish_date', null)->orWhere('publish_date', '<=', Carbon::now());
        });

        return $query;
    }

    public function scopeUnpublished($query)
    {
        $query->where('published', false)->orWhere(function ($q) {
            $q->where('publish_date', '>', Carbon::now());
        });

        return $query;
    }

    public function scopeAvailable($query)
    {
        $query->where(function ($query2) {
            $query2->whereHas('prices', function ($p) {
                $p->where('stock', '>', 0);
            })->orWhere('type', 'download');
        });

        return $query;
    }

    public function scopeUnavailable($query)
    {
        $query->where(function ($query2) {
            $query2->whereDoesntHave('prices', function ($p) {
                $p->where('stock', '>', 0);
            });
        });

        return $query;
    }

    public function scopeDiscount($query)
    {
        $query->where(function ($query2) {
            $query2->where('price_type', 'multiple-price')->whereHas('prices', function ($p) {
                $p->where('discount', '>', 0);
            });
        });

        return $query;
    }

    public function scopeSpecial($query)
    {
        return $query->where('special', true);
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

    public function scopeOrderByStock($query, $type = 'desc')
    {
        $query
            ->select('*')
            ->selectRaw(DB::raw('if ((select max(stock) from prices where prices.product_id = products.id and prices.deleted_at is null) > 0, 1, 0) as in_stock'))
            ->orderBy('in_stock', $type);

        return $query;
    }

    public function scopeFilter($query, $request)
    {
        if ($request->title) {
            $query->where('title', 'like', '%' . $request->title . '%');
        }

        if ($request->special) {
            $query->where('special', true);
        }

        if ($request->stock) {
            if ($request->stock == 'available') {

                $query->available();
            } else if ($request->stock == 'unavailable') {
                $query->unavailable();
            }
        }

        if ($request->published) {
            if ($request->published == 'yes') {

                $query->where('published', true);
            } else if ($request->published == 'no') {
                $query->where('published', false);
            }
        }

        $categories = $request->category_id;

        if ($categories) {

            $allcats = $categories;

            foreach ($categories as $category) {

                $category = Category::find($category);

                if ($category) {
                    $allcats = array_merge($category->allChildCategories(), $allcats);
                }
            }

            $query->whereIn('category_id', $allcats);
        }

        switch ($request->ordering) {
            case 'oldest': {
                    $query->oldest();
                    break;
                }
            default: {
                    $query->latest();
                }
        }

        return $query;
    }

    function validate_mobile($mobile)
    {
        return preg_match('/^[0-9]{11}+$/', $mobile);
    }
    public function scopeDatatableFilter($query, Request $request)
    {
        //$query=SellerInfo::paginate(20);

        if(is_numeric($request->input('query.name'))){
            $field = 'id';
        }  else {
            $field = 'business_name';
        }

        if ($title = $request->input('query.name')) {
            $query->Where($field, 'like', '%' . $title . '%');
        }


        if($this->validate_mobile($request->input('query.national_number'))){
            $field = 'mobile';
        } else {
            $field = 'national_identity_number';
        }
        if ($title = $request->input('query.national_number')) {
            $query->Where($field, 'like', '%' . $title . '%');
        }


        if ($status_documents = $request->input('query.status_documents')) {
            switch ($status_documents) {
                case "Accept": {
                        $query->Where('status_documents', 'Accept');
                        break;
                    }
                case "Waiting": {
                    $query->Where('status_documents', 'Waiting');
                        break;
                    }
                case "Reject": {
                    $query->Where('status_documents', 'Reject');
                        break;
                    }
            }
        }

        if ($stock = $request->input('query.stock')) {
            switch ($stock) {
                case "available": {
                        $query->available();
                        break;
                    }
                case "unavailable": {
                        $query->unavailable();
                        break;
                    }
            }
        }

        if ($published = $request->input('query.published')) {
            switch ($published) {
                case "yes": {
                        $query->published();
                        break;
                    }
                case "no": {
                        $query->unpublished();
                        break;
                    }
            }
        }

        if ($request->sort) {
            switch ($request->sort['field']) {
                case 'addableToCart': {
                    $query->orderByStock($request->sort['sort']);
                    break;
                }
                default: {
                    if (schema_has_column($this->getTable(), $request->sort['field'])) {
                        $query->orderby($request->sort['field'], $request->sort['sort']);
                    }
                }
            }
        }


        return $query;
    }

    public function scopeFrontFilter($query, $category)
    {
        $request = request();

        $attribute_groups = [];

        if ($request->filters) {
            foreach ($request->filters as $key => $values) {
                $filter = $category->getFilter()->related()->find($key);

                if ($filter && is_array($values)) {

                    $values = array_keys($values);

                    switch ($filter->filterable_type) {
                        case AttributeGroup::class: {
                                $vals = array_values($values);
                                $attribute_groups = array_merge($attribute_groups, $vals);

                                $query->whereHas('prices', function ($q1) use ($values, $request) {
                                    $q1->whereHas('get_attributes', function ($q2) use ($values) {
                                        $q2->whereIn('attribute_id', $values);
                                    });

                                    if ($request->in_stock) {
                                        $q1->where('stock', '>', 0);
                                    }
                                });
                                break;
                            }
                        case Specification::class: {
                                $query->whereHas('specifications', function ($q3) use ($values, $filter) {
                                    if ($filter->separator) {
                                        $q3->where('product_specification.specification_id', $filter->filterable_id)->where(function ($q4) use ($values, $filter) {
                                            $count = 0;
                                            foreach ($values as $item) {
                                                if ($count == 0) {
                                                    $q4->where('value', 'like', '%' . $item . '%');
                                                    $count++;
                                                } else {
                                                    $q4->orWhere('value', 'REGEXP', '%' . $item . '%');
                                                }
                                            }
                                        });
                                    } else {
                                        $q3->where('product_specification.specification_id', $filter->filterable_id)->whereIn('value', $values);
                                    }
                                });

                                break;
                            }
                        case StaticFilter::class: {

                                switch ($filter->filterable->type) {
                                    case 'brand': {
                                            $query->whereIn('brand_id', $values);
                                            break;
                                        }
                                    case 'child_category': {
                                            $categories = [];

                                            foreach ($values as $value) {
                                                $category = Category::find($value);

                                                if ($category) {
                                                    $categories = array_merge($categories, $category->allChildCategories());
                                                }
                                            }

                                            $query->whereIn('category_id', $categories);
                                            break;
                                        }
                                }

                                break;
                            }
                    }
                }
            }
        }

        if ($request->in_stock) {
            $query->available();
        }

        if ($request->price_filter && $request->min_price) {
            $query->whereHas('prices', function ($q) use ($request) {
                $q->where('stock', '>', 0)->where('price', '>=', $request->min_price);
            });
        }

        if ($request->price_filter && $request->max_price) {
            $query->whereHas('prices', function ($q) use ($request) {
                $q->where('stock', '>', 0)->where('price', '<=', $request->max_price);
            });
        }

        if ($request->s && is_string($request->s)) {
            $query->where(function ($query2) use ($request) {
                $query2->where('title', 'like', '%' . $request->s . '%')->orWhere('title_en', 'like', '%' . $request->s . '%');
            });
        }

        switch ($request->sort_type) {
            case "view": {
                    $query->orderBy('view', 'desc');
                    break;
                }
            case "sale": {
                    $query->orderBySale('desc');
                    break;
                }
            case "cheapest": {
                    $query->orderByPrice('asc');
                    break;
                }
            case "expensivest": {
                    $query->orderByPrice('desc');
                    break;
                }
            default: {
                    $query->latest();
                }
        }

        return $query;
    }

    public function scopeApiFilter($query)
    {
        $request = request();

        if ($category_id = $request->category_id) {
            $category = Category::findOrFail($category_id);

            if ($category) {
                $query->whereIn('category_id', $category->allChildCategories());
            }
        }

        if ($request->in_stock) {
            $query->available();
        }

        if ($request->in_stock) {
            $query->available();
        }

        if ($request->special == 1) {
            $query->special();
        }

        if ($request->discount == 1) {
            $query->special();
        }

        if ($request->max_price) {
            $query->whereHas('prices', function ($q) use ($request) {
                $q->where('stock', '>', 0)->where('price', '<=', $request->max_price);
            });
        }

        if ($request->search && is_string($request->search)) {
            $query->where(function ($query2) use ($request) {
                $query2->where('title', 'like', '%' . $request->search . '%')->orWhere('title_en', 'like', '%' . $request->search . '%');
            });
        }

        switch ($request->sort_field) {
            case "view": {
                    $query->orderBy('view', 'desc');
                    break;
                }
            case "sale": {
                    $query->orderBySale('desc');
                    break;
                }
            case "cheapest": {
                    $query->orderByPrice('asc');
                    break;
                }
            case "expensivest": {
                    $query->orderByPrice('desc');
                    break;
                }
            default: {
                    $query->latest();
                }
        }

        return $query;
    }

    public function scopePhysical($query)
    {
        return $query->where('type', 'physical');
    }

    public function scopeOrderBySale($query, $type = 'asc')
    {
        return $query->orderBy('sell', $type);
    }

    public function scopeOrderByPrice($query, $type = '')
    {
        return $query
            ->selectRaw(DB::raw('(select min(discount_price) from prices where prices.product_id = products.id and prices.deleted_at is null and prices.stock > 0) as min_price'))
            ->orderBy('min_price', $type);
    }
}
