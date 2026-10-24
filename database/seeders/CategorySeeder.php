<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run()
    {
        $categories = [
            'کالای دیجیتال' => [
                'گوشی موبایل' => ['اپل', 'سامسونگ', 'شیائومی', 'نوکیا', 'هواوی', 'انر'],
                'لوازم جانبی گوشی' => [],
                'واقعیت مجازی' => [],
                'مچ بند و ساعت هوشمند' => [],
                'هدفون، هدست، هندزفری' => [],
                'اسپیکر بلوتوث و با سیم' => [],
                'دوربین' => [],
                'لپ تاپ' => ['ایسوس', 'ایسر'],
            ],
            'خودرو، ابزار و تجهیزات صنعتی' => [
                'خودرو ایرانی و خارجی' => [],
                'متورسیکلت' => [],
            ],
            'مد و پوشاک' => [],
            'کالاهای سوپرمارکتی' => [],
            'اسباب بازی، کودک و نوزاد' => [],
            'محصولات بومی و محلی' => [],
            'زیبایی و سلامت' => [],
            'خانه و آشپزخانه' => [],
            'کتاب، لوازم تحریر و هنر' => [],
            'ورزش و سفر' => [],
        ];

        $ordering = 1;
        $this->createCategories($categories, null, $ordering);

        $this->seedPostCategories();
    }

    /**
     * ایجاد دسته‌بندی‌های محصولات
     */
    private function createCategories(array $categories, $parentId = null, &$ordering)
    {
        foreach ($categories as $title => $children) {
            // ایجاد دسته اصلی
            $category = Category::create([
                'title' => $title,
                'slug' => sluggable_helper_function($title),
                'category_id' => $parentId,
                'type' => 'productcat',
                'ordering' => $ordering++,
                'show_in_index' => 1,
            ]);

            // اگر زیردسته‌ها وجود دارند
            if (is_array($children) && !empty($children)) {
                // بررسی می‌کنیم که آیا کلیدها عددی هستند (آرایه ساده از مقادیر)
                if ($this->isSequentialArray($children)) {
                    // آرایه ساده از مقادیر - زیردسته‌ها را ایجاد می‌کنیم
                    foreach ($children as $childTitle) {
                        if (is_string($childTitle)) {
                            Category::create([
                                'title' => $childTitle,
                                'slug' => sluggable_helper_function($childTitle),
                                'category_id' => $category->id,
                                'type' => 'productcat',
                                'ordering' => $ordering++,
                                'show_in_index' => 1,
                            ]);
                        }
                    }
                } else {
                    // آرایه تو در تو - بازگشت به حلقه
                    $this->createCategories($children, $category->id, $ordering);
                }
            }
        }
    }

    /**
     * بررسی می‌کند که آیا آرایه یک آرایه ساده با کلیدهای عددی متوالی است
     */
    private function isSequentialArray(array $array): bool
    {
        if (empty($array)) {
            return false;
        }

        // بررسی می‌کنیم که آیا کلیدها عددی و متوالی هستند
        return array_keys($array) === range(0, count($array) - 1);
    }

    /**
     * ایجاد دسته‌بندی‌های پست
     */
    private function seedPostCategories()
    {
        $postCategories = [
            'خانه' => [
                'راهنمای خرید',
                'نقد و بررسی',
                'ویدیو',
            ],
            'تکنولوژی و کالای دیجیتال' => [
                'کالای الکترونیک',
                'موبایل',
                'لپ‌تاپ',
                'ساعت و دستبند هوشمند',
                'دوربین عکاسی و فیلم‌برداری',
                'اپلیکیشن و نرم‌افزار',
                'آموزش و ترفند',
                'مقایسه گجت',
                'هوا فضا و نجوم',
                'خبر تکنولوژی',
            ],
            'بازی و سرگرمی' => [
                'بررسی بازی',
                'گجت بازی',
                'خبر بازی',
                'تریلر بازی',
                'موزه بازی',
                'اسباب بازی',
            ],
            'هنر و کتاب' => [
                'فیلم و سریال',
                'تئاتر و هنرهای تجسمی',
                'راهنمای خرید کتاب',
                'نقد کتاب',
                'لوازم التحریر',
                'موسیقی',
                'فرش و صنایع دستی',
            ],
            'مد و سبک زندگی' => [
                'مد و پوشاک',
                'آرایشی و بهداشتی',
                'طلا و جواهرات',
                'ورزش و تناسب اندام',
            ],
            'خانه و دکوراسیون' => [
                'خانه و آشپزخانه',
                'دکوراتیو',
                'مادر و کودک',
                'گل و گیاه',
                'خودرو و حمل‌و‌نقل',
            ],
            'سلامتی و تندرستی' => [
                'سلامت جسم',
                'سلامت روان',
                'دارو و مکمل',
                'ورزش و تناسب اندام',
                'آشپزی و تغذیه',
            ],
            'سرمایه‌گذاری' => [],
        ];

        $ordering = 1000; // شروع از ۱۰۰۰ برای جلوگیری از تداخل با دسته‌بندی محصولات

        // تولید اسلاگ یکتا برای دسته‌بندی‌های بلاگ (جلوگیری از تداخل با دسته‌های محصول و تکرارها)
        $uniquePostSlug = function (string $title) {
            $base = 'blog-' . sluggable_helper_function($title);
            $slug = $base;
            $i = 2;
            while (Category::where('slug', $slug)->exists()) {
                $slug = $base . '-' . $i++;
            }
            return $slug;
        };

        foreach ($postCategories as $parentTitle => $children) {
            // ایجاد دسته اصلی
            $parent = Category::create([
                'title' => $parentTitle,
                'slug' => $uniquePostSlug($parentTitle),
                'type' => 'postcat',
                'image' => '',
                'ordering' => $ordering++,
            ]);

            // ایجاد زیردسته‌ها
            if (!empty($children)) {
                foreach ($children as $childTitle) {
                    Category::create([
                        'title' => $childTitle,
                        'slug' => $uniquePostSlug($childTitle),
                        'category_id' => $parent->id,
                        'type' => 'postcat',
                        'image' => '',
                        'show_in_index' => 1,
                        'ordering' => $ordering++,
                    ]);
                }
            }
        }
    }
}
