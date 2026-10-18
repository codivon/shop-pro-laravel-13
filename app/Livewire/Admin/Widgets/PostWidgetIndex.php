<?php

namespace App\Livewire\Admin\Widgets;

use Livewire\Attributes\Title;

/**
 * پورت PostWidgetController::index — همان الگوی WidgetIndex ولی page='posts'
 * (route ساده: بدون پارامتر page)
 */
#[Title('ابزارک‌های صفحه مقالات')]
class PostWidgetIndex extends WidgetIndex
{
    public string $page = 'posts';

    public function routePrefix(): string
    {
        return 'np.posts-widgets';
    }

    public function pageTitle(): string
    {
        return 'ابزارک‌های صفحه مقالات';
    }

    /** ویوی مخصوص صفحه مقالات (مارک‌آپ مشترک با widgets.blade.php) */
    public function render()
    {
        return view('admin-panel::livewire.admin.widgets.posts-widgets')
            ->layout('admin-panel::layout');
    }
}
