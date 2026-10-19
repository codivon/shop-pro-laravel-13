<?php

namespace App\Livewire\Admin\Settings;

use App\Models\RobotsSetting;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('مدیریت robots.txt')]
class Robots extends Component
{
    public string $mode = 'production';

    public array $disallow = [];

    public array $allow = [];

    public $crawlDelay = 5;

    public string $sitemap = '';

    public function mount(): void
    {
        // پورت دقیق index قدیمی — فقط سازنده اجازه دارد
        if (! auth('adminPanel')->user()->isCreator()) {
            abort(403, 'شما دسترسی به این بخش را ندارید.');
        }

        $settings = RobotsSetting::getSettings();

        $this->mode       = $settings['mode'];
        $this->disallow   = $settings['disallow'];
        $this->allow      = $settings['allow'];
        $this->crawlDelay = $settings['crawl_delay'];
        $this->sitemap    = $settings['sitemap'];
    }

    /* ---------------- پیش‌نمایش زنده (پورت preview قدیمی) ---------------- */

    #[Computed]
    public function preview(): string
    {
        return $this->generateRobotsTxt($this->settingsFromState());
    }

    protected function settingsFromState(): array
    {
        return [
            'mode'        => $this->mode,
            'disallow'    => $this->disallow,
            'allow'       => $this->allow,
            'crawl_delay' => $this->crawlDelay,
            'sitemap'     => $this->sitemap ?: url('/sitemap.xml'),
        ];
    }

    /* ---------------- ذخیره (پورت ۱:۱ update قدیمی) ---------------- */

    public function save(): void
    {
        // رول‌های validate قدیمی
        $this->validate([
            'mode'        => 'required|in:production,development,disabled',
            'disallow'    => 'required|array',
            'disallow.*'  => 'string',
            'allow'       => 'nullable|array',
            'allow.*'     => 'string',
            'crawlDelay'  => 'required|integer|min:0|max:60',
            'sitemap'     => 'nullable|url',
        ], [], [
            'mode'       => 'حالت عملکرد',
            'disallow'   => 'مسیرهای ممنوع',
            'allow'      => 'مسیرهای استثنا',
            'crawlDelay' => 'تأخیر کراول',
            'sitemap'    => 'آدرس Sitemap',
        ]);

        // ذخیره تنظیمات (پورت updateOrCreate حلقه قدیمی)
        $settings = [
            'mode'        => $this->mode,
            'disallow'    => $this->disallow,
            'allow'       => $this->allow,
            'crawl_delay' => $this->crawlDelay,
            'sitemap'     => $this->sitemap ?? url('/sitemap.xml'),
        ];

        foreach ($settings as $key => $value) {
            RobotsSetting::updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }

        // تولید robots.txt (پورت generateRobotsFile)
        $this->generateRobotsFile($settings);

        // ثبت لاگ (پورت دقیق پیام قدیمی)
        $adminName = auth('adminPanel')->user()->full_name ?? auth('adminPanel')->user()->name ?? 'مدیر';
        activity()
            ->causedBy(auth('adminPanel')->user())
            ->withProperties([
                'action'   => 'update_robots',
                'settings' => $settings,
                'ip'       => request()->ip(),
            ])
            ->log("مدیر {$adminName} تنظیمات robots.txt را به‌روزرسانی کرد");

        $this->dispatch('toast', message: 'تنظیمات robots.txt با موفقیت ذخیره شد', type: 'success');
    }

    /* ---------------- پورت کامل generateRobotsTxt قدیمی ---------------- */

    public function generateRobotsTxt($settings)
    {
        $content = "# robots.txt\n";
        $content .= '# Generated: ' . now() . "\n";
        $content .= '# Mode: ' . $settings['mode'] . "\n\n";

        if ($settings['mode'] === 'disabled') {
            $content .= "User-agent: *\n";
            $content .= "Disallow: /\n";
            $content .= 'Sitemap: ' . ($settings['sitemap'] ?? url('/sitemap.xml')) . "\n";

            return $content;
        }

        if ($settings['mode'] === 'development') {
            $content .= "User-agent: *\n";
            $content .= "Disallow: /\n";
            $content .= 'Sitemap: ' . ($settings['sitemap'] ?? url('/sitemap.xml')) . "\n";

            return $content;
        }

        // حالت production
        $content .= "# All robots\n";
        $content .= "User-agent: *\n";
        $content .= 'Crawl-delay: ' . ($settings['crawl_delay'] ?? 5) . "\n";

        // Disallow
        foreach ($settings['disallow'] ?? [] as $path) {
            if (! empty($path)) {
                $content .= 'Disallow: ' . $path . "\n";
            }
        }

        // Allow
        foreach ($settings['allow'] ?? [] as $path) {
            if (! empty($path)) {
                $content .= 'Allow: ' . $path . "\n";
            }
        }

        // Sitemap
        $content .= "\n";
        $content .= "# Sitemap\n";
        $content .= 'Sitemap: ' . ($settings['sitemap'] ?? url('/sitemap.xml')) . "\n";

        // ربات‌های خاص
        $content .= "\n";
        $content .= "# Googlebot\n";
        $content .= "User-agent: Googlebot\n";
        $content .= "Crawl-delay: 1\n";

        $content .= "\n";
        $content .= "# Bingbot\n";
        $content .= "User-agent: Bingbot\n";
        $content .= "Crawl-delay: 2\n";

        return $content;
    }

    /* ---------------- پورت کامل generateRobotsFile قدیمی ---------------- */

    public function generateRobotsFile($settings)
    {
        $content = $this->generateRobotsTxt($settings);
        file_put_contents(public_path('robots.txt'), $content);

        return $content;
    }

    /* ---------------- ریپیتر مسیرها (جایگزین JS خارجی قدیمی) ---------------- */

    public function addDisallow(?string $path = ''): void
    {
        $this->disallow[] = $path ?? '';
    }

    public function removeDisallow(int $index): void
    {
        unset($this->disallow[$index]);
        $this->disallow = array_values($this->disallow);
    }

    public function addAllow(?string $path = ''): void
    {
        $this->allow[] = $path ?? '';
    }

    public function removeAllow(int $index): void
    {
        unset($this->allow[$index]);
        $this->allow = array_values($this->allow);
    }

    public function render()
    {
        return view('admin-panel::livewire.admin.settings.robots')
            ->layout('admin-panel::layout');
    }
}
