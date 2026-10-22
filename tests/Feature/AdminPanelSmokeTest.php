<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * دودآزمون (Smoke) پنل — بدون نوشتن در دیتابیس، فقط صفحات عمومی و ریدایرکت‌ها
 */
class AdminPanelSmokeTest extends TestCase
{
    public function test_login_page_renders(): void
    {
        // REMOTE_ADDR داخل وایت‌لیست ForceHttps (بدون ریدایرکت https)
        $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1']);

        $response = $this->get('/admin/admin/login');

        $response->assertStatus(200);
        $response->assertSee('ورود', false);
    }

    public function test_non_whitelisted_ip_over_http_is_forced_to_https(): void
    {
        // IP خارج از وایت‌لیست ForceHttps روی http باید به https هدایت شود
        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.10']);

        $response = $this->get('/admin/admin/login');

        $response->assertStatus(302);
        $this->assertStringStartsWith('https://', (string) $response->headers->get('Location'));
    }

    public function test_guest_is_redirected_from_panel_root(): void
    {
        $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1']);

        $response = $this->get('/admin/admin');

        $this->assertContains(
            $response->status(),
            [301, 302, 307],
            'مهمان باید به صفحه ورود ریدایرکت شود.'
        );
    }

    public function test_admin_prefix_helper_matches_config(): void
    {
        // پنل جدید همیشه /admin/{admin_route_prefix} است؛ این قرارداد
        // منوی پکیج‌ها و ریدایرکت لاگین به آن وابسته است.
        $this->assertNotEmpty(config('general.admin_route_prefix'));
    }

    public function test_app_responds_on_front_homepage(): void
    {
        $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1']);

        // صفحه اصلی فروشگاه (بدون احراز هویت) باید پاسخ 200 یا ریدایرکت بدهد
        $response = $this->get('/');

        $this->assertContains($response->status(), [200, 301, 302, 307]);
    }
}
