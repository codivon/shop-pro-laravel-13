<?php

namespace App\Livewire\Admin\Pages;

use App\Models\Link;
use App\Models\Menu;
use App\Models\Page;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * فرم ایجاد/ویرایش صفحه — پورت ۱:۱ PageController@store/update.
 *
 * {page} با «اسلاگ» بایند می‌شود (Page::getRouteKeyName = 'slug').
 *
 * نکته پل CKEditor: JS پنل (ApEditor) خروجی ویرایشگر را روی
 * `component.$wire.description` می‌نویسد (سخت‌کدشده در admin.js)؛ پس محتوای صفحه
 * در پراپرتی `description` نگهداری و هنگام ذخیره در ستون `content` نوشته می‌شود.
 *
 * پورت update قدیمی: فقط title/content/slug/published به‌روزرسانی می‌شود
 * (متا در قدیمی آپدیت نمی‌شد — عیناً حفظ شد؛ فیلدهای متا در حالت ویرایش غیرفعال‌اند)
 * و بعد از تغییر اسلاگ، لینک‌های «/pages/{oldslug}» در منوها و لینک‌های فوتر
 * بازنویسی می‌شوند (پورت بازنویسی لینک PageController@update).
 */
#[Layout('admin-panel::layout')]
#[Title('فرم صفحه')]
class PageForm extends Component
{
    public ?Page $page = null;

    /** true = حالت ویرایش */
    public bool $editing = false;

    /* ---------------- فیلدهای فرم ---------------- */

    public string $title = '';

    public string $slug = '';

    /** محتوای صفحه (ویرایشگر CKEditor — در DB ستون content) */
    public ?string $description = null;

    public ?string $meta_title = null;

    public ?string $meta_description = null;

    public bool $published = true;

    /* ============================ دسترسی ============================ */

    public function mount(?Page $page = null): void
    {
        if ($page?->exists) {
            auth('adminPanel')->user()?->can('pages.update') ?: abort(403);

            $this->editing = true;
            $this->page = $page;

            // پیش‌بارگذاری مثل ویو edit قدیمی
            $this->title = (string) $page->title;
            $this->slug = (string) $page->slug;
            $this->description = $page->content;
            $this->meta_title = $page->meta_title;
            $this->meta_description = $page->meta_description;
            $this->published = (bool) $page->published;
        } else {
            auth('adminPanel')->user()?->can('pages.create') ?: abort(403);

            $this->page = new Page();
        }
    }

    /* ============================ اعتبارسنجی (پورت ۱:۱ validate) ============================ */

    protected function rules(): array
    {
        return [
            // قدیمی update فقط title/content را validate می‌کرد (unique اسلاگ فقط در store)
            'title'             => 'required|string|max:191',
            'description'       => 'required',
            'slug'              => $this->editing ? 'nullable|string' : 'nullable|string|unique:pages,slug',
            'meta_title'        => 'nullable|string',
            'meta_description'  => 'nullable|string',
            'published'         => 'boolean',
        ];
    }

    protected function messages(): array
    {
        return [
            'title.required'       => 'عنوان صفحه الزامی است.',
            'title.max'            => 'عنوان صفحه نمی‌تواند بیشتر از ۱۹۱ کاراکتر باشد.',
            'description.required' => 'محتوای صفحه الزامی است.',
            'slug.unique'          => 'این آدرس (اسلاگ) قبلاً استفاده شده است.',
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'title'             => 'عنوان',
            'description'       => 'محتوا',
            'slug'              => 'آدرس (اسلاگ)',
            'meta_title'        => 'عنوان سئو',
            'meta_description'  => 'توضیحات سئو',
        ];
    }

    /* ============================ ذخیره (پورت ۱:۱ store) ============================ */

    public function store()
    {
        auth('adminPanel')->user()?->can('pages.create') ?: abort(403);

        try {
            $data = $this->validate();
        } catch (\Illuminate\Validation\ValidationException $e) {
            $first = collect($e->errors())->flatten()->first();

            $this->dispatch('toast', message: $first ?? 'لطفا خطاهای فرم را برطرف کنید.', type: 'error');

            throw $e;
        }

        Page::create([
            'title'            => $this->title,
            'content'          => $this->description,
            'slug'             => $this->slug ?: $this->title,
            'meta_title'       => $this->meta_title,
            'meta_description' => $this->meta_description,
            'published'        => $this->published ? true : false,
            'lang'             => app()->getLocale(),
        ]);

        session()->flash('np-toast', ['type' => 'success', 'message' => 'صفحه با موفقیت ایجاد شد.']);

        $this->redirectIntended(route('np.pages.index', absolute: false), navigate: true);

        return null;
    }

    /* ============================ بروزرسانی (پورت ۱:۱ update + بازنویسی لینک) ============================ */

    public function update()
    {
        auth('adminPanel')->user()?->can('pages.update') ?: abort(403);

        try {
            $this->validate();
        } catch (\Illuminate\Validation\ValidationException $e) {
            $first = collect($e->errors())->flatten()->first();

            $this->dispatch('toast', message: $first ?? 'لطفا خطاهای فرم را برطرف کنید.', type: 'error');

            throw $e;
        }

        // اسلاگ قدیمی برای بازنویسی لینک‌ها باید قبل از update نگه داشته شود
        $oldSlug = $this->page->slug;

        $this->page->update([
            'title'     => $this->title,
            'content'   => $this->description,
            'slug'      => $this->slug ?: $this->title,
            'published' => $this->published ? true : false,
            // متا در قدیمی آپدیت نمی‌شد — عیناً حفظ شد
        ]);

        // پورت بازنویسی لینک: منوها و لینک‌های فوتر به اسلاگ جدید هدایت شوند
        Menu::where('link', '/pages/' . $oldSlug)->update([
            'link' => '/pages/' . $this->page->slug,
        ]);

        Link::where('link', '/pages/' . $oldSlug)->update([
            'link' => '/pages/' . $this->page->slug,
        ]);

        session()->flash('np-toast', ['type' => 'success', 'message' => 'صفحه با موفقیت ویرایش شد.']);

        $this->redirectIntended(route('np.pages.index', absolute: false), navigate: true);

        return null;
    }

    /* ============================ نمایش ============================ */

    public function render()
    {
        return view('admin-panel::livewire.admin.pages.page-form');
    }
}
