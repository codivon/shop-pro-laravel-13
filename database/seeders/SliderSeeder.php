<?php

namespace Database\Seeders;

use App\Models\Slider;
use Faker\Factory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class SliderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $ordering = 1;

        Storage::disk('public')->makeDirectory('uploads/ckeditor/sliders');

        if (config('front.sliderGroups')) {
            $ordering = 1; // فراموش نکنید متغیر ordering رو تعریف کنید

            foreach (config('front.sliderGroups') as $pageName => $sliders) {
                // $pageName = 'home', 'posts', 'sellers'
                // $sliders = آرایه گروه‌های اسلایدر برای آن صفحه

                foreach ($sliders as $sliderGroup) {
                    // بررسی وجود کلید count
                    if (!isset($sliderGroup['count'])) {
                        continue; // یا لاگ خطا: logger()->warning("Missing 'count' for slider group: {$sliderGroup['group']}");
                    }

                    for ($i = 0; $i < $sliderGroup['count']; $i++) {
                        $image = '';

                        if (isset($sliderGroup['images'])) {
                            $demo_image_path = $sliderGroup['images'][$i] ?? $sliderGroup['images'][0];
                            $path = public_path('uploads/ckeditor/sliders/') . basename($demo_image_path);

                            // اطمینان از وجود پوشه مقصد
                            if (!File::isDirectory(dirname($path))) {
                                File::makeDirectory(dirname($path), 0755, true);
                            }

                            $image = substr($path, strpos($path, 'uploads'));

                            if (File::exists(theme_path($demo_image_path))) {
                                File::copy(theme_path($demo_image_path), $path);
                            }
                        }

                        Slider::create([
                            'pages' => [$pageName],
                            'link' => '#',
                            'title' => $sliderGroup['titles'][$i] ?? '',
                            'groups' => [$sliderGroup['group']],
                            'published' => true,
                            'image' => str_replace('public', '', $image),
                            'ordering' => $ordering++
                        ]);
                    }
                }
            }
        }
    }
}
