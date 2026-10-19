<?php

namespace App\Http\Controllers\AdminPanel;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * آپلود مدیا از ویرایشگر CKEditor پنل مدیریت جدید.
 * دسترسی: هر ادمینِ واردشده (بدون پرمیشن خاص).
 *
 * پاسخ موفق: { "url": "..." } — آداپتر آپلود CKEditor در admin.js همین ساختار را می‌خواند.
 */
class MediaController extends Controller
{
    public function __construct()
    {
        auth('adminPanel')->check() ?: abort(403);
    }

    public function store(Request $request)
    {
        /*
         * توجه: قانون «image» لاراول ۱۳ پسوند svg را نمی‌پذیرد؛ برای پوشش
         * همان فرمت‌های مجاز، کنترل نوع با mimes (بر اساس محتوای واقعی فایل)
         * + extensions انجام می‌شود.
         */
        $request->validate([
            'file' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,gif,svg', 'extensions:jpg,jpeg,png,webp,gif,svg', 'max:4096'],
        ], [
            'file.required'   => 'انتخاب فایل تصویر الزامی است.',
            'file.file'       => 'فایل ارسالی معتبر نیست.',
            'file.mimes'      => 'فرمت‌های مجاز تصویر: jpg، jpeg، png، webp، gif، svg',
            'file.extensions' => 'پسوند فایل مجاز نیست.',
            'file.max'        => 'حجم تصویر نباید بیشتر از ۴ مگابایت باشد.',
        ]);

        $file = $request->file('file');

        try {
            try {
                if (strtolower((string) $file->getClientOriginalExtension()) === 'svg') {
                    // SVG را نمی‌توان با Intervention باز-انکد کرد — ذخیره مستقیم در پوشه ماه جاری
                    $path = 'uploads/images/' . now()->format('Y-m') . '/svg-' . uniqid() . '.svg';
                    Storage::disk('public')->put($path, file_get_contents($file->getRealPath()));
                } else {
                    // بهینه‌سازی/تبدیل با هلپر موجود پنل (ذخیره زیر uploads/images/)
                    $path = uploadOptimizedImage($file, 'images');
                }
            } catch (Throwable $e) {
                /*
                 * اگر پردازش تصویر در دسترس نبود (مثلاً GD سرور فاقد پشتیبانی
                 * webp/jpeg است) یا هر خطای دیگری رخ داد، فایل اصلی بدون تغییر
                 * ذخیره می‌شود تا آپلود کاربر شکست نخورد.
                 */
                report($e);

                $path = 'uploads/images/' . now()->format('Y-m') . '/' . uniqid() . '.' . strtolower((string) $file->getClientOriginalExtension());
                Storage::disk('public')->put($path, file_get_contents($file->getRealPath()));
            }
        } catch (Throwable $e) {
            report($e);

            return response()->json(['message' => 'ذخیره تصویر با خطا مواجه شد.'], 500);
        }

        return response()->json(['url' => url($path)]);
    }
}
