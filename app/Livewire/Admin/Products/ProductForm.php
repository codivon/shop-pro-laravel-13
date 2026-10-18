<?php

namespace App\Livewire\Admin\Products;

use App\Models\Attribute;
use App\Models\AttributeGroup;
use App\Models\Category;
use App\Models\Currency;
use App\Models\Fild;
use App\Models\Label;
use App\Models\Price;
use App\Models\Product;
use App\Models\Seller;
use App\Models\SizeType;
use App\Models\SpecType;
use App\Models\Tag;
use App\Models\Warehouse;
use Cviebrock\EloquentSluggable\Services\SlugService;
use App\Services\AdminPanel\ProductPersistence;
use App\Services\AdminPanel\ProductScraper;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use Morilog\Jalali\Jalalian;

#[Layout('admin-panel::layout')]
#[Title('ایجاد محصول')]
class ProductForm extends Component
{
    use WithFileUploads;

    /* ------------------------------- پایه ------------------------------- */

    public string $title = '';
    public string $title_en = '';
    public $category_id = null;
    public string $type = 'physical';
    public string $brand = '';
    public $weight = null;
    public string $unit = 'تعداد';
    public string $shipping_nature = '';
    public $seller_id = null;
    public array $extraCategories = [];
    public string $labels = '';
    public string $short_description = '';
    public string $description = '';
    public string $spec_type = '';
    public $size_type_id = null;

    /* ------------------------------- سئو ------------------------------- */

    public string $meta_title = '';
    public string $slug = '';
    public string $meta_description = '';
    public string $tags = '';

    /* --------------------------- قیمت و گرد کردن --------------------------- */

    public $currency_id = null;
    public string $rounding_amount = 'default';
    public string $rounding_type = 'default';

    /** @var array<int, array> ردیف‌های تنوع قیمت */
    public array $prices = [];

    /** @var array<int, array> فایل‌های دانلودی */
    public array $download_files = [];

    /* ------------------------------ تصاویر ------------------------------ */

    /** تصویر شاخص جدید */
    public $image = null;

    public string $image_alt = '';

    /** تصاویر جدید (TemporaryUploadedFile) */
    public array $gallery = [];

    /** تصاویر موجود به‌صورت مسیر مرتب‌شده */
    public array $existingGallery = [];

    /* ------------------------------ انتشار ------------------------------ */

    public string $publish_date = '';
    public bool $published = true;
    public string $status = 'Accept';
    public bool $special = false;
    public string $special_end_date = '';

    /* --------------------- مشخصات / سایز / امتیاز / فیلدها --------------------- */

    public array $specificationGroup = [];
    public array $sizes = [];
    public array $abilityScores = [];
    public array $filds = [];

    /* ------------------------------ وضعیت ------------------------------ */

    public ?Product $product = null;

    public bool $isEditing = false;

    /** فروشنده ممکن است در هر تنوع تعیین شود */
    public bool $multiVendor = false;

    /* ------------------------------ دریافت از دیجی‌کالا ------------------------------ */

    public string $scraperSiteCode = '';

    /** تصاویر دانلودشده از دیجی‌کالا (نام فایل‌ها در public/uploads/tmp) */
    public array $scraperImages = [];

    public ?string $scraperBaseImage = null;

    /* ============================ Mount ============================ */

    public function mount(?Product $product = null, ?string $copy = null)
    {
        $this->authorizeAccess($product && $product->exists ? 'products.update' : 'products.create');

        $this->multiVendor = option('multi_vendor_system_status', 'false') == 'true';

        // حالت ویرایش
        if ($product && $product->exists) {
            $this->product = $product;
            $this->isEditing = true;
            $this->loadProduct($product);

            return;
        }

        // حالت کپی
        if ($copy) {
            $source = Product::where('slug', $copy)->first();
            if ($source) {
                $this->loadProduct($source);
                $this->product = null;
                $this->isEditing = false;
                // عنوان و اسلاگ برای محصول جدید — تصاویر/فایل‌ها/شناسه‌ها کپی نمی‌شوند (مطابق پنل قدیمی)
                $this->title = $this->title ? $this->title . ' (کپی)' : $this->title;
                $this->slug = '';
                $this->existingGallery = [];

                $this->prices = array_map(function ($row) {
                    $row['id'] = null;

                    return $row;
                }, $this->prices);

                $this->download_files = array_map(function ($row) {
                    $row['price_id'] = null;
                    $row['file'] = null;
                    $row['file_name'] = '';

                    return $row;
                }, $this->download_files);
            }

            return;
        }

        // ایجاد جدید — مقادیر پیش‌فرض
        $this->currency_id = option('default_currency_id');
        $this->prices = [$this->blankPriceRow()];
        $this->download_files = [$this->blankDownloadRow()];
        $this->specificationGroup = [];
        $this->filds = $this->defaultFilds();
    }

    protected function authorizeAccess(string $permission): void
    {
        if (! auth('adminPanel')->user()?->can($permission)) {
            abort(403);
        }
    }

    /* ============================ بارگذاری محصول (ویرایش/کپی) ============================ */

    protected function loadProduct(Product $product): void
    {
        $this->title = (string) $product->title;
        $this->title_en = (string) $product->title_en;
        $this->category_id = $product->category_id;
        $this->type = (string) $product->type;
        $this->brand = (string) ($product->brand?->name ?? '');
        $this->weight = $product->weight;
        $this->unit = (string) ($product->unit ?? 'تعداد');
        $this->shipping_nature = (string) ($product->shipping_nature ?? '');
        $this->seller_id = $product->seller_id;
        $this->extraCategories = $product->categories()->pluck('categories.id')->reject(fn ($id) => $id == $product->category_id)->values()->toArray();
        $this->labels = (string) $product->getLabels();
        $this->short_description = (string) $product->short_description;
        $this->description = (string) $product->description;
        $this->spec_type = (string) ($product->specType?->name ?? '');
        $this->size_type_id = $product->size_type_id;

        $this->meta_title = (string) $product->meta_title;
        $this->slug = (string) $product->slug;
        $this->meta_description = (string) $product->meta_description;
        $this->tags = (string) $product->getTags;

        $this->currency_id = $product->currency_id;
        $this->rounding_amount = (string) ($product->rounding_amount ?? 'default');
        $this->rounding_type = (string) ($product->rounding_type ?? 'default');

        $this->publish_date = $product->publish_date ? Jalalian::fromDateTime($product->publish_date)->format('Y-m-d H:i:s') : '';
        $this->published = (bool) $product->published;
        $this->status = (string) ($product->status ?? 'Accept');
        $this->special = (bool) $product->special;
        $this->special_end_date = $product->special_end_date ? Jalalian::fromDateTime($product->special_end_date)->format('Y-m-d H:i:s') : '';

        $this->image_alt = (string) ($product->image_alt ?? '');

        // گالری موجود
        $this->existingGallery = $product->gallery()->orderBy('ordering')->pluck('image')->toArray();

        // فیلدهای اختصاصی
        $this->filds = $this->loadFilds($product);

        // مشخصات فنی
        $this->specificationGroup = $this->loadSpecifications($product);

        // سایز بندی
        $this->sizes = [];
        if ($product->size_type_id) {
            $this->sizes = [1 => []];
            foreach ($product->sizes as $size) {
                $group = $size->pivot->group ?: 1;
                $this->sizes[$group] ??= [];
                $this->sizes[$group][$size->id] = $size->pivot->value;
            }
        }

        // امتیاز ویژگی‌ها
        $this->abilityScores = \App\Models\ProductAbilityScore::where('product_id', $product->id)->orderBy('ordering')->get(['name', 'value'])->toArray();

        // قیمت‌ها / فایل‌های دانلودی
        if ($product->isDownload()) {
            $this->download_files = [];
            $files = $product->prices()->withTrashed()->orderBy('ordering')->with('file')->get();

            foreach ($files as $price) {
                $this->download_files[] = [
                    'price_id' => $price->id,
                    'title' => $price->file?->title ?? '',
                    'file' => null,
                    'file_name' => $price->file?->file ?? '',
                    'price' => (string) ($price->price ?? 0),
                    'discount' => (string) ($price->discount ?? 0),
                    'status' => $price->file?->status ?? 'active',
                ];
            }

            if (! $this->download_files) {
                $this->download_files = [$this->blankDownloadRow()];
            }
        } else {
            $this->prices = [];
            $prices = $product->prices()->with('get_attributes')->orderBy('id')->get();

            foreach ($prices as $price) {
                $this->prices[] = [
                    'id' => $price->id,
                    'warehouse' => $price->warehouse_id,
                    'attributes' => $price->get_attributes->pluck('id')->map(fn ($v) => (string) $v)->values()->toArray(),
                    'price' => (string) round((float) $price->price),
                    'discount' => (string) ($price->discount ?? 0),
                    'discount_expire_at' => $price->discount_expire_at ? Jalalian::fromDateTime($price->discount_expire_at)->format('Y-m-d H:i:s') : '',
                    'cart_max' => $price->cart_max,
                    'cart_min' => $price->cart_min,
                    'stock' => (string) $price->stock,
                    'published' => (bool) $price->published,
                    'seller_id' => $price->seller_id,
                ];
            }

            if (! $this->prices) {
                $this->prices = [$this->blankPriceRow()];
            }
        }
    }

    protected function loadFilds(Product $product): array
    {
        $result = [];

        $filds = Fild::where('belongs_to', 'products')->orderBy('created_at', 'desc')->get();
        $values = $product->exists ? \App\Models\FieldValue::where('related_id', $product->id)->get()->keyBy('field_id') : collect();

        foreach ($filds as $fild) {
            $result[$fild->id] = $values[$fild->id]->value ?? ($fild->type == 'checkbox' ? false : '');
        }

        return $result;
    }

    protected function loadSpecifications(Product $product): array
    {
        $rows = $product->specifications()
            ->withPivot('specification_group_id', 'group_ordering', 'specification_ordering', 'value', 'special')
            ->orderBy('product_specification.group_ordering')
            ->orderBy('product_specification.specification_ordering')
            ->get();

        $groups = [];

        foreach ($rows as $row) {
            $groupId = $row->pivot->specification_group_id;
            $groupName = $row->pivot->group_ordering !== null
                ? \App\Models\SpecificationGroup::find($groupId)?->name ?? ''
                : '';

            if (! isset($groups[$row->pivot->group_ordering])) {
                $groups[$row->pivot->group_ordering] = [
                    'name' => $groupName,
                    'specifications' => [],
                ];
            }

            $groups[$row->pivot->group_ordering]['specifications'][] = [
                'name' => $row->name,
                'value' => $row->pivot->value,
                'special' => (bool) $row->pivot->special,
            ];
        }

        ksort($groups);

        return array_values($groups);
    }

    protected function defaultFilds(): array
    {
        $result = [];

        foreach (Fild::where('belongs_to', 'products')->orderBy('created_at', 'desc')->get() as $fild) {
            $result[$fild->id] = $fild->type == 'checkbox' ? false : '';
        }

        return $result;
    }

    /* ============================ ردیف‌ها ============================ */

    public function blankPriceRow(): array
    {
        return [
            'id' => null,
            'warehouse' => null,
            'attributes' => [],
            'price' => '',
            'discount' => '0',
            'discount_expire_at' => '',
            'cart_max' => '',
            'cart_min' => '',
            'stock' => '0',
            'published' => true,
            'seller_id' => null,
        ];
    }

    public function addPriceRow(): void
    {
        $this->prices[] = $this->blankPriceRow();
    }

    public function removePriceRow(int $index): void
    {
        unset($this->prices[$index]);
        $this->prices = array_values($this->prices);

        if (! $this->prices && $this->type === 'physical') {
            $this->prices = [$this->blankPriceRow()];
        }
    }

    public function blankDownloadRow(): array
    {
        return [
            'price_id' => null,
            'title' => '',
            'file' => null,
            'file_name' => '',
            'price' => '',
            'discount' => '0',
            'status' => 'active',
        ];
    }

    public function addDownloadRow(): void
    {
        $this->download_files[] = $this->blankDownloadRow();
    }

    public function removeDownloadRow(int $index): void
    {
        unset($this->download_files[$index]);
        $this->download_files = array_values($this->download_files);

        if (! $this->download_files && $this->type === 'download') {
            $this->download_files = [$this->blankDownloadRow()];
        }
    }

    /* ============================ مشخصات فنی ============================ */

    public function addSpecGroup(): void
    {
        $this->specificationGroup[] = ['name' => '', 'specifications' => [['name' => '', 'value' => '', 'special' => false]]];
    }

    public function removeSpecGroup(int $gi): void
    {
        unset($this->specificationGroup[$gi]);
        $this->specificationGroup = array_values($this->specificationGroup);
    }

    public function addSpecRow(int $gi): void
    {
        $this->specificationGroup[$gi]['specifications'][] = ['name' => '', 'value' => '', 'special' => false];
    }

    public function removeSpecRow(int $gi, int $si): void
    {
        unset($this->specificationGroup[$gi]['specifications'][$si]);
        $this->specificationGroup[$gi]['specifications'] = array_values($this->specificationGroup[$gi]['specifications']);
    }

    /** اعمال قالب نوع مشخصات موجود */
    public function applySpecTemplate(): void
    {
        if (! $this->spec_type) {
            return;
        }

        $specType = SpecType::where('name', $this->spec_type)->with('specificationGroups.specifications')->first();

        if (! $specType) {
            $this->addError('spec_type', 'قالبی با این نام یافت نشد. به صورت خودکار هنگام ذخیره ساخته می‌شود.');

            return;
        }

        $this->specificationGroup = [];

        foreach ($specType->specificationGroups as $group) {
            $specs = [];
            foreach ($group->specifications as $spec) {
                $specs[] = ['name' => $spec->name, 'value' => '', 'special' => false];
            }
            $this->specificationGroup[] = ['name' => $group->name, 'specifications' => $specs];
        }
    }

    /* ============================ سایز بندی ============================ */

    public function updatedSizeTypeId(): void
    {
        if ($this->size_type_id) {
            $this->sizes = [1 => array_fill_keys(
                \App\Models\Size::where('size_type_id', $this->size_type_id)->orderBy('ordering')->pluck('id')->toArray(),
                ''
            )];
        } else {
            $this->sizes = [];
        }
    }

    public function addSizeGroup(): void
    {
        if (! $this->size_type_id) {
            return;
        }

        $this->sizes[] = array_fill_keys(
            \App\Models\Size::where('size_type_id', $this->size_type_id)->orderBy('ordering')->pluck('id')->toArray(),
            ''
        );
    }

    public function removeSizeGroup(int $gi): void
    {
        unset($this->sizes[$gi]);
        $this->sizes = array_values($this->sizes);
    }

    /* ============================ امتیاز ویژگی‌ها ============================ */

    public function addAbilityScore(): void
    {
        $this->abilityScores[] = ['name' => '', 'value' => ''];
    }

    public function removeAbilityScore(int $i): void
    {
        unset($this->abilityScores[$i]);
        $this->abilityScores = array_values($this->abilityScores);
    }

    /** انتخاب رنگ برای ردیف تنوع (در هر گروه رنگ فقط یک رنگ) */
    public function pickColor(int $i, int $groupId, int $attributeId): void
    {
        $group = AttributeGroup::with('get_attributes')->find($groupId);
        $groupAttrIds = $group?->get_attributes->pluck('id')->toArray() ?? [];

        // حذف رنگ‌های قبلی همین گروه
        $current = array_values(array_diff($this->prices[$i]['attributes'] ?? [], $groupAttrIds));

        // اگر همان رنگ دوباره کلیک شد، حذف می‌شود (toggle)
        if (! in_array($attributeId, $current)) {
            $current[] = $attributeId;
        }

        $this->prices[$i]['attributes'] = $current;
    }

    /* ============================ تصاویر ============================ */

    public function removeGalleryImage(int $i): void
    {
        unset($this->gallery[$i]);
        $this->gallery = array_values($this->gallery);
    }

    public function moveExistingImage(int $i, string $direction): void
    {
        $target = $direction === 'up' ? $i - 1 : $i + 1;

        if (! isset($this->existingGallery[$target])) {
            return;
        }

        [$this->existingGallery[$i], $this->existingGallery[$target]] = [$this->existingGallery[$target], $this->existingGallery[$i]];
        $this->existingGallery = array_values($this->existingGallery);
    }

    public function removeExistingImage(int $i): void
    {
        unset($this->existingGallery[$i]);
        $this->existingGallery = array_values($this->existingGallery);
    }

    /* ============================ اسلاگ ============================ */

    public function generateSlug(): void
    {
        $this->validateOnly('meta_title');

        $source = $this->meta_title ?: $this->title;

        if (! $source) {
            $this->addError('slug', 'برای ساخت خودکار اسلاگ، ابتدا عنوان را وارد کنید.');

            return;
        }

        $this->slug = SlugService::createSlug(Product::class, 'slug', $source);
    }

    /* ============================ اعتبارسنجی ============================ */

    protected function rules(): array
    {
        $uniqueSlug = $this->isEditing
            ? 'nullable|unique:products,slug,' . $this->product->id
            : 'nullable|unique:products,slug';

        $rules = [
            'title' => 'required|string|max:191',
            'title_en' => 'nullable|string|max:191',
            'category_id' => 'required|exists:categories,id',
            'image' => 'nullable|image',
            'image_alt' => 'nullable|string|max:191',
            'slug' => $uniqueSlug,
            'spec_type' => 'required_with:specificationGroup',
            'extraCategories' => 'nullable|array',
            'extraCategories.*' => 'exists:categories,id',
            'type' => 'required|in:physical,download',
            'rounding_amount' => 'required|in:default,no,100,1000,10000,100000',
            'rounding_type' => 'required|in:default,close,up,down',
            'currency_id' => 'nullable|exists:currencies,id',
            'gallery.*' => 'nullable|image|max:10240',
        ];

        if ($this->type === 'physical') {
            $rules = array_merge($rules, [
                'weight' => 'required|integer',
                'unit' => 'required|string',
                'prices' => 'required|array',
                'prices.*.warehouse' => ($this->isEditing ? 'nullable' : 'required') . '|exists:warehouses,id',
                'prices.*.price' => 'required|numeric|min:0',
                'prices.*.stock' => 'required|integer',
                'prices.*.attributes' => 'nullable|array',
                'prices.*.attributes.*' => 'nullable',
                'prices.*.cart_max' => 'nullable|integer',
                'prices.*.discount' => 'nullable|min:0|max:100',
                'prices.*.discount_expire_at' => 'nullable',
            ]);
        }

        if ($this->type === 'download') {
            $rules = array_merge($rules, [
                'download_files' => 'required|array',
                'download_files.*.title' => 'required|string',
                'download_files.*.file' => 'nullable|file',
                'download_files.*.price' => 'required|numeric|min:0',
                'download_files.*.discount' => 'nullable|min:0|max:100',
                'download_files.*.status' => 'required|in:active,inactive',
                'download_files.*.price_id' => 'nullable',
            ]);
        }

        return $rules;
    }

    protected function messages(): array
    {
        return [
            'title.required' => 'عنوان محصول الزامی است',
            'title.max' => 'عنوان محصول حداکثر ۱۹۱ کاراکتر می‌تواند باشد',
            'category_id.required' => 'دسته‌بندی محصول الزامی است',
            'slug.unique' => 'این Slug قبلاً ثبت شده است',
            'spec_type.required_with' => 'نوع مشخصات در صورت انتخاب گروه مشخصات الزامی است',
            'type.required' => 'نوع محصول الزامی است',
            'type.in' => 'نوع محصول باید فیزیکی یا دانلودی باشد',
            'rounding_amount.required' => 'مقدار گرد کردن قیمت الزامی است',
            'rounding_type.required' => 'نوع گرد کردن قیمت الزامی است',
            'weight.required' => 'وزن محصول الزامی است',
            'weight.integer' => 'وزن محصول باید عدد صحیح باشد',
            'unit.required' => 'واحد وزن الزامی است',
            'prices.required' => 'قیمت‌ها برای محصولات فیزیکی الزامی است',
            'prices.*.warehouse.required' => 'انتخاب انبار برای هر ردیف الزامی است',
            'prices.*.price.required' => 'قیمت برای هر ردیف الزامی است',
            'prices.*.price.min' => 'قیمت نمی‌تواند کمتر از ۰ باشد',
            'prices.*.stock.required' => 'موجودی برای هر ردیف الزامی است',
            'prices.*.discount.max' => 'درصد تخفیف نمی‌تواند بیشتر از ۱۰۰ باشد',
            'download_files.required' => 'فایل‌های دانلودی برای محصولات دانلودی الزامی است',
            'download_files.*.title.required' => 'عنوان فایل دانلودی الزامی است',
            'download_files.*.price.required' => 'قیمت فایل دانلودی الزامی است',
            'download_files.*.status.required' => 'وضعیت فایل دانلودی الزامی است',
            'download_files.*.file.file' => 'فایل دانلودی معتبر نیست',
            'gallery.*.image' => 'فایل آپلود شده باید تصویر باشد',
            'gallery.*.max' => 'حجم تصویر حداکثر ۱۰ مگابایت',
            'image.image' => 'فایل آپلود شده باید تصویر باشد',
        ];
    }

    /**
     * اعتبارسنجی فیلدهای اختصاصی اجباری + فایل جدید برای فایل‌های دانلودی جدید
     */
    protected function validateCustomFields(): void
    {
        $messages = [];

        $requiredFilds = Fild::where('belongs_to', 'products')->where('required', 1)->get();
        foreach ($requiredFilds as $requiredFild) {
            $value = $this->filds[$requiredFild->id] ?? null;
            if ($value === null || $value === '' || $value === false) {
                $messages["فیلد {$requiredFild->title} اجباری است."] = 1;
                $this->addError("filds.{$requiredFild->id}", "فیلد {$requiredFild->title} اجباری است.");
            }
        }

        foreach ($this->download_files as $i => $row) {
            if ($this->type !== 'download') {
                break;
            }

            if (empty($row['price_id']) && empty($row['file'])) {
                $this->addError("download_files.{$i}.file", 'فایل دانلودی این ردیف الزامی است');
            }

            if (! empty($row['price_id'])) {
                $exists = $this->product
                    ? $this->product->prices()->withTrashed()->where('id', $row['price_id'])->exists()
                    : false;

                if (! $exists) {
                    $this->addError("download_files.{$i}.price_id", 'فایل انتخاب شده معتبر نیست');
                }
            }
        }
    }

    /* ============================ دریافت از دیجی‌کالا ============================ */

    public function fetchFromSite(ProductScraper $scraper): void
    {
        $this->validateOnly('scraperSiteCode', ['scraperSiteCode' => 'required|numeric']);

        if ($this->getErrorBag()->has('scraperSiteCode')) {
            return;
        }

        $result = $scraper->fetchDigikala($this->scraperSiteCode, true);

        if (! empty($result['error'])) {
            $this->addError('scraperSiteCode', 'خطا در دریافت محصول: ' . $result['error']);

            return;
        }

        // اعمال داده‌ها روی فرم
        if (! empty($result['title'])) {
            $this->title = $result['title'];
        }

        if (! empty($result['metaTitle'])) {
            $this->meta_title = $result['metaTitle'];
            $this->slug = SlugService::createSlug(Product::class, 'slug', $this->meta_title);
        }

        if (! empty($result['metaDescription'])) {
            $this->meta_description = $result['metaDescription'];
        }

        if (! empty($result['categoryId'])) {
            $this->category_id = $result['categoryId'];
        }

        if (! empty($result['brandName'])) {
            $this->brand = $result['brandName'];
        }

        if (! empty($result['weight'])) {
            $this->weight = $result['weight'];
        }

        if (! empty($result['descriptionHtml'])) {
            $this->description = $result['descriptionHtml'];
            // ویرایشگر CKEditor با محتوای جدید مقداردهی شود
            $this->dispatch('ap-set-description');
        }

        if (! empty($result['specGroups'])) {
            $this->specificationGroup = $result['specGroups'];

            if (! $this->spec_type) {
                $this->spec_type = 'دریافتی از دیجی‌کالا';
            }
        }

        if (! empty($result['variantRows'])) {
            $this->prices = [];

            foreach ($result['variantRows'] as $row) {
                $this->prices[] = array_merge($this->blankPriceRow(), [
                    'price' => (string) $row['price'],
                    'attributes' => array_map('strval', $row['attributes']),
                ]);
            }
        }

        if (! empty($result['galleryFiles'])) {
            $this->scraperImages = $result['galleryFiles'];
            $this->scraperBaseImage = $result['baseImage'];
        }

        $this->dispatch('toast', message: 'محصول از دیجی‌کالا دریافت شد. لطفا اطلاعات را بازبینی و انبار را انتخاب کنید.', type: 'success');
    }

    /* ============================ ذخیره ============================ */

    public function save(ProductPersistence $persistence)
    {
        $this->authorizeAccess($this->isEditing ? 'products.update' : 'products.create');

        $this->validate();
        $this->validateCustomFields();

        if ($this->getErrorBag()->any()) {
            $this->dispatch('toast', message: 'لطفا خطاهای فرم را برطرف کنید.', type: 'error');

            return null;
        }

        if ($this->isEditing) {
            $this->updateProduct($persistence);
        } else {
            $this->storeProduct($persistence);
        }
    }

    protected function storeProduct(ProductPersistence $persistence): void
    {
        $product = Product::create([
            'title' => $this->title,
            'title_en' => $this->title_en,
            'category_id' => $this->category_id,
            'spec_type_id' => $this->resolveSpecTypeId(),
            'size_type_id' => $this->size_type_id,
            'weight' => $this->type === 'physical' ? $this->weight : null,
            'unit' => $this->type === 'physical' ? $this->unit : null,
            'price_type' => 'multiple-price',
            'type' => $this->type,
            'seller_id' => $this->seller_id,
            'shipping_nature' => $this->shipping_nature,
            'description' => $this->description,
            'short_description' => $this->short_description,
            'special' => $this->special,
            'slug' => $this->slug ?: $this->title,
            'meta_title' => $this->meta_title,
            'image_alt' => $this->image_alt,
            'meta_description' => $this->meta_description,
            'published' => $this->published,
            'status' => $this->status,
            'publish_date' => ProductPersistence::jdate($this->publish_date),
            'special_end_date' => ProductPersistence::jdate($this->special_end_date),
            'currency_id' => $this->currency_id,
            'rounding_amount' => $this->rounding_amount,
            'rounding_type' => $this->rounding_type,
            'lang' => app()->getLocale(),
        ]);

        // برند
        $persistence->updateProductBrand($product, $this->brand);

        // قیمت‌ها / فایل‌های دانلودی
        $persistence->updateProductPrices($product, $this->normalizePrices());
        $persistence->updateProductFiles($product, $this->normalizeDownloadFiles());

        // مشخصات / تصاویر / دسته‌بندی‌ها / برچسب‌ها / سایز / امتیاز
        $persistence->updateProductSpecifications($product, $this->specificationGroup);
        $persistence->updateProductImages($product, $this->image, $this->mergedGallery());
        $persistence->updateProductCategories($product, $this->extraCategories);
        $persistence->updateProductLabels($product, $this->labels);
        $persistence->updateProductSizes($product, $this->sizes);
        $persistence->updateProductAbilityScore($product, $this->abilityScores);

        // پاکسازی تصاویر موقت دیجی‌کالا (در صورت دریافت از سایت)
        $this->cleanupScraperFiles();

        // برچسب‌ها (tags) — در Livewire ناظر TaggableObserver از request نمی‌خواند، پس دستی همگام می‌کنیم
        $product->tags()->sync(addTags($this->tags));

        // فیلدهای اختصاصی
        if ($this->filds) {
            saveFieldValues($this->filds, 'products', $product->id);
        }

        // لاگ
        $adminName = $this->adminName();
        activity()
            ->performedOn($product)
            ->causedBy(Auth::guard('adminPanel')->user())
            ->event('created')
            ->withProperties([
                'action' => 'create_product',
                'product_id' => $product->id,
                'product_title' => $product->title,
                'attributes' => [
                    'عنوان' => $product->title,
                    'وضعیت انتشار' => $product->published ? 'فعال' : 'غیرفعال',
                    'نوع محصول' => $product->type == 'download' ? 'دانلودی' : 'فیزیکی',
                    'دسته‌بندی' => $product->category->title ?? 'بدون دسته‌بندی',
                    'برند' => $product->brand->name ?? '',
                ],
                'ip' => request()->ip(),
            ])
            ->log("مدیر {$adminName} محصول جدید «{$product->title}» را ایجاد کرد");

        session()->flash('np-toast', ['type' => 'success', 'message' => 'محصول با موفقیت ایجاد شد.']);

        $this->redirectIntended(route('np.products.index', absolute: false), navigate: true);
    }

    protected function updateProduct(ProductPersistence $persistence): void
    {
        $product = $this->product;

        // مقادیر قدیمی برای لاگ
        $oldValues = [
            'title' => $product->getOriginal('title'),
            'category_id' => $product->getOriginal('category_id'),
            'weight' => $product->getOriginal('weight'),
            'unit' => $product->getOriginal('unit'),
            'type' => $product->getOriginal('type'),
            'seller_id' => $product->getOriginal('seller_id'),
            'special' => $product->getOriginal('special') ? 'بله' : 'خیر',
            'slug' => $product->getOriginal('slug'),
            'meta_title' => $product->getOriginal('meta_title'),
            'published' => $product->getOriginal('published') ? 'فعال' : 'غیرفعال',
            'status' => $product->getOriginal('status'),
            'brand_id' => $product->brand_id,
        ];

        // تغییرات قیمت‌ها برای لاگ (قبل از ذخیره)
        $priceChanges = $this->getPriceChangesLog($product);

        $product->update([
            'title' => $this->title,
            'title_en' => $this->title_en,
            'category_id' => $this->category_id,
            'spec_type_id' => $this->resolveSpecTypeId(),
            'size_type_id' => $this->size_type_id,
            'weight' => $this->type === 'physical' ? $this->weight : null,
            'unit' => $this->type === 'physical' ? $this->unit : null,
            'price_type' => 'multiple-price',
            'type' => $this->type,
            'seller_id' => $this->seller_id,
            'shipping_nature' => $this->shipping_nature,
            'description' => $this->description,
            'short_description' => $this->short_description,
            'special' => $this->special,
            'slug' => $this->slug ?: $this->title,
            'meta_title' => $this->meta_title,
            'image_alt' => $this->image_alt,
            'meta_description' => $this->meta_description,
            'published' => $this->published,
            'status' => $this->status,
            'publish_date' => ProductPersistence::jdate($this->publish_date),
            'special_end_date' => ProductPersistence::jdate($this->special_end_date),
            'currency_id' => $this->currency_id,
            'rounding_amount' => $this->rounding_amount,
            'rounding_type' => $this->rounding_type,
        ]);

        $persistence->updateProductBrand($product, $this->brand);
        $persistence->updateProductPrices($product, $this->normalizePrices());
        $persistence->updateProductFiles($product, $this->normalizeDownloadFiles());
        $persistence->updateProductSpecifications($product, $this->specificationGroup);
        $persistence->updateProductImages($product, $this->image, $this->mergedGallery());
        $persistence->updateProductCategories($product, $this->extraCategories);
        $persistence->updateProductLabels($product, $this->labels);
        $persistence->updateProductSizes($product, $this->sizes);
        $persistence->updateProductAbilityScore($product, $this->abilityScores);

        // پاکسازی تصاویر موقت دیجی‌کالا (در صورت دریافت از سایت)
        $this->cleanupScraperFiles();

        $product->tags()->sync(addTags($this->tags));

        if ($this->filds) {
            saveFieldValues($this->filds, 'products', $product->id);
        }

        // لاگ واحد با همه تغییرات
        $newValues = [
            'title' => $product->title,
            'category_id' => $product->category_id,
            'weight' => $product->weight,
            'unit' => $product->unit,
            'type' => $product->type,
            'seller_id' => $product->seller_id,
            'special' => $product->special ? 'بله' : 'خیر',
            'slug' => $product->slug,
            'meta_title' => $product->meta_title,
            'published' => $product->published ? 'فعال' : 'غیرفعال',
            'status' => $product->status,
            'brand_id' => $product->brand_id,
        ];

        $finalOld = [];
        $finalNew = [];

        foreach ($oldValues as $key => $old) {
            if ($old != $newValues[$key]) {
                $finalOld[$key] = $old;
                $finalNew[$key] = $newValues[$key];
            }
        }

        foreach ($priceChanges as $change) {
            $finalOld[$change['attributes'] . ' - ' . $change['field']] = $change['old'];
            $finalNew[$change['attributes'] . ' - ' . $change['field']] = $change['new'];
        }

        $adminName = $this->adminName();
        $properties = [
            'action' => 'را ویرایش کرد',
            'product_id' => $product->id,
            'product_title' => $product->title,
            'ip' => request()->ip(),
        ];

        if (! empty($finalOld)) {
            $properties['old'] = $finalOld;
            $properties['attributes'] = $finalNew;
        }

        activity()
            ->performedOn($product)
            ->causedBy(Auth::guard('adminPanel')->user())
            ->withProperties($properties)
            ->log("مدیر {$adminName}, محصول «{$product->title}» را ویرایش کرد");

        session()->flash('np-toast', ['type' => 'success', 'message' => 'محصول با موفقیت ویرایش شد.']);

        $this->redirectIntended(route('np.products.index', absolute: false), navigate: true);
    }

    /* ============================ پردازش داده‌ها ============================ */

    /**
     * ساخت id نوع مشخصات (spec_type) — پورت helper spec_type()
     */
    protected function resolveSpecTypeId(): ?int
    {
        if (! $this->spec_type || ! $this->specificationGroup) {
            return null;
        }

        $specType = SpecType::firstOrCreate([
            'name' => $this->spec_type,
            'lang' => app()->getLocale(),
        ]);

        $group_ordering = 0;

        foreach ($this->specificationGroup as $group) {
            if (! isset($group['specifications'])) {
                continue;
            }

            $specGroup = \App\Models\SpecificationGroup::firstOrCreate([
                'name' => $group['name'],
                'lang' => app()->getLocale(),
            ]);

            $specification_ordering = 0;

            foreach ($group['specifications'] as $specification) {
                if (empty($specification['name'])) {
                    continue;
                }

                $spec = \App\Models\Specification::firstOrCreate([
                    'name' => $specification['name'],
                ]);

                if (! $specType->specifications()
                    ->where('specification_id', $spec->id)
                    ->where('specification_group_id', $specGroup->id)
                    ->first()) {
                    $specType->specifications()->attach([
                        $spec->id => [
                            'specification_group_id' => $specGroup->id,
                            'group_ordering' => $group_ordering,
                            'specification_ordering' => $specification_ordering++,
                        ],
                    ]);
                }
            }

            $group_ordering++;
        }

        return $specType->id;
    }

    /**
     * نرمال‌سازی ردیف‌های قیمت برای ProductPersistence
     */
    protected function normalizePrices(): array
    {
        $prices = [];

        foreach ($this->prices as $i => $row) {
            $prices[] = [
                'id' => $row['id'] ?? null,
                'warehouse' => $row['warehouse'] ?? null,
                'attributes' => array_values(array_map('intval', array_filter($row['attributes'] ?? []))),
                'price' => (float) $row['price'],
                'discount' => (int) ($row['discount'] ?? 0),
                'discount_expire_at' => $row['discount_expire_at'] ?? null,
                'cart_max' => $row['cart_max'] !== '' ? (int) $row['cart_max'] : null,
                'cart_min' => ($row['cart_min'] ?? '') !== '' ? (int) $row['cart_min'] : null,
                'stock' => (int) $row['stock'],
                'published' => (bool) ($row['published'] ?? true),
                'seller_id' => $row['seller_id'] ?? null,
            ];
        }

        return $prices;
    }

    /**
     * نرمال‌سازی فایل‌های دانلودی برای ProductPersistence
     */
    protected function normalizeDownloadFiles(): array
    {
        $files = [];

        foreach ($this->download_files as $row) {
            $files[] = [
                'price_id' => $row['price_id'] ?? null,
                'title' => $row['title'],
                'file' => $row['file'] instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile ? $row['file'] : null,
                'price' => (float) $row['price'],
                'discount' => (int) ($row['discount'] ?? 0),
                'status' => $row['status'],
            ];
        }

        return $files;
    }

    /**
     * گالری نهایی: مسیرهای موجود + تصاویر دیجی‌کالا (از public/uploads/tmp) + فایل‌های جدید (به ترتیب)
     */
    protected function mergedGallery(): array
    {
        $gallery = $this->existingGallery;

        // تصاویر دانلودشده از دیجی‌کالا → UploadedFile از public/uploads/tmp
        foreach ($this->scraperImages as $fileName) {
            $path = public_path('uploads/tmp/' . $fileName);

            if (file_exists($path)) {
                $gallery[] = new \Illuminate\Http\UploadedFile(
                    $path,
                    $fileName,
                    mime_content_type($path) ?: 'image/webp',
                    null,
                    true
                );
            }
        }

        return array_merge($gallery, $this->gallery);
    }

    /**
     * پاکسازی فایل‌های موقت دیجی‌کالا بعد از ذخیره
     */
    protected function cleanupScraperFiles(): void
    {
        foreach ($this->scraperImages as $fileName) {
            $path = public_path('uploads/tmp/' . $fileName);
            if (file_exists($path)) {
                @unlink($path);
            }
        }

        if ($this->scraperBaseImage) {
            $path = public_path('uploads/tmp/' . $this->scraperBaseImage);
            if (file_exists($path)) {
                @unlink($path);
            }
        }

        $this->scraperImages = [];
        $this->scraperBaseImage = null;
    }

    /**
     * ساخت لاگ تغییرات قیمت‌ها (قبل از ذخیره) — مطابق getProductPriceChanges قدیمی
     */
    protected function getPriceChangesLog(Product $product): array
    {
        $log = [];

        foreach ($this->normalizePrices() as $row) {
            $attributes = $product->prices()
                ->where('id', $row['id'])
                ->first();

            if (! $attributes) {
                continue;
            }

            $names = $attributes->get_attributes->pluck('name')->implode(' - ') ?: 'بدون ویژگی';

            if ($attributes->price != $row['price']) {
                $log[] = ['attributes' => $names, 'field' => 'قیمت', 'old' => $attributes->price, 'new' => $row['price']];
            }

            if ($attributes->stock != $row['stock']) {
                $log[] = ['attributes' => $names, 'field' => 'موجودی', 'old' => $attributes->stock, 'new' => $row['stock']];
            }

            if (($attributes->discount ?? 0) != ($row['discount'] ?? 0)) {
                $log[] = ['attributes' => $names, 'field' => 'تخفیف', 'old' => $attributes->discount ?? 0, 'new' => $row['discount'] ?? 0];
            }
        }

        return $log;
    }

    protected function adminName(): string
    {
        $admin = Auth::guard('adminPanel')->user();

        return $admin->full_name ?? $admin->name ?? 'مدیر';
    }

    /* ============================ منابع فرم (computed) ============================ */

    #[\Livewire\Attributes\Computed]
    public function categories(): \Illuminate\Support\Collection
    {
        return Category::detectLang()->where('type', 'productcat')->orderBy('ordering')->get(['id', 'title', 'category_id']);
    }

    /** درخت دسته‌بندی با تورفتگی برای select اصلی */
    #[\Livewire\Attributes\Computed]
    public function categoryOptions(): array
    {
        $result = [];
        $byParent = $this->categories()->groupBy('category_id');

        $walk = function ($parentId, $depth) use (&$walk, &$result, $byParent) {
            foreach ($byParent->get($parentId, collect()) as $cat) {
                $result[] = ['id' => $cat->id, 'title' => str_repeat('— ', $depth) . $cat->title];
                $walk($cat->id, $depth + 1);
            }
        };

        $walk(null, 0);

        return $result;
    }

    #[\Livewire\Attributes\Computed]
    public function attributeGroups(): \Illuminate\Support\Collection
    {
        return AttributeGroup::detectLang()->orderBy('ordering')->with('get_attributes')->get();
    }

    #[\Livewire\Attributes\Computed]
    public function currencies(): \Illuminate\Support\Collection
    {
        return Currency::latest()->get();
    }

    #[\Livewire\Attributes\Computed]
    public function sellers(): \Illuminate\Support\Collection
    {
        return Seller::with('seller_info')
            ->where(['status_register' => 'complete', 'status_documents' => 'Accept', 'status_work' => 'ACTIVE'])
            ->get();
    }

    #[\Livewire\Attributes\Computed]
    public function warehouses(): \Illuminate\Support\Collection
    {
        return Warehouse::active()->get();
    }

    #[\Livewire\Attributes\Computed]
    public function sizeTypes(): \Illuminate\Support\Collection
    {
        return SizeType::detectLang()->get(['id', 'title']);
    }

    #[\Livewire\Attributes\Computed]
    public function labelSuggestions(): array
    {
        return Label::detectLang()->limit(50)->pluck('title')->toArray();
    }

    #[\Livewire\Attributes\Computed]
    public function tagSuggestions(): array
    {
        return Tag::take(50)->pluck('name')->toArray();
    }

    public function render()
    {
        $sizeTypeSizes = [];

        if ($this->size_type_id) {
            $sizeTypeSizes = \App\Models\Size::where('size_type_id', $this->size_type_id)->orderBy('ordering')->get(['id', 'title']);
        }

        return view('admin-panel::livewire.admin.products.form', [
            'sizeTypeSizes' => $sizeTypeSizes,
        ]);
    }
}
