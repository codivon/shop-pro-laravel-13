<?php

namespace App\Livewire\Admin\Users;

use App\Models\User;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Morilog\Jalali\Jalalian;

#[Title('بازدیدهای کاربر')]
class UserViews extends Component
{
    use WithPagination;

    public User $user;

    public int $perPage = 20;

    public function mount(User $user): void
    {
        auth('adminPanel')->user()?->can('users.view') ?: abort(403);

        $this->user = $user;
    }

    #[Computed]
    public function views()
    {
        return $this->user->views()->latest()->paginate($this->perPage);
    }

    public static function jdate($date, $format = 'Y/m/d H:i'): string
    {
        if (! $date) {
            return '—';
        }

        return Jalalian::fromDateTime($date)->format($format);
    }

    public function render()
    {
        return view('admin-panel::livewire.admin.users.views')
            ->layout('admin-panel::layout');
    }
}
