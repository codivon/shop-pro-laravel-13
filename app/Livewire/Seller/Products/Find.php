<?php

namespace App\Livewire\Seller\Products;

use App\Models\Category;
use App\Models\Product;
use App\Models\SellerVariant;
use Illuminate\Support\Facades\Route;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * فاز ۱۰۸ — «جستجو یا درج محصول» فروشنده (پنل مدرن Livewire).
 *
 * پورت ProductSellerController@find + apiIndexFind:
 *  • گاردهای مدارک/فعالیت (redirect داشبورد + toast-warning مثل قدیم)
 *  • کوئری: Product::published()->detectLang() — محصولات منتشرشدهٔ سایت
 *    + فیلترهای همان فرم قدیمی (title / category_id[] / stock / special) در URL
 *  • اعمال ردیف (پورت find.js + Resource قدیمی):
 *    تصویر/عنوان لینک به صفحهٔ محصول، گروه، تعداد تنوع، کمترین قیمت (ناموجود وقتی سبدناپذیر)
 *    و «شما هم بفروشید» → صفحهٔ تنوع‌ها (seller.products.variant با product_id) —
 *    همان مسیر «افزودن» پنل قدیمی؛ اگر فروشنده از قبل تنوع دارد → «فروشنده هستید».
 */
#[Layout('sellers.layouts.panel')]
#[Title('جستجو یا درج محصول')]
class Find extends Component
{
    use WithPagination;

    /* ---------------- فیلترها — همان کلیدهای فرم قدیمی، در URL ---------------- */

    #[Url(as: 'title', except: '')]
    public string $title = '';

    #[Url(as: 'category_id', except: [])]
    public array $category_id = [];

    #[Url(as: 'stock', except: 'all')]
    public string $stock = 'all';

    #[Url(as: 'special', except: 'all')]
    public string $special = 'all';

    public function mount(): void
    {
        // گارد مدارک/فعالیت — پورت find() قدیمی
        $this->guardSellerStatusOrRedirect();
    }

    /**
     * پورت گاردهای ابتدای متدهای کنترلر قدیمی:
     * مدارک تاییدنشده یا پنل متوقف → toast-warning + redirect داشبورد.
     */
    private function guardSellerStatusOrRedirect(): void
    {
        if (seller()->status_documents != 'Accept') {
            session()->put('toast-warning', 'مدارک شما در حال برسی می باشد.');

            $this->redirect(route('seller.dashboard'), navigate: true);

            return;
        }

        if (seller()->status_work != 'ACTIVE') {
            session()->put('toast-warning', 'اظلاعات شما در حال برسی می باشد.');

            $this->redirect(route('seller.dashboard'), navigate: true);
        }
    }

    public function updating(string $property): void
    {
        if (in_array($property, ['title', 'category_id', 'stock', 'special'], true)) {
            $this->resetPage();
        }
    }

    /** بازنشانی همهٔ فیلترها */
    public function resetFilters(): void
    {
        $this->title = '';
        $this->category_id = [];
        $this->stock = 'all';
        $this->special = 'all';
        $this->resetPage();
    }

    /* ---------------- کوئری لیست (پورت apiIndexFind) ---------------- */

    private function baseQuery()
    {
        // پورت apiIndexFind: محصولات منتشرشدهٔ سایت با دسته‌بندی و کمترین قیمت
        $query = Product::with('category', 'lowestPrice')
            ->published()
            ->detectLang();

        // پورت scopeDatatableFilter
        if ($this->title !== '') {
            $query->where('title', 'like', '%'.$this->title.'%');
        }

        if ($this->special === 'yes') {
            $query->where('special', true);
        } elseif ($this->special === 'no') {
            $query->where('special', false);
        }

        if ($this->stock === 'available') {
            $query->available();
        } elseif ($this->stock === 'unavailable') {
            $query->unavailable();
        }

        if ($this->category_id) {
            $allcats = $this->category_id;

            foreach ($this->category_id as $categoryId) {
                $category = Category::find($categoryId);

                if ($category) {
                    $allcats = array_merge($category->allChildCategories(), $allcats);
                }
            }

            $query->whereIn('category_id', array_unique($allcats));
        }

        return $query->latest();
    }

    /* ---------------- نمایش ---------------- */

    public function render()
    {
        $products = $this->baseQuery()->paginate(10);

        // پورت Resource قدیمی + find.js برای رندر جدول
        $rows = $products->getCollection()->map(function (Product $product) {
            $variant_count = SellerVariant::where('product_id', $product->id)->count();

            return (object) [
                'id' => $product->id,
                'image' => $product->image ? asset($product->image) : asset('/empty.svg'),
                'title' => $product->title,
                'category' => $product->category,
                'variant_count' => $variant_count,
                'price' => number_format(@$product->lowestPrice->price),
                'price_discount' => number_format(@$product->lowestPrice->discount_price),
                'addableToCart' => $product->addableToCart(),
                // فروشندهٔ فعلی از قبل تنوع دارد؟ (پورت SellerVariant check در Resource)
                'isSeller' => (bool) SellerVariant::where(['product_id' => $product->id, 'seller_id' => seller_info()->id])->first(),
                'frontUrl' => Route::has('front.products.show')
                    ? route('front.products.show', ['product' => $product])
                    : null,
                // «شما هم بفروشید» → همان لینک تنوع‌ها با کلید روت قدیمی (product_id)
                'variantUrl' => route('seller.products.variant', ['product_id' => $product->id]),
            ];
        });

        $categories = Category::detectLang()->where('type', 'productcat')->orderBy('ordering')->get();

        return view('sellers.panel.products.find', [
            'products' => $products,
            'rows' => $rows,
            'categories' => $categories,
            'createUrl' => route('seller.products.create'),
        ]);
    }
}
