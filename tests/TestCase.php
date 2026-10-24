<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

/**
 * پایه تست‌های پروژه.
 *
 * نکته مهم: چون برخی پکیج‌ها vendor/ خودشان را داخل Modules/{Name}/vendor
 * دارند (مثل SeoAudit) و provider آن‌ها autoload ماژول را require می‌کند،
 * Composer یک ClassLoader اضافه با basePath داخل ماژول ثبت می‌شود و
 * Application::inferBasePath() (که TestCase پیش‌فرض لاراول برای پیدا کردن
 * bootstrap/app.php استفاده می‌کند) مسیر اشتباه برمی‌گرداند. پس بوت را
 * صریحاً از ریشه پروژه انجام می‌دهیم.
 */
abstract class TestCase extends BaseTestCase
{
    public function createApplication(): Application
    {
        $app = require dirname(__DIR__).'/bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        return $app;
    }
}
