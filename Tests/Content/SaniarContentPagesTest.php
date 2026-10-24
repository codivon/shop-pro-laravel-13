<?php

namespace Themes\Saniar\Tests\Content;

use App\Models\Admin;
use App\Models\Category;
use App\Models\Comment;
use App\Models\Page;
use App\Models\Post;
use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * تست‌های اختصاصی فاز K تم سانیار — صفحات محتوایی + سئو + SPA + رفع باگ‌های لاتنت.
 *
 * همهٔ تست‌ها داخل تراکنش روی دیتابیس توسعه اجرا و در پایان rollback می‌شوند.
 */
class SaniarContentPagesTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        // ForceHttps فقط IPهای خارج وایت‌لیست را هدایت می‌کند
        $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1']);
        Cache::flush();
    }

    // ------------------------------------------------\Helpers

    private function testEditor(): Admin
    {
        return Admin::firstOrCreate(
            ['username' => 'saniar-k-test'],
            [
                'first_name' => 'تحریریه', 'last_name' => 'تست K',
                'email' => 'k-test@saniar.local',
                'password' => bcrypt('secret1234'),
                'level' => 'admin',
                'status' => 1,
            ]
        );
    }

    private function postCategory(): Category
    {
        $category = Category::where('type', 'postcat')->whereNull('category_id')->first();

        if (! $category) {
            $category = Category::create([
                'title' => 'دسته تست K ' . Str::random(4),
                'slug' => 'k-test-' . Str::random(6),
                'type' => 'postcat',
                'published' => 1,
            ]);
        }

        return $category;
    }

    private function publishedPost(array $attributes = []): Post
    {
        $title = $attributes['title'] ?? 'مقالهٔ تستی فاز K ' . Str::random(5);

        return Post::create([
            'title' => $title,
            'slug' => $attributes['slug'] ?? Str::slug($title . '-' . Str::random(4)),
            'category_id' => $attributes['category_id'] ?? $this->postCategory()->id,
            'admin_id' => $this->testEditor()->id,
            'post_type' => 'text',
            'published' => true,
            'status' => 'end',
            'allow_comments' => $attributes['allow_comments'] ?? true,
            'is_editor_pick' => $attributes['is_editor_pick'] ?? false,
            'view' => $attributes['view'] ?? 0,
            'summary' => 'خلاصهٔ تستی فاز K',
            'content' => '<h2>مقدمه</h2><p>محتوای تستی مقالهٔ فاز K برای صفحهٔ نمایش سانیار.</p>',
        ] + ($attributes['meta_title'] ?? [] ? ['meta_title' => $attributes['meta_title']] : []));
    }

    private function publishedPage(array $attributes = []): Page
    {
        return Page::create([
            'title' => $attributes['title'] ?? 'صفحهٔ تستی K ' . Str::random(4),
            'slug' => $attributes['slug'] ?? 'k-test-page-' . Str::random(5),
            'published' => true,
            'content' => '<p>محتوای داینامیک تستی فاز K</p>',
            'meta_title' => $attributes['meta_title'] ?? null,
            'meta_description' => $attributes['meta_description'] ?? null,
        ]);
    }

    // ------------------------------------------------وبلاگ و آرشیو

    public function test_blog_index_renders_saniar_design(): void
    {
        $post = $this->publishedPost();

        $response = $this->get('/blog');

        $response->assertStatus(200);
        $response->assertSee('blog-hero-box', false);          // سربرگ اختصاصی سانیار
        $response->assertSee($post->title, false);             // محتوای داینامیک (نه استاتیک)
        $response->assertSee('آرشیو مقالات', false);
        $response->assertSee('<link rel="canonical" href="', false);
    }

    public function test_articles_index_renders_grid_and_sort_bar(): void
    {
        $post = $this->publishedPost();

        $response = $this->get('/blog/articles');

        $response->assertStatus(200);
        $response->assertSee('data-saniar-articles-index', false);
        $response->assertSee($post->title, false);
        $response->assertSee('مرتب سازی بر اساس', false);
    }

    public function test_articles_index_ajax_returns_json_shape(): void
    {
        $this->publishedPost();

        $response = $this->get('/blog/articles', ['X-Requested-With' => 'XMLHttpRequest']);

        $response->assertStatus(200);
        $response->assertJsonStructure(['data', 'pagination', 'total']);
    }

    public function test_articles_index_search_q_filters_by_title(): void
    {
        $needle = 'گوشیکتستK' . Str::random(3);
        $this->publishedPost(['title' => 'بررسی ' . $needle]);
        $this->publishedPost(['title' => 'مقالهٔ بی‌ربط ' . Str::random(5)]);

        $response = $this->get('/blog/articles?q=' . urlencode($needle));

        $response->assertStatus(200);
        $content = $response->getContent();
        $this->assertStringContainsString($needle, $content);
        $this->assertStringNotContainsString('مقالهٔ بی‌ربط', $content);
    }

    public function test_blog_search_dead_route_now_redirects(): void
    {
        // در WeblakShop این روت به متد ناموجود پوینت می‌کرد (BadMethodCall/500)
        $response = $this->get('/blog/search?q=' . urlencode('تست'));

        $response->assertStatus(302);
        $response->assertRedirect('/blog/articles?q=' . urlencode('تست'));
    }

    public function test_dead_blog_comment_route_removed(): void
    {
        // Route blogs/{post}/comments → BlogController::comments (متد ناموجود) حذف شد
        $this->assertFalse(\Illuminate\Support\Facades\Route::has('front.post.comments'));
    }

    // ------------------------------------------------صفحه مقاله

    public function test_article_show_renders_seo_and_schema(): void
    {
        // پست دوم برای سایدبار «آخرین مطالب» — کنترلر پست جاری را کنار می‌گذارد
        // (این تست بدون دیتای دموی باقیمانده هم باید خودکفا باشد — فاز ۶۳)
        $sidebarPost = $this->publishedPost(['title' => 'مقالهٔ دوم سایدبار فاز ۶۳']);
        $sidebarTag = \App\Models\Tag::create(['name' => 'هشتگ تستی فاز ۶۳']);
        $sidebarPost->tags()->attach($sidebarTag->id);

        $post = $this->publishedPost(['is_editor_pick' => true]);

        $response = $this->get('/blog/articles/' . $post->slug);

        $response->assertStatus(200);
        $content = $response->getContent();

        // JSON-LD
        $this->assertStringContainsString('application/ld+json', $content);
        $this->assertStringContainsString('"@type":"Article"', $content);
        $this->assertStringContainsString('"@type":"BreadcrumbList"', $content);

        // متاهای Open Graph مقاله
        $this->assertStringContainsString('<meta property="og:type" content="article">', $content);
        $this->assertStringContainsString('article:published_time', $content);

        // توضیحات متای داینامیک از خلاصه
        $this->assertStringContainsString('خلاصهٔ تستی فاز K', $content);

        // بخش‌های سانیار
        $this->assertStringContainsString('data-saniar-article', $content);
        $this->assertStringContainsString('mini-blog-item', $content);      // سایدبار آخرین مطالب
        $this->assertStringContainsString('هشتگ‌های داغ', $content);       // سایدبار تگ‌ها
        $this->assertStringContainsString('پیشنهادهایی', $content);        // اسلایدر پیشنهادها
    }

    public function test_article_show_increments_view_counter(): void
    {
        $post = $this->publishedPost(['view' => 10]);

        $this->get('/blog/articles/' . $post->slug);

        $this->assertDatabaseHas('posts', ['id' => $post->id, 'view' => 11]);
    }

    public function test_article_like_toggle_for_guest(): void
    {
        $post = $this->publishedPost();

        $first = $this->post('/blog/articles/like', ['post_id' => $post->id]);
        $first->assertStatus(200)->assertJson(['success' => true, 'liked' => true]);

        // دومین فراخوانی همان مهمان (IP یکسان) لایک را برمی‌دارد
        $second = $this->post('/blog/articles/like', ['post_id' => $post->id]);
        $second->assertStatus(200)->assertJson(['success' => true, 'liked' => false]);
    }

    public function test_article_comment_stores_as_pending(): void
    {
        $post = $this->publishedPost();

        $response = $this->post("/blog/articles/{$post->slug}/comments", [
            'content' => 'دیدگاه تستی فاز K از PHPUnit',
        ], ['X-Requested-With' => 'XMLHttpRequest']);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertDatabaseHas('comments', [
            'commentable_id' => $post->id,
            'commentable_type' => Post::class,
            'status' => 'pending',
        ]);
    }

    public function test_article_comment_reply_stores_as_pending(): void
    {
        $post = $this->publishedPost();
        $admin = $this->testEditor();

        $parent = Comment::create([
            'body' => 'نظر تاییدشدهٔ اصلی',
            'commentable_id' => $post->id,
            'commentable_type' => Post::class,
            'admin_id' => $admin->id,
            'status' => 'accepted',
        ]);

        $response = $this->post('/blog/articles/comments/reply', [
            'comment_id' => $parent->id,
            'content' => 'پاسخ تستی فاز K',
        ], ['X-Requested-With' => 'XMLHttpRequest']);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertDatabaseHas('comments', [
            'comment_id' => $parent->id,
            'status' => 'pending',
        ]);
    }

    public function test_article_comment_like_increments_counter(): void
    {
        $post = $this->publishedPost();
        $admin = $this->testEditor();

        $comment = Comment::create([
            'body' => 'نظر تاییدشده',
            'commentable_id' => $post->id,
            'commentable_type' => Post::class,
            'admin_id' => $admin->id,
            'status' => 'accepted',
            'likes_count' => 5,
            'dislikes_count' => 0,
        ]);

        $response = $this->post("/blog/articles/comments/{$comment->id}/like", ['type' => 'like']);

        $response->assertStatus(200);
        $response->assertJson(['success' => true, 'liked' => true, 'likes_count' => 6]);
        $response->assertCookie('comment_likes');
    }

    public function test_articles_comments_list_ajax_returns_accepted_only(): void
    {
        $post = $this->publishedPost();
        $admin = $this->testEditor();

        Comment::create([
            'body' => 'نظر تاییدشده', 'commentable_id' => $post->id, 'commentable_type' => Post::class,
            'admin_id' => $admin->id, 'status' => 'accepted',
        ]);
        Comment::create([
            'body' => 'نظر در انتظار', 'commentable_id' => $post->id, 'commentable_type' => Post::class,
            'admin_id' => $admin->id, 'status' => 'pending',
        ]);

        $response = $this->get("/blog/articles/comments/{$post->id}", ['X-Requested-With' => 'XMLHttpRequest']);

        $response->assertStatus(200);
        $response->assertJsonStructure(['data', 'pagination']);
        $this->assertStringContainsString('نظر تاییدشده', $response->json('data'));
        $this->assertStringNotContainsString('نظر در انتظار', $response->json('data'));
    }

    // ------------------------------------------------صفحات داینامیک

    public function test_pages_show_renders_dynamic_content(): void
    {
        $page = $this->publishedPage(['meta_title' => 'عنوان سئوی اختصاصی K']);

        $response = $this->get('/pages/' . $page->slug);

        $response->assertStatus(200);
        $response->assertSee($page->title, false);
        $response->assertSee('محتوای داینامیک تستی فاز K', false);
        $response->assertSee('<title>عنوان سئوی اختصاصی K |', false);
        $response->assertSee('"@type":"WebPage"', false);
    }

    public function test_pages_show_404_for_missing_slug(): void
    {
        $response = $this->get('/pages/k-missing-page-' . Str::random(6));

        $response->assertStatus(404);
    }

    public function test_pages_show_guards_literal_null_string_meta(): void
    {
        // باگ لاتنت میزبان: پیش‌فرض ستون meta_title رشتهٔ literal «NULL» است
        $page = $this->publishedPage(['meta_title' => 'NULL', 'meta_description' => 'NULL']);

        $response = $this->get('/pages/' . $page->slug);

        $response->assertStatus(200);
        $content = $response->getContent();
        $this->assertStringNotContainsString('<title>NULL |', $content);
        $this->assertStringContainsString('<title>' . $page->title . ' |', $content);
    }

    // ------------------------------------------------تماس با ما

    public function test_contact_page_renders_form_and_schema(): void
    {
        $response = $this->get('/contact');

        $response->assertStatus(200);
        $response->assertSee('data-saniar-contact-form', false);
        $response->assertSee('پیام خود را با ما در میان بگذارید', false);
        $response->assertSee('"@type":"ContactPage"', false);
        $response->assertSee('name="captcha"', false);
    }

    public function test_contact_store_requires_captcha(): void
    {
        $response = $this->post('/contact', [
            'name' => 'کاربر تستی',
            'email' => 'test@example.com',
            'subject' => 'موضوع تستی',
            'message' => 'متن پیام تستی فاز K',
            // captcha عمداً ارسال نشد
        ]);

        $response->assertStatus(302); // اعتبارسنجی → ریدایرکت با خطاها
        $this->assertTrue(session('errors')?->has('captcha'));
        $this->assertDatabaseMissing('contacts', ['subject' => 'موضوع تستی']);
    }

    // ------------------------------------------------۴۰۴

    public function test_404_page_is_noindex_with_search_and_links(): void
    {
        $response = $this->get('/k-definitely-missing-' . Str::random(6));

        $response->assertStatus(404);
        $content = $response->getContent();

        // تک‌متای robots (بدون تعارض) و noindex
        $this->assertEquals(1, substr_count($content, '<meta name="robots"'));
        $this->assertStringContainsString('<meta name="robots" content="noindex, follow">', $content);

        // جستجوی نجات + دسترسی سریع
        $this->assertStringContainsString('notfound-code', $content);
        $this->assertStringContainsString('جستجوی محصولات', $content);
    }

    // ------------------------------------------------سئوی سراسری

    public function test_home_has_organization_and_search_action_schema(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $content = $response->getContent();

        $this->assertStringContainsString('"@type":"Organization"', $content);
        $this->assertStringContainsString('"@type":"WebSite"', $content);
        $this->assertStringContainsString('"@type":"SearchAction"', $content);
    }

    public function test_master_outputs_dynamic_meta_description(): void
    {
        $this->publishedPost();

        $response = $this->get('/blog/articles');

        $response->assertStatus(200);
        $content = $response->getContent();

        $this->assertStringContainsString('<meta name="description" content="', $content);
        $this->assertStringContainsString('آرشیو مقالات', $content);
    }

    public function test_spa_assets_present_on_storefront(): void
    {
        $response = $this->get('/blog');

        $content = $response->getContent();

        // هستهٔ لایووایر با data-navigate-once (جلوگیری از دوباره‌بوت در ناوبری)
        $this->assertStringContainsString('livewire.js', $content);
        $this->assertStringContainsString('data-navigate-once', $content);

        // کتابخانه‌های vendor علامت‌خورده و اسکریپت ناوبری SPA
        $this->assertStringContainsString('spa-navigate.js', $content);

        // استایل‌های هسته (نوار پیشرفت) — سلکتورها با بک‌اسلِپ می‌آیند: [wire\\:loading]
        $this->assertStringContainsString('Livewire Styles', $content);
        $this->assertStringContainsString('[wire\\:loading]', $content);

        // wire:navigate روی لینک‌های ساختاری
        $this->assertStringContainsString('wire:navigate', $content);
    }

    public function test_sitemap_articles_and_statics_render_after_fix(): void
    {
        $post = $this->publishedPost();

        $articles = $this->get('/sitemap/articles.xml');
        $articles->assertStatus(200);
        $articles->assertSee(route('front.articles.show', ['slug' => $post->slug]), false);

        $statics = $this->get('/sitemap/statics.xml');
        $statics->assertStatus(200);
        $statics->assertSee(route('front.index'), false);
        $statics->assertSee(route('front.contact.index'), false);
    }

    // ------------------------------------------------کمکی‌ها

    public function test_saniar_seo_value_helper_guards_null_string(): void
    {
        $this->assertSame('جایگزین', saniar_seo_value('NULL', 'جایگزین'));
        $this->assertSame('جایگزین', saniar_seo_value(' null ', 'جایگزین'));
        $this->assertSame('جایگزین', saniar_seo_value('', 'جایگزین'));
        $this->assertSame('متن سئو', saniar_seo_value('متن سئو', 'جایگزین'));
        $this->assertNull(saniar_seo_value('NULL'));
    }
}
