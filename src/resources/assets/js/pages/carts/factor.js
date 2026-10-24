/* فاکتور چاپی — سانیار: دکمه پرینت (در حالت چاپ با CSS مخفی می‌شود) */
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        var btn = document.getElementById('printPageButton');
        if (btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                window.print();
            });
        }
    });
})();
