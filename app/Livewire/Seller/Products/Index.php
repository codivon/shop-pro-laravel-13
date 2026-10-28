<?php

namespace App\Livewire\Seller\Products;

use App\Models\Category;
use App\Models\Product;
use App\Models\SellerVariant;
use App\Services\AdminPanel\ProductPersistence;
use App\Support\MultiVendorSettings;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Validator;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * فاز ۱۰۸ — «محصولات من» فروشنده (پنل مدرن Livewire).
 *
 * پورت ProductSellerController@index + apiIndex:
 *  • گاردهای مدارک/فعالیت (redirect داشبورد + toast-warning مثل قدیم)
 *  • مبنای کوئری: شناسهٔ محصول‌های SellerVariant فروشنده + detectLang
 *  • فیلترها با همان کلیدهای فرم قدیمی (title / category_id[] / stock / special) در URL
 *    — منطق scopeDatatableFilter (لایک عنوان، children دسته‌ها، موجودی، ویژه)
 *  • اعمال ردیف (پورت App\Http\Resources\Datatable\Product\Product):
 *    ویرایش (فروشندهٔ سازنده) / تنوع‌ها (seller.products.variant با product_id) /
 *    مشاهده در سایت / کپی (create?product=slug) / حذف
 *  • حذف: پورت ۱:۱ Back\ProductController@multipleDestroy + destroy
 *    (detach تگ/مشخصات، حذف تصاویر، SellerVariant، deleteProductSafely)
 *    با اسکوپ مالکیت auth('seller')->id()
 *  • خروجی اکسل و «افزودن از محصولات سایت»: لینک کلاسیک به همان روت‌های قدیمی
 */
#[Layout('sellers.layouts.panel')]
#[Title('مدیریت محصولات')]
class Index extends Component
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

    /* ---------------- انتخاب گروهی + حذف ---------------- */

    public array $selected = [];

    public bool $selectAll = false;

    public ?int $confirmingDelete = null;

    public bool $confirmingBulkDelete = false;

    public function mount(): void
    {
        // گارد مدارک/فعالیت — پورت index() قدیمی (redirect داشبورد + toast-warning)
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
            $this->selected = [];
            $this->selectAll = false;
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
        $this->selected = [];
        $this->selectAll = false;
    }

    public function updatedSelectAll($value): void
    {
        $this->selected = $value
            ? $this->baseQuery()->forPage($this->getPage(), 10)->pluck('id')->map(fn ($id) => (string) $id)->all()
            : [];
    }

    /* ---------------- کوئری لیست (پورت apiIndex) ---------------- */

    private function baseQuery()
    {
        // پورت apiIndex: محصولات دارای SellerVariant برای این فروشنده
        $seller_product_id = SellerVariant::where('seller_id', seller_info()->seller_id)->get()->pluck('product_id')->toArray();

        $query = Product::with('category', 'lowestPrice', 'brand')->detectLang()->whereIn('id', $seller_product_id);

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

    /* ---------------- حذف (پورت multipleDestroy قدیمی) ---------------- */

    public function confirmDelete(int $id): void
    {
        $this->confirmingDelete = $id;
    }

    /** حذف تکی — همان مسیر multipleDestroy قدیمی با یک شناسه */
    public function deleteOne(): void
    {
        $this->multipleDestroy([(string) $this->confirmingDelete]);

        $this->confirmingDelete = null;
    }

    /** حذف گروهی ردیف‌های انتخاب‌شده */
    public function deleteSelected(): void
    {
        $this->multipleDestroy($this->selected);

        $this->confirmingBulkDelete = false;
        $this->selected = [];
        $this->selectAll = false;
    }

    /**
     * پورت ۱:۱ Back\ProductController@multipleDestroy:
     * اعتبارسنجی ids (required|array + exists) و سپس destroy تکتک محصولات
     * (+ اسکوپ مالکیت: فقط محصول‌های ساختهٔ خودِ فروشنده حذف می‌شوند).
     */
    private function multipleDestroy(array $ids): void
    {
        Validator::make(
            ['ids' => $ids],
            ['ids' => 'required|array', 'ids.*' => 'exists:products,id']
        )->validate();

        $products = Product::whereIn('id', $ids)
            ->where('seller_id', auth('seller')->id())
            ->get();

        $persistence = app(ProductPersistence::class);

        foreach ($products as $product) {
            // همان destroy قدیمی: detach تگ/مشخصات، حذف تصاویر، SellerVariant، انبار و نرم/سخت حذف
            $persistence->destroy($product);
        }

        if (count($ids) === 1) {
            $this->dispatch('toast', message: 'محصول با موفقیت حذف شد.', type: 'success');
        } else {
            $this->dispatch('toast', message: 'محصولات انتخاب شده با موفقیت حذف شدند.', type: 'success');
        }
    }

    /* ---------------- نمایش ---------------- */

    public function render()
    {
        $products = $this->baseQuery()->paginate(10);

        // پورت Resource قدیمی (App\Http\Resources\Datatable\Product\Product) برای رندر جدول
        // فاز ۱۱۸ — seller_id ها یکدست روی sellers.id
        $rows = $products->getCollection()->map(function (Product $product) {
            $sellerVariant = SellerVariant::where(['product_id' => $product->id, 'seller_id' => seller_info()->seller_id])->first();

            $variant_count_seller = $product->prices()->withTrashed()->where('seller_id', seller_info()->seller_id)->count();

            return (object) [
                'id' => $product->id,
                'image' => $product->image ? asset($product->image) : asset('/empty.svg'),
                'title' => $product->title,
                'created_at' => jdate($product->created_at)->format('%d %B %Y'),
                'published' => $product->isPublished(),
                'category' => $product->category,
                'brand' => $product->brand,
                'stock_count' => $product->prices()->sum('stock'),
                'price' => number_format(@$product->lowestPrice->price),
                'price_discount' => number_format(@$product->lowestPrice->discount_price),
                'addableToCart' => $product->addableToCart(),
                'status' => $product->status,
                // تعداد تنوع‌های این فروشنده روی محصول (شمارهٔ ستون جدول — مثل Resource قدیمی)
                'variant_count_seller' => $variant_count_seller,
                // ویرایش فقط برای محصول‌های ساختهٔ خود فروشنده (پورت edit_seller)
                'editable' => $product->seller_id == seller_info()->seller_id,
                'editUrl' => $product->seller_id == seller_info()->seller_id
                    ? route('seller.products.edit', ['product' => $product])
                    : null,
                // تنوع‌ها — همان کلید روت قدیمی (product_id)
                'variantUrl' => route('seller.products.variant', ['product_id' => $product->id]),
                'frontUrl' => Route::has('front.products.show')
                    ? route('front.products.show', ['product' => $product])
                    : null,
                'copyUrl' => route('seller.products.create', ['product' => $product->slug]),
                'isSeller' => (bool) $sellerVariant,
            ];
        });

        $categories = Category::detectLang()->where('type', 'productcat')->orderBy('ordering')->get();

        return view('sellers.panel.products.index', [
            'products' => $products,
            'rows' => $rows,
            'categories' => $categories,
            'exportUrl' => MultiVendorSettings::productsExportEnabled()
                ? route('seller.products.export')
                : null,
            'createUrl' => route('seller.products.create'),
            'findUrl' => route('seller.products.find'),
        ]);
    }
}
