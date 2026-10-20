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

    /* ---------------- ذخیره هر درگاه در مودال (فاز ۲۰) ----------------
     * تغییر معماری از «فرم سراسری + ذخیره‌ی همه» به «مودال هر درگاه + ذخیره‌ی همان»
     * — سوییچ فعال/غیرفعال هم مستقیم روی کارت ذخیره می‌شود (toggleActive).
     * لاگ فعالیتِ تغییرات، پورت دقیق همان updateGateways قدیمی است.
     */

    /** ذخیره‌ی تنظیمات یک درگاه از مودال کارت */
    public function saveGateway(int $id): void
    {
        auth('adminPanel')->user()?->can('settings.gateway') ?: abort(403);

        $gateway = Gateway::find($id);
        $request_gateway = $this->form[$id] ?? null;

        if (! $gateway || $request_gateway === null) {
            $this->dispatch('toast', message: 'درگاه یافت نشد.', type: 'error');

            return;
        }

        // سوییچ فعال/غیرفعال مستقل است — وضعیت فعلی DB حفظ می‌شود
        $request_gateway['is_active'] = (bool) $gateway->is_active;

        $changes = $this->persistGateway($gateway, $request_gateway);
        $this->logGatewayChanges($gateway, $changes);

        $this->loadForm();

        $this->dispatch('gw-saved'); // بستن مودال کارت (Alpine)
        $this->dispatch('toast', message: 'تنظیمات درگاه «'.$gateway->name.'» ذخیره شد.', type: 'success');
    }

    /** سوییچ فعال/غیرفعال روی کارت — ذخیره‌ی فوری */
    public function toggleActive(int $id): void
    {
        auth('adminPanel')->user()?->can('settings.gateway') ?: abort(403);

        $gateway = Gateway::find($id);

        if (! $gateway) {
            $this->dispatch('toast', message: 'درگاه یافت نشد.', type: 'error');

            return;
        }

        $gateway->update(['is_active' => ! $gateway->is_active]);

        activity()
            ->causedBy(auth('adminPanel')->user())
            ->withProperties([
                'action' => $gateway->is_active ? 'فعال‌سازی درگاه پرداخت' : 'غیرفعال‌سازی درگاه پرداخت',
                'ip' => request()->ip(),
            ])
            ->log('مدیر درگاه «'.$gateway->name.'» را '.($gateway->is_active ? 'فعال' : 'غیرفعال').' کرد.');

        $this->loadForm();

        $this->dispatch('toast', message: 'درگاه «'.$gateway->name.'» '.($gateway->is_active ? 'فعال' : 'غیرفعال').' شد.', type: 'success');
    }

    /**
     * نوشتن نام/ترتیب/کانفیگ‌های یک درگاه + ساخت آرایه‌ی تغییرات (پورت دقیق updateGateways)
     *
     * @return array تغییرات برای لاگ
     */
    private function persistGateway(Gateway $gateway, array $request_gateway): array
    {
        $gatewayChanges = [];

        // مقادیر قدیمی (پورت دقیق)
        $oldData = [
            'name' => $gateway->name,
            'ordering' => $gateway->ordering,
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
            'name' => (string) ($request_gateway['name'] ?? $gateway->name),
            'ordering' => (string) ($request_gateway['ordering'] ?? '0'),
            'configs' => $newConfigs,
        ];

        if ($oldData['name'] != $newData['name']) {
            $gatewayChanges['name'] = ['old' => $oldData['name'], 'new' => $newData['name']];
        }

        if ($oldData['ordering'] != $newData['ordering']) {
            $gatewayChanges['ordering'] = ['old' => $oldData['ordering'], 'new' => $newData['ordering']];
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

        $gateway->update([
            'name' => $newData['name'],
            'ordering' => $newData['ordering'],
        ]);

        foreach ($newData['configs'] as $key => $value) {
            $gateway->configs()->updateOrCreate(['key' => $key], ['value' => $value]);
        }

        return $gatewayChanges;
    }

    /** لاگ فعالیت تغییرات درگاه (پورت دقیق) */
    private function logGatewayChanges(Gateway $gateway, array $changes): void
    {
        if (empty($changes)) {
            return;
        }

        $adminName = auth('adminPanel')->user()->full_name ?? auth('adminPanel')->user()->name ?? 'مدیر';

        $fieldChanges = [];

        if (isset($changes['name'])) {
            $fieldChanges[] = "نام از «{$changes['name']['old']}» به «{$changes['name']['new']}»";
        }

        if (isset($changes['ordering'])) {
            $fieldChanges[] = "ترتیب از {$changes['ordering']['old']} به {$changes['ordering']['new']}";
        }

        foreach ($changes['configs'] ?? [] as $configKey => $configChange) {
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

        activity()
            ->causedBy(auth('adminPanel')->user())
            ->withProperties([
                'action' => 'تنظیمات درگاه پرداخت را به‌روزرسانی کرد',
                'changes' => [$gateway->name => $changes],
                'ip' => request()->ip(),
            ])
            ->log("مدیر {$adminName} تنظیمات درگاه «{$gateway->name}» را به‌روزرسانی کرد: ".implode('، ', $fieldChanges));
    }

    public function render()
    {
        return view('admin-panel::livewire.admin.settings.gateways')
            ->layout('admin-panel::layout');
    }
}
