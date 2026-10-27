<?php

namespace Themes\WeblakShop\src\Controllers\sellers;

use App\Http\Controllers\Controller;
use App\Models\Price;
use App\Models\StockMovement;
use App\Support\MultiVendorSettings;

/**
 * فاز ۱۰۶-۵ — مرکز موجودی فروشنده.
 *
 * تا امروز فروشنده فقط کارت «مدیریت موجودی انبار» داشبورد را داشت (با
 * پارس رشتهٔ prices_id در Blade و شمارش درون-حلقه)؛ این صفحه مرکز واقعی
 * موجودی است: وضعیت ناموجود/کم‌موجود ردیف‌به‌ردیف + تاریخچهٔ
 * stock_movements (سرویس فاز ۹۳) از نگاه فروشنده.
 *
 * گیت: seller_inventory_center (تب چندفروشندگی تنظیمات برنامه‌نویس — قانون طلایی).
 * فقط-خواندنی: تغییر موجودی همچنان از جریان ادمین/سرویس انبار است.
 */
class SellerInventoryController extends Controller
{
    public function index()
    {
        if (! MultiVendorSettings::inventoryCenterEnabled()) {
            session()->put('toast-error', 'مرکز موجودی از تنظیمات غیرفعال شده است.');

            return redirect()->route('seller.dashboard');
        }

        $sellerId = (int) sellerID();

        // مبنای واحد تنوع‌ها: قیمت‌هایی که seller_id مستقیم دارند یا محصولشان
        // متعلق به این فروشنده است (فراگیر — هم هم‌قیمت‌های «درج محصول» و هم
        // تنوع‌های اختصاصی؛ هر ردیف فقط یک‌بار شمرده می‌شود)
        $priceBase = Price::query()
            ->where(function ($q) use ($sellerId) {
                $q->where('seller_id', $sellerId)
                    ->orWhereHas('product', fn ($p) => $p->where('seller_id', $sellerId));
            });

        // تاریخچهٔ حرکت انبار: حرکت‌های محصولِ فروشنده یا قیمتِ فروشنده
        // (product_id سرویس فاز ۹۳ همیشه پر می‌شود؛ اتحاد هر دو مبنا برای
        // فروشندگانی که تنوع اختصاصی روی محصول مشترک دارند)
        $movementBase = fn () => StockMovement::query()
            ->where(function ($q) use ($sellerId) {
                $q->whereHas('product', fn ($p) => $p->where('seller_id', $sellerId))
                    ->orWhereHas('price', fn ($p) => $p->where('seller_id', $sellerId));
            });

        $stats = [
            // کل تنوع‌های قیمت
            'total_variants' => (clone $priceBase)->count(),
            // ناموجود (stock < 1)
            'out_of_stock' => (clone $priceBase)->where('stock', '<', 1)->count(),
            // کم‌موجود (۱ تا ۲ عدد) — آستانهٔ هم‌خوان با کارت داشبورد (stock<3)
            'low_stock' => (clone $priceBase)->whereBetween('stock', [1, 2])->count(),
            // حرکت انبار ۳۰ روز اخیر — نبض گردش کالا
            'recent_movements' => $movementBase()
                ->where('created_at', '>=', now()->subDays(30))
                ->count(),
        ];

        // جدول وضعیت موجودی — بحرانی‌ترها اول (stock صعودی) سپس جدیدتر
        // (eager-load کامل: product + ویژگی‌های تنوع با گروه + فایل دانلودی
        // تا getAttributesName بدون کوئری اضافه رندر شود — درس N+1 فاز ۱۰۶-۲)
        $prices = (clone $priceBase)
            ->with(['product:id,title,slug,type', 'get_attributes.group', 'file'])
            ->orderBy('stock')
            ->orderByDesc('id')
            ->paginate(20);

        // تاریخچهٔ حرکت انبار — آخرین ۲۰ حرکت
        $movements = $movementBase()
            ->with('product:id,title,slug')
            ->latest()
            ->limit(20)
            ->get();

        return view('front::sellers.panel.inventory.index', compact('stats', 'prices', 'movements'));
    }
}
