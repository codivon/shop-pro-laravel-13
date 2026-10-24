<?php

namespace Tests\Feature\Extra;

use App\Jobs\InstallPackageJob;
use App\Jobs\InstallThemeJob;
use App\Livewire\Admin\Packages\PackageIndex;
use App\Models\Admin;
use App\Models\InstalledModule;
use App\Models\InstalledTheme;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * تست‌های قفل نصب همزمان بازار (فاز ۶۴) — درخواست کاربر:
 *  - تا وقتی یک پکیج/قالب در حال نصب است، نباید بتوان پکیج/قالب دیگری را نصب کرد
 *  - مودال پیشرفت حین نصب قابل بستن نباشد (سمت سرور: dismissProgress فقط پس از پایان معنا دارد
 *    و blade دکمهٔ بستن/Esc/بک‌دراپ‌کلیک را حین نصب رندر نمی‌کند)
 *  - با رفرش صفحه هم مودال حین نصب دوباره باز می‌شود (resumeActiveInstall)
 *  - رکورد updating گیرکرده (بیش از ۱۰ دقیقه) قفل دائمی نسازد
 */
class InstallLockTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        Queue::fake();
        Cache::flush();
    }

    /** فیک HTTP — الگوهای مشخص + catch-all امن */
    private function fakeHttp(array $patterns = []): void
    {
        Http::fake(array_merge($patterns, [
            '*' => Http::response([]),
        ]));
    }

    private function actingCreator(): Admin
    {
        $creator = Admin::create([
            'first_name' => 'تست',
            'last_name'  => 'قفل',
            'username'   => 'lock-'.random_int(10000, 99999),
            'email'      => 'lock-'.random_int(10000, 99999).'@test.ir',
            'password'   => \Illuminate\Support\Facades\Hash::make('secret1234'),
            'level'      => 'creator',
            'status'     => 'ACTIVE',
        ]);
        $this->actingAs($creator, 'adminPanel');

        // گیت‌های پرمیشن در کنسول ثبت نمی‌شوند — مثل منطق واقعی creator همه‌چیز مجاز
        \Illuminate\Support\Facades\Gate::before(function ($user, $ability) {
            return $user instanceof Admin ? true : null;
        });

        return $creator;
    }

    private function lockMessage(): string
    {
        return 'یک نصب دیگر در جریان است؛ تا پایان آن امکان شروع نصب پکیج یا قالب دیگری وجود ندارد.';
    }

    /* =====================================================================
     *  قفل سراسری — پکیج
     * ===================================================================== */

    #[Test]
    public function package_install_is_blocked_while_another_package_is_installing(): void
    {
        $this->actingCreator();

        // پکیج دیگر در حال نصب است
        InstalledModule::markPending('pkg-a', 'پکیج الف', '1.0.0');

        $this->fakeHttp([
            '*/api/v1/packages/pkg-b' => Http::response(['data' => ['slug' => 'pkg-b', 'name' => 'پکیج ب', 'is_free' => true, 'active_versions' => [['version' => '1.0.0']]]]),
            '*/api/v1/packages/pkg-b/purchase' => Http::response(['license_key' => 'lk_b']),
        ]);

        Livewire::test(PackageIndex::class)
            ->call('startInstall', 'pkg-b')
            ->assertDispatched('toast', message: $this->lockMessage(), type: 'error');

        Queue::assertNotPushed(InstallPackageJob::class);
        $this->assertNull(InstalledModule::where('slug', 'pkg-b')->first(), 'نصب پکیج دوم نباید شروع شود');
    }

    #[Test]
    public function package_install_is_blocked_while_a_template_is_installing(): void
    {
        $this->actingCreator();

        // نصب قالب در جریان است
        InstalledTheme::markPending('moonlight', 'قالب ماه‌تاب', '1.4.2');

        $this->fakeHttp([
            '*/api/v1/packages/pkg-b' => Http::response(['data' => ['slug' => 'pkg-b', 'name' => 'پکیج ب', 'is_free' => true, 'active_versions' => [['version' => '1.0.0']]]]),
            '*/api/v1/packages/pkg-b/purchase' => Http::response(['license_key' => 'lk_b']),
        ]);

        Livewire::test(PackageIndex::class)
            ->call('startInstall', 'pkg-b')
            ->assertDispatched('toast', message: $this->lockMessage(), type: 'error');

        Queue::assertNotPushed(InstallPackageJob::class);
    }

    #[Test]
    public function same_slug_install_is_blocked_with_in_progress_message(): void
    {
        $this->actingCreator();

        InstalledModule::markPending('pkg-a', 'پکیج الف', '1.0.0');

        Livewire::test(PackageIndex::class)
            ->call('startInstall', 'pkg-a')
            ->assertDispatched('toast', message: 'نصب این مورد همچنان در جریان است؛ لطفاً تا پایان آن صبر کنید.', type: 'error');

        Queue::assertNotPushed(InstallPackageJob::class);
    }

    #[Test]
    public function install_lock_releases_when_installation_finishes(): void
    {
        $this->actingCreator();

        $module = InstalledModule::markPending('pkg-a', 'پکیج الف', '1.0.0');
        $module->update(['status' => InstalledModule::STATUS_INSTALLED]);

        $this->assertFalse((new PackageIndex)->hasOtherInstallationInProgress('pkg-b'));

        $this->fakeHttp([
            '*/api/v1/packages/pkg-b' => Http::response(['data' => ['slug' => 'pkg-b', 'name' => 'پکیج ب', 'is_free' => true, 'active_versions' => [['version' => '1.0.0']]]]),
            '*/api/v1/packages/pkg-b/purchase' => Http::response(['license_key' => 'lk_b']),
        ]);

        Livewire::test(PackageIndex::class)
            ->call('startInstall', 'pkg-b');

        Queue::assertPushed(InstallPackageJob::class, fn (InstallPackageJob $job) => $job->slug === 'pkg-b');
    }

    #[Test]
    public function stale_updating_record_does_not_block_new_installs(): void
    {
        $this->actingCreator();

        // رکورد گیرکرده: updating اما قدیمی‌تر از پنجرهٔ ۱۰ دقیقه‌ای (مثل کرش worker)
        InstalledModule::markPending('pkg-a', 'پکیج الف', '1.0.0');
        InstalledModule::where('slug', 'pkg-a')->update(['updated_at' => now()->subMinutes(11)]);

        $this->assertFalse((new PackageIndex)->hasOtherInstallationInProgress());

        $this->fakeHttp([
            '*/api/v1/packages/pkg-b' => Http::response(['data' => ['slug' => 'pkg-b', 'name' => 'پکیج ب', 'is_free' => true, 'active_versions' => [['version' => '1.0.0']]]]),
            '*/api/v1/packages/pkg-b/purchase' => Http::response(['license_key' => 'lk_b']),
        ]);

        Livewire::test(PackageIndex::class)
            ->call('startInstall', 'pkg-b');

        Queue::assertPushed(InstallPackageJob::class, fn (InstallPackageJob $job) => $job->slug === 'pkg-b');
    }

    /* =====================================================================
     *  قفل سراسری — قالب
     * ===================================================================== */

    #[Test]
    public function template_purchase_is_blocked_while_a_package_is_installing(): void
    {
        $this->actingCreator();

        InstalledModule::markPending('pkg-a', 'پکیج الف', '1.0.0');

        $this->fakeHttp([
            '*/api/v1/templates/moonlight/purchase' => Http::response(['is_free' => true, 'download_token' => 'dl_x']),
        ]);

        Livewire::test(PackageIndex::class)
            ->set('tDetail', ['slug' => 'moonlight', 'name' => 'قالب ماه‌تاب', 'latest_version' => ['version' => '1.4.2']])
            ->call('startTemplatePurchase')
            ->assertDispatched('toast', message: $this->lockMessage(), type: 'error');

        Queue::assertNotPushed(InstallThemeJob::class);
    }

    #[Test]
    public function template_reinstall_is_blocked_while_a_package_is_installing(): void
    {
        $this->actingCreator();

        InstalledModule::markPending('pkg-a', 'پکیج الف', '1.0.0');

        Livewire::test(PackageIndex::class)
            ->call('reinstallTemplate', 'moonlight')
            ->assertDispatched('toast', message: $this->lockMessage(), type: 'error');

        Queue::assertNotPushed(InstallThemeJob::class);
    }

    /* =====================================================================
     *  مودال پیشرفت بلوکه‌شده
     * ===================================================================== */

    #[Test]
    public function template_install_opens_blocking_progress_modal(): void
    {
        $this->actingCreator();

        $this->fakeHttp([
            '*/api/v1/templates' => Http::response(['data' => [], 'meta' => ['current_page' => 1, 'last_page' => 1]]),
            '*/api/v1/templates/moonlight/purchase' => Http::response([
                'is_free'        => true,
                'download_token' => 'dl_repeat',
                'version'        => '1.4.2',
            ]),
        ]);

        Livewire::test(PackageIndex::class)
            ->set('tDetail', ['slug' => 'moonlight', 'name' => 'قالب ماه‌تاب', 'latest_version' => ['version' => '1.4.2']])
            ->call('startTemplatePurchase')
            ->assertSet('progressKind', 'template')
            ->assertSet('progressSlug', 'moonlight')
            ->assertSee('قابل بستن نیست', false); // پانویسِ قفل حین نصب

        Queue::assertPushed(InstallThemeJob::class);
    }

    #[Test]
    public function market_reopens_blocking_modal_for_active_install_on_load(): void
    {
        $this->actingCreator();

        // نصب در جریان — بعد از رفرش صفحه هم مودال باید دوباره باز شود
        InstalledModule::markPending('pkg-a', 'پکیج الف', '1.0.0');

        $this->fakeHttp();

        Livewire::test(PackageIndex::class)
            ->assertSet('progressKind', 'package')
            ->assertSet('progressSlug', 'pkg-a')
            ->assertSee('در حال نصب', false)
            ->assertSee('قابل بستن نیست', false);

        // حین نصب نباید دکمهٔ بستن رندر شود
        $html = Livewire::test(PackageIndex::class)->html();
        $this->assertStringNotContainsString('dismissProgress', $html, 'حین نصب هیچ مسیر بستنی نباید در DOM باشد');
    }

    #[Test]
    public function progress_modal_is_dismissible_after_installation_completes(): void
    {
        $this->actingCreator();

        InstalledModule::markPending('pkg-a', 'پکیج الف', '1.0.0');

        $component = Livewire::test(PackageIndex::class);
        $this->assertSame('pkg-a', $component->get('progressSlug'));

        // نصب تمام می‌شود
        InstalledModule::where('slug', 'pkg-a')->update(['status' => InstalledModule::STATUS_INSTALLED]);

        $component->call('pollProgress')
            ->assertSet('progressSlug', null) // مودال بسته شد
            ->assertDispatched('toast', type: 'success');
    }

    #[Test]
    public function dismiss_progress_resets_modal_state(): void
    {
        $this->actingCreator();

        InstalledModule::markPending('pkg-a', 'پکیج الف', '1.0.0');

        Livewire::test(PackageIndex::class)
            ->call('dismissProgress')
            ->assertSet('progressSlug', null)
            ->assertSet('progressName', null)
            ->assertSet('progressState', []);
    }
}
