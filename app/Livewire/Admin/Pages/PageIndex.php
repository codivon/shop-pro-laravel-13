<?php

namespace App\Livewire\Admin\Pages;

use App\Models\Page;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Morilog\Jalali\Jalalian;

/**
 * لیست صفحات (برگه‌ها) — پورت ۱:۱ PageController@index/destroy.
 *
 * destroy قدیمی: tags()->detach() + delete (چون Page تگ‌پذیر است) — عیناً پورت شد.
 * {page} در روت ویرایش با «اسلاگ» بایند می‌شود (Page::getRouteKeyName = 'slug').
 */
#[Title('لیست صفحات')]
class PageIndex extends Component
{
    use WithPagination;

    /* ---------------- فیلتر (State در URL) ---------------- */

    #[Url(as: 'q', except: '')]
    public string $search = '';

    public int $perPage = 15;

    /* ---------------- انتخاب گروهی و حذف ---------------- */

    /** @var array<int, int> شناسه‌های انتخاب‌شده برای حذف گروهی */
    public array $selected = [];

    public bool $confirmingBulkDelete = false;

    public ?int $confirmingDelete = null;

    public function mount(): void
    {
        auth('adminPanel')->user()?->can('pages.index') ?: abort(403);
    }

    public function updating(string $name, $value): void
    {
        if (in_array($name, ['search', 'perPage'])) {
            $this->resetPage();
        }
    }

    /** انتخاب/لغو انتخاب همه ردیف‌های صفحه جاری (حذف گروهی) */
    public function toggleSelectAll(): void
    {
        $this->selected = count($this->selected) === count($this->pages->items())
            ? []
            : $this->pages->map(fn ($page) => $page->id)->values()->all();
    }

    /* ---------------- لیست (پورت index قدیمی) ---------------- */

    #[Computed]
    public function pages()
    {
        return Page::query()
            ->detectLang()
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
        return Page::detectLang()->count();
    }

    /* ---------------- حذف (پورت ۱:۱ destroy قدیمی) ---------------- */

    public function confirmDelete(int $id): void
    {
        auth('adminPanel')->user()?->can('pages.delete') ?: abort(403);

        $this->confirmingDelete = $id;
    }

    public function delete(): void
    {
        auth('adminPanel')->user()?->can('pages.delete') ?: abort(403);

        $page = Page::find($this->confirmingDelete);

        if ($page) {
            $page->tags()->detach();
            $page->delete();
        }

        $this->confirmingDelete = null;

        $this->dispatch('toast', message: 'صفحه با موفقیت حذف شد.', type: 'success');
    }

    /* ---------------- حذف گروهی (مکمل پنل — همان پورت destroy روی چند ردیف) ---------------- */

    public function deleteSelected(): void
    {
        auth('adminPanel')->user()?->can('pages.delete') ?: abort(403);

        if ($this->selected === []) {
            return;
        }

        foreach (Page::whereIn('id', $this->selected)->get() as $page) {
            $page->tags()->detach();
            $page->delete();
        }

        $this->selected = [];
        $this->confirmingBulkDelete = false;

        $this->dispatch('toast', message: 'صفحات انتخاب‌شده حذف شدند.', type: 'success');
    }

    /* ---------------- ابزار ---------------- */

    public static function jdate($date, $format = 'Y/m/d'): string
    {
        if (! $date) {
            return '—';
        }

        return Jalalian::fromDateTime($date)->format($format);
    }

    public function render()
    {
        return view('admin-panel::livewire.admin.pages.pages')
            ->layout('admin-panel::layout');
    }
}
