<?php

namespace App\Livewire\Admin\Support;

use App\Models\Contact;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Morilog\Jalali\Jalalian;

/**
 * تماس با ما — پورت ۱:۱ Back\ContactController (index/show/destroy)
 * دسترسی: contacts.index / contacts.view / contacts.delete (پورت ContactPolicy قدیمی)
 */
#[Title('تماس با ما')]
class ContactIndex extends Component
{
    use WithPagination;

    public int $perPage = 15;

    /* ---------------- مودال جزئیات (پورت show قدیمی) ---------------- */

    public bool $showDetail = false;

    public ?int $viewingContact = null;

    /* ---------------- حذف (پورت destroy قدیمی) ---------------- */

    public ?int $confirmingDelete = null;

    public function mount(): void
    {
        auth('adminPanel')->user()?->can('contacts.index') ?: abort(403);
    }

    /* ---------------- لیست (پورت index قدیمی) ---------------- */

    #[Computed]
    public function contacts()
    {
        return Contact::query()
            ->latest()
            ->paginate($this->perPage);
    }

    #[Computed(persist: true, seconds: 60)]
    public function totalCount(): int
    {
        return Contact::count();
    }

    /* ---------------- مودال جزئیات (پورت show قدیمی — gate contacts.view) ---------------- */

    public function openDetail(int $id): void
    {
        auth('adminPanel')->user()?->can('contacts.view') ?: abort(403);

        if (! Contact::find($id)) {
            $this->dispatch('toast', message: 'پیام یافت نشد.', type: 'error');

            return;
        }

        $this->viewingContact = $id;
        $this->showDetail = true;
    }

    #[Computed]
    public function detailContact(): ?Contact
    {
        if (! $this->viewingContact) {
            return null;
        }

        return Contact::find($this->viewingContact);
    }

    /* ---------------- حذف (پورت destroy قدیمی — gate contacts.delete) ---------------- */

    public function confirmDelete(int $id): void
    {
        auth('adminPanel')->user()?->can('contacts.delete') ?: abort(403);

        $this->confirmingDelete = $id;
    }

    public function delete(): void
    {
        auth('adminPanel')->user()?->can('contacts.delete') ?: abort(403);

        if ($contact = Contact::find($this->confirmingDelete)) {
            $contact->delete();
        }

        $this->confirmingDelete = null;

        if ($this->viewingContact !== null && ! Contact::find($this->viewingContact)) {
            $this->showDetail = false;
            $this->viewingContact = null;
        }

        unset($this->contacts);
        unset($this->totalCount);

        $this->dispatch('toast', message: 'پیام با موفقیت حذف شد.', type: 'success');
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
        return view('admin-panel::livewire.admin.support.contacts')
            ->layout('admin-panel::layout');
    }
}
