<?php

namespace App\Livewire\Admin\Shipping;

use App\Models\Carrier;
use App\Models\City;
use App\Models\Province;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

#[Title('لیست روش‌های ارسال')]
class CarrierIndex extends Component
{
    use WithPagination;
    use WithFileUploads;

    public int $perPage = 15;

    /* ---------------- مودال ایجاد (پورت create/store قدیمی) ---------------- */

    public bool $showCreateModal = false;

    public string $createTitle = '';

    /** تصویر روش ارسال جدید (Livewire TemporaryUploadedFile) */
    public $createImage = null;

    public $createIsActive = '1';

    public string $createWaitingTime = '';

    public $createMaxPackageWeight = '';

    public $createMinPackageWeight = '';

    public string $createDescription = '';

    public $createProvinceId = '';

    public $createCityId = '';

    public bool $createCarrigeForward = false;

    public $createFreeShippingWeight = '';

    public $createFreeShippingPrice = '';

    public $createExtraCost = '';

    public string $createCoveredCities = 'all';

    public array $createIncludedCities = [];

    public string $createDeliveryTimeType = 'default';

    public string $createDefaultDeliveryRange = 'ارسال در 3 الی 6 روز کاری';

    public $createUserSelectRanges = 7;

    public bool $createDisableHolidays = false;

    public bool $createDisableFridays = false;

    public $createStartDaysAfterOrder = 1;

    /* ---------------- مودال ویرایش (پورت edit/update قدیمی) ---------------- */

    public ?int $editingId = null;

    public string $editTitle = '';

    public $editImage = null;

    public $editIsActive = '1';

    public string $editWaitingTime = '';

    public $editMaxPackageWeight = '';

    public $editMinPackageWeight = '';

    public string $editDescription = '';

    public $editProvinceId = '';

    public $editCityId = '';

    public bool $editCarrigeForward = false;

    public $editFreeShippingWeight = '';

    public $editFreeShippingPrice = '';

    public $editExtraCost = '';

    public string $editCoveredCities = 'all';

    public array $editIncludedCities = [];

    public string $editDeliveryTimeType = 'default';

    public string $editDefaultDeliveryRange = 'ارسال در 3 الی 6 روز کاری';

    public $editUserSelectRanges = 7;

    public bool $editDisableHolidays = false;

    public bool $editDisableFridays = false;

    public $editStartDaysAfterOrder = 1;

    /* ---------------- حذف (پورت destroy قدیمی) ---------------- */

    public ?int $confirmingDelete = null;

    public function mount(): void
    {
        // پورت middleware('can:carriers') قدیمی — درخت carriers کلید فرعی delete ندارد
        auth('adminPanel')->user()?->can('carriers') ?: abort(403);
    }

    /* ---------------- لیست (پورت index قدیمی + تعداد تعرفه‌ها طبق اسپک) ---------------- */

    #[Computed]
    public function rows()
    {
        return Carrier::query()
            ->detectLang()
            ->currentGeneral()
            ->withCount('tariffs')
            ->latest()
            ->paginate($this->perPage);
    }

    /* ---------------- درخت استان→شهر (پورت create/edit قدیمی) ---------------- */

    #[Computed]
    public function provincesTree()
    {
        // پورت دقیق: Province::with('cities:id,province_id,name')->select('id', 'name')->get()
        return Province::query()
            ->with('cities:id,province_id,name')
            ->select('id', 'name')
            ->get();
    }

    #[Computed]
    public function createShopCities()
    {
        return $this->createProvinceId
            ? City::where('province_id', $this->createProvinceId)->get(['id', 'name'])
            : collect();
    }

    #[Computed]
    public function editShopCities()
    {
        return ($this->editingId && $this->editProvinceId)
            ? City::where('province_id', $this->editProvinceId)->get(['id', 'name'])
            : collect();
    }

    /* ---------------- ایجاد (پورت ۱:۱ store قدیمی) ---------------- */

    public function createCarrier(): void
    {
        auth('adminPanel')->user()?->can('carriers') ?: abort(403);

        // رول‌های StoreCarrierRequest قدیمی
        $data = $this->validate([
            'createTitle'                 => 'required|string',
            'createImage'                 => 'nullable|image',
            'createWaitingTime'           => 'nullable|string',
            'createMaxPackageWeight'      => 'nullable|numeric',
            'createMinPackageWeight'      => 'nullable|numeric',
            'createExtraCost'             => 'nullable|numeric',
            'createDescription'           => 'nullable|string',
            'createProvinceId'            => 'required|exists:provinces,id',
            'createCityId'                => 'required|exists:cities,id',
            'createFreeShippingWeight'    => 'nullable|numeric',
            'createFreeShippingPrice'     => 'nullable|numeric',
            'createCoveredCities'         => 'required|in:all,select_city',
            'createIncludedCities'        => 'required_if:createCoveredCities,select_city',
            'createIncludedCities.*'      => 'exists:cities,id',
            'createCarrigeForward'        => 'boolean',
            'createIsActive'              => 'boolean',
            'createDeliveryTimeType'      => 'required|in:default,user_select',
            'createDefaultDeliveryRange'  => 'required_if:createDeliveryTimeType,default|nullable|string',
            'createUserSelectRanges'      => 'required_if:createDeliveryTimeType,user_select|min:1|max:20',
            'createDisableHolidays'       => 'nullable|boolean',
            'createDisableFridays'        => 'nullable|boolean',
            'createStartDaysAfterOrder'   => 'required|integer|min:1|max:12',
        ], [
            'createIncludedCities.required_if' => 'لطفا شهرهای تحت پوشش را انتخاب کنید',
        ], [
            'createTitle'                => 'عنوان',
            'createImage'                => 'تصویر',
            'createProvinceId'           => 'استان فروشگاه',
            'createCityId'               => 'شهر فروشگاه',
            'createCoveredCities'        => 'نوع شهرهای تحت پوشش',
            'createIncludedCities'       => 'شهرهای تحت پوشش',
            'createDeliveryTimeType'     => 'نوع بازه زمانی ارسال',
            'createDefaultDeliveryRange' => 'بازه زمانی ارسال',
            'createUserSelectRanges'     => 'بازه‌های قابل انتخاب',
            'createStartDaysAfterOrder'  => 'شروع بازه زمانی',
        ]);

        if ($this->createImage) {
            // پورت: uploadOptimizedImage($request->image, 'carriers')
            $image = uploadOptimizedImage($this->createImage, 'carriers');
        } else {
            $image = null;
        }

        $carrier = Carrier::create([
            'title'                  => $data['createTitle'],
            'image'                  => $image,
            'waiting_time'           => $data['createWaitingTime'] ?: null,
            'max_package_weight'     => $data['createMaxPackageWeight'] !== '' ? $data['createMaxPackageWeight'] : null,
            'min_package_weight'     => $data['createMinPackageWeight'] !== '' ? $data['createMinPackageWeight'] : null,
            'extra_cost'             => $data['createExtraCost'] !== '' ? $data['createExtraCost'] : null,
            'description'            => $data['createDescription'] ?: null,
            'province_id'            => $data['createProvinceId'],
            'city_id'                => $data['createCityId'],
            'free_shipping_weight'   => $data['createFreeShippingWeight'] !== '' ? $data['createFreeShippingWeight'] : null,
            'free_shipping_price'    => $data['createFreeShippingPrice'] !== '' ? $data['createFreeShippingPrice'] : null,
            // prepareForValidation قدیمی: carrige_forward = has('carrige_forward')
            'carrige_forward'        => $this->createCarrigeForward,
            'is_active'              => (bool) $this->createIsActive,
            'covered_cities'         => $data['createCoveredCities'],
            'delivery_time_type'     => $data['createDeliveryTimeType'],
            'default_delivery_range' => $data['createDefaultDeliveryRange'] ?: null,
            'user_select_ranges'     => $data['createUserSelectRanges'] ?? 7,
            'disable_holidays'       => (bool) $this->createDisableHolidays,
            'disable_fridays'        => (bool) $this->createDisableFridays,
            'start_days_after_order' => $data['createStartDaysAfterOrder'] ?? 1,
            'lang'                   => app()->getLocale(),
        ]);

        // پورت: $carrier->cities()->attach($request->included_cities)
        $carrier->cities()->attach($this->createIncludedCities ?: []);

        $this->resetCreateForm();
        $this->showCreateModal = false;

        // متن دقیق توست قدیمی
        $this->dispatch('toast', message: 'روش ارسال با موفقیت ایجاد شد.', type: 'success');
    }

    /* ---------------- ویرایش (پورت ۱:۱ edit/update قدیمی) ---------------- */

    public function editCarrier(int $id): void
    {
        auth('adminPanel')->user()?->can('carriers') ?: abort(403);

        $carrier = Carrier::find($id);

        if (! $carrier) {
            return;
        }

        $this->editingId                = $carrier->id;
        $this->editTitle                = (string) $carrier->title;
        $this->editImage                = null; // بدون آپلود جدید = نگه‌داشتن قبلی (پورت update)
        $this->editIsActive             = $carrier->is_active ? '1' : '0';
        $this->editWaitingTime          = (string) $carrier->waiting_time;
        $this->editMaxPackageWeight     = $carrier->max_package_weight;
        $this->editMinPackageWeight     = $carrier->min_package_weight;
        $this->editDescription          = (string) $carrier->description;
        $this->editProvinceId           = $carrier->province_id;
        $this->editCityId               = $carrier->city_id;
        $this->editCarrigeForward       = (bool) $carrier->carrige_forward;
        $this->editFreeShippingWeight   = $carrier->free_shipping_weight;
        $this->editFreeShippingPrice    = $carrier->free_shipping_price;
        $this->editExtraCost            = $carrier->extra_cost;
        $this->editCoveredCities        = $carrier->covered_cities;
        $this->editIncludedCities       = $carrier->cities->pluck('id')->map(fn ($id) => (int) $id)->all();
        $this->editDeliveryTimeType     = $carrier->delivery_time_type;
        $this->editDefaultDeliveryRange = (string) ($carrier->default_delivery_range ?? 'ارسال در 3 الی 6 روز کاری');
        $this->editUserSelectRanges     = $carrier->user_select_ranges;
        $this->editDisableHolidays      = (bool) $carrier->disable_holidays;
        $this->editDisableFridays       = (bool) $carrier->disable_fridays;
        $this->editStartDaysAfterOrder  = $carrier->start_days_after_order;
    }

    public function updateCarrier(): void
    {
        auth('adminPanel')->user()?->can('carriers') ?: abort(403);

        $carrier = $this->editingId ? Carrier::find($this->editingId) : null;

        if (! $carrier) {
            return;
        }

        // رول‌های UpdateCarrierRequest قدیمی
        $data = $this->validate([
            'editTitle'                 => 'required|string',
            'editImage'                 => 'nullable|image',
            'editWaitingTime'           => 'nullable|string',
            'editMaxPackageWeight'      => 'nullable|numeric',
            'editMinPackageWeight'      => 'nullable|numeric',
            'editExtraCost'             => 'nullable|numeric',
            'editDescription'           => 'nullable|string',
            'editProvinceId'            => 'required|exists:provinces,id',
            'editCityId'                => 'required|exists:cities,id',
            'editFreeShippingWeight'    => 'nullable|numeric',
            'editFreeShippingPrice'     => 'nullable|numeric',
            'editCoveredCities'         => 'required|in:all,select_city',
            'editIncludedCities'        => 'required_if:editCoveredCities,select_city',
            'editIncludedCities.*'      => 'exists:cities,id',
            'editCarrigeForward'        => 'boolean',
            'editIsActive'              => 'boolean',
            'editDeliveryTimeType'      => 'required|in:default,user_select',
            'editDefaultDeliveryRange'  => 'required_if:editDeliveryTimeType,default|nullable|string',
            'editUserSelectRanges'      => 'required_if:editDeliveryTimeType,user_select|min:1|max:20',
            'editDisableHolidays'       => 'nullable|boolean',
            'editStartDaysAfterOrder'   => 'required|integer|min:1|max:12',
        ], [
            'editIncludedCities.required_if' => 'لطفا شهرهای تحت پوشش را انتخاب کنید',
        ], [
            'editTitle'                => 'عنوان',
            'editImage'                => 'تصویر',
            'editProvinceId'           => 'استان فروشگاه',
            'editCityId'               => 'شهر فروشگاه',
            'editCoveredCities'        => 'نوع شهرهای تحت پوشش',
            'editIncludedCities'       => 'شهرهای تحت پوشش',
            'editDeliveryTimeType'     => 'نوع بازه زمانی ارسال',
            'editDefaultDeliveryRange' => 'بازه زمانی ارسال',
            'editUserSelectRanges'     => 'بازه‌های قابل انتخاب',
            'editStartDaysAfterOrder'  => 'شروع بازه زمانی',
        ]);

        if ($this->editImage) {
            // پورت: uploadOptimizedImage($request->image, 'carriers', $carrier->id)
            $image = uploadOptimizedImage($this->editImage, 'carriers', $carrier->id);
        } else {
            // پورت: بدون آپلود جدید → $data['image'] = $carrier->image
            $image = $carrier->image;
        }

        $carrier->update([
            'title'                  => $data['editTitle'],
            'image'                  => $image,
            'waiting_time'           => $data['editWaitingTime'] ?: null,
            'max_package_weight'     => $data['editMaxPackageWeight'] !== '' ? $data['editMaxPackageWeight'] : null,
            'min_package_weight'     => $data['editMinPackageWeight'] !== '' ? $data['editMinPackageWeight'] : null,
            'extra_cost'             => $data['editExtraCost'] !== '' ? $data['editExtraCost'] : null,
            'description'            => $data['editDescription'] ?: null,
            'province_id'            => $data['editProvinceId'],
            'city_id'                => $data['editCityId'],
            'free_shipping_weight'   => $data['editFreeShippingWeight'] !== '' ? $data['editFreeShippingWeight'] : null,
            'free_shipping_price'    => $data['editFreeShippingPrice'] !== '' ? $data['editFreeShippingPrice'] : null,
            'carrige_forward'        => $this->editCarrigeForward,
            'is_active'              => (bool) $this->editIsActive,
            'covered_cities'         => $data['editCoveredCities'],
            'delivery_time_type'     => $data['editDeliveryTimeType'],
            'default_delivery_range' => $data['editDefaultDeliveryRange'] ?: null,
            'user_select_ranges'     => $data['editUserSelectRanges'] ?? 7,
            'disable_holidays'       => (bool) $this->editDisableHolidays,
            'disable_fridays'        => (bool) $this->editDisableFridays,
            'start_days_after_order' => $data['editStartDaysAfterOrder'] ?? 1,
        ]);

        // پورت: $carrier->cities()->sync($request->included_cities)
        $carrier->cities()->sync($this->editIncludedCities ?: []);

        $this->resetEditForm();

        // متن دقیق توست قدیمی
        $this->dispatch('toast', message: 'روش ارسال با موفقیت ویرایش شد.', type: 'success');
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

        $carrier = Carrier::find($this->confirmingDelete);

        if ($carrier) {
            // پورت: $carrier->cities()->detach(); $carrier->delete();
            $carrier->cities()->detach();
            $carrier->delete();
        }

        $this->confirmingDelete = null;

        $this->dispatch('toast', message: 'روش ارسال با موفقیت حذف شد.', type: 'success');
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

    public function updatedCreateProvinceId(): void
    {
        $this->createCityId = '';
    }

    public function updatedEditProvinceId(): void
    {
        $this->editCityId = '';
    }

    protected function resetCreateForm(): void
    {
        $this->reset([
            'createTitle', 'createImage', 'createWaitingTime', 'createMaxPackageWeight', 'createMinPackageWeight',
            'createDescription', 'createProvinceId', 'createCityId', 'createCarrigeForward',
            'createFreeShippingWeight', 'createFreeShippingPrice', 'createExtraCost',
            'createCoveredCities', 'createIncludedCities', 'createDeliveryTimeType',
            'createUserSelectRanges', 'createDisableHolidays', 'createDisableFridays', 'createStartDaysAfterOrder',
        ]);

        $this->createIsActive = '1';
        $this->createDefaultDeliveryRange = 'ارسال در 3 الی 6 روز کاری';
        $this->createUserSelectRanges = 7;
        $this->createStartDaysAfterOrder = 1;
        $this->createDeliveryTimeType = 'default';
        $this->createCoveredCities = 'all';
    }

    protected function resetEditForm(): void
    {
        $this->reset([
            'editingId', 'editTitle', 'editImage', 'editWaitingTime', 'editMaxPackageWeight', 'editMinPackageWeight',
            'editDescription', 'editProvinceId', 'editCityId', 'editCarrigeForward',
            'editFreeShippingWeight', 'editFreeShippingPrice', 'editExtraCost',
            'editCoveredCities', 'editIncludedCities', 'editDeliveryTimeType',
            'editUserSelectRanges', 'editDisableHolidays', 'editDisableFridays', 'editStartDaysAfterOrder',
        ]);

        $this->editIsActive = '1';
        $this->editDefaultDeliveryRange = 'ارسال در 3 الی 6 روز کاری';
    }

    public function render()
    {
        return view('admin-panel::livewire.admin.shipping.carriers')
            ->layout('admin-panel::layout');
    }
}
