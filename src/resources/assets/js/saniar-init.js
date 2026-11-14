/**
 * SaniarTheme — منطق‌های سراسری تکمیلی تمپلیت
 * (پاپ‌آپ صفحه اصلی + حالت پیش‌نمایش ویجت‌ساز پنل)
 * بدون هیچ کد JS داخل فایل‌های بلید.
 */
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {

        // ---------- اسلایدر «اسلایدر و دو بنر کناری» (فاز ۱۶۳) ----------
        // مقداردهی مستقل برای هر نمونه با گره‌های خودِ همان اسلایدر تا اگر چند
        // اسلایدر در صفحه باشند، Pagination/Navigation ها با هم قاطی نشوند.
        if (window.Swiper) {
            document.querySelectorAll('.saniar-side-slider').forEach(function (el) {
                if (el.dataset.saniarSlider === '1') { return; }
                el.dataset.saniarSlider = '1';

                try {
                    new Swiper(el, {
                        spaceBetween: 30,
                        centeredSlides: true,
                        loop: el.querySelectorAll('.swiper-slide').length > 1,
                        autoplay: {
                            delay: 4500,
                            disableOnInteraction: false,
                        },
                        effect: 'fade',
                        fadeEffect: { crossFade: true },
                        pagination: {
                            el: el.querySelector('.swiper-pagination'),
                            clickable: true,
                        },
                        navigation: {
                            nextEl: el.querySelector('.swiper-button-next'),
                            prevEl: el.querySelector('.swiper-button-prev'),
                        },
                    });
                } catch (e) { /* اسلایدر نباید بقیهٔ صفحه را بشکند */ }
            });
        }

        // ---------- پاپ‌آپ صفحه اصلی ----------
        var imagePopup = document.getElementById('image-popup-modal');
        var textPopup = document.getElementById('text-popup-modal');

        if (imagePopup && window.bootstrap && bootstrap.Modal) {
            bootstrap.Modal.getOrCreateInstance(imagePopup).show();
        }
        if (textPopup && window.bootstrap && bootstrap.Modal) {
            bootstrap.Modal.getOrCreateInstance(textPopup).show();
        }

        // ---------- حالت پیش‌نمایش ویجت‌ساز پنل (?ap-preview=1) ----------
        if (window.location.search.indexOf('ap-preview') === -1) {
            return;
        }

        var style = document.createElement('style');
        style.textContent =
            '[data-ap-outline]{animation:apWidgetPulse 1.6s ease-in-out infinite}' +
            '@keyframes apWidgetPulse{0%,100%{outline-color:rgba(245,158,11,.95);outline-offset:4px}' +
            '50%{outline-color:rgba(245,158,11,.35);outline-offset:9px}}' +
            '.ap-widget-guide{outline:1px dashed rgba(148,163,184,.8)!important;outline-offset:2px}';
        document.head.appendChild(style);

        function clearHighlight() {
            document.querySelectorAll('[data-ap-outline]').forEach(function (node) {
                node.style.outline = '';
                node.removeAttribute('data-ap-outline');
            });
        }

        function highlightWidget(id) {
            var el = document.getElementById('widget-' + id);
            if (!el) { return; }
            clearHighlight();
            el.setAttribute('data-ap-outline', '1');
            el.style.outline = '2px dashed rgba(245, 158, 11, .95)';
            el.style.outlineOffset = '4px';
            el.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }

        function setGuides(on) {
            document.querySelectorAll('[id^="widget-"]').forEach(function (node) {
                node.classList.toggle('ap-widget-guide', !!on);
            });
        }

        window.addEventListener('message', function (event) {
            var data = event.data || {};
            if (data.apWidgetId) { highlightWidget(data.apWidgetId); }
            if (typeof data.apShowGuides !== 'undefined') { setGuides(data.apShowGuides); }
        });
    });
})();
