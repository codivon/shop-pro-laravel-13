<?php

namespace App\Livewire\Admin\Shipping;

use App\Models\Carrier;
use App\Models\Tariff;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('لیست تعرفه‌ها')]
class TariffIndex extends Component
{
    use WithPagination;

    public int $perPage = 15;

    /* ---------------- روش ارسال فعال (پورت ?carrier= قدیمی — State در URL) ---------------- */

    #[Url(as: 'carrier', except: '')]
    public $carrierId = '';

    /* ---------------- مودال ایجاد (پورت create/store قدیمی) ---------------- */

    public bool $showCreateModal = false;

    public string $createType = 'within_province';

    public $createMaxWeight = '';

    public $createShippingCost = '';

    /* ---------------- مودال ویرایش (پورت edit/update قدیمی) ---------------- */

    public ?int $editingId = null;

    public string $editType = 'within_province';

    public $editMaxWeight = '';

    public $editShippingCost = '';

    /* ---------------- حذف (پورت destroy قدیمی) ---------------- */

    public ?int $confirmingDelete = null;

    public function mount(): void
    {
        // پورت middleware('can:carriers') قدیمی
        auth('adminPanel')->user()?->can('carriers') ?: abort(403);

        // پورت index/create قدیمی: Carrier::findOrFail($request->carrier)
        $this->resolveCarrier();
    }

    protected function resolveCarrier(): Carrier
    {
        return Carrier::findOrFail($this->carrierId);
    }

    #[Computed]
    public function carrier()
    {
        return $this->resolveCarrier();
    }

    /* ---------------- لیست (پورت index قدیمی) ---------------- */

    #[Computed]
    public function rows()
    {
        return $this->carrier->tariffs()
            ->orderBy('type')
            ->orderBy('max_weight')
            ->paginate($this->perPage);
    }

    #[Computed]
    public function carriersList()
    {
        return Carrier::detectLang()->currentGeneral()->get(['id', 'title']);
    }

    /* ---------------- تغییر روش ارسال (سلکت بالای جدول) ---------------- */

    public function updatedCarrierId(): void
    {
        $this->resetPage();
    }

    /* ---------------- ایجاد (پورت ۱:۱ store قدیمی) ---------------- */

    public function createTariff(): void
    {
        auth('adminPanel')->user()?->can('carriers') ?: abort(403);

        // رول‌های StoreTariffRequest قدیمی (unique ترکیبی روی carrier_id+type)
        $data = $this->validate([
            'createType'          => 'required|in:within_province,extra_province',
            'createShippingCost'  => 'required|numeric',
            'createMaxWeight'     => [
                'required', 'numeric',
                // پورت unique ترکیبی قدیمی — ستون max_weight صریح (پراپرتی لایوایر createMaxWeight است)
                Rule::unique('tariffs', 'max_weight')->where(function ($query) {
                    return $query->where('carrier_id', $this->carrierId)->where('type', $this->createType);
                }),
            ],
        ], [
            'createMaxWeight.unique' => 'برای این وزن قبلا هزینه پستی وارد شده است',
        ], [
            'createType'         => 'نوع منطقه ارسالی',
            'createShippingCost' => 'هزینه ارسال',
            'createMaxWeight'    => 'حداکثر وزن',
        ]);

        Tariff::create([
            'carrier_id'    => $this->carrierId,
            'type'          => $data['createType'],
            'shipping_cost' => $data['createShippingCost'],
            'max_weight'    => $data['createMaxWeight'],
        ]);

        $this->reset(['showCreateModal', 'createType', 'createMaxWeight', 'createShippingCost']);
        $this->createType = 'within_province';

        // متن دقیق توست قدیمی
        $this->dispatch('toast', message: 'تعرفه با موفقیت ایجاد شد.', type: 'success');
    }

    /* ---------------- ویرایش (پورت ۱:۱ edit/update قدیمی) ---------------- */

    public function editTariff(int $id): void
    {
        auth('adminPanel')->user()?->can('carriers') ?: abort(403);

        $tariff = Tariff::find($id);

        if (! $tariff) {
            return;
        }

        $this->editingId         = $tariff->id;
        $this->editType          = $tariff->type;
        $this->editMaxWeight     = $tariff->max_weight;
        $this->editShippingCost  = $tariff->shipping_cost;
    }

    public function updateTariff(): void
    {
        auth('adminPanel')->user()?->can('carriers') ?: abort(403);

        $tariff = $this->editingId ? Tariff::find($this->editingId) : null;

        if (! $tariff) {
            return;
        }

        // رول‌های UpdateTariffRequest قدیمی (unique ترکیبی + ignore خود ردیف)
        $data = $this->validate([
            'editType'          => 'required|in:within_province,extra_province',
            'editShippingCost'  => 'required|numeric',
            'editMaxWeight'     => [
                'required', 'numeric',
                Rule::unique('tariffs', 'max_weight')->where(function ($query) use ($tariff) {
                    return $query->where('carrier_id', $tariff->carrier_id)->where('type', $this->editType);
                })->ignore($tariff->id),
            ],
        ], [
            'editMaxWeight.unique' => 'برای این وزن قبلا هزینه پستی وارد شده است',
        ], [
            'editType'         => 'نوع منطقه ارسالی',
            'editShippingCost' => 'هزینه ارسال',
            'editMaxWeight'    => 'حداکثر وزن',
        ]);

        $tariff->update([
            'type'          => $data['editType'],
            'shipping_cost' => $data['editShippingCost'],
            'max_weight'    => $data['editMaxWeight'],
        ]);

        $this->reset(['editingId', 'editType', 'editMaxWeight', 'editShippingCost']);

        // متن دقیق توست قدیمی
        $this->dispatch('toast', message: 'تعرفه با موفقیت ویرایش شد.', type: 'success');
    }

    /* ---------------- حذف (پورت ۱:۱ destroy قدیمی) ---------------- */

    public function confirmDelete(int $id): void
    {
        auth('adminPanel')->user()?->can('carriers') ?: abort(403);

        $this->confirmingDelete = $id;
    }

    public function delete(): void
    {
        auth('adminPanel')->user()?->can('carriers') ?: abort(403);

        $tariff = Tariff::find($this->confirmingDelete);

        if ($tariff) {
            $tariff->delete();
        }

        $this->confirmingDelete = null;

        $this->dispatch('toast', message: 'تعرفه با موفقیت حذف شد.', type: 'success');
    }

    /* ---------------- ابزار ---------------- */

    public static function jdate($date, $format = 'Y/m/d'): string
    {
        if (! $date) {
            return '—';
        }

        try {
            return \Morilog\Jalali\Jalalian::fromDateTime($date)->format($format);
        } catch (\Throwable) {
            return '—';
        }
    }

    public function render()
    {
        return view('admin-panel::livewire.admin.shipping.tariffs')
            ->layout('admin-panel::layout');
    }
}
