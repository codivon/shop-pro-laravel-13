/* ============================================================
   محصولات فروشنده — سانیار (فاز H)
   سه حالت از data-saniar-products روی تگ اسکریپت:
   - index / find : جدول AJAX (apiIndex/apiIndexFind) + فیلترها + صفحه‌بندی
   - form         : فرم ایجاد/ویرایش محصول (تب‌ها، تنوع‌ها، فایل‌ها،
                    مشخصات، سایز، امتیاز، برچسب، گالری، سابمیت AJAX)
   - variant      : فرم تنوع (افزودن/حذف ردیف + محاسبهٔ قیمت نهایی)
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

    var scriptTag = document.currentScript ||
        (function () { var s = document.getElementsByTagName('script'); return s[s.length - 1]; })();
    var MODE = scriptTag.getAttribute('data-saniar-products');
    var ACTION = scriptTag.getAttribute('data-action') || '';
    var REDIRECT = scriptTag.getAttribute('data-redirect') || '';

    /* ================================================================
       بخش ۱) جدول AJAX — index / find
       ================================================================ */
    function initDatatable() {
        var state = { page: 1, perpage: 10, sortField: 'id', sortDir: 'desc', busy: false };

        var $body = $('.saniar-dt-body');
        var $total = $('.saniar-dt-total b');
        var $pager = $('.saniar-dt-pagination ul');
        if (!$body.length) return;

        function loadingRow() {
            var cols = $body.closest('table').find('thead th').length;
            $body.html('<tr><td colspan="' + cols + '" class="py-4 text-muted font-14"><span class="spinner-border spinner-border-sm ms-2 align-middle" role="status" aria-hidden="true"></span>در حال بارگذاری…</td></tr>');
        }

        function emptyRow() {
            var cols = $body.closest('table').find('thead th').length;
            $body.html('<tr><td colspan="' + cols + '" class="py-5 text-center text-muted font-14"><i class="bi bi-inbox d-block font-24 mb-2"></i>موردی یافت نشد.</td></tr>');
        }

        function buildPayload() {
            var payload = {
                'pagination[page]': state.page,
                'pagination[perpage]': state.perpage,
                'sort[field]': state.sortField,
                'sort[sort]': state.sortDir,
                'query[title]': $('#sfp-filter-title').val() || '',
                'query[stock]': ($('#sfp-filter-stock').val() || 'all'),
                'query[special]': ($('#sfp-filter-special').val() || 'all')
            };
            var parts = [];
            $.each(payload, function (k, v) {
                parts.push(encodeURIComponent(k) + '=' + encodeURIComponent(v));
            });
            $('#sfp-filter-category option:selected').each(function () {
                parts.push(encodeURIComponent('query[category_id][]') + '=' + encodeURIComponent($(this).val()));
            });
            return parts.join('&');
        }

        function statusBadge(status) {
            if (status === 'Accept') return '<span class="badge rounded-pill success-label">تایید شده</span>';
            if (status === 'Waiting') return '<span class="badge rounded-pill warning-label">در انتظار تایید</span>';
            return '<span class="badge rounded-pill danger-label">رد شده</span>';
        }

        function rowActions(row, isIndex) {
            var html = '<a href="' + esc(row.variant) + '" class="btn btn-sm btn-outline-primary rounded-3 font-12 mb-1" title="افزودن/ویرایش تنوع">'
                + '<i class="bi bi-plus-lg"></i> افزودن تنوع</a>';
            if (isIndex && row.links && row.links.edit_seller) {
                html += ' <a href="' + esc(row.links.edit_seller) + '" class="btn btn-sm btn-outline-secondary rounded-3 font-12 mb-1" title="ویرایش محصول">'
                    + '<i class="bi bi-pencil"></i> ویرایش</a>';
            }
            return html;
        }

        function renderRow(row, i, isIndex) {
            var rowNo = (state.page - 1) * state.perpage + i + 1;
            var img = '<img src="' + esc(row.image) + '" alt="' + esc(row.title) + '" loading="lazy" style="width:52px;height:52px;object-fit:cover;" class="rounded-3">';
            var link = (row.links && row.links.front) ? esc(row.links.front) : '#';
            var title = '<a href="' + link + '" target="_blank" class="font-13 fw-bold text-decoration-none main-color-one-color">' + esc(row.title) + '</a>'
                + '<div class="font-12 text-muted mt-1">شناسه: ' + esc(row.id) + '</div>';

            if (isIndex) {
                var brand = row.brand ? esc(row.brand.name) : 'بدون برند';
                return '<tr>'
                    + '<td class="font-13">' + rowNo + '</td>'
                    + '<td><a href="' + link + '" target="_blank">' + img + '</a></td>'
                    + '<td class="text-start">' + title + '</td>'
                    + '<td class="font-13">' + esc(row.category ? row.category.title : '—') + '</td>'
                    + '<td class="font-13">' + brand + '</td>'
                    + '<td>' + statusBadge(row.status) + '</td>'
                    + '<td class="font-13 fw-bold">' + esc(row.variant_count_seller) + '</td>'
                    + '<td class="text-nowrap">' + rowActions(row, true) + '</td>'
                    + '</tr>';
            }

            return '<tr>'
                + '<td class="font-13">' + rowNo + '</td>'
                + '<td><a href="' + link + '" target="_blank">' + img + '</a></td>'
                + '<td class="text-start">' + title + '</td>'
                + '<td class="font-13">' + esc(row.category ? row.category.title : '—') + '</td>'
                + '<td class="font-13 fw-bold">' + esc(row.price_discount && row.price_discount !== '0' ? row.price_discount : row.price) + ' <span class="font-12 text-muted">تومان</span></td>'
                + '<td class="text-nowrap">' + rowActions(row, false) + '</td>'
                + '</tr>';
        }

        function renderPager(pages) {
            $pager.empty();
            if (!pages || pages <= 1) { $total.text(fmtNumber($body.data('rows') || 0)); return; }

            var maxShow = 5;
            var start = Math.max(1, state.page - 2);
            var end = Math.min(pages, start + maxShow - 1);
            if (end - start < maxShow - 1) start = Math.max(1, end - maxShow + 1);

            function btn(label, page, disabled, active, ariaLabel) {
                return '<li class="page-item ' + (disabled ? 'disabled' : '') + ' ' + (active ? 'active' : '') + '">'
                    + '<button type="button" class="page-link" data-page="' + page + '" aria-label="' + esc(ariaLabel || label) + '">' + label + '</button></li>';
            }

            var html = btn('«', Math.max(1, state.page - 1), state.page === 1, false, 'صفحه قبل');
            if (start > 1) html += btn('۱', 1, false, false);
            for (var p = start; p <= end; p++) {
                html += btn(String(p).replace(/\d/g, function (d) { return '۰۱۲۳۴۵۶۷۸۹'[d]; }), p, false, p === state.page);
            }
            if (end < pages) html += btn(String(pages).replace(/\d/g, function (d) { return '۰۱۲۳۴۵۶۷۸۹'[d]; }), pages);
            html += btn('»', Math.min(pages, state.page + 1), state.page === pages, false, 'صفحه بعد');
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
                headers: { 'X-CSRF-TOKEN': csrf(), 'X-Requested-With': 'XMLHttpRequest' },
                dataType: 'json'
            }).done(function (res) {
                var rows = (res && res.data) ? res.data : [];
                var meta = (res && res.meta) ? res.meta : {};
                state.page = meta.page || 1;
                $body.data('rows', rows.length);
                $total.text(fmtNumber(rows.length));
                if (!rows.length) { emptyRow(); renderPager(meta.pages); return; }
                var isIndex = MODE === 'index';
                var html = '';
                $.each(rows, function (i, row) { html += renderRow(row, i, isIndex); });
                $body.html(html);
                renderPager(meta.pages);
            }).fail(function (xhr) {
                if (xhr.status === 401 || (xhr.responseText || '').indexOf('login') !== -1) {
                    window.location.reload();
                    return;
                }
                $body.html('<tr><td colspan="8" class="py-4 text-center text-danger font-14">خطا در دریافت اطلاعات. دوباره تلاش کنید.</td></tr>');
            }).always(function () {
                state.busy = false;
            });
        }

        /* --- رویدادها --- */
        var debounceTimer = null;
        $('#sfp-filter-title').on('input', function () {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(function () { state.page = 1; load(); }, 400);
        });
        $('#sfp-filter-category, #sfp-filter-stock, #sfp-filter-special').on('change', function () {
            state.page = 1; load();
        });
        $('#sfp-perpage').on('change', function () {
            state.perpage = parseInt($(this).val(), 10) || 10;
            state.page = 1; load();
        });
        $pager.on('click', 'button.page-link', function () {
            var p = parseInt($(this).data('page'), 10);
            if (p && p !== state.page) { state.page = p; load(); }
        });
        $('.saniar-dt-sort').on('click', function () {
            var f = $(this).data('field');
            if (!f) return;
            state.sortDir = (state.sortField === f && state.sortDir === 'asc') ? 'desc' : 'asc';
            state.sortField = f;
            state.page = 1;
            load();
        });

        load();
    }

    /* ================================================================
       بخش ۲) ردیف‌های تنوع/قیمت — مشترک بین form و variant
       ================================================================ */
    function calcFinalPrice($row) {
        var price = parseFloat($row.find('.price').val()) || 0;
        var discount = parseFloat($row.find('.discount').val()) || 0;
        var final = price - price * (discount / 100);
        $row.find('.final-price').val(fmtNumber(Math.round(final)) + ' تومان');
    }

    function priceArea() { return $('#product-prices-div'); }

    function addPriceRow() {
        var $area = priceArea();
        var tpl = $('#prices-template').html();
        if (!tpl) return;
        var count = (parseInt($area.attr('data-count'), 10) || 0) + 1;
        $area.attr('data-count', count);
        var $row = $(tpl);
        $row.find('.saniar-price-head .badge').text('تنوع ' + count);
        $row.find('select[name="attributes"]').attr('name', 'prices[' + count + '][attributes][]');
        $row.find('input[name="price"]').attr('name', 'prices[' + count + '][price]');
        $row.find('input[name="discount"]').attr('name', 'prices[' + count + '][discount]');
        $row.find('input[name="stock"]').attr('name', 'prices[' + count + '][stock]');
        $row.find('input[name="cart_min"]').attr('name', 'prices[' + count + '][cart_min]');
        $row.find('input[name="cart_max"]').attr('name', 'prices[' + count + '][cart_max]');
        $row.find('select[name="published"]').attr('name', 'prices[' + count + '][published]');
        $row.find('select[name="attributes_seller"]').attr('name', 'prices[' + count + '][attributes_seller][]');
        $area.append($row);
    }

    function initPriceRows() {
        // محاسبهٔ اولیهٔ قیمت نهایی ردیف‌های سروری
        priceArea().find('.single-price').each(function () { calcFinalPrice($(this)); });
    }

    /* ================================================================
       بخش ۳) فرم ایجاد/ویرایش محصول — mode=form
       ================================================================ */
    function initProductForm() {
        var $form = $('#product-create-form, #product-edit-form').first();
        if (!$form.length) return;

        /* ---------- سوییچ نوع محصول ---------- */
        function applyType(type) {
            var isPhysical = type === 'physical';
            $('.sfp-physical-item').toggleClass('d-none', !isPhysical);
            $('.sfp-download-item').toggleClass('d-none', isPhysical);
            $('#sfp-weight, #sfp-unit, #sfp-nature').each(function () {
                if (isPhysical) { $(this).attr('required', 'required'); } else { $(this).removeAttr('required'); }
            });
            $('.sfp-req').toggleClass('d-none', !isPhysical);
        }
        $('#sfp-type').on('change', function () { applyType($(this).val()); });
        applyType($('#sfp-type').val());

        /* ---------- ردیف‌های تنوع ---------- */
        $(document).on('click', '#sfp-add-price', addPriceRow);
        $(document).on('click', '.remove-product-price', function () {
            $(this).closest('.single-price').fadeOut(180, function () { $(this).remove(); });
        });
        $(document).on('keyup change', '.single-price .price, .single-price .discount', function () {
            calcFinalPrice($(this).closest('.single-price'));
        });
        if (!(parseInt(priceArea().attr('data-count'), 10) > 0)) {
            addPriceRow(); // حداقل یک ردیف برای محصول فیزیکی
        }
        initPriceRows();

        /* ---------- ردیف‌های فایل دانلودی ---------- */
        function addFileRow() {
            var $area = $('#product-files-area');
            var tpl = $('#files-template').html();
            if (!tpl) return;
            var count = (parseInt($area.attr('data-count'), 10) || 0) + 1;
            $area.attr('data-count', count);
            var $row = $(tpl);
            $row.find('.saniar-price-head .badge').text('فایل ' + count);
            $row.find('input[name="title"]').attr('name', 'download_files[' + count + '][title]');
            $row.find('input[name="file"]').attr('name', 'download_files[' + count + '][file]');
            $row.find('input[name="price"]').attr('name', 'download_files[' + count + '][price]');
            $row.find('input[name="discount"]').attr('name', 'download_files[' + count + '][discount]');
            $row.find('select[name="status"]').attr('name', 'download_files[' + count + '][status]');
            $area.append($row);
        }
        $(document).on('click', '#sfp-add-file', addFileRow);
        $(document).on('click', '.remove-file', function () {
            $(this).closest('.single-file').fadeOut(180, function () { $(this).remove(); });
        });
        if (!(parseInt($('#product-files-area').attr('data-count'), 10) > 0)) {
            addFileRow(); // حداقل یک ردیف برای محصول دانلودی
        }

        /* ---------- برچسب‌ها (chips ساده) ---------- */
        (function initLabels() {
            var $input = $('#sfp-labels-input');
            var $value = $('#sfp-labels-value');
            var $chips = $('#sfp-labels-chips');
            if (!$input.length) return;

            function current() {
                return $.map(($value.val() || '').split(','), function (v) { return v.trim(); })
                    .filter(function (v) { return v.length; });
            }
            function render() {
                $chips.empty();
                $.each(current(), function (i, label) {
                    $('<span class="saniar-chip" role="button" tabindex="0" title="حذف برچسب"></span>')
                        .text(label)
                        .append('<i class="bi bi-x-lg"></i>')
                        .on('click keydown', function (e) {
                            if (e.type === 'keydown' && e.key !== 'Enter' && e.key !== ' ') return;
                            var list = current();
                            list.splice(i, 1);
                            $value.val(list.join(','));
                            render();
                        })
                        .appendTo($chips);
                });
            }
            $input.on('keydown', function (e) {
                if (e.key === 'Enter' || e.key === ',') {
                    e.preventDefault();
                    var v = $input.val().trim().replace(/,+$/, '');
                    if (!v) return;
                    var list = current();
                    if (list.indexOf(v) === -1) { list.push(v); $value.val(list.join(',')); }
                    $input.val('');
                    render();
                } else if (e.key === 'Backspace' && !$input.val()) {
                    var list2 = current();
                    list2.pop();
                    $value.val(list2.join(','));
                    render();
                }
            });
            render();
        })();

        /* ---------- ساخت خودکار slug ---------- */
        $('#sfp-generate-slug').on('click', function () {
            var src = ($('#sfp-title').val() || '').trim() || ($('#sfp-meta-title').val() || '').trim();
            if (!src) { toast('ابتدا عنوان محصول را وارد کنید.', 'warning'); return; }
            var slug = src
                .replace(/[\s\u200c]+/g, '-')
                .replace(/[^\p{L}\p{N}-]+/gu, '')
                .replace(/-{2,}/g, '-')
                .replace(/^-+|-+$/g, '');
            $('#sfp-slug').val(slug);
            toast('آدرس ساخته شد؛ در صورت تکراری بودن تغییرش دهید.');
        });

        /* ---------- گروه‌ها و ردیف‌های مشخصات ---------- */
        var $specArea = $('#specifications-area');

        function specCounts() {
            return {
                g: parseInt($specArea.attr('data-group-count'), 10) || 0,
                s: parseInt($specArea.attr('data-spec-count'), 10) || 0
            };
        }
        function setSpecCounts(c) {
            $specArea.attr('data-group-count', c.g);
            $specArea.attr('data-spec-count', c.s);
        }

        function addSpecGroup(groupName, specs) {
            var tpl = $('#specification-group').html();
            if (!tpl) return null;
            var c = specCounts();
            c.g += 1;
            setSpecCounts(c);
            var $group = $(tpl);
            $group.attr('data-group', c.g);
            $group.find('.saniar-price-head .badge, .row .badge').first().text('گروه ' + c.g);
            $group.find('input[name="specification_group"]')
                .attr('name', 'specification_group[' + c.g + '][name]')
                .attr('data-group-name', c.g);
            if (groupName) $group.find('input[name="specification_group[' + c.g + '][name]"]').val(groupName);
            $specArea.append($group);
            if (specs && specs.length) {
                $.each(specs, function (i, specName) {
                    addSpecRow($group, specName);
                });
            }
            return $group;
        }

        function addSpecRow($group, specName) {
            var tpl = $('#specification-single').html();
            if (!tpl) return;
            var c = specCounts();
            c.s += 1;
            setSpecCounts(c);
            var g = $group.attr('data-group');
            var $row = $(tpl);
            $row.find('input[name="special_specification"]')
                .attr('name', 'specification_group[' + g + '][specifications][' + c.s + '][special]');
            $row.find('input[name="specification_name"]')
                .attr('name', 'specification_group[' + g + '][specifications][' + c.s + '][name]');
            $row.find('textarea[name="specification_value"]')
                .attr('name', 'specification_group[' + g + '][specifications][' + c.s + '][value]');
            if (specName) $row.find('input[name$="[name]"]').val(specName);
            $group.find('.all-specifications').append($row);
        }

        $(document).on('click', '#sfp-add-spec-group', function () { addSpecGroup(); });
        $(document).on('click', '.add-specifaction', function () {
            addSpecRow($(this).closest('.specification-group'));
        });
        $(document).on('click', '.remove-specification', function () {
            $(this).closest('.single-specificition').fadeOut(160, function () { $(this).remove(); });
        });
        $(document).on('click', '.remove-group', function () {
            $(this).closest('.specification-group').fadeOut(160, function () { $(this).remove(); });
        });

        /* ---------- بارگذاری گروه‌های نوع مشخصات ---------- */
        function loadSpecTypeData() {
            var name = $('#sfp-spec-type').val().trim();
            if (!name) { toast('نوع مشخصات را وارد کنید.', 'warning'); return; }
            var $btn = $('#sfp-load-spec-type');
            var url = $btn.data('action');
            $btn.prop('disabled', true);
            $.getJSON(url, { name: name }).done(function (res) {
                $specArea.empty();
                setSpecCounts({ g: 0, s: 0 });
                var groups = res.groups || [];
                if (!groups.length) {
                    toast('برای این نوع مشخصات گروهی تعریف نشده است.', 'warning');
                    return;
                }
                $.each(groups, function (i, g) {
                    addSpecGroup(g.name, g.specifications || []);
                });
                toast('گروه‌های آماده بارگذاری شد.');
            }).fail(function (xhr) {
                if (xhr.status === 404) toast('این نوع مشخصات یافت نشد.', 'error');
                else toast('خطا در بارگذاری نوع مشخصات.', 'error');
            }).always(function () {
                $btn.prop('disabled', false);
            });
        }

        $('#sfp-load-spec-type').on('click', function () {
            var c = specCounts();
            if (c.g > 0 || $specArea.children().length) {
                var modalEl = document.getElementById('specifications-modal');
                if (modalEl) {
                    window.bootstrap.Modal.getOrCreateInstance(modalEl).show();
                    return;
                }
            }
            loadSpecTypeData();
        });
        $(document).on('click', '#add-spec-type-data', loadSpecTypeData);
        $('#sfp-spec-type').on('keydown', function (e) { if (e.key === 'Enter') e.preventDefault(); });

        /* ---------- سایزبندی ---------- */
        var $sizesArea = $('#sizes-area');

        function addSizeRow(sizeDefs, groupIndex, clearValues) {
            var $row = $('<div class="saniar-size-row single-value row g-2 align-items-center"></div>');
            var $del = $('<div class="col-auto"></div>')
                .append('<button type="button" class="btn btn-sm btn-outline-danger rounded-3 remove-value" title="حذف ردیف"><i class="bi bi-trash"></i></button>');
            var $cols = $('<div class="col"><div class="row g-2"></div></div>');
            $.each(sizeDefs, function (i, s) {
                $cols.find('.row').append(
                    '<div class="col-md-3 col-6">'
                    + '<label class="form-label font-12 mb-1">' + esc(s.title) + '</label>'
                    + '<input type="text" class="form-control" data-size-id="' + s.id + '" name="sizes[' + groupIndex + '][' + s.id + ']">'
                    + '</div>'
                );
            });
            $row.append($del).append($cols);
            $sizesArea.append($row);
        }

        $('#size_type_id').on('change', function () {
            var val = $(this).val();
            $sizesArea.empty();
            if (!val) {
                $('#sfp-add-value-wrap').addClass('d-none');
                $sizesArea.attr('data-count', 0);
                return;
            }
            var url = ($(this).data('values-url') || '').replace('SIZETYPE_ID', val);
            var $sel = $(this);
            $sel.prop('disabled', true);
            $.getJSON(url).done(function (sizes) {
                if (!sizes || !sizes.length) {
                    $('#sfp-add-value-wrap').addClass('d-none');
                    toast('برای این راهنمای سایز مقداری تعریف نشده است.', 'warning');
                    return;
                }
                $sizesArea.attr('data-count', 1);
                addSizeRow(sizes, 1, true);
                $('#sfp-add-value-wrap').removeClass('d-none');
            }).fail(function () {
                toast('خطا در دریافت سایزها.', 'error');
            }).always(function () { $sel.prop('disabled', false); });
        });

        $(document).on('click', '.add-value', function () {
            var $first = $sizesArea.find('.single-value').first();
            if (!$first.length) return;
            var count = (parseInt($sizesArea.attr('data-count'), 10) || 0) + 1;
            $sizesArea.attr('data-count', count);
            var $clone = $first.clone();
            $clone.find('input').val('');
            $clone.find('input').each(function () {
                var sid = $(this).data('size-id');
                $(this).attr('name', 'sizes[' + count + '][' + sid + ']');
            });
            $sizesArea.append($clone);
        });
        $(document).on('click', '.remove-value', function () {
            $(this).closest('.single-value').fadeOut(160, function () { $(this).remove(); });
        });

        /* ---------- ردیف‌های امتیاز ویژگی ---------- */
        function addAbilityRow() {
            var $area = $('#AbilityProductScore-area');
            var tpl = $('#AbilityProductScore-group').html();
            if (!tpl) return;
            var count = (parseInt($area.attr('data-count'), 10) || 0) + 1;
            $area.attr('data-count', count);
            var $row = $(tpl);
            $row.find('input[name="AbilityProductScore"]').attr('name', 'AbilityProductScore[' + count + '][name]');
            $row.find('input[name="AbilityProductScore_value"]').attr('name', 'AbilityProductScore[' + count + '][value]');
            $area.append($row);
        }
        $(document).on('click', '#sfp-add-ability', addAbilityRow);
        $(document).on('click', '.remove-ability', function () {
            $(this).closest('.saniar-ability-row').fadeOut(160, function () { $(this).remove(); });
        });

        /* ---------- گالری تصاویر (آپلود فوری + ترتیب + حذف) ---------- */
        (function initGallery() {
            var $grid = $('#sfp-gallery-grid');
            var $hidden = $('#sfp-images-input');
            var $fileInput = $('#sfp-gallery-input');
            if (!$grid.length) return;

            function syncImages() {
                var names = [];
                $grid.find('.saniar-gallery-tile').each(function () {
                    names.push($(this).attr('data-name'));
                });
                $hidden.val(names.join(','));
            }

            function tileHtml(name, src) {
                return '<div class="saniar-gallery-tile" data-name="' + esc(name) + '">'
                    + '<img src="' + esc(src) + '" alt="تصویر گالری محصول">'
                    + '<div class="saniar-gallery-tile-actions">'
                    + '<button type="button" class="btn tile-move" data-dir="up" title="جابه‌جایی به قبل" aria-label="جابه‌جایی به قبل"><i class="bi bi-arrow-right"></i></button>'
                    + '<button type="button" class="btn tile-remove" title="حذف تصویر" aria-label="حذف تصویر"><i class="bi bi-trash"></i></button>'
                    + '<button type="button" class="btn tile-move" data-dir="down" title="جابه‌جایی به بعد" aria-label="جابه‌جایی به بعد"><i class="bi bi-arrow-left"></i></button>'
                    + '</div></div>';
            }

            $fileInput.on('change', function () {
                var files = this.files;
                if (!files || !files.length) return;
                var pending = files.length;
                $.each(files, function (i, file) {
                    if (!file.type.match(/^image\//)) { pending--; return; }
                    var fd = new FormData();
                    fd.append('file', file);
                    $.ajax({
                        url: $grid.data('store-url'),
                        type: 'POST',
                        data: fd,
                        headers: { 'X-CSRF-TOKEN': csrf(), 'X-Requested-With': 'XMLHttpRequest' },
                        processData: false,
                        contentType: false
                    }).done(function (res) {
                        if (!res || !res.imagename) { toast('خطا در آپلود تصویر.', 'error'); return; }
                        var reader = new FileReader();
                        reader.onload = function (e) {
                            $grid.append(tileHtml(res.imagename, e.target.result));
                            syncImages();
                        };
                        reader.readAsDataURL(file);
                    }).fail(function (xhr) {
                        var msg = 'خطا در آپلود تصویر.';
                        try {
                            var errs = (xhr.responseJSON && xhr.responseJSON.errors) || {};
                            var first = Object.keys(errs)[0];
                            if (first) msg = errs[first][0];
                        } catch (e) {}
                        toast(msg, 'error');
                    }).always(function () {
                        pending--;
                        if (pending <= 0) $fileInput.val('');
                    });
                });
            });

            $grid.on('click', '.tile-remove', function () {
                var $tile = $(this).closest('.saniar-gallery-tile');
                var name = $tile.attr('data-name');
                // تصاویر موجودِ دیتابیس مسیر کامل دارند و فقط از لیست حذف می‌شوند؛
                // فایل‌های تازه‌آپلودشده (نام موقت) از دیسک هم پاک می‌شوند.
                if (name && name.indexOf('/uploads/') === -1) {
                    $.post($grid.data('delete-url'), { filename: name, _token: csrf() });
                }
                $tile.fadeOut(160, function () { $(this).remove(); syncImages(); });
            });

            $grid.on('click', '.tile-move', function () {
                var dir = $(this).data('dir');
                var $tile = $(this).closest('.saniar-gallery-tile');
                if (dir === 'up') {
                    var $prev = $tile.prev();
                    if ($prev.length) { $tile.insertBefore($prev); syncImages(); }
                } else {
                    var $next = $tile.next();
                    if ($next.length) { $tile.insertAfter($next); syncImages(); }
                }
            });

            syncImages();
        })();

        /* ---------- اعتبارسنجی و سابمیت ---------- */
        function activateTab(selector) {
            var $link = $('.saniar-form-tab-link[data-bs-target="' + selector + '"]');
            if ($link.length && window.bootstrap) {
                window.bootstrap.Tab.getOrCreateInstance($link[0]).show();
            }
        }

        function validateForm() {
            if (!$('#sfp-title').val().trim()) {
                activateTab('#sfp-general');
                toast('عنوان محصول الزامی است.', 'error');
                $('#sfp-title').trigger('focus');
                return false;
            }
            if (!$('#sfp-category').val()) {
                activateTab('#sfp-general');
                toast('دسته‌بندی اصلی را انتخاب کنید.', 'error');
                $('#sfp-category').trigger('focus');
                return false;
            }
            var type = $('#sfp-type').val();
            if (type === 'physical') {
                if (!$('#sfp-weight').val()) {
                    activateTab('#sfp-general');
                    toast('وزن برای محصول فیزیکی الزامی است.', 'error');
                    $('#sfp-weight').trigger('focus');
                    return false;
                }
                var $validRows = priceArea().find('.single-price').filter(function () {
                    var p = $(this).find('input[name$="[price]"]').val();
                    var s = $(this).find('input[name$="[stock]"]').val();
                    return p && s;
                });
                if (!$validRows.length) {
                    activateTab('#sfp-prices');
                    toast('حداقل یک تنوع با قیمت و موجودی وارد کنید.', 'error');
                    return false;
                }
            } else {
                var $fileRows = $('#product-files-area').find('.single-file').filter(function () {
                    return $(this).find('input[name$="[title]"]').val() && $(this).find('input[name$="[price]"]').val();
                });
                if (!$fileRows.length) {
                    activateTab('#sfp-files');
                    toast('برای محصول دانلودی حداقل یک فایل با عنوان و قیمت لازم است.', 'error');
                    return false;
                }
            }
            return true;
        }

        $form.on('submit', function (e) {
            e.preventDefault();
            if ($form.data('busy')) return;
            if (!validateForm()) return;

            $form.data('busy', true);
            var $btn = $('#sfp-submit-btn');
            $btn.prop('disabled', true);
            $btn.find('.saniar-btn-spinner').removeClass('d-none');

            var fd = new FormData(this);

            $.ajax({
                url: $form.attr('action'),
                type: 'POST',
                data: fd,
                headers: { 'X-CSRF-TOKEN': csrf(), 'X-Requested-With': 'XMLHttpRequest' },
                processData: false,
                contentType: false
            }).done(function (res, textStatus, xhr) {
                if (xhr.status === 200) {
                    toast('محصول با موفقیت ذخیره شد.');
                    setTimeout(function () {
                        window.location.href = REDIRECT || '/seller/products';
                    }, 650);
                }
            }).fail(function (xhr) {
                $form.data('busy', false);
                $btn.prop('disabled', false);
                $btn.find('.saniar-btn-spinner').addClass('d-none');
                if (xhr.status === 422) {
                    var msg = 'اطلاعات فرم ناقص است.';
                    try {
                        var errs = (xhr.responseJSON && xhr.responseJSON.errors) || {};
                        var first = Object.keys(errs)[0];
                        if (first) msg = errs[first][0];
                    } catch (err) {}
                    toast(msg, 'error');
                    return;
                }
                if (xhr.status === 419) {
                    toast('نشست شما منقضی شده است؛ صفحه را رفرش کنید.', 'error');
                    return;
                }
                toast('خطا در ذخیرهٔ محصول. دوباره تلاش کنید.', 'error');
            });
        });
    }

    /* ================================================================
       بخش ۴) فرم تنوع — mode=variant (سابمیت عادی سروری)
       ================================================================ */
    function initVariantForm() {
        var $form = $('#product-variant-form');
        if (!$form.length) return;

        $(document).on('click', '#sfp-add-price', addPriceRow);
        $(document).on('click', '.remove-product-price', function () {
            $(this).closest('.single-price').fadeOut(180, function () { $(this).remove(); });
        });
        $(document).on('keyup change', '.single-price .price, .single-price .discount', function () {
            calcFinalPrice($(this).closest('.single-price'));
        });

        if (!(parseInt(priceArea().attr('data-count'), 10) > 0)) {
            addPriceRow();
        }
        initPriceRows();

        // چک سمت کلاینت: حداقل یک ردیف معتبر بماند (سفارش نهایی دست سرور است)
        $form.on('submit', function () {
            var $validRows = priceArea().find('.single-price');
            if (!$validRows.length) {
                toast('حداقل یک تنوع اضافه کنید یا از این صفحه خارج شوید.', 'error');
                return false;
            }
            var $btn = $form.find('button[type="submit"]');
            $btn.prop('disabled', true);
            return true;
        });
    }

    /* ================= راه‌اندازی ================= */
    $(function () {
        if (MODE === 'index' || MODE === 'find') initDatatable();
        else if (MODE === 'form') initProductForm();
        else if (MODE === 'variant') initVariantForm();
    });
})();
