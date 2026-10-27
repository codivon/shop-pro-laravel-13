<?php

namespace App\Livewire\Admin\Sellers\Settings;

use App\Support\MultiVendorSettings;
use App\Support\SellerLandingContent;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * فاز ۱۰۹ — تنظیمات فروشندگان: صفحهٔ فرود (/seller)
 *
 * محتوای لندینگ تک‌صفحه‌ای فروشندگان را ویرایش می‌کند (کلید
 * seller_landing_content در options به‌صورت JSON). بخش‌های «چرا ما»،
 * «مزایا» و «سوالات متداول» از صفحات ادمین خودشان مدیریت می‌شوند و
 * اینجا تکرار نمی‌شوند.
 *
 * دسترسی: @can('sellers') مثل بقیهٔ صفحات تنظیمات فروشندگان + نمایش/گیت
 * فقط وقتی سیستم چندفروشندگی فعال است (طبق درخواست فاز ۱۰۹).
 */
#[Title('تنظیمات فروشندگان — صفحهٔ فرود')]
class SellerLanding extends Component
{
    /* ================= هیرو ================= */
    public string $heroTitle = '';

    public string $heroSubtitle = '';

    public string $heroShopLabel = '';

    public string $heroMobileLabel = '';

    public string $heroButton = '';

    public string $heroResponseTime = '';

    public string $heroImage = '';

    /* ================= بخش‌های تکرارشو ================= */

    /** [['value' => '', 'label' => ''], ...] — value خالی ⇒ عدد واقعی از دیتابیس */
    public array $stats = [];

    /** [['title' => '', 'features' => 'خط‌به‌خط'], ...] — features بعداً به آرایه تبدیل می‌شود */
    public array $plans = [];

    /** [['title' => '', 'description' => ''], ...] */
    public array $steps = [];

    /** [['name' => '', 'business' => '', 'quote' => ''], ...] */
    public array $testimonials = [];

    /** [['name' => '', 'tag' => '', 'metric' => ''], ...] */
    public array $brands = [];

    /** ['title' => '', 'button' => '', 'link' => '', 'pills' => 'با ویرگول جدا شود'] */
    public array $app = ['title' => '', 'button' => '', 'link' => '', 'pills' => ''];

    public function mount(): void
    {
        auth('adminPanel')->user()?->can('sellers') ?: abort(403);

        // طبق درخواست فاز ۱۰۹ — این صفحه فقط با چندفروشندگی فعال معنا دارد
        if (! MultiVendorSettings::enabled()) {
            abort(404);
        }

        $this->fillFromContent(SellerLandingContent::get());
    }

    private function fillFromContent(array $content): void
    {
        $hero = $content['hero'] ?? [];

        $this->heroTitle = (string) ($hero['title'] ?? '');
        $this->heroSubtitle = (string) ($hero['subtitle'] ?? '');
        $this->heroShopLabel = (string) ($hero['shop_label'] ?? '');
        $this->heroMobileLabel = (string) ($hero['mobile_label'] ?? '');
        $this->heroButton = (string) ($hero['button'] ?? '');
        $this->heroResponseTime = (string) ($hero['response_time'] ?? '');
        $this->heroImage = (string) ($hero['image'] ?? '');

        $this->stats = $this->normalizeRows($content['stats'] ?? [], ['value', 'label']);
        $this->steps = $this->normalizeRows($content['steps'] ?? [], ['title', 'description']);
        $this->testimonials = $this->normalizeRows($content['testimonials'] ?? [], ['name', 'business', 'quote']);
        $this->brands = $this->normalizeRows($content['brands'] ?? [], ['name', 'tag', 'metric']);

        // پلن‌ها: features آرایه ⇒ textarea خط‌به‌خط
        $this->plans = [];

        foreach ((array) ($content['plans'] ?? []) as $plan) {
            $this->plans[] = [
                'title' => (string) ($plan['title'] ?? ''),
                'features' => implode("\n", (array) ($plan['features'] ?? [])),
            ];
        }

        if ($this->plans === []) {
            $this->plans[] = ['title' => '', 'features' => ''];
        }

        $app = (array) ($content['app'] ?? []);

        $this->app = [
            'title' => (string) ($app['title'] ?? ''),
            'button' => (string) ($app['button'] ?? ''),
            'link' => (string) ($app['link'] ?? ''),
            'pills' => implode('، ', (array) ($app['pills'] ?? [])),
        ];
    }

    /** ردیف‌ها را هم‌شکل می‌کند تا bind مستقیم wire:model نشکند */
    private function normalizeRows(array $rows, array $keys): array
    {
        $normalized = [];

        foreach ((array) $rows as $row) {
            $row = (array) $row;
            $item = [];

            foreach ($keys as $key) {
                $item[$key] = (string) ($row[$key] ?? '');
            }

            $normalized[] = $item;
        }

        return $normalized;
    }

    /* ================= افزودن/حذف ردیف ================= */

    public function addStat(): void
    {
        $this->stats[] = ['value' => '', 'label' => ''];
    }

    public function removeStat(int $index): void
    {
        unset($this->stats[$index]);
        $this->stats = array_values($this->stats);
    }

    public function addPlan(): void
    {
        $this->plans[] = ['title' => '', 'features' => ''];
    }

    public function removePlan(int $index): void
    {
        unset($this->plans[$index]);
        $this->plans = array_values($this->plans);
    }

    public function addStep(): void
    {
        $this->steps[] = ['title' => '', 'description' => ''];
    }

    public function removeStep(int $index): void
    {
        unset($this->steps[$index]);
        $this->steps = array_values($this->steps);
    }

    public function addTestimonial(): void
    {
        $this->testimonials[] = ['name' => '', 'business' => '', 'quote' => ''];
    }

    public function removeTestimonial(int $index): void
    {
        unset($this->testimonials[$index]);
        $this->testimonials = array_values($this->testimonials);
    }

    public function addBrand(): void
    {
        $this->brands[] = ['name' => '', 'tag' => '', 'metric' => ''];
    }

    public function removeBrand(int $index): void
    {
        unset($this->brands[$index]);
        $this->brands = array_values($this->brands);
    }

    /* ================= ذخیره ================= */

    public function save(): void
    {
        $this->validate([
            'heroTitle' => 'required',
            'heroButton' => 'required',
        ], [
            'heroTitle.required' => 'عنوان هیرو الزامی است.',
            'heroButton.required' => 'متن دکمهٔ هیرو الزامی است.',
        ]);

        $payload = [
            'hero' => [
                'title' => $this->heroTitle,
                'subtitle' => $this->heroSubtitle,
                'shop_label' => $this->heroShopLabel,
                'mobile_label' => $this->heroMobileLabel,
                'button' => $this->heroButton,
                'response_time' => $this->heroResponseTime,
                'image' => $this->heroImage,
            ],
            'stats' => $this->dropEmptyRows($this->stats, ['label']),
            'plans' => collect($this->plans)
                ->map(fn ($plan) => [
                    'title' => trim((string) ($plan['title'] ?? '')),
                    'features' => array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', (string) ($plan['features'] ?? '')) ?: []), fn ($f) => $f !== '')),
                ])
                ->filter(fn ($plan) => $plan['title'] !== '')
                ->values()
                ->all(),
            'steps' => $this->dropEmptyRows($this->steps, ['title', 'description']),
            'testimonials' => $this->dropEmptyRows($this->testimonials, ['name', 'business', 'quote']),
            'brands' => $this->dropEmptyRows($this->brands, ['name', 'tag', 'metric']),
            'app' => [
                'title' => $this->app['title'],
                'button' => $this->app['button'],
                'link' => $this->app['link'],
                'pills' => array_values(array_filter(array_map('trim', preg_split('/،|,/', (string) $this->app['pills']) ?: []), fn ($p) => $p !== '')),
            ],
        ];

        option_update(SellerLandingContent::KEY, json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        $this->dispatch('toast', message: 'محتوای صفحهٔ فرود فروشندگان ذخیره شد.', type: 'success');
    }

    /** ردیف‌هایی که همهٔ فیلدهای کلیدی‌شان خالی است را می‌اندازد */
    private function dropEmptyRows(array $rows, array $keys): array
    {
        return collect($rows)
            ->map(fn ($row) => (array) $row)
            ->filter(fn ($row) => collect($keys)->contains(fn ($key) => trim((string) ($row[$key] ?? '')) !== ''))
            ->map(fn ($row) => collect($keys)
                ->mapWithKeys(fn ($key) => [$key => (string) ($row[$key] ?? '')])
                ->all())
            ->values()
            ->all();
    }

    /** بازگشت به پیش‌فرض‌ها (پاک‌کردن کلید options) */
    public function resetDefaults(): void
    {
        option_update(SellerLandingContent::KEY, '');

        $this->fillFromContent(SellerLandingContent::get());

        $this->dispatch('toast', message: 'محتوا به پیش‌فرض بازگشت.', type: 'success');
    }

    public function render()
    {
        return view('admin-panel::livewire.admin.sellers.settings.landing')
            ->layout('admin-panel::layout');
    }
}
