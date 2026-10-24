<?php

namespace Tests\Feature\Security;

use App\Models\Comment;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\ShopTestSetup;
use Tests\TestCase;

/**
 * تست‌های امنیت:
 *  - CSRF روی فرم‌ها
 *  - XSS در فیلدهای ورودی (جستجو، نظر)
 *  - SQL Injection
 *  - HTTPS اجباری
 *  - کنترل دسترسی به سفارش دیگران
 */
class SecurityTest extends TestCase
{
    use ShopTestSetup;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpShop();
        Notification::fake();
        Queue::fake();
    }

    // =====================================================================
    //  CSRF
    // =====================================================================

    /**
     * نکته: Laravel 13 به‌صورت پیش‌فرض بررسی CSRF را هنگام اجرای تست‌ها
     * رد می‌کند (runningUnitTests در PreventRequestForgery). بنابراین
     * رفتار middleware را مستقیماً در سطح خودش تست می‌کنیم.
     */
    #[Test]
    public function csrf_middleware_rejects_requests_without_valid_token(): void
    {
        $middleware = new \App\Http\Middleware\VerifyCsrfToken(
            app(\Illuminate\Contracts\Foundation\Application::class),
            app('encrypter')
        );

        $request = \Illuminate\Http\Request::create('/login', 'POST', ['username' => 'x']);
        $request->setLaravelSession(app('session.store'));
        $request->session()->put('_token', 'valid-token');

        // توکن اشتباه → TokenMismatchException
        $request->merge(['_token' => 'wrong-token']);

        // محیط را موقتاً از testing خارج می‌کنیم تا Laravel بررسی CSRF را رد نکند
        $originalEnv = $this->app->environment();
        $this->app['env'] = 'production';

        try {
            $this->expectException(\Illuminate\Session\TokenMismatchException::class);
            $middleware->handle($request, fn () => response('ok'));
        } finally {
            $this->app['env'] = $originalEnv;
        }
    }

    #[Test]
    public function csrf_middleware_accepts_requests_with_valid_token(): void
    {
        $middleware = new \App\Http\Middleware\VerifyCsrfToken(
            app(\Illuminate\Contracts\Foundation\Application::class),
            app('encrypter')
        );

        $request = \Illuminate\Http\Request::create('/login', 'POST', [
            'username' => 'x',
            '_token'   => 'valid-token',
        ]);
        $request->setLaravelSession(app('session.store'));
        $request->session()->put('_token', 'valid-token');

        $response = $middleware->handle($request, fn () => response('ok'));

        $this->assertEquals('ok', $response->getContent());
    }

    #[Test]
    public function forms_include_csrf_token_field(): void
    {
        // فرم لاگین باید توکن CSRF داشته باشد
        $response = $this->get('/login');
        $content = $response->getContent();

        $this->assertTrue(
            str_contains($content, 'csrf-token') ||
            str_contains($content, '_token') ||
            str_contains($content, 'csrf'),
            'صفحه لاگین باید توکن CSRF را در خود داشته باشد'
        );
    }

    // =====================================================================
    //  XSS
    // =====================================================================

    #[Test]
    public function search_input_is_escaped_against_xss(): void
    {
        $product = $this->newProduct(['title' => 'محصول امنیتی سالم']);

        $payload = '<script>alert("xss")</script>';

        $response = $this->get('/search?q=' . urlencode($payload));

        $response->assertStatus(200);

        $content = $response->getContent();
        // خروجی خام نباید تگ اسکریپت داخل خودش داشته باشد
        $this->assertStringNotContainsString(
            '<script>alert("xss")</script>',
            $content,
            'ورودی جستجو باید قبل از نمایش escape شود'
        );
    }

    #[Test]
    public function search_query_with_event_handler_is_escaped(): void
    {
        $payload = '" onmouseover="alert(1)" data-x="';

        $response = $this->get('/search?q=' . urlencode($payload));

        $response->assertStatus(200);
        $this->assertStringNotContainsString(
            'onmouseover="alert(1)"',
            $response->getContent()
        );
    }

    #[Test]
    public function comment_body_with_script_tag_is_escaped_on_output(): void
    {
        $user = $this->newUser();
        $product = $this->newProduct();

        // ذخیره نظر با محتوای مخرب
        $this->actingAs($user, 'web')
            ->post('/products/' . $product->slug . '/comments', [
                'body' => '<script>alert("comment-xss")</script> نظر تستی',
            ])
            ->assertStatus(200);

        $comment = Comment::where('user_id', $user->id)->latest('id')->first();
        $this->assertNotNull($comment, 'نظر باید ذخیره شود');

        // خروجی AJAX پرسش‌ها باید escape شده باشد
        $comment->update(['status' => 'accepted']);

        $response = $this->get('/products/questions/getQuestions?sortComment=new&product_id=' . $product->id);
        $response->assertStatus(200);

        $content = $response->getContent();
        $this->assertStringNotContainsString(
            '<script>alert("comment-xss")</script>',
            $content,
            'بدنه نظر باید در خروجی escape شود'
        );
    }

    #[Test]
    public function stored_xss_in_product_title_does_not_execute(): void
    {
        // عنوان محصول حاوی تگ اسکریپت (ثبت‌شده از پنل)
        $product = $this->newProduct(['title' => 'محصول <script>alert("title-xss")</script> تستی']);

        $response = $this->get('/product/' . $product->slug);
        $response->assertStatus(200);

        $this->assertStringNotContainsString(
            '<script>alert("title-xss")</script>',
            $response->getContent(),
            'عنوان محصول باید escape شود'
        );
    }

    // =====================================================================
    //  SQL Injection
    // =====================================================================

    #[Test]
    public function search_is_protected_against_sql_injection(): void
    {
        $product = $this->newProduct(['title' => 'محصول واقعی SQL تست']);

        $payloads = [
            "' OR 1=1 -- -",
            "'; DROP TABLE products; -- -",
            "' UNION SELECT * FROM users -- -",
            "%' OR '1'='1",
        ];

        foreach ($payloads as $payload) {
            $response = $this->get('/search?q=' . urlencode($payload));

            // هیچ‌وقت ۵۰۰ نمی‌دهد — کوئری bind شده است
            $this->assertNotEquals(
                500,
                $response->status(),
                "payload «{$payload}» نباید خطای سرور بدهد"
            );
            $response->assertStatus(200);
        }

        // جدول محصولات هنوز وجود دارد (DROP TABLE اجرا نشده)
        $this->assertDatabaseCount('products', \App\Models\Product::count());
    }

    #[Test]
    public function login_is_protected_against_sql_injection(): void
    {
        $payloads = [
            "' OR '1'='1",
            "admin'--",
            "' OR 1=1 LIMIT 1; -- -",
        ];

        foreach ($payloads as $payload) {
            $response = $this->post('/login', [
                'username' => $payload,
                'password' => "' OR '1'='1",
            ]);

            $this->assertNotEquals(500, $response->status());
        }

        // هیچ نشستی ایجاد نشده باشد
        $this->assertGuest();
    }

    #[Test]
    public function ajax_search_is_protected_against_sql_injection(): void
    {
        $response = $this->post('/search', [
            'q' => "' UNION SELECT password FROM users -- -",
        ]);

        $response->assertStatus(200);
        // password کاربران نباید در خروجی JSON باشد
        $this->assertStringNotContainsString('$2y$', $response->getContent());
    }

    // =====================================================================
    //  HTTPS اجباری
    // =====================================================================

    #[Test]
    public function non_whitelisted_ip_is_forced_to_https_on_shop_pages(): void
    {
        $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.7']);

        $response = $this->get('/login');

        $response->assertStatus(302);
        $this->assertStringStartsWith('https://', (string) $response->headers->get('Location'));
    }

    #[Test]
    public function whitelisted_ip_is_not_redirected(): void
    {
        // REMOTE_ADDR=127.0.0.1 در وایت‌لیست است (پیش‌فرض setUpShop)
        $this->get('/login')->assertStatus(200);
    }

    // =====================================================================
    //  کنترل دسترسی
    // =====================================================================

    #[Test]
    public function order_resource_is_protected_by_ownership(): void
    {
        $owner = $this->newUser();
        $intruder = $this->newUser();
        $product = $this->newProduct();
        [$order] = $this->newPaidOrder($owner, $product);

        // نمایش
        $this->actingAs($intruder, 'web')
            ->get('/profile/orders/' . $order->id)
            ->assertNotFound();

        // پرینت
        $this->actingAs($intruder, 'web')
            ->get('/profile/orders/' . $order->id . '/print')
            ->assertNotFound();

        // پرداخت
        $this->actingAs($intruder, 'web')
            ->get('/profile/orders/pay/' . $order->id . '?gateway=zarinpal')
            ->assertNotFound();
    }

    #[Test]
    public function address_of_other_user_cannot_be_used_for_order(): void
    {
        $owner = $this->newUser();
        $intruder = $this->newUser();
        $otherAddress = $this->newAddress($owner);
        $product = $this->newProduct();
        $this->addToCart($intruder, $product);
        $this->newGateway('zarinpal');
        $carrier = $this->newCarrier();

        // آدرس کاربر دیگر باید رد شود (کنترل مالکیت در StoreOrderRequest)
        $this->actingAs($intruder, 'web')
            ->post('/profile/orders', [
                'gateway'          => 'zarinpal',
                'address'          => $otherAddress->id,
                'carrier_id_store' => $carrier->id,
            ])
            ->assertSessionHasErrors(['address']);

        // هیچ سفارشی با آدرس قربانی ثبت نشده باشد
        $order = \App\Models\Order::where('user_id', $intruder->id)->first();
        $this->assertNull($order, 'سفارش با آدرس کاربر دیگر نباید ثبت شود');
    }

    #[Test]
    public function guest_cannot_access_any_profile_resource(): void
    {
        $protected = [
            '/profile',
            '/profile/orders',
            '/profile/addresses',
            '/profile/returns',
            '/profile/favorites',
            '/profile/wallet',
        ];

        foreach ($protected as $url) {
            $response = $this->get($url);
            $this->assertContains(
                $response->status(),
                [301, 302, 307],
                "«{$url}» باید مهمان را به لاگین هدایت کند"
            );
        }
    }

    #[Test]
    public function users_table_credentials_are_never_exposed_in_search_results(): void
    {
        $user = $this->newUser(['password' => 'top-secret-hash']);

        $response = $this->post('/search', ['q' => $user->username]);

        $this->assertStringNotContainsString('top-secret-hash', $response->getContent());
    }
}
