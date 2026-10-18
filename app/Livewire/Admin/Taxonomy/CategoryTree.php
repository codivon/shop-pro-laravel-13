<?php

namespace App\Livewire\Admin\Taxonomy;

use App\Models\Category;
use Cviebrock\EloquentSluggable\Services\SlugService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * درخت دسته‌بندی‌ها (محصولات: productcat / وبلاگ: postcat)
 *
 * پورت ۱:۱ از ProductController::categories + PostController::categories
 * و CategoryController@store/destroy/sort (پنل قدیمی).
 *
 * انحراف آگاهانه: مرتب‌سازی درگ‌انددراپ (jquery.nestable) با دکمه‌های ↑↓ و
 * سلکت «انتقال به سطح» جایگزین شده که همان نتیجه نهایی (category_id + ordering)
 * را بدون jQuery اعمال می‌کند.
 */
#[Layout('admin-panel::layout')]
#[Title('دسته‌بندی‌ها')]
class CategoryTree extends Component
{
    /** نوع صفحه: productcat (محصولات) یا postcat (وبلاگ) — از روت تعیین می‌شود */
    public string $type = 'productcat';

    /* ---------------- فرم ایجاد سریع (پورت store) ---------------- */

    public bool $creating = false;

    /** والد فرم ایجاد؛ null = دسته اصلی (مثل فرم بالای صفحه قدیمی) */
    public ?int $creatingParent = null;

    public string $title = '';

    public string $slug = '';

    /* ---------------- حذف (پورت destroy) ---------------- */

    public ?int $confirmingDelete = null;

    /* نگاشت شناسه دسته => والد فعلی (۰ = سطح ریشه) برای سلکت «انتقال به سطح» */
    public array $parentIds = [];

    /** لیست تخت دسته‌های همین صفحه (نوع + زبان فعلی) — فقط سمت سرور (Collection) */
    protected $flat = [];

    /** کش تعداد محصولات هر شاخه در طول یک ریکوئست */
    protected array $productCountCache = [];

    /* ============================ دسترسی ============================ */

    /**
     * پورت authorizeCategory قدیمی.
     * تفاوت: نوع ناشناخته در قدیمی بدون گیت رد می‌شد (شاخه مرده) — اینجا ۴۰۳.
     */
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

    public function mount(?string $type = 'productcat'): void
    {
        abort_unless(in_array($type, ['productcat', 'postcat'], true), 404);

        $this->type = $type;
        $this->authorizeCategory();
    }

    /* ============================ درخت ============================ */

    /**
     * ساخت درخت با یک کوئری (خروجی نهایی یکسان با
     * whereNull('category_id')->with('childrenCategories') قدیمی — بدون N+1 در عمق ۴ به بعد).
     * سقف عمق = ۱۰ (مثل maxDepth nestable قدیمی).
     */
    protected function buildTree(): array
    {
        $this->flat = Category::detectLang()
            ->where('type', $this->type)
            ->orderBy('ordering')
            ->orderBy('id')
            ->get();

        $byParent = $this->flat->groupBy(fn (Category $category) => (int) $category->category_id);

        $this->parentIds = $this->flat
            ->mapWithKeys(fn (Category $category) => [$category->id => (int) $category->category_id])
            ->all();

        $build = function (int $parentId, int $level = 1) use (&$build, $byParent) {
            if ($level > 10) {
                return [];
            }

            $nodes = [];

            foreach ($byParent->get($parentId, collect()) as $category) {
                $children = $build($category->id, $level + 1);

                $subtreeIds = [$category->id];

                foreach ($children as $child) {
                    $subtreeIds = array_merge($subtreeIds, $child['subtreeIds']);
                }

                // گزینه‌های «انتقال به سطح»: همه دسته‌ها به‌جز خودِ شاخه و زیرشاخه‌هایش (ضد حلقه)
                $parentOptions = $this->flat
                    ->reject(fn (Category $option) => in_array($option->id, $subtreeIds, true))
                    ->values();

                $nodes[] = [
                    'model'         => $category,
                    'children'      => $children,
                    'subtreeIds'    => $subtreeIds,
                    'parentOptions' => $parentOptions,
                ];
            }

            return $nodes;
        };

        return $build(0);
    }

    /**
     * ستون «محصولات» (فقط productcat) — پورت Category::productsCount
     * (تعداد محصولات «کل شاخه»، نه فقط فرزند مستقیم) با کش در طول ریکوئست.
     */
    public function productCount(Category $category): int
    {
        if (! array_key_exists($category->id, $this->productCountCache)) {
            $this->productCountCache[$category->id] = (int) $category->productsCount();
        }

        return $this->productCountCache[$category->id];
    }

    /* ============================ ایجاد سریع ============================ */

    public function startCreate(?int $parentId = null): void
    {
        $this->authorizeCategory();

        $this->resetValidation();
        $this->title = '';
        $this->slug = '';

        if ($parentId) {
            // والد فقط از بین دسته‌های همین نوع معتبر است
            $this->creatingParent = Category::where('type', $this->type)->find($parentId)?->id;
        } else {
            $this->creatingParent = null;
        }

        $this->creating = true;
    }

    public function cancelCreate(): void
    {
        $this->creating = false;
        $this->creatingParent = null;
        $this->title = '';
        $this->slug = '';
        $this->resetValidation();
    }

    /** پورت generate_slug قدیمی (دکمه «ایجاد خودکار») — منبع: عنوان دسته */
    public function generateSlug(): void
    {
        $this->authorizeCategory();

        if (trim($this->title) === '') {
            $this->addError('title', 'برای ساخت خودکار اسلاگ، ابتدا عنوان را وارد کنید.');

            return;
        }

        $this->slug = SlugService::createSlug(Category::class, 'slug', $this->title);
    }

    /**
     * پورت ۱:۱ CategoryController@store
     * (title / lang=app locale / type ثابت صفحه / slug ?: title).
     * تفاوت: category_id برای «افزودن زیرشاخه» و ordering = انتهای همان سطح
     * ست می‌شود (قدیمی همیشه ریشه با ordering=null می‌ساخت).
     */
    public function createCategory(): void
    {
        $this->authorizeCategory();

        try {
            $this->validate([
                'title' => 'required|string',
                'slug'  => 'nullable|string|unique:categories,slug',
            ]);
        } catch (ValidationException $e) {
            $first = collect($e->errors())->flatten()->first();

            $this->dispatch('toast', message: $first ?? 'لطفا خطاهای فرم را برطرف کنید.', type: 'error');

            throw $e;
        }

        $parentId = null;

        if ($this->creatingParent) {
            $parentId = Category::where('type', $this->type)->find($this->creatingParent)?->id;
        }

        // اسلاگ خالی → مثل دکمه «ایجاد خودکار» از عنوان ساخته می‌شود
        $slug = $this->slug !== ''
            ? $this->slug
            : SlugService::createSlug(Category::class, 'slug', $this->title);

        Category::create([
            'title'       => $this->title,
            'lang'        => app()->getLocale(),
            'type'        => $this->type,
            'slug'        => $slug ?: $this->title,
            'category_id' => $parentId,
            'ordering'    => $this->nextOrdering($parentId),
            // published ست نمی‌شود — مثل قدیمی (پیش‌فرض دیتابیس true)
        ]);

        $this->title = '';
        $this->slug = '';
        $this->resetValidation();

        // فرم ریشه مثل قدیمی برای ثبت‌های پشت‌سرهم باز می‌ماند؛ فرم زیرشاخه بسته می‌شود
        if ($parentId) {
            $this->creating = false;
            $this->creatingParent = null;
        }

        $this->dispatch('toast', message: 'دسته بندی با موفقیت ایجاد شد.', type: 'success');
    }

    /* ============================ حذف ============================ */

    public function confirmDelete(int $id): void
    {
        $this->authorizeCategory();

        if (Category::where('type', $this->type)->find($id)) {
            $this->confirmingDelete = $id;
        }
    }

    /**
     * پورت ۱:۱ CategoryController@destroy.
     * نکته (راستی‌آزمایی‌شده): allChildCategories() شناسه «خودِ دسته» را هم در لیست دارد،
     * بنابراین حلقه قدیمی علاوه بر همه زیرشاخه‌ها، خودِ دسته والد را هم حذف می‌کند —
     * یعنی رفتار قدیمی «حذف فقط فرزندان» نبود و همین‌طور عیناً پورت شد.
     */
    public function delete(): void
    {
        $this->authorizeCategory();

        $category = Category::where('type', $this->type)->find($this->confirmingDelete);

        if ($category) {
            foreach (Category::whereIn('id', $category->allChildCategories())->get() as $childCategory) {
                if ($childCategory->image) {
                    Storage::disk('public')->delete($childCategory->image);
                }

                if ($childCategory->background_image) {
                    Storage::disk('public')->delete($childCategory->background_image);
                }

                $childCategory->menus()->detach();
                $childCategory->delete();
            }

            // اگر دسته در حال ایجادِ زیرشاخه حذف شده بود، فرم به حالت ریشه برگردد
            if ($this->creatingParent && ! Category::find($this->creatingParent)) {
                $this->creatingParent = null;
            }

            $this->confirmingDelete = null;

            $this->dispatch('toast', message: 'دسته بندی با موفقیت حذف شد.', type: 'success');
        } else {
            // توست موفقیت فقط وقتی حذف واقعی انجام شد (الگوی اصلاح‌شده فاز ۴)
            $this->confirmingDelete = null;
        }
    }

    /* ============================ مرتب‌سازی (جایگزین درگ‌انددراپ) ============================ */

    public function moveUp(int $id): void
    {
        $this->moveCategory($id, -1);
    }

    public function moveDown(int $id): void
    {
        $this->moveCategory($id, 1);
    }

    /**
     * جایگزین ساده‌شده sort_category قدیمی:
     * خواهرها (هم‌والدها) به ۱..n نرمالایز و جای دسته با همسایه بالا/پایین عوض می‌شود.
     * خروجی نهایی روی category_id + ordering با خروجی پورت nestable یکی است.
     */
    protected function moveCategory(int $id, int $direction): void
    {
        $this->authorizeCategory();

        $category = Category::where('type', $this->type)->find($id);

        if (! $category) {
            return;
        }

        $siblings = Category::where('type', $this->type)
            ->where('category_id', $category->category_id)
            ->orderBy('ordering')
            ->orderBy('id')
            ->pluck('id')
            ->values();

        if ($siblings->count() < 2) {
            return;
        }

        // نرمال‌سازی ترتیب خواهرها (ترتیب‌های null/تکراری قدیمی اصلاح می‌شود)
        foreach ($siblings as $index => $siblingId) {
            Category::whereKey($siblingId)->update(['ordering' => $index + 1]);
        }

        $from = $siblings->search($id);

        if ($from === false) {
            return;
        }

        $to = $from + $direction;

        if ($to < 0 || $to >= $siblings->count()) {
            return; // ابتدا/انتهای لیست — بدون تغییر
        }

        Category::whereKey($siblings[$from])->update(['ordering' => $to + 1]);
        Category::whereKey($siblings[$to])->update(['ordering' => $from + 1]);

        $this->dispatch('toast', message: 'ترتیب دسته‌بندی‌ها ذخیره شد.', type: 'success');
    }

    /* ============================ تغییر والد (جایگزین درگ‌انددراپ) ============================ */

    /** hook تغییر سلکت «انتقال به سطح» هر ردیف */
    public function updatedParentIds($value, $key): void
    {
        $this->changeParent((int) $key);
    }

    public function changeParent(int $id): void
    {
        $this->authorizeCategory();

        $category = Category::where('type', $this->type)->find($id);

        if (! $category) {
            return;
        }

        $target = (int) ($this->parentIds[$id] ?? 0);

        if ($target) {
            $parent = Category::where('type', $this->type)->find($target);

            // ضد حلقه: والد جدید نباید خودِ دسته یا یکی از زیرشاخه‌هایش باشد
            // (allChildCategories شامل خودِ دسته + همه نوادگان است)
            if (! $parent || in_array($parent->id, $category->allChildCategories(), true)) {
                $this->parentIds[$id] = (int) $category->category_id;

                $this->dispatch('toast', message: 'انتقال دسته‌بندی به این سطح مجاز نیست.', type: 'error');

                return;
            }
        }

        $category->category_id = $target ?: null;
        $category->ordering = $this->nextOrdering($target ?: null);
        $category->save();

        $this->dispatch('toast', message: 'جایگاه دسته‌بندی به‌روزرسانی شد.', type: 'success');
    }

    /* ============================ کمکی ============================ */

    /** بزرگ‌ترین ترتیب خواهرها + ۱ (برای افزودن به انتهای سطح) */
    protected function nextOrdering(?int $parentId): int
    {
        return (int) Category::where('type', $this->type)
            ->where('category_id', $parentId)
            ->max('ordering') + 1;
    }

    /* ============================ نمایش ============================ */

    public function render()
    {
        $nodes = $this->buildTree();

        $stats = [
            'total'     => count($this->flat),
            'published' => $this->flat->where('published', true)->count(),
        ];

        $creatingParentTitle = $this->creatingParent
            ? collect($this->flat)->firstWhere('id', $this->creatingParent)?->title
            : null;

        return view('admin-panel::livewire.admin.taxonomy.categories-tree', [
            'nodes'               => $nodes,
            'stats'               => $stats,
            'creatingParentTitle' => $creatingParentTitle,
        ]);
    }
}
