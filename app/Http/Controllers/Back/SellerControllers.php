<?php

namespace App\Http\Controllers\Back;

use App\Exports\OrdersExport;
use App\Exports\SellerOrdersExport;
use App\Exports\UsersExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Back\Seller\UpdateSellerRequest;
use App\Http\Resources\Datatable\Order\OrderCollection;
use App\Http\Resources\Datatable\Product\ProductCollection;
use App\Http\Resources\Datatable\Seller\SellerCollection;
use App\Models\Carrier;
use App\Models\Category;
use App\Models\NotificationManage;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Province;
use App\Models\Seller;
use App\Models\SellerInfo;
use App\Models\SellerVariant;
use App\Models\SizeType;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class SellerControllers extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Seller::class, 'seller');
        if(option('multi_vendor_system_status','false')=="false"){
            abort(503,'سیستم چند فروشندگی غیرفعال است');
        }
    }

    public function index()
    {
        return view('back.sellers.index');
    }

    public function apiIndex(Request $request)
    {
        $this->authorize('sellers.index');

        $sellers = SellerInfo::datatableFilter($request);
        //$sellers_info = SellerInfo::datatableFilter($request);

        $sellers = datatable($request, $sellers);

        return new SellerCollection($sellers);
    }

    public function products()
    {
        $this->authorize('sellers.products');
        return view('back.sellers.products');
    }

    public function productsApiIndex(Request $request)
    {
        $this->authorize('sellers.products');
        $seller_variants=SellerVariant::all();
        $product_id=[];
        foreach ($seller_variants as $seller_variant){
            $product_id[]=$seller_variant->product_id;
        }
        $product_id=array_unique($product_id);
        $products = Product::whereIn('id', $product_id)->detectLang()->datatableFilter($request);

        $products = datatable($request, $products);

        return new ProductCollection($products);
    }

    public function create()
    {
        //
    }


    public function store(Request $request)
    {
        //
    }


    public function show(Seller $seller)
    {
        $categories=Category::where('category_id',null)->get();
        $provinces = Province::with('cities:id,province_id,name')->select('id', 'name')->get();
        $products= Product::where('seller_id',$seller->id)->take(6)->get();
        $variants=SellerVariant::where('seller_id',$seller->id)->take(6)->get();

        $orderItem_ids=OrderItem::where('seller_id',$seller->id)->get();

        $order_ids=[];
        foreach ($orderItem_ids as $orderItem_id){
            $order_ids[]=$orderItem_id->order_id;
        }
        $order_ids=array_unique($order_ids);
        $orders = Order::whereIn('id',$order_ids)->orderBy('id','desc')->take(5)->get();


        $users_notifications = DB::table('notification_manage_users')->where('seller_id',$seller->id)->get();
        $users_notification_ids=[];
        foreach ($users_notifications as $users_notification){
            $users_notification_ids[]=$users_notification->notification_manage_id;
        }
        $notifications=NotificationManage::whereIn('id',$users_notification_ids)->where('private','seller')->get();

        $sellerCarriers = Carrier::detectLang()->forCurrentSeller($seller->id)->latest()->paginate(20);

        return view('back.sellers.show', compact('seller','provinces','categories','products','variants','orders','notifications','sellerCarriers'));
    }


    public function update(UpdateSellerRequest $request, Seller $seller)
    {

        $seller->email=$request->email;
        $seller->mobile=$request->mobile;
        $seller->status=$request->status;
        $seller->status_register=$request->status_register;
        $seller->status_documents=$request->status_documents;
        $seller->status_work=$request->status_work;
        if ($request->password){
            $seller->password=Hash::make($request->password);
        }
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
        $seller_info->mobile=$request->mobile;

        $seller_info->business_name=$request->business_name;
        $seller_info->shaba_number=$request->shaba_number;
        $seller_info->main_supply_category_id=$request->main_supply_category_id;
        $seller_info->number_of_products=$request->number_of_products;

        $seller_info->bio=$request->bio;
        $seller_info->website=$request->website;
        $seller_info->vat_free=$request->vat_free;
        $seller_info->status_documents=$request->status_documents;
        $seller_info->satisfaction=$request->satisfaction;
        $seller_info->operation=$request->operation;

        if ($request->hasFile('image')) {

            $imageOptions=[
                'size'=>100,
                'path'=>"uploads/sellers/logo/",
                'field'=>"logo",
            ];
            $seller_info->logo =  uploadOptimizedImage($request->file('image'), 'sellerInfo', $seller->id,$imageOptions);
        }
        if ($request->hasFile('card_image')) {

            $imageOptions=[
                'size'=>100,
                'path'=>"uploads/sellers/documents/",
                'field'=>"card_image",
            ];
            $seller_info->card_image =  uploadOptimizedImage($request->file('image'), 'sellerInfo', $seller->id,$imageOptions);

        }
        if ($request->hasFile('card_image_back')) {

            $imageOptions=[
                'size'=>100,
                'path'=>"uploads/sellers/documents/",
                'field'=>"card_image_back",
            ];
            $seller_info->card_image_back =  uploadOptimizedImage($request->file('image'), 'sellerInfo', $seller->id,$imageOptions);

        }
        if ($request->hasFile('vat_image')) {

            $imageOptions=[
                'size'=>100,
                'path'=>"uploads/sellers/documents/",
                'field'=>"vat_image",
            ];
            $seller_info->vat_image =  uploadOptimizedImage($request->file('image'), 'sellerInfo', $seller->id,$imageOptions);


        }

        $seller_info->save();
        session()->put('toast-success','اطلاعات فروشنده با موفقیت ویرایش شد.');
        return response("success");
    }

    public function views(Seller $seller)
    {
        $views = $seller->views()->latest()->paginate(20);

        return view('back.sellers.views', compact('views', 'seller'));
    }
    public function seller_products(Seller $seller)
    {
        $products= Product::where('seller_id',$seller->id)->paginate(20);

        return view('back.sellers.seller_products', compact('products', 'seller'));
    }
    public function seller_variants(Seller $seller)
    {
        $variants=SellerVariant::where('seller_id',$seller->id)->paginate(20);

        return view('back.sellers.seller_variants', compact('variants', 'seller'));
    }

    public function destroy(Seller $seller)
    {

        if ($seller->seller_info->logo && Storage::disk('public')->exists($seller->seller_info->logo)) {
            Storage::disk('public')->delete($seller->seller_info->logo);
        }
        if ($seller->seller_info->vat_image && Storage::disk('public')->exists($seller->seller_info->vat_image)) {
            Storage::disk('public')->delete($seller->seller_info->vat_image);
        }
        if ($seller->seller_info->card_image && Storage::disk('public')->exists($seller->seller_info->card_image)) {
            Storage::disk('public')->delete($seller->seller_info->card_image);
        }
        if ($seller->seller_info->card_image_back && Storage::disk('public')->exists($seller->seller_info->card_image_back)) {
            Storage::disk('public')->delete($seller->seller_info->card_image_back);
        }
        if (file_exists(public_path('uploads/sellers/documents/'.$seller->id))){
            rmdir(public_path('uploads/sellers/documents/'.$seller->id));
        }

        //delete product seller
        $sellerVariants=SellerVariant::where('seller_id',$seller->id)->get();
        foreach ($sellerVariants as $sellerVariant){
            $product=Product::find($sellerVariant->product_id);
            if ($product){
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

        return response('success');
    }

    public function multipleDestroy(Request $request)
    {

        $this->authorize('sellers.delete');

        $request->validate([
            'ids'   => 'required|array',
            'ids.*' => 'exists:sellers,id',
        ]);

        foreach ($request->ids as $id) {
            $seller = Seller::find($id);
            $this->destroy($seller);
        }

        return response('success');
    }

    public function orders()
    {
        $this->authorize('orders.index');
        $sizeTypes = SizeType::latest()->get();

        return view('back.sellers.orders.index' , compact('sizeTypes'));
    }

    public function apiOrdersIndex(Request $request)
    {
        $this->authorize('orders.index');

        $orders = Order::where('seller_id', '!=', null)->filter($request);

        $orders = datatable($request, $orders);

        return new OrderCollection($orders);
    }

    public function seller_orders(Seller $seller)
    {
        $orderItem_ids=OrderItem::where('seller_id',$seller->id)->get();
        $order_ids=[];
        foreach ($orderItem_ids as $orderItem_id){
            $order_ids[]=$orderItem_id->order_id;
        }
        $order_ids=array_unique($order_ids);
        $orders=Order::whereIn('id',$order_ids)->get();

        return view('back.sellers.orders.seller_orders' , compact('seller'));
    }

    public function seller_orders_ApiIndex(Seller $seller,Request $request)
    {

        $this->authorize('orders.index');
        $orderItem_ids=OrderItem::where('seller_id',$seller->id)->get();
        $order_ids=[];
        foreach ($orderItem_ids as $orderItem_id){
            $order_ids[]=$orderItem_id->order_id;
        }
        $order_ids=array_unique($order_ids);
        $orders = Order::whereIn('id',$order_ids)->filter($request);

        $orders = datatable($request, $orders);

        return new OrderCollection($orders);
    }

    public function seller_orders_show(Seller $seller,Order $order)
    {
        return view('back.sellers.orders.show', compact('seller','order'));
    }
    public function exportOrders(Request $request)
    {
        $this->authorize('orders.export');
        $orderItem_ids=OrderItem::where('seller_id',$seller->id)->get();
        $order_ids=[];
        foreach ($orderItem_ids as $orderItem_id){
            $order_ids[]=$orderItem_id->order_id;
        }
        $order_ids=array_unique($order_ids);
        $orders = Order::whereIn('id',$order_ids)->filter($request)->get();

        switch ($request->export_type) {
            case 'excel': {
                return $this->exportSellerOrderExcel($orders, $request);
                break;
            }
            default: {
                return $this->exportPrint($orders, $request);
            }
        }
    }
    public function sellerOrdersExport(Seller $seller,Request $request)
    {
        $this->authorize('orders.export');
        $orderItem_ids=OrderItem::where('seller_id',$seller->id)->get();
        $order_ids=[];
        foreach ($orderItem_ids as $orderItem_id){
            $order_ids[]=$orderItem_id->order_id;
        }
        $order_ids=array_unique($order_ids);
        $orders = Order::whereIn('id',$order_ids)->filter($request)->get();

        switch ($request->export_type) {
            case 'excel': {
                return $this->exportSellerOrderExcel($orders, $request);
                break;
            }
            default: {
                return $this->exportPrint($orders, $request);
            }
        }
    }

    private function exportSellerOrderExcel($orders)
    {
        return Excel::download(new SellerOrdersExport($orders), 'sellersOrders.xlsx');
    }

    public function printOrdersSeller(Seller $seller,Order $order)
    {
        $this->authorize('orders.view');
        return view('back.sellers.orders.print', compact('order','seller'));
    }
    public function printAllOrdersSeller(Request $request)
    {
        $this->authorize('orders.view');

        foreach ($request->ids as $id) {
            $orders = Order::paid()->whereIn('id', $request->ids)->get();
        }

        return view('back.sellers.orders.print-all', compact('orders'));
    }


    // start notifications

    public function notifications(Seller $seller)
    {

        $users_notifications = DB::table('notification_manage_users')->where('seller_id',$seller->id)->get();
        $users_notification_ids=[];
        foreach ($users_notifications as $users_notification){
            $users_notification_ids[]=$users_notification->notification_manage_id;
        }
        $notifications=NotificationManage::whereIn('id',$users_notification_ids)->where('private','seller')->paginate(20);

        return view('back.sellers.notifications.index',compact('notifications','seller'));
    }
    public function notification_create(Seller $seller)
    {
        return view('back.sellers.notifications.create',compact('seller'));
    }
    public function notification_store(Seller $seller,Request $request)
    {
        $this->validate($request, [
            'message'         => 'required',
        ]);

        $notification=new NotificationManage();
        $notification->admin_id=Auth::guard('adminPanel')->id();
        $notification->title=$request->title;
        $notification->message=$request->message;
        $notification->private='seller';
        $notification->priority=$request->priority;
        $notification->popup=$request->popup ? true : false;
        $notification->save();

        $notification->sellers()->attach($seller);

        session()->put('toast-success','اعلان با موفقیت ایجاد شد.');
        return response("success");
    }
    public function notification_show(Seller $seller,NotificationManage $notification)
    {
        return view('back.sellers.notifications.show',compact('notification','seller'));
    }
    public function notification_update(Seller $seller,NotificationManage $notification,Request $request)
    {
        $this->validate($request, [
            'message'         => 'required',
        ]);

        $notification->admin_id=Auth::guard('adminPanel')->id();
        $notification->title=$request->title;
        $notification->message=$request->message;
        $notification->private='seller';
        $notification->priority=$request->priority;
        $notification->popup=$request->popup ? true : false;
        $notification->save();

        session()->put('toast-success','اعلان با موفقیت ویرایش شد.');
        return response("success");
    }
    // end notifications

}
