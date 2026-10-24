<?php

namespace Themes\SaniarTheme\src\Controllers\sellers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Back\Carrier\StoreCarrierRequest;
use App\Http\Requests\Back\Carrier\UpdateCarrierRequest;
use App\Models\Carrier;
use App\Models\Province;

class SellerCarrierController extends Controller
{

    public function index()
    {
        $carriers = Carrier::detectLang()->forCurrentSeller()
            ->with('province:id,name', 'city:id,name')
            ->latest()->paginate(20);

        return view('front::sellers.panel.carriers.index', compact('carriers'));
    }

    public function create()
    {
        $provinces = Province::with('cities:id,province_id,name')->select('id', 'name')->get();

        return view('front::sellers.panel.carriers.create', compact('provinces'));
    }

    public function store(StoreCarrierRequest $request)
    {
        $data = $request->validated();

        if ($request->hasFile('image')) {

            $data['image'] = uploadOptimizedImage($request->image, 'carriers');
        }

        $data['lang'] = app()->getLocale();


        $data['delivery_time_type'] = $request->delivery_time_type;
        $data['default_delivery_range'] = $request->default_delivery_range;
        $data['disable_holidays'] = $request->has('disable_holidays');
        $data['disable_fridays'] = $request->has('disable_fridays');
        $data['start_days_after_order'] = $request->start_days_after_order ?? 1;
        $data['user_select_ranges'] = $request->user_select_ranges ?? 7;
        $data['seller_id'] = seller()->id;

        $carrier = Carrier::create($data);

        $carrier->cities()->attach($request->included_cities);

        session()->put('toast-success','روش ارسال با موفقیت ایجاد شد.');
        return response('success');
    }

    public function edit(Carrier $carrier)
    {
        $this->assertSellerCarrier($carrier);

        $provinces = Province::with('cities:id,province_id,name')->select('id', 'name')->get();

        return view('front::sellers.panel.carriers.edit', compact('provinces', 'carrier'));
    }

    public function update(Carrier $carrier, UpdateCarrierRequest $request)
    {
        $this->assertSellerCarrier($carrier);

        $data = $request->validated();

        if ($request->hasFile('image')) {
            $data['image'] =uploadOptimizedImage($request->image, 'carriers',$carrier->id);
        } else {
            $data['image'] = $carrier->image;
        }


        $data['delivery_time_type'] = $request->delivery_time_type;
        $data['default_delivery_range'] = $request->default_delivery_range;
        $data['disable_holidays'] = $request->has('disable_holidays');
        $data['disable_fridays'] = $request->has('disable_fridays');
        $data['start_days_after_order'] = $request->start_days_after_order ?? 1;
        $data['user_select_ranges'] = $request->user_select_ranges ?? 7;

        $carrier->update($data);

        $carrier->cities()->sync($request->included_cities);

        session()->put('toast-success','روش ارسال با موفقیت ویرایش شد.');
        return response('success');
    }

    public function destroy(Carrier $carrier)
    {
        $this->assertSellerCarrier($carrier);

        $carrier->cities()->detach();
        $carrier->delete();


        return response('success');
    }

    public function cities(Carrier $carrier)
    {
        $this->assertSellerCarrier($carrier);

        // سانیار فاز J: در WeblakShop این متد نمای back-office را برمی‌گرداند که از روت
        // ادمین (پشت گارد Admin) فراخوانی می‌شد و برای فروشنده 500/403 می‌داد؛
        // سانیار روت seller-friendly مخصوص خود + پارشال پنل فروشنده دارد.
        $carrier->load('cities.province:id,name');

        return view('front::sellers.panel.carriers.partials.cities', compact('carrier'));
    }

    /**
     * امنیت IDOR: حامل باید متعلق به فروشنده جاری باشد (حامل‌های عمومی/سایر فروشندگان → 404)
     */
    private function assertSellerCarrier(Carrier $carrier)
    {
        abort_unless($carrier->seller_id === seller()->id, 404);
    }
}
