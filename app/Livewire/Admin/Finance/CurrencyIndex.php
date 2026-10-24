<?php

namespace App\Livewire\Admin\Finance;

use App\Models\Currency;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Morilog\Jalali\Jalalian;

#[Title('لیست ارزها')]
class CurrencyIndex extends Component
{
    public int $perPage = 15;

    /* ---------------- کارت «ارز جدید» (پورت create/store قدیمی) ---------------- */

    public string $createTitle = '';

    public string $createAmount = '';

    /* ---------------- مودال ویرایش (پورت edit/update قدیمی) ---------------- */

    public ?int $editingId = null;

    public string $editTitle = '';

    public string $editAmount = '';

    /* ---------------- حذف ---------------- */

    public ?int $confirmingDelete = null;

    public function mount(): void
    {
        auth('adminPanel')->user()?->can('payments.currencies') ?: abort(403);
    }

    /* ---------------- لیست (پورت index قدیمی) ---------------- */

    #[Computed]
    public function currencies()
    {
        return Currency::query()
            ->detectLang()
            ->latest()
            ->paginate($this->perPage);
    }

    #[Computed(persist: true, seconds: 60)]
    public function totalCount(): int
    {
        return Currency::detectLang()->count();
    }

    /* ---------------- ایجاد (پورت ۱:۱ store قدیمی) ---------------- */

    public function createCurrency(): void
    {
        auth('adminPanel')->user()?->can('payments.currencies') ?: abort(403);

        // رول‌های StoreCurrencyRequest قدیمی: title required|string + amount required|numeric
        $data = $this->validate([
            'createTitle'  => 'required|string',
            'createAmount' => 'required|numeric',
        ], [], [
            'createTitle'  => 'عنوان',
            'createAmount' => 'مبلغ',
        ]);

        $data['title']  = $this->createTitle;
        $data['amount'] = $this->createAmount;
        $data['lang']   = app()->getLocale();

        Currency::create($data);

        $this->reset(['createTitle', 'createAmount']);

        $this->dispatch('toast', message: 'ارز با موفقیت ایجاد شد.', type: 'success');
    }

    /* ---------------- ویرایش (پورت ۱:۱ update قدیمی + بازمحاسبه قیمت‌ها) ---------------- */

    public function editCurrency(int $id): void
    {
        auth('adminPanel')->user()?->can('payments.currencies') ?: abort(403);

        $currency = Currency::find($id);

        if (! $currency) {
            return;
        }

        $this->editingId  = $currency->id;
        $this->editTitle  = (string) $currency->title;
        $this->editAmount = (string) $currency->amount;
    }

    public function updateCurrency(): void
    {
        auth('adminPanel')->user()?->can('payments.currencies') ?: abort(403);

        // رول‌های UpdateCurrencyRequest قدیمی
        $data = $this->validate([
            'editTitle'  => 'required|string',
            'editAmount' => 'required|numeric',
        ], [], [
            'editTitle'  => 'عنوان',
            'editAmount' => 'مبلغ',
        ]);

        $currency = $this->editingId ? Currency::find($this->editingId) : null;

        if (! $currency) {
            $this->reset(['editingId', 'editTitle', 'editAmount']);

            return;
        }

        $data['title']  = $this->editTitle;
        $data['amount'] = $this->editAmount;

        $old_amount = $currency->amount;

        $currency->update($data);

        // پورت حلقه بازمحاسبه قیمت‌های قدیمی: با تغییر نرخ ارز،
        // ردیف تغییر قیمت (createChange) + discount_price تازه (get_discount_price) ثبت می‌شود
        if ($old_amount != $data['amount']) {
            $prices = $currency->prices()->get();

            foreach ($prices as $price) {
                $price->createChange($price->price, $price->discount);

                $price->update([
                    'discount_price' => get_discount_price($price->price, $price->discount, $price->product),
                ]);
            }
        }

        $this->reset(['editingId', 'editTitle', 'editAmount']);

        $this->dispatch('toast', message: 'ارز با موفقیت ویرایش شد.', type: 'success');
    }

    /* ---------------- حذف (پورت destroy قدیمی) ---------------- */

    public function confirmDelete(int $id): void
    {
        auth('adminPanel')->user()?->can('payments.currencies') ?: abort(403);

        $this->confirmingDelete = $id;
    }

    public function delete(): void
    {
        auth('adminPanel')->user()?->can('payments.currencies') ?: abort(403);

        $currency = Currency::find($this->confirmingDelete);

        if ($currency) {
            // محصولات این ارز بدون ارز می‌شوند (برابری FK قدیمی products.currency_id)
            \App\Models\Product::where('currency_id', $currency->id)->update(['currency_id' => null]);

            $currency->delete();
        }

        $this->confirmingDelete = null;

        // قدیمی متنی غلط «پیام با موفقیت حذف شد» چاپ می‌کرد — متن درست‌شده
        $this->dispatch('toast', message: 'ارز با موفقیت حذف شد.', type: 'success');
    }

    /* ---------------- ابزار ---------------- */

    public static function jdate($date, $format = 'Y/m/d'): string
    {
        if (! $date) {
            return '—';
        }

        return Jalalian::fromDateTime($date)->format($format);
    }

    public function render()
    {
        return view('admin-panel::livewire.admin.finance.currencies')
            ->layout('admin-panel::layout');
    }
}
