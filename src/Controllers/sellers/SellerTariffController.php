<?php

namespace Themes\Saniar\src\Controllers\sellers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Back\Tariff\StoreTariffRequest;
use App\Http\Requests\Back\Tariff\UpdateTariffRequest;
use App\Models\Carrier;
use App\Models\Tariff;
use Illuminate\Http\Request;

class SellerTariffController extends Controller
{

    public function index(Request $request)
    {
        // سانیار فاز J: لینک منوی «تعرفه‌های ارسال» بدون پارامتر carrier است؛
        // در WeblakShop این حالت 500 می‌داد → هدایت به فهرست حامل‌ها + توست راهنما
        if (!$request->filled('carrier')) {
            session()->put('toast-warning', 'ابتدا روش ارسال موردنظر را از فهرست حامل‌ها انتخاب کنید.');
            return redirect()->route('seller.carriers.index');
        }

        $carrier = Carrier::where('seller_id', seller()->id)->findOrFail($request->carrier);
        $tariffs = $carrier->tariffs()->orderBy('type')->orderBy('max_weight')->paginate(20);

        return view('front::sellers.panel.tariffs.index', compact('carrier', 'tariffs'));
    }

    public function create(Request $request)
    {
        $carrier = Carrier::where('seller_id', seller()->id)->findOrFail($request->carrier);

        return view('front::sellers.panel.tariffs.create', compact('carrier'));
    }

    public function store(StoreTariffRequest $request)
    {
        $data = $request->validated();

        // امنیت IDOR: رول اعتبارسنجی فقط exists است؛ مالکیت حامل روی carrier_id بررسی می‌شود
        $carrier = Carrier::where('seller_id', seller()->id)->findOrFail($data['carrier_id']);

        Tariff::create($data);


        session()->put('toast-success','تعرفه با موفقیت ایجاد شد.');
        return response('success');
    }

    public function edit(Tariff $tariff)
    {
        $this->assertSellerTariff($tariff);

        return view('front::sellers.panel.tariffs.edit', compact('tariff'));
    }

    public function update(UpdateTariffRequest $request, Tariff $tariff)
    {
        $this->assertSellerTariff($tariff);

        $data = $request->validated();

        // امنیت IDOR: جابه‌جایی تعرفه به حامل دیگری (عمومی/سایر فروشندگان) مسدود می‌شود
        $carrier = Carrier::where('seller_id', seller()->id)->findOrFail($data['carrier_id']);

        $tariff->update($data);

        session()->put('toast-success','تعرفه با موفقیت ویرایش شد.');
        return response('success');
    }

    public function destroy(Tariff $tariff)
    {
        $this->assertSellerTariff($tariff);

        $tariff->delete();

        session()->put('toast-success', 'تعرفه با موفقیت حذف شد.');
        return response('success');
    }

    /**
     * امنیت IDOR: تعرفه باید از طریق حاملِ متعلق به فروشنده جاری باشد (وگرنه 404)
     */
    private function assertSellerTariff(Tariff $tariff)
    {
        abort_unless($tariff->carrier && $tariff->carrier->seller_id === seller()->id, 404);
    }
}
