<?php

namespace Themes\Saniar\src\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Models\City;
use App\Models\Favorite;
use App\Models\Province;
use Illuminate\Http\Request;

class AddressesController extends Controller
{
    public function index()
    {
        $addresses = auth()->user()->addresses()->latest()->paginate(10);
        $provinces = Province::all();
        $active="addresses";
        return view('front::user.addresses.index', compact('addresses','provinces','active'));
    }

    public function show($id)
    {
        // امنیت: کاربر فقط باید آدرس خودش را ببیند (رفع IDOR)
        $address = Address::where('user_id', auth()->id())->find($id);

        if (! $address) {
            return response()->json(['message' => 'یافت نشد'], 404);
        }

        return response()->json(['address'=>$address]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'fullname' => 'required',
            'mobile' => 'required',
            'buildingNumber' => 'required',
            'postal_code' => 'required',
            'province_id' => 'required',
            'city_id' => 'required',
            'address' => 'required',
        ]);

        $user = auth()->user();

        $province_name=Province::where('id',$request->province_id)->first();
        $city_name=City::where('id',$request->city_id)->first();
        auth()->user()->addresses()->update(['active'=>'0']);

        $item=new Address();
        $item->address=$request->address;
        $item->fullname=$request->fullname;
        $item->mobile=$request->mobile;
        $item->buildingNumber=$request->buildingNumber;
        $item->postal_code=$request->postal_code;
        $item->province_id=$request->province_id;
        $item->city_id=$request->city_id;
        $item->province_name=$province_name->name;
        $item->city_name=$city_name->name;
        $item->unit=$request->unit;
        $item->lat=$request->lat;
        $item->lng=$request->lng;
        $item->user_id=$user->id;
        $item->active=1;
        $item->save();

        return response()->json(['action' => 'store']);
    }

    public function update(Request $request,Address $address)
    {
        if ($address->user_id != auth()->user()->id) {
            abort(404);
        }
        $request->validate([
            'fullname' => 'required',
            'mobile' => 'required',
            'buildingNumber' => 'required',
            'postal_code' => 'required',
            'province_id' => 'required',
            'city_id' => 'required',
            'address' => 'required',
        ]);



        $province_name=Province::where('id',$request->province_id)->first();
        $city_name=City::where('id',$request->city_id)->first();


        $address->address=$request->address;
        $address->fullname=$request->fullname;
        $address->mobile=$request->mobile;
        $address->buildingNumber=$request->buildingNumber;
        $address->postal_code=$request->postal_code;
        $address->province_id=$request->province_id;
        $address->city_id=$request->city_id;
        $address->province_name=$province_name->name;
        $address->city_name=$city_name->name;
        $address->unit=$request->unit;
        $address->lat=$request->lat;
        $address->lng=$request->lng;
        $address->save();

        return response()->json(['action' => 'update']);
    }

    public function active_address(Address $address)
    {
        if ($address->user_id != auth()->user()->id) {
            abort(404);
        }
        auth()->user()->addresses()->update(['active'=>'0']);
        $address->active=1;
        $address->save();
        return response()->json(['active' => 'success']);
    }

    public function destroy(Address $address)
    {
        if ($address->user_id != auth()->user()->id) {
            abort(404);
        }
        $active=0;
        if ($address->active==1){
            $active=1;
        }

        $address->delete();

        if ($active==1){
            auth()->user()->addresses()->latest()->take(1)->update(['active'=>'1']);
        }

        return redirect()->route('front.addresses.index');
    }
}
