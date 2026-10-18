<?php

namespace App\Livewire\Admin\Banners;

use App\Models\Banner;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * فرم ایجاد/ویرایش بنر — پورت ۱:۱ BannerController@store/update (+validateBanner با همان پیام‌های فارسی).
 *
 * تفاوت با اسلایدر (پورت دقیق قدیمی): بنر ستون lang/motionTitle ندارد و جستجوی فهرست بدون detectLang است.
 */
#[Layout('admin-panel::layout')]
#[Title('فرم بنر')]
class BannerForm extends Component
{
    use WithFileUploads;

    public ?Banner $banner = null;

    public bool $isEditing = false;

    /* ---------------- فیلدهای فرم (پورت store/update) ---------------- */

    /** فایل تصویر (TemporaryUploadedFile) — در ویرایش null یعنی «تصویر قبلی حفظ شود» */
    public $image;

    public array $pages = [];

    public array $groups = [];

    public array $places = [];

    public string $title = '';

    public string $link = '';

    public ?string $description = null;

    public bool $published = true;

    /* ============================ دسترسی + Mount ============================ */

    public function mount(?Banner $banner = null): void
    {
        if ($banner && $banner->exists) {
            auth('adminPanel')->user()?->can('banners.update') ?: abort(403);

            $this->banner = $banner;
            $this->isEditing = true;

            // پیش‌بارگذاری (مثل فرم edit قدیمی)
            $this->pages = $banner->pages ?: [];
            $this->groups = $banner->groups ?: [];
            $this->places = $banner->places ?: [];
            $this->title = (string) $banner->title;
            $this->link = (string) $banner->link;
            $this->description = $banner->description;
            $this->published = (bool) $banner->published;
        } else {
            auth('adminPanel')->user()?->can('banners.create') ?: abort(403);
        }
    }

    /* ============================ مراجع ============================ */

    /** کاتالوگ صفحات با لیبل فارسی از خود مدل */
    #[Computed]
    public function pageOptions(): array
    {
        return Banner::availablePages();
    }

    /** کاتالوگ گروه‌ها با لیبل/ابعاد فارسی از خود مدل */
    #[Computed]
    public function groupOptions(): array
    {
        return Banner::availableGroups();
    }

    /** کاتالوگ موقعیت‌ها با لیبل فارسی از خود مدل */
    #[Computed]
    public function placeOptions(): array
    {
        return Banner::availablePlaces();
    }

    /* ---------------- دکمه‌های «انتخاب همه / پاک کردن» (پورت sk-mini-btn قدیمی) ---------------- */

    public function selectAllPages(): void
    {
        $this->pages = array_keys($this->pageOptions);
    }

    public function clearPages(): void
    {
        $this->pages = [];
    }

    public function selectAllGroups(): void
    {
        $this->groups = array_keys($this->groupOptions);
    }

    public function clearGroups(): void
    {
        $this->groups = [];
    }

    public function selectAllPlaces(): void
    {
        $this->places = array_keys($this->placeOptions);
    }

    public function clearPlaces(): void
    {
        $this->places = [];
    }

    /* ============================ اعتبارسنجی (پورت ۱:۱ validateBanner) ============================ */

    protected function rules(): array
    {
        return [
            // قدیمی: 'image' => 'required|max:2048' — در ویرایش بدون فایل جدید، تصویر قبلی حفظ می‌شود
            'image'       => $this->isEditing ? 'nullable|image|max:2048' : 'required|image|max:2048',
            'pages'       => 'required|array|min:1',
            'pages.*'     => 'required|string|in:' . implode(',', array_keys(Banner::availablePages())),
            'groups'      => 'required|array|min:1',
            'groups.*'    => 'required|string|in:' . implode(',', array_keys(Banner::availableGroups())),
            'places'      => 'required|array|min:1',
            'places.*'    => 'required|string|in:' . implode(',', array_keys(Banner::availablePlaces())),
            'title'       => 'nullable|string|max:255',
            'link'        => 'nullable|string|max:500',
            'description' => 'nullable|string',
            'published'   => 'boolean',
        ];
    }

    protected function messages(): array
    {
        return [
            'image.required'  => 'انتخاب تصویر بنر الزامی است.',
            'image.image'     => 'فایل انتخاب‌شده باید تصویر باشد.',
            'image.max'       => 'حجم تصویر نباید بیشتر از ۲ مگابایت باشد.',
            'pages.required'  => 'انتخاب حداقل یک صفحه الزامی است.',
            'pages.min'       => 'باید حداقل یک صفحه را انتخاب کنید.',
            'pages.*.in'      => 'صفحه‌ی انتخاب شده معتبر نیست.',
            'groups.required' => 'انتخاب حداقل یک گروه الزامی است.',
            'groups.min'      => 'باید حداقل یک گروه را انتخاب کنید.',
            'groups.*.in'     => 'گروه انتخاب شده معتبر نیست.',
            'places.required' => 'انتخاب حداقل یک موقعیت الزامی است.',
            'places.min'      => 'باید حداقل یک موقعیت را انتخاب کنید.',
            'places.*.in'     => 'موقعیت انتخاب شده معتبر نیست.',
        ];
    }

    /* ============================ ذخیره (پورت ۱:۱ store/update) ============================ */

    public function save(): void
    {
        if ($this->isEditing) {
            auth('adminPanel')->user()?->can('banners.update') ?: abort(403);
        } else {
            auth('adminPanel')->user()?->can('banners.create') ?: abort(403);
        }

        try {
            $this->validate();
        } catch (ValidationException $e) {
            $first = collect($e->errors())->flatten()->first();

            $this->dispatch('toast', message: $first ?? 'لطفا خطاهای فرم را برطرف کنید.', type: 'error');

            throw $e;
        }

        // قدیمی روی ConvertEmptyStringsToNull تکیه داشت → خالی‌ها null ذخیره شوند
        $payload = [
            'title'       => $this->title ?: null,
            'pages'       => $this->pages,
            'groups'      => $this->groups,
            'places'      => $this->places,
            'link'        => $this->link ?: null,
            'description' => $this->description ?: null,
            'published'   => $this->published,
        ];

        if ($this->isEditing) {
            if ($this->image) {
                // حذف فایل قبلی (الگوی پاک‌سازی رسانه‌های پنل — برندها)
                if ($this->banner->image) {
                    Storage::disk('public')->delete(ltrim($this->banner->image, '/'));
                }

                $this->banner->image = uploadOptimizedImage($this->image, 'banners');
            }

            $this->banner->update($payload);

            $this->banner->save(); // برای ستون image آپلودشده

            session()->flash('np-toast', ['type' => 'success', 'message' => 'بنر با موفقیت ویرایش شد.']);
        } else {
            // انحراف مستند: قدیمی ordering را null می‌گذاشت (بنر جدید بالای لیست می‌پرید)؛
            // برای سازگاری با مرتب‌سازی ۱..n، انتهای لیست قرار می‌گیرد.
            $payload['ordering'] = (int) Banner::max('ordering') + 1;

            $payload['image'] = uploadOptimizedImage($this->image, 'banners');

            Banner::create($payload);

            session()->flash('np-toast', ['type' => 'success', 'message' => 'بنر با موفقیت ایجاد شد.']);
        }

        $this->redirectIntended(route('np.banners.index', absolute: false), navigate: true);
    }

    /* ---------------- نمایش ---------------- */

    public function render()
    {
        return view('admin-panel::livewire.admin.banners.banner-form');
    }
}
