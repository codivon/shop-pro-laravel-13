<?php

/*
 * بوت‌استرپ مجموعهٔ تست — قبل از autoload رسمی.
 *
 * ⭐ درس از اجرای کامل مجموعه روی ویندوز (لودرگون):
 * max_execution_time در ویندوز زمانِ واقعی (wall-clock) را می‌شمارد و حتی
 * usleep داخل Symfony Process (WindowsPipes) هم در آن محاسبه می‌شود؛ اجرای
 * کامل Featureها روی سیستم‌های کند (اسکن Real-time آنتی‌ویروس روی هزاران
 * فایل vendor) از سقف ۳۰۰ ثانیهٔ php.ini رد می‌شود و وسط اجرا با
 * «Maximum execution time of 300 seconds exceeded» می‌میرد.
 * (روی لینوکس max_execution_time فقط CPU time می‌شمارد و در CLI اصلاً 0 است.)
 */
if (PHP_OS_FAMILY === 'Windows') {
    set_time_limit(0);
}

require __DIR__.'/../vendor/autoload.php';
