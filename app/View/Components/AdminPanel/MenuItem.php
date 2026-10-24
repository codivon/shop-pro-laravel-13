<?php

namespace App\View\Components\AdminPanel;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Illuminate\Support\Str;

/**
 * آیتم منوی سایدبار — چک دسترسی + آدرس + وضعیت فعال
 */
class MenuItem extends Component
{
    public string $url;
    public bool $isActive;
    public bool $allowed;

    public function __construct(
        public array $item,
    ) {
        $this->url = $this->resolveUrl();
        $this->isActive = $this->resolveActive();
        $this->allowed = $this->resolveAllowed();
    }

    protected function resolveUrl(): string
    {
        $route = $this->item['route'] ?? null;
        if (! $route) {
            return '#';
        }

        if (isset($this->item['external']) && $this->item['external']) {
            try {
                return route($route);
            } catch (\Throwable) {
                return '#';
            }
        }

        try {
            return route($route, $this->item['params'] ?? []);
        } catch (\Throwable) {
            return '#';
        }
    }

    protected function resolveActive(): bool
    {
        $route = $this->item['route'] ?? null;
        if (! $route || str_starts_with($route, 'admin.')) {
            return false; // لینک‌های موقت به پنل قدیمی هیچ‌وقت «فعال» نمی‌شوند
        }

        return request()->fullUrlIs($this->url . '*') || request()->url() === $this->url;
    }

    protected function resolveAllowed(): bool
    {
        // فقط بخش‌های برنامه‌نویس (بدون دسترسی تعریف‌شده) محدود می‌شوند
        if (isset($this->item['creator_only']) && $this->item['creator_only']) {
            $admin = auth('adminPanel')->user();

            return $admin && ($admin->id === 1 || $admin->level === 'creator');
        }

        $permission = $this->item['permission'] ?? null;
        if (! $permission) {
            return true;
        }

        $admin = auth('adminPanel')->user();

        return $admin && $admin->can($permission);
    }

    public function render(): View|Closure|string
    {
        return view('admin-panel::components.menu-item');
    }
}
