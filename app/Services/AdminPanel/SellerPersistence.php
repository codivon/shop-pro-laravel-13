<?php

namespace App\Services\AdminPanel;

use App\Models\Product;
use App\Models\Seller;
use App\Models\SellerInfo;
use App\Models\SellerVariant;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * منطق ذخیره‌سازی فروشندگان پنل مدیریت جدید (Livewire)
 *
 * پورت ۱:۱ متدهای update/destroy کنترلر قدیمی Back\SellerControllers —
 * با رفع ۵ باگ قدیمی:
 *  ۱) هر فیلد آپلود از input خودش خوانده می‌شود (قدیمی همه از «image» می‌خواند)
 *  ۲) location از فیلد خودش ذخیره می‌شود (قدیمی از lat_and_long)
 *  ۳) private_business ذخیره می‌شود (قدیمی هرگز ذخیره نمی‌شد)
 *  ۴) SellerInfo در صورت نبود ساخته می‌شود (firstOrCreate به‌جای خطای null)
 *  ۵) حذف پوشه‌ی مدارک با try/catch تا خطای غیربازگشتی تولید نکند
 *
 * ورودی‌ها آرایه‌های ساده‌اند (به‌جای Request) تا از Livewire قابل استفاده باشند.
 */
class SellerPersistence
{
    /**
     * فیلدهای SellerInfo که از فرم ذخیره می‌شوند
     */
    protected array $infoFields = [
        'private_business', 'first_name', 'last_name', 'birth_day', 'gender',
        'identity_card_number', 'national_identity_number',
        'company_name', 'company_type', 'company_registration_number',
        'company_national_identity_number', 'company_economic_number',
        'state_id', 'city_id', 'address', 'post_code', 'phone', 'mobile',
        'business_name', 'shaba_number', 'main_supply_category_id',
        'number_of_products', 'bio', 'website', 'vat_free',
        'status_documents', 'satisfaction', 'operation', 'location',
    ];

    /**
     * بروزرسانی فروشنده + اطلاعات تکمیلی (پورت update قدیمی)
     *
     * @param  Seller  $seller
     * @param  array<string, mixed>  $data
     * @param  array<string, UploadedFile|null>  $files  logo / card_image / card_image_back / vat_image
     */
    public function update(Seller $seller, array $data, array $files = []): void
    {
        $seller->email = $data['email'] ?? $seller->email;
        $seller->mobile = $data['mobile'] ?? $seller->mobile;
        $seller->status = $data['status'] ?? $seller->status;
        $seller->status_register = $data['status_register'] ?? $seller->status_register;
        $seller->status_documents = $data['status_documents'] ?? $seller->status_documents;
        $seller->status_work = $data['status_work'] ?? $seller->status_work;

        if (! empty($data['password'])) {
            $seller->password = bcrypt($data['password']);
        }

        $seller->save();

        $sellerInfo = SellerInfo::firstOrCreate(
            ['seller_id' => $seller->id],
            ['private_business' => 'private']
        );

        foreach ($this->infoFields as $field) {
            if (array_key_exists($field, $data)) {
                $sellerInfo->{$field} = $data[$field];
            }
        }

        $sellerInfo->seller_id = $seller->id;

        // آپلود تصاویر — هر فیلد از input خودش (رفع باگ قدیمی)
        if (! empty($files['logo'])) {
            $sellerInfo->logo = uploadOptimizedImage($files['logo'], 'sellerInfo', $seller->id, [
                'size' => 100,
                'path' => 'uploads/sellers/logo/',
                'field' => 'logo',
            ]);
        }

        if (! empty($files['card_image'])) {
            $sellerInfo->card_image = uploadOptimizedImage($files['card_image'], 'sellerInfo', $seller->id, [
                'size' => 100,
                'path' => 'uploads/sellers/documents/',
                'field' => 'card_image',
            ]);
        }

        if (! empty($files['card_image_back'])) {
            $sellerInfo->card_image_back = uploadOptimizedImage($files['card_image_back'], 'sellerInfo', $seller->id, [
                'size' => 100,
                'path' => 'uploads/sellers/documents/',
                'field' => 'card_image_back',
            ]);
        }

        if (! empty($files['vat_image'])) {
            $sellerInfo->vat_image = uploadOptimizedImage($files['vat_image'], 'sellerInfo', $seller->id, [
                'size' => 100,
                'path' => 'uploads/sellers/documents/',
                'field' => 'vat_image',
            ]);
        }

        $sellerInfo->save();

        activity()
            ->performedOn($seller)
            ->causedBy(auth('adminPanel')->user())
            ->event('updated')
            ->withProperties([
                'action' => 'update_seller',
                'seller_id' => $seller->id,
                'business_name' => $data['business_name'] ?? null,
                'ip' => request()->ip(),
            ])
            ->log('ویرایش فروشنده «' . ($data['business_name'] ?? '#' . $seller->id) . '»');
    }

    /**
     * حذف کامل فروشنده + پاکسازی (پورت destroy قدیمی)
     */
    public function destroy(Seller $seller): void
    {
        activity()
            ->performedOn($seller)
            ->causedBy(auth('adminPanel')->user())
            ->event('deleted')
            ->withProperties([
                'action' => 'delete_seller',
                'seller_id' => $seller->id,
                'ip' => request()->ip(),
            ])
            ->log('حذف فروشنده «' . ($seller->seller_info->business_name ?? '#' . $seller->id) . '»');

        $sellerInfo = SellerInfo::where('seller_id', $seller->id)->first();

        // حذف تصاویر فروشنده
        if ($sellerInfo) {
            foreach (['logo', 'vat_image', 'card_image', 'card_image_back'] as $field) {
                if ($sellerInfo->{$field} && Storage::disk('public')->exists($sellerInfo->{$field})) {
                    Storage::disk('public')->delete($sellerInfo->{$field});
                }
            }
        }

        if (file_exists(public_path('uploads/sellers/documents/' . $seller->id))) {
            try {
                rmdir(public_path('uploads/sellers/documents/' . $seller->id));
            } catch (\Throwable $e) {
                Log::warning('Seller documents directory not empty: ' . $seller->id);
            }
        }

        // حذف رکورد اطلاعات فروشنده (در production کسکید FK انجامش می‌دهد؛
        // ولی صریحاً پاک می‌کنیم تا در محیط‌های بدون FK رکورد یتیم نماند)
        if ($sellerInfo) {
            $sellerInfo->delete();
        }

        // حذف محصولات فروشنده
        $sellerVariants = SellerVariant::where('seller_id', $seller->id)->get();

        foreach ($sellerVariants as $sellerVariant) {
            $product = Product::find($sellerVariant->product_id);

            if ($product) {
                $product->tags()->detach();
                $product->specifications()->detach();

                if ($product->image && Storage::disk('public')->exists($product->image)) {
                    Storage::disk('public')->delete($product->image);
                }

                foreach ($product->gallery as $image) {
                    if (Storage::disk('public')->exists($image->image)) {
                        Storage::disk('public')->delete($image->image);
                    }

                    $image->delete();
                }

                $product->delete();
            }

            $sellerVariant->delete();
        }

        $seller->delete();
    }
}
