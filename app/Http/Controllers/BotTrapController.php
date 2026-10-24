<?php

namespace App\Http\Controllers;

use App\Models\BotTrap;
use App\Services\BlockPage;
use App\Services\SecuritySettings;
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

        // فاز ۷۱ — صفحهٔ زیبا به‌جای متن خام Forbidden
        // اگر تله فعال است، IP تازه مسدود شده ⇒ پیام «مسدود»؛ با کلید خاموش فقط «این مسیر ممنوع»
        return BlockPage::respond(
            $request,
            SecuritySettings::botTrapEnabled() ? 'blocked' : 'trap',
            (string) $request->ip()
        );
    }
}
