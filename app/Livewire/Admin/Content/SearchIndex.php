<?php

namespace App\Livewire\Admin\Content;

use App\Models\Search;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('لیست جستجوها')]
class SearchIndex extends Component
{
    use WithPagination;

    public int $perPage = 15;

    /* ---------------- فیلترها (پورت filters قدیمی — State در URL) ---------------- */

    #[Url(as: 'search_type', except: '')]
    public string $filterType = '';

    #[Url(as: 'keyword', except: '')]
    public string $filterKeyword = '';

    /* ---------------- مودال جزئیات (پورت details قدیمی) ---------------- */

    public bool $showDetails = false;

    public string $detailsKeyword = '';

    public string $detailsType = '';

    public array $detailsRows = [];

    public int $totalSearches = 0;

    /* ---------------- حذف (پورت destroy / multipleDestroy قدیمی) ---------------- */

    public ?string $confirmingDelete = null; // کلید ترکیبی «keyword__type»

    public ?string $confirmingBulkDelete = null;

    public array $selected = [];

    public function mount(): void
    {
        // پورت middleware('can:searches') قدیمی
        auth('adminPanel')->user()?->can('searches') ?: abort(403);
    }

    public function updating(string $name, $value): void
    {
        if (in_array($name, ['filterType', 'filterKeyword', 'perPage'])) {
            $this->resetPage();
        }
    }

    /* ---------------- لیست گروه‌بندی‌شده (پورت ۱:۱ index قدیمی — قابل‌حمل SQLite/MySQL) ---------------- */

    #[Computed]
    public function rows()
    {
        return Search::query()
            ->select(
                'keyword',
                'search_type',
                DB::raw('COUNT(*) as search_count'),
                DB::raw('MAX(searched_at) as last_searched'),
                DB::raw('MIN(searched_at) as first_searched'),
                // پورت AVG(CASE ...) قدیمی — quote ها تک‌کوتیشن تا روی SQLite/MySQL هر دو اجرا شود
                DB::raw("AVG(CASE
                    WHEN search_type = 'products' THEN products_count
                    WHEN search_type = 'posts' THEN posts_count
                    ELSE 0 END) as avg_results")
            )
            ->when($this->filterType, function ($query) {
                $query->where('search_type', $this->filterType);
            })
            ->when($this->filterKeyword !== '', function ($query) {
                $query->where('keyword', 'like', '%' . $this->filterKeyword . '%');
            })
            ->groupBy('keyword', 'search_type')
            ->orderBy('search_count', 'desc')
            ->paginate($this->perPage);
    }

    /* ---------------- ردیف‌های صفحه جاری (برای ارسال پارامتر امن به اکشن‌ها) ---------------- */

    #[Computed]
    public function pageItems()
    {
        return $this->rows->getCollection()->values();
    }

    public function applyFilters(): void
    {
        $this->reset(['filterType', 'filterKeyword']);
        $this->resetPage();
    }

    /* ---------------- جزئیات (پورت ۱:۱ details قدیمی) ---------------- */

    public function showDetails(int $index): void
    {
        auth('adminPanel')->user()?->can('searches') ?: abort(403);

        $item = $this->pageItems[$index] ?? null;

        if (! $item) {
            return;
        }

        $this->detailsKeyword = $item->keyword;
        $this->detailsType = $item->search_type;

        // پورت: Search::with('user')->where('keyword')->where('search_type')->orderBy('searched_at', 'desc')->get()
        $searches = Search::with('user')
            ->where('keyword', $this->detailsKeyword)
            ->where('search_type', $this->detailsType)
            ->orderBy('searched_at', 'desc')
            ->get();

        $this->detailsRows = $searches->map(fn ($search) => [
            'name'          => $search->user ? ($search->user->fullname ?: ($search->user->username ?? '')) : 'کاربر مهمان',
            'email'         => $search->user?->email ?? '-',
            'ip'            => $search->ip_address,
            'searched_at'   => self::jdate($search->searched_at, 'd F Y | H:i:s'),
            'results'       => $search->search_type === 'products'
                ? 'محصولات: ' . number_format($search->products_count) . ' | دسته‌ها: ' . number_format($search->categories_count) . ' | برندها: ' . number_format($search->brands_count)
                : 'پست‌ها: ' . number_format($search->posts_count),
        ])->all();

        $this->totalSearches = $searches->count();
        $this->showDetails = true;
    }

    /* ---------------- حذف یک کلیدواژه (پورت ۱:۱ destroy قدیمی — همه ردیف‌های keyword+type) ---------------- */

    public function confirmDelete(int $index): void
    {
        auth('adminPanel')->user()?->can('searches.delete') ?: abort(403);

        $item = $this->pageItems[$index] ?? null;

        if ($item) {
            $this->confirmingDelete = $item->keyword . '__' . $item->search_type;
        }
    }

    public function destroy(): void
    {
        auth('adminPanel')->user()?->can('searches.delete') ?: abort(403);

        if (! $this->confirmingDelete) {
            return;
        }

        list($keyword, $type) = explode('__', $this->confirmingDelete);

        // پورت: حذف تک‌تک ردیف‌ها (رویدادهای مدل فعال می‌مانند مثل قدیمی)
        $query = Search::where('keyword', $keyword);

        if ($type) {
            $query->where('search_type', $type);
        }

        $deletedCount = 0;

        foreach ($query->get() as $search) {
            $search->delete();
            $deletedCount++;
        }

        $this->confirmingDelete = null;
        unset($this->rows, $this->pageItems);

        $this->dispatch('toast', message: "$deletedCount رکورد جستجو حذف شد", type: 'success');
    }

    /* ---------------- حذف گروهی (پورت ۱:۱ multipleDestroy قدیمی) ---------------- */

    public function confirmBulkDelete(): void
    {
        auth('adminPanel')->user()?->can('searches.delete') ?: abort(403);

        if (count($this->selected)) {
            $this->confirmingBulkDelete = 'open';
        }
    }

    public function multipleDestroy(): void
    {
        auth('adminPanel')->user()?->can('searches.delete') ?: abort(403);

        // پورت validate قدیمی: ids => required|array
        $this->validate([
            'selected' => 'required|array',
        ], [], ['selected' => 'شناسه‌ها']);

        $deletedCount = 0;

        foreach ($this->selected as $item) {
            // کلیدهای ترکیبی «keyword__type» (پورت دقیق explode قدیمی)
            list($keyword, $type) = explode('__', $item);

            $search = Search::where('keyword', $keyword)
                ->when($type, function ($q) use ($type) {
                    return $q->where('search_type', $type);
                })
                ->first();

            if ($search) {
                $search->delete();
                $deletedCount++;
            }
        }

        $this->selected = [];
        $this->confirmingBulkDelete = null;
        unset($this->rows, $this->pageItems);

        $this->dispatch('toast', message: "$deletedCount رکورد جستجو حذف شد", type: 'success');
    }

    /* ---------------- ابزار ---------------- */

    public function toggleSelectAll(): void
    {
        $keys = $this->pageItems
            ->map(fn ($item) => $item->keyword . '__' . $item->search_type)
            ->all();

        if (count(array_diff($keys, $this->selected)) === 0 && count($this->selected) > 0) {
            $this->selected = array_values(array_diff($this->selected, $keys));
        } else {
            $this->selected = array_values(array_unique(array_merge($this->selected, $keys)));
        }
    }

    public static function jdate($date, $format = 'Y/m/d'): string
    {
        if (! $date) {
            return '—';
        }

        try {
            return \Morilog\Jalali\Jalalian::fromDateTime($date)->format($format);
        } catch (\Throwable) {
            return '—';
        }
    }

    public function render()
    {
        return view('admin-panel::livewire.admin.content.searches')
            ->layout('admin-panel::layout');
    }
}
