/**
 * SaniarTheme — مقداردهی‌های اولیه صفحه محصول (فرم نظر / نظرات خالی)
 * استخراج‌شده از add-review.blade.php و reviews-comments.blade.php
 */
jQuery(function ($) {
    // مقدار پیش‌فرض امتیاز فرم ثبت نظر
    $('input[name=rating]').val(3);

    // در حالت «بدون هیچ نظری» صفحه‌بند مخفی شود
    $('#Newscomments .paginationPager').addClass('hide');
});
