<?php

namespace App\Livewire\Admin\Settings;

use App\Models\Gateway;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * درگاه‌های پرداخت — پورت ۱:۱ Back\SettingController::showGateways/updateGateways
 * + پورت دقیق لیست فیلدهای $gwList از ویوی back/settings/gateways.blade.php
 *
 * مدل‌ها: Gateway (firstOrCreate از config('general.supported_gateways')) + GatewayConfig (updateOrCreate)
 */
#[Title('درگاه های پرداخت')]
class Gateways extends Component
{
    /**
     * فرم هر درگاه: [gatewayId => ['name','ordering','is_active','configs'=>[field=>value]]]
     */
    public array $form = [];

    public function mount(): void
    {
        auth('adminPanel')->user()?->can('settings.gateway') ?: abort(403);

        $this->loadForm();
    }

    /** ساخت فرم از DB (پورت showGateways: firstOrCreate + خواندن configs) */
    private function loadForm(): void
    {
        // پورت showGateways: ایجاد درگاه‌های پشتیبانی‌شده‌ی از قبل ناموجود
        foreach (config('general.supported_gateways') as $key => $name) {
            Gateway::firstOrCreate(['key' => $key], ['name' => $name]);
        }

        $gateways = Gateway::get();

        foreach ($gateways as $gateway) {
            $this->form[$gateway->id] = [
                'name' => (string) $gateway->name,
                'ordering' => (string) ($gateway->ordering !== null ? $gateway->ordering : 0),
                'is_active' => (bool) $gateway->is_active,
                'configs' => [],
            ];

            foreach ($this->gatewayFields($gateway->key) as $field) {
                $this->form[$gateway->id]['configs'][$field['name']] = (string) $gateway->config($field['name']);
            }
        }
    }

    /** پورت دقیق $gwList ویوی قدیمی — فیلدهای هر درگاه (فاز ۱۸: + فیلد لوگو برای همه) */
    public static function gatewayFields(string $key): array
    {
        $logo = [['name' => 'logo', 'label' => 'لوگوی درگاه (تصویر بانک)', 'type' => 'image']];

        $fields = match ($key) {
            'payir' => [
                ['name' => 'merchantId', 'label' => 'api کد', 'type' => 'text'],
            ],
            'behpardakht' => [
                ['name' => 'username', 'label' => 'نام کاربری', 'type' => 'text'],
                ['name' => 'password', 'label' => 'رمز عبور', 'type' => 'text'],
                ['name' => 'terminalId', 'label' => 'کد پذیرنده', 'type' => 'text'],
            ],
            'zarinpal' => [
                ['name' => 'merchantId', 'label' => 'کد درگاه پرداخت', 'type' => 'text'],
            ],
            'toman' => [
                ['name' => 'shop_slug', 'label' => 'shop slug', 'type' => 'text', 'col' => 4],
                ['name' => 'auth_code', 'label' => 'auth code', 'type' => 'text', 'col' => 8],
            ],
            'payping' => [
                ['name' => 'merchantId', 'label' => 'کد درگاه پرداخت', 'type' => 'text'],
            ],
            'irankish' => [
                ['name' => 'terminalId', 'label' => 'کد پایانه', 'type' => 'text'],
                ['name' => 'acceptorId', 'label' => 'کد پذیرنده', 'type' => 'text'],
                ['name' => 'password', 'label' => 'کلمه عبور', 'type' => 'text'],
                ['name' => 'pubKey', 'label' => 'کلید عمومی', 'type' => 'textarea', 'col' => 6],
            ],
            'idpay' => [
                ['name' => 'merchantId', 'label' => 'کد درگاه پرداخت', 'type' => 'text'],
            ],
            'sepehr' => [
                ['name' => 'terminalId', 'label' => 'کد پذیرنده', 'type' => 'text'],
            ],
            'saman' => [
                ['name' => 'merchantId', 'label' => 'کد پذیرنده', 'type' => 'text'],
            ],
            'sadad' => [
                ['name' => 'terminalId', 'label' => 'شماره پذیرنده', 'type' => 'text'],
                ['name' => 'merchantId', 'label' => 'کد پذیرنده', 'type' => 'text'],
                ['name' => 'key', 'label' => 'کلید تراکنش', 'type' => 'text'],
            ],
            'zibal' => [
                ['name' => 'merchantId', 'label' => 'کد پذیرنده', 'type' => 'text'],
            ],
            default => [],
        };

        // فیلد لوگو اول لیست می‌آید (همه‌ی درگاه‌ها پشتیبانی می‌کنند)
        array_unshift($fields, ...$logo);

        return $fields;
    }

    /** فاز ۱۸: هویت بصری هر درگاه (رنگ برند) — برای کارت‌ها و آواتار حرف‌اول */
    public static function gatewayBranding(string $key): array
    {
        return match ($key) {
            'behpardakht' => ['color' => '#E11D48', 'bank' => 'بانک ملت'],
            'payir'       => ['color' => '#0D9488', 'bank' => 'pay.ir'],
            'zarinpal'    => ['color' => '#FFB800', 'bank' => 'زرین‌پال'],
            'toman'       => ['color' => '#7C3AED', 'bank' => 'toman.ir'],
            'payping'     => ['color' => '#F97316', 'bank' => 'پی‌پینگ'],
            'irankish'    => ['color' => '#DC2626', 'bank' => 'ایران کیش'],
            'saman'       => ['color' => '#0EA5A4', 'bank' => 'بانک سامان'],
            'sepehr'      => ['color' => '#16A34A', 'bank' => 'بانک صادرات'],
            'idpay'       => ['color' => '#0EA5E9', 'bank' => 'idpay'],
            'sadad'       => ['color' => '#B91C1C', 'bank' => 'بانک ملی'],
            'zibal'       => ['color' => '#111827', 'bank' => 'زیبال'],
            default       => ['color' => '#64748B', 'bank' => ''],
        };
    }

    /** کارت‌های نمایش (ادغام لیست ثابت + ردیف DB + فرم) */
    public function getCardsProperty(): array
    {
        $cards = [];

        foreach (config('general.supported_gateways') as $key => $name) {
            $gateway = Gateway::where('key', $key)->first();

            if (! $gateway) {
                continue; // مثل قدیمی: if (!$gateway) continue;
            }

            $cards[] = [
                'id' => $gateway->id,
                'key' => $key,
                'label' => $name,
                'name' => $this->form[$gateway->id]['name'] ?? $gateway->name,
                'ordering' => $this->form[$gateway->id]['ordering'] ?? $gateway->ordering,
                'is_active' => (bool) ($this->form[$gateway->id]['is_active'] ?? $gateway->is_active),
                'fields' => $this->gatewayFields($key),
                'configs' => $this->form[$gateway->id]['configs'] ?? [],
            ];
        }

        return $cards;
    }

    public function getStatsProperty(): array
    {
        $cards = $this->cards;

        return [
            'active' => count(array_filter($cards, fn ($c) => $c['is_active'])),
            'total' => count($cards),
        ];
    }

    /* ---------------- ذخیره (پورت updateGateways) ---------------- */

    public function save(): void
    {
        auth('adminPanel')->user()?->can('settings.gateway') ?: abort(403);

        $active_ids = [];
        $allChanges = [];

        foreach ($this->form as $id => $request_gateway) {
            if (empty($request_gateway['is_active'])) {
                continue;
            }

            $active_ids[] = (int) $id;
            $gateway = Gateway::find($id);

            if (! $gateway) {
                continue;
            }

            // مقادیر قدیمی (پورت دقیق)
            $oldData = [
                'name' => $gateway->name,
                'ordering' => $gateway->ordering,
                'is_active' => $gateway->is_active,
                'configs' => [],
            ];

            foreach ($gateway->configs as $config) {
                $oldData['configs'][$config->key] = $config->value;
            }

            // مقادیر جدید — فقط فیلدهای تعریف‌شده‌ی همان درگاه
            $newConfigs = [];
            foreach ($this->gatewayFields($gateway->key) as $field) {
                $newConfigs[$field['name']] = (string) ($request_gateway['configs'][$field['name']] ?? '');
            }

            $newData = [
                'name' => (string) $request_gateway['name'],
                'ordering' => (string) $request_gateway['ordering'],
                'is_active' => true,
                'configs' => $newConfigs,
            ];

            // بررسی تغییرات
            $gatewayChanges = [];

            if ($oldData['name'] != $newData['name']) {
                $gatewayChanges['name'] = ['old' => $oldData['name'], 'new' => $newData['name']];
            }

            if ($oldData['ordering'] != $newData['ordering']) {
                $gatewayChanges['ordering'] = ['old' => $oldData['ordering'], 'new' => $newData['ordering']];
            }

            if ($oldData['is_active'] != $newData['is_active']) {
                $gatewayChanges['is_active'] = [
                    'old' => $oldData['is_active'] ? 'فعال' : 'غیرفعال',
                    'new' => $newData['is_active'] ? 'فعال' : 'غیرفعال',
                ];
            }

            foreach ($newData['configs'] as $key => $newValue) {
                $oldValue = $oldData['configs'][$key] ?? null;
                if ($oldValue != $newValue) {
                    $gatewayChanges['configs'][$key] = ['old' => $oldValue, 'new' => $newValue];
                }
            }

            foreach ($oldData['configs'] as $key => $oldValue) {
                if (! isset($newData['configs'][$key])) {
                    $gatewayChanges['configs'][$key] = ['old' => $oldValue, 'new' => 'حذف شده'];
                }
            }

            if (! empty($gatewayChanges)) {
                $allChanges[$gateway->name] = $gatewayChanges;
            }

            // آپدیت (پورت دقیق)
            $gateway->update([
                'name' => $newData['name'],
                'ordering' => $newData['ordering'],
                'is_active' => true,
            ]);

            foreach ($newData['configs'] as $key => $value) {
                $gateway->configs()->updateOrCreate(['key' => $key], ['value' => $value]);
            }
        }

        // غیرفعال کردن درگاه‌هایی که در لیست نیستند
        $deactivatedGateways = Gateway::whereNotIn('id', $active_ids)->get();
        foreach ($deactivatedGateways as $gateway) {
            if ($gateway->is_active) {
                $allChanges[$gateway->name] = [
                    'is_active' => ['old' => 'فعال', 'new' => 'غیرفعال'],
                ];
            }
        }

        Gateway::whereNotIn('id', $active_ids)->update(['is_active' => false]);

        // لاگ کامل (پورت دقیق)
        if (! empty($allChanges)) {
            $adminName = auth('adminPanel')->user()->full_name ?? auth('adminPanel')->user()->name ?? 'مدیر';

            $logDetails = [];
            foreach ($allChanges as $gatewayName => $changes) {
                $changeText = "درگاه «{$gatewayName}»: ";
                $fieldChanges = [];

                if (isset($changes['name'])) {
                    $fieldChanges[] = "نام از «{$changes['name']['old']}» به «{$changes['name']['new']}»";
                }
                if (isset($changes['ordering'])) {
                    $fieldChanges[] = "ترتیب از {$changes['ordering']['old']} به {$changes['ordering']['new']}";
                }
                if (isset($changes['is_active'])) {
                    $fieldChanges[] = "وضعیت از {$changes['is_active']['old']} به {$changes['is_active']['new']}";
                }
                if (isset($changes['configs'])) {
                    foreach ($changes['configs'] as $configKey => $configChange) {
                        $configNames = [
                            'logo' => 'لوگوی درگاه',
                            'merchantId' => 'کد درگاه',
                            'terminalId' => 'کد پایانه',
                            'acceptorId' => 'کد پذیرنده',
                            'username' => 'نام کاربری',
                            'password' => 'رمز عبور',
                            'key' => 'کلید',
                            'shop_slug' => 'شناسه فروشگاه',
                            'auth_code' => 'کد احراز هویت',
                            'pubKey' => 'کلید عمومی',
                        ];
                        $configName = $configNames[$configKey] ?? $configKey;
                        $fieldChanges[] = "{$configName} از «{$configChange['old']}» به «{$configChange['new']}»";
                    }
                }

                $logDetails[] = $changeText.implode('، ', $fieldChanges);
            }

            $fullLogMessage = "مدیر {$adminName} تنظیمات درگاه‌های پرداخت را به‌روزرسانی کرد: ".implode('; ', $logDetails);

            activity()
                ->causedBy(auth('adminPanel')->user())
                ->withProperties([
                    'action' => 'تنظیمات درگاه‌های پرداخت را به‌روزرسانی کرد',
                    'changes' => $allChanges,
                    'ip' => request()->ip(),
                ])
                ->log($fullLogMessage);
        }

        // همگام‌سازی وضعیت فرم با DB
        $this->loadForm();

        $this->dispatch('toast', message: 'تنظیمات درگاه‌های پرداخت ذخیره شد.', type: 'success');
    }

    public function render()
    {
        return view('admin-panel::livewire.admin.settings.gateways')
            ->layout('admin-panel::layout');
    }
}
