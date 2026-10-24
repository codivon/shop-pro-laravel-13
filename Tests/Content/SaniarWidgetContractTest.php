<?php

namespace Themes\SaniarTheme\Tests\Content;

use Tests\TestCase;

/**
 * فاز ۶۸ — قرارداد ویجت‌های صفحهٔ اصلی (خواستهٔ صریح مالک پروژه):
 *   ۱) تمام ویجت‌های صفحهٔ اصلی باید نمای Blade در «پوشهٔ ویجت‌ها» داشته باشند
 *      (resources/views/widgets/{key}.blade.php).
 *   ۲) هر ویجتی که قالب ما «بیشتر» از قالب مرجع دارد، باید در
 *      config/HomeWidgets.php ثبت شده باشد تا از پنل قابل افزودن باشد.
 *   ۳) تصویر پیش‌نمایش هر ویجت در config باید واقعاً در دارایی‌های تم موجود باشد.
 *
 * نگاشت: کلید products-default-block عمداً به نمای products-slider رندر می‌شود
 * (هر دو کیس در index.blade به همان ویو پوینت می‌کنند).
 */
class SaniarWidgetContractTest extends TestCase
{
    /** کلیدهای پذیرفته‌شدهٔ حلقهٔ ویجت صفحهٔ اصلی در index.blade.php */
    private function switchKeysFromIndex(): array
    {
        $index = (string) file_get_contents(dirname(__DIR__, 2).'/src/resources/views/index.blade.php');
        preg_match_all("/@case\('([\w-]+)'\)/", $index, $m);

        return array_values(array_unique($m[1]));
    }

    /** نگاشت کلید → نام فایل ویوی ویجت (همان‌طور که index.blade رندر می‌کند) */
    private function viewNameForKey(string $key): string
    {
        // در index سانیار: products-default-block و products-slider هر دو به
        // widgets.products-slider پوینت می‌کنند؛ بقیه هم‌نام‌اند.
        return $key === 'products-default-block' ? 'products-slider' : $key;
    }

    public function test_every_index_widget_case_has_a_blade_view_in_widgets_folder(): void
    {
        $keys = $this->switchKeysFromIndex();
        $this->assertNotEmpty($keys, 'index.blade باید ویجت‌های صفحه اصلی را via @switch رندر کند');

        foreach ($keys as $key) {
            $view = dirname(__DIR__, 2).'/src/resources/views/widgets/'.$this->viewNameForKey($key).'.blade.php';
            $this->assertFileExists($view, "ویجت «{$key}» باید نمای Blade در پوشهٔ widgets داشته باشد");
        }
    }

    public function test_every_index_widget_case_is_registered_in_home_widgets_config(): void
    {
        $config = include dirname(__DIR__, 2).'/config/HomeWidgets.php';
        $keys   = $this->switchKeysFromIndex();

        foreach ($keys as $key) {
            $this->assertArrayHasKey(
                $key,
                $config,
                "ویجت «{$key}» در config/HomeWidgets.php ثبت نشده — بدون ثبت، از پنل قابل افزودن نیست"
            );
        }
    }

    public function test_widget_extra_beyond_reference_theme_is_registered(): void
    {
        // products-slider ویجت اضافهٔ سانیار نسبت به WeblakShop است (نمای اختصاصی اسلایدر
        // محصولات) — طبق قرارداد باید در config باشد تا از پنل مدیریت قابل انتخاب باشد.
        $config = include dirname(__DIR__, 2).'/config/HomeWidgets.php';

        $this->assertArrayHasKey('products-slider', $config, 'ویجت اضافهٔ products-slider باید در config باشد');
        $this->assertNotEmpty($config['products-slider']['options'] ?? null, 'گزینه‌های ویجت نباید خالی باشد');
        $this->assertNotEmpty($config['products-slider']['rules'] ?? null, 'قواعد اعتبارسنجی ویجت نباید خالی باشد');
    }

    public function test_every_widget_preview_image_exists_in_theme_assets(): void
    {
        $config = include dirname(__DIR__, 2).'/config/HomeWidgets.php';
        $assets = dirname(__DIR__, 2).'/src/resources/assets';

        foreach ($config as $key => $entry) {
            $image = $entry['image'] ?? null;
            if (! $image) {
                continue;
            }

            $this->assertFileExists(
                $assets.'/'.$image,
                "تصویر پیش‌نمایش ویجت «{$key}» در دارایی‌های تم موجود نیست ({$image})"
            );
        }
    }

    public function test_products_slider_has_data_pipeline_in_helpers(): void
    {
        // ویجت بدون کیس دیتا = همیشه خالی رندر می‌شود (باگ فاز ۶۸)
        $helpers = (string) file_get_contents(dirname(__DIR__, 2).'/src/helpers.php');
        $this->assertStringContainsString(
            "case 'products-slider':",
            $helpers,
            'کیس دیتای products-slider باید در get_widget_uncached وجود داشته باشد وگرنه ویجت همیشه خالی است'
        );
    }
}
