<?php

namespace Themes\Saniar\src\Controllers\sellers;

use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Models\Admin;
use App\Models\Category;
use App\Models\City;
use App\Models\Favorite;
use App\Models\Product;
use App\Models\Province;
use App\Models\Seller;
use App\Models\SellerEcontract;
use App\Models\SellerInfo;
use App\Notifications\Seller\SellerEditProfile;
use App\Notifications\Seller\SellerRegistered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Intervention\Image\Facades\Image;
use Themes\Saniar\src\Requests\seller_panel\profile\UpdateSellerRequest;

class ProfileSellerController extends Controller
{
    public function index()
    {
        $seller=Seller::find(Auth::guard('seller')->id());
        $seller_info=SellerInfo::where('seller_id',$seller->id)->first();
        $categories=Category::where('category_id',null)->get();
        $econtract=SellerEcontract::find(1);
        $provinces = Province::with('cities:id,province_id,name')->select('id', 'name')->get();
        return view('front::sellers.panel.profile.index',compact('seller','seller_info','categories','provinces','econtract'));
    }

    public function update(UpdateSellerRequest $request, Seller $seller)
    {
        if ($seller->id!=Auth::guard('seller')->id()){
            abort(404);
        }
        $seller->email=$request->email;
        if ($request->prev_password and $request->password){
            if (!Hash::check($request->prev_password, $seller->password)) {
                throw ValidationException::withMessages(['prev_password' => 'رمز عبور قبلی وارد شده اشتباه است']);
            }else{
                $seller->password = Hash::make($request->password);
                session()->put('toast-success','رمز ورود شما تغییر کرد.');
            }
        }
        $seller->status_work="EditProfile";
        $seller->save();

        $seller_info=SellerInfo::where('seller_id',$seller->id)->first();
        $seller_info->seller_id=$seller->id;
        $seller_info->first_name=$request->first_name;
        $seller_info->last_name=$request->last_name;
        $seller_info->birth_day=$request->birth_day;
        $seller_info->gender=$request->gender;
        $seller_info->identity_card_number=$request->identity_card_number;
        $seller_info->national_identity_number=$request->national_identity_number;

        $seller_info->company_name=$request->company_name;
        $seller_info->company_type=$request->company_type;
        $seller_info->company_registration_number=$request->company_registration_number;
        $seller_info->company_national_identity_number=$request->company_national_identity_number;
        $seller_info->company_economic_number=$request->company_economic_number;

        $seller_info->state_id=$request->state_id;
        $seller_info->city_id=$request->city_id;
        $seller_info->address=$request->address;
        $seller_info->phone=$request->phone;
        $seller_info->post_code=$request->post_code;
        $seller_info->location=$request->lat_and_long;

        $seller_info->shaba_number=$request->shaba_number;
        $seller_info->main_supply_category_id=$request->main_supply_category_id;
        $seller_info->number_of_products=$request->number_of_products;

        $seller_info->bio=$request->bio;
        $seller_info->website=$request->website;
        $seller_info->vat_free=$request->vat_free;


        if ($request->hasFile('image')) {
            $seller_info->logo = uploadOptimizedImage($request->image, 'sellers/logo',$seller->id);
        }

        if ($request->hasFile('card_image')) {
            $seller_info->card_image  = uploadOptimizedImage($request->card_image, 'sellers/documents',$seller->id);
            $seller_info->status_documents='Waiting';
        }
        if ($request->hasFile('card_image_back')) {
            $seller_info->card_image_back  = uploadOptimizedImage($request->card_image_back, 'sellers/documents',$seller->id);
            $seller_info->status_documents='Waiting';
        }
        if ($request->hasFile('vat_image')) {
            $seller_info->vat_image  = uploadOptimizedImage($request->vat_image, 'sellers/documents',$seller->id);
            $seller_info->status_documents='Waiting';
        }

        $seller_info->save();

        $admins = Admin::whereIn('level', ['admin', 'creator'])->get();
        Notification::send($admins, new SellerEditProfile($seller));


        session()->put('toast-success','تغییرات شما با موفقیت ثبت شد.');
        return response("success");
    }

    public function set_econtract(Request $request)
    {
        $seller=Seller::find(Auth::guard('seller')->id());
        $seller_info=SellerInfo::where('seller_id',Auth::guard('seller')->id())->first();

        if ($seller->status_documents=='Accept'){

            $seller_info->econtract=1;
            $seller_info->save();
            return response([
                'status'=>'success'
            ]);
        }else{
            return response([
                'status'=>'documents'
            ]);
        }
    }
}
