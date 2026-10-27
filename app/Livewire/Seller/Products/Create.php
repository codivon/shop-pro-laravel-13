<?php

namespace App\Livewire\Seller\Products;

use App\Models\AttributeGroup;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Currency;
use App\Models\Product;
use App\Models\SizeType;
use App\Models\SpecType;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * فاز ۱۰۸ — «درج محصول» فروشنده (پنل مدرن Livewire).
 *
 * پورت ProductSellerController@create — فقط دادهٔ GET:
 *  • گاردهای مدارک/فعالیت (redirect داشبورد + toast-warning مثل قدیم)
 *  • کالکشن‌های فرم: categories / specTypes / sizetypes / attributeGroups / currencies / brands
 *  • copy_product: پورت ?product=slug (کپی از محصول — «کپی کردن» لیست محصولات)
 *
 * خودِ فرم کلاسیک است (multipart) و مستقیم به seller.products.store (StoreProductRequest
 * قدیمی) پست می‌شود — نام فیلدها عیناً فرم قدیمی: prices[i][…] / download_files[i][…] /
 * specification_group[i][…] / sizes[group][size_id] / AbilityProductScore[i][…].
 * آپلود گالری با همان اندپوینت‌های قدیمی (seller.products.image-store / image-delete) است
 * و در submit فیلد images (نام‌ها با کاما — مثل formData.append قدیمی) ارسال می‌شود.
 */
#[Layout('sellers.layouts.panel')]
#[Title('درج محصول')]
class Create extends Component
{
    public function mount(): void
    {
        // گارد مدارک/فعالیت — پورت create() قدیمی
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

    /* ---------------- نمایش ---------------- */

    public function render()
    {
        $categories = Category::detectLang()->where('type', 'productcat')->orderBy('ordering')->get();
        $specTypes = SpecType::detectLang()->get();
        $sizetypes = SizeType::detectLang()->with('sizes')->get();
        $attributeGroups = AttributeGroup::detectLang()->orderBy('ordering')->with('get_attributes')->get();
        $currencies = Currency::latest()->get();
        $brands = Brand::all();

        // پورت ?product=slug — کپی اطلاعات یک محصول موجود در فرم درج
        $copy_product = request('product') ? Product::where('slug', request('product'))->first() : null;

        return view('sellers.panel.products.create', [
            'categories' => $categories,
            'specTypes' => $specTypes,
            'sizetypes' => $sizetypes,
            'attributeGroups' => $attributeGroups,
            'currencies' => $currencies,
            'sellers' => null,
            'brands' => $brands,
            'copy_product' => $copy_product,
            'storeUrl' => route('seller.products.store'),
            'imageStoreUrl' => route('seller.products.image-store'),
            'imageDeleteUrl' => route('seller.products.image-delete'),
            'indexUrl' => route('seller.products.index'),
            // بهترین اندازهٔ تصاویر (کانفیگ تم — مثل قدیم؛ بدون خطا وقتی تم لود نشده)
            'productImageSize' => config('front.imageSizes.productImage', '—'),
            'productGalleryImageSize' => config('front.imageSizes.productGalleryImage', '—'),
        ]);
    }
}
