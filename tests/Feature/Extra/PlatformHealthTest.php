<?php

namespace Tests\Feature\Extra;

use App\Models\Contact;
use App\Models\Newsletter;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\ShopTestSetup;
use Tests\TestCase;

/**
 * تست‌های تکمیلی سلامت پلتفرم (اضافه‌شده توسط تحلیل خود پروژه):
 *  - robots.txt معتبر با مسدودسازی مسیرهای مدیریتی و طعمه‌ها
 *  - manifest.json پاسخ می‌دهد
 *  - صفحه 404 اختصاصی (نه stack trace خام)
 *  - نسخه‌های چندزبانه (fa / en)
 *  - فرم تماس با ما (اعتبارسنجی + ذخیره + XSS امن)
 *  - خبرنامه (عضویت ایمیل/موبایل + رد تکراری/نامعتبر)
 *  - captcha endpoint
 */
class PlatformHealthTest extends TestCase
{
    use ShopTestSetup;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpShop();
    }

    // =====================================================================
    //  robots.txt و manifest
    // =====================================================================

    #[Test]
    public function robots_txt_blocks_admin_and_private_areas(): void
    {
        // نکته: robots.txt فایل استاتیک است و وب‌سرور (نه Laravel) سروش می‌کند؛
        // پس محتوای فایل را از دیسک کنترل می‌کنیم (همان چیزی که nginx سرو می‌کند)
        $path = public_path('robots.txt');

        $this->assertFileExists($path, 'robots.txt باید در public وجود داشته باشد');

        $content = file_get_contents($path);
        $this->assertStringContainsString('User-agent', $content);
        $this->assertStringContainsString('Disallow: /admin', $content, 'مسیر مدیریت باید از ایندکس موتورهای جستجو بسته باشد');
        $this->assertStringContainsString('Disallow: /profile', $content, 'پروفایل کاربران نباید ایندکس شود');
        $this->assertStringContainsString('Disallow: /cart', $content);
    }

    #[Test]
    public function manifest_json_is_served(): void
    {
        $response = $this->get('/manifest.json');

        $response->assertStatus(200);
        $this->assertJson($response->getContent());
    }

    // =====================================================================
    //  صفحه 404
    // =====================================================================

    #[Test]
    public function missing_pages_get_a_proper_404_page(): void
    {
        $response = $this->get('/this-page-does-not-exist-' . \Illuminate\Support\Str::random(8));

        $response->assertStatus(404);
        $this->assertNotSame('', trim($response->getContent()), 'صفحه 404 نباید خالی باشد');
        $this->assertStringNotContainsString(
            'Whoops',
            $response->getContent(),
            'پیغام دیباگ خام نباید نمایش داده شود'
        );
    }

    // =====================================================================
    //  چندزبانه
    // =====================================================================

    #[Test]
    public function default_homepage_serves_with_fa_locale(): void
    {
        // زبان پیش‌فرض فروشگاه فارسی است (نسخه‌های /fa و /en فقط وقتی
        // چندزبانه فعال شود رجیستر می‌شوند — اینجا رفتار پیش‌فرض کنترل می‌شود)
        $response = $this->get('/');

        $response->assertStatus(200);
        $this->assertSame('fa', app()->getLocale());
    }

    // =====================================================================
    //  فرم تماس با ما
    // =====================================================================

    #[Test]
    public function contact_form_stores_message_with_captcha(): void
    {
        Notification::fake();
        $captcha = app(\App\Services\CaptchaService::class)->generate();

        $response = $this->post('/contact', [
            'name'    => 'کاربر تماس',
            'email'   => 'contact-' . \Illuminate\Support\Str::random(5) . '@test.ir',
            'subject' => 'موضوع تستی',
            'message' => 'متن پیام تستی برای بررسی فرم تماس',
            'captcha' => $captcha,
        ]);

        $response->assertStatus(200);
        $this->assertSame(
            1,
            Contact::where('subject', 'موضوع تستی')->count(),
            'پیام تماس باید در دیتابیس ذخیره شود'
        );
    }

    #[Test]
    public function contact_form_validates_required_fields(): void
    {
        $response = $this->post('/contact', [
            'name'    => '',
            'email'   => 'not-an-email',
            'subject' => '',
            'message' => '',
        ]);

        $response->assertSessionHasErrors(['name', 'email', 'subject', 'message']);
    }

    #[Test]
    public function contact_form_rejects_wrong_captcha(): void
    {
        $response = $this->post('/contact', [
            'name'    => 'کاربر',
            'email'   => 'cap@test.ir',
            'subject' => 'بدون کپچا',
            'message' => 'متن',
            'captcha' => 'XXXXX', // کد غلط
        ]);

        $response->assertSessionHasErrors(['captcha']);
        $this->assertSame(0, Contact::where('subject', 'بدون کپچا')->count());
    }

    #[Test]
    public function contact_message_with_xss_payload_is_escaped_on_admin_view(): void
    {
        Notification::fake();
        $captcha = app(\App\Services\CaptchaService::class)->generate();

        $payload = '<script>alert("contact-xss")</script>';

        $this->post('/contact', [
            'name'    => $payload,
            'email'   => 'xss@test.ir',
            'subject' => 'تست XSS تماس',
            'message' => $payload . ' متن پیام',
            'captcha' => $captcha,
        ])->assertStatus(200);

        $contact = Contact::where('subject', 'تست XSS تماس')->first();
        $this->assertNotNull($contact);

        // لایه‌ی دفاعی اصلی خروجی Blade است: ویوهای پنل مدیریتی باید با
        // {{ }} (escape شده) رندر کنند و هرگز {!! !!} خام استفاده نکنند
        $adminViews = [
            resource_path('views/back/contacts/show.blade.php'),
            resource_path('views/back/contacts/index.blade.php'),
        ];

        foreach ($adminViews as $view) {
            $this->assertFileExists($view);
            $content = file_get_contents($view);

            $this->assertStringNotContainsString(
                '{!!',
                $content,
                basename($view) . ' نباید خروجی خام Blade ({!!) داشته باشد — ریسک XSS'
            );
            $this->assertStringContainsString(
                '{{ $contact->name }}',
                $content,
                basename($view) . ' باید نام تماس‌گیرنده را escape شده نمایش دهد'
            );
        }
    }

    // =====================================================================
    //  خبرنامه
    // =====================================================================

    #[Test]
    public function guest_can_subscribe_to_newsletter_with_email(): void
    {
        $email = 'news-' . \Illuminate\Support\Str::random(6) . '@test.ir';

        $response = $this->post('/newsletter/subscribe', ['contact' => $email]);

        $this->assertContains($response->status(), [200, 302]);
        $this->assertDatabaseHas('newsletters', ['contact' => $email]);
    }

    #[Test]
    public function guest_can_subscribe_to_newsletter_with_mobile(): void
    {
        $mobile = '0935' . str_pad((string) random_int(0, 9999999), 7, '0', STR_PAD_LEFT);

        $this->post('/newsletter/subscribe', ['contact' => $mobile]);

        $this->assertDatabaseHas('newsletters', ['contact' => $mobile]);
    }

    #[Test]
    public function newsletter_rejects_invalid_and_duplicate_contacts(): void
    {
        $email = 'dup-' . \Illuminate\Support\Str::random(6) . '@test.ir';

        // بار اول موفق
        $this->post('/newsletter/subscribe', ['contact' => $email]);

        // تکراری → خطای اعتبارسنجی unique
        $this->post('/newsletter/subscribe', ['contact' => $email])
            ->assertSessionHasErrors(['contact']);

        // فرمت نامعتبر → خطای فرمت (پیام error، نه errors اعتبارسنجی)
        $this->post('/newsletter/subscribe', ['contact' => 'not-an-email-or-mobile'])
            ->assertSessionHas('error');

        $this->assertSame(1, Newsletter::where('contact', $email)->count());
    }

    // =====================================================================
    //  captcha endpoint
    // =====================================================================

    #[Test]
    public function captcha_image_endpoint_serves_an_image(): void
    {
        $response = $this->get('/captcha/image');

        $response->assertStatus(200);
        $this->assertStringContainsString(
            'image/',
            (string) $response->headers->get('Content-Type'),
            'خروجی captcha باید تصویر باشد'
        );
    }
}
