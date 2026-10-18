<?php

namespace App\Livewire\Admin\Finance;

use App\Events\WalletAmountDecreased;
use App\Events\WalletAmountIncreased;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Title;
use Livewire\Component;
use Morilog\Jalali\Jalalian;

#[Title('ایجاد تراکنش کیف پول')]
class WalletTransactionCreate extends Component
{
    public Wallet $wallet;

    /* ---------------- فیلدهای فرم (پورت فرم create قدیمی) ---------------- */

    public string $amount = '';

    public string $type = 'deposit';

    public string $description = '';

    public function mount(Wallet $wallet): void
    {
        auth('adminPanel')->user()?->can('users.wallet') ?: abort(403);

        $this->wallet = $wallet;
    }

    protected function validationAttributes(): array
    {
        return [
            'amount' => 'مبلغ',
            'type' => 'نوع عملیات',
            'description' => 'توضیحات',
        ];
    }

    /**
     * ذخیره — پورت دقیق WalletController::store قدیمی:
     * validate(type/amount/description) + max:100000000
     * → data[source]=admin, data[status]=success
     * → withdraw: اعتبارسنجی max:{$wallet->balance}
     * → DB::transaction: ساخت history + بروزرسانی balance + event افزایش/کاهش
     */
    public function save(): void
    {
        auth('adminPanel')->user()?->can('users.wallet') ?: abort(403);

        $data = $this->validate([
            'type' => 'required|in:deposit,withdraw',
            'amount' => 'required|numeric|max:100000000',
            'description' => 'nullable',
        ]);

        $data['source'] = 'admin';
        $data['status'] = 'success';

        if ($data['type'] == 'withdraw') {
            $this->validate([
                'amount' => 'numeric|max:' . $this->wallet->balance,
            ]);
        }

        $wallet = $this->wallet;

        DB::transaction(function () use ($wallet, $data) {
            $wallet->histories()->create($data);

            if ($data['type'] == 'deposit') {
                $wallet->update([
                    'balance' => $wallet->balance + $data['amount'],
                ]);

                // پورت: if (@$data['order_id']) — فرم جدید order_id ندارد (فقط جریان cancel قدیمی)
                if (@$data['order_id']) {
                    $wallet->update([
                        'order_id' => $data['order_id'],
                    ]);
                }

                event(new WalletAmountIncreased($wallet));
            } else {
                $wallet->update([
                    'balance' => $wallet->balance - $data['amount'],
                ]);

                event(new WalletAmountDecreased($wallet));
            }
        });

        $this->dispatch('toast', message: 'تراکنش با موفقیت ایجاد شد.', type: 'success');

        $this->redirect(route('np.wallets.show', ['wallet' => $wallet->id]), navigate: true);
    }

    public function render()
    {
        return view('admin-panel::livewire.admin.finance.wallet-create')
            ->layout('admin-panel::layout');
    }

    public static function jdate($date, $format = 'Y/m/d'): string
    {
        if (! $date) {
            return '—';
        }

        return Jalalian::fromDateTime($date)->format($format);
    }
}
