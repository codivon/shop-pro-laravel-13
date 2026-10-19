<?php

namespace App\Livewire\Admin\Admins;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * نقش‌ها (مقام‌ها) — پورت ۱:۱ Back\RoleController (index/create/store/edit/update/destroy)
 * به‌صورت لیست + مودال ایجاد/ویرایش با درخت دسترسی‌ها (چک‌باکس والد/فرزند + کسکید مثل JS قدیمی) + sync پیوت.
 *
 * لاگ‌های spatie activity با همان properties/پیام‌های قدیمی پورت شده‌اند (عبارت full_name ?? name ?? 'مدیر'
 * عیناً حفظ شد — برای مدل Admin همیشه «مدیر» می‌دهد چون اکسسور مدل fullname است نه full_name).
 */
#[Title('نقش‌ها')]
class RoleIndex extends Component
{
    use WithPagination;

    /** مثل index قدیمی: paginate(20) */
    public int $perPage = 20;

    /* ---------------- مودال ایجاد/ویرایش (پورت create/edit/store/update قدیمی) ---------------- */

    public bool $showFormModal = false;

    /** null = حالت ایجاد */
    public ?int $roleId = null;

    public string $title = '';

    public ?string $description = null;

    /** دسترسی‌های انتخاب‌شده (رشته‌ای مثل الگوی پروژه برای wire:model) */
    public array $permissions = [];

    /* ---------------- حذف ---------------- */

    public ?int $confirmingDelete = null;

    public function mount(): void
    {
        auth('adminPanel')->user()?->can('roles.index') ?: abort(403);
    }

    /* ---------------- لیست (پورت index قدیمی + withCount طبق اسپک) ---------------- */

    #[Computed]
    public function roles()
    {
        return Role::query()
            ->withCount('admins')
            ->latest()
            ->paginate($this->perPage);
    }

    /** درخت دسترسی‌های فرم (پورت create/edit قدیمی: والد active مرتب با ordering + فرزند active) */
    #[Computed]
    public function permissionTree()
    {
        return Permission::query()
            ->whereNull('permission_id')
            ->where('active', true)
            ->orderBy('ordering')
            ->with(['permissions' => fn ($query) => $query->where('active', true)])
            ->get();
    }

    /* ---------------- مودال ---------------- */

    public function openCreateModal(): void
    {
        auth('adminPanel')->user()?->can('roles.create') ?: abort(403);

        $this->resetForm();

        $this->showFormModal = true;
    }

    /** پورت edit قدیمی */
    public function openEditModal(int $id): void
    {
        auth('adminPanel')->user()?->can('roles.update') ?: abort(403);

        $role = Role::findOrFail($id);

        $this->roleId = $role->id;
        $this->title = (string) $role->title;
        $this->description = $role->description;
        $this->permissions = $role->permissions->pluck('id')->map(fn ($id) => (string) $id)->all();
        $this->resetErrorBag();

        $this->showFormModal = true;
    }

    public function closeFormModal(): void
    {
        $this->showFormModal = false;
        $this->resetForm();
    }

    protected function resetForm(): void
    {
        $this->roleId = null;
        $this->title = '';
        $this->description = null;
        $this->permissions = [];
        $this->resetErrorBag();
    }

    /* ---------------- کسکید والد/فرزند (پورت JS قدیمی $('.parent-permission').change) ---------------- */

    /**
     * تیک والد → همه فرزندان active تیک می‌خورند / برداشتن تیک والد → تیک فرزندان برداشته می‌شود.
     * فرزندان مستقل هم قابل انتخاب‌اند (مثل قدیمی).
     */
    public function toggleParent(int $parentId): void
    {
        $parent = (string) $parentId;

        $children = Permission::query()
            ->where('permission_id', $parentId)
            ->where('active', true)
            ->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->all();

        if (in_array($parent, $this->permissions, true)) {
            $this->permissions = array_values(array_diff($this->permissions, [$parent], $children));
        } else {
            $this->permissions = array_values(array_unique(array_merge($this->permissions, [$parent], $children)));
        }
    }

    /* ---------------- ذخیره (پورت ۱:۱ store/update قدیمی) ---------------- */

    public function save(): void
    {
        $isEditing = $this->roleId !== null;

        auth('adminPanel')->user()?->can($isEditing ? 'roles.update' : 'roles.create') ?: abort(403);

        $role = $isEditing ? Role::findOrFail($this->roleId) : null;

        $this->validate([
            'permissions'   => 'array',
            'permissions.*' => [
                Rule::exists('permissions', 'id')->where(function ($query) {
                    $query->where('active', true);
                }),
            ],
            'title'         => $isEditing
                ? ['required', Rule::unique('roles', 'title')->ignore($role->id)]
                : 'required|unique:roles,title',
        ], [], [
            'title'       => 'عنوان',
            'permissions' => 'دسترسی‌ها',
        ]);

        // مثل HTTP قدیمی (ConvertEmptyStringsToNull): رشته خالی → null
        $this->description = $this->description !== null && $this->description !== '' ? $this->description : null;

        $permissionIds = array_values(array_map('intval', $this->permissions));

        // عبارت قدیمی عیناً حفظ شد (برای Admin نتیجه همیشه «مدیر» است)
        $adminName = auth('adminPanel')->user()->full_name
            ?? auth('adminPanel')->user()->name
            ?? 'مدیر';

        if ($role) {
            // پورت update قدیمی + لاگ دقیق old/new
            $oldTitle = $role->title;
            $oldDescription = $role->description;
            $oldPermissions = $role->permissions->pluck('id')->toArray();
            $oldPermissionNames = $role->permissions->pluck('title')->toArray();

            $role->update([
                'title'       => $this->title,
                'description' => $this->description,
            ]);

            $role->permissions()->sync($permissionIds);

            $newPermissionNames = Permission::whereIn('id', $permissionIds)->pluck('title')->toArray();

            $addedPermissions = array_diff($permissionIds, $oldPermissions);
            $removedPermissions = array_diff($oldPermissions, $permissionIds);

            $addedNames = Permission::whereIn('id', $addedPermissions)->pluck('title')->toArray();
            $removedNames = Permission::whereIn('id', $removedPermissions)->pluck('title')->toArray();

            $oldData = [];
            $newData = [];

            if ($oldTitle != $this->title) {
                $oldData['عنوان مقام'] = $oldTitle;
                $newData['عنوان مقام'] = $this->title;
            }

            if ($oldDescription != $this->description) {
                $oldData['توضیحات'] = $oldDescription ?: 'ندارد';
                $newData['توضیحات'] = $this->description ?: 'ندارد';
            }

            foreach ($removedNames as $name) {
                $oldData["دسترسی - {$name}"] = 'فعال';
                $newData["دسترسی - {$name}"] = 'غیرفعال';
            }

            foreach ($addedNames as $name) {
                $oldData["دسترسی - {$name}"] = 'غیرفعال';
                $newData["دسترسی - {$name}"] = 'فعال';
            }

            $properties = [
                'action'    => 'update_role',
                'role_id'   => $role->id,
                'old_title' => $oldTitle,
                'new_title' => $this->title,
                'ip'        => request()->ip(),
            ];

            if (! empty($oldData)) {
                $properties['old'] = $oldData;
                $properties['attributes'] = $newData;
            }

            activity()
                ->performedOn($role)
                ->causedBy(auth('adminPanel')->user())
                ->event('updated')
                ->withProperties($properties)
                ->log("مدیر {$adminName} مقام «{$oldTitle}» را ویرایش کرد");

            $message = 'مقام با موفقیت ویرایش شد.';
        } else {
            // پورت store قدیمی + لاگ دقیق
            $newRole = Role::create([
                'title'       => $this->title,
                'description' => $this->description,
            ]);

            $newRole->permissions()->attach($permissionIds);

            $permissionNames = Permission::whereIn('id', $permissionIds)->pluck('title')->toArray();

            $properties = [
                'action'     => 'create_role',
                'role_title' => $this->title,
                'attributes' => [
                    'عنوان مقام'       => $this->title,
                    'توضیحات'          => $this->description ?? 'ندارد',
                    'دسترسی‌ها'        => ! empty($permissionNames) ? implode('، ', $permissionNames) : 'هیچ',
                    'تعداد دسترسی‌ها'  => count($permissionNames),
                ],
                'ip'         => request()->ip(),
            ];

            activity()
                ->performedOn($newRole)
                ->causedBy(auth('adminPanel')->user())
                ->event('created')
                ->withProperties($properties)
                ->log("مدیر {$adminName} مقام جدید «{$this->title}» را ایجاد کرد");

            $message = 'مقام با موفقیت ایجاد شد.';
        }

        $this->showFormModal = false;
        $this->resetForm();

        unset($this->roles);

        $this->dispatch('toast', message: $message, type: 'success');
    }

    /* ---------------- حذف (پورت destroy قدیمی) ---------------- */

    public function confirmDelete(int $id): void
    {
        auth('adminPanel')->user()?->can('roles.delete') ?: abort(403);

        $this->confirmingDelete = $id;
    }

    public function delete(): void
    {
        auth('adminPanel')->user()?->can('roles.delete') ?: abort(403);

        if ($role = Role::find($this->confirmingDelete)) {
            $adminName = auth('adminPanel')->user()->full_name
                ?? auth('adminPanel')->user()->name
                ?? 'مدیر';

            $roleTitle = $role->title;
            $permissionNames = $role->permissions->pluck('title')->toArray();

            $role->permissions()->detach();
            $role->delete();

            $properties = [
                'action'     => 'delete_role',
                'role_title' => $roleTitle,
                'attributes' => [
                    'عنوان مقام'      => $roleTitle,
                    'دسترسی‌های مرتبط' => ! empty($permissionNames) ? implode('، ', $permissionNames) : 'هیچ',
                    'تعداد دسترسی‌ها' => count($permissionNames),
                ],
                'ip'         => request()->ip(),
            ];

            activity()
                ->causedBy(auth('adminPanel')->user())
                ->event('deleted')
                ->withProperties($properties)
                ->log("مدیر {$adminName} مقام «{$roleTitle}» را حذف کرد");
        }

        $this->confirmingDelete = null;

        unset($this->roles);

        // متن پیام عیناً از JS قدیمی
        $this->dispatch('toast', message: 'مقام با موفقیت حذف شد', type: 'success');
    }

    /* ---------------- ابزار ---------------- */

    public static function jdate($date, $format = 'Y/m/d'): string
    {
        if (! $date) {
            return '—';
        }

        return \Morilog\Jalali\Jalalian::fromDateTime($date)->format($format);
    }

    public function render()
    {
        return view('admin-panel::livewire.admin.admins.roles')
            ->layout('admin-panel::layout');
    }
}
