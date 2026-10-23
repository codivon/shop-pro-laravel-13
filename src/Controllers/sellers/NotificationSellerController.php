<?php

namespace Themes\Saniar\src\Controllers\sellers;

use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Models\City;
use App\Models\Favorite;
use App\Models\NotificationManage;
use App\Models\Province;
use App\Models\SellerVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class NotificationSellerController extends Controller
{
    public function index(Request $request)
    {
        $notifications=seller()->notifications()->filter($request)->paginate(20);
        $notifications_count=seller()->notifications()->filter($request)->get();
        return view('front::sellers.panel.notifications.index',compact(['notifications','notifications_count']));
    }

    public function read(NotificationManage $notification)
    {
        $users_notifications = DB::table('notification_manage_users')->where(['seller_id'=>sellerID(),'notification_manage_id'=>$notification->id])->first();
        if (!$users_notifications->read){
            DB::table('notification_manage_users')->where(['seller_id'=>sellerID(),'notification_manage_id'=>$notification->id])->update(['read'=>1]);

            return response([
                'status'=>'success',
                'count'=>count(seller()->notifications()->where('read',0)->get())
            ]);
        }
    }
}
