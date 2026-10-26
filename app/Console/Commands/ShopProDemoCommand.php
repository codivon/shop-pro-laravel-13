<?php

namespace App\Console\Commands;

use App\Models\Admin;
use App\Models\Category;
use App\Models\Page;
use App\Models\Post;
use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * فاز ۸۵ نقشهٔ راه — «دموی آمادهٔ نصب»
 *
 * با یک دستور، نصب تازهٔ فروشگاه را به یک فروشگاه نمونهٔ قابل مرور تبدیل می‌کند:
 *  • ۱۰ محصول نمونهٔ فارسی (شامل یک محصول دانلودی و یک پیش‌فروش) با قیمت/تخفیف/موجودی
 *  • ۳ مقالهٔ وبلاگ + ۲ صفحهٔ ثابت (دربارهٔ ما / تماس با ما)
 *  • مدیر دمو (در نصب خالی) با مشخصات مستند
 *
 * گاردها:
 *  • پرچم option('shoppro_demo_installed') — اجرای تکراری را می‌بندد
 *  • اگر محصول غیردمویی موجود باشد دست نمی‌زند (مگر --force)
 *  • در محیط production بدون --force تأیید می‌گیرد
 */
class ShopProDemoCommand extends Command
{
    protected $signature = 'shoppro:demo
                            {--force : اجرای اجباری حتی در محیط production یا با دادهٔ موجود}
                            {--admin-password=demo1234 : گذرواژهٔ مدیر دمو}';

    protected $description = 'نصب دموی آمادهٔ فروشگاه (محصولات، مقالات، صفحات و مدیر نمونه — فاز ۸۵ زیرساخت فروش)';

    /** پرچم ذخیرهٔ نصب دمو در جدول options */
    public const FLAG = 'shoppro_demo_installed';

    public function handle(): int
    {
        // ---------- گارد ۱: اجرای تکراری ----------
        if (option(self::FLAG) && ! $this->option('force')) {
            $this->warn('دمو قبلاً نصب شده است. برای بازنویسی از --force استفاده کنید.');

            return self::FAILURE;
        }

        // ---------- گارد ۲: دادهٔ موجود ----------
        $existingProducts = Product::count();

        if ($existingProducts > 0 && ! $this->option('force')) {
            // اگر هیچ فروشی ثبت نشده باشد یعنی نصب تازه است (محصولات فیک سیدر پایه) —
            // این محصولات جایگزین دموی منتخب می‌شوند؛ در غیر این صورت فروشگاه واقعی است.
            $hasSales = DB::table('order_items')->whereIn('product_id', Product::select('id'))->exists();

            if ($hasSales) {
                $this->warn("فروشگاه شما {$existingProducts} محصول با سابقهٔ فروش دارد؛ دستور دمو برای نصب تازه است.");
                $this->line('برای افزودن دادهٔ نمونه به دادهٔ موجود از --force استفاده کنید.');

                return self::FAILURE;
            }

            $this->line("حذف {$existingProducts} محصول نمونهٔ سیدر پایه (بدون سابقهٔ فروش) و جایگزینی با دموی منتخب...");

            Product::query()->orderBy('id')->chunkById(100, function ($products) {
                foreach ($products as $product) {
                    $product->delete();
                }
            });
        }

        // ---------- گارد ۳: محیط production ----------
        if (app()->environment('production') && ! $this->option('force') && ! $this->confirm('محیط production است. دمو نصب شود؟')) {
            return self::FAILURE;
        }

        $this->info('در حال نصب دموی فروشگاه...');

        DB::beginTransaction();

        try {
            $categories = $this->pickCategories();
            $demoAdmin  = $this->seedDemoAdmin();
            $products   = $this->seedProducts($categories);
            $this->seedPosts($demoAdmin?->id);
            $this->seedPages();

            option_update(self::FLAG, 1);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('خطا در نصب دمو: '.$e->getMessage());
            report($e);

            return self::FAILURE;
        }

        // ---------- خلاصهٔ نهایی ----------
        $this->newLine();
        $this->info('✅ دموی فروشگاه نصب شد.');
        $this->table(['مورد', 'تعداد'], [
            ['محصولات نمونه', $products.' (شامل دانلودی و پیش‌فروش)'],
            ['مقالات وبلاگ', 3],
            ['صفحات ثابت', 2],
            ['مدیر دمو', Admin::where('email', 'demo@shoppro.local')->exists() ? 'demo@shoppro.local' : 'موجود (از قبل)'],
        ]);

        $this->newLine();
        $this->line('مشخصات ورود مدیر دمو:  <comment>demo@shoppro.local</comment> / <comment>'.$this->option('admin-password').'</comment>');
        $this->line('فرانت فروشگاه: <comment>'.url('/').'</comment>');
        $this->line('پنل مدیریت: <comment>'.url('/'.config('admin.route.prefix', 'admin')).'</comment>');
        $this->newLine();
        $this->line('برای مرور کامل امکانات: سبد خرید، تسویه‌حساب API (docs/API-DOCUMENTATION.md) و پکیج‌های فعال را ببینید.');

        return self::SUCCESS;
    }

    /**
     * انتخاب دسته‌های مبنای محصولات نمونه از درخت سیدشده.
     * اگر دسته‌ای پیدا نشد، اولین دستهٔ منتشرشده به‌عنوان fallback استفاده می‌شود.
     */
    private function pickCategories(): array
    {
        $wanted = [
            'گوشی موبایل',
            'لپ تاپ',
            'هدفون، هدست، هندزفری',
            'لوازم جانبی گوشی',
            'مد و پوشاک',
            'خانه و آشپزخانه',
            'ورزش و سفر',
            'کتاب، لوازم تحریر و هنر',
        ];

        $found = [];

        foreach ($wanted as $title) {
            $category = Category::published()->where('title', $title)->first();

            if ($category) {
                $found[$title] = $category->id;
            }
        }

        if (! count($found)) {
            $first = Category::published()->orderBy('id')->first();
            if (! $first) {
                throw new \RuntimeException('هیچ دسته‌بندی‌ای وجود ندارد؛ ابتدا db:seed را اجرا کنید.');
            }

            $found['عمومی'] = $first->id;
        }

        return $found;
    }

    /**
     * محصولات نمونهٔ فارسی — آینهٔ رفتار فروشگاه واقعی:
     * قیمت‌های چندگانه، تخفیف با انقضا، ویژه، دانلودی و پیش‌فروش.
     */
    private function seedProducts(array $categories): int
    {
        // [title, category, type, basePrice, discountPercent, stock, special, preOrder]
        $catalog = [
            ['گوشی هوشمند نووا X5 حافظهٔ ۲۵۶ گیگابایت', 'گوشی موبایل', 'physical', 24500000, 15, 30, true, false],
            ['گوشی هوشمند نووا لایت حافظهٔ ۱۲۸ گیگابایت', 'گوشی موبایل', 'physical', 13900000, 0, 45, false, false],
            ['لپ‌تاپ ایربوک ۱۴ اینچی مدل پرو', 'لپ تاپ', 'physical', 58900000, 10, 12, false, false],
            ['هدفون بی‌سیم ساندوبیت مدل Air', 'هدفون، هدست، هندزفری', 'physical', 1850000, 25, 80, true, false],
            ['پاوربانک ۲۰ هزار میلی‌آمپری ولت‌مکس', 'لوازم جانبی گوشی', 'physical', 1290000, 0, 60, false, false],
            ['کتونی ورزشی ران‌فست نسخهٔ تابستان', 'مد و پوشاک', 'physical', 2350000, 20, 25, false, false],
            ['ست ۱۲ پارچهٔ ظروف سرامیکی هوم‌کیت', 'خانه و آشپزخانه', 'physical', 3480000, 0, 18, false, false],
            ['کوله‌پشتی کوهنوردی ۴۰ لیتری اکسپلور', 'ورزش و سفر', 'physical', 2870000, 12, 22, false, true],
            ['پکیج آموزش طراحی وب از صفر (نسخهٔ دیجیتال)', 'کتاب، لوازم تحریر و هنر', 'download', 690000, 30, 999, true, false],
            ['کتاب الکترونیکی رشد فردی ۳۰ روزه', 'کتاب، لوازم تحریر و هنر', 'download', 149000, 0, 999, false, false],
        ];

        $count = 0;

        foreach ($catalog as $index => [$title, $categoryTitle, $type, $price, $discount, $stock, $special, $preOrder]) {
            $categoryId = $categories[$categoryTitle] ?? reset($categories);

            $product = Product::create([
                'title'             => $title,
                'slug'              => $title,
                'title_en'          => 'Demo Product '.($index + 1),
                'type'              => $type,
                'category_id'       => $categoryId,
                'price_type'        => 'multiple-price',
                'weight'            => $type === 'download' ? 0 : ($index % 3 + 1) * 250,
                'description'       => $this->demoDescription($title, $type),
                'short_description' => 'محصول نمونهٔ دمو — '.$title,
                'special'           => $special,
                'pre_order'         => $preOrder,
                'published'         => true,
            ]);

            $price = $product->prices()->create([
                'price'               => $price,
                'discount'            => $discount,
                'discount_price'      => get_discount_price($price, $discount, $product),
                'regular_price'       => $price,
                'discount_expire_at'  => $discount > 0 ? now()->addDays(14) : null,
                'stock'               => $stock,
                'published'           => true,
            ]);

            // فایل نمونهٔ محصول دانلودی — بدون آن صفحهٔ محصول دانلودی ۵۰۰ می‌دهد
            if ($type === 'download') {
                $price->file()->create([
                    'title'  => $title,
                    'file'   => 'demo/demo-download.zip',
                    'disk'   => 'downloads',
                    'size'   => 1048576,
                    'status' => 1,
                ]);
            }

            $count++;
        }

        return $count;
    }

    private function demoDescription(string $title, string $type): string
    {
        $ending = $type === 'download'
            ? '<p>پس از پرداخت موفق، لینک دانلود بلافاصله در پنل کاربری شما فعال می‌شود.</p>'
            : '<p>ارسال به سراسر کشور، ضمانت اصالت کالا و ۷ روز مهلت بازگشت.</p>';

        return '<h3>'.$title.'</h3>'
            .'<p>این متن توضیحات نمونهٔ دمو است. در پنل مدیریت می‌توانید توضیحات واقعی محصول را جایگزین کنید.</p>'
            .'<ul><li>کیفیت مطابق استاندارد</li><li>گارانتی معتبر شرکتی</li><li>پرداخت امن</li></ul>'
            .$ending;
    }

    /** مقالات وبلاگ نمونه — با نویسندهٔ مدیر دمو (در ویو مقاله نویسنده نال، ۵۰۰ نمی‌دهد) */
    private function seedPosts(?int $adminId = null): void
    {
        $posts = [
            [
                'title'   => 'راهنمای خرید گوشی هوشمند در سال جدید',
                'summary' => 'قبل از خرید گوشی جدید، به این نکات ساده اما مهم توجه کنید تا بهترین انتخاب را داشته باشید.',
                'content' => '<p>خرید گوشی هوشمند یکی از تصمیم‌های مهم کاربران است...</p><p>در این مقاله مهم‌ترین معیارها مثل باتری، دوربین، حافظه و قیمت را مرور می‌کنیم.</p>',
            ],
            [
                'title'   => '۷ ترفند برای خرید بهتر آنلاین',
                'summary' => 'از مقایسهٔ قیمت تا استفاده از کدهای تخفیف؛ با این ترفندها خرید آنلاین به‌صرفه‌تر می‌شود.',
                'content' => '<p>خرید آنلاین مزایای زیادی دارد اما خریدار هوشمند تفاوت‌ها را می‌داند...</p><ul><li>مقایسهٔ قیمت</li><li>خواندن دیدگاه‌ها</li><li>پیگیری تخفیف‌ها</li></ul>',
            ],
            [
                'title'   => 'معرفی لوازم جانبی کاربردی برای لپ‌تاپ',
                'summary' => 'این لوازم جانبی ساده، تجربهٔ کار با لپ‌تاپ شما را متحول می‌کنند.',
                'content' => '<p>از استند گرفته تا ماوس بی‌سیم و هاب USB-C...</p><p>هر کدام را با کاربرد واقعی معرفی کرده‌ایم.</p>',
            ],
        ];

        foreach ($posts as $post) {
            Post::create([
                'post_type'      => 'text',
                'title'          => $post['title'],
                'slug'           => $post['title'],
                'lang'           => app()->getLocale(),
                'admin_id'       => $adminId,
                'published'      => true,
                'summary'        => $post['summary'],
                'content'        => $post['content'],
                'allow_comments' => true,
            ]);
        }
    }

    /** صفحات ثابت نمونه */
    private function seedPages(): void
    {
        Page::create([
            'title'     => 'دربارهٔ ما',
            'slug'      => 'about-us',
            'lang'      => app()->getLocale(),
            'published' => true,
            'content'   => '<p>این یک فروشگاه نمونه (دمو) است که برای آشنایی با امکانات پلتفرم نصب شده است.</p><p>می‌توانید این متن را از پنل مدیریت ویرایش کنید.</p>',
        ]);

        Page::create([
            'title'     => 'تماس با ما',
            'slug'      => 'contact-us',
            'lang'      => app()->getLocale(),
            'published' => true,
            'content'   => '<p>برای ارتباط با ما از بخش تیکت پشتیبانی در پنل کاربری استفاده کنید.</p><p>پاسخگویی: شنبه تا پنجشنبه، ۹ تا ۱۸</p>',
        ]);
    }

    /** مدیر دمو — فقط اگر از قبل وجود نداشته باشد؛ خروجی: مدل مدیر برای انتساب نویسندگی مقاله‌ها */
    private function seedDemoAdmin(): ?Admin
    {
        if (Admin::where('email', 'demo@shoppro.local')->exists()) {
            return Admin::where('email', 'demo@shoppro.local')->first();
        }

        $baseName = 'demo';
        $username = $baseName;
        $suffix   = 1;

        while (Admin::where('username', $username)->exists()) {
            $username = $baseName.$suffix++;
        }

        Admin::create([
            'first_name' => 'مدیر',
            'last_name'  => 'دمو',
            'username'   => $username,
            'email'      => 'demo@shoppro.local',
            'mobile'     => '09120000000',
            'level'      => 'admin',
            'status'     => 'ACTIVE',
            'password'   => Hash::make($this->option('admin-password')),
        ]);

        return Admin::where('email', 'demo@shoppro.local')->first();
    }
}
