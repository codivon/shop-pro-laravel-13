<?php

namespace Tests\Feature\Extra;

use App\Livewire\Admin\System\Developer;
use App\Models\Admin;
use App\Models\Menu;
use App\Models\Permission;
use App\Models\SellerHero;
use App\Support\IconRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class Phase114Test extends TestCase
{
    use RefreshDatabase;

    /* ---------------- IconRegistry ---------------- */

    public function test_icon_registry_renders_svg_raw_name_fa_and_mdi(): void
    {
        // SVG خام دست‌نخورده می‌ماند
        $svg = '<svg xmlns="http://www.w3.org/2000/svg"><path d="M1"/></svg>';
        $this->assertSame($svg, IconRegistry::render($svg));

        // نام Lucide از نقشه به SVG تبدیل می‌شود
        $name = array_key_first(IconRegistry::lucide());
        $this->assertStringStartsWith('<svg', IconRegistry::render($name));
        $this->assertStringContainsString('stroke-width="2"', IconRegistry::render($name));

        // کلاس FA → <i>
        $this->assertSame('<i class="fa-solid fa-store"></i>', IconRegistry::render('fa-solid fa-store'));

        // کلاس MDI + قرارداد style → <i style>
        $this->assertSame(
            '<i class="mdi mdi-store" style="color:#ef4444;font-size:24px"></i>',
            IconRegistry::render('mdi mdi-store|color:#ef4444;font-size:24px')
        );

        // نام ناشناخته → خالی (متن خام رندر نمی‌شود)
        $this->assertSame('', IconRegistry::render('not-a-real-icon'));
        $this->assertSame('', IconRegistry::render(''));
    }

    public function test_icon_class_and_style_helpers_split_convention(): void
    {
        $this->assertSame('mdi mdi-store', icon_class('mdi mdi-store|color:#111;font-size:20px'));
        $this->assertSame('color:#111;font-size:20px', icon_style('mdi mdi-store|color:#111;font-size:20px'));
        $this->assertSame('mdi mdi-store', icon_class('mdi mdi-store'));
        $this->assertSame('', icon_style('mdi mdi-store'));
    }

    public function test_icon_picker_switch_persists_and_gates_render(): void
    {
        // پیش‌فرض: روشن
        $this->assertTrue(IconRegistry::pickerEnabled());

        $this->newCreator();

        // بی‌همتا کردن وضعیت اولیه نسبت به دیتابیس میزبان
        option_update('admin_icon_picker', '1');

        // خاموش — همان الگوی ذخیرهٔ فاز ۱۰۳ (Livewire)
        Livewire::test(Developer::class)
            ->set('adminIconPicker', false)
            ->call('saveMultiVendor')
            ->assertDispatched('toast');

        $this->assertSame('0', option('admin_icon_picker', '1'));
        $this->assertFalse(IconRegistry::pickerEnabled());

        // دوباره روشن
        Livewire::test(Developer::class)
            ->set('adminIconPicker', true)
            ->call('saveMultiVendor');

        $this->assertTrue(IconRegistry::pickerEnabled());
    }

    private function newCreator(): Admin
    {
        // سطح creator از همهٔ گیت‌ها/میدل‌ورهای پرمیشن عبور می‌کند (الگوی تست‌های قبلی + Gate::before)
        Gate::before(function ($admin) {
            return $admin instanceof Admin && $admin->level == 'creator' ? true : null;
        });

        foreach (Permission::where('active', true)->get() as $permission) {
            Gate::define($permission->name, function ($user = null) {
                $admin = $user instanceof Admin
                    ? $user
                    : Auth::guard('adminPanel')->user();

                return $admin instanceof Admin && $admin->level == 'creator';
            });
        }

        $creator = Admin::create([
            'first_name' => 'تست',
            'last_name' => 'فاز ۱۱۴',
            'username' => 'feat114-'.Str::random(6),
            'email' => 'feat114-'.Str::random(6).'@test.ir',
            'password' => Hash::make('secret1234'),
            'level' => 'creator',
            'status' => 'ACTIVE',
        ]);

        $this->actingAs($creator, 'adminPanel');

        return $creator;
    }

    /* ---------------- فیلدهای آیکون ---------------- */

    public function test_sellers_hero_and_commission_forms_show_picker_button(): void
    {
        SellerHero::query()->create([
            'title' => 'تست هیرو',
            'description' => 'توضیحات تست',
            'icon' => '<svg xmlns="http://www.w3.org/2000/svg"><path d="M1"/></svg>',
        ]);

        $this->actingAs($this->newCreator(), 'adminPanel');

        $this->get(route('np.settings.seller-hero'))
            ->assertOk()
            ->assertSee('seller-icon-input', false)
            ->assertSee('open-ap-icon-picker', false)
            // سلول جدول: SVG خام همان‌طور که هست رندر می‌شود
            ->assertSee('<path d="M1"/>', false);
    }

    public function test_menus_form_shows_picker_button(): void
    {
        $this->actingAs($this->newCreator(), 'adminPanel');

        $this->get(route('np.menus.index'))
            ->assertOk()
            ->assertSee('menu-form-icon', false)
            ->assertSee('open-ap-icon-picker', false);
    }

    public function test_price_alert_settings_show_picker_button(): void
    {
        $this->actingAs($this->newCreator(), 'adminPanel');

        $this->get(route('np.settings.others'))
            ->assertOk()
            ->assertSee('dt-price-alert-button-icon', false)
            ->assertSee('open-ap-icon-picker', false);
    }

    /* ---------------- رندر رنگ/اندازه در منوها ---------------- */

    public function test_menu_icon_with_style_convention_persists(): void
    {
        Menu::query()->create([
            'type' => 'normal',
            'lang' => app()->getLocale(),
            'title' => 'منوی تست',
            'link' => '/products',
            'icon' => 'mdi mdi-store|color:#ef4444;font-size:24px',
            'ordering' => 1,
        ]);

        $menu = Menu::query()->latest('id')->first();
        $this->assertSame('mdi mdi-store', icon_class($menu->icon));
        $this->assertSame('color:#ef4444;font-size:24px', icon_style($menu->icon));
    }
}
