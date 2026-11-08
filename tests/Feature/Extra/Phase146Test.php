<?php

namespace Tests\Feature\Extra;

use App\Livewire\Admin\Products\ProductIndex;
use App\Models\Admin;
use App\Models\Product;
use App\Services\AdminPanel\ProductPersistence;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

/**
 * فاز ۱۴۶ — تغییر وضعیت گروهی محصولات در لیست (کنار حذف گروهی)
 *
 * سه سطح پوشش:
 *   ۱) سرویس ProductPersistence::bulkStatus — آپدیت گروهی تک‌کوئری + ابطال یک‌بارهٔ
 *      کش (Product::clearCache — چون update گروهی رویداد مدل/Observer نمی‌گیرد) + رکورد فعالیت.
 *   ۲) کامپوننت Livewire ProductIndex::bulkChangeStatus/bulkChangePublished —
 *      گیت products.update، اعتبارسنجی مقادیر، ریست انتخاب و توست موفقیت.
 *   ۳) مقادیر نامعتبر/انتخاب خالی → بدون هیچ تغییری در دیتابیس.
 */
class Phase146Test extends TestCase
{
    use \Illuminate\Foundation\Testing\DatabaseTransactions;

    /* ---------------- سرویس ---------------- */

    public function test_service_bulk_status_updates_products_flushes_cache_and_logs(): void
    {
        // همه با وضعیت Accept تا آپدیت گروهی به Waiting دقیقاً count برابر تعداد برگرداند
        [$ids] = $this->createProducts([
            ['status' => 'Accept'],
            ['status' => 'Accept'],
            ['status' => 'Accept'],
        ]);

        // کش شمارندهٔ پنل که باید با تغییر وضعیت ابطال شود
        Cache::put('admin.products_count', 999);

        $count = app(ProductPersistence::class)->bulkStatus(
            $ids,
            ['status' => 'Waiting'],
            'تغییر وضعیت تایید آزمون گروهی',
        );

        $this->assertSame(count($ids), $count);

        foreach ($ids as $id) {
            $this->assertSame('Waiting', Product::find($id)->status);
        }

        $this->assertNull(Cache::get('admin.products_count'), 'کش admin.products_count باید ابطال می‌شد');

        $activity = Activity::where('description', 'تغییر وضعیت تایید آزمون گروهی')->latest('id')->first();
        $this->assertNotNull($activity, 'رکورد فعالیت تغییر وضعیت گروهی باید ثبت می‌شد');
        $this->assertSame('bulk_status_product', $activity->properties['action'] ?? null);
        $this->assertSame('Waiting', $activity->properties['changes']['status'] ?? null);
    }

    public function test_service_bulk_published_updates_boolean(): void
    {
        [$ids, $products] = $this->createProducts([
            ['published' => true],
            ['published' => false],
        ]);

        app(ProductPersistence::class)->bulkStatus(
            $ids,
            ['published' => false],
            'تغییر وضعیت انتشار آزمون گروهی',
        );

        foreach ($ids as $id) {
            $this->assertFalse((bool) Product::find($id)->published);
        }

        // برگشت به انتشار
        app(ProductPersistence::class)->bulkStatus(
            $ids,
            ['published' => true],
            'تغییر وضعیت انتشار آزمون گروهی ۲',
        );

        foreach ($ids as $id) {
            $this->assertTrue((bool) Product::find($id)->published);
        }
    }

    /* ---------------- کامپوننت Livewire ---------------- */

    public function test_bulk_change_status_updates_db_and_resets_selection(): void
    {
        $this->newCreator();

        [$ids, $products] = $this->createProducts([
            ['status' => 'Accept'],
            ['status' => 'Waiting'],
        ]);

        $component = Livewire::test(ProductIndex::class)
            ->set('selected', $ids)
            ->call('bulkChangeStatus', 'Reject');

        $component->assertDispatched('toast', type: 'success');
        $component->assertSet('selected', []);
        $component->assertSet('selectAll', false);

        foreach ($ids as $id) {
            $this->assertSame('Reject', Product::find($id)->status);
        }
    }

    public function test_bulk_change_published_via_livewire(): void
    {
        $this->newCreator();

        [$ids] = $this->createProducts([
            ['published' => true],
            ['published' => true],
        ]);

        Livewire::test(ProductIndex::class)
            ->set('selected', $ids)
            ->call('bulkChangePublished', '0')
            ->assertDispatched('toast', type: 'success');

        foreach ($ids as $id) {
            $this->assertFalse((bool) Product::find($id)->published);
        }

        Livewire::test(ProductIndex::class)
            ->set('selected', $ids)
            ->call('bulkChangePublished', '1')
            ->assertDispatched('toast', type: 'success');

        foreach ($ids as $id) {
            $this->assertTrue((bool) Product::find($id)->published);
        }
    }

    public function test_bulk_change_status_with_invalid_value_changes_nothing(): void
    {
        $this->newCreator();

        [$ids] = $this->createProducts([
            ['status' => 'Accept'],
        ]);

        Livewire::test(ProductIndex::class)
            ->set('selected', $ids)
            ->call('bulkChangeStatus', 'Hacked')
            ->assertNotDispatched('toast');

        $this->assertSame('Accept', Product::find($ids[0])->status);

        Livewire::test(ProductIndex::class)
            ->set('selected', $ids)
            ->call('bulkChangePublished', 'maybe')
            ->assertNotDispatched('toast');

        $this->assertTrue((bool) Product::find($ids[0])->published);
    }

    public function test_bulk_change_status_with_empty_selection_is_noop(): void
    {
        $this->newCreator();

        [$ids] = $this->createProducts([
            ['status' => 'Accept'],
        ]);

        Livewire::test(ProductIndex::class)
            ->call('bulkChangeStatus', 'Waiting')
            ->assertNotDispatched('toast');

        $this->assertSame('Accept', Product::find($ids[0])->status);
    }

    public function test_bulk_change_status_requires_products_update_permission(): void
    {
        // ادمین بدون نقش/پرمیشن (level پایین‌تر از creator)
        Gate::before(function ($admin) {
            return $admin instanceof Admin && $admin->level == 'creator' ? true : null;
        });

        $plain = Admin::create([
            'first_name' => 'تست',
            'last_name' => 'بی‌دسترسی',
            'username' => 'feat146-plain-'.Str::random(6),
            'email' => 'feat146-plain-'.Str::random(6).'@test.ir',
            'password' => Hash::make('secret1234'),
            'level' => 'admin',
            'status' => 'ACTIVE',
        ]);

        $this->actingAs($plain, 'adminPanel');
        $this->assertFalse($plain->can('products.update'));

        [$ids] = $this->createProducts([
            ['status' => 'Accept'],
        ]);

        // Livewire در تست ممکن است HttpException را به پاسخ 403 تبدیل کند —
        // قرارداد رفتاری (الگوی فاز ۱۰۳): «هیچ تغییری رخ ندهد»؛ هر دو حالت را پوشش می‌دهیم
        try {
            Livewire::test(ProductIndex::class)
                ->set('selected', $ids)
                ->call('bulkChangeStatus', 'Waiting');
        } catch (\Throwable $e) {
            $status = method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 0;
            $this->assertSame(403, $status);
        }

        $this->assertSame('Accept', Product::find($ids[0])->status, 'وضعیت نباید تغییر می‌کرد');
    }

    public function test_selection_bar_renders_bulk_status_menu(): void
    {
        $this->newCreator();

        $component = Livewire::test(ProductIndex::class);

        // عناصر کلیدی منوی تغییر وضعیت گروهی
        $component->assertSee('bulkChangeStatus', false);
        $component->assertSee('bulkChangePublished', false);
        $component->assertSee('وضعیت تایید', false);
        $component->assertSee('وضعیت انتشار', false);
        $component->assertSee('حذف همه', false);
    }

    /* ---------------- کمکی ---------------- */

    /**
     * ساخت محصولات آزمون — بازگشت: [شناسه‌ها، مدل‌ها]
     *
     * @param  array<int, array<string, mixed>>  $overrides
     * @return array{0: list<string>, 1: \Illuminate\Support\Collection}
     */
    private function createProducts(array $overrides): array
    {
        $products = collect();
        $ids = [];

        foreach ($overrides as $override) {
            $product = Product::factory()->create(array_merge([
                'lang' => app()->getLocale(),
            ], $override));

            $products->push($product);
            $ids[] = (string) $product->id;
        }

        return [$ids, $products];
    }

    private function newCreator(): Admin
    {
        // سطح creator از همهٔ گیت‌ها/میدل‌ورهای پرمیشن عبور می‌کند (الگوی تست‌های قبلی + Gate::before)
        Gate::before(function ($admin) {
            return $admin instanceof Admin && $admin->level == 'creator' ? true : null;
        });

        foreach (\App\Models\Permission::where('active', true)->get() as $permission) {
            Gate::define($permission->name, function ($user = null) {
                $admin = $user instanceof Admin
                    ? $user
                    : Auth::guard('adminPanel')->user();

                return $admin instanceof Admin && $admin->level == 'creator';
            });
        }

        $creator = Admin::create([
            'first_name' => 'تست',
            'last_name' => 'فاز ۱۴۶',
            'username' => 'feat146-'.Str::random(6),
            'email' => 'feat146-'.Str::random(6).'@test.ir',
            'password' => Hash::make('secret1234'),
            'level' => 'creator',
            'status' => 'ACTIVE',
        ]);

        $this->actingAs($creator, 'adminPanel');

        return $creator;
    }
}
