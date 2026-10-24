/* ============================================================
   آمار و بازدید فروشنده — سانیار (فاز J)
   - دو نمودار میله‌ای chartjs: تعداد بازدیدها / بازدیدکنندگان یکتا
   - بازه‌ها: روزانه/هفتگی/ماهانه/سالانه + فیلتر تاریخ شمسی اختیاری
   - پیکربندی از data-attrs روی تگ اسکریپت:
       data-views-action  → GET seller.statistics.viewCounts
       data-viewers-action → GET seller.statistics.viewerCounts
     پاسخ JSON: {status:'success', data:{1:{chart_category,total},...}, meta:{total:'...',avg:'...'}}
   بدون پلاگین جدید — chart.js باید «قبل از» این فایل لود شود
   ============================================================ */
(function () {
    'use strict';

    var $ = window.jQuery;
    if (!$) return;
    if (typeof window.Chart === 'undefined') return;

    var scriptTag = document.currentScript ||
        (function () { var s = document.getElementsByTagName('script'); return s[s.length - 1]; })();
    var VIEWS_ACTION = scriptTag.getAttribute('data-views-action') || '';
    var VIEWERS_ACTION = scriptTag.getAttribute('data-viewers-action') || '';

    var state = {
        tab: 'views',          // 'views' | 'viewers'
        period: 'monthly',     // daily | weekly | monthly | yearly
        charts: {}             // views / viewers → Chart instance
    };

    /* ================= ابزارهای مشترک (همسان seller-orders.js) ================= */

    function toast(message, type) {
        var $t = $('#saniar-toast');
        if (!$t.length) {
            $t = $('<div id="saniar-toast" class="saniar-toast"></div>').appendTo('body');
        }
        $t.text(message)
            .removeClass('saniar-toast-error saniar-toast-success saniar-toast-warning')
            .addClass(type === 'error' ? 'saniar-toast-error' : (type === 'warning' ? 'saniar-toast-warning' : 'saniar-toast-success'))
            .addClass('saniar-toast-show');
        if ($t.data('timer')) clearTimeout($t.data('timer'));
        $t.data('timer', setTimeout(function () { $t.removeClass('saniar-toast-show'); }, 3600));
    }

    function csrf() {
        return $('meta[name="csrf-token"]').attr('content');
    }

    // meta.total/meta.avg ممکن است عدد خام یا رشتهٔ قالب‌بندی‌شده (مثل «1.2 هزار») باشد
    function fmtMeta(value) {
        if (value === null || value === undefined || value === '') return '—';
        if (typeof value === 'number') return value.toLocaleString('en-US');
        var str = String(value);
        if (/^\d+(\.\d+)?$/.test(str)) return Number(str).toLocaleString('en-US');
        return str;
    }

    /* ================= ساخت نمودار (الگوی seller-panel.js) ================= */

    function makeChart(canvasId, color, label) {
        var canvas = document.getElementById(canvasId);
        if (!canvas) return null;

        return new window.Chart(canvas, {
            type: 'bar',
            data: {
                labels: [],
                datasets: [{
                    label: label,
                    data: [],
                    backgroundColor: color,
                    borderRadius: 6,
                    maxBarThickness: 46
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

    /* ================= دریافت داده و به‌روزرسانی نمودار ================= */

    function activeAction() {
        return state.tab === 'viewers' ? VIEWERS_ACTION : VIEWS_ACTION;
    }

    function fetchChart() {
        var action = activeAction();
        if (!action) return;

        var payload = { period: state.period };
        var fromDate = $('#sfs-' + state.tab + '-from').val();
        var toDate = $('#sfs-' + state.tab + '-to').val();
        if ($.trim(fromDate)) payload.from_date = $.trim(fromDate);
        if ($.trim(toDate)) payload.to_date = $.trim(toDate);

        $.ajax({
            url: action,
            type: 'GET',
            data: payload,
            dataType: 'json',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrf() }
        }).done(function (res) {
            if (!res || res.status !== 'success') {
                toast('خطا در دریافت آمار', 'error');
                return;
            }

            var items = res.data || {};
            var labels = [];
            var values = [];
            $.each(items, function (key, item) {
                labels.push(item.chart_category);
                values.push(Number(item.total || 0));
            });

            var chart = state.charts[state.tab];
            if (chart) {
                chart.data.labels = labels;
                chart.data.datasets[0].data = values;
                chart.update();
            }

            var meta = res.meta || {};
            $('#sfs-' + state.tab + '-total').text(fmtMeta(meta.total));
            $('#sfs-' + state.tab + '-avg').text(fmtMeta(meta.avg));
        }).fail(function () {
            toast('خطا در دریافت آمار', 'error');
        });
    }

    /* ================= رویدادها ================= */

    function initPeriodPills() {
        $('.sfs-period-pill').on('click', function () {
            var period = $(this).data('period');
            if (!period || period === state.period) return;
            state.period = String(period);

            // همگام‌سازی حالت فعال در نوار هر دو تب
            $('.sfs-period-pill').removeClass('active main-color-one-bg text-white').addClass('btn-outline-secondary');
            $('.sfs-period-pill[data-period="' + period + '"]')
                .addClass('active main-color-one-bg text-white')
                .removeClass('btn-outline-secondary');

            fetchChart();
        });
    }

    function initApplyButtons() {
        $('.sfs-apply').on('click', function () {
            // همگام‌سازی تاریخ‌ها بین دو تب تا وضعیت فیلتر یکدست بماند
            var fromDate = $('#sfs-' + state.tab + '-from').val();
            var toDate = $('#sfs-' + state.tab + '-to').val();
            $('#sfs-views-from, #sfs-viewers-from').val(fromDate);
            $('#sfs-views-to, #sfs-viewers-to').val(toDate);
            fetchChart();
        });
    }

    /* ================= تب‌های هدر (تعداد بازدیدها / بازدیدکنندگان یکتا) ================= */

    // کلاس سفارشی فعال (main-color-one-bg) را بین دو دکمهٔ هدر جابه‌جا می‌کند؛
    // Bootstrap فقط .active را جابه‌جا می‌کند و از کلاس‌های سفارشی بی‌خبر است.
    function syncHeaderPills() {
        var $viewsBtn = $('#sfs-views-tab-btn');
        var $viewersBtn = $('#sfs-viewers-tab-btn');
        if (state.tab === 'viewers') {
            $viewsBtn.removeClass('main-color-one-bg text-white').addClass('btn-outline-secondary');
            $viewersBtn.addClass('main-color-one-bg text-white').removeClass('btn-outline-secondary');
        } else {
            $viewersBtn.removeClass('main-color-one-bg text-white').addClass('btn-outline-secondary');
            $viewsBtn.addClass('main-color-one-bg text-white').removeClass('btn-outline-secondary');
        }
    }

    function initTabs() {
        $('button[data-bs-toggle="pill"]').on('shown.bs.tab', function (e) {
            var target = $(e.target).attr('data-bs-target');
            if (target === '#sfs-tab-views') state.tab = 'views';
            else if (target === '#sfs-tab-viewers') state.tab = 'viewers';
            else return;

            syncHeaderPills();
            var chart = state.charts[state.tab];
            if (chart) chart.resize(); // نمودار داخل تب مخفی ابعاد صفر می‌گیرد؛ بعد از نمایش resize
            fetchChart();
        });
    }

    $(function () {
        state.charts.views = makeChart('sfs-views-canvas', '#275fe0', 'تعداد بازدیدها');
        state.charts.viewers = makeChart('sfs-viewers-canvas', '#189D0E', 'بازدیدکنندگان یکتا');

        initPeriodPills();
        initApplyButtons();
        initTabs();

        fetchChart();
    });
})();
