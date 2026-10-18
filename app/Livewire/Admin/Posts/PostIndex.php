<?php

namespace App\Livewire\Admin\Posts;

use App\Models\Post;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Morilog\Jalali\Jalalian;

/**
 * لیست نوشته‌های وبلاگ — پورت ۱:۱ PostController@index/destroy (پنل قدیمی).
 *
 * انحرافات مستند (بنا بر اسپک فاز ۶):
 *  - صفحه‌بندی ۱۵ (قدیمی ۱۰) + فیلتر جستجوی عنوان/اسلاگ با #[Url] (قدیمی فیلتر نداشت)
 *  - ستون‌های تکمیلی خواسته‌ی اسپک: اسلاگ، نوع، دسته‌ها، منتخب سردبیر، تعداد دیدگاه‌ها
 *  - حذف گروهی (قدیمی فقط تکی بود) با همان منطق destroy
 */
#[Title('لیست نوشته‌ها')]
class PostIndex extends Component
{
    use WithPagination;

    /* ---------------- فیلتر (State در URL) ---------------- */

    #[Url(as: 'q', except: '')]
    public string $search = '';

    public int $perPage = 15;

    /* ---------------- انتخاب گروهی ---------------- */

    public array $selected = [];

    public bool $selectAll = false;

    /* ---------------- حذف ---------------- */

    public ?int $confirmingDelete = null;

    public bool $confirmingBulkDelete = false;

    public function mount(): void
    {
        auth('adminPanel')->user()?->can('posts.index') ?: abort(403);
    }

    public function updating(string $name, $value): void
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

    /* ---------------- لیست (پورت index قدیمی) ---------------- */

    #[Computed]
    public function posts()
    {
        // پورت: Post::detectLang()->latest()->paginate(10)
        return Post::query()
            ->detectLang()
            ->with('Categories:id,title')
            ->withCount('comments')
            ->when($this->search !== '', function ($query) {
                $term = '%' . $this->search . '%';

                $query->where(function ($q) use ($term) {
                    $q->where('title', 'like', $term)
                        ->orWhere('slug', 'like', $term);
                });
            })
            ->latest()
            ->paginate($this->perPage);
    }

    #[Computed(persist: true, seconds: 60)]
    public function totalCount(): int
    {
        return Post::detectLang()->count();
    }

    /* ---------------- انتخاب گروهی ---------------- */

    public function updatedSelectAll($value): void
    {
        $this->selected = $value
            ? $this->posts->getCollection()->pluck('id')->map(fn ($id) => (string) $id)->toArray()
            : [];
    }

    /* ---------------- حذف (پورت destroy قدیمی) ---------------- */

    protected function destroyPost(Post $post): void
    {
        // پورت destroy: حذف تصویر از disk public + جدا کردن تگ‌ها + خالی کردن دسته‌ها + حذف
        if ($post->image) {
            // نرمال‌سازی اسلش ابتدای مسیر (رفع وابستگی رفتاری به Flysystem قدیمی — الگوی فاز ۵)
            Storage::disk('public')->delete(ltrim($post->image, '/'));
        }

        $post->tags()->detach();
        $post->Categories()->sync([]);
        $post->delete();
    }

    public function confirmDelete(int $id): void
    {
        auth('adminPanel')->user()?->can('posts.delete') ?: abort(403);

        $this->confirmingDelete = $id;
    }

    public function delete(): void
    {
        auth('adminPanel')->user()?->can('posts.delete') ?: abort(403);

        $post = Post::find($this->confirmingDelete);

        if ($post) {
            $this->destroyPost($post);

            $this->dispatch('toast', message: 'نوشته با موفقیت حذف شد.', type: 'success');
        }

        $this->confirmingDelete = null;
        $this->selected = [];
        $this->selectAll = false;
    }

    public function bulkDelete(): void
    {
        auth('adminPanel')->user()?->can('posts.delete') ?: abort(403);

        $posts = Post::whereIn('id', $this->selected)->get();

        foreach ($posts as $post) {
            $this->destroyPost($post);
        }

        $this->confirmingBulkDelete = false;
        $this->selected = [];
        $this->selectAll = false;

        $this->dispatch('toast', message: 'نوشته‌های انتخاب‌شده حذف شدند.', type: 'success');
    }

    /* ---------------- ابزار ---------------- */

    /** برچسب نوع نوشته (پورت مقدارهای enum posts.post_type) */
    public static function typeLabel(?string $type): string
    {
        return match ($type) {
            'video'   => 'ویدیویی',
            'podcast' => 'پادکست',
            default   => 'متنی',
        };
    }

    public static function jdate($date, $format = 'Y/m/d'): string
    {
        if (! $date) {
            return '—';
        }

        return Jalalian::fromDateTime($date)->format($format);
    }

    public function render()
    {
        return view('admin-panel::livewire.admin.posts.posts')
            ->layout('admin-panel::layout');
    }
}
