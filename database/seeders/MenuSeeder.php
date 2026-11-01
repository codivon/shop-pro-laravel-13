<?php

namespace Database\Seeders;

use App\Models\Menu;
use Illuminate\Database\Seeder;

class MenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $ordering = 1;

        /*
         * فاز ۱۳۹ — id والد را هاردکد نکن: قبلاً فرزندها با menu_id='1' ساخته می‌شدند
         * که فقط روی جدولِ کاملاً خالی درست بود؛ روی هر دیتابیس غیر-بدوی (نصب مجدد،
         * اجرای دوبارهٔ سیدر، دیتابیس‌های با رکورد قبلی) کلید خارجی menus_menu_id_foreign
         * می‌شکست و کل سیدر با Integrity constraint violation ناکام می‌شد.
         */
        $megamenu = Menu::create([
            'title'       => 'دسته بندی کالاها',
            'type'        => 'megamenu',
            'link'        => '/search',
            'icon'        => 'mdi mdi-menu',
            'ordering'    => $ordering++,
        ]);

        Menu::create([
            'title'       => 'کالای دیجیتال',
            'type'        => 'category',
            'static_type' => 'products',
            'icon'        => 'fa fa-laptop',
            'menu_id'     => $megamenu->id,
            'menuable_id'     => '1',
            'children'     => '1',
            'ordering'    => $ordering++,
        ]);
        Menu::create([
            'title'       => 'خودرو، ابزار و تجهیزات صنعتی',
            'type'        => 'category',
            'static_type' => 'products',
            'icon'        => 'fa fa-wrench',
            'menu_id'     => $megamenu->id,
            'menuable_id' => '18',
            'children'    => '1',
            'ordering'    => $ordering++,
        ]);
        Menu::create([
            'title'       => 'مد و پوشاک',
            'type'        => 'category',
            'static_type' => 'products',
            'icon'        => 'fa-solid fa-shirt',
            'menu_id'     => $megamenu->id,
            'menuable_id' => '21',
            'children'    => '1',
            'ordering'    => $ordering++,
        ]);
        Menu::create([
            'title'       => 'کالاهای سوپرمارکتی',
            'type'        => 'category',
            'static_type' => 'products',
            'icon'        => 'fa fa-basket-shopping',
            'menu_id'     => $megamenu->id,
            'menuable_id' => '22',
            'children'    => '1',
            'ordering'    => $ordering++,
        ]);
        Menu::create([
            'title'       => 'اسباب بازی، کودک و نوزاد',
            'type'        => 'category',
            'static_type' => 'products',
            'icon'        => 'fa-solid fa-person-breastfeeding',
            'menu_id'     => $megamenu->id,
            'menuable_id' => '23',
            'children'    => '1',
            'ordering'    => $ordering++,
        ]);
        Menu::create([
            'title'       => 'محصولات بومی و محلی',
            'type'        => 'category',
            'static_type' => 'products',
            'icon'        => 'fa-brands fa-square-pied-piper',
            'menu_id'     => $megamenu->id,
            'menuable_id' => '24',
            'children'    => '1',
            'ordering'    => $ordering++,
        ]);
        Menu::create([
            'title'       => 'زیبایی و سلامت',
            'type'        => 'category',
            'static_type' => 'products',
            'icon'        => 'fa-solid fa-heart',
            'menu_id'     => $megamenu->id,
            'menuable_id' => '25',
            'children'    => '1',
            'ordering'    => $ordering++,
        ]);
        Menu::create([
            'title'       => 'خانه و آشپزخانه',
            'type'        => 'category',
            'static_type' => 'products',
            'icon'        => 'fa fa-laptop',
            'menu_id'     => $megamenu->id,
            'menuable_id' => '26',
            'children'    => '1',
            'ordering'    => $ordering++,
        ]);
        Menu::create([
            'title'       => 'کتاب، لوازم تحریر و هنر',
            'type'        => 'category',
            'static_type' => 'products',
            'icon'        => 'fa fa-couch',
            'menu_id'     => $megamenu->id,
            'menuable_id' => '27',
            'children'    => '1',
            'ordering'    => $ordering++,
        ]);
        Menu::create([
            'title'       => 'ورزش و سفر',
            'type'        => 'category',
            'static_type' => 'products',
            'icon'        => 'fa-solid fa-tent',
            'menu_id'     => $megamenu->id,
            'menuable_id' => '28',
            'children'    => '1',
            'ordering'    => $ordering++,
        ]);
        Menu::create([
            'title'       => 'تخفیف و پیشنهاد ها',
            'type'        => 'normal',
            'link'        => '/product/discount',
            'icon'        => 'mdi mdi-sale text-danger',
            'ordering'    => $ordering++,
        ]);

        Menu::create([
            'title'       => 'مطالب',
            'type'        => 'normal',
            'icon'        => 'mdi mdi-script',
            'link'        => '/blog',
            'ordering'    => $ordering++,
        ]);
        Menu::create([
            'title'       => 'تماس با ما',
            'type'        => 'normal',
            'icon'        => 'mdi mdi-contact-mail',
            'link'        => '/contact',
            'ordering'    => $ordering++,
        ]);

        Menu::create([
            'title'       => 'فروشنده شوید',
            'type'        => 'normal',
            'icon'        => 'mdi mdi-marker-check text-success',
            'link'        => '/seller',
            'ordering'    => $ordering++,
        ]);

    }
}
