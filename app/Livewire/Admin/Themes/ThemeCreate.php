<?php

namespace App\Livewire\Admin\Themes;

use App\Providers\AppServiceProvider;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use Madnest\Madzipper\Madzipper;
use Throwable;

#[Title('افزودن قالب')]
class ThemeCreate extends Component
{
    use WithFileUploads;

    /** فایل zip قالب */
    public $file = null;

    /** درصد پیشرفت نمایشی هنگام پردازش (پورت نوار progress ویوی قدیمی) */
    public bool $processing = false;

    public function mount(): void
    {
        auth('adminPanel')->user()?->can('themes.create') ?: abort(403);
    }

    /* ---------------- ذخیره (پورت ۱:۱ store قدیمی) ---------------- */

    public function store(): void
    {
        auth('adminPanel')->user()?->can('themes.create') ?: abort(403);

        $this->validate([
            'file' => 'required|file|mimes:zip',
        ], [], [
            'file' => 'فایل قالب',
        ]);

        $this->processing = true;

        try {
            $uuid = uniqid();
            $path = Storage::disk('public')->path('uploads/tmp/');

            if (!File::exists($path)) {
                File::makeDirectory($path, 0755, true);
            }

            $name = $uuid . '.' . $this->file->getClientOriginalExtension();
            $this->file->storeAs('tmp', $name);

            $zipper = new Madzipper;
            $zipper->make($path . $name)->extractTo($path . $uuid);
            $zipper->close();

            File::delete($path . $name);

            $themes = Storage::disk('themes')->directories();
            $newTheme = Storage::disk('public')->directories('uploads/tmp/' . $uuid);
            $newTheme = substr($newTheme[0], strrpos($newTheme[0], '/') + 1);

            if (in_array($newTheme, $themes)) {
                File::deleteDirectory($path . $uuid);

                $this->processing = false;
                $this->dispatch('toast', message: 'این قالب قبلا آپلود شده است.', type: 'error');
                return;
            }

            File::moveDirectory($path . $uuid . '/' . $newTheme, Storage::disk('themes')->path($newTheme));
            File::deleteDirectory($path . $uuid);

            change_env('CURRENT_THEME', $newTheme);
            Artisan::call('dump-autoload');

            AppServiceProvider::loadTheme();
            Artisan::call('vendor:publish --tag="' . $newTheme . '" --force');
            Artisan::call('shop:link');

            $this->reset(['file']);
            $this->processing = false;

            $this->dispatch('toast', message: 'قالب «' . $newTheme . '» با موفقیت نصب و فعال شد.', type: 'success');
        } catch (Throwable $e) {
            $this->processing = false;
            report($e);
            $this->dispatch('toast', message: 'خطا در نصب قالب: ' . $e->getMessage(), type: 'error');
        }
    }

    public function render()
    {
        return view('admin-panel::livewire.admin.themes.create')
            ->layout('admin-panel::layout');
    }
}
