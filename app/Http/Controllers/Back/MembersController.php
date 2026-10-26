<?php

namespace Modules\CustomerClub\Http\Controllers\Back;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Modules\CustomerClub\Models\LoyaltyPoint;
use Modules\CustomerClub\Services\LoyaltyService;

class MembersController extends Controller
{
    public function index(Request $request)
    {
        // بدون پچ هسته: موجودی زنده با زیرکوئری (نه relation روی User)
        $balanceSql = 'COALESCE((SELECT SUM(lp.remaining) FROM loyalty_points lp '
            .'WHERE lp.user_id = users.id AND lp.remaining > 0), 0)';

        $query = User::query()
            ->whereIn('id', LoyaltyPoint::query()->select('user_id'))
            ->select('users.*')
            ->selectRaw("{$balanceSql} as live_balance")
            ->orderByDesc(\Illuminate\Support\Facades\DB::raw($balanceSql));

        if ($request->filled('q')) {
            $term = $request->string('q');
            $query->where(fn ($q) => $q->where('username', 'like', "%{$term}%")
                ->orWhere('first_name', 'like', "%{$term}%")
                ->orWhere('last_name', 'like', "%{$term}%")
                ->orWhere('mobile', 'like', "%{$term}%"));
        }

        return view('customer-club::back.members.index', [
            'rows' => $query->paginate(30)->withQueryString(),
            'term' => $request->q,
        ]);
    }

    /**
     * تنظیم دستی امتیاز — مثبت = لات جدید؛ منفی = کسر FIFO تا سقف موجودی.
     * دلیل اجباری است (audit کامل در دفتر).
     */
    public function adjust(Request $request, User $user)
    {
        $data = $request->validate([
            'points'      => ['required', 'integer', 'not_in:0', 'min:-1000000', 'max:1000000'],
            'description' => ['required', 'string', 'max:500'],
        ]);

        $points = (int) $data['points'];

        if ($points > 0) {
            LoyaltyService::award(
                $user,
                $points,
                LoyaltyPoint::TYPE_ADJUST,
                'تنظیم دستی ادمین: '.$data['description'],
                'admin',
                (int) auth('adminPanel')->id(),
                null,
                true,
                'admin_adjust'
            );

            session()->put('toast-success', number_format($points)." امتیاز به مشتری اضافه شد.");
        } else {
            $ok = LoyaltyService::deduct(
                $user->id,
                abs($points),
                LoyaltyPoint::TYPE_ADJUST,
                'admin',
                'تنظیم دستی ادمین: '.$data['description'],
                (int) auth('adminPanel')->id()
            );

            if (!$ok) {
                session()->put('toast-error', 'موجودی امتیاز مشتری برای این کسر کافی نیست.');

                return redirect()->route('admin.customerclub.members.index');
            }

            session()->put('toast-success', number_format(abs($points))." امتیاز از مشتری کسر شد.");
        }

        return redirect()->route('admin.customerclub.members.index');
    }
}
