/**
 * سانیار فاز K — اسلایدرهای وبلاگ (ویجت پربازدیدها + پیشنهادها + آرشیو)
 * هر عنصر [data-saniar-blog-slider] یک سوایپر مستقل می‌گیرد.
 * اسکریپت‌های صفحه در ناوبری wire:navigate مجدد اجرا می‌شوند — فقط init در سطح ریشه.
 */
(function () {
    'use strict';

    document.querySelectorAll('[data-saniar-blog-slider]').forEach(function (el) {
        if (el.dataset.saniarSliderInit === '1') return;
        el.dataset.saniarSliderInit = '1';

        var slidesCount = el.querySelectorAll('.swiper-slide').length;

        new Swiper(el, {
            spaceBetween: 12,
            slidesPerView: 1.15,
            centeredSlides: false,
            loop: slidesCount > 2,
            autoplay: slidesCount > 2 ? { delay: 4000, disableOnInteraction: false } : false,
            breakpoints: {
                576: { slidesPerView: 2 },
                768: { slidesPerView: 2.4 },
                992: { slidesPerView: 3 },
                1400: { slidesPerView: 4 }
            },
            navigation: {
                nextEl: el.querySelector('.swiper-button-next'),
                prevEl: el.querySelector('.swiper-button-prev')
            }
        });
    });
})();
