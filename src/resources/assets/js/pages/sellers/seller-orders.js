/* ============================================================
   سفارش‌های فروشنده — سانیار (فاز I)
   دو حالت از data-saniar-orders روی تگ اسکریپت:
   - index : جدول AJAX (apiIndex) + فیلترها + صفحه‌بندی + انتخاب گروهی
             + چاپ گروهی (فاکتور/برچسب/لیبل) + تغییر وضعیت گروهی + خروجی اکسل
   - show  : تغییر وضعیت ارسال + مودال لغو با بازگشت وجه + کد رهگیری
   بدون پلاگین جدید — jQuery + Bootstrap5 موجود قالب
   ============================================================ */
(function () {
    'use strict';

    var $ = window.jQuery;
    if (typeof $ === 'undefined') return;

    /* ================= ابزارهای مشترک ================= */

    function esc(str) {
        return String(str == null ? '' : str)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#039;');
    }

    function fmtNumber(num) {
        num = Number(num || 0);
        return num.toLocaleString('en-US');
    }

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

    function ajaxError(xhr, fallback) {
        var msg = fallback || 'خطای ناشناخته';
        if (xhr && xhr.responseJSON) {
            if (xhr.responseJSON.message) msg = xhr.responseJSON.message;
            else if (xhr.responseJSON.errors) {
                var first = Object.keys(xhr.responseJSON.errors)[0];
                if (first) msg = xhr.responseJSON.errors[first][0];
            }
        }
        toast(msg, 'error');
    }

    var scriptTag = document.currentScript ||
        (function () { var s = document.getElementsByTagName('script'); return s[s.length - 1]; })();
    var MODE = scriptTag.getAttribute('data-saniar-orders');
    var ACTION = scriptTag.getAttribute('data-action') || '';

    /* برچسب‌های وضعیت — همسان با طراحی سانیار */
    var PAY_STATUS = {
        paid: { label: 'پرداخت شده', cls: 'success-label' },
        unpaid: { label: 'پرداخت نشده', cls: 'warning-label' },
        canceled: { label: 'لغو شده', cls: 'danger-label' }
    };
    var SHIP_STATUS = {
        'w-pending': { label: 'در انتظار بررسی', cls: 'warning-label' },
        'pending': { label: 'در حال بررسی', cls: 'warning-label' },
        'processing': { label: 'در حال پردازش', cls: 'warning-label' },
        'waiting': { label: 'منتظر ارسال', cls: 'warning-label' },
        'sent': { label: 'ارسال شد', cls: 'success-label' },
        'post-sent': { label: 'تحویل به پست', cls: 'success-label' },
        'delivered': { label: 'تحویل داده شد', cls: 'success-label' },
        'canceled': { label: 'لغو شده', cls: 'danger-label' }
    };

    function badge(map, key) {
        var cfg = map[key] || { label: key || '—', cls: 'secondary-label' };
        return '<span class="badge rounded-pill ' + cfg.cls + '">' + esc(cfg.label) + '</span>';
    }

    /* ================================================================
       بخش ۱) حالت index — دیتاتیبل AJAX
       ================================================================ */
    function initIndex() {
        var state = { page: 1, perpage: 10, sortField: 'id', sortDir: 'desc', busy: false, selected: [] };

        var $body = $('.saniar-dt-body');
        var $total = $('.saniar-dt-total b');
        var $pager = $('.saniar-dt-pagination ul');
        var $bulkBar = $('#sfo-bulk-bar');
        var $bulkCount = $('#sfo-selected-count');
        if (!$body.length) return;

        function loadingRow() {
            var cols = $body.closest('table').find('thead th').length;
            $body.html('<tr><td colspan="' + cols + '" class="py-4 text-muted font-14"><span class="spinner-border spinner-border-sm ms-2 align-middle" role="status" aria-hidden="true"></span>در حال بارگذاری…</td></tr>');
        }

        function emptyRow() {
            var cols = $body.closest('table').find('thead th').length;
            $body.html('<tr><td colspan="' + cols + '" class="py-5 text-center text-muted font-14"><i class="bi bi-inbox d-block font-24 mb-2"></i>سفارشی یافت نشد.</td></tr>');
        }

        function buildPayload() {
            var parts = [
                'pagination[page]=' + state.page,
                'pagination[perpage]=' + state.perpage,
                'sort[field]=' + state.sortField,
                'sort[sort]=' + state.sortDir,
                'query[fullname]=' + encodeURIComponent($('#sfo-fullname').val() || ''),
                'query[username]=' + encodeURIComponent($('#sfo-username').val() || ''),
                'query[id]=' + encodeURIComponent($('#sfo-id').val() || ''),
                'query[status]=' + encodeURIComponent($('#sfo-status').val() || 'all'),
                'query[shipping_status]=' + encodeURIComponent($('#sfo-shipping').val() || 'all'),
                'query[product_name]=' + encodeURIComponent($('#sfo-product-name').val() || ''),
                'query[product_id]=' + encodeURIComponent($('#sfo-product-id').val() || ''),
                'query[from_date]=' + encodeURIComponent($('#sfo-from-date').val() || ''),
                'query[to_date]=' + encodeURIComponent($('#sfo-to-date').val() || '')
            ];
            return parts.join('&');
        }

        function renderRow(row, i) {
            var rowNo = (state.page - 1) * state.perpage + i + 1;
            var checked = state.selected.indexOf(String(row.id)) !== -1 ? ' checked' : '';
            var refundedTag = row.refunded
                ? ' <span class="badge rounded-pill danger-label font-11">برگشت وجه</span>' : '';

            return '<tr data-id="' + esc(row.id) + '">'
                + '<td><input class="form-check-input sfo-row-check" type="checkbox" value="' + esc(row.id) + '" aria-label="انتخاب سفارش ' + esc(row.id) + '"' + checked + '></td>'
                + '<td class="font-13">' + rowNo + '</td>'
                + '<td class="font-13 fw-bold">#' + esc(row.id)
                + '<div class="font-12 text-muted mt-1">' + esc(row.items_count) + ' قلم / ' + esc(row.quantity) + ' عدد</div></td>'
                + '<td class="text-start">'
                + '<span class="font-13 fw-bold">' + esc(row.name) + '</span>'
                + '<div class="font-12 text-muted mt-1" dir="ltr">' + esc(row.mobile) + '</div>'
                + (row.username ? '<div class="font-12 text-muted">@' + esc(row.username) + '</div>' : '')
                + '</td>'
                + '<td class="font-13 text-nowrap">' + esc(row.created_at) + '</td>'
                + '<td class="font-13 fw-bold text-nowrap">' + fmtNumber(row.price_seller) + ' <span class="font-11 text-muted">تومان</span></td>'
                + '<td>' + badge(PAY_STATUS, row.status) + '</td>'
                + '<td>' + badge(SHIP_STATUS, row.shipping_status) + refundedTag + '</td>'
                + '<td class="text-nowrap">'
                + '<a href="' + esc(row.show_url) + '" class="btn btn-sm btn-outline-primary rounded-3 font-12 mb-1" title="جزئیات سفارش"><i class="bi bi-eye"></i> مشاهده</a> '
                + '<a href="' + esc(row.print_url) + '" target="_blank" class="btn btn-sm btn-outline-secondary rounded-3 font-12 mb-1" title="چاپ فاکتور"><i class="bi bi-printer"></i></a>'
                + '</td>'
                + '</tr>';
        }

        function syncBulkBar() {
            var n = state.selected.length;
            $bulkCount.text(fmtNumber(n));
            if (n > 0) { $bulkBar.removeAttr('hidden'); }
            else { $bulkBar.attr('hidden', 'hidden'); }
            var allChecked = $body.find('.sfo-row-check').length > 0
                && $body.find('.sfo-row-check:not(:checked)').length === 0;
            $('.sfo-check-all').prop('checked', allChecked);
        }

        function renderPager(pages) {
            $pager.empty();
            if (!pages || pages <= 1) return;

            var maxShow = 5;
            var start = Math.max(1, state.page - 2);
            var end = Math.min(pages, start + maxShow - 1);
            if (end - start < maxShow - 1) start = Math.max(1, end - maxShow + 1);

            function btn(label, page, disabled, active, ariaLabel) {
                return '<li class="page-item ' + (disabled ? 'disabled' : '') + ' ' + (active ? 'active' : '') + '">'
                    + '<button type="button" class="page-link font-13" data-page="' + page + '"'
                    + (ariaLabel ? ' aria-label="' + ariaLabel + '"' : '') + ' ' + (disabled ? 'disabled' : '') + '>'
                    + label + '</button></li>';
            }

            var html = btn('«', state.page - 1, state.page <= 1, false, 'صفحه قبل');
            for (var p = start; p <= end; p++) {
                html += btn(fmtNumber(p), p, false, p == state.page, 'صفحه ' + p);
            }
            html += btn('»', state.page + 1, state.page >= pages, false, 'صفحه بعد');
            $pager.html(html);
        }

        function load() {
            if (state.busy) return;
            state.busy = true;
            loadingRow();
            $.ajax({
                url: ACTION,
                type: 'POST',
                data: buildPayload(),
                headers: { 'X-CSRF-TOKEN': csrf() },
                dataType: 'json'
            }).done(function (res) {
                var rows = (res.data || []);
                if (!rows.length) { emptyRow(); }
                else {
                    var html = '';
                    for (var i = 0; i < rows.length; i++) html += renderRow(rows[i], i);
                    $body.html(html);
                }
                $total.text(fmtNumber(res.meta ? res.meta.total : rows.length));
                renderPager(res.meta ? res.meta.pages : 1);
                syncBulkBar();
            }).fail(function (xhr) {
                if (xhr.status === 419) {
                    toast('نشست شما منقضی شده؛ صفحه را نوسازی کنید.', 'warning');
                } else {
                    ajaxError(xhr, 'خطا در دریافت سفارش‌ها');
                }
                emptyRow();
            }).always(function () {
                state.busy = false;
            });
        }

        /* --- رویدادها --- */

        // اعمال فیلتر با دبونس روی ورودی‌ها
        var debounceTimer = null;
        $('#sfo-filter-form').on('input change', 'input, select', function () {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(function () {
                state.page = 1;
                load();
            }, 420);
        });
        $('#sfo-apply-btn').on('click', function () { state.page = 1; load(); });
        $('#sfo-reset-btn').on('click', function () {
            $('#sfo-filter-form').find('input:not([type=hidden])').val('');
            $('#sfo-filter-form').find('select').val('all');
            state.page = 1;
            load();
        });

        // مرتب‌سازی
        $('.saniar-dt-sort').on('click', function () {
            var field = $(this).data('field') || 'id';
            if (state.sortField === field) {
                state.sortDir = state.sortDir === 'asc' ? 'desc' : 'asc';
            } else {
                state.sortField = field;
                state.sortDir = 'desc';
            }
            load();
        });

        // صفحه‌بندی
        $pager.on('click', '.page-link', function () {
            var p = parseInt($(this).data('page'), 10);
            if (p && p !== state.page) { state.page = p; load(); }
        });

        // تعداد در صفحه
        $('#sfo-perpage').on('change', function () {
            state.perpage = parseInt($(this).val(), 10) || 10;
            state.page = 1;
            load();
        });

        // انتخاب ردیف
        $body.on('change', '.sfo-row-check', function () {
            var id = String($(this).val());
            if (this.checked) {
                if (state.selected.indexOf(id) === -1) state.selected.push(id);
            } else {
                state.selected = state.selected.filter(function (v) { return v !== id; });
            }
            syncBulkBar();
        });

        // انتخاب همه
        $('.sfo-check-all').on('change', function () {
            var checked = this.checked;
            var $checks = $body.find('.sfo-row-check');
            $checks.each(function () {
                this.checked = checked;
                var id = String($(this).val());
                if (checked) {
                    if (state.selected.indexOf(id) === -1) state.selected.push(id);
                } else {
                    state.selected = state.selected.filter(function (v) { return v !== id; });
                }
            });
            syncBulkBar();
        });

        // لغو انتخاب
        $('#sfo-clear-selection').on('click', function () {
            state.selected = [];
            $body.find('.sfo-row-check').prop('checked', false);
            syncBulkBar();
        });

        // چاپ گروهی — فاکتور/برچسب/لیبل با ids[] در URL جدید
        $('[data-sfo-print]').on('click', function () {
            if (!state.selected.length) { toast('حداقل یک سفارش انتخاب کنید.', 'warning'); return; }
            var base = $(this).data('sfo-print');
            var qs = [];
            for (var i = 0; i < state.selected.length; i++) {
                qs.push('ids[]=' + encodeURIComponent(state.selected[i]));
            }
            window.open(base + '?' + qs.join('&'), '_blank');
        });

        // تغییر وضعیت گروهی
        $('#sfo-bulk-status-form').on('submit', function () {
            if (!state.selected.length) { toast('حداقل یک سفارش انتخاب کنید.', 'warning'); return; }
            var $btn = $('#sfo-bulk-status-submit').prop('disabled', true);
            var parts = [];
            for (var i = 0; i < state.selected.length; i++) {
                parts.push('ids[]=' + encodeURIComponent(state.selected[i]));
            }
            parts.push('status=' + encodeURIComponent($('#sfo-bulk-status').val()));
            $.ajax({
                url: $(this).data('action'),
                type: 'POST',
                data: parts.join('&'),
                headers: { 'X-CSRF-TOKEN': csrf() }
            }).done(function () {
                toast('وضعیت سفارش‌های انتخاب‌شده تغییر کرد.', 'success');
                $('#sfo-bulk-status-modal').modal('hide');
                state.selected = [];
                load();
            }).fail(function (xhr) {
                ajaxError(xhr, 'خطا در تغییر وضعیت گروهی');
            }).always(function () {
                $btn.prop('disabled', false);
            });
        });

        // خروجی اکسل — فیلترهای جاری به‌صورت hidden داخل فرم
        $('#sfo-export-modal').on('show.bs.modal', function () {
            var $q = $('#sfo-export-query').empty();
            var fields = ['fullname', 'username', 'id', 'status', 'shipping_status', 'product_name', 'product_id', 'from_date', 'to_date'];
            for (var i = 0; i < fields.length; i++) {
                var val = $('#sfo-filter-form').find('[name="' + fields[i] + '"]').val() || '';
                if (val && val !== 'all') {
                    $q.append('<input type="hidden" name="query[' + fields[i] + ']" value="' + esc(val) + '">');
                }
            }
        });

        load();
    }

    /* ================================================================
       بخش ۲) حالت show — وضعیت ارسال + لغو + کد رهگیری
       ================================================================ */
    function initShow() {
        var $status = $('#sfo-shipping-status');
        var $saveBtn = $('#sfo-save-status');
        if (!$status.length) return;

        function postStatus(extra, doneMsg, $btn, restore) {
            var data = { shipping_status: $status.val() };
            if (extra) { $.extend(data, extra); }

            $.ajax({
                url: $saveBtn.data('action'),
                type: 'POST',
                data: data,
                headers: { 'X-CSRF-TOKEN': csrf() },
                dataType: 'json'
            }).done(function () {
                toast(doneMsg, 'success');
                setTimeout(function () { window.location.reload(); }, 900);
            }).fail(function (xhr) {
                ajaxError(xhr, 'خطا در تغییر وضعیت');
                if ($btn && restore) { $btn.prop('disabled', false).html(restore); }
            });
        }

        $saveBtn.on('click', function () {
            if ($status.val() === 'canceled') {
                $('#sfo-cancel-modal').modal('show');
                return;
            }
            var $btn = $(this).prop('disabled', true);
            var restore = $btn.html();
            $btn.html('<span class="spinner-border spinner-border-sm ms-1"></span>در حال ذخیره…');
            postStatus(null, 'وضعیت با موفقیت تغییر کرد.', $btn, restore);
        });

        $('#sfo-confirm-cancel').on('click', function () {
            var $btn = $(this).prop('disabled', true);
            var restore = $btn.html();
            $btn.html('<span class="spinner-border spinner-border-sm ms-1"></span>در حال لغو…');
            postStatus({
                cancel_reason: $('#sfo-cancel-reason').val() || '',
                canceled_refund_amount: $('#sfo-cancel-refund').is(':checked') ? 1 : 0
            }, 'سفارش با موفقیت لغو شد.', $btn, restore);
        });

        var $trackingBtn = $('#sfo-save-tracking');
        $trackingBtn.on('click', function () {
            var code = $('#sfo-tracking-code').val();
            if (!code) { toast('لطفاً کد رهگیری را وارد کنید.', 'warning'); return; }
            var $btn = $(this).prop('disabled', true);
            $.ajax({
                url: $btn.data('action'),
                type: 'POST',
                data: { tracking_code: code },
                headers: { 'X-CSRF-TOKEN': csrf() },
                dataType: 'json'
            }).done(function () {
                toast('کد رهگیری ثبت شد.', 'success');
                $btn.prop('disabled', false);
            }).fail(function (xhr) {
                ajaxError(xhr, 'خطا در ثبت کد رهگیری');
                $btn.prop('disabled', false);
            });
        });
    }

    $(function () {
        if (MODE === 'index') initIndex();
        if (MODE === 'show') initShow();
    });
})();
