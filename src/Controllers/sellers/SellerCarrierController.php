<?php

namespace Themes\Saniar\src\Controllers\sellers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Back\Carrier\StoreCarrierRequest;
use App\Http\Requests\Back\Carrier\UpdateCarrierRequest;
use App\Models\Carrier;
use App\Models\Province;

class SellerCarrierController extends Controller
{

    public function index()
    {
        $carriers = Carrier::detectLang()->forCurrentSeller()->latest()->paginate(20);

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
        $provinces = Province::with('cities:id,province_id,name')->select('id', 'name')->get();

        return view('front::sellers.panel.carriers.edit', compact('provinces', 'carrier'));
    }

    public function update(Carrier $carrier, UpdateCarrierRequest $request)
    {
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

        $carrier->cities()->detach();
        $carrier->delete();


        return response('success');
    }

    public function cities(Carrier $carrier)
    {
        return view('back.carriers.cities', compact('carrier'));
    }
}
