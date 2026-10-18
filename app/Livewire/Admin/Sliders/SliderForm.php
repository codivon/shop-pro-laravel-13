<?php

namespace App\Livewire\Admin\Sliders;

use App\Models\Slider;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * فرم ایجاد/ویرایش اسلایدر — پورت ۱:۱ SliderController@store/update (+validateSlider با همان پیام‌های فارسی).
 *
 * تفاوت مستند با قدیمی: تصویر در قدیمی از File Manager (input مخفی رشته‌ای) می‌آمد؛
 * اینجا آپلود مستقیم با uploadOptimizedImage در پوشه sliders انجام می‌شود و در ویرایش،
 * بدون آپلود فایل جدید، تصویر قبلی حفظ می‌گردد.
 */
#[Layout('admin-panel::layout')]
#[Title('فرم اسلایدر')]
class SliderForm extends Component
{
    use WithFileUploads;

    public ?Slider $slider = null;

    public bool $isEditing = false;

    /* ---------------- فیلدهای فرم (پورت store/update) ---------------- */

    /** فایل تصویر (TemporaryUploadedFile) — در ویرایش null یعنی «تصویر قبلی حفظ شود» */
    public $image;

    public array $pages = [];

    public array $groups = [];

    public string $title = '';

    public string $motionTitle = '';

    public string $link = '';

    public ?string $description = null;

    public bool $published = true;

    /* ============================ دسترسی + Mount ============================ */

    public function mount(?Slider $slider = null): void
    {
        if ($slider && $slider->exists) {
            auth('adminPanel')->user()?->can('sliders.update') ?: abort(403);

            $this->slider = $slider;
            $this->isEditing = true;

            // پیش‌بارگذاری (مثل فرم edit قدیمی)
            $this->pages = $slider->pages ?: [];
            $this->groups = $slider->groups ?: [];
            $this->title = (string) $slider->title;
            $this->motionTitle = (string) $slider->motionTitle;
            $this->link = (string) $slider->link;
            $this->description = $slider->description;
            $this->published = (bool) $slider->published;
        } else {
            auth('adminPanel')->user()?->can('sliders.create') ?: abort(403);
        }
    }

    /* ============================ مراجع ============================ */

    /** کاتالوگ صفحات با لیبل فارسی از خود مدل (پورت _pages_section قدیمی) */
    #[Computed]
    public function pageOptions(): array
    {
        return Slider::availablePages();
    }

    /** کاتالوگ گروه‌ها با لیبل/ابعاد فارسی از خود مدل (پورت _checkbox_grid قدیمی) */
    #[Computed]
    public function groupOptions(): array
    {
        return Slider::availableGroups();
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

    /* ============================ اعتبارسنجی (پورت ۱:۱ validateSlider) ============================ */

    protected function rules(): array
    {
        return [
            // قدیمی: 'image' => 'required|max:2048' — در ویرایش بدون فایل جدید، تصویر قبلی حفظ می‌شود
            'image'       => $this->isEditing ? 'nullable|image|max:2048' : 'required|image|max:2048',
            'pages'       => 'required|array|min:1',
            'pages.*'     => 'required|string|in:' . implode(',', array_keys(Slider::availablePages())),
            'groups'      => 'required|array|min:1',
            'groups.*'    => 'required|string|in:' . implode(',', array_keys(Slider::availableGroups())),
            'title'       => 'nullable|string|max:255',
            'motionTitle' => 'nullable|string|max:255',
            'link'        => 'nullable|string|max:500',
            'description' => 'nullable|string',
            'published'   => 'boolean',
        ];
    }

    protected function messages(): array
    {
        return [
            'image.required'  => 'انتخاب تصویر اسلایدر الزامی است.',
            'image.image'     => 'فایل انتخاب‌شده باید تصویر باشد.',
            'image.max'       => 'حجم تصویر نباید بیشتر از ۲ مگابایت باشد.',
            'pages.required'  => 'انتخاب حداقل یک صفحه الزامی است.',
            'pages.min'       => 'باید حداقل یک صفحه را انتخاب کنید.',
            'pages.*.in'      => 'صفحه‌ی انتخاب شده معتبر نیست.',
            'groups.required' => 'انتخاب حداقل یک گروه الزامی است.',
            'groups.min'      => 'باید حداقل یک گروه را انتخاب کنید.',
            'groups.*.in'     => 'گروه انتخاب شده معتبر نیست.',
        ];
    }

    /* ============================ ذخیره (پورت ۱:۱ store/update) ============================ */

    public function save(): void
    {
        if ($this->isEditing) {
            auth('adminPanel')->user()?->can('sliders.update') ?: abort(403);
        } else {
            auth('adminPanel')->user()?->can('sliders.create') ?: abort(403);
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
            'link'        => $this->link ?: null,
            'motionTitle' => $this->motionTitle ?: null,
            'description' => $this->description ?: null,
            'published'   => $this->published,
            'lang'        => app()->getLocale(), // مثل store/update قدیمی
        ];

        if ($this->isEditing) {
            if ($this->image) {
                // حذف فایل قبلی (الگوی پاک‌سازی رسانه‌های پنل — برندها)
                if ($this->slider->image) {
                    Storage::disk('public')->delete(ltrim($this->slider->image, '/'));
                }

                $this->slider->image = uploadOptimizedImage($this->image, 'sliders');
            }

            $this->slider->update($payload);

            $this->slider->save(); // برای ستون image آپلودشده

            session()->flash('np-toast', ['type' => 'success', 'message' => 'اسلایدر با موفقیت ویرایش شد.']);
        } else {
            // انحراف مستند: قدیمی ordering را null می‌گذاشت (اسلایدر جدید بالای لیست می‌پرید)؛
            // برای سازگاری با مرتب‌سازی ۱..n، انتهای لیست قرار می‌گیرد.
            $payload['ordering'] = (int) Slider::max('ordering') + 1;

            $payload['image'] = uploadOptimizedImage($this->image, 'sliders');

            Slider::create($payload);

            session()->flash('np-toast', ['type' => 'success', 'message' => 'اسلایدر با موفقیت ایجاد شد.']);
        }

        $this->redirectIntended(route('np.sliders.index', absolute: false), navigate: true);
    }

    /* ---------------- نمایش ---------------- */

    public function render()
    {
        return view('admin-panel::livewire.admin.sliders.slider-form');
    }
}
