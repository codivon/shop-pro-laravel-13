<?php

namespace App\View\Components\AdminPanel;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

/**
 * رندر آیکون‌های Lucide به صورت inline SVG
 * نمونه استفاده: <x-admin-panel::icon name="users" class="size-4" />
 */
class Icon extends Component
{
    public static ?array $map = null;

    public function __construct(
        public string $name,
        public string $class = 'size-5',
        public int $strokeWidth = 2,
    ) {
    }

    public function render(): View|Closure|string
    {
        if (self::$map === null) {
            self::$map = require resource_path('views/admin-panel/icons-map.php');
        }

        $inner = self::$map[$this->name] ?? self::$map['circle-alert'] ?? '';

        return '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" '
            . 'fill="none" stroke="currentColor" stroke-width="' . $this->strokeWidth . '" '
            . 'stroke-linecap="round" stroke-linejoin="round" '
            . 'class="' . $this->class . '" aria-hidden="true">' . $inner . '</svg>';
    }
}
