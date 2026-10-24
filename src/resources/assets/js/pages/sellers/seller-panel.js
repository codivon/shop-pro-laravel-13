/* ============================================================
   پنل فروشنده — سانیار (فاز G)
   - نمودار بازدید ۷ روز اخیر فروشگاه (chartjs، داده از data-attrs)
   ============================================================ */
(function () {
    'use strict';

    var $ = window.jQuery;

    /* ================= نمودار بازدید ================= */
    function initVisitsChart() {
        var canvas = document.getElementById('seller-visits-chart');
        if (!canvas || typeof window.Chart === 'undefined') return;

        var labels = [];
        var values = [];
        try {
            labels = JSON.parse(canvas.getAttribute('data-labels') || '[]');
            values = JSON.parse(canvas.getAttribute('data-values') || '[]');
        } catch (e) {
            labels = [];
            values = [];
        }

        // شبیه‌سازی آرایه ۷روزه با صفر برای روزهای بدون بازدید
        while (values.length < 7) {
            values.unshift(0);
            labels.unshift('');
        }

        var css = getComputedStyle(document.documentElement);
        var mainColor = css.getPropertyValue('--main-color-one').trim() || '#275fe0';
        var mainShadow = css.getPropertyValue('--main-color-one-shadow').trim() || 'rgba(64,123,255,.2)';

        new window.Chart(canvas, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'بازدید',
                    data: values,
                    borderColor: mainColor,
                    backgroundColor: mainShadow,
                    fill: true,
                    tension: 0.35,
                    pointBackgroundColor: mainColor,
                    pointRadius: 4,
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, ticks: { precision: 0 } },
                    x: { grid: { display: false } }
                }
            }
        });
    }

    $(function () {
        initVisitsChart();
    });
})();
