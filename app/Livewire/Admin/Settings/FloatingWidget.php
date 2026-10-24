<?php

namespace App\Livewire\Admin\Settings;

use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * پورت ۱:۱ Floatingwidgetcontroller — همه‌ی کلیدهای fw_* با option_update
 */
#[Title('ویجت شناور پشتیبانی')]
class FloatingWidget extends Component
{
    /* ---------------- کلیدهای عمومی ---------------- */

    public bool $fw_enabled = false;

    public string $fw_main_color = '#5b6af7';

    public string $fw_button_label = '';

    public string $fw_greeting = '';

    public string $fw_sub_greeting = '';

    public string $fw_working_hours = '';

    public string $fw_phone = '';

    public string $fw_email = '';

    public string $fw_address = '';

    /* ---------------- شبکه‌های اجتماعی (label + url × ۶) ---------------- */

    public string $fw_whatsapp_label = '';

    public string $fw_whatsapp_url = '';

    public string $fw_telegram_label = '';

    public string $fw_telegram_url = '';

    public string $fw_instagram_label = '';

    public string $fw_instagram_url = '';

    public string $fw_twitter_label = '';

    public string $fw_twitter_url = '';

    public string $fw_youtube_label = '';

    public string $fw_youtube_url = '';

    public string $fw_linkedin_label = '';

    public string $fw_linkedin_url = '';

    public function mount(): void
    {
        // گیت settings.floating-widget (مطابق config('general.permissions'))
        auth('adminPanel')->user()?->can('settings.floating-widget') ?: abort(403);

        // مقدار اولیه از option() — پورت رفتار back/settings/floating-widget.blade.php
        $this->fw_enabled = option('fw_enabled', '1') == '1';
        $this->fw_main_color = (string) option('fw_main_color', '#5b6af7');
        $this->fw_button_label = (string) option('fw_button_label');
        $this->fw_greeting = (string) option('fw_greeting');
        $this->fw_sub_greeting = (string) option('fw_sub_greeting');
        $this->fw_working_hours = (string) option('fw_working_hours', '');
        $this->fw_phone = (string) option('fw_phone', '');
        $this->fw_email = (string) option('fw_email', '');
        $this->fw_address = (string) option('fw_address', '');

        foreach ($this->socialNetworks() as $network => $meta) {
            $this->{'fw_' . $network . '_label'} = (string) option('fw_' . $network . '_label', $meta['label']);
            $this->{'fw_' . $network . '_url'} = (string) option('fw_' . $network . '_url', '');
        }
    }

    /** شبکه‌های اجتماعی + رنگ/آیکون برای نمایش (پورت آرایه‌های chat_items/social_items قدیمی) */
    public function socialNetworks(): array
    {
        return [
            'whatsapp'  => ['label' => 'واتساپ',       'color' => '#25D366', 'icon' => 'message-circle', 'placeholder_url' => 'https://wa.me/989123456789'],
            'telegram'  => ['label' => 'تلگرام',       'color' => '#2AABEE', 'icon' => 'send',           'placeholder_url' => 'https://t.me/username'],
            'instagram' => ['label' => 'اینستاگرام',   'color' => '#E1306C', 'icon' => 'instagram',      'placeholder_url' => 'https://instagram.com/yourpage'],
            'twitter'   => ['label' => 'توییتر/ایکس',  'color' => '#000000', 'icon' => 'twitter',        'placeholder_url' => 'https://x.com/yourpage'],
            'youtube'   => ['label' => 'یوتیوب',       'color' => '#FF0000', 'icon' => 'youtube',        'placeholder_url' => 'https://youtube.com/@yourchannel'],
            'linkedin'  => ['label' => 'لینکدین',      'color' => '#0077B5', 'icon' => 'linkedin',       'placeholder_url' => 'https://linkedin.com/company/yourpage'],
        ];
    }

    /* ---------------- ذخیره (پورت ۱:۱ update قدیمی) ---------------- */

    public function save(): void
    {
        auth('adminPanel')->user()?->can('settings.floating-widget') ?: abort(403);

        $validated = $this->validate([
            'fw_main_color'      => 'nullable|regex:/^#[0-9A-Fa-f]{6}$/',
            'fw_button_label'    => 'nullable|string|max:60',
            'fw_greeting'        => 'nullable|string|max:120',
            'fw_sub_greeting'    => 'nullable|string|max:180',
            'fw_working_hours'   => 'nullable|string|max:100',
            'fw_phone'           => 'nullable|string|max:30',
            'fw_email'           => 'nullable|email|max:120',
            'fw_address'         => 'nullable|string|max:300',

            'fw_whatsapp_label'  => 'nullable|string|max:60',
            'fw_whatsapp_url'    => 'nullable|url|max:300',
            'fw_telegram_label'  => 'nullable|string|max:60',
            'fw_telegram_url'    => 'nullable|url|max:300',
            'fw_instagram_label' => 'nullable|string|max:60',
            'fw_instagram_url'   => 'nullable|url|max:300',
            'fw_twitter_label'   => 'nullable|string|max:60',
            'fw_twitter_url'     => 'nullable|url|max:300',
            'fw_youtube_label'   => 'nullable|string|max:60',
            'fw_youtube_url'     => 'nullable|url|max:300',
            'fw_linkedin_label'  => 'nullable|string|max:60',
            'fw_linkedin_url'    => 'nullable|url|max:300',
        ], [
            'fw_main_color.regex'    => 'رنگ باید در فرمت هگز مانند #5b6af7 باشد.',
            'fw_email.email'         => 'آدرس ایمیل معتبر نیست.',
            'fw_whatsapp_url.url'    => 'لینک واتساپ معتبر نیست.',
            'fw_telegram_url.url'    => 'لینک تلگرام معتبر نیست.',
            'fw_instagram_url.url'   => 'لینک اینستاگرام معتبر نیست.',
            'fw_twitter_url.url'     => 'لینک توییتر معتبر نیست.',
            'fw_youtube_url.url'     => 'لینک یوتیوب معتبر نیست.',
            'fw_linkedin_url.url'    => 'لینک لینکدین معتبر نیست.',
        ], [
            'fw_main_color'      => 'رنگ اصلی دکمه',
            'fw_button_label'    => 'متن روی دکمه شناور',
            'fw_greeting'        => 'پیام خوشامدگویی',
            'fw_sub_greeting'    => 'زیرعنوان پنل',
            'fw_working_hours'   => 'ساعات کاری',
            'fw_phone'           => 'شماره تماس',
            'fw_email'           => 'ایمیل',
            'fw_address'         => 'آدرس',
            'fw_whatsapp_label'  => 'برچسب واتساپ',
            'fw_whatsapp_url'    => 'لینک واتساپ',
            'fw_telegram_label'  => 'برچسب تلگرام',
            'fw_telegram_url'    => 'لینک تلگرام',
            'fw_instagram_label' => 'برچسب اینستاگرام',
            'fw_instagram_url'   => 'لینک اینستاگرام',
            'fw_twitter_label'   => 'برچسب توییتر',
            'fw_twitter_url'     => 'لینک توییتر',
            'fw_youtube_label'   => 'برچسب یوتیوب',
            'fw_youtube_url'     => 'لینک یوتیوب',
            'fw_linkedin_label'  => 'برچسب لینکدین',
            'fw_linkedin_url'    => 'لینک لینکدین',
        ]);

        // همه‌ی کلیدهای fw_* — پورت آرایه‌ی $keys قدیمی
        $keys = [
            'fw_enabled', 'fw_main_color', 'fw_button_label',
            'fw_greeting', 'fw_sub_greeting', 'fw_working_hours',
            'fw_phone', 'fw_email', 'fw_address',
            'fw_whatsapp_label', 'fw_whatsapp_url',
            'fw_telegram_label', 'fw_telegram_url',
            'fw_instagram_label', 'fw_instagram_url',
            'fw_twitter_label', 'fw_twitter_url',
            'fw_youtube_label', 'fw_youtube_url',
            'fw_linkedin_label', 'fw_linkedin_url',
        ];

        foreach ($keys as $key) {
            // fw_enabled: اگر چک‌باکس نبود مقدار صفر ذخیره شود (پورت دقیق)
            if ($key === 'fw_enabled') {
                option_update($key, $this->fw_enabled ? '1' : '0');
            } else {
                option_update($key, $this->{$key} ?? '');
            }
        }

        $this->dispatch('toast', message: 'تنظیمات ویجت شناور با موفقیت ذخیره شد.', type: 'success');
    }

    public function render()
    {
        return view('admin-panel::livewire.admin.settings.floating-widget')
            ->layout('admin-panel::layout');
    }
}
