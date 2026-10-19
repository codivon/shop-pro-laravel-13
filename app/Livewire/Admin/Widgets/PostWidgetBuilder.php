<?php

namespace App\Livewire\Admin\Widgets;

use Livewire\Attributes\Title;

/**
 * فاز ۱۱ — ویجت‌ساز صفحه مقالات (همان تجربه‌ی ویجت‌ساز اصلی، برای page='posts')
 * پیش‌نمایش روی /blog و ابزارک‌های مخصوص مقالات.
 */
#[Title('ویجت‌ساز مقالات')]
class PostWidgetBuilder extends WidgetBuilder
{
    public string $page = 'posts';

    public function pageTitle(): string
    {
        return 'ویجت‌ساز مقالات';
    }

    public function pageLabel(): string
    {
        return 'مقالات';
    }

    public function previewBaseUrl(): string
    {
        return url('/blog');
    }

    public function indexRoute(): string
    {
        return route('np.posts-widgets.index');
    }
}
