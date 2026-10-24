<?php

namespace App\Livewire\Admin\Widgets;

use App\Models\Widget;
use Livewire\Attributes\Title;

/**
 * پورت PostWidgetController — همان الگوی WidgetForm ولی page='posts'
 * (route ساده: بدون پارامتر page)
 */
#[Title('فرم ابزارک صفحه مقالات')]
class PostWidgetForm extends WidgetForm
{
    public function mount(?Widget $widget = null, string $page = 'posts'): void
    {
        parent::mount($widget, 'posts');
    }

    /* ---------------- پورت ۱:۱ store/update قدیمی: ذخیره‌ی صریح page='posts' ---------------- */

    protected function creationPayload(): array
    {
        return array_merge(parent::creationPayload(), [
            'page' => 'posts',
        ]);
    }

    protected function updatePayload(): array
    {
        return array_merge(parent::updatePayload(), [
            'page' => 'posts',
        ]);
    }

    protected function logSubject(): string
    {
        return 'ابزارک صفحه مقالات';
    }

    protected function logAction(): string
    {
        return 'ابزارک صفحه مقالات را ویرایش کرد';
    }

    public function indexRoute(): string
    {
        return route('np.posts-widgets.index');
    }

    /** ویوی مخصوص صفحه مقالات (مارک‌آپ مشترک با widget-form.blade.php) */
    public function render()
    {
        return view('admin-panel::livewire.admin.widgets.posts-widget-form')
            ->layout('admin-panel::layout');
    }
}
