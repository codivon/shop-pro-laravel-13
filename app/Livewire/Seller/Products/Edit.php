<?php

namespace App\Livewire\Seller\Products;

use App\Models\AttributeGroup;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Currency;
use App\Models\Product;
use App\Models\ProductAbilityScore;
use App\Models\SizeType;
use App\Models\SpecType;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * فاز ۱۰۸ — «ویرایش محصول» فروشنده (پنل مدرن Livewire).
 *
 * پورت ProductSellerController@edit — فقط دادهٔ GET:
 *  • گارد مالکیت (پورت قدیم): محصول دیگری → 404
 *  • کالکشن‌های فرم مثل قدیم: categories / specTypes / sizetypes / attributeGroups /
 *    currencies / AbilityScores / brands
 *
 * خودِ فرم کلاسیک است (multipart + @method('PUT')) و مستقیم به seller.products.update
 * (UpdateProductRequest قدیمی) پست می‌شود — نام فیلدها عیناً فرم قدیمی:
 * prices[i][…] / download_files[i][…] / specification_group[i][…] /
 * sizes[group][size_id] / AbilityProductScore[i][…] / images (گالری با کاما).
 */
#[Layout('sellers.layouts.panel')]
#[Title('ویرایش محصول')]
class Edit extends Component
{
    public function mount(Product $product): void
    {
        // پورت گارد قدیم: فقط محصول‌های خودِ فروشنده
        if ($product->seller_id != seller_info()->seller_id) {
            abort(404);
        }
    }

    /* ---------------- نمایش ---------------- */

    public function render(Product $product)
    {
        $categories = Category::detectLang()->where('type', 'productcat')->orderBy('ordering')->get();
        $specTypes = SpecType::detectLang()->get();
        $sizetypes = SizeType::detectLang()->with('sizes')->get();
        $attributeGroups = AttributeGroup::detectLang()->orderBy('ordering')->with('get_attributes')->get();
        $currencies = Currency::latest()->get();
        $AbilityScores = ProductAbilityScore::where('product_id', $product->id)->orderBy('ordering')->get();
        $brands = Brand::all();

        // گالری موجود — مثل mockImages قدیم: نام = مسیر کامل تصویر (برای حفظ ترتیب/حذف در update)
        $gallery = $product->gallery()->orderBy('ordering')->get()->map(fn ($image) => [
            'name' => $image->image,
            'preview' => asset($image->image),
        ])->values()->all();

        // گروه‌های سایز موجود (pivot.group → [size_id => value]) — برای پیش‌پر کردن ردیف‌ها
        $existingSizeGroups = [];
        $maxSizeGroup = -1;

        if ($product->sizetype) {
            foreach ($product->sizes->groupBy('pivot.group') as $group => $sizes) {
                $values = [];

                foreach ($sizes as $size) {
                    $values[$size->id] = $product->sizes()
                        ->where('size_id', $size->id)
                        ->where('group', $group)
                        ->first()
                        ->pivot->value ?? '';
                }

                $existingSizeGroups[(int) $group] = $values;
                $maxSizeGroup = max($maxSizeGroup, (int) $group);
            }

            ksort($existingSizeGroups);
        }

        return view('sellers.panel.products.edit', [
            'product' => $product,
            'categories' => $categories,
            'specTypes' => $specTypes,
            'sizetypes' => $sizetypes,
            'attributeGroups' => $attributeGroups,
            'currencies' => $currencies,
            'AbilityScores' => $AbilityScores,
            'sellers' => null,
            'brands' => $brands,
            'gallery' => $gallery,
            'existingSizeGroups' => $existingSizeGroups,
            'sizeGroupStart' => $maxSizeGroup + 1,
            'updateUrl' => route('seller.products.update', $product->id),
            'imageStoreUrl' => route('seller.products.image-store'),
            'imageDeleteUrl' => route('seller.products.image-delete'),
            'indexUrl' => route('seller.products.index'),
            'productImageSize' => config('front.imageSizes.productImage', '—'),
            'productGalleryImageSize' => config('front.imageSizes.productGalleryImage', '—'),
        ]);
    }
}
