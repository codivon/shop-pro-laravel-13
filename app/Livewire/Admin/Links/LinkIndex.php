<?php

namespace App\Livewire\Admin\Links;

use App\Models\Link;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Illuminate\Validation\ValidationException;

/**
 * مدیریت لینک‌های فوتر — پورت ۱:۱ LinkController@index/create/store/edit/update/destroy/sort.
 *
 * مثل قدیمی بدون صفحه‌بندی (تعداد کم) و گروه‌بندی نمایش بر اساس config('front.linkGroups')
 * با عنوان‌های option('link_groups_{key}').
 *
 * انحراف مستند: مرتب‌سازی درگ‌انددراپ (jquery-ui sortable + POST /links/sort) با دکمه‌های
 * ↑↓ درون هر گروه جایگزین شد — جابه‌جایی مقدار ordering بین دو همسایه همان گروه
 * (authorize با links.update مثل متد sort قدیمی).
 */
#[Title('لینک‌ها')]
class LinkIndex extends Component
{
    /* ---------------- کارت «لینک جدید» (پورت create/store قدیمی) ---------------- */

    public string $createTitle = '';

    public string $createLink = '';

    /** @var int|string کلید گروه انتخابی */
    public $createGroupId = '';

    /* ---------------- مودال ویرایش (پورت edit/update قدیمی) ---------------- */

    public ?int $editingLinkId = null;

    public string $editTitle = '';

    public string $editLink = '';

    /** @var int|string کلید گروه انتخابی در ویرایش */
    public $editGroupId = '';

    /* ---------------- حذف ---------------- */

    public ?int $confirmingDelete = null;

    public function mount(): void
    {
        auth('adminPanel')->user()?->can('links.index') ?: abort(403);
    }

    /* ---------------- لیست (پورت index قدیمی — بدون صفحه‌بندی) ---------------- */

    /** کلیدها/عناوین گروه‌ها (config + گروه‌های افزوده‌شده — قرارداد LinkGroups) */
    #[Computed]
    public function groups(): array
    {
        return LinkGroups::allGroups();
    }

    #[Computed]
    public function links()
    {
        return Link::query()
            ->detectLang()
            ->orderBy('ordering')
            ->orderBy('id')
            ->get();
    }

    /** لینک‌های یک گروه (مثل $links->where('link_group_id', $group['key']) قدیمی) */
    public function linksInGroup(int|string $key)
    {
        return $this->links->where('link_group_id', $key)->values();
    }

    public function groupTitle(int|string $key): string
    {
        return LinkGroups::groupTitle($key);
    }

    /* ---------------- ایجاد (پورت ۱:۱ store قدیمی) ---------------- */

    public function createLink(): void
    {
        auth('adminPanel')->user()?->can('links.create') ?: abort(403);

        try {
            $this->validate([
                'createTitle'   => 'required',
                'createLink'    => 'required',
                'createGroupId' => 'required',
            ], [], [
                'createTitle'   => 'عنوان',
                'createLink'    => 'لینک',
                'createGroupId' => 'گروه',
            ]);
        } catch (ValidationException $e) {
            $first = collect($e->errors())->flatten()->first();

            $this->dispatch('toast', message: $first ?? 'لطفا خطاهای فرم را برطرف کنید.', type: 'error');

            throw $e;
        }

        Link::create([
            'title'         => $this->createTitle,
            'link'          => $this->createLink,
            'link_group_id' => $this->createGroupId,
            'lang'          => app()->getLocale(),
            'ordering'      => Link::max('ordering') + 1,
        ]);

        $this->reset(['createTitle', 'createLink', 'createGroupId']);

        $this->dispatch('toast', message: 'لینک با موفقیت ایجاد شد.', type: 'success');
    }

    /* ---------------- ویرایش (پورت ۱:۱ update قدیمی) ---------------- */

    public function editLink(int $id): void
    {
        auth('adminPanel')->user()?->can('links.update') ?: abort(403);

        $link = Link::find($id);

        if (! $link) {
            return;
        }

        $this->editingLinkId = $link->id;
        $this->editTitle = (string) $link->title;
        $this->editLink = (string) $link->link;
        $this->editGroupId = (string) $link->link_group_id;
    }

    public function updateLink(): void
    {
        auth('adminPanel')->user()?->can('links.update') ?: abort(403);

        try {
            $this->validate([
                'editTitle'   => 'required',
                'editLink'    => 'required',
                'editGroupId' => 'required',
            ], [], [
                'editTitle'   => 'عنوان',
                'editLink'    => 'لینک',
                'editGroupId' => 'گروه',
            ]);
        } catch (ValidationException $e) {
            $first = collect($e->errors())->flatten()->first();

            $this->dispatch('toast', message: $first ?? 'لطفا خطاهای فرم را برطرف کنید.', type: 'error');

            throw $e;
        }

        $link = $this->editingLinkId ? Link::find($this->editingLinkId) : null;

        if ($link) {
            // مثل قدیمی ordering دست‌نخورده می‌ماند
            $link->update([
                'title'         => $this->editTitle,
                'link'          => $this->editLink,
                'link_group_id' => $this->editGroupId,
            ]);

            $this->dispatch('toast', message: 'لینک با موفقیت ویرایش شد.', type: 'success');
        }

        $this->reset(['editingLinkId', 'editTitle', 'editLink', 'editGroupId']);
    }

    /* ---------------- حذف (پورت ۱:۱ destroy قدیمی) ---------------- */

    public function confirmDelete(int $id): void
    {
        auth('adminPanel')->user()?->can('links.delete') ?: abort(403);

        $this->confirmingDelete = $id;
    }

    public function delete(): void
    {
        auth('adminPanel')->user()?->can('links.delete') ?: abort(403);

        $link = Link::find($this->confirmingDelete);

        if ($link) {
            if ($link->image) {
                Storage::disk('public')->delete($link->image);
            }

            $link->delete();
        }

        $this->confirmingDelete = null;

        $this->dispatch('toast', message: 'لینک با موفقیت حذف شد.', type: 'success');
    }

    /* ---------------- مرتب‌سازی ↑↓ درون گروه (جایگزین درگ‌انددراپ sort) ---------------- */

    public function moveUp(int $id): void
    {
        $this->moveLink($id, -1);
    }

    public function moveDown(int $id): void
    {
        $this->moveLink($id, 1);
    }

    /**
     * جابه‌جایی مقدار ordering با همسایه بالا/پایین «درون همان گروه»
     * (authorize با links.update مثل متد sort قدیمی).
     */
    protected function moveLink(int $id, int $direction): void
    {
        auth('adminPanel')->user()?->can('links.update') ?: abort(403);

        $link = Link::find($id);

        if (! $link) {
            return;
        }

        $siblings = Link::detectLang()
            ->where('link_group_id', $link->link_group_id)
            ->orderBy('ordering')
            ->orderBy('id')
            ->get(['id', 'ordering']);

        if ($siblings->count() < 2) {
            return;
        }

        $from = $siblings->search(fn ($row) => (int) $row->id === $link->id);

        if ($from === false) {
            return;
        }

        $to = $from + $direction;

        if ($to < 0 || $to >= $siblings->count()) {
            return; // ابتدا/انتهای گروه — بدون تغییر
        }

        $current = $siblings[$from];
        $neighbor = $siblings[$to];

        Link::whereKey($current->id)->update(['ordering' => $neighbor->ordering]);
        Link::whereKey($neighbor->id)->update(['ordering' => $current->ordering]);

        $this->dispatch('toast', message: 'ترتیب لینک‌ها ذخیره شد.', type: 'success');
    }

    public function render()
    {
        return view('admin-panel::livewire.admin.links.links')
            ->layout('admin-panel::layout');
    }
}
