<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Str;
use PHPUnit\Framework\SkippedWithMessageException;

/**
 * پایه تست‌های پروژه.
 *
 * نکته مهم: چون برخی پکیج‌ها vendor/ خودشان را داخل Modules/{Name}/vendor
 * دارند (مثل SeoAudit) و provider آن‌ها autoload ماژول را require می‌کند،
 * Composer یک ClassLoader اضافه با basePath داخل ماژول ثبت می‌شود و
 * Application::inferBasePath() (که TestCase پیش‌فرض لاراول برای پیدا کردن
 * bootstrap/app.php استفاده می‌کند) مسیر اشتباه برمی‌گرداند. پس بوت را
 * صریحاً از ریشه پروژه انجام می‌دهیم.
 *
 * ⭐ گارد محیطی دیتابیس (درس از اجرای کامل مجموعه روی ویندوز/لودرگون):
 * وقتی دیتابیس محلی در دسترس نیست (MySQL خاموش / .env اشتباه / فایروال روی پورت)،
 * اتصال PDO روی ویندوز تا تایم‌اوت TCP (~۲۰ ثانیه به‌ازای هر تست) معلق می‌ماند و
 * صدها تست Feature یکی‌یکی قرمز یا معلق می‌شوند. سه لایه دفاع:
 *   ۱) سقف اتصال PDO روی ۳ ثانیه — در هوک booting (قبل از boot هر provider) تا
 *      کوئری‌های self-heal بوتِ پکیج‌ها هم محدود شوند؛ اتصال lazy بعدی هم همین را
 *      ارث می‌برد.
 *   ۲) خطای «اتصال/احراز هویت/انتخاب دیتابیس» در setUp (شروع تراکنش
 *      DatabaseTransactions و ...) به markTestSkipped با پیام واضح فارسی تبدیل
 *      می‌شود — بایگ‌های واقعی کوئری (ستون اشتباه و ...) همچنان قرمز می‌مانند.
 *   ۳) کشفِ یک‌بار «دیتابیس قطع» برای کل پروسه به‌خاطر سپرده می‌شود؛ تست‌های
 *      بعدی بدون بوتِ سنگین فوری skip می‌شوند تا اجرای کامل محلی چند دقیقه
 *      طول نکشد. (روی CI/محیط سالم این مسیر هیچ‌وقت فعال نمی‌شود.)
 */
abstract class TestCase extends BaseTestCase
{
    /** وضعیت دیتابیس در سطح پروسه — null = هنوز آزمایش نشده، true = قطع است */
    private static ?bool $databaseDown = null;

    public function createApplication(): Application
    {
        $app = require dirname(__DIR__).'/bootstrap/app.php';

        // ۱) سقف اتصال PDO — هوک booting قبل از boot همهٔ providerها اجرا می‌شود
        //    (کوئری‌های self-heal پکیج‌ها در بوت هم سقف‌دار می‌شوند) و چون اتصال
        //    lazy است، اتصال‌های بعدیِ setUp/بدنهٔ تست هم از همین تنظیم می‌خوانند.
        $app->booting(function ($app) {
            $default = (string) $app['config']->get('database.default');
            $connection = (array) $app['config']->get("database.connections.{$default}", []);

            if (($connection['driver'] ?? null) === 'mysql') {
                if (self::$databaseDown === true) {
                    // ۲-ب) قبلاً قطع بودن کشف شده → اتصال‌های بعدیِ این بوت (self-heal
                    // پکیج‌ها، شروع تراکنش و ...) فوری fail شوند (refuse آنی) نه ۳ ثانیه‌ای
                    $connection['host'] = '127.0.0.1';
                    $connection['port'] = '1';
                    $connection['unix_socket'] = '';
                    $app['config']->set("database.connections.{$default}", $connection);

                    return;
                }

                $connection['options'] = array_merge(
                    (array) ($connection['options'] ?? []),
                    [\PDO::ATTR_TIMEOUT => 3]
                );
                $app['config']->set("database.connections.{$default}", $connection);
            }
        });

        $app->make(Kernel::class)->bootstrap();

        return $app;
    }

    protected function setUp(): void
    {
        try {
            parent::setUp();
        } catch (SkippedWithMessageException $e) {
            // skip عمدی خودِ فریمورک/گاردهای محیطی — دست‌نخورده عبور کند
            throw $e;
        } catch (\Throwable $e) {
            if (self::isDatabaseUnreachableError($e)) {
                self::noteDatabaseDown();
                self::markTestSkipped(
                    self::databaseDownMessage().' («'.Str::limit($e->getMessage(), 120).'»)'
                );
            }

            throw $e;
        }
    }

    /**
     * ثبت «دیتابیس این ماشین در دسترس نیست» برای کل پروسهٔ تست.
     * گاردهای محیطی (مثل پروب AdminPanelSmokeTest) هم برای استفاده از
     * skip فوریِ تست‌های بعدی، این متد را صدا می‌زنند.
     */
    public static function noteDatabaseDown(): void
    {
        self::$databaseDown = true;
    }

    private static function databaseDownMessage(): string
    {
        return 'دیتابیس محلی در دسترس نیست — MySQL/MariaDB را روشن کنید و DB_HOST/DB_DATABASE/DB_USERNAME/DB_PASSWORD در .env را بررسی کنید '
            .'(تست‌های نیازمند دیتابیس تا پایان اجرا skip می‌شوند تا مجموعه سریع سبز بماند).';
    }

    /**
     * آیا این استثنا یعنی «دیتابیس در دسترس/قابل‌استفاده نیست»؟
     * فقط خطاهای اتصال/احراز هویت/انتخاب دیتابیس — نه باگ‌های واقعی کوئری
     * (ستون اشتباه، جدول مفقودِ ناشی از باگ کد و ... همچنان قرمز می‌مانند).
     */
    private static function isDatabaseUnreachableError(\Throwable $e): bool
    {
        do {
            if ($e instanceof \PDOException || $e instanceof \Illuminate\Database\QueryException) {
                $message = $e->getMessage();
                $code = (string) $e->getCode();

                // 2002/2003/2006 = اتصال/سرور، 1044/1045 = دسترسی، 1046 = انتخاب نشده،
                // 1049 = دیتابیس ناموجود، 08001..08006 = SQLSTATE استاندارد اتصال
                foreach (['2002', '2003', '2006', '1044', '1045', '1046', '1049', '08001', '08002', '08003', '08004', '08006'] as $sqlState) {
                    if ($code === $sqlState || str_contains($message, "SQLSTATE[{$sqlState}]")) {
                        return true;
                    }
                }

                // درایورها متن خطای متفاوتی می‌دهند — الگوهای متنی رایج
                if (preg_match(
                    "/(Can't connect|Connection refused|Connection timed out|No database selected|Unknown database|Access denied for user|could not find driver)/i",
                    $message
                )) {
                    return true;
                }
            }

            $e = $e->getPrevious();
        } while ($e !== null);

        return false;
    }
}
