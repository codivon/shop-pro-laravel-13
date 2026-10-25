<?php

namespace Tests\Unit;

use App\Models\Sms;
use App\Services\Sms\FarazSms;
use App\Services\Sms\IdehPardazanSms;
use App\Services\Sms\IppanelSms;
use App\Services\Sms\KavenegarSms;
use App\Services\Sms\MelipayamakSms;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * فاز ۸۰ — نوع‌های پیامکی پکیج «مارکتینگ و پیام‌رسانی» (MarketingMessaging)
 *
 * دو نوع جدید در هسته ثبت می‌شود (پریسدنت فاز ۷۸ برای PRICE_ALERT):
 *  - CART_RECOVERY: یادآور سبد خرید رهاشده (متغیرهای name/items/total)
 *  - MARKETING_CAMPAIGN: کمپین پیامکی هدفمند (متغیرهای name/store)
 *
 * قرارداد: هر نوع باید «متد» متناظر در هر ۵ درایور پیامک داشته باشد و
 * کلید پترن آن در تعریف‌های UI تنظیمات پیامک (SmsSettings::patternDefinitions)
 * برای هر ۵ ارائه‌دهنده حاضر باشد — وگرنه ارسال بی‌صدا شکست می‌خورد.
 */
class SmsMarketingTypesTest extends TestCase
{
    private const DRIVERS = [
        IppanelSms::class,
        KavenegarSms::class,
        MelipayamakSms::class,
        IdehPardazanSms::class,
        FarazSms::class,
    ];

    // ==================================================================
    //  ثبت در Sms::TYPES
    // ==================================================================

    #[Test]
    public function cart_recovery_type_is_registered_with_correct_contract(): void
    {
        $type = Sms::TYPES['CART_RECOVERY'] ?? null;

        $this->assertNotNull($type, 'نوع CART_RECOVERY باید در Sms::TYPES ثبت باشد.');
        $this->assertSame('cart-recovery', $type['key']);
        $this->assertSame('cartRecovery', $type['method']);
        $this->assertSame('cartRecovery', Sms::getMethodByKey('cart-recovery'));
    }

    #[Test]
    public function marketing_campaign_type_is_registered_with_correct_contract(): void
    {
        $type = Sms::TYPES['MARKETING_CAMPAIGN'] ?? null;

        $this->assertNotNull($type, 'نوع MARKETING_CAMPAIGN باید در Sms::TYPES ثبت باشد.');
        $this->assertSame('marketing-campaign', $type['key']);
        $this->assertSame('marketingCampaign', $type['method']);
        $this->assertSame('marketingCampaign', Sms::getMethodByKey('marketing-campaign'));
    }

    // ==================================================================
    //  متد متناظر در هر ۵ درایور
    // ==================================================================

    #[Test]
    public function cart_recovery_method_exists_on_all_five_drivers(): void
    {
        foreach (self::DRIVERS as $driver) {
            $this->assertTrue(
                method_exists($driver, 'cartRecovery'),
                "درایور {$driver} باید متد cartRecovery را داشته باشد."
            );
        }
    }

    #[Test]
    public function marketing_campaign_method_exists_on_all_five_drivers(): void
    {
        foreach (self::DRIVERS as $driver) {
            $this->assertTrue(
                method_exists($driver, 'marketingCampaign'),
                "درایور {$driver} باید متد marketingCampaign را داشته باشد."
            );
        }
    }

    // ==================================================================
    //  کلیدهای پترن در UI تنظیمات پیامک
    // ==================================================================

    #[Test]
    public function marketing_pattern_codes_are_present_in_sms_settings_ui_for_all_providers(): void
    {
        if (!class_exists(\App\Livewire\Admin\Settings\SmsSettings::class)) {
            $this->markTestSkipped('پنل جدید (Livewire) روی این میزبان موجود نیست.');
        }

        $definitions = \App\Livewire\Admin\Settings\SmsSettings::patternDefinitions();

        foreach (['ippanel', 'kavenegar', 'melipayamak', 'idehpardazan', 'farazsms'] as $provider) {
            $this->assertArrayHasKey(
                'cart_recovery_pattern_code',
                $definitions[$provider] ?? [],
                "کلید پترن cart_recovery_pattern_code باید در سکشن {$provider} تنظیمات پیامک باشد."
            );
            $this->assertArrayHasKey(
                'marketing_campaign_pattern_code',
                $definitions[$provider] ?? [],
                "کلید پترن marketing_campaign_pattern_code باید در سکشن {$provider} تنظیمات پیامک باشد."
            );
        }
    }

    #[Test]
    public function price_alert_pattern_code_is_also_present_in_ui_phase78_debt(): void
    {
        // بدهی فاز ۷۸ که در فاز ۸۰ تسویه شد: کلید پترن price_alert در UI نبود
        if (!class_exists(\App\Livewire\Admin\Settings\SmsSettings::class)) {
            $this->markTestSkipped('پنل جدید (Livewire) روی این میزبان موجود نیست.');
        }

        $definitions = \App\Livewire\Admin\Settings\SmsSettings::patternDefinitions();

        foreach (['ippanel', 'kavenegar', 'melipayamak', 'idehpardazan', 'farazsms'] as $provider) {
            $this->assertArrayHasKey(
                'price_alert_pattern_code',
                $definitions[$provider] ?? [],
                "کلید پترن price_alert_pattern_code باید در سکشن {$provider} باشد (بدهی فاز ۷۸)."
            );
        }
    }
}
