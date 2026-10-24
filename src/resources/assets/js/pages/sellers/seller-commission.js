/* جستجوی زندهٔ جدول کمیسیون — سانیار فاز I (بدون inline JS)
   فیلتر ردیف‌های .sfc-category-row بر اساس data-name */
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        var input = document.getElementById('sfc-search');
        var table = document.getElementById('sfc-commission-table');
        if (!input || !table) return;

        input.addEventListener('input', function () {
            var q = this.value.trim().toLowerCase();
            var rows = table.querySelectorAll('.sfc-category-row');
            for (var i = 0; i < rows.length; i++) {
                var name = rows[i].getAttribute('data-name') || '';
                rows[i].style.display = (!q || name.indexOf(q) !== -1) ? '' : 'none';
            }
        });
    });
})();
