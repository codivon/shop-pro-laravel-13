<?php

namespace App\Livewire\Admin\Sellers;

use App\Models\Carrier;
use App\Models\Category;
use App\Models\NotificationManage;
use App\Models\Province;
use App\Models\Seller;
use App\Models\SellerInfo;
use App\Rules\PersianAlpha;
use App\Rules\PersianAlphaEngNum;
use App\Services\AdminPanel\SellerPersistence;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithoutUrlPagination;
use Morilog\Jalali\Jalalian;

#[Title('پرونده فروشنده')]
class SellerShow extends Component
{
    use WithFileUploads;
    use WithoutUrlPagination;

    public Seller $seller;

    /* ---------------- وضعیت‌ها (ردیف بالای فرم) ---------------- */

    public string $status = '';

    public string $statusRegister = '';

    public string $statusDocuments = '';

    public string $statusWork = '';

    /* ---------------- اطلاعات پایه ---------------- */

    public string $privateBusiness = 'private';

    public string $businessName = '';

    public string $firstName = '';

    public string $lastName = '';

    public string $gender = 'male';

    public string $birthDay = '';

    public $nationalIdentityNumber = '';

    public $identityCardNumber = '';

    public string $companyName = '';

    public string $companyType = '';

    public $companyRegistrationNumber = '';

    public $companyNationalIdentityNumber = '';

    public $companyEconomicNumber = '';

    public $mainSupplyCategoryId = null;

    public $numberOfProducts = null;

    public $logo = null;

    public string $bio = '';

    /* ---------------- حساب بانکی ---------------- */

    public string $shabaNumber = '';

    /* ---------------- تماس و آدرس ---------------- */

    public string $email = '';

    public string $mobile = '';

    public $phone = '';

    public string $website = '';

    public $stateId = null;

    public $cityId = null;

    public $postCode = '';

    public string $address = '';

    public string $location = '';

    /* ---------------- مدارک ---------------- */

    public $cardImage = null;

    public $cardImageBack = null;

    public string $vatFree = '2';

    public $vatImage = null;

    /* ---------------- اطلاعات ورود ---------------- */

    public string $password = '';

    public string $passwordConfirmation = '';

    /* ---------------- عملکرد ---------------- */

    public $operation = null;

    public $satisfaction = null;

    /* ---------------- مودال اعلان ---------------- */

    public bool $notifModal = false;

    public ?int $editingNotifId = null;

    public string $notifTitle = '';

    public string $notifPriority = 'low';

    public string $notifMessage = '';

    public bool $notifPopup = false;

    /* ---------------- راه‌اندازی ---------------- */

    public function mount(Seller $seller): void
    {
        auth('adminPanel')->user()?->can('sellers.view') ?: abort(403);

        $this->seller = $seller;

        // اطلاعات تکمیلی در صورت نبود ساخته می‌شود (رفع خطای null قدیمی)
        $info = $this->sellerInfo;

        $this->status = (string) ($seller->status ?? 'ACTIVE');
        $this->statusRegister = (string) ($seller->status_register ?? 'business-details');
        $this->statusDocuments = (string) ($seller->status_documents ?? 'Waiting');
        $this->statusWork = (string) ($seller->status_work ?? 'ACTIVE');

        $this->privateBusiness = (string) ($info->private_business ?: 'private');
        $this->businessName = (string) ($info->business_name ?? '');
        $this->firstName = (string) ($info->first_name ?? '');
        $this->lastName = (string) ($info->last_name ?? '');
        $this->gender = (string) ($info->gender ?: 'male');
        $this->birthDay = (string) ($info->birth_day ?? '');
        $this->nationalIdentityNumber = $info->national_identity_number;
        $this->identityCardNumber = $info->identity_card_number;
        $this->companyName = (string) ($info->company_name ?? '');
        $this->companyType = (string) ($info->company_type ?? '');
        $this->companyRegistrationNumber = $info->company_registration_number;
        $this->companyNationalIdentityNumber = $info->company_national_identity_number;
        $this->companyEconomicNumber = $info->company_economic_number;
        $this->mainSupplyCategoryId = $info->main_supply_category_id;
        $this->numberOfProducts = $info->number_of_products;
        $this->bio = (string) ($info->bio ?? '');
        $this->shabaNumber = (string) ($info->shaba_number ?? '');
        $this->email = (string) ($seller->email ?? '');
        $this->mobile = (string) ($info->mobile ?? $seller->mobile ?? '');
        $this->phone = $info->phone;
        $this->website = (string) ($info->website ?? '');
        $this->stateId = $info->state_id;
        $this->cityId = $info->city_id;
        $this->postCode = $info->post_code;
        $this->address = (string) ($info->address ?? '');
        $this->location = (string) ($info->location ?? '');
        $this->vatFree = (string) ($info->vat_free ?: '2');
        $this->operation = $info->operation;
        $this->satisfaction = $info->satisfaction;
    }

    /* ---------------- منابع فرم ---------------- */

    #[Computed]
    public function sellerInfo(): SellerInfo
    {
        return SellerInfo::firstOrCreate(
            ['seller_id' => $this->seller->id],
            [
                'private_business' => 'private',
                /*
                 | فروشنده‌ی تازه‌ای که هنوز ثبت‌نامش کامل نشده ممکن است info نداشته باشد؛
                 | ستون‌های state_id/city_id/address/... در مایگریشن NOT NULL هستند
                 | و بدون این مقادیر پیش‌فرض، ساخت رکورد کرش می‌کند.
                 */
                'state_id' => 0,
                'city_id' => 0,
                'address' => '',
                'post_code' => '',
                'phone' => '',
                'mobile' => (string) $this->seller->mobile,
                'business_name' => '',
                'shaba_number' => '',
                'main_supply_category_id' => 0,
                'number_of_products' => 0,
            ]
        );
    }

    /** دسته‌بندی‌ها (ریشه + فرزندان برای انتخاب آبشاری) */
    #[Computed(persist: true, seconds: 300)]
    public function categoryTree(): array
    {
        return $this->buildCategoryTree(null, 0);
    }

    protected function buildCategoryTree($parentId = null, $depth = 0): array
    {
        $query = Category::detectLang()->where('type', 'productcat')->orderBy('ordering');

        $query->when($parentId === null, fn ($q) => $q->whereNull('category_id'), fn ($q) => $q->where('category_id', $parentId));

        $result = [];

        foreach ($query->get(['id', 'title', 'category_id']) as $category) {
            $result[] = [
                'id' => $category->id,
                'title' => str_repeat('— ', $depth) . $category->title,
                'depth' => $depth,
            ];

            if ($depth < 5) {
                $result = array_merge($result, $this->buildCategoryTree($category->id, $depth + 1));
            }
        }

        return $result;
    }

    #[Computed(persist: true, seconds: 300)]
    public function provinces()
    {
        return Province::with('cities:id,province_id,name')->select('id', 'name')->get();
    }

    #[Computed]
    public function cities()
    {
        if (! $this->stateId) {
            return collect();
        }

        return Province::find($this->stateId)?->cities()->orderBy('name')->get(['id', 'name']) ?? collect();
    }

    public function updatedStateId(): void
    {
        $this->cityId = null;
    }

    /* ---------------- اعتبارسنجی (UpdateSellerRequest قدیمی — اصلاح‌شده) ---------------- */

    protected function rules(): array
    {
        $rules = [
            'email' => ['required', 'email', 'unique:sellers,email,' . $this->seller->id],
            'mobile' => ['required', 'regex:/(09)[0-9]{9}/', 'digits:11', 'unique:sellers,mobile,' . $this->seller->id],
            'stateId' => ['required', 'exists:provinces,id'],
            'cityId' => ['required', 'exists:cities,id'],
            'address' => ['required', new PersianAlphaEngNum],
            'postCode' => ['nullable', 'digits:10'],
            'phone' => ['nullable', 'digits:11'],
            'businessName' => ['required', new PersianAlphaEngNum, 'unique:sellers_info,business_name,' . $this->sellerInfo->id],
            'shabaNumber' => ['required'],
            'mainSupplyCategoryId' => ['required', 'exists:categories,id'],
            'numberOfProducts' => ['required'],
            'password' => ['nullable', 'string', 'min:6', 'confirmed'],
            'operation' => ['nullable', 'numeric', 'min:0.5', 'max:5'],
            'satisfaction' => ['nullable', 'integer', 'min:0', 'max:100'],
            'vatFree' => ['nullable', 'in:1,2'],
            'logo' => ['nullable', 'image', 'max:10240'],
            'cardImage' => ['nullable', 'image', 'max:10240'],
            'cardImageBack' => ['nullable', 'image', 'max:10240'],
            'vatImage' => ['nullable', 'image', 'max:10240'],
        ];

        if ($this->privateBusiness === 'private') {
            $rules += [
                'firstName' => ['required', new PersianAlpha],
                'lastName' => ['required', new PersianAlpha],
                'birthDay' => ['required'],
                'gender' => ['required', 'in:male,female'],
                'identityCardNumber' => ['nullable'], // فاز ۱۱۸ — شماره شناسنامه دیگر اجباری نیست
                'nationalIdentityNumber' => ['required', 'digits:10'],
            ];
        }

        if ($this->privateBusiness === 'business') {
            $rules += [
                'companyName' => ['required', new PersianAlpha],
                'companyType' => ['required'],
                'companyRegistrationNumber' => ['required'],
                'companyNationalIdentityNumber' => ['required', 'digits:11'],
                'companyEconomicNumber' => ['nullable', 'digits:12'],
            ];
        }

        return $rules;
    }

    protected function messages(): array
    {
        return [
            'email.required' => 'ایمیل الزامی است.',
            'email.unique' => 'این ایمیل قبلاً ثبت شده است.',
            'mobile.required' => 'تلفن همراه الزامی است.',
            'mobile.regex' => 'شماره همراه باید با ۰۹ شروع شود و ۱۱ رقم باشد.',
            'mobile.digits' => 'شماره همراه باید ۱۱ رقم باشد.',
            'mobile.unique' => 'این شماره همراه قبلاً ثبت شده است.',
            'stateId.required' => 'انتخاب استان الزامی است.',
            'cityId.required' => 'انتخاب شهر الزامی است.',
            'address.required' => 'آدرس الزامی است.',
            'postCode.digits' => 'کد پستی باید ۱۰ رقم باشد.',
            'phone.digits' => 'تلفن ثابت باید ۱۱ رقم باشد.',
            'businessName.required' => 'نام تجاری فروشگاه الزامی است.',
            'businessName.unique' => 'این نام تجاری قبلاً ثبت شده است.',
            'shabaNumber.required' => 'شماره شبا الزامی است.',
            'mainSupplyCategoryId.required' => 'انتخاب دسته‌بندی اصلی کالا الزامی است.',
            'numberOfProducts.required' => 'تعداد تقریبی تنوع کالا الزامی است.',
            'password.min' => 'رمز عبور باید حداقل ۶ کاراکتر باشد.',
            'password.confirmed' => 'تکرار رمز عبور مطابقت ندارد.',
            'firstName.required' => 'نام الزامی است.',
            'lastName.required' => 'نام خانوادگی الزامی است.',
            'birthDay.required' => 'تاریخ تولد الزامی است.',
            'nationalIdentityNumber.required' => 'کد ملی الزامی است.',
            'nationalIdentityNumber.digits' => 'کد ملی باید ۱۰ رقم باشد.',
            // فاز ۱۱۸ — شماره شناسنامه اختیاری شد؛ پیام الزام حذف شد
            'companyName.required' => 'نام شرکت الزامی است.',
            'companyType.required' => 'نوع شرکت الزامی است.',
            'companyRegistrationNumber.required' => 'شماره ثبت شرکت الزامی است.',
            'companyNationalIdentityNumber.required' => 'شناسه ملی شرکت الزامی است.',
            'companyNationalIdentityNumber.digits' => 'شناسه ملی باید ۱۱ رقم باشد.',
            'companyEconomicNumber.digits' => 'کد اقتصادی باید ۱۲ رقم باشد.',
            'logo.image' => 'فایل آپلود شده باید تصویر باشد.',
            'cardImage.image' => 'فایل آپلود شده باید تصویر باشد.',
            'cardImageBack.image' => 'فایل آپلود شده باید تصویر باشد.',
            'vatImage.image' => 'فایل آپلود شده باید تصویر باشد.',
        ];
    }

    /* ---------------- ذخیره ---------------- */

    public function save(SellerPersistence $persistence): void
    {
        auth('adminPanel')->user()?->can('sellers.update') ?: abort(403);

        $this->validate();

        $data = [
            'email' => $this->email,
            'mobile' => $this->mobile,
            'status' => $this->status,
            'status_register' => $this->statusRegister,
            'status_documents' => $this->statusDocuments,
            'status_work' => $this->statusWork,
            'password' => $this->password ?: null,
            'private_business' => $this->privateBusiness,
            'business_name' => $this->businessName,
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'birth_day' => $this->birthDay,
            'gender' => $this->gender,
            'identity_card_number' => $this->identityCardNumber,
            'national_identity_number' => $this->nationalIdentityNumber,
            'company_name' => $this->companyName,
            'company_type' => $this->companyType,
            'company_registration_number' => $this->companyRegistrationNumber,
            'company_national_identity_number' => $this->companyNationalIdentityNumber,
            'company_economic_number' => $this->companyEconomicNumber,
            'main_supply_category_id' => $this->mainSupplyCategoryId,
            'number_of_products' => $this->numberOfProducts,
            'bio' => $this->bio,
            'shaba_number' => $this->shabaNumber,
            'phone' => $this->phone,
            'website' => $this->website,
            'state_id' => $this->stateId,
            'city_id' => $this->cityId,
            'post_code' => $this->postCode,
            'address' => $this->address,
            'location' => $this->location,
            'vat_free' => $this->vatFree,
            'satisfaction' => $this->satisfaction,
            'operation' => $this->operation,
        ];

        $files = [
            'logo' => $this->logo,
            'card_image' => $this->cardImage,
            'card_image_back' => $this->cardImageBack,
            'vat_image' => $this->vatImage,
        ];

        $persistence->update($this->seller, $data, $files);

        // بازنشانی فیلدهای موقت فرم
        $this->reset(['logo', 'cardImage', 'cardImageBack', 'vatImage', 'password', 'passwordConfirmation']);

        // بروزرسانی شماره همراه مدل فروشنده
        $this->seller->refresh();
        $this->sellerInfo->refresh();

        $this->dispatch('toast', message: 'اطلاعات فروشنده با موفقیت ویرایش شد.', type: 'success');
    }

    /* ---------------- حذف فروشنده ---------------- */

    public function delete(SellerPersistence $persistence): void
    {
        auth('adminPanel')->user()?->can('sellers.delete') ?: abort(403);

        $persistence->destroy($this->seller);

        $this->dispatch('toast', message: 'فروشنده با موفقیت حذف شد.', type: 'success');

        $this->redirect(route('np.sellers.index'), navigate: true);
    }

    /* ---------------- اعلان‌ها ---------------- */

    public function openNotifModal(): void
    {
        auth('adminPanel')->user()?->can('sellers.view') ?: abort(403);

        $this->reset(['editingNotifId', 'notifTitle', 'notifPriority', 'notifMessage', 'notifPopup']);
        $this->notifPriority = 'low';
        $this->notifModal = true;
    }

    public function editNotif(int $id): void
    {
        auth('adminPanel')->user()?->can('sellers.view') ?: abort(403);

        $notification = $this->seller->notifications()->where('notification_manages.id', $id)->first();

        if (! $notification) {
            $this->dispatch('toast', message: 'اعلان یافت نشد.', type: 'error');

            return;
        }

        $this->editingNotifId = $notification->id;
        $this->notifTitle = (string) ($notification->title ?? '');
        $this->notifPriority = (string) ($notification->priority ?? 'low');
        $this->notifMessage = (string) ($notification->message ?? '');
        $this->notifPopup = (bool) $notification->popup;
        $this->notifModal = true;
    }

    public function storeNotif(): void
    {
        auth('adminPanel')->user()?->can('sellers.view') ?: abort(403);

        $this->validate([
            'notifMessage' => 'required',
            'notifPriority' => 'in:low,medium,high',
        ], ['notifMessage.required' => 'متن اعلان الزامی است.']);

        $notification = new NotificationManage();
        $notification->admin_id = auth('adminPanel')->id();
        $notification->title = $this->notifTitle;
        $notification->message = $this->notifMessage;
        $notification->private = 'seller';
        $notification->priority = $this->notifPriority;
        $notification->popup = $this->notifPopup;
        $notification->save();

        $notification->sellers()->attach($this->seller->id);

        $this->notifModal = false;
        $this->dispatch('toast', message: 'اعلان با موفقیت ایجاد شد.', type: 'success');
    }

    public function updateNotif(): void
    {
        auth('adminPanel')->user()?->can('sellers.view') ?: abort(403);

        $this->validate([
            'notifMessage' => 'required',
            'notifPriority' => 'in:low,medium,high',
        ], ['notifMessage.required' => 'متن اعلان الزامی است.']);

        $notification = NotificationManage::findOrFail($this->editingNotifId);
        $notification->admin_id = auth('adminPanel')->id();
        $notification->title = $this->notifTitle;
        $notification->message = $this->notifMessage;
        $notification->private = 'seller';
        $notification->priority = $this->notifPriority;
        $notification->popup = $this->notifPopup;
        $notification->save();

        $this->notifModal = false;
        $this->dispatch('toast', message: 'اعلان با موفقیت ویرایش شد.', type: 'success');
    }

    public function deleteNotif(int $id): void
    {
        auth('adminPanel')->user()?->can('notifications.delete') ?: abort(403);

        NotificationManage::where('id', $id)->where('private', 'seller')->delete();

        $this->dispatch('toast', message: 'اعلان با موفقیت حذف شد.', type: 'success');
    }

    #[Computed]
    public function notifications()
    {
        $notificationIds = DB::table('notification_manage_users')
            ->where('seller_id', $this->seller->id)
            ->pluck('notification_manage_id');

        return NotificationManage::query()
            ->where('private', 'seller')
            ->whereIn('id', $notificationIds)
            ->latest('id')
            ->take(5)
            ->get();
    }

    /** وضعیت خوانده‌شدن اعلان برای این فروشنده (پیوت) */
    public function notifRead(NotificationManage $notification): bool
    {
        return (bool) DB::table('notification_manage_users')
            ->where('notification_manage_id', $notification->id)
            ->where('seller_id', $this->seller->id)
            ->value('read');
    }

    /* ---------------- آمار و جداول زیرین ---------------- */

    #[Computed]
    public function walletBalance(): int
    {
        return (int) $this->seller->getWallet()->balance();
    }

    #[Computed]
    public function paidOrdersSum(): int
    {
        return (int) $this->seller->orders()->paid()->sum('price');
    }

    #[Computed]
    public function viewsToday(): int
    {
        return $this->seller->views()->whereDate('created_at', now())->count();
    }

    #[Computed]
    public function viewsTotal(): int
    {
        return $this->seller->views()->count();
    }

    /** شناسه‌های سفارش‌های دارای قلم این فروشنده (پروتکل مرجع: order_items.seller_id) */
    public function sellerOrderIds(): array
    {
        return \App\Models\OrderItem::where('seller_id', $this->seller->id)
            ->pluck('order_id')
            ->unique()
            ->values()
            ->all();
    }

    #[Computed]
    public function ordersCount(): int
    {
        return count($this->sellerOrderIds());
    }

    #[Computed]
    public function productsCount(): int
    {
        return \App\Models\Product::where('seller_id', $this->seller->id)->count();
    }

    #[Computed]
    public function variantsCount(): int
    {
        return \App\Models\SellerVariant::where('seller_id', $this->seller->id)->count();
    }

    #[Computed]
    public function notificationsCount(): int
    {
        return count($this->notifications);
    }

    #[Computed]
    public function latestOrders()
    {
        return \App\Models\Order::whereIn('id', $this->sellerOrderIds())
            ->latest('id')
            ->take(5)
            ->get();
    }

    #[Computed]
    public function latestProducts()
    {
        return \App\Models\Product::where('seller_id', $this->seller->id)
            ->latest('id')
            ->take(6)
            ->get();
    }

    #[Computed]
    public function latestVariants()
    {
        return \App\Models\SellerVariant::where('seller_id', $this->seller->id)
            ->latest('id')
            ->take(6)
            ->get();
    }

    #[Computed]
    public function latestViews()
    {
        return $this->seller->views()->latest()->take(5)->get();
    }

    #[Computed]
    public function carriers()
    {
        return Carrier::detectLang()
            ->forCurrentSeller($this->seller->id)
            ->latest()
            ->take(10)
            ->get();
    }

    /* ---------------- کمکی‌ها ---------------- */

    public static function jdate($date, $format = 'Y/m/d'): string
    {
        if (! $date) {
            return '—';
        }

        return Jalalian::fromDateTime($date)->format($format);
    }

    public static function statusLabel(?string $status): string
    {
        return match ($status) {
            'paid' => 'پرداخت شده',
            'unpaid' => 'پرداخت نشده',
            'canceled', 'cancelled' => 'لغو شده',
            default => $status ?? '—',
        };
    }

    public function render()
    {
        return view('admin-panel::livewire.admin.sellers.show')
            ->layout('admin-panel::layout');
    }
}
