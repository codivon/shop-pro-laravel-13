<?php

namespace App\Livewire\Admin\Menus;

use App\Models\Category;
use App\Models\Menu;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Illuminate\Validation\ValidationException;

/**
 * مدیریت منوها (صفحه تک‌صفحه‌ای) — پورت ۱:۱ MenuController
 * (index/store/update/show/destroy/sort).
 *
 * انحرافات آگاهانه (مستند در worklog):
 * - درگ‌انددراپ nestable قدیمی ابتدا با دکمه‌های ↑↓ (جابه‌جایی ordering بین خواهرها با تراکنش)
 *   و «select والد» در فرم جایگزین شد — همان نتیجه نهایی روی menu_id + ordering.
 * - فاز ۱۲-د: درگ‌انددراپ واقعی هم اضافه شد (هم‌الگوی درخت دسته‌بندی‌ها؛ اکشن
 *   moveNode قبل/بعد/داخل + moveNodeToRoot با گارد ضد حلقه)؛ دکمه‌های ↑↓ و
 *   «select والد» فرم به‌عنوان جایگزین لمسی/در دسترس حفظ شده‌اند.
 * - قدیمی فرم والد نداشت (زیرمنو فقط با درگ ساخته می‌شد) → فیلد والد اختیاری افزودنی است.
 *
 * کلیدهای دسترسی: صفحه=menus (کلید اصلی منو؛ menus.index هم پذیرفته می‌شود مثل Policy قدیمی)،
 * ایجاد=menus.create، ویرایش=menus.update، حذف=menus.delete.
 */
#[Layout('admin-panel::layout')]
#[Title('مدیریت منوها')]
class MenuManager extends Component
{
    /* ---------------- فرم (آفلاین در صفحه — پورت مودال‌های create/edit قدیمی) ---------------- */

    public bool $formOpen = false;

    public ?int $editingId = null;

    /** نوع منو: category / static / normal / megamenu */
    public string $formType = 'normal';

    /* فیلدهای مشترک normal/megamenu/static */
    public string $formTitle = '';

    public ?string $formIcon = null;

    /* فقط normal/megamenu */
    public ?string $formLink = null;

    /* فقط category */
    public string $formCategoryTitle = '';

    public ?string $formCategoryIcon = null;

    /** @var int|string|null شناسه دسته انتخابی (انتخابگر درختی int/null ست می‌کند) */
    public $formCategoryId = '';

    public bool $formChildren = true;

    /* فقط static — کلیدهای دقیق از config('general.static_menus') */
    public string $formStatic = '';

    /** والد اختیاری (جایگزین درگ‌انددراپ؛ null = منوی اصلی) */
    public ?int $formParentId = null;

    /* ---------------- حذف ---------------- */

    public ?int $confirmingDelete = null;

    /* ============================ دسترسی ============================ */

    protected function authorizeView(): void
    {
        $user = auth('adminPanel')->user();

        // کلید اصلی منوی پنل قدیمی «menus» است؛ Policy قدیمی هم viewAny را با menus.index می‌بست
        ($user?->can('menus') || $user?->can('menus.index')) ?: abort(403);
    }

    protected function authorizeCreate(): void
    {
        auth('adminPanel')->user()?->can('menus.create') ?: abort(403);
    }

    protected function authorizeUpdate(): void
    {
        auth('adminPanel')->user()?->can('menus.update') ?: abort(403);
    }

    protected function authorizeDelete(): void
    {
        auth('adminPanel')->user()?->can('menus.delete') ?: abort(403);
    }

    public function mount(): void
    {
        $this->authorizeView();
    }

    /* ============================ درخت (پورت index قدیمی) ============================ */

    /**
     * ساخت درخت با یک کوئری (خروجی یکسان با
     * whereNull('menu_id')->with('childrenMenus')->orderBy('ordering') قدیمی — بدون N+1).
     * سقف عمق = ۱۰ (مثل maxDepth nestable قدیمی).
     */
    #[Computed]
    public function nodes(): array
    {
        $flat = Menu::query()
            ->detectLang()
            ->with('category')
            ->orderBy('ordering')
            ->orderBy('id')
            ->get();

        $byParent = $flat->groupBy(fn (Menu $menu) => (int) $menu->menu_id);

        $build = function (int $parentId, int $level = 1) use (&$build, $byParent) {
            if ($level > 10) {
                return [];
            }

            $nodes = [];

            foreach ($byParent->get($parentId, collect()) as $menu) {
                $children = $build($menu->id, $level + 1);

                // شناسه‌های زیردرخت (خود گره + همه نوادگان) — گارد سمت کلاینت درگ‌انددراپ
                $subtreeIds = [$menu->id];

                foreach ($children as $child) {
                    $subtreeIds = array_merge($subtreeIds, $child['subtreeIds']);
                }

                $nodes[] = [
                    'model'      => $menu,
                    'children'   => $children,
                    'subtreeIds' => $subtreeIds,
                ];
            }

            return $nodes;
        };

        return $build(0);
    }

    #[Computed(persist: true, seconds: 60)]
    public function totalCount(): int
    {
        return Menu::detectLang()->count();
    }

    /** دسته‌ها برای select نوع category (پورت index قدیمی — گروه‌بندی بر اساس type) */
    #[Computed]
    public function categories()
    {
        return Category::query()
            ->detectLang()
            ->orderBy('ordering')
            ->get()
            ->groupBy('type');
    }

    /**
     * درخت تو در توی دسته‌ها برای انتخابگر درختی فرم (فاز ۱۲-د) —
     * همه گروه‌های نوع (محصولات/وبلاگ/...) در یک درخت ادغام می‌شوند و
     * عنوان‌ها همان عنوان ساده دسته است (مسیر کامل خودش را درخت نشان می‌دهد).
     */
    #[Computed]
    public function menuCategoryTree(): array
    {
        $flat = $this->categories->flatMap(fn ($group) => $group->values());

        // ریشه‌ها = دسته‌هایی که category_id آن‌ها null یا صفر است
        $byParent = $flat->groupBy(fn ($cat) => (int) $cat->category_id ?: 0);

        $build = function (int $parentId) use (&$build, $byParent): array {
            return $byParent->get($parentId, collect())
                ->map(fn ($cat) => [
                    'id'       => (int) $cat->id,
                    'title'    => (string) $cat->title,
                    'children' => $build((int) $cat->id),
                ])
                ->values()
                ->toArray();
        };

        return $build(0);
    }

    /** گزینه‌های «منوی والد» فرم — همه منوها به‌جز خود منو و زیرمجموعه‌هایش (ضد حلقه) */
    #[Computed]
    public function parentOptions()
    {
        $excluded = [];

        if ($this->editingId && ($menu = Menu::find($this->editingId))) {
            $excluded = array_merge([$menu->id], $this->descendantIds($menu));
        }

        return Menu::query()
            ->detectLang()
            ->orderBy('title')
            ->get()
            ->reject(fn (Menu $menu) => in_array($menu->id, $excluded, true))
            ->values();
    }

    /* ============================ فرم: ایجاد / ویرایش ============================ */

    public function startCreate(?int $parentId = null): void
    {
        $this->authorizeCreate();

        $this->resetForm();

        // ایجاد زیرمنو از دکمه هر گره (جایگزین کشیدن منو به داخل منوی دیگر)
        if ($parentId) {
            $this->formParentId = Menu::detectLang()->find($parentId)?->id;
        }

        $this->formOpen = true;
    }

    /** پورت show + پیش‌بارگذاری مودال edit قدیمی */
    public function editMenu(int $id): void
    {
        $this->authorizeUpdate();

        $menu = Menu::detectLang()->find($id);

        if (! $menu) {
            return;
        }

        $this->resetForm();
        $this->resetValidation();

        $this->editingId = $menu->id;
        $this->formParentId = $menu->menu_id;
        $this->formType = (string) $menu->type;

        switch ($menu->type) {
            case 'category':
                $this->formCategoryTitle = (string) $menu->title;
                $this->formCategoryIcon = $menu->icon;
                $this->formCategoryId = $menu->menuable_id;
                $this->formChildren = (bool) $menu->children;
                break;

            case 'static':
                $this->formTitle = (string) $menu->title;
                $this->formIcon = $menu->icon;
                $this->formStatic = (string) $menu->static_type;
                break;

            default: // normal / megamenu
                $this->formTitle = (string) $menu->title;
                $this->formIcon = $menu->icon;
                $this->formLink = $menu->link;
                break;
        }

        $this->formOpen = true;
    }

    public function cancelForm(): void
    {
        $this->resetForm();
        $this->resetValidation();
    }

    protected function resetForm(): void
    {
        $this->editingId = null;
        $this->formType = 'normal';
        $this->formTitle = '';
        $this->formIcon = null;
        $this->formLink = null;
        $this->formCategoryTitle = '';
        $this->formCategoryIcon = null;
        $this->formCategoryId = '';
        $this->formChildren = true;
        $this->formStatic = '';
        $this->formParentId = null;
        $this->formOpen = false;
    }

    /** دکمه ذخیره فرم → store/update */
    public function save(): void
    {
        if ($this->editingId) {
            $this->updateMenu();
        } else {
            $this->storeMenu();
        }
    }

    /* ============================ ذخیره (پورت ۱:۱ switch store) ============================ */

    public function storeMenu(): void
    {
        $this->authorizeCreate();

        try {
            $this->validate([
                'formType' => 'required|in:category,static,normal,megamenu',
            ]);

            switch ($this->formType) {
                case 'category':
                    $this->validate([
                        'formCategoryId' => 'required|exists:categories,id',
                    ]);
                    break;

                case 'static':
                    $this->validate([
                        'formTitle'  => 'required',
                        'formStatic' => 'required',
                    ]);
                    break;

                default: // normal / megamenu
                    $this->validate([
                        'formTitle' => 'required',
                        // لینک در قدیمی اختیاری بود (validate شده کامنت شد) — عیناً حفظ شد
                    ]);
                    break;
            }
        } catch (ValidationException $e) {
            $first = collect($e->errors())->flatten()->first();

            $this->dispatch('toast', message: $first ?? 'لطفا خطاهای فرم را برطرف کنید.', type: 'error');

            throw $e;
        }

        $ordering = Menu::max('ordering') + 1;

        $attributes = [
            'ordering' => $ordering,
            'menu_id'  => $this->validatedParentId(), // افزودنی: جایگزین درگ‌انددراپ
            'lang'     => app()->getLocale(),
        ];

        switch ($this->formType) {
            case 'category':
                $attributes += [
                    'title'       => $this->formCategoryTitle,
                    'menuable_id' => $this->formCategoryId,
                    'type'        => 'category',
                    'children'    => $this->formChildren ? true : false,
                    'icon'        => $this->formCategoryIcon,
                ];
                break;

            case 'static':
                $attributes += [
                    'title'       => $this->formTitle,
                    'static_type' => $this->formStatic,
                    'type'        => 'static',
                    'icon'        => $this->formIcon,
                ];
                break;

            default: // normal / megamenu
                $attributes += [
                    'title' => $this->formTitle,
                    'link'  => $this->formLink,
                    'type'  => $this->formType,
                    'icon'  => $this->formIcon,
                ];
                break;
        }

        $menu = Menu::create($attributes);

        $this->resetForm();

        $this->dispatch('toast', message: 'منوی «' . $menu->full_title . '» ایجاد شد.', type: 'success');
    }

    /* ============================ بروزرسانی (پورت ۱:۱ switch update) ============================ */

    public function updateMenu(): void
    {
        $this->authorizeUpdate();

        $menu = Menu::detectLang()->find($this->editingId);

        if (! $menu) {
            return;
        }

        try {
            $this->validate([
                'formType' => 'required|in:category,static,normal,megamenu',
            ]);

            switch ($this->formType) {
                case 'category':
                    $this->validate([
                        'formCategoryId' => 'required|exists:categories,id',
                    ]);
                    break;

                case 'static':
                    $this->validate([
                        'formTitle'  => 'required',
                        'formStatic' => 'required',
                    ]);
                    break;

                default: // normal / megamenu
                    $this->validate([
                        'formTitle' => 'required',
                    ]);
                    break;
            }
        } catch (ValidationException $e) {
            $first = collect($e->errors())->flatten()->first();

            $this->dispatch('toast', message: $first ?? 'لطفا خطاهای فرم را برطرف کنید.', type: 'error');

            throw $e;
        }

        $type = $this->formType;

        switch ($type) {
            case 'category':
                $menu->update([
                    'title'       => $this->formCategoryTitle,
                    'menuable_id' => $this->formCategoryId,
                    'link'        => null,
                    'type'        => $type,
                    'children'    => $this->formChildren ? true : false,
                    'icon'        => $this->formCategoryIcon,
                ]);
                break;

            case 'static':
                // مثل قدیمی: link/menuable_id دست نخورده می‌مانند (رفتار ۱:۱)
                $menu->update([
                    'title'       => $this->formTitle,
                    'static_type' => $this->formStatic,
                    'type'        => $type,
                    'icon'        => $this->formIcon,
                ]);
                break;

            default: // normal / megamenu
                $menu->update([
                    'title'       => $this->formTitle,
                    'link'        => $this->formLink,
                    'menuable_id' => null,
                    'type'        => $type,
                    'icon'        => $this->formIcon,
                ]);
                break;
        }

        // افزودنی (جایگزین درگ‌انددراپ): تغییر جایگاه در درخت با select والد
        $menu->update(['menu_id' => $this->validatedParentId($menu)]);

        $this->resetForm();

        $this->dispatch('toast', message: 'منوی «' . $menu->full_title . '» ویرایش شد.', type: 'success');
    }

    /**
     * اعتبارسنجی والد انتخابی: باید منوی همان زبان و غیرخودش باشد.
     * ضد حلقه: والدِ جدید نباید از زیرمجموعه‌های خود منو باشد (وقتی در حال ویرایش است).
     */
    protected function validatedParentId(?Menu $self = null): ?int
    {
        if (! $this->formParentId) {
            return null;
        }

        if ($self && $this->formParentId === $self->id) {
            return $self->menu_id;
        }

        $parent = Menu::detectLang()->find($this->formParentId);

        if (! $parent) {
            return null;
        }

        if ($self) {
            $descendants = $this->descendantIds($self);

            if (in_array($parent->id, $descendants, true)) {
                $this->dispatch('toast', message: 'انتخاب این والد مجاز نیست؛ منو نمی‌تواند زیرمجموعه خودش باشد.', type: 'error');

                return $self->menu_id;
            }
        }

        return $parent->id;
    }

    /* ============================ حذف (پورت destroy + برابری cascade) ============================ */

    public function confirmDelete(int $id): void
    {
        $this->authorizeDelete();

        if (Menu::detectLang()->find($id)) {
            $this->confirmingDelete = $id;
        }
    }

    /**
     * قدیمی فقط $menu->delete() صدا می‌زد (MySQL: FK cascade حذف زیرمنوها).
     * برای قابل‌حمل بودن (SQLite بدون FK) زیرمجموعه‌ها صریح حذف می‌شوند — نتیجه یکسان.
     */
    public function delete(): void
    {
        $this->authorizeDelete();

        $menu = Menu::find($this->confirmingDelete);

        if ($menu) {
            $ids = $menu->id ? array_merge([$menu->id], $this->descendantIds($menu)) : [];

            DB::transaction(function () use ($ids) {
                Menu::whereIn('id', $ids)->delete();
            });
        }

        $this->confirmingDelete = null;

        $this->dispatch('toast', message: 'منو با موفقیت حذف شد.', type: 'success');
    }

    /** شناسه‌های همه زیرمجموعه‌های یک منو (برای حذف و گارد ضد حلقه) */
    protected function descendantIds(Menu $menu): array
    {
        $ids = [];

        $collect = function (int $parentId) use (&$collect, &$ids) {
            foreach (Menu::where('menu_id', $parentId)->pluck('id') as $childId) {
                $ids[] = (int) $childId;
                $collect((int) $childId);
            }
        };

        $collect($menu->id);

        return $ids;
    }

    /* ============================ مرتب‌سازی (جایگزین درگ‌انددراپ sort) ============================ */

    public function moveUp(int $id): void
    {
        $this->moveMenu($id, -1);
    }

    public function moveDown(int $id): void
    {
        $this->moveMenu($id, 1);
    }

    /**
     * جایگزین ساده‌شده sort_category قدیمی: خواهرها (هم‌والدها) به ۱..n نرمالایز و
     * جای منو با همسایه بالا/پایین عوض می‌شود (با تراکنش).
     * خروجی نهایی روی menu_id + ordering با خروجی پورت nestable یکی است.
     */
    protected function moveMenu(int $id, int $direction): void
    {
        $this->authorizeUpdate();

        $menu = Menu::detectLang()->find($id);

        if (! $menu) {
            return;
        }

        DB::transaction(function () use ($menu, $direction) {
            $menuId = $menu->id;

            $siblings = Menu::detectLang()
                ->where('menu_id', $menu->menu_id)
                ->orderBy('ordering')
                ->orderBy('id')
                ->pluck('id')
                ->values();

            if ($siblings->count() < 2) {
                return;
            }

            // نرمال‌سازی ترتیب خواهرها (ترتیب‌های null/تکراری قدیمی اصلاح می‌شود)
            foreach ($siblings as $index => $siblingId) {
                Menu::whereKey($siblingId)->update(['ordering' => $index + 1]);
            }

            $from = $siblings->search($menuId);

            if ($from === false) {
                return;
            }

            $to = $from + $direction;

            if ($to < 0 || $to >= $siblings->count()) {
                return; // ابتدا/انتهای لیست — بدون تغییر
            }

            Menu::whereKey($siblings[$from])->update(['ordering' => $to + 1]);
            Menu::whereKey($siblings[$to])->update(['ordering' => $from + 1]);
        });

        $this->dispatch('toast', message: 'ترتیب منوها ذخیره شد.', type: 'success');
    }

    /* ============================ درگ‌انددراپ (فاز ۱۲-د — پورت نهایی sort قدیمی) ============================ */

    /**
     * جایگزین نهایی jquery.nestable قدیمی با مدل قبل/بعد/داخل (هم‌الگوی CategoryTree):
     *
     *   - before/after: گره به خانوادهٔ والدِ هدف منتقل و درجستِ قبل/بعد از هدف
     *     قرار می‌گیرد؛ خواهرها (با گره) به ۱..n نرمالایز می‌شوند — ترتیب نسبی
     *     (ordering, id) حفظ می‌شود و ترتیب‌های تکراری/گپ‌دار داده قدیمی اصلاح می‌شود.
     *   - inside: menu_id = هدف و ordering = nextOrdering(هدف) (انتهای صف فرزندان).
     *   - فرزندان گره با والدشان جابه‌جا می‌شوند (menu_id آن‌ها دست نمی‌خورد) و برای
     *     جلوگیری از حلقه، هدف نباید داخل زیردرخت گره باشد (descendantIds شامل
     *     همه نوادگان است — همان گارد validatedParentId).
     */
    public function moveNode(int $dragId, int $targetId, string $mode): void
    {
        $this->authorizeUpdate();

        if (! in_array($mode, ['before', 'after', 'inside'], true)) {
            return;
        }

        $drag = Menu::detectLang()->find($dragId);
        $target = Menu::detectLang()->find($targetId);

        if (! $drag || ! $target || $drag->id === $target->id) {
            return;
        }

        // ضد حلقه: هدف نباید یکی از زیرشاخه‌های خودِ گره باشد
        if (in_array($target->id, $this->descendantIds($drag), true)) {
            $this->dispatch('toast', message: 'انتقال منو به داخل زیرمجموعهٔ خودش مجاز نیست.', type: 'error');

            return;
        }

        $oldParentId = $drag->menu_id !== null ? (int) $drag->menu_id : null;
        $newParentId = null;

        DB::transaction(function () use ($drag, $target, $mode, $oldParentId) {
            if ($mode === 'inside') {
                $newParentId = (int) $target->id;

                $drag->menu_id = $newParentId;
                $drag->ordering = $this->nextOrdering($newParentId); // انتهای صف فرزندان هدف
                $drag->save();

                $this->renumberSiblings($newParentId);
            } else {
                $newParentId = $target->menu_id !== null ? (int) $target->menu_id : null;

                $drag->menu_id = $newParentId;
                $drag->save();

                // خواهرهای والدِ هدف بدون خودِ گره — با ترتیب استاندارد صفحه
                $siblings = Menu::detectLang()
                    ->where('menu_id', $target->menu_id)
                    ->where('id', '!=', $drag->id)
                    ->orderBy('ordering')
                    ->orderBy('id')
                    ->pluck('id')
                    ->values();

                $position = $siblings->search($target->id);

                if ($position === false) {
                    $position = $siblings->count();
                }

                // درج گره قبل/بعد از هدف (جابه‌جایی هم‌سطح) و نرمال‌سازی ۱..n
                $siblings->splice($mode === 'before' ? $position : $position + 1, 0, [$drag->id]);

                foreach ($siblings as $index => $siblingId) {
                    Menu::whereKey($siblingId)->update(['ordering' => $index + 1]);
                }
            }

            // پاک‌سازی گپ والد قبلی بعد از خروج گره (حفظ ترتیب نسبی)
            if ($oldParentId !== $newParentId) {
                $this->renumberSiblings($oldParentId);
            }
        });

        $this->dispatch('toast', message: 'ساختار منوها به‌روزرسانی شد.', type: 'success');
    }

    /**
     * فاز ۱۲-د — خارج کردن منو از زیرمجموعه با درگ (رها کردن روی ناحیه «سطح ریشه»):
     * menu_id=null + انتهای صف ریشه، مثل خروجی فرم با والدِ «منوی اصلی».
     */
    public function moveNodeToRoot(int $dragId): void
    {
        $this->authorizeUpdate();

        $drag = Menu::detectLang()->find($dragId);

        if (! $drag || $drag->menu_id === null) {
            return; // از قبل ریشه است
        }

        $oldParentId = (int) $drag->menu_id;

        DB::transaction(function () use ($drag, $oldParentId) {
            $drag->menu_id = null;
            $drag->ordering = $this->nextOrdering(null); // انتهای صف ریشه
            $drag->save();

            $this->renumberSiblings($oldParentId); // پاک‌سازی گپ والد قبلی
            $this->renumberSiblings(null);         // نرمال‌سازی ترتیب ریشه‌ها
        });

        $this->dispatch('toast', message: 'منو به سطح ریشه منتقل شد.', type: 'success');
    }

    /**
     * فاز ۱۳ — ذخیره کل ساختار درخت از درگ‌انددراپ SortableJS:
     * آرایه تو در تو [{id, children:[…]}, …] — والد و ترتیب هر منو از جایگاهش در آرایه.
     * چون ساختار از DOM رندرشده می‌آید، حلقه به‌طور ساختاری ناممکن است؛ با این حال
     * همه شناسه‌ها اعتبارسنجی می‌شوند (باید منوهای همین محدوده زبان باشند).
     */
    public function saveTree($tree = null): void
    {
        $this->authorizeUpdate();

        $nodes = is_string($tree) ? json_decode($tree, true) : $tree;

        if (! is_array($nodes)) {
            return;
        }

        // گردآوری همه شناسه‌ها برای اعتبارسنجی
        $ids = [];

        $collect = function (array $items) use (&$collect, &$ids): void {
            foreach ($items as $item) {
                if (! is_array($item)) {
                    continue;
                }

                $id = (int) ($item['id'] ?? 0);

                if ($id > 0) {
                    $ids[] = $id;
                }

                if (! empty($item['children']) && is_array($item['children'])) {
                    $collect($item['children']);
                }
            }
        };

        $collect($nodes);

        if (empty($ids)) {
            return; // ساختار خالی (مثلاً درگ بی‌تغییر) — کاری نکن
        }

        $validIds = Menu::detectLang()
            ->whereIn('id', $ids)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if (count($ids) !== count($validIds)) {
            $this->dispatch('toast', message: 'ساختار ارسال‌شده نامعتبر است.', type: 'error');

            return;
        }

        DB::transaction(function () use ($nodes) {
            $apply = function (array $items, ?int $parentId) use (&$apply): void {
                foreach ($items as $index => $item) {
                    if (! is_array($item) || empty($item['id'])) {
                        continue;
                    }

                    Menu::whereKey((int) $item['id'])->update([
                        'menu_id'  => $parentId,
                        'ordering' => $index + 1,
                    ]);

                    if (! empty($item['children']) && is_array($item['children'])) {
                        $apply($item['children'], (int) $item['id']);
                    }
                }
            };

            $apply($nodes, null);
        });

        $this->dispatch('toast', message: 'ساختار منوها به‌روزرسانی شد.', type: 'success');
    }

    /* ============================ کمکی ============================ */

    /** بزرگ‌ترین ترتیب خواهرها + ۱ (برای افزودن به انتهای سطح) */
    protected function nextOrdering(?int $parentId): int
    {
        return (int) Menu::detectLang()
            ->where('menu_id', $parentId)
            ->max('ordering') + 1;
    }

    /**
     * نرمال‌سازی ترتیب خواهرها به ۱..n با حفظ ترتیب نسبی فعلی (ordering, id) —
     * ترتیب‌های null/تکراری/گپ‌دار داده قدیمی اصلاح می‌شود و رندر تغییر نمی‌کند.
     */
    protected function renumberSiblings(?int $parentId): void
    {
        $ids = Menu::detectLang()
            ->where('menu_id', $parentId)
            ->orderBy('ordering')
            ->orderBy('id')
            ->pluck('id')
            ->values();

        foreach ($ids as $index => $id) {
            Menu::whereKey($id)->update(['ordering' => $index + 1]);
        }
    }

    /* ============================ برچسب‌ها (مثل ویوهای قدیمی) ============================ */

    public static function typeLabel(string $type): string
    {
        return match ($type) {
            'category' => 'منوی دسته بندی',
            'static'   => 'منوی ثابت',
            'megamenu' => 'مگامنو',
            default    => 'منوی معمولی',
        };
    }

    /**
     * عنوان نمایشی گره درخت (پورت full_title).
     * رفع خطای احتمالی قدیمی: اگر دسته‌بندیِ یک منوی نوع category حذف شده باشد،
     * Menu::getFullTitleAttribute روی null فراخوانی می‌شود (بازتاب باگ قدیمی) — اینجا نال‌ایمن.
     */
    public function displayTitle(Menu $menu): string
    {
        if ($menu->type === 'category') {
            if ($menu->category?->full_title) {
                return $menu->category->full_title . ' ( دسته بندی )';
            }

            return $menu->title ?: 'منوی دسته‌بندی بدون عنوان';
        }

        return (string) $menu->full_title;
    }

    /* ============================ نمایش ============================ */

    public function render()
    {
        return view('admin-panel::livewire.admin.menus.menus');
    }
}
