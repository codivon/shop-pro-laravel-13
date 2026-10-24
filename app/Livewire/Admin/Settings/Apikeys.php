<?php

namespace App\Livewire\Admin\Settings;

use App\Models\Apikey;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('لیست کلیدهای وب سرویس')]
class Apikeys extends Component
{
    /* ---------------- مودال ایجاد (پورت create/store قدیمی) ---------------- */

    public bool $showCreateModal = false;

    public string $createKey = '';

    public string $createDescription = '';

    public bool $createIsActive = true;

    /* ---------------- مودال ویرایش (پورت edit/update قدیمی) ---------------- */

    public ?int $editingId = null;

    public string $editKey = '';

    public string $editDescription = '';

    public bool $editIsActive = true;

    /* ---------------- حذف (پورت destroy قدیمی) ---------------- */

    public ?int $confirmingDelete = null;

    public function mount(): void
    {
        // پورت authorizeResource قدیمی — کلیدهای DB: apikeys.index/create/update/delete (مثل @can های ویو قدیمی)
        auth('adminPanel')->user()?->can('apikeys.index') ?: abort(403);
    }

    /* ---------------- لیست (پورت index قدیمی — بدون صفحه‌بندی مثل get() قدیمی) ---------------- */

    #[Computed]
    public function rows()
    {
        return Apikey::query()->latest()->get();
    }

    /* ---------------- ایجاد (پورت ۱:۱ store قدیمی) ---------------- */

    public function openCreateModal(): void
    {
        auth('adminPanel')->user()?->can('apikeys.create') ?: abort(403);

        $this->reset(['createDescription', 'showCreateModal']);
        // پورت value="{{ str_random(40) }}" فرم قدیمی
        $this->createKey = Str::random(40);
        $this->createIsActive = true;
        $this->showCreateModal = true;
    }

    public function regenerateCreateKey(): void
    {
        $this->createKey = Str::random(40);
    }

    public function createApikey(): void
    {
        auth('adminPanel')->user()?->can('apikeys.create') ?: abort(403);

        // رول‌های StoreApikeyRequest قدیمی (prepareForValidation: is_active = has('is_active'))
        $data = $this->validate([
            'createKey'         => 'required|string|unique:apikeys,key',
            'createDescription' => 'nullable|string',
            'createIsActive'    => 'boolean',
        ], [], [
            'createKey'         => 'کلید',
            'createDescription' => 'توضیحات',
            'createIsActive'    => 'وضعیت',
        ]);

        Apikey::create([
            'key'         => $data['createKey'],
            'description' => $data['createDescription'] ?: null,
            'is_active'   => $this->createIsActive,
        ]);

        $this->reset(['showCreateModal', 'createKey', 'createDescription', 'editingId']);
        $this->createIsActive = true;
        unset($this->rows);

        // متن دقیق توست قدیمی
        $this->dispatch('toast', message: 'کلید وب سرویس با موفقیت ایجاد شد.', type: 'success');
    }

    /* ---------------- ویرایش (پورت ۱:۱ edit/update قدیمی) ---------------- */

    public function editApikey(int $id): void
    {
        auth('adminPanel')->user()?->can('apikeys.update') ?: abort(403);

        $apikey = Apikey::find($id);

        if (! $apikey) {
            return;
        }

        $this->editingId        = $apikey->id;
        $this->editKey          = (string) $apikey->key;
        $this->editDescription  = (string) $apikey->description;
        $this->editIsActive     = (bool) $apikey->is_active;
    }

    public function updateApikey(): void
    {
        auth('adminPanel')->user()?->can('apikeys.update') ?: abort(403);

        $apikey = $this->editingId ? Apikey::find($this->editingId) : null;

        if (! $apikey) {
            return;
        }

        // رول‌های UpdateApikeyRequest قدیمی (unique با ignore خود ردیف)
        $data = $this->validate([
            'editKey'         => 'required|string|unique:apikeys,key,' . $apikey->id,
            'editDescription' => 'nullable|string',
            'editIsActive'    => 'boolean',
        ], [], [
            'editKey'         => 'کلید',
            'editDescription' => 'توضیحات',
            'editIsActive'    => 'وضعیت',
        ]);

        $apikey->update([
            'key'         => $data['editKey'],
            'description' => $data['editDescription'] ?: null,
            'is_active'   => $this->editIsActive,
        ]);

        $this->reset(['editingId', 'editKey', 'editDescription']);
        $this->editIsActive = true;
        unset($this->rows);

        // متن دقیق توست قدیمی
        $this->dispatch('toast', message: 'کلید وب سرویس با موفقیت ویرایش شد.', type: 'success');
    }

    /* ---------------- حذف (پورت ۱:۱ destroy قدیمی) ---------------- */

    public function confirmDelete(int $id): void
    {
        auth('adminPanel')->user()?->can('apikeys.delete') ?: abort(403);

        $this->confirmingDelete = $id;
    }

    public function delete(): void
    {
        auth('adminPanel')->user()?->can('apikeys.delete') ?: abort(403);

        $apikey = Apikey::find($this->confirmingDelete);

        if ($apikey) {
            $apikey->delete();
        }

        $this->confirmingDelete = null;
        unset($this->rows);

        $this->dispatch('toast', message: 'کلید وب سرویس با موفقیت حذف شد.', type: 'success');
    }

    /* ---------------- ابزار ---------------- */

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

    /** نمایش ماسک‌شده کلید (فقط ۴ کاراکتر اول و آخر) */
    public static function maskKey(string $key): string
    {
        if (mb_strlen($key) <= 8) {
            return $key;
        }

        return mb_substr($key, 0, 4) . str_repeat('•', 12) . mb_substr($key, -4);
    }

    public function render()
    {
        return view('admin-panel::livewire.admin.settings.apikeys')
            ->layout('admin-panel::layout');
    }
}
