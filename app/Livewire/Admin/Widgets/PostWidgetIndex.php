<?php

namespace App\Livewire\Admin\Widgets;

use Livewire\Attributes\Title;

/**
 * پورت PostWidgetController::index — همان الگوی WidgetIndex ولی page='posts'
 * (route ساده: بدون پارامتر page)
 */
#[Title('ابزارک‌های مقالات')]
class PostWidgetIndex extends WidgetIndex
{
    public string $page = 'posts';

    public function routePrefix(): string
    {
        return 'np.posts-widgets';
    }

    public function pageTitle(): string
    {
        return 'ابزارک‌های مقالات';
    }

    public function pageLabel(): string
    {
        return 'مقالات';
    }

    public function builderRoute(): ?string
    {
        try {
            return route('np.widgets.posts-builder');
        } catch (\Throwable) {
            return null;
        }
    }

    /** ویوی مخصوص صفحه مقالات (مارک‌آپ مشترک با widgets.blade.php) */
    public function render()
    {
        return view('admin-panel::livewire.admin.widgets.posts-widgets')
            ->layout('admin-panel::layout');
    }
}
