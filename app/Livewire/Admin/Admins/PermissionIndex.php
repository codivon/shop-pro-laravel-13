<?php

namespace App\Livewire\Admin\Admins;

use App\Models\Permission;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * مدیریت دسترسی‌ها — پورت ۱:۱ Back\PermissionController (index/update) + میدل‌ور CheckCreator.
 * درخت دسترسی‌های DB (والد با ordering + فرزندان) با چک‌باکس فعال/غیرفعال؛
 * ذخیره = همه را غیرفعال کن و فقط انتخاب‌شده‌ها را فعال کن (مثل قدیمی) + لاگ spatie با performedOn مدل ساختگی.
 *
 * نکته: این صفحه در قدیمی فقط برای creator است (middleware) — در منوی پنل جدید هم creator_only است.
 */
#[Title('دسترسی‌ها')]
class PermissionIndex extends Component
{
    /** دسترسی‌های فعال انتخاب‌شده (پورت فیلد request قدیمی «permission») */
    public array $permission = [];

    public function mount(): void
    {
        // پورت middleware CheckCreator
        $this->ensureCreator();

        // پیش‌بارگذاری چک‌باکس‌ها از وضعیت فعلی DB (مثل checked های فرم قدیمی)
        $this->permission = Permission::query()
            ->where('active', true)
            ->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->all();
    }

    /** پورت middleware CheckCreator */
    protected function ensureCreator(): void
    {
        $admin = auth('adminPanel')->user();

        if (! $admin || $admin->level !== 'creator') {
            abort(403);
        }
    }

    /* ---------------- درخت (پورت index قدیمی) ---------------- */

    #[Computed]
    public function tree()
    {
        // والد‌ها مرتب با ordering — فرزندان بدون ترتیب خاص (مثل رابطه قدیمی)
        return Permission::query()
            ->whereNull('permission_id')
            ->orderBy('ordering')
            ->with('permissions')
            ->get();
    }

    /* ---------------- کسکید والد/فرزند (پورت JS قدیمی $('.parent-permission').change) ---------------- */

    /** تیک والد → همه فرزندان تیک می‌خورند / برداشتن تیک والد → تیک فرزندان برداشته می‌شود. */
    public function toggleParent(int $parentId): void
    {
        $parent = (string) $parentId;

        $children = Permission::query()
            ->where('permission_id', $parentId)
            ->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->all();

        if (in_array($parent, $this->permission, true)) {
            $this->permission = array_values(array_diff($this->permission, [$parent], $children));
        } else {
            $this->permission = array_values(array_unique(array_merge($this->permission, [$parent], $children)));
        }
    }

    /* ---------------- ذخیره (پورت ۱:۱ update قدیمی) ---------------- */

    public function save(): void
    {
        // پورت middleware CheckCreator
        $this->ensureCreator();

        $this->validate([
            'permission'   => 'required|array',
            'permission.*' => 'exists:permissions,id',
        ], [], [
            'permission' => 'دسترسی‌ها',
        ]);

        $ids = array_values(array_map('intval', $this->permission));

        $admin = auth('adminPanel')->user();

        // عبارت قدیمی عیناً حفظ شد (برای Admin نتیجه همیشه «مدیر» است)
        $adminName = $admin->full_name ?? $admin->name ?? 'مدیر';

        // دسترسی‌های قبلی فعال
        $oldActiveIds = Permission::query()->where('active', true)->pluck('id')->toArray();

        $addedPermissions = array_diff($ids, $oldActiveIds);
        $removedPermissions = array_diff($oldActiveIds, $ids);

        $allPermissions = Permission::all()->keyBy('id');

        // مدل ساختگی برای performedOn (پورت دقیق قدیمی)
        $dummyPermission = new Permission();
        $dummyPermission->id = 0;
        $dummyPermission->name = 'permissions_update';

        // همه غیرفعال → فقط انتخاب‌شده‌ها فعال (مثل قدیمی)
        Permission::query()->update(['active' => false]);

        foreach ($ids as $permissionId) {
            Permission::find($permissionId)?->update(['active' => true]);
        }

        // ساخت old/attributes برای لاگ (پورت دقیق)
        $oldData = [];
        $newData = [];

        foreach ($removedPermissions as $permissionId) {
            $title = $allPermissions[$permissionId]->title ?? $allPermissions[$permissionId]->name ?? "دسترسی #{$permissionId}";
            $oldData[$title] = 'فعال';
            $newData[$title] = 'غیرفعال';
        }

        foreach ($addedPermissions as $permissionId) {
            $title = $allPermissions[$permissionId]->title ?? $allPermissions[$permissionId]->name ?? "دسترسی #{$permissionId}";
            $oldData[$title] = 'غیرفعال';
            $newData[$title] = 'فعال';
        }

        activity()
            ->performedOn($dummyPermission)
            ->causedBy($admin)
            ->event('updated')
            ->withProperties([
                'action'     => 'update_permissions',
                'old'        => $oldData,
                'attributes' => $newData,
                'ip'         => request()->ip(),
            ])
            ->log("مدیر {$adminName} سطح دسترسی‌ها را تغییر داد");

        // متن پیام عیناً از JS قدیمی (Swal)
        $this->dispatch('toast', message: 'تغییرات با موفقیت ذخیره شد', type: 'success');
    }

    public function render()
    {
        return view('admin-panel::livewire.admin.admins.permissions')
            ->layout('admin-panel::layout');
    }
}
