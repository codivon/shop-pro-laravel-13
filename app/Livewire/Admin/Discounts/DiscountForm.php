<?php

namespace App\Livewire\Admin\Discounts;

use App\Models\Category;
use App\Models\Discount;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Morilog\Jalali\Jalalian;

#[Layout('admin-panel::layout')]
#[Title('فرم تخفیف')]
class DiscountForm extends Component
{
    /* ------------------------------- پایه ------------------------------- */

    public ?Discount $discount = null;

    public bool $isEditing = false;

    public ?int $discountId = null;

    /* --------------------------- تب «اطلاعات» --------------------------- */

    public string $title = '';

    public string $code = '';

    public string $start_date = '';

    public string $end_date = '';

    public string $type = 'percent';

    public $price = null;

    public $percent = null;

    public $discount_ceiling = null;

    public $least_price = null;

    public $least_products_count = null;

    public string $description = '';

    public bool $published = true;

    /* ------------------------- تب «محدودیت‌ها» ------------------------- */

    public $quantity = null;

    public $quantity_per_user = null;

    public bool $only_first_purchase = false;

    public bool $not_discount_products = true;

    public string $include_type = 'all';

    public string $exclude_type = 'none';

    public array $include_categories = [];

    public array $exclude_categories = [];

    /** چیپ‌های انتخاب‌شده (id + برچسب) */
    public array $usersSelected = [];

    public array $includeProductsSelected = [];

    public array $excludeProductsSelected = [];

    /** آرایه شناسه‌ها برای اعتبارسنجی — در save() از چیپ‌ها پر می‌شود */
    public array $users = [];

    public array $include_products = [];

    public array $exclude_products = [];

    /* ----------------------------- جستجوها ----------------------------- */

    public string $usersSearch = '';

    public array $usersResults = [];

    public string $includeProductsSearch = '';

    public array $includeProductsResults = [];

    public string $excludeProductsSearch = '';

    public array $excludeProductsResults = [];

    /* ============================ Mount ============================ */

    public function mount(?Discount $discount = null)
    {
        $this->authorizeAccess($discount && $discount->exists ? 'discounts.update' : 'discounts.create');

        if ($discount && $discount->exists) {
            $this->discount = $discount;
            $this->isEditing = true;
            $this->discountId = $discount->id;
            $this->loadDiscount($discount);
        }
    }

    protected function authorizeAccess(string $permission): void
    {
        if (! auth('adminPanel')->user()?->can($permission)) {
            abort(403);
        }
    }

    /* ============================ بارگذاری (ویرایش) ============================ */

    protected function loadDiscount(Discount $discount): void
    {
        $this->title = (string) $discount->title;
        $this->code = (string) $discount->code;
        $this->start_date = $discount->start_date ? Jalalian::fromDateTime($discount->start_date)->format('Y-m-d H:i:s') : '';
        $this->end_date = $discount->end_date ? Jalalian::fromDateTime($discount->end_date)->format('Y-m-d H:i:s') : '';
        $this->type = (string) $discount->type;
        $this->price = $discount->type === 'amount' ? $discount->amount : null;
        $this->percent = $discount->type === 'percent' ? $discount->amount : null;
        $this->discount_ceiling = $discount->discount_ceiling;
        $this->least_price = $discount->least_price;
        $this->least_products_count = $discount->least_products_count;
        $this->description = (string) $discount->description;
        $this->published = (bool) $discount->published;
        $this->quantity = $discount->quantity;
        $this->quantity_per_user = $discount->quantity_per_user;
        $this->only_first_purchase = (bool) $discount->only_first_purchase;
        $this->not_discount_products = (bool) $discount->not_discount_products;
        $this->include_type = (string) $discount->include_type;
        $this->exclude_type = (string) $discount->exclude_type;

        // دسته‌بندی‌های شامل/مستثنی (پیوت type)
        $this->include_categories = array_map('intval', $discount->includeCategories()->get(['categories.id'])->modelKeys());
        $this->exclude_categories = array_map('intval', $discount->excludeCategories()->get(['categories.id'])->modelKeys());

        // کاربران هدف
        $this->usersSelected = $discount->users()
            ->get(['users.id', 'users.username', 'users.first_name', 'users.last_name'])
            ->map(fn (User $user) => [
                'id' => (int) $user->id,
                'label' => $this->userLabel($user),
            ])
            ->values()
            ->all();

        // محصولات شامل/مستثنی — فقط شناسه و عنوان (نه کل رکورد)
        $this->includeProductsSelected = $this->productsToChips(
            $discount->includeProducts()->get(['products.id', 'products.title'])
        );
        $this->excludeProductsSelected = $this->productsToChips(
            $discount->excludeProducts()->get(['products.id', 'products.title'])
        );
    }

    protected function productsToChips($products): array
    {
        return collect($products)
            ->map(fn ($product) => [
                'id' => (int) $product->id,
                'label' => (string) $product->title,
            ])
            ->values()
            ->all();
    }

    protected function userLabel(User $user): string
    {
        return trim((string) ($user->fullname ?? '')) ?: $user->username;
    }

    /* ============================ مراجع ============================ */

    #[Computed]
    public function categories()
    {
        return Category::where('type', 'productcat')
            ->orderBy('ordering')
            ->get(['id', 'title']);
    }

    /* ============================ جستجوی کاربران ============================ */

    public function updatedUsersSearch(): void
    {
        $term = trim($this->usersSearch);

        if (mb_strlen($term) < 2) {
            $this->usersResults = [];

            return;
        }

        $this->usersResults = User::excludeCreator()
            ->where(function ($query) use ($term) {
                $query->where('username', 'like', "%{$term}%")
                    ->orWhere('first_name', 'like', "%{$term}%")
                    ->orWhere('last_name', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%");
            })
            ->latest()
            ->limit(10)
            ->get(['id', 'username', 'first_name', 'last_name'])
            ->map(fn (User $user) => [
                'id' => (int) $user->id,
                'label' => $this->userLabel($user),
                'username' => (string) $user->username,
            ])
            ->values()
            ->all();
    }

    public function addUser(int $userId): void
    {
        if (collect($this->usersSelected)->pluck('id')->contains($userId)) {
            return;
        }

        if (! $user = User::find($userId)) {
            return;
        }

        $this->usersSelected[] = ['id' => (int) $user->id, 'label' => $this->userLabel($user)];
        $this->usersSearch = '';
        $this->usersResults = [];
    }

    public function removeUser(int $userId): void
    {
        $this->usersSelected = $this->filterChips($this->usersSelected, $userId);
    }

    /* ============================ جستجوی محصولات ============================ */

    public function updatedIncludeProductsSearch(): void
    {
        $this->includeProductsResults = $this->searchProducts($this->includeProductsSearch);
    }

    public function updatedExcludeProductsSearch(): void
    {
        $this->excludeProductsResults = $this->searchProducts($this->excludeProductsSearch);
    }

    protected function searchProducts(string $search): array
    {
        $term = trim($search);

        if (mb_strlen($term) < 2) {
            return [];
        }

        return Product::query()
            ->where(function ($query) use ($term) {
                $query->where('title', 'like', "%{$term}%")
                    ->orWhere('title_en', 'like', "%{$term}%");
            })
            ->orderByDesc('id')
            ->limit(10)
            ->get(['id', 'title'])
            ->map(fn (Product $product) => [
                'id' => (int) $product->id,
                'label' => (string) $product->title,
            ])
            ->values()
            ->all();
    }

    public function addIncludeProduct(int $productId): void
    {
        $this->addProductChip('includeProductsSelected', $productId);
        $this->includeProductsSearch = '';
        $this->includeProductsResults = [];
    }

    public function addExcludeProduct(int $productId): void
    {
        $this->addProductChip('excludeProductsSelected', $productId);
        $this->excludeProductsSearch = '';
        $this->excludeProductsResults = [];
    }

    public function removeIncludeProduct(int $productId): void
    {
        $this->includeProductsSelected = $this->filterChips($this->includeProductsSelected, $productId);
    }

    public function removeExcludeProduct(int $productId): void
    {
        $this->excludeProductsSelected = $this->filterChips($this->excludeProductsSelected, $productId);
    }

    protected function addProductChip(string $property, int $productId): void
    {
        if (collect($this->{$property})->pluck('id')->contains($productId)) {
            return;
        }

        if (! $product = Product::find($productId)) {
            return;
        }

        $this->{$property}[] = ['id' => (int) $product->id, 'label' => (string) $product->title];
    }

    protected function filterChips(array $chips, int $id): array
    {
        return array_values(array_filter($chips, fn (array $chip) => (int) $chip['id'] !== $id));
    }

    /* ============================ کد خودکار (پورت اسکریپت قدیمی) ============================ */

    public function generateCode(): void
    {
        $this->code = Str::random(6);
    }

    /* ============================ اعتبارسنجی (پورت ۱:۱ Requestها) ============================ */

    protected function rules(): array
    {
        return [
            'title'                 => 'required',
            'code'                  => 'required|unique:discounts,code' . ($this->isEditing ? ',' . $this->discountId : ''),
            'start_date'            => 'required',
            'end_date'              => 'required',
            'type'                  => 'required|in:amount,percent',
            'price'                 => 'required_if:type,amount|numeric|nullable',
            'percent'               => 'required_if:type,percent|numeric|min:0|max:100|nullable',
            'include_categories'    => 'required_if:include_type,category',
            'include_products'      => 'required_if:include_type,product',
            'exclude_categories'    => 'required_if:exclude_type,category',
            'exclude_products'      => 'required_if:exclude_type,product',
            'users'                 => 'nullable|exists:users,id',
            'discount_ceiling'      => 'nullable|numeric',
            'least_price'           => 'nullable|numeric',
            'least_products_count'  => 'nullable|integer',
            'description'           => 'nullable|string',
            'only_first_purchase'   => 'boolean',
            'not_discount_products' => 'boolean',
            'published'             => 'boolean',
            'quantity'              => 'nullable|integer',
            'quantity_per_user'     => 'nullable|integer',
            'include_type'          => 'in:all,product,category',
            'exclude_type'          => 'in:none,product,category',
        ];
    }

    protected function messages(): array
    {
        return [
            'title.required' => 'عنوان تخفیف الزامی است.',
            'code.required' => 'کد تخفیف الزامی است.',
            'code.unique' => 'این کد تخفیف قبلاً ثبت شده است.',
            'start_date.required' => 'تاریخ شروع الزامی است.',
            'end_date.required' => 'تاریخ پایان الزامی است.',
            'type.required' => 'نوع تخفیف الزامی است.',
            'type.in' => 'نوع تخفیف نامعتبر است.',
            'price.required_if' => 'مقدار تخفیف (مبلغ) الزامی است.',
            'price.numeric' => 'مقدار تخفیف باید عدد باشد.',
            'percent.required_if' => 'درصد تخفیف الزامی است.',
            'percent.numeric' => 'درصد تخفیف باید عدد باشد.',
            'percent.min' => 'درصد تخفیف نمی‌تواند کمتر از ۰ باشد.',
            'percent.max' => 'درصد تخفیف نمی‌تواند بیشتر از ۱۰۰ باشد.',
            'include_categories.required_if' => 'انتخاب دسته‌بندی برای اعمال تخفیف الزامی است.',
            'include_products.required_if' => 'انتخاب محصول برای اعمال تخفیف الزامی است.',
            'exclude_categories.required_if' => 'انتخاب دسته‌بندی مستثنی الزامی است.',
            'exclude_products.required_if' => 'انتخاب محصول مستثنی الزامی است.',
            'users.exists' => 'یکی از کاربران انتخاب‌شده نامعتبر است.',
            'discount_ceiling.numeric' => 'سقف تخفیف باید عدد باشد.',
            'least_price.numeric' => 'کمترین مبلغ سفارش باید عدد باشد.',
            'least_products_count.integer' => 'کمترین تعداد محصول باید عدد صحیح باشد.',
            'quantity.integer' => 'تعداد مجاز استفاده باید عدد صحیح باشد.',
            'quantity_per_user.integer' => 'تعداد مجاز برای هر کاربر باید عدد صحیح باشد.',
        ];
    }

    /**
     * تبدیل ارقام فارسی و رشته خالی به null برای فیلدهای عددی
     * (جایگزین ConvertEmptyStringsToNull قدیمی)
     */
    protected function normalizeNumericInputs(): void
    {
        foreach (['price', 'percent', 'discount_ceiling', 'least_price', 'least_products_count', 'quantity', 'quantity_per_user'] as $field) {
            $value = convertPersianToEnglish((string) ($this->{$field} ?? ''));
            $value = str_replace(',', '', $value); // حذف جداکننده هزارگان هنگام پیست

            $this->{$field} = $value === '' ? null : $value;
        }
    }

    /* ============================ ذخیره (پورت DiscountController@store/update) ============================ */

    public function save()
    {
        $this->authorizeAccess($this->isEditing ? 'discounts.update' : 'discounts.create');

        // آرایه شناسه‌ها از چیپ‌های انتخاب‌شده
        $this->users = array_map('intval', array_column($this->usersSelected, 'id'));
        $this->include_products = array_map('intval', array_column($this->includeProductsSelected, 'id'));
        $this->exclude_products = array_map('intval', array_column($this->excludeProductsSelected, 'id'));

        $this->normalizeNumericInputs();

        try {
            $data = $this->validate();
        } catch (ValidationException $e) {
            $first = collect($e->errors())->flatten()->first();

            $this->dispatch('toast', message: $first ?? 'لطفا خطاهای فرم را برطرف کنید.', type: 'error');

            throw $e;
        }

        // مقدار نهایی تخفیف بر اساس نوع (مثل قدیمی)
        $data['amount'] = $data['type'] == 'amount' ? $data['price'] : $data['percent'];

        // تبدیل تاریخ شمسی «Y-m-d H:i:s» به میلادی (مثل قدیمی)
        foreach (['start_date', 'end_date'] as $field) {
            try {
                $data[$field] = Jalalian::fromFormat('Y-m-d H:i:s', convertPersianToEnglish($data[$field]))->toCarbon();
            } catch (\Throwable) {
                $this->dispatch('toast', message: 'تاریخ وارد شده نامعتبر است. لطفا از تاریخ‌انتخابگر استفاده کنید.', type: 'error');

                return null;
            }
        }

        if ($this->isEditing) {
            $this->discount->update($data); // ستون lang مثل قدیمی در ویرایش دست‌نخورده می‌ماند

            $this->updateRelations($this->discount);

            session()->flash('np-toast', ['type' => 'success', 'message' => 'تخفیف با موفقیت ویرایش شد.']);
        } else {
            $data['lang'] = app()->getLocale();

            $discount = Discount::create($data);

            $this->updateRelations($discount);

            session()->flash('np-toast', ['type' => 'success', 'message' => 'تخفیف با موفقیت ایجاد شد.']);
        }

        $this->redirectIntended(route('np.discounts.index', absolute: false), navigate: true);

        return null;
    }

    /**
     * پورت ۱:۱ متد خصوصی updateRelations قدیمی.
     * detach همه پیوت‌ها سپس attach بر اساس نوع؛ کاربران همیشه sync می‌شوند.
     */
    protected function updateRelations(Discount $discount): void
    {
        $discount->includeCategories()->detach();
        $discount->excludeCategories()->detach();
        $discount->includeProducts()->detach();
        $discount->excludeProducts()->detach();

        if ($this->include_type == 'category') {
            $discount->includeCategories()->attach($this->include_categories, ['type' => 'include']);
        }

        if ($this->exclude_type == 'category') {
            $discount->excludeCategories()->attach($this->exclude_categories, ['type' => 'exclude']);
        }

        if ($this->include_type == 'product') {
            $discount->includeProducts()->attach($this->include_products, ['type' => 'include']);
        }

        if ($this->exclude_type == 'product') {
            $discount->excludeProducts()->attach($this->exclude_products, ['type' => 'exclude']);
        }

        // رفع باگ قدیمی: فرم ایجاد «include_users» می‌فرستاد و کاربران هرگز ذخیره نمی‌شدند
        $discount->users()->sync($this->users ?: []);
    }

    public function render()
    {
        return view('admin-panel::livewire.admin.discounts.form');
    }
}
