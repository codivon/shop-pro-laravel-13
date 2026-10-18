<?php

namespace App\Livewire\Admin\Taxonomy;

use App\Models\Category;
use App\Models\Filter;
use Cviebrock\EloquentSluggable\Services\SlugService;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * فرم ویرایش دسته‌بندی — پورت ۱:۱ CategoryController@update
 * ({category} با اسلاگ بایند می‌شود — Category::getRouteKeyName = 'slug').
 *
 * فیلدهای نوع فیلتر / کمیسیون / توضیحات فقط برای productcat نمایش داده می‌شوند
 * (مثل ویوهای edit قدیمی)، ولی update() قدیمی این ستون‌ها را برای هر دو نوع می‌نوشت؛
 * پس ۱:۱ حفظ شد (برای postcat مقدار پیش‌فرض/تهی مثل درخواست فاقد آن فیلدها ذخیره می‌شود).
 *
 * رفع باگ قدیمی: آپلود background_image از $request->image انجام می‌شد (ورودی اشتباه).
 */
#[Layout('admin-panel::layout')]
#[Title('ویرایش دسته‌بندی')]
class CategoryForm extends Component
{
    use WithFileUploads;

    public Category $category;

    public string $type = 'productcat';

    /* ---------------- فیلدهای فرم (پورت update) ---------------- */

    public string $title = '';

    public string $slug = '';

    public ?string $meta_title = null;

    public ?string $meta_description = null;

    public ?string $description = null;

    public string $filter_type = 'inherit';

    public ?int $filter_id = null;

    public $commission = null;

    public bool $published = true;

    /* ---------------- تصاویر ---------------- */

    public $image;

    public $background_image;

    /* ============================ دسترسی ============================ */

    /** پورت authorizeCategory — گیت بر اساس نوع خودِ دسته */
    protected function authorizeCategory(): void
    {
        switch ($this->type) {
            case 'postcat':
                auth('adminPanel')->user()?->can('posts.category') ?: abort(403);
                break;

            case 'productcat':
                auth('adminPanel')->user()?->can('products.category') ?: abort(403);
                break;

            default:
                abort(403);
        }
    }

    public function mount(Category $category): void
    {
        $this->category = $category;
        $this->type = (string) $category->type;
        $this->authorizeCategory();

        // پیش‌بارگذاری (مثل مودال edit قدیمی که همین ویوها را برمی‌گرداند)
        // نکته ۱:۱: ویوهای postcat فیلدهای فیلتر/کمیسیون/توضیحات را ندارند → در قدیمی هم
        // این فیلدها در update ارسال نمی‌شدند (پیش‌فرض/تهی)؛ پس فقط برای productcat پر می‌شوند.
        $this->title = (string) $category->title;
        $this->slug = (string) $category->slug;
        $this->meta_title = $category->meta_title;
        $this->meta_description = $category->meta_description;
        $this->published = (bool) $category->published;

        if ($category->type === 'productcat') {
            $this->description = $category->description;
            $this->filter_type = $category->filter_type ?: 'inherit';
            $this->filter_id = $category->filter_id;
            $this->commission = $category->commission;
        }
    }

    /* ============================ مراجع ============================ */

    /**
     * فهرست فیلترها برای «انتخاب فیلتر».
     * رفع باگ قدیمی: ویو edit به متغیر $filters اشاره می‌کرد ولی edit() آن را پاس نمی‌داد
     * (دراپ‌داون فیلتر همیشه خالی بود) — اینجا مثل FilterController@index با Filter::latest.
     */
    #[Computed]
    public function filters()
    {
        return Filter::latest()->get(['id', 'title']);
    }

    /* ============================ اسلاگ ============================ */

    /**
     * پورت generate_slug قدیمی (دکمه «ایجاد خودکار» /category/slug).
     * تفاوت: اسکریپت قدیمی مقدار meta_title را به‌عنوان منبع می‌فرستاد؛
     * طبق اسپک منبعِ اول «عنوان دسته» است (متا عنوان فقط جایگزین).
     */
    public function generateSlug(): void
    {
        $this->authorizeCategory();

        $source = trim($this->title) !== '' ? $this->title : $this->meta_title;

        if (trim((string) $source) === '') {
            $this->addError('slug', 'برای ساخت خودکار اسلاگ، ابتدا عنوان را وارد کنید.');

            return;
        }

        $this->slug = SlugService::createSlug(Category::class, 'slug', $source);
    }

    /* ============================ اعتبارسنجی (پورت ۱:۱ validate) ============================ */

    protected function rules(): array
    {
        return [
            'title'             => 'required|string',
            'slug'              => 'nullable|string|unique:categories,slug,' . $this->category->id,
            'meta_title'        => 'nullable|string',
            'meta_description'  => 'nullable|string',
            'description'       => 'nullable|string',
            'filter_type'       => 'required|in:inherit,none,filterId',
            'filter_id'         => 'nullable|exists:filters,id',
            'commission'        => 'nullable|numeric|max:100',
            'published'         => 'boolean',
            // قدیمی فقط image => image داشت و background_image اصلاً اعتبارسنجی نمی‌شد
            'image'             => 'nullable|image|max:2048',
            'background_image'  => 'nullable|image|max:2048',
        ];
    }

    protected function messages(): array
    {
        return [
            'title.required'             => 'نام دسته بندی الزامی است.',
            'slug.unique'                => 'این نامک قبلاً استفاده شده است.',
            'filter_type.required'       => 'نوع فیلتر الزامی است.',
            'filter_type.in'             => 'نوع فیلتر نامعتبر است.',
            'filter_id.exists'           => 'فیلتر انتخاب‌شده نامعتبر است.',
            'commission.numeric'         => 'میزان کمیسیون باید عدد باشد.',
            'commission.max'             => 'میزان کمیسیون نمی‌تواند بیشتر از ۱۰۰ باشد.',
            'image.image'                => 'فایل تصویر دسته‌بندی باید تصویر باشد.',
            'image.max'                  => 'حجم تصویر دسته‌بندی نباید بیشتر از ۲ مگابایت باشد.',
            'background_image.image'     => 'فایل تصویر پس زمینه باید تصویر باشد.',
            'background_image.max'       => 'حجم تصویر پس زمینه نباید بیشتر از ۲ مگابایت باشد.',
        ];
    }

    /** جایگزین ConvertEmptyStringsToNull قدیمی برای فیلد عددی کمیسیون */
    protected function normalizeCommission(): void
    {
        $value = convertPersianToEnglish((string) ($this->commission ?? ''));
        $value = str_replace(',', '', $value);

        $this->commission = $value === '' ? null : $value;
    }

    /* ============================ ذخیره (پورت ۱:۱ update) ============================ */

    public function save()
    {
        $this->authorizeCategory();

        $this->normalizeCommission();

        try {
            $this->validate();
        } catch (ValidationException $e) {
            $first = collect($e->errors())->flatten()->first();

            $this->dispatch('toast', message: $first ?? 'لطفا خطاهای فرم را برطرف کنید.', type: 'error');

            throw $e;
        }

        $this->category->update([
            'title'            => $this->title,
            'slug'             => $this->slug ?: $this->title,
            'meta_title'       => $this->meta_title,
            'meta_description' => $this->meta_description,
            // postcat این فیلد را در فرم ندارد (مثل قدیمی → تهی ذخیره می‌شود)
            'description'      => $this->description ?: null,
            'filter_type'      => $this->filter_type ?: 'inherit',
            'filter_id'        => $this->filter_id,
            'commission'       => $this->commission,
            'published'        => $this->published,
        ]);

        // آپلود تصویر اصلی — از ورودی خودش
        if ($this->image) {
            $this->category->image = uploadOptimizedImage($this->image, 'categories', $this->category->id);
            $this->category->save();
        }

        // آپلود تصویر پس زمینه — رفع باگ قدیمی که $request->image را ذخیره می‌کرد
        if ($this->background_image) {
            $this->category->background_image = uploadOptimizedImage($this->background_image, 'categories', $this->category->id);
            $this->category->save();
        }

        session()->flash('np-toast', ['type' => 'success', 'message' => 'دسته بندی با موفقیت ویرایش شد.']);

        $this->redirectIntended(
            route($this->type === 'postcat' ? 'np.categories.post' : 'np.categories.product', absolute: false),
            navigate: true
        );

        return null;
    }

    /* ============================ نمایش ============================ */

    public function render()
    {
        return view('admin-panel::livewire.admin.taxonomy.category-form');
    }
}
