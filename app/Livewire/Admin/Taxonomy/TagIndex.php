<?php

namespace App\Livewire\Admin\Taxonomy;

use App\Models\Tag;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Morilog\Jalali\Jalalian;

/**
 * لیست تگ‌ها — پورت TagController@index/store/update/destroy/multipleDestroy/details/export
 * دسترسی: tags.index / tags.create / tags.update / tags.delete / tags.show
 */
#[Title('تگ‌ها')]
class TagIndex extends Component
{
    use WithPagination;

    public int $perPage = 20;

    /* ---------------- فیلتر جستجو ---------------- */

    #[Url(as: 'search', except: '')]
    public string $search = '';

    /* ---------------- مودال ایجاد / ویرایش (پورت createTagModal/editTagModal) ---------------- */

    public bool $showCreateModal = false;

    public string $name = '';

    public ?int $editingTag = null;

    public string $editingName = '';

    /* ---------------- مودال جزئیات (پورت detailsModal) ---------------- */

    public bool $showingDetails = false;

    public array $detailTag = [];

    public int $detailTotalUsage = 0;

    public array $detailUsage = [];

    public array $detailItems = [];

    /** برچسب فارسی انواع مورف */
    public const TYPE_LABELS = [
        'Product' => 'محصولات',
        'Post'    => 'نوشته‌ها',
        'Page'    => 'صفحه‌ها',
    ];

    /* ---------------- انتخاب گروهی و حذف ---------------- */

    public array $selected = [];

    public bool $selectAll = false;

    public ?int $confirmingDelete = null;

    public bool $confirmingBulkDelete = false;

    public function mount(): void
    {
        auth('adminPanel')->user()?->can('tags.index') ?: abort(403);
    }

    public function updating($name, $value): void
    {
        if (in_array($name, ['search', 'perPage'])) {
            $this->resetPage();
            $this->selected = [];
            $this->selectAll = false;
        }
    }

    public function applyFilters(): void
    {
        $this->search = '';
        $this->resetPage();
        $this->selected = [];
        $this->selectAll = false;
    }

    /* ---------------- لیست ---------------- */

    #[Computed]
    public function tags()
    {
        return Tag::query()
            ->when($this->search !== '', fn ($query) => $query->search($this->search))
            ->withCount('taggables')
            ->orderBy('created_at', 'desc')
            ->paginate($this->perPage);
    }

    /* ---------------- آمار سربرگ (پورت index statistics) ---------------- */

    #[Computed(persist: true, seconds: 60)]
    public function statistics(): array
    {
        return [
            'total'       => Tag::count(),
            'total_views' => (int) Tag::sum('view_count'),
            'most_used'   => Tag::withCount('taggables')->orderBy('taggables_count', 'desc')->first(),
            'most_viewed' => Tag::orderBy('view_count', 'desc')->first(),
        ];
    }

    /* ---------------- ایجاد (پورت store) ---------------- */

    public function store(): void
    {
        auth('adminPanel')->user()?->can('tags.create') ?: abort(403);

        $this->validate([
            'name' => ['required', 'string', 'max:255', 'unique:tags,name'],
        ], [
            'name.required' => 'عنوان تگ الزامی است.',
            'name.unique'   => 'این عنوان قبلاً ثبت شده است.',
        ]);

        // پورت store قدیمی؛ اسلاگ با هلپر فارسی ساخته می‌شود (فاز ۲۵)
        Tag::create([
            'name' => $this->name,
            'slug' => sluggable_helper_function($this->name),
            'lang' => app()->getLocale(),
        ]);

        $this->reset('name', 'showCreateModal');

        $this->dispatch('toast', message: 'تگ با موفقیت ایجاد شد.', type: 'success');
    }

    /* ---------------- ویرایش (پورت update) ---------------- */

    public function editTag(int $id): void
    {
        auth('adminPanel')->user()?->can('tags.update') ?: abort(403);

        if (! $tag = Tag::find($id)) {
            return;
        }

        $this->editingTag = $tag->id;
        $this->editingName = (string) $tag->name;
    }

    public function update(): void
    {
        auth('adminPanel')->user()?->can('tags.update') ?: abort(403);

        if (! $this->editingTag || ! $tag = Tag::find($this->editingTag)) {
            $this->dispatch('toast', message: 'تگ یافت نشد.', type: 'error');

            return;
        }

        $this->validate([
            'editingName' => ['required', 'string', 'max:255', 'unique:tags,name,' . $tag->id],
        ], [
            'editingName.required' => 'عنوان تگ الزامی است.',
            'editingName.unique'   => 'این عنوان قبلاً ثبت شده است.',
        ]);

        $tag->update([
            'name' => $this->editingName,
            'slug' => sluggable_helper_function($this->editingName),
        ]);

        $this->reset('editingTag', 'editingName');

        $this->dispatch('toast', message: 'تگ با موفقیت به‌روزرسانی شد.', type: 'success');
    }

    /* ---------------- جزئیات (پورت details) ---------------- */

    public function openDetails(int $id): void
    {
        auth('adminPanel')->user()?->can('tags.show') ?: abort(403);

        if (! $tag = Tag::find($id)) {
            $this->dispatch('toast', message: 'تگ یافت نشد.', type: 'error');

            return;
        }

        $taggables = $tag->taggables()->with('taggable')->get()->groupBy('taggable_type');

        $this->detailTag = [
            'id'         => $tag->id,
            'name'       => (string) $tag->name,
            'slug'       => (string) $tag->slug,
            'view_count' => (int) $tag->view_count,
            'created_at' => $this->jdate($tag->created_at, 'Y/m/d | H:i'),
        ];

        $this->detailTotalUsage = $tag->taggables()->count();
        $this->detailUsage = $tag->getUsageDetails();
        $this->detailItems = $taggables
            ->map(function ($items, $type) {
                $base = class_basename($type);

                return [
                    'type'    => $base,
                    'type_fa' => self::TYPE_LABELS[$base] ?? $base,
                    'items'   => $items->map(fn ($item) => [
                        'id'    => $item->taggable_id,
                        'title' => $item->taggable?->title ?? $item->taggable?->name ?? '',
                    ])->values()->all(),
                ];
            })
            ->values()
            ->all();

        $this->showingDetails = true;
    }

    /* ---------------- خروجی CSV (روت قدیمی np.tags.export) ---------------- */

    public function export(): void
    {
        $this->dispatch('open-url', url: route('np.tags.export'));
    }

    /* ---------------- انتخاب گروهی ---------------- */

    public function updatedSelectAll($value): void
    {
        $this->selected = $value
            ? $this->tags->getCollection()->pluck('id')->map(fn ($id) => (string) $id)->toArray()
            : [];
    }

    /* ---------------- حذف (پورت destroy/multipleDestroy) ---------------- */

    public function confirmDelete(int $id): void
    {
        auth('adminPanel')->user()?->can('tags.delete') ?: abort(403);

        $this->confirmingDelete = $id;
    }

    public function delete(): void
    {
        auth('adminPanel')->user()?->can('tags.delete') ?: abort(403);

        if ($tag = Tag::find($this->confirmingDelete)) {
            $tag->delete(); // taggables با cascade دیتابیس پاک می‌شود (در تست هم با پاک‌سازی دستی امن است)
        }

        $this->confirmingDelete = null;
        $this->selected = [];
        $this->selectAll = false;

        $this->dispatch('toast', message: 'تگ حذف شد.', type: 'success');
    }

    public function bulkDelete(): void
    {
        auth('adminPanel')->user()?->can('tags.delete') ?: abort(403);

        foreach (Tag::whereIn('id', $this->selected)->get() as $tag) {
            $tag->taggables()->delete();
            $tag->delete();
        }

        $this->confirmingBulkDelete = false;
        $this->selected = [];
        $this->selectAll = false;

        $this->dispatch('toast', message: 'تگ‌های انتخاب‌شده حذف شدند.', type: 'success');
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
        return view('admin-panel::livewire.admin.taxonomy.tags')
            ->layout('admin-panel::layout');
    }
}
