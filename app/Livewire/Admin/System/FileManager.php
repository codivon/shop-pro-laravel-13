<?php

namespace App\Livewire\Admin\System;

use Illuminate\Support\Facades\Route;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * مدیریت فایل‌ها — پورت MainController::fileManager
 *
 * محتوای اصلی از بین نرفته است: همان iframe فایل‌منیجر (file-manager-iframe قدیمی)
 * داخل قالب جدید رندر می‌شود. آدرس iframe اولویتاً np.file-manager-iframe است و
 * اگر روت هنوز ثبت نشده باشد به روت قدیمی admin.file-manager-iframe (که سالم
 * مانده) اشاره می‌کند.
 */
#[Title('مدیریت فایل‌ها')]
class FileManager extends Component
{
    public function mount(): void
    {
        auth('adminPanel')->user()?->can('file-manager') ?: abort(403);
    }

    /** آدرس iframe فایل‌منیجر (np جدید یا روت قدیمی) */
    public function getIframeUrlProperty(): string
    {
        if (Route::has('np.file-manager-iframe')) {
            return route('np.file-manager-iframe');
        }

        // روت قدیمی پنل (admin-old/...) همچنان سرو می‌شود — محتوای اصلی حفظ است
        return route('admin.file-manager-iframe');
    }

    public function render()
    {
        return view('admin-panel::livewire.admin.system.file-manager')
            ->layout('admin-panel::layout');
    }
}
