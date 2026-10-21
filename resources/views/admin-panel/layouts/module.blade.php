{{-- 
    فاز ۳۴ — لایه‌ی پل دو-پنلی (نقطه‌ی واحد تصمیم برای ویوهای پکیج‌های به‌روزشده)
    --------------------------------------------------------------------------
    پکیج‌های به‌روزشده (فاز ۳۲) مستقیماً این لایه را @extends می‌کنند؛ حالا این
    لایه هم مثل back.layouts.master از App\Support\ActivePanel می‌پرسد:
      • پنل فعال = جدید  →  admin-panel::layouts.module-new (کروم پنل جدید)
      • پنل فعال = قدیمی →  back.layouts.master-legacy (کروم پنل قدیمی)
    به این ترتیب حتی وقتی کاربر هر دو پنل را در دو تب باز دارد (?ap=new در
    لینک‌های سایدبار جدید) یا کوکی دستی عوض شده باشد، chrome همیشه درست است.
    --------------------------------------------------------------------------
    نکته: مستقیم به لایه‌ی «واقعی» (module-new) اشاره می‌کنیم نه به خود این
    wrapper — وگرنه ریکورشن بی‌نهایت رخ می‌دهد.
--}}
@php($apLayout = (\App\Support\ActivePanel::useNewPanel() && \App\Support\ActivePanel::hasNewChrome()) ? 'admin-panel::layouts.module-new' : 'back.layouts.master-legacy')
@extends($apLayout)
