<?php

namespace App\Http\Controllers;

use App\Models\BotTrap;
use Illuminate\Http\Request;

/*
 * ربات‌گیر — مسیرهای طعمه
 * این مسیرها عمداً در robots.txt اجازه‌ی پیمایش ندارند و هیچ کاربر واقعی‌ای به آن‌ها سر نمی‌زند.
 * هر درخواست به این مسیرها = ربات/اسکنر ⇒ IP مسدود می‌شود.
 */
class BotTrapController extends Controller
{
    public function catch(Request $request)
    {
        BotTrap::catch(
            (string) $request->ip(),
            (string) $request->userAgent(),
            (string) $request->path()
        );

        return response()->make('Forbidden', 403);
    }
}
