<?php

namespace App\Livewire\Admin\Products;

use App\Models\Category;
use App\Models\Product;
use App\Services\AdminPanel\ProductPersistence;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Morilog\Jalali\Jalalian;

#[Title('لیست محصولات')]
class ProductIndex extends Component
{
    use WithPagination;

    /* ---------------- فیلترها (State در URL) ---------------- */

    #[Url(as: 'title', except: '')]
    public string $title = '';

    #[Url(as: 'stock', except: '')]
    public string $stock = '';

    #[Url(as: 'published', except: '')]
    public string $published = '';

    #[Url(as: 'special', except: '')]
    public string $special = '';

    #[Url(as: 'status', except: '')]
    public string $status = '';

    #[Url(as: 'cats', except: [])]
    public array $categoryIds = [];

    #[Url(as: 'sort', except: '')]
    public string $sortField = '';

    #[Url(as: 'dir', except: '')]
    public string $sortDir = 'desc';

    public int $perPage = 15;

    /* ---------------- انتخاب گروهی ---------------- */

    /** شناسه‌های انتخاب‌شده در صفحه فعلی (چون صفحه با wire:key رندر می‌شود) */
    public array $selected = [];

    public bool $selectAll = false;

    /* ---------------- حذف ---------------- */

    public ?int $confirmingDelete = null;

    public bool $confirmingBulkDelete = false;

    /* ---------------- فیلترها ---------------- */

    /** بازنشانی همه فیلترها */
    public function applyFilters(): void
    {
        $this->title = '';
        $this->stock = '';
        $this->published = '';
        $this->special = '';
        $this->status = '';
        $this->categoryIds = [];
        $this->sortField = '';
        $this->sortDir = 'desc';
        $this->resetPage();
        $this->selected = [];
        $this->selectAll = false;
    }

    public function updating($name, $value)
    {
        if (in_array($name, ['title', 'stock', 'published', 'special', 'status', 'categoryIds', 'sortField', 'sortDir', 'perPage'])) {
            $this->resetPage();
            $this->selected = [];
            $this->selectAll = false;
        }
    }

    /* ---------------- منابع فرم فیلتر ---------------- */

    #[Computed(persist: true, seconds: 300)]
    public function categoryOptions(): array
    {
        return $this->categoryTree();
    }

    protected function categoryTree($parentId = null, $depth = 0): array
    {
        $query = Category::detectLang()->where('type', 'productcat')->orderBy('ordering');

        $query->when($parentId === null, fn ($q) => $q->whereNull('category_id'), fn ($q) => $q->where('category_id', $parentId));

        $result = [];

        foreach ($query->get(['id', 'title', 'category_id']) as $category) {
            $result[] = [
                'id' => $category->id,
                'title' => str_repeat('— ', $depth) . $category->title,
                'depth' => $depth,
            ];

            if ($depth < 5) {
                $result = array_merge($result, $this->categoryTree($category->id, $depth + 1));
            }
        }

        return $result;
    }

    /* ---------------- آمار سربرگ ---------------- */

    #[Computed(persist: true, seconds: 60)]
    public function statistics(): array
    {
        return [
            'total' => Product::detectLang()->count(),
            'published' => Product::detectLang()->where('published', true)->count(),
            'unpublished' => Product::detectLang()->where('published', false)->count(),
        ];
    }

    /* ---------------- کوئری لیست ---------------- */

    protected function baseQuery()
    {
        $query = Product::detectLang()
            ->with(['category:id,title,published,category_id', 'brand:id,name', 'lowestPrice:id,product_id,price,discount_price,regular_price,stock,discount,discount_expire_at'])
            ->withSum('prices', 'stock');

        // فیلترها — مطابق scopeDatatableFilter پنل قدیمی
        if ($this->title !== '') {
            $query->where('title', 'like', '%' . $this->title . '%');
        }

        if ($this->special === 'yes') {
            $query->where('special', true);
        } elseif ($this->special === 'no') {
            $query->where('special', false);
        }

        if ($this->stock === 'available') {
            $query->available();
        } elseif ($this->stock === 'unavailable') {
            $query->unavailable();
        }

        if ($this->published === 'yes') {
            $query->published();
        } elseif ($this->published === 'no') {
            $query->unpublished();
        }

        if ($this->status !== '') {
            $query->where('status', $this->status);
        }

        if ($this->categoryIds) {
            $allCats = $this->categoryIds;

            foreach ($this->categoryIds as $categoryId) {
                $category = Category::find($categoryId);

                if ($category) {
                    $allCats = array_merge($category->allChildCategories(), $allCats);
                }
            }

            $query->whereIn('category_id', array_unique($allCats));
        }

        // مرتب‌سازی
        $field = $this->sortField !== '' ? $this->sortField : 'created_at';
        $dir = in_array(strtolower($this->sortDir), ['asc', 'desc']) ? $this->sortDir : 'desc';

        if ($field === 'stock') {
            $query->orderBy('prices_sum_stock', $dir);
        } elseif ($field === 'title') {
            $query->orderBy('title', $dir);
        } else {
            $query->orderBy($field, $dir);
        }

        return $query;
    }

    #[Computed]
    public function products()
    {
        return $this->baseQuery()->paginate($this->perPage);
    }

    /* ---------------- مرتب‌سازی ---------------- */

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDir = $this->sortDir === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDir = 'desc';
        }
    }

    /* ---------------- انتخاب گروهی ---------------- */

    public function updatedSelectAll($value): void
    {
        $this->selected = $value
            ? $this->products->getCollection()->pluck('id')->map(fn ($id) => (string) $id)->toArray()
            : [];
    }

    /* ---------------- حذف ---------------- */

    public function confirmDelete(int $id): void
    {
        $this->authorizeProduct('products.delete');
        $this->confirmingDelete = $id;
    }

    public function delete(): void
    {
        $this->authorizeProduct('products.delete');

        $product = Product::find($this->confirmingDelete);

        if ($product) {
            app(ProductPersistence::class)->destroy($product);
        }

        $this->confirmingDelete = null;
        $this->selected = [];
        $this->selectAll = false;

        $this->dispatch('toast', message: 'محصول با موفقیت حذف شد.', type: 'success');
    }

    public function bulkDelete(): void
    {
        $this->authorizeProduct('products.delete');

        $service = app(ProductPersistence::class);

        foreach (Product::whereIn('id', $this->selected)->get() as $product) {
            $service->destroy($product);
        }

        $this->confirmingBulkDelete = false;
        $this->selected = [];
        $this->selectAll = false;

        $this->dispatch('toast', message: 'محصولات انتخاب‌شده حذف شدند.', type: 'success');
    }

    protected function authorizeProduct(string $permission): void
    {
        if (! auth('adminPanel')->user()?->can($permission)) {
            abort(403);
        }
    }

    /* ---------------- نمایش ---------------- */

    public static function jdate($date, $format = 'Y/m/d'): string
    {
        if (! $date) {
            return '—';
        }

        return Jalalian::fromDateTime($date)->format($format);
    }

    public function render()
    {
        return view('admin-panel::livewire.admin.products.index')
            ->layout('admin-panel::layout');
    }
}
