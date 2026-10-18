<?php

namespace Database\Seeders;

use App\Models\Banner;
use Faker\Factory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class BannerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Storage::disk('public')->makeDirectory('uploads/ckeditor/banners');

        if (config('front.bannerGroups')) {
            foreach (config('front.bannerGroups') as $pageName => $banners) {
                // $pageName = 'home', 'posts', 'sellers' و ...

                if (!is_array($banners)) {
                    continue;
                }

                foreach ($banners as $bannerGroup) {
                    // بررسی وجود کلیدهای ضروری
                    if (!isset($bannerGroup['group'], $bannerGroup['count'])) {
                        \Log::warning("Missing required keys for banner group in page: {$pageName}", $bannerGroup);
                        continue;
                    }

                    for ($i = 0; $i < $bannerGroup['count']; $i++) {
                        $image = '';

                        if (isset($bannerGroup['images'][$i])) {
                            $demo_image_path = $bannerGroup['images'][$i];
                            $path = public_path('uploads/ckeditor/banners/') . basename($demo_image_path);

                            // ایجاد پوشه در صورت عدم وجود
                            if (!File::isDirectory(dirname($path))) {
                                File::makeDirectory(dirname($path), 0755, true);
                            }

                            $image = substr($path, strpos($path, 'uploads'));

                            if (File::exists(theme_path($demo_image_path))) {
                                File::copy(theme_path($demo_image_path), $path);
                            }
                        }

                        Banner::create([
                            'pages' => [$pageName],
                            'link' => '#',
                            'groups' => [$bannerGroup['group']],
                            'published' => true,
                            'places' => [$bannerGroup['place'] ?? 'index_banners_place1'], // استفاده از place تعریف شده در config
                            'image' => str_replace('public', '', $image)
                        ]);
                    }
                }
            }
        }
    }
}
