<?php

namespace App\Livewire\Admin\Notifications;

use App\Models\NotificationManage;
use App\Models\Seller;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Morilog\Jalali\Jalalian;

/**
 * مدیریت اعلان‌های سراسری — پورت ۱:۱ Back\NotificationController
 * (index/list/store/update/togglePopup/destroy/searchRecipients)
 *
 * دسترسی: کلید اصلی «notifications» برای صفحه (مثل middleware قدیمی can:notifications)
 * و کلیدهای notifications.create/update/delete برای اکشن‌ها (طبق اسپک فاز ۷).
 */
#[Title('مدیریت اعلان‌ها')]
class NotificationIndex extends Component
{
    use WithPagination;

    public int $perPage = 15;

    /* ---------------- فیلترها (پورت pillها و جستجوی قدیمی) ---------------- */

    /** all | high | medium | low */
    #[Url(as: 'priority', except: 'all')]
    public string $priority = 'all';

    #[Url(as: 'search', except: '')]
    public string $search = '';

    /* ---------------- مودال جزئیات (پورت back/notifications/show.blade.php) ---------------- */

    public bool $showDetail = false;

    public ?int $viewingNotification = null;

    /** صفحه‌بندی مشترک گیرندگان داخل مودال (۵۰ در صفحه — مثل قدیمی) */
    public int $recipientPage = 1;

    /* ---------------- مودال ایجاد/ویرایش (پورت store/update قدیمی) ---------------- */

    public bool $showFormModal = false;

    /** null = حالت ایجاد */
    public ?int $editingId = null;

    public string $title = '';

    public string $message = '';

    public string $priorityForm = 'low';

    public bool $popup = false;

    /** گروه‌های گیرنده (پورت send_users/send_sellers) */
    public bool $sendUsers = false;

    public bool $sendSellers = false;

    /** چیپ‌های انتخاب‌شده (id + label) — پورت nt-picker */
    public array $usersSelected = [];

    public array $sellersSelected = [];

    /** آرایه شناسه‌ها برای اعتبارسنجی — در save() از چیپ‌ها پر می‌شود */
    public array $userIds = [];

    public array $sellerIds = [];

    /* ---------------- جستجوی زنده گیرندگان (پورت searchRecipients قدیمی) ---------------- */

    public string $usersSearch = '';

    public array $usersResults = [];

    public string $sellersSearch = '';

    public array $sellersResults = [];

    /* ---------------- حذف (پورت destroy قدیمی) ---------------- */

    public ?int $confirmingDelete = null;

    public function mount(): void
    {
        auth('adminPanel')->user()?->can('notifications') ?: abort(403);
    }

    public function updating(string $name, $value): void
    {
        if (in_array($name, ['priority', 'search', 'perPage'])) {
            $this->resetPage();
        }
    }

    public function applyFilters(): void
    {
        $this->priority = 'all';
        $this->search = '';
        $this->resetPage();
    }

    /* ---------------- لیست (پورت list قدیمی با withCount گیرندگان) ---------------- */

    #[Computed]
    public function notifications()
    {
        return NotificationManage::query()
            ->when($this->priority !== 'all', fn ($query) => $query->where('priority', $this->priority))
            ->when($this->search !== '', function ($query) {
                $term = '%' . $this->search . '%';

                $query->where(function ($q) use ($term) {
                    $q->where('title', 'like', $term)
                        ->orWhere('message', 'like', $term);
                });
            })
            ->latest()
            ->withCount([
                'recipients as selected_users'   => fn ($q) => $q->whereNotNull('user_id'),
                'recipients as selected_sellers' => fn ($q) => $q->whereNotNull('seller_id'),
                'recipients as read_count'       => fn ($q) => $q->where('read', 1),
            ])
            ->paginate($this->perPage);
    }

    /* ---------------- آمار (پورت renderStats قدیمی — کل/پاپ‌آپ/گروهی/اولویت بالا) ---------------- */

    #[Computed]
    public function stats(): array
    {
        return [
            'total'     => NotificationManage::count(),
            'popup'     => NotificationManage::where('popup', 1)->count(),
            'broadcast' => NotificationManage::where(function ($query) {
                $query->where(function ($q) {
                    $q->whereIn('private', ['all', 'user'])->where('allUsers', 1);
                })->orWhere(function ($q) {
                    $q->whereIn('private', ['all', 'seller'])->where('allSellers', 1);
                });
            })->count(),
            'high'      => NotificationManage::where('priority', 'high')->count(),
        ];
    }

    /* ---------------- مجموع کاربران/فروشندگان (پورت list قدیمی) ---------------- */

    #[Computed]
    public function usersTotal(): int
    {
        return User::count();
    }

    #[Computed]
    public function sellersTotal(): int
    {
        return Seller::count();
    }

    /** پورت ساختار targets قدیمی (mode all/selected + تعداد) */
    public function targets(NotificationManage $notification): array
    {
        return [
            'users' => in_array($notification->private, ['all', 'user'])
                ? [
                    'mode'  => $notification->allUsers ? 'all' : 'selected',
                    'count' => $notification->allUsers ? $this->usersTotal : (int) $notification->selected_users,
                ]
                : null,
            'sellers' => in_array($notification->private, ['all', 'seller'])
                ? [
                    'mode'  => $notification->allSellers ? 'all' : 'selected',
                    'count' => $notification->allSellers ? $this->sellersTotal : (int) $notification->selected_sellers,
                ]
                : null,
        ];
    }

    /* ---------------- مودال جزئیات (پورت show قدیمی — گیرندگان با وضعیت خوانده‌شدن) ---------------- */

    public function openDetail(int $id): void
    {
        auth('adminPanel')->user()?->can('notifications') ?: abort(403);

        if (! NotificationManage::find($id)) {
            $this->dispatch('toast', message: 'اعلان یافت نشد.', type: 'error');

            return;
        }

        $this->viewingNotification = $id;
        $this->recipientPage = 1;
        $this->showDetail = true;
    }

    #[Computed]
    public function detailNotification(): ?NotificationManage
    {
        if (! $this->viewingNotification) {
            return null;
        }

        return NotificationManage::find($this->viewingNotification);
    }

    #[Computed]
    public function detailUsers()
    {
        $notification = $this->detailNotification;

        if (! $notification) {
            return null;
        }

        return $notification->recipients()
            ->whereNotNull('user_id')
            ->with('user')
            ->orderBy('id')
            ->paginate(50, ['*'], 'rpage', $this->recipientPage);
    }

    #[Computed]
    public function detailSellers()
    {
        $notification = $this->detailNotification;

        if (! $notification) {
            return null;
        }

        return $notification->recipients()
            ->whereNotNull('seller_id')
            ->with('seller.seller_info')
            ->orderBy('id')
            ->paginate(50, ['*'], 'rpage', $this->recipientPage);
    }

    #[Computed]
    public function recipientLastPage(): int
    {
        return max($this->detailUsers?->lastPage() ?? 1, $this->detailSellers?->lastPage() ?? 1);
    }

    /** اعلان در حال ویرایش (برای نمایش خلاصه گیرندگان ثابت) */
    #[Computed]
    public function editingNotification(): ?NotificationManage
    {
        if (! $this->editingId) {
            return null;
        }

        return NotificationManage::query()
            ->withCount([
                'recipients as selected_users'   => fn ($q) => $q->whereNotNull('user_id'),
                'recipients as selected_sellers' => fn ($q) => $q->whereNotNull('seller_id'),
            ])
            ->find($this->editingId);
    }

    public function recipientPrev(): void
    {
        if ($this->recipientPage > 1) {
            $this->recipientPage--;
        }
    }

    public function recipientNext(): void
    {
        if ($this->recipientPage < $this->recipientLastPage) {
            $this->recipientPage++;
        }
    }

    /* ---------------- جستجوی زنده گیرندگان (پورت searchRecipients قدیمی) ---------------- */

    public function updatedUsersSearch(): void
    {
        $this->usersResults = $this->searchUsers($this->usersSearch);
    }

    public function updatedSellersSearch(): void
    {
        $this->sellersResults = $this->searchSellers($this->sellersSearch);
    }

    /** پورت شاخه users قدیمی — جستجوی خالی = ۱۵ کاربر آخر */
    protected function searchUsers(string $term): array
    {
        $q = trim($term);

        $query = User::query();

        if ($q !== '') {
            $query->where(function ($qq) use ($q) {
                $qq->where('first_name', 'like', "%{$q}%")
                    ->orWhere('last_name', 'like', "%{$q}%")
                    ->orWhere('mobile', 'like', "%{$q}%");
            });
        }

        return $query->orderBy('id', 'desc')->limit(15)
            ->get(['id', 'first_name', 'last_name', 'mobile'])
            ->map(fn (User $row) => [
                'id'       => (int) $row->id,
                'label'    => trim((string) $row->first_name . ' ' . (string) $row->last_name) ?: 'کاربر #' . $row->id,
                'username' => (string) $row->mobile,
            ])
            ->values()
            ->all();
    }

    /** پورت شاخه sellers قدیمی — جستجو در sellers با join sellers_info (نام/موبایل)؛ خالی = ۱۵ فروشنده آخر */
    protected function searchSellers(string $term): array
    {
        $q = trim($term);

        $query = Seller::query()
            ->join('sellers_info', 'sellers.id', '=', 'sellers_info.seller_id')
            ->select('sellers.id', 'sellers_info.first_name', 'sellers_info.last_name', 'sellers.mobile');

        if ($q !== '') {
            $query->where(function ($qq) use ($q) {
                $qq->where('sellers_info.first_name', 'like', "%{$q}%")
                    ->orWhere('sellers.mobile', 'like', "%{$q}%");
            });
        }

        return $query->orderBy('sellers.id', 'desc')->limit(15)
            ->get()
            ->map(fn ($row) => [
                'id'       => (int) $row->id,
                'label'    => trim((string) $row->first_name . ' ' . (string) $row->last_name) ?: 'فروشنده #' . $row->id,
                'username' => (string) $row->mobile,
            ])
            ->values()
            ->all();
    }

    /* ---------------- چیپ‌های گیرندگان (الگوی DiscountForm) ---------------- */

    public function addUser(int $userId): void
    {
        if (collect($this->usersSelected)->pluck('id')->contains($userId)) {
            return;
        }

        if (! $user = User::find($userId)) {
            return;
        }

        $this->usersSelected[] = [
            'id'    => (int) $user->id,
            'label' => trim((string) $user->first_name . ' ' . (string) $user->last_name) ?: 'کاربر #' . $user->id,
        ];
        $this->usersSearch = '';
        $this->usersResults = [];
    }

    public function removeUser(int $userId): void
    {
        $this->usersSelected = $this->filterChips($this->usersSelected, $userId);
    }

    public function addSeller(int $sellerId): void
    {
        if (collect($this->sellersSelected)->pluck('id')->contains($sellerId)) {
            return;
        }

        if (! $seller = Seller::find($sellerId)) {
            return;
        }

        $this->sellersSelected[] = [
            'id'    => (int) $seller->id,
            'label' => trim((string) $seller->first_name . ' ' . (string) $seller->last_name) ?: 'فروشنده #' . $seller->id,
        ];
        $this->sellersSearch = '';
        $this->sellersResults = [];
    }

    public function removeSeller(int $sellerId): void
    {
        $this->sellersSelected = $this->filterChips($this->sellersSelected, $sellerId);
    }

    protected function filterChips(array $chips, int $id): array
    {
        return array_values(array_filter($chips, fn (array $chip) => (int) $chip['id'] !== $id));
    }

    /** غیرفعال‌کردن گروه → پاک‌شدن چیپ‌ها (پورت reset پیکر قدیمی) */
    public function updatedSendUsers($value): void
    {
        if (! $value) {
            $this->usersSelected = [];
            $this->usersSearch = '';
            $this->usersResults = [];
        }
    }

    public function updatedSendSellers($value): void
    {
        if (! $value) {
            $this->sellersSelected = [];
            $this->sellersSearch = '';
            $this->sellersResults = [];
        }
    }

    /* ---------------- مودال ایجاد/ویرایش ---------------- */

    public function openCreateModal(): void
    {
        auth('adminPanel')->user()?->can('notifications.create') ?: abort(403);

        $this->resetForm();

        // پورت رفتار focus قدیمی: با باز شدن فرم ۱۵ گیرنده آخر هر گروه آماده است
        $this->usersResults = $this->searchUsers('');
        $this->sellersResults = $this->searchSellers('');

        $this->showFormModal = true;
    }

    /** پورت editModal قدیمی — ویرایش فقط محتوا (گیرندگان ثابت می‌مانند) */
    public function openEditModal(int $id): void
    {
        auth('adminPanel')->user()?->can('notifications.update') ?: abort(403);

        if (! $notification = NotificationManage::find($id)) {
            $this->dispatch('toast', message: 'اعلان یافت نشد.', type: 'error');

            return;
        }

        $this->resetForm();

        $this->editingId = $notification->id;
        $this->title = (string) $notification->title;
        $this->message = (string) $notification->message;
        $this->priorityForm = (string) $notification->priority;
        $this->popup = (bool) $notification->popup;

        $this->showFormModal = true;
    }

    public function closeFormModal(): void
    {
        $this->showFormModal = false;
        $this->resetForm();
    }

    protected function resetForm(): void
    {
        $this->editingId = null;
        $this->title = '';
        $this->message = '';
        $this->priorityForm = 'low';
        $this->popup = false;
        $this->sendUsers = false;
        $this->sendSellers = false;
        $this->usersSelected = [];
        $this->sellersSelected = [];
        $this->userIds = [];
        $this->sellerIds = [];
        $this->usersSearch = '';
        $this->usersResults = [];
        $this->sellersSearch = '';
        $this->sellersResults = [];
        $this->resetErrorBag();
    }

    /* ---------------- ذخیره (پورت ۱:۱ store/update قدیمی) ---------------- */

    public function save(): void
    {
        $isEditing = $this->editingId !== null;

        auth('adminPanel')->user()?->can($isEditing ? 'notifications.update' : 'notifications.create') ?: abort(403);

        if ($isEditing) {
            $data = $this->validate([
                'title'        => ['required', 'string', 'max:190'],
                'message'      => ['required', 'string', 'max:2000'],
                'priorityForm' => ['required', 'in:high,medium,low'],
                'popup'        => ['boolean'],
            ], $this->messages());

            if (! $notification = NotificationManage::find($this->editingId)) {
                $this->dispatch('toast', message: 'اعلان یافت نشد.', type: 'error');

                return;
            }

            $notification->update([
                'title'    => $data['title'],
                'message'  => $data['message'],
                'priority' => $data['priorityForm'],
                'popup'    => $this->popup,
            ]);

            $this->logActivity('ویرایش', $data['title'], 0);

            $this->closeFormModal();
            $this->refreshAfterMutation();

            $this->dispatch('toast', message: 'اعلان با موفقیت ویرایش شد.', type: 'success');

            return;
        }

        // ---------- ایجاد ----------
        $this->userIds = array_map('intval', array_column($this->usersSelected, 'id'));
        $this->sellerIds = array_map('intval', array_column($this->sellersSelected, 'id'));

        $data = $this->validate([
            'title'        => ['required', 'string', 'max:190'],
            'message'      => ['required', 'string', 'max:2000'],
            'priorityForm' => ['required', 'in:high,medium,low'],
            'popup'        => ['boolean'],
            'sendUsers'    => ['boolean'],
            'sendSellers'  => ['boolean'],
            'userIds'      => ['nullable', 'array'],
            'userIds.*'    => ['integer', 'exists:users,id'],
            'sellerIds'    => ['nullable', 'array'],
            'sellerIds.*'  => ['integer', 'exists:sellers,id'],
        ], $this->messages());

        $sendUsers = $this->sendUsers;
        $sendSellers = $this->sendSellers;

        if (! $sendUsers && ! $sendSellers) {
            $this->addError('sendUsers', 'حداقل یکی از گروه‌های گیرنده را فعال کنید.');
            $this->dispatch('toast', message: 'گروه گیرنده را انتخاب کنید!', type: 'warning');

            return;
        }

        // گروه فعال با چیپ خالی = همه اعضای همان گروه (پورت دقیق store قدیمی)
        $userIds = array_values(array_unique(array_filter((array) $this->userIds)));
        if ($sendUsers && ! count($userIds)) {
            $userIds = User::all()->pluck('id');
        }

        $sellerIds = array_values(array_unique(array_filter((array) $this->sellerIds)));
        if ($sendSellers && ! count($sellerIds)) {
            $sellerIds = Seller::all()->pluck('id');
        }

        $notification = NotificationManage::create([
            'admin_id'   => auth('adminPanel')->id(),
            'title'      => $data['title'],
            'message'    => $data['message'],
            'priority'   => $data['priorityForm'],
            'popup'      => $this->popup,
            'private'    => $sendUsers && $sendSellers ? 'all' : ($sendSellers ? 'seller' : 'user'),
            'allUsers'   => $sendUsers ? (count($userIds) ? 0 : 1) : 0,
            'allSellers' => $sendSellers ? (count($sellerIds) ? 0 : 1) : 0,
        ]);

        // درج دستی ردیف‌های گیرندگان (پورت دقیق insert قدیمی)
        $rows = [];
        $now = now();

        foreach ($userIds as $id) {
            $rows[] = ['notification_manage_id' => $notification->id, 'user_id' => $id, 'seller_id' => null, 'read' => 0, 'created_at' => $now, 'updated_at' => $now];
        }

        foreach ($sellerIds as $id) {
            $rows[] = ['notification_manage_id' => $notification->id, 'user_id' => null, 'seller_id' => $id, 'read' => 0, 'created_at' => $now, 'updated_at' => $now];
        }

        if ($rows) {
            DB::table('notification_manage_users')->insert($rows);
        }

        $this->logActivity('ایجاد', $data['title'], count($rows));

        $this->closeFormModal();
        $this->refreshAfterMutation();

        $this->dispatch('toast', message: count($rows)
            ? 'اعلان برای ' . count($rows) . ' گیرنده انتخابی ذخیره شد.'
            : 'اعلان به صورت گروهی (همه گیرندگان گروه انتخاب‌شده) ذخیره شد.', type: 'success');
    }

    protected function messages(): array
    {
        return [
            'title.required'          => 'عنوان الزامی است.',
            'title.max'               => 'عنوان نمی‌تواند بیشتر از ۱۹۰ کاراکتر باشد.',
            'message.required'        => 'متن اعلان الزامی است.',
            'message.max'             => 'متن اعلان نمی‌تواند بیشتر از ۲۰۰۰ کاراکتر باشد.',
            'priorityForm.required'   => 'اولویت اعلان الزامی است.',
            'priorityForm.in'         => 'اولویت اعلان نامعتبر است.',
            'userIds.*.exists'        => 'یکی از کاربران انتخاب‌شده نامعتبر است.',
            'sellerIds.*.exists'      => 'یکی از فروشندگان انتخاب‌شده نامعتبر است.',
        ];
    }

    /* ---------------- سوییچ پاپ‌آپ (پورت togglePopup قدیمی) ---------------- */

    public function togglePopup(int $id): void
    {
        auth('adminPanel')->user()?->can('notifications.update') ?: abort(403);

        if (! $notification = NotificationManage::find($id)) {
            $this->dispatch('toast', message: 'اعلان یافت نشد.', type: 'error');

            return;
        }

        $notification->update(['popup' => ! $notification->popup]);

        unset($this->notifications);
        unset($this->stats);

        $this->dispatch('toast', message: $notification->popup
            ? 'نمایش پاپ‌آپ فعال شد.'
            : 'نمایش پاپ‌آپ غیرفعال شد.', type: 'success');
    }

    /* ---------------- حذف (پورت destroy قدیمی — cascade گیرندگان) ---------------- */

    public function confirmDelete(int $id): void
    {
        auth('adminPanel')->user()?->can('notifications.delete') ?: abort(403);

        $this->confirmingDelete = $id;
    }

    public function delete(): void
    {
        auth('adminPanel')->user()?->can('notifications.delete') ?: abort(403);

        if ($notification = NotificationManage::find($this->confirmingDelete)) {
            $title = $notification->title ?: 'بدون عنوان';
            $count = $notification->recipients()->count();

            $notification->delete();
            // برابری FK cascade قدیمی (notification_manage_users onDelete cascade) — قابل‌حمل در SQLite
            DB::table('notification_manage_users')->where('notification_manage_id', $notification->id)->delete();

            $this->logActivity('حذف', $title, $count);

            if ($this->viewingNotification !== null && ! NotificationManage::find($this->viewingNotification)) {
                $this->showDetail = false;
                $this->viewingNotification = null;
            }

            $this->confirmingDelete = null;
            $this->refreshAfterMutation();

            $this->dispatch('toast', message: "اعلان و {$count} ردیف گیرنده آن حذف شد.", type: 'success');

            return;
        }

        $this->confirmingDelete = null;
    }

    /* ---------------- activity log (پورت دقیق logActivity قدیمی) ---------------- */

    protected function logActivity(string $action, string $title, int $recipients): void
    {
        $admin = auth('adminPanel')->user();
        $adminName = $admin?->full_name ?? $admin?->name ?? 'مدیر';

        activity()
            ->causedBy($admin)
            ->event('notification')
            ->withProperties([
                'action'     => 'notification_' . strtolower($action),
                'title'      => $title,
                'recipients' => $recipients,
                'ip'         => request()->ip(),
            ])
            ->log("مدیر {$adminName} اعلان «{$title}» را {$action} کرد.");
    }

    protected function refreshAfterMutation(): void
    {
        unset($this->notifications);
        unset($this->stats);
        unset($this->detailNotification);
        unset($this->detailUsers);
        unset($this->detailSellers);
        unset($this->recipientLastPage);
    }

    /* ---------------- ابزار ---------------- */

    /** برچسب/رنگ اولویت — پورت PRIORITIES رندر قدیمی */
    public static function priorityMeta(string $priority): array
    {
        return match ($priority) {
            'high'   => ['label' => 'فوری', 'icon' => 'triangle-alert', 'class' => 'bg-red-50 text-red-700 dark:bg-red-500/12 dark:text-red-400', 'iconClass' => 'bg-red-50 text-red-600 dark:bg-red-500/12 dark:text-red-400'],
            'medium' => ['label' => 'متوسط', 'icon' => 'circle-alert', 'class' => 'bg-amber-50 text-amber-700 dark:bg-amber-500/12 dark:text-amber-400', 'iconClass' => 'bg-amber-50 text-amber-600 dark:bg-amber-500/12 dark:text-amber-400'],
            default  => ['label' => 'عادی', 'icon' => 'info', 'class' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/12 dark:text-emerald-400', 'iconClass' => 'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/12 dark:text-emerald-400'],
        };
    }

    public static function jdate($date, $format = 'Y/m/d'): string
    {
        if (! $date) {
            return '—';
        }

        return Jalalian::fromDateTime($date)->format($format);
    }

    public static function jdateAgo($date): string
    {
        if (! $date) {
            return '—';
        }

        return Jalalian::fromDateTime($date)->ago();
    }

    public function render()
    {
        return view('admin-panel::livewire.admin.notifications.notifications')
            ->layout('admin-panel::layout');
    }
}
