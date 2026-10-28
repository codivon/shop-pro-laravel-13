<?php

namespace App\View\Components\AdminPanel;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

/**
 * فاز ۱۱۴ — مودال مشترک انتخابگر آیکون
 * فقط یک‌بار به‌ازای هر صفحه رندر می‌شود (گارد $rendered) و
 * با رخداد open-ap-icon-picker روی هر دکمهٔ x-admin-panel::icon-picker باز می‌شود.
 */
class IconPickerModal extends Component
{
    public static bool $rendered = false;

    public function render(): View
    {
        return view('admin-panel.components.icon-picker-modal');
    }
}
