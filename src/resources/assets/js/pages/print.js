/* چاپ خودکار صفحات فاکتور/برچسب سانیار — بدون inline JS
   body[data-saniar-print] → auto-print پس از بارگذاری تصاویر
   [data-print-btn] → دکمه چاپ دستی */
(function () {
    'use strict';

    function triggerPrint() {
        setTimeout(function () { window.print(); }, 500);
    }

    document.addEventListener('DOMContentLoaded', function () {
        var btn = document.querySelector('[data-print-btn]');
        if (btn) {
            btn.addEventListener('click', function () { window.print(); });
        }

        if (document.body.hasAttribute('data-saniar-print')) {
            var imgs = document.images;
            var pending = 0;
            for (var i = 0; i < imgs.length; i++) {
                if (!imgs[i].complete) {
                    pending++;
                    imgs[i].addEventListener('load', function () {
                        pending--;
                        if (pending <= 0) triggerPrint();
                    }, { once: true });
                    imgs[i].addEventListener('error', function () {
                        pending--;
                        if (pending <= 0) triggerPrint();
                    }, { once: true });
                }
            }
            if (pending === 0) triggerPrint();
        }
    });
})();
