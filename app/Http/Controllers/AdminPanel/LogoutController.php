<?php

namespace App\Http\Controllers\AdminPanel;

use Illuminate\Support\Facades\Auth;

/**
 * خروج از پنل مدیریت جدید
 */
class LogoutController
{
    public function __invoke()
    {
        Auth::guard('adminPanel')->logout();
        session()->invalidate();
        session()->regenerateToken();

        return redirect()->route('np.login');
    }
}
