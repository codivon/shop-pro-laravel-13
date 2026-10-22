<?php

namespace Tests\Unit;

use Tests\TestCase;

/**
 * تست هلپرهای ماژول‌ها — مسیر asset پکیج‌ها باید همیشه
 * public/modules/{lowercase-slug}/... باشد (کنوانسیون انتشار asset در نصاب).
 */
class ModuleHelpersTest extends TestCase
{
    public function test_module_asset_builds_lowercase_module_path(): void
    {
        $this->assertStringEndsWith(
            '/modules/seoaudit/css/seo.css',
            module_asset('SeoAudit', 'css/seo.css')
        );

        $this->assertStringEndsWith(
            '/modules/story/js/back/stories/all.js',
            module_asset('Story', '/js/back/stories/all.js')
        );
    }

    public function test_module_asset_is_insensitive_to_leading_slashes_and_case(): void
    {
        $this->assertSame(
            module_asset('SmartAssembly', 'images/no-image.jpg'),
            module_asset('smartassembly', '/images/no-image.jpg')
        );
    }

    public function test_module_asset_stays_inside_modules_namespace(): void
    {
        // asset پکیج هرگز نباید به پوشه‌های پنل (back/ یا admin-panel/) اشاره کند
        $this->assertStringContainsString('/modules/', module_asset('DigiPay', 'js/gateway.js'));
        $this->assertStringNotContainsString('/back/', module_asset('DigiPay', 'js/gateway.js'));
    }
}
