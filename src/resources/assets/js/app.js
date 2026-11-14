/* =========================================================================
   app.js — مقداردهی اسلایدرهای Swiper تمپلیت سانیار
   فاز ۱۶۳ — همهٔ مقداردهی‌ها «اسکوپ‌شده روی خود نمونه» شدند:
   قبلاً همهٔ اسلایدرها با سلکتور سراسری (.swiper-button-next / .swiper-pagination)
   مقداردهی می‌شدند؛ در نتیجه هر دکمهٔ ناوبری، «همهٔ» اسلایدرهای صفحه را
   جلو می‌برد و با وجود چند اسلایدر در صفحهٔ اصلی، رفتارها قاطی می‌شد.
   حالا هر نمونه فقط گره‌های داخل خودش را کنترل می‌کند.
   ========================================================================= */

/* ساخت نمونهٔ Swiper با گره‌های اسکوپ‌شدهٔ خودِ المنت */
function saniarSwiper(el, config) {
    if (!el || !window.Swiper) { return null; }
    config = config || {};
    config.pagination = config.pagination || {};
    config.navigation = config.navigation || {};
    if (config.pagination.el === undefined) { config.pagination.el = el.querySelector('.swiper-pagination'); }
    if (config.navigation.nextEl === undefined) { config.navigation.nextEl = el.querySelector('.swiper-button-next'); }
    if (config.navigation.prevEl === undefined) { config.navigation.prevEl = el.querySelector('.swiper-button-prev'); }
    if (config.pagination.el) { config.pagination.clickable = true; }
    return new Swiper(el, config);
}

/* ========= اسلایدر اصلی صفحهٔ اصلی (js-home-slider — فاز ۱۶۴ کلاس شد تا چند
   نمونهٔ main-slider با id یکتا روی یک صفحه مقداردهی شوند) ========= */
document.querySelectorAll('.js-home-slider').forEach(function (el) {
    if (el.dataset.swiperBound === '1') { return; }
    el.dataset.swiperBound = '1';
    saniarSwiper(el, {
        spaceBetween: 30,
        centeredSlides: true,
        loop: el.querySelectorAll('.swiper-slide').length > 1,
        autoplay: {
            delay: 3000,
            disableOnInteraction: false,
        },
        effect: "fade",
    });
});

/* ============== پیشنهاد لحظه‌ای (js-moment-slider — فاز ۱۶۴ کلاس شد) ============== */
document.querySelectorAll('.js-moment-slider').forEach(function (el) {
    if (el.dataset.swiperBound === '1') { return; }
    el.dataset.swiperBound = '1';
    var progressBar = el.querySelector('.swiper-progress-bar');
    saniarSwiper(el, {
        slidesPerView: 1,
        spaceBetween: 30,
        loop: el.querySelectorAll('.swiper-slide').length > 1,
        autoplay: {
            delay: 3000,
            disableOnInteraction: false,
        },
        speed: 500,
        on: {
            init: function () {
                if (!progressBar) { return; }
                progressBar.classList.remove('animate', 'active');
                progressBar.classList.add('animate', 'active');
            },
            slideChangeTransitionStart: function () {
                if (!progressBar) { return; }
                progressBar.classList.remove('animate', 'active');
                progressBar.classList.add('active');
            },
            slideChangeTransitionEnd: function () {
                if (progressBar) { progressBar.classList.add('animate'); }
            }
        }
    });
});

/* ============== اسلایدر شگفت انگیز (امازینگ) ============== */
/* جفت اصلی/فهرست کنار هم در یک ریشهٔ .amazing-slider مقداردهی می‌شوند */
document.querySelectorAll('.amazing-slider').forEach(function (root) {
    if (root.dataset.swiperBound === '1') { return; }
    root.dataset.swiperBound = '1';

    var linkEl = root.querySelector('.amazing-slider-link');
    var sliderEl = root.querySelector('.amazing-slider-slider');
    if (!linkEl || !sliderEl) { return; }

    var thumbs = saniarSwiper(linkEl, {
        spaceBetween: 10,
        slidesPerView: 4,
        freeMode: true,
        watchSlidesProgress: true,
        allowTouchMove: false,
    });

    var mainConfig = {
        effect: "fade",
        speed: 1000,
        loop: sliderEl.querySelectorAll('.swiper-slide').length > 1,
        autoplay: {
            delay: 5000,
            disableOnInteraction: false,
        },
        spaceBetween: 10,
    };

    /* اتصال thumbs — فقط از طریق کانفیگ (API درست Swiper) */
    if (thumbs) { mainConfig.thumbs = { swiper: thumbs }; }

    saniarSwiper(sliderEl, mainConfig);
});

/* ========= اسلایدر ردیف محصولات (چند نمونه در صفحه) ========= */
document.querySelectorAll('.product-slider-swiper').forEach(function (el) {
    if (el.dataset.swiperBound === '1') { return; }
    el.dataset.swiperBound = '1';
    saniarSwiper(el, {
        slidesPerView: 5,
        spaceBetween: 10,
        breakpoints: {
            100:  { slidesPerView: 1, spaceBetween: 20 },
            576:  { slidesPerView: 2, spaceBetween: 20 },
            768:  { slidesPerView: 2, spaceBetween: 20 },
            1024: { slidesPerView: 3, spaceBetween: 20 },
            1200: { slidesPerView: 4, spaceBetween: 20 },
            1400: { slidesPerView: 5, spaceBetween: 20 },
        },
    });
});

/* ============ گالری تصاویر صفحهٔ محصول ============ */
document.querySelectorAll('.product-gallery-thumb').forEach(function (el) {
    if (el.dataset.swiperBound === '1') { return; }
    el.dataset.swiperBound = '1';
    saniarSwiper(el, {
        spaceBetween: 10,
        slidesPerView: 4,
        freeMode: true,
        watchSlidesProgress: true,
        breakpoints: {
            320: { slidesPerView: 3, spaceBetween: 10 },
            400: { slidesPerView: 4, spaceBetween: 10 },
        },
    });
});

document.querySelectorAll('.product-gallery').forEach(function (el) {
    if (el.dataset.swiperBound === '1') { return; }
    el.dataset.swiperBound = '1';

    var thumbEl = document.querySelector('.product-gallery-thumb');
    var thumbsInstance = thumbEl && thumbEl.swiper ? thumbEl.swiper : null;

    var galleryConfig = {
        spaceBetween: 10,
        zoom: {
            maxRatio: 3,
            minRation: 1
        },
    };

    if (thumbsInstance) { galleryConfig.thumbs = { swiper: thumbsInstance }; }

    saniarSwiper(el, galleryConfig);
});

/* ========= اسلایدر افقی دسته‌بندی‌ها (free-mode) ========= */
document.querySelectorAll('.free-mode').forEach(function (el) {
    if (el.dataset.swiperBound === '1') { return; }
    el.dataset.swiperBound = '1';
    saniarSwiper(el, {
        slidesPerView: "auto",
        spaceBetween: 10,
        freeMode: true,
    });
});
