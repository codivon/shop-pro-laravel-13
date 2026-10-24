<?php

namespace Themes\Saniar\src\Controllers\sellers;

use App\Http\Controllers\Controller;
use App\Models\NotificationManage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NotificationSellerController extends Controller
{
    public function index(Request $request)
    {
        // سانیار فاز J: بدون scopeFilterِ خراب — NotificationManage::scopeFilter روی جدولِ
        // بدون ستون read فیلتر می‌زند (SQL 500 در WeblakShop)؛ ستون read فقط روی پیوت است.
        $query = seller()->notifications();

        if ($request->filled('priority') && $request->priority !== 'all') {
            $query->where('priority', $request->priority);
        }
        if ($request->input('unread') === 'on') {
            $query->wherePivot('read', 0);
        }
        if ($request->input('sort') === 'priority') {
            $query->orderBy('priority', 'asc');
        } else {
            $query->latest();
        }

        $notifications = $query->paginate(20);

        // شمارنده از ->total() خود Paginator خوانده می‌شود
        return view('front::sellers.panel.notifications.index', compact('notifications'));
    }

    public function read(NotificationManage $notification)
    {
        // سانیار فاز J: ردیف پیوت ممکن است اصلاً وجود نداشته باشد → ->read روی null = 500 در WeblakShop
        $row = DB::table('notification_manage_users')
            ->where(['seller_id' => sellerID(), 'notification_manage_id' => $notification->id])
            ->first();

        if ($row && !$row->read) {
            DB::table('notification_manage_users')->where('id', $row->id)->update([
                'read'    => 1,
                'read_at' => now(),
            ]);
        }

        // شمارش باقی‌ماندهٔ ناخوانده بعد از نشانه‌گذاری (wherePivot — ستون فقط روی پیوت است)
        $count = seller()->notifications()->wherePivot('read', 0)->count();

        return response(['status' => 'success', 'count' => $count]);
    }
}
