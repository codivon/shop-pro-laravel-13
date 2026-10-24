<?php

namespace Tests\Unit;

use App\Services\ThemeAssetLinker;
use Tests\TestCase;

/**
 * فاز ۶۵ — تست سرویس لینک‌ساز دارایی‌های قالب.
 *
 * ریشهٔ باگ: File::link() لاراول روی لینوکس وقتی symlink غیرفعال باشد مستقیم
 * exec('ln -s …') می‌زند؛ روی هاستی که exec هم disable شده باشد فعال‌سازی قالب با
 * «Call to undefined function Illuminate\Filesystem\exec()» می‌مُرد.
 * این سرویس زنجیرهٔ symlink → exec → کپی را با گاردهای function_exists اجرا می‌کند.
 *
 * پارامترهای allowSymlink/allowExec سناریوی هاست سخت‌گیرانه را شبیه‌سازی می‌کنند
 * (در محیط تست واقعی هر دو فعال‌اند وگرنه حالت copy قابل شبیه‌سازی نیست).
 */
class ThemeAssetLinkerTest extends TestCase
{
    protected string $tmpBase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tmpBase = sys_get_temp_dir().'/shop-theme-linker-'.uniqid();
        mkdir($this->tmpBase, 0755, true);

        // پوشهٔ «تم آزمایشی» با یک فایل نمونه
        mkdir($this->tmpBase.'/target', 0755, true);
        file_put_contents($this->tmpBase.'/target/style.css', 'body{direction:rtl}');
    }

    protected function tearDown(): void
    {
        // حذف نرم — symlink اول پاک شود تا deleteDirectory روی هدف واقعی نرود
        $link = $this->tmpBase.'/link';
        if (is_link($link)) {
            @unlink($link);
        }

        if (is_dir($this->tmpBase)) {
            \Illuminate\Support\Facades\File::deleteDirectory($this->tmpBase);
        }

        parent::tearDown();
    }

    private function target(): string
    {
        return $this->tmpBase.'/target';
    }

    private function link(): string
    {
        return $this->tmpBase.'/link';
    }

    public function test_creates_symlink_on_healthy_host(): void
    {
        $mode = ThemeAssetLinker::linkOrCopy($this->target(), $this->link());

        $this->assertContains($mode, ['symlink', 'exec'], 'در محیط سالم باید symlink ساخته شود');
        $this->assertTrue(is_link($this->link()));
        $this->assertFileExists($this->link().'/style.css');
    }

    public function test_hardened_host_without_symlink_and_exec_falls_back_to_real_copy(): void
    {
        // شبیه‌سازی هاست اشتراکی: symlink و exec هر دو در disable_functions
        $mode = ThemeAssetLinker::linkOrCopy($this->target(), $this->link(), false, false);

        $this->assertSame('copy', $mode);
        $this->assertFileExists($this->link().'/style.css', 'فایل باید واقعاً کپی شده باشد');
        $this->assertFileExists($this->link().'/'.ThemeAssetLinker::COPY_MARKER, 'کپی باید مارکر مالکیت داشته باشد');
        $this->assertFalse(is_link($this->link()));
    }

    public function test_copy_mode_is_idempotent_and_refreshes_stale_copy(): void
    {
        ThemeAssetLinker::linkOrCopy($this->target(), $this->link(), false, false);

        // فایل کهنه در کپی — باید با فعال‌سازی مجدد نوسازی شود
        file_put_contents($this->link().'/style.css', 'STALE');

        $mode = ThemeAssetLinker::linkOrCopy($this->target(), $this->link(), false, false);

        $this->assertSame('copy', $mode);
        $this->assertSame('body{direction:rtl}', file_get_contents($this->link().'/style.css'), 'کپی کهنه باید از نو کپی شود');
    }

    public function test_stale_copy_is_upgraded_to_symlink_when_host_allows(): void
    {
        // هاست سخت‌گیرانه کپی ساخته؛ حالا هاست symlink را باز کرده است
        ThemeAssetLinker::linkOrCopy($this->target(), $this->link(), false, false);
        $this->assertFileExists($this->link().'/'.ThemeAssetLinker::COPY_MARKER);

        $mode = ThemeAssetLinker::linkOrCopy($this->target(), $this->link(), true, false);

        $this->assertSame('symlink', $mode, 'کپی مارک‌دار باید به symlink ارتقا یابد');
        $this->assertTrue(is_link($this->link()));
        $this->assertFileDoesNotExist($this->link().'/'.ThemeAssetLinker::COPY_MARKER);
    }

    public function test_existing_valid_symlink_is_left_untouched(): void
    {
        symlink($this->target(), $this->link());

        $mode = ThemeAssetLinker::linkOrCopy($this->target(), $this->link(), false, false);

        $this->assertSame('existing', $mode);
        $this->assertTrue(is_link($this->link()));
    }

    public function test_broken_symlink_is_recreated(): void
    {
        symlink($this->tmpBase.'/target-nonexistent', $this->link());

        $mode = ThemeAssetLinker::linkOrCopy($this->target(), $this->link(), false, false);

        $this->assertSame('copy', $mode);
        $this->assertFileExists($this->link().'/style.css');
    }

    public function test_unknown_real_directory_is_never_destroyed(): void
    {
        mkdir($this->link(), 0755, true);
        file_put_contents($this->link().'/hand-made.css', '/* فایل دست‌ساز ادمین */');

        $mode = ThemeAssetLinker::linkOrCopy($this->target(), $this->link(), true, true);

        $this->assertSame('existing-dir', $mode, 'پوشهٔ ناشناس نباید حذف یا بازنویسی شود');
        $this->assertFileExists($this->link().'/hand-made.css');
        $this->assertFileDoesNotExist($this->link().'/style.css');
    }

    public function test_missing_target_throws_clear_exception(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('هدف لینک وجود ندارد');

        ThemeAssetLinker::linkOrCopy($this->tmpBase.'/no-such-target', $this->link());
    }
}
