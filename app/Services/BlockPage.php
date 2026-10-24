<?php

namespace App\Services;

use App\Models\BotTrap;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/*
 * فاز ۷۱ — پاسخ‌دهندهٔ صفحهٔ زیبای مسدودسازی (تلهٔ ربات)
 *
 * تا فاز ۷۰، مسدودشده‌ها متن خام «Forbidden» می‌دیدند — بدون هیچ توضیحی
 * (گزارش کاربر: «باید صفحه یک ظاهر زیبا داشته باشد و به کاربر بگوید بلاک شده است»).
 *
 * صفحهٔ رندرشده کاملاً خودکفاست (همهٔ استایل‌ها inline) — چون کاربرِ مسدود
 * به هیچ asset دیگری از همین سایت هم دسترسی ندارد (BlockBots سراسری است).
 *
 * دو حالت:
 *   blocked  → آی‌پی به‌طور سراسری مسدود است (توضیح + انقضای خودکار ۲۴ ساعته + نمایش آی‌پی)
 *   trap     → فقط این مسیرِ طعمه ممنوع است (وقتی کلید اصلی خاموش است و ثبت هم نمی‌شود)
 */
class BlockPage
{
    public static function respond(Request $request, string $mode = 'blocked', ?string $ip = null): Response|JsonResponse
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => $mode === 'blocked'
                    ? 'دسترسی شما به‌صورت موقت مسدود شده است.'
                    : 'دسترسی به این مسیر مجاز نیست.',
            ], 403);
        }

        try {
            $siteName = trim((string) option('info_site_title', '')) ?: config('app.name', 'Shop Pro');
        } catch (\Throwable) {
            $siteName = config('app.name', 'Shop Pro');
        }

        return response()
            ->view('errors.bot-blocked', [
                'mode'     => $mode === 'trap' ? 'trap' : 'blocked',
                'ip'       => $ip,
                'siteName' => $siteName,
                'ttlHours' => BotTrap::BLOCK_TTL_HOURS,
            ], 403)
            // صفحهٔ مسدودی هرگز کش نشود — بعد از رفع مسدودی نباید از کش CDN بیاید
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache');
    }
}
