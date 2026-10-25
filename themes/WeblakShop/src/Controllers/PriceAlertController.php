<?php

namespace Themes\WeblakShop\src\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Price;
use App\Models\PriceAlert;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * فاز ۷۸ — اشتراک هشدار کاهش قیمت (کاربر یا مهمان با موبایل).
 * فاز ۸۳ — انتخاب کانال اطلاع‌رسانی: پیامک و/یا اعلان پروفایل.
 *   - کانال‌های مجاز از تنظیمات سایت (dt_price_alert_channels) می‌آید.
 *   - مهمان فقط پیامک می‌گیرد (اعلان پروفایل مخصوص کاربر عضو است).
 *   - پاسخ JSON تا مودال مدرن فرانت بتواند وضعیت موفقیت/خطا را نمایش دهد.
 */
class PriceAlertController extends Controller
{
    public function store(Request $request)
    {
        // فاز ۸۱ — قابلیت از تنظیمات سایت غیرفعال شده است
        abort_if(option('dt_price_alert_status', '1') != '1', 404);

        $this->validate($request, [
            'product_id' => [
                'required',
                Rule::exists('products', 'id')->where('status', 'Accept'),
            ],
        ]);

        $product = \App\Models\Product::query()
            ->where('id', $request->product_id)
            ->where('status', 'Accept')
            ->firstOrFail();

        // فاز ۸۳ — کانال‌های مجاز از تنظیمات سایت («هر دو» پیش‌فرض)
        $allowed = $this->allowedChannels();

        $channels = $request->input('channels');
        $channels = is_array($channels) ? $channels : (! empty($channels) ? [$channels] : $allowed);
        $channels = array_values(array_unique(array_intersect($channels, $allowed)));

        // کاربر عضو یا مهمان؟
        if (auth()->check()) {
            $user   = auth()->user();
            $mobile = $user->mobile ?: $user->username;
            $name   = $user->fullname ?? $user->username;
            $userId = $user->id;
        } else {
            $this->validate($request, [
                'mobile' => 'required|string|regex:/(09)[0-9]{9}/|digits:11',
                'name'   => 'nullable|string|max:100',
            ]);

            $mobile = $request->mobile;
            $name   = $request->name;
            $userId = null;
        }

        // فاز ۸۳ — قاعده‌های کانال: مهمان فقط پیامک؛ اگر پیامک در کانال‌های مجاز نیست،
        // اشتراکِ بدون هیچ کانالی بی‌معناست → خطا
        if (! in_array('sms', $allowed, true) && ! $userId) {
            return response()->json([
                'status'  => 'error',
                'message' => 'اطلاع‌رسانی این قابلیت فقط از طریق اعلان پروفایل است؛ لطفاً وارد حساب کاربری شوید.',
            ], 422);
        }

        if (! $userId) {
            $channels = ['sms'];
        } elseif (empty($channels)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'حداقل یکی از راه‌های اطلاع‌رسانی را انتخاب کنید.',
            ], 422);
        }

        PriceAlert::updateOrCreate(
            [
                'product_id' => $product->id,
                'mobile'     => $mobile,
            ],
            [
                'user_id'        => $userId,
                'name'           => $name,
                'target_price'   => $this->currentBuyablePrice($product),
                'notify_sms'     => in_array('sms', $channels, true),
                'notify_profile' => $userId && in_array('profile', $channels, true),
                'active'         => true,
                'notified_at'    => null,
            ]
        );

        return response()->json(['status' => 'ok']);
    }

    public function destroy(Request $request)
    {
        $this->validate($request, [
            'product_id' => 'required|integer',
        ]);

        $mobile = auth()->check()
            ? (auth()->user()->mobile ?: auth()->user()->username)
            : $request->mobile;

        if (! $mobile) {
            return response('success');
        }

        PriceAlert::query()
            ->where('product_id', $request->product_id)
            ->where('mobile', $mobile)
            ->delete();

        return response('success');
    }

    /**
     * فاز ۸۳ — کانال‌های اطلاع‌رسانی مجاز از تنظیمات سایت.
     * مقادیر ممکن dt_price_alert_channels: «sms,profile» | «sms» | «profile»
     * خالی/نامعتبر = هر دو (پیش‌فرض).
     */
    private function allowedChannels(): array
    {
        $raw = trim((string) option('dt_price_alert_channels', 'sms,profile'));

        if ($raw === '') {
            return ['sms', 'profile'];
        }

        $channels = collect(explode(',', $raw))
            ->map(fn ($c) => trim(strtolower($c)))
            ->filter(fn ($c) => in_array($c, ['sms', 'profile'], true))
            ->unique()
            ->values()
            ->all();

        return $channels ?: ['sms', 'profile'];
    }

    /**
     * کمترین قیمت مؤثرِ قابل‌خریدِ فعلی محصول — هدف پیش‌فرضِ اشتراک.
     * با افت قیمت زیر همین عدد، کاربر پیامک می‌گیرد.
     */
    private function currentBuyablePrice($product)
    {
        return $product->prices()
            ->where('stock', '>', 0)
            ->where('published', 1)
            ->get()
            ->map(fn (Price $price) => $price->salePrice())
            ->filter(fn ($price) => $price > 0)
            ->min() ?: null;
    }
}
