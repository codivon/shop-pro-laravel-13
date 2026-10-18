<?php

namespace App\Livewire\Admin\Posts;

use App\Models\Category;
use App\Models\FieldValue;
use App\Models\Fild;
use App\Models\Post;
use Cviebrock\EloquentSluggable\Services\SlugService;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use Morilog\Jalali\Jalalian;

/**
 * فرم ایجاد/ویرایش نوشته — پورت ۱:۱ PostController@store/update (پنل قدیمی).
 *
 * یک کامپوننت دوتایی (create + edit) به الگوی AttributeForm فاز ۵.
 * CKEditor محتوا با پل ApEditor متصل است — پراپرتی «description» قرارداد پل پنل است
 * (الگوی products/category form) و در ذخیره به ستون content نوشته نگاشت می‌شود.
 *
 * فیلدهای اختصاصی: جدول filds ستون belongs_to enum(users,products,blogs) دارد؛
 * PostController قدیمی «posts» می‌خواند که هرگز match نمی‌شد (فرم بدون فیلد) و
 * saveFieldValues با belongs_to=posts در MySQL strict خطای enum می‌داد —
 * مطابق اصلاح فیلدهای فاز ۵ (FildIndex) از «blogs» (مقالات) استفاده شد.
 *
 * رفع باگ‌های مستند:
 *  ۱) created_by قدیمی null ذخیره می‌کرد (فرم فیلد نداشت) → نوشته‌های پنل در لیست قدیمی
 *     «هوش مصنویی» نشان داده می‌شدند → صریحاً 'admin' ذخیره می‌شود.
 *  ۲) update قدیمی اسلاگ خالی را با title خام (بدون sluggable_helper_function) می‌نوشت →
 *     اینجا مثل store نرمال می‌شود و چک تکراری با ignore-id هم برای edit اضافه شد
 *     (جلوگیری از SQLSTATE 23000 — اسلاگ unique).
 */
#[Layout('admin-panel::layout')]
#[Title('فرم نوشته')]
class PostForm extends Component
{
    use WithFileUploads;

    public ?Post $post = null;

    public bool $isEditing = false;

    /* ---------------- فیلدهای فرم (پورت create/edit قدیمی) ---------------- */

    /** نوع نوشته — enum: text | video | podcast */
    public string $post_type = 'text';

    public string $title = '';

    public string $slug = '';

    /** تاریخ انتشار شمسی — فرمت پنل Y-m-d H:i:s (خط تیره هماهنگ با سرور) */
    public string $publish_date = '';

    public ?string $summary = null;

    /** محتوای نوشته — پل ApEditor (نگاشت به ستون content) */
    public ?string $description = null;

    /** دسته‌بندی‌های انتخاب‌شده (پورت categories[] قدیمی) */
    public array $categories = [];

    public ?string $meta_title = null;

    public ?string $meta_description = null;

    public ?string $video_url = null;

    public ?string $podcast_url = null;

    /** پیش‌فرض‌ها مثل فرم قدیمی create (چک‌باکس‌های checked) */
    public bool $published = true;

    public bool $is_editor_pick = false;

    public bool $allow_comments = true;

    /** تصویر شاخص (Livewire TemporaryUploadedFile) */
    public $image = null;

    /** فیلدهای اختصاصی — کلیدها شناسه جدول filds هستند */
    public array $filds = [];

    /* ============================ Mount ============================ */

    public function mount(?Post $post = null): void
    {
        $this->isEditing = (bool) $post;

        auth('adminPanel')->user()?->can($this->isEditing ? 'posts.update' : 'posts.create') ?: abort(403);

        $this->post = $post;

        if ($this->isEditing) {
            $this->post_type = (string) $post->post_type ?: 'text';
            $this->title = (string) $post->title;
            $this->slug = (string) $post->slug;
            $this->publish_date = $post->publish_date
                ? Jalalian::fromDateTime($post->publish_date)->format('Y-m-d H:i:s')
                : '';
            $this->summary = $post->summary;
            // پل ApEditor → پراپرتی description؛ ستون مدل content است
            $this->description = (string) ($post->content ?? '');
            $this->categories = $post->Categories()->pluck('categories.id')->map(fn ($id) => (int) $id)->all();
            $this->meta_title = $post->meta_title;
            $this->meta_description = $post->meta_description;
            $this->video_url = $post->video_url;
            $this->podcast_url = $post->podcast_url;
            $this->published = (bool) $post->published;
            $this->is_editor_pick = (bool) $post->is_editor_pick;
            $this->allow_comments = (bool) $post->allow_comments;

            // مقادیر ذخیره‌شده فیلدهای اختصاصی (پورت fieldValues قدیمی + قید belongs_to مثل UserForm)
            foreach ($this->postFieldValues() as $fieldId => $value) {
                $this->filds[$fieldId] = (string) $value;
            }
        }

        // مقدار پیش‌فرض فیلدهای اختصاصی از ستون value خود فیلد (مثل فرم create قدیمی)
        foreach ($this->fildsList as $fild) {
            if (! array_key_exists($fild->id, $this->filds)) {
                $this->filds[$fild->id] = $fild->value !== null ? (string) $fild->value : '';
            }

            // سلکت اجباری مثل HTML همیشه مقدار دارد (اولین گزینه)
            if ($fild->type === 'select' && $fild->required && ($this->filds[$fild->id] ?? '') === '') {
                $firstOption = trim((string) (explode(',', (string) $fild->select_options)[0] ?? ''));
                $this->filds[$fild->id] = $firstOption;
            }
        }
    }

    /* ============================ مراجع ============================ */

    /**
     * دسته‌های وبلاگ به‌صورت درخت چک‌باکس — یک کوئری با ترتیب پیش‌پیمایش
     * (پورت: Category::detectLang()->where('type','postcat')->orderBy('ordering'))
     */
    #[Computed]
    public function categoryTree(): array
    {
        $flat = Category::query()
            ->detectLang()
            ->where('type', 'postcat')
            ->orderBy('ordering')
            ->orderBy('id')
            ->get();

        $byParent = $flat->groupBy(fn (Category $category) => (int) $category->category_id);

        $rows = [];

        $walk = function (int $parentId, int $depth = 0) use (&$walk, $byParent, &$rows): void {
            if ($depth > 10) {
                return;
            }

            foreach ($byParent->get($parentId, collect()) as $category) {
                $rows[] = ['model' => $category, 'depth' => $depth];
                $walk($category->id, $depth + 1);
            }
        };

        $walk(0);

        return $rows;
    }

    /** پورت: Fild::where('belongs_to', 'posts') — با اصلاح belongs_to=blogs (enum دیتابیس) */
    #[Computed]
    public function fildsList()
    {
        return Fild::where('belongs_to', 'blogs')->orderBy('created_at', 'desc')->get();
    }

    protected function postFieldValues(): array
    {
        if (! $this->isEditing) {
            return [];
        }

        return FieldValue::where('belongs_to', 'blogs')
            ->where('related_id', $this->post->id)
            ->pluck('value', 'field_id')
            ->toArray();
    }

    /* ============================ اسلاگ (پورت generate_slug) ============================ */

    public function generateSlug(): void
    {
        $source = trim($this->title) !== '' ? $this->title : $this->slug;

        if (trim((string) $source) === '') {
            $this->addError('slug', 'برای ساخت خودکار نامک، ابتدا عنوان را وارد کنید.');

            return;
        }

        $this->slug = SlugService::createSlug(Post::class, 'slug', $source);
    }

    /* ============================ اعتبارسنجی (پورت ۱:۱ Requestها) ============================ */

    protected function rules(): array
    {
        $rules = [
            'post_type'        => 'required|in:text,video,podcast',
            'title'            => 'required|string|max:191',
            'summary'          => 'nullable|string',
            'description'      => 'nullable|string', // محتوا (ستون content)
            'categories'       => 'required|array',
            'categories.*'     => 'exists:categories,id',
            'image'            => 'nullable|image|max:2048',
            'slug'             => 'nullable|unique:posts,slug' . ($this->isEditing ? ',' . $this->post->id : ''),
            'publish_date'     => 'nullable|string',
            'meta_title'       => 'nullable|string',
            'meta_description' => 'nullable|string',
            'video_url'        => 'nullable|url|required_if:post_type,video',
            'podcast_url'      => 'nullable|url|required_if:post_type,podcast',
            'published'        => 'boolean',
            'is_editor_pick'   => 'boolean',
            'allow_comments'   => 'boolean',
            'filds'            => 'nullable|array',
        ];

        // اعتبارسنجی فیلدهای اجباری اختصاصی — پیام مثل قدیمی: «فیلد X اجباری است.»
        foreach (Fild::where('belongs_to', 'blogs')->where('required', 1)->get(['id', 'title']) as $requiredFild) {
            $rules["filds.{$requiredFild->id}"] = 'required';
        }

        return $rules;
    }

    protected function messages(): array
    {
        $messages = [
            'post_type.required'          => 'نوع نوشته الزامی است.',
            'post_type.in'                => 'نوع نوشته نامعتبر است.',
            'title.required'              => 'عنوان الزامی است.',
            'title.max'                   => 'عنوان نمی‌تواند بیشتر از ۱۹۱ کاراکتر باشد.',
            'categories.required'         => 'انتخاب دسته‌بندی الزامی است.',
            'categories.*.exists'         => 'یکی از دسته‌بندی‌های انتخاب‌شده نامعتبر است.',
            'image.image'                 => 'فایل آپلود شده باید تصویر باشد.',
            'image.max'                   => 'حجم تصویر نباید بیشتر از ۲ مگابایت باشد.',
            'slug.unique'                 => 'اسلاگ تکراری است. لطفا نامک دیگری انتخاب کنید.',
            'video_url.url'               => 'لینک ویدیو معتبر نیست.',
            'video_url.required_if'       => 'برای نوشته ویدیویی، لینک ویدیو الزامی است.',
            'podcast_url.url'             => 'لینک پادکست معتبر نیست.',
            'podcast_url.required_if'     => 'برای نوشته پادکست، لینک پادکست الزامی است.',
            // توجه: پیام سراسری wildcard «filds.*.required» نباید تعریف شود —
            // در Laravel برای اتریبیوت تودرتو (filds.8) الگوی wildcard بر کلید دقیق مقدم است
            // (Validator::getFromLocalArray) و پیام اختصاصی «فیلد X اجباری است.» را می‌بلعد.
        ];

        foreach (Fild::where('belongs_to', 'blogs')->where('required', 1)->get(['id', 'title']) as $requiredFild) {
            $messages["filds.{$requiredFild->id}.required"] = "فیلد {$requiredFild->title} اجباری است.";
        }

        return $messages;
    }

    protected function validationAttributes(): array
    {
        return [
            'post_type'        => 'نوع نوشته',
            'title'            => 'عنوان',
            'slug'             => 'نامک (اسلاگ)',
            'publish_date'     => 'تاریخ انتشار',
            'summary'          => 'خلاصه',
            'description'      => 'محتوا',
            'categories'       => 'دسته‌بندی‌ها',
            'meta_title'       => 'عنوان سئو',
            'meta_description' => 'توضیحات سئو',
            'video_url'        => 'لینک ویدیو',
            'podcast_url'      => 'لینک پادکست',
            'image'            => 'تصویر شاخص',
        ];
    }

    /**
     * چک تکراری بودن اسلاگِ نرمال‌شده (پورت پاسخ Repetition-slug قدیمی در store).
     * قدیمی فقط در create این چک را داشت؛ در edit هم اضافه شد تا اسلاگِ حاصل از
     * «slug ?: title» (رفع باگ) باعث خطای unique دیتابیس (۵۰۰) نشود.
     */
    protected function guardDuplicateSlug(): void
    {
        $slug = sluggable_helper_function($this->slug ?: $this->title);

        if (trim((string) $slug) === '') {
            return;
        }

        $query = Post::where('slug', $slug);

        if ($this->isEditing) {
            $query->where('id', '!=', $this->post->id);
        }

        if ($query->exists()) {
            $this->addError('slug', "اسلاگ تکراری است. نامک «{$slug}» قبلاً استفاده شده است.");
        }
    }

    /* ============================ ذخیره (پورت ۱:۱ store / update) ============================ */

    public function save()
    {
        auth('adminPanel')->user()?->can($this->isEditing ? 'posts.update' : 'posts.create') ?: abort(403);

        try {
            $this->validate();
        } catch (ValidationException $e) {
            $first = collect($e->errors())->flatten()->first();

            $this->dispatch('toast', message: $first ?? 'لطفا خطاهای فرم را برطرف کنید.', type: 'error');

            throw $e;
        }

        $this->guardDuplicateSlug();

        if ($this->getErrorBag()->isNotEmpty()) {
            $this->dispatch('toast', message: $this->getErrorBag()->first(), type: 'error');

            return null;
        }

        // مقدارهای checkbox فیلدهای اختصاصی به 0/1 نرمال می‌شود (الگوی UserForm فاز ۴)
        $filds = collect($this->filds)
            ->map(fn ($value) => $value === true ? '1' : ($value === false ? '0' : $value))
            ->all();

        $data = [
            'post_type'        => $this->post_type,
            'title'            => $this->title,
            'summary'          => $this->summary,
            'content'          => $this->description,
            'video_url'        => $this->video_url,
            'podcast_url'      => $this->podcast_url,
            'meta_title'       => $this->meta_title,
            'meta_description' => $this->meta_description,
            'published'        => $this->published,
            'is_editor_pick'   => $this->is_editor_pick,
            'allow_comments'   => $this->allow_comments,
        ];

        // پورت: تاریخ شمسی Y-m-d H:i:s → میلادی (گارد خطای تاریخ نامعتبر مثل فاز ۴)
        // نکته ۱:۱: در validated() قدیمی کلید publish_date همیشه هست (nullable) →
        // خالی بودن آن در update هم مقدار قبلی را null می‌کند
        $data['publish_date'] = null;

        if ($this->publish_date) {
            try {
                $data['publish_date'] = Jalalian::fromFormat(
                    'Y-m-d H:i:s',
                    convertPersianToEnglish($this->publish_date)
                )->toCarbon();
            } catch (\Throwable) {
                $this->dispatch('toast', message: 'تاریخ وارد شده نامعتبر است. لطفا از تاریخ‌انتخابگر استفاده کنید.', type: 'error');

                return null;
            }
        }

        if ($this->isEditing) {
            $post = $this->post;

            // پورت update: $data['slug'] = $data['slug'] ?: $data['title'] — با نرمال‌سازی مثل store (رفع باگ)
            $data['slug'] = sluggable_helper_function($this->slug ?: $this->title) ?: $this->title;

            // پورت update: تصویر جدید → آپلود با پاک‌سازی قبلی؛ وگرنه تصویر فعلی حفظ می‌شود
            if ($this->image) {
                $data['image'] = uploadOptimizedImage($this->image, 'posts', $post->id);
            }

            $post->update($data);
            $post->Categories()->sync($this->categories);

            if (count($filds)) {
                saveFieldValues($filds, 'blogs', $post->id);
            }

            session()->flash('np-toast', ['type' => 'success', 'message' => 'نوشته با موفقیت ویرایش شد.']);
        } else {
            // پورت store: status=end + admin_id + lang
            $data['status'] = 'end';
            $data['created_by'] = 'admin'; // رفع باگ: قدیمی null ذخیره می‌کرد و نوشته «هوش مصنویی» برچسب می‌خورد
            $data['slug'] = sluggable_helper_function($this->slug ?: $this->title) ?: $this->title;
            $data['admin_id'] = auth('adminPanel')->user()->id;
            $data['lang'] = app()->getLocale();

            if ($this->image) {
                $data['image'] = uploadOptimizedImage($this->image, 'posts');
            }

            $post = Post::create($data);
            $post->Categories()->attach($this->categories);

            if (count($filds)) {
                saveFieldValues($filds, 'blogs', $post->id);
            }

            session()->flash('np-toast', ['type' => 'success', 'message' => 'نوشته با موفقیت ایجاد شد.']);
        }

        $this->redirectIntended(route('np.posts.index', absolute: false), navigate: true);

        return null;
    }

    /* ============================ نمایش ============================ */

    public static function jdate($date, $format = 'Y/m/d'): string
    {
        if (! $date) {
            return '—';
        }

        return Jalalian::fromDateTime($date)->format($format);
    }

    public function render()
    {
        return view('admin-panel::livewire.admin.posts.post-form');
    }
}
