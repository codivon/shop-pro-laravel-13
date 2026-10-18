<?php

namespace App\Livewire\Admin\Auth;

use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('admin-panel::layouts.login')]
#[Title('ورود به پنل مدیریت')]
class Login extends Component
{
    public string $username = '';
    public string $password = '';
    public bool $remember = false;

    protected function rules(): array
    {
        return [
            'username' => ['required', 'string'],
            'password' => ['required', 'string', 'min:6'],
        ];
    }

    protected function messages(): array
    {
        return [
            'username.required' => 'نام کاربری الزامی است.',
            'password.required' => 'رمز عبور الزامی است.',
            'password.min' => 'رمز عبور باید حداقل ۶ کاراکتر باشد.',
        ];
    }

    public function mount()
    {
        if (Auth::guard('adminPanel')->check()) {
            return $this->redirectIntended(route('np.dashboard', absolute: false), navigate: true);
        }
    }

    public function authenticate()
    {
        $this->validate();

        $credentials = [
            'username' => $this->username,
            'password' => $this->password,
        ];

        // امکان ورود با ایمیل هم وجود داشته باشد
        if (!Auth::guard('adminPanel')->attempt($credentials, $this->remember)) {
            $credentials = [
                'email' => $this->username,
                'password' => $this->password,
            ];

            if (!Auth::guard('adminPanel')->attempt($credentials, $this->remember)) {
                throw ValidationException::withMessages([
                    'username' => 'اطلاعات ورود نادرست است.',
                ]);
            }
        }

        session()->regenerate();

        return $this->redirectIntended(route('np.dashboard', absolute: false), navigate: true);
    }

    public function render()
    {
        return view('admin-panel::livewire.admin.auth.login');
    }
}
