<?php

namespace App\Livewire\Admin\Users;

use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Morilog\Jalali\Jalalian;

#[Title('لیست کاربران')]
class UserIndex extends Component
{
    use WithPagination;

    /* ---------------- فیلترها (State در URL) — پورت User::scopeFilter ---------------- */

    #[Url(as: 'fullname', except: '')]
    public string $fullname = '';

    #[Url(as: 'email', except: '')]
    public string $email = '';

    #[Url(as: 'username', except: '')]
    public string $username = '';

    /** user | admin | '' (همه) — مثل قدیمی level=all فقط «همه» است */
    #[Url(as: 'level', except: '')]
    public string $level = '';

    public int $perPage = 15;

    /* ---------------- انتخاب گروهی ---------------- */

    public array $selected = [];

    public bool $selectAll = false;

    /* ---------------- حذف ---------------- */

    public ?int $confirmingDelete = null;

    public bool $confirmingBulkDelete = false;

    /* ---------------- خروجی اکسل (پورت export قدیمی) ---------------- */

    public bool $exportModal = false;

    /** ستون‌های قابل انتخاب — دقیقاً لیست قدیمی resources/views/back/users/index.blade.php */
    public array $exportColumnList = [
        'id' => 'آیدی',
        'first_name' => 'نام',
        'last_name' => 'نام خانوادگی',
        'username' => 'نام کاربری',
        'email' => 'ایمیل',
        'created_at' => 'تاریخ ثبت نام',
    ];

    public array $exportColumns = ['id', 'first_name', 'last_name', 'username', 'email', 'created_at'];

    public string $exportType = 'excel';

    public function mount(): void
    {
        auth('adminPanel')->user()?->can('users.index') ?: abort(403);
    }

    public function updating($name, $value)
    {
        if (in_array($name, ['fullname', 'email', 'username', 'level', 'perPage'])) {
            $this->resetPage();
            $this->selected = [];
            $this->selectAll = false;
        }
    }

    /** بازنشانی همه فیلترها */
    public function applyFilters(): void
    {
        $this->fullname = '';
        $this->email = '';
        $this->username = '';
        $this->level = '';
        $this->resetPage();
        $this->selected = [];
        $this->selectAll = false;
    }

    /* ---------------- کوئری لیست (پورت User::scopeFilter قدیمی) ---------------- */

    protected function baseQuery()
    {
        // مثل قدیمی: ردیف‌های creator حذف می‌شوند (User::scopeExcludeCreator)
        $query = User::query()->where('level', '!=', 'creator');

        // پورت scopeFilter — «نام و نام خانوادگی» به صورت concat مثل قدیمی (با بایندینگ امن)
        if ($this->fullname !== '') {
            $query->whereRaw("concat(first_name, ' ', last_name) like ?", ['%' . $this->fullname . '%']);
        }

        if ($this->email !== '') {
            $query->where('email', 'like', '%' . $this->email . '%');
        }

        if ($this->username !== '') {
            $query->where('username', 'like', '%' . $this->username . '%');
        }

        // قدیمی فقط admin و user را فیلتر می‌کرد؛ هر مقدار دیگری = همه
        if (in_array($this->level, ['admin', 'user'], true)) {
            $query->where('level', $this->level);
        }

        return $query->latest();
    }

    #[Computed]
    public function users()
    {
        return $this->baseQuery()->paginate($this->perPage);
    }

    /* ---------------- انتخاب گروهی ---------------- */

    public function updatedSelectAll($value): void
    {
        $this->selected = $value
            ? $this->users->getCollection()->pluck('id')->map(fn ($id) => (string) $id)->toArray()
            : [];
    }

    /* ---------------- حذف (پورت destroy / multipleDestroy) ---------------- */

    /**
     * پورت UserController::destroy — اگر تصویر داشته باشد حذف می‌شود، سپس رکورد.
     */
    protected function destroyUser(User $user): void
    {
        if ($user->image) {
            Storage::disk('public')->delete($user->image);
        }

        $user->delete();
    }

    public function confirmDelete(int $id): void
    {
        $this->authorizeUser('users.delete');
        $this->confirmingDelete = $id;
    }

    public function delete(): void
    {
        $this->authorizeUser('users.delete');

        $user = User::find($this->confirmingDelete);

        if ($user) {
            // مثل قانون multipleDestroy قدیمی: خودِ ادمین و کاربر creator قابل حذف نیستند
            if ($user->id === auth('adminPanel')->id() || $user->level === 'creator') {
                $this->confirmingDelete = null;
                $this->dispatch('toast', message: 'حذف این کاربر مجاز نیست.', type: 'error');

                return;
            }

            $this->destroyUser($user);

            $this->dispatch('toast', message: 'کاربر با موفقیت حذف شد.', type: 'success');
        }

        $this->confirmingDelete = null;
        $this->selected = [];
        $this->selectAll = false;
    }

    public function bulkDelete(): void
    {
        $this->authorizeUser('users.delete');

        // پورت قاعده Rule::exists + where در multipleDestroy قدیمی
        $users = User::whereIn('id', $this->selected)
            ->where('id', '!=', auth('adminPanel')->user()->id)
            ->where('level', '!=', 'creator')
            ->get();

        foreach ($users as $user) {
            $this->destroyUser($user);
        }

        $this->confirmingBulkDelete = false;
        $this->selected = [];
        $this->selectAll = false;

        $this->dispatch('toast', message: 'کاربران انتخاب‌شده حذف شدند.', type: 'success');
    }

    /* ---------------- خروجی اکسل ---------------- */

    public function toggleExportColumn(string $column): void
    {
        if (in_array($column, $this->exportColumns, true)) {
            $this->exportColumns = array_values(array_diff($this->exportColumns, [$column]));
        } else {
            $this->exportColumns[] = $column;
        }
    }

    public function exportUsers(): void
    {
        $this->authorizeUser('users.export');

        // پورت فرم قدیمی back/users/index.blade.php:
        // فیلترهای فعلی داخل query[...] و ستون‌های انتخابی داخل filters[...] می‌روند
        $query = http_build_query([
            'query' => array_filter([
                'fullname' => $this->fullname ?: null,
                'email' => $this->email ?: null,
                'username' => $this->username ?: null,
                'level' => in_array($this->level, ['admin', 'user'], true) ? $this->level : null,
            ]),
            'filters' => array_fill_keys($this->exportColumns, '1'),
            'export_type' => $this->exportType === 'excel' ? 'excel' : 'print',
        ]);

        $this->exportModal = false;

        // توجه: در قدیمی هم شاخه print خالی است (no-op) و چیزی دانلود نمی‌کند
        $this->dispatch('open-url', url: route('np.users.export') . '?' . $query);
    }

    /* ---------------- ابزارها ---------------- */

    protected function authorizeUser(string $permission): void
    {
        if (! auth('adminPanel')->user()?->can($permission)) {
            abort(403);
        }
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
        return view('admin-panel::livewire.admin.users.index')
            ->layout('admin-panel::layout');
    }
}
