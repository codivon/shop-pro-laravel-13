/**
 * سانیار فاز C — جاوااسکریپت صفحه محصول
 * گالری سوایپر | واریانت‌ها (AJAX prices) | فروشندگان (AJAX get-stores) | افزودن به سبد |
 * علاقه‌مندی | اطلاع‌رسانی موجودی | دیدگاه‌ها/پرسش‌ها (AJAX) | فرم دیدگاه + نقاط | نمودار قیمت |
 * اشتراک‌گذاری | مینی‌کارت
 */
(function () {
    'use strict';

    var CSRF = document.querySelector('meta[name="csrf-token"]');
    var BASE_URL = window.location.origin;

    function ajaxHeaders(xhr) {
        if (CSRF) xhr.setRequestHeader('X-CSRF-TOKEN', CSRF.content);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    }

    // اعلان کوچک (toast سبک سانیار)
    function toast(message, type) {
        var el = document.createElement('div');
        el.className = 'saniar-toast alert alert-' + (type === 'error' ? 'danger' : 'success') +
            ' position-fixed shadow-box rounded-3 px-3 py-2 font-14';
        el.style.cssText = 'top:84px;left:18px;z-index:2000;max-width:340px;';
        el.textContent = message;
        document.body.appendChild(el);
        setTimeout(function () { el.remove(); }, 3200);
    }

    /* ============================================================
     * ۱) گالری محصول — مقداردهی در app.js قالب انجام می‌شود
     *    (تک‌نمونه با پیوند thumbs؛ فاز C از تداخل دوباره مقداردهی حذف شد)
     * ============================================================ */

    /* ============================================================
     * ۲) واریانت‌ها — تغییر رنگ/ویژگی → GET /product/{slug}/prices
     * ============================================================ */
    var ajaxInProgress = false;

    function updateColorName(scope) {
        var checked = (scope || document).querySelector('.product-attribute-input:checked');
        if (checked) {
            var nameEl = document.querySelector('.js-color-name');
            if (nameEl) nameEl.textContent = checked.closest('label') ? checked.closest('label').title || '' : '';
        }
    }

    function onVariantChange() {
        var infoBlock = document.querySelector('.product-info-block');
        if (!infoBlock) return;
        if (ajaxInProgress) return;

        var inputs = infoBlock.querySelectorAll('input.product-attribute-input:checked');
        if (!inputs.length) return;

        var slug = inputs[0].dataset.product;
        var groups = [];
        inputs.forEach(function (el) { groups.push(el.value); });

        ajaxInProgress = true;
        infoBlock.classList.add('saniar-loading');

        $.ajax({
            type: 'GET',
            url: BASE_URL + '/product/' + slug + '/prices',
            data: { groups: groups },
            success: function (html) {
                var wrapper = document.createElement('div');
                wrapper.innerHTML = html;
                var fresh = wrapper.querySelector('.product-info-block');

                if (fresh) {
                    infoBlock.replaceWith(fresh);
                    initQtyTouchspin();
                    updateStoresByColor();
                }
            },
            error: function (xhr) {
                console.error('Saniar show: variant prices error', xhr);
                toast('خطا در دریافت اطلاعات واریانت', 'error');
            },
            complete: function () {
                ajaxInProgress = false;
                var blk = document.querySelector('.product-info-block');
                if (blk) blk.classList.remove('saniar-loading');
            }
        });
    }

    // delegate — چون بلاک بعد از هر تغییر ریلود می‌شود
    $(document).on('change', '.product-info-block input.product-attribute-input', function () {
        updateColorName(document);
        onVariantChange();
    });

    /* ============================================================
     * ۳) فروشندگان — POST /product/get-stores بر اساس رنگ فعال
     * ============================================================ */
    var cachedStores = {};

    function updateStoresByColor() {
        if (document.querySelector('#get-stores') === null) return; // چندفروشندگی خاموش

        var checkedInput = document.querySelector('.product-info-block input.product-attribute-input:checked');
        var productInfo = document.getElementById('product-stores-info');
        if (!checkedInput || !productInfo) return;

        var colorId = checkedInput.value;
        var productId = productInfo.dataset.productId ||
            (document.querySelector('.footer-product-id') ? document.querySelector('.footer-product-id').dataset.id : null);

        if (!productId || !colorId) return;

        var cacheKey = productId + '_' + colorId;
        if (cachedStores[cacheKey]) {
            renderStores(cachedStores[cacheKey]);
            return;
        }

        $.ajax({
            type: 'POST',
            url: document.getElementById('get-stores').dataset.action,
            data: { product_id: productId, color_id: colorId },
            beforeSend: ajaxHeaders,
            success: function (response) {
                if (response && typeof response.has_stores !== 'undefined') {
                    cachedStores[cacheKey] = response;
                    renderStores(response);
                }
            },
            error: function (xhr) {
                console.error('Saniar show: get-stores error', xhr);
            }
        });
    }

    function renderStores(response) {
        var storesBody = document.querySelector('.saniar-stores-body');
        var storesBox = document.getElementById('saniar-stores');
        if (!storesBody || !storesBox) return;

        if (response.html && response.has_stores) {
            storesBody.innerHTML = response.html;
            storesBox.classList.remove('d-none');

            // شمارش فروشندگان از ردیف‌های رندرشده
            var rows = storesBody.querySelectorAll('.saniar-store-row').length;
            document.querySelectorAll('.count_stores').forEach(function (el) { el.textContent = rows; });
        } else {
            storesBox.classList.add('d-none');
        }
    }

    /* ============================================================
     * ۴) شمارنده تعداد (بالا/پایین ساده — بدون پلاگین)
     * ============================================================ */
    function initQtyTouchspin() {
        document.querySelectorAll('.js-cart-quantity').forEach(function (input) {
            if (input.dataset.saniarBound) return;
            input.dataset.saniarBound = '1';
            if (typeof jQuery !== 'undefined' && $.fn.TouchSpin) {
                $(input).TouchSpin({
                    min: 1,
                    max: 20,
                    step: 1,
                    boostat: 5,
                    button_down_class: 'btn btn-light border',
                    button_up_class: 'btn btn-light border'
                });
            }
        });
    }

    /* ============================================================
     * ۵) افزودن به سبد — POST cart.store با price_id
     * ============================================================ */
    $(document).on('click', '.js-btn-add-to-cart', function () {
        var btn = this;
        var quantity = 1;
        var qtyInput = document.querySelector('.product-info-block .js-cart-quantity') || document.querySelector('#cart-quantity');
        if (qtyInput && parseInt(qtyInput.value, 10) > 0) quantity = parseInt(qtyInput.value, 10);

        var oldHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

        $.ajax({
            type: 'POST',
            url: $(btn).data('action'),
            data: { quantity: quantity, price_id: $(btn).data('price_id') },
            beforeSend: ajaxHeaders,
            success: function (data) {
                if (data.status === 'success') {
                    toast('محصول با موفقیت به سبد خرید اضافه شد');
                    // جایگزینی کل آفسنواس سبد با رندر تازه
                    if (data.cart) {
                        var wrapper = document.createElement('div');
                        wrapper.innerHTML = data.cart;
                        var freshCanvas = wrapper.querySelector('#offcanvasCart');
                        var oldCanvas = document.getElementById('offcanvasCart');
                        if (freshCanvas && oldCanvas) oldCanvas.replaceWith(freshCanvas);

                        // به‌روزرسانی شمارنده هدر (دسکتاپ + موبایل)
                        var newCount = freshCanvas ? freshCanvas.querySelectorAll('.factor-item').length : 0;
                        document.querySelectorAll('.header-cart-counter').forEach(function (el) { el.textContent = newCount; });

                        // باز کردن سبد
                        if (window.bootstrap && freshCanvas) {
                            var canvasId = document.getElementById('offcanvasCart');
                            if (canvasId) bootstrap.Offcanvas.getOrCreateInstance(canvasId).show();
                        }
                    }
                } else {
                    toast(data.message || 'خطا در افزودن به سبد', 'error');
                }
            },
            error: function () {
                toast('خطا در ارتباط با سرور', 'error');
            },
            complete: function () {
                btn.disabled = false;
                btn.innerHTML = oldHtml;
            }
        });
    });

    /* ============================================================
     * ۶) علاقه‌مندی (toggle) — POST favorites.store
     * ============================================================ */
    $(document).on('click', '#add-to-favorites', function () {
        var btn = this;
        $.ajax({
            type: 'POST',
            url: btn.dataset.action,
            data: { product_id: btn.dataset.product },
            beforeSend: ajaxHeaders,
            success: function (data) {
                var icon = btn.querySelector('i');
                if (data.action === 'create') {
                    icon.className = 'bi bi-heart-fill main-color-one-color';
                    toast('به لیست علاقه‌مندی‌ها اضافه شد');
                } else {
                    icon.className = 'bi bi-heart';
                    toast('از لیست علاقه‌مندی‌ها حذف شد');
                }
            },
            error: function (xhr) {
                if (xhr.status === 401) {
                    window.location.href = BASE_URL + '/login?redirect=' + encodeURIComponent(window.location.href);
                } else {
                    toast('خطا در ثبت علاقه‌مندی', 'error');
                }
            }
        });
    });

    /* ============================================================
     * ۷) اطلاع‌رسانی موجودی — POST /stock-notify
     * ============================================================ */
    $(document).on('click', '#stock_notify_btn', function () {
        var btn = this;
        $.ajax({
            type: 'POST',
            url: BASE_URL + '/stock-notify',
            data: { product_id: btn.dataset.product, user_id: btn.dataset.user },
            beforeSend: ajaxHeaders,
            success: function () {
                toast('اطلاع‌رسانی موجودی برای شما ثبت شد');
                btn.disabled = true;
                btn.innerHTML = '<i class="bi bi-check2 me-1"></i>ثبت شد';
            },
            error: function () {
                toast('خطا در ثبت درخواست', 'error');
            }
        });
    });

    /* ============================================================
     * ۸) دیدگاه‌ها — فیلتر + صفحه‌بندی (AJAX getComments)
     * ============================================================ */
    function loadComments(sort, page) {
        var urlInput = document.querySelector('input[name="UrlGetComment"]');
        if (!urlInput) return;
        var productId = (document.querySelector('input[name="poduct_id"]') || {}).value;

        $.ajax({
            type: 'GET',
            url: urlInput.value,
            data: { page: page || 1, product_id: productId, sortComment: sort },
            success: function (html) {
                var wrapper = document.querySelector('.saniar-comments-wrapper');
                if (wrapper) wrapper.innerHTML = html;
            }
        });
    }

    $(document).on('click', '.js-comment-sort-btn', function () {
        document.querySelectorAll('.js-comment-sort-btn').forEach(function (b) { b.classList.remove('active'); });
        this.classList.add('active');
        loadComments(this.dataset.sort, 1);
    });

    // صفحه‌بندی دیدگاه‌ها (Ajax Partial Rendering)
    $(document).on('click', '.saniar-comments-wrapper .pagination a', function (e) {
        e.preventDefault();
        var sort = (document.querySelector('.js-comment-sort-btn.active') || {}).dataset || {};
        var page = new URL(this.href, BASE_URL).searchParams.get('page') || 1;
        loadComments(sort.sort || 'new', page);
    });

    /* ============================================================
     * ۹) پرسش‌ها — فیلتر + صفحه‌بندی (AJAX getQuestions)
     * ============================================================ */
    function loadQuestions(sort, page) {
        var urlInput = document.querySelector('input[name="UrlGetQuestions"]');
        if (!urlInput) return;
        var productId = (document.querySelector('input[name="poduct_id"]') || {}).value;

        $.ajax({
            type: 'GET',
            url: urlInput.value,
            data: { page: page || 1, product_id: productId, sortComment: sort },
            success: function (html) {
                var wrapper = document.querySelector('.saniar-questions-wrapper');
                if (wrapper) wrapper.innerHTML = html;
            }
        });
    }

    $(document).on('click', '.js-question-sort-btn', function () {
        document.querySelectorAll('.js-question-sort-btn').forEach(function (b) { b.classList.remove('active'); });
        this.classList.add('active');
        loadQuestions(this.dataset.sort, 1);
    });

    $(document).on('click', '.saniar-questions-wrapper .pagination a', function (e) {
        e.preventDefault();
        var sort = (document.querySelector('.js-question-sort-btn.active') || {}).dataset || {};
        var page = new URL(this.href, BASE_URL).searchParams.get('page') || 1;
        loadQuestions(sort.sort || 'new', page);
    });

    /* ============================================================
     * ۱۰) فرم پرسش — POST product.comments
     * ============================================================ */
    $(document).on('submit', '#product-question-form', function (e) {
        e.preventDefault();
        var form = this;
        $.ajax({
            type: 'POST',
            url: form.action,
            data: $(form).serialize(),
            beforeSend: ajaxHeaders,
            success: function () {
                toast('پرسش شما ثبت شد و پس از تایید نمایش داده می‌شود');
                form.reset();
            },
            error: function (xhr) {
                toast((xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'خطا در ثبت پرسش', 'error');
            }
        });
    });

    // پاسخ به پرسش — پر کردن فرم با comment_id
    $(document).on('click', '.js-add-answer-btn', function () {
        var form = document.getElementById('product-question-form');
        if (!form) return;

        var idInput = form.querySelector('input[name="comment_id"]');
        if (!idInput) {
            idInput = document.createElement('input');
            idInput.type = 'hidden';
            idInput.name = 'comment_id';
            form.appendChild(idInput);
        }
        idInput.value = this.dataset.id || '';

        var input = form.querySelector('input[name="body"]');
        if (input) {
            input.placeholder = 'پاسخ خود را به این پرسش بنویسید ...';
            input.focus();
        }
        form.scrollIntoView({ behavior: 'smooth', block: 'center' });
    });

    /* ============================================================
     * ۱۱) فرم دیدگاه — نقاط قوت/ضعف داینامیک + ارسال FormData
     * ============================================================ */
    function bindPointAdd(listSel, inputSel, type) {
        $(document).on('click', listSel + ' .js-icon-form-add', function () {
            var input = document.querySelector(inputSel);
            var parent = document.querySelector(listSel + ' .form-comment-dynamic-labels');
            if (!input || !parent) return;
            if (parent.querySelectorAll('.js-dynamic-point').length >= 5) return;

            var val = input.value.trim();
            if (val.length > 0) {
                var div = document.createElement('div');
                div.className = 'ui-dynamic-label ui-dynamic-label--' + type + ' js-dynamic-point';
                div.appendChild(document.createTextNode(val));

                var removeBtn = document.createElement('button');
                removeBtn.type = 'button';
                removeBtn.className = 'ui-dynamic-label-remove';
                div.appendChild(removeBtn);

                var hidden = document.createElement('input');
                hidden.type = 'hidden';
                hidden.name = 'review[' + (type === 'positive' ? 'advantages' : 'disadvantages') + '][]';
                hidden.value = val;
                div.appendChild(hidden);

                parent.appendChild(div);
                input.value = '';
                input.focus();
            }
        });

        $(document).on('keydown', inputSel, function (event) {
            if (event.which === 13) {
                event.preventDefault();
                $(listSel + ' .js-icon-form-add').trigger('click');
            }
        });
    }

    bindPointAdd('#advantages', '#advantage-input', 'positive');
    bindPointAdd('#disadvantages', '#disadvantage-input', 'negative');

    $(document).on('click', '.js-dynamic-point .ui-dynamic-label-remove', function () {
        this.closest('.js-dynamic-point').remove();
    });

    // متن راهنمای ستاره‌ها
    $(document).on('change', 'input[name="rating"]', function () {
        var label = this.closest('label');
        var textEl = document.getElementById('selected-rating-text');
        if (label && textEl) textEl.textContent = label.dataset.title || '';
    });

    // ارسال فرم دیدگاه
    $(document).on('submit', '#add-product-review-form', function (e) {
        e.preventDefault();
        var form = this;
        var formData = new FormData(form);

        $.ajax({
            url: form.action,
            type: 'POST',
            data: formData,
            beforeSend: ajaxHeaders,
            success: function () {
                toast('نظر شما با موفقیت ثبت شد و پس از تایید مدیر نمایش داده خواهد شد');
                form.reset();
                document.querySelectorAll('.js-dynamic-point').forEach(function (el) { el.remove(); });
                var modalEl = document.getElementById('add-product-review-modal');
                if (modalEl && window.bootstrap) bootstrap.Modal.getInstance(modalEl)?.hide();
            },
            error: function (xhr) {
                var msg = (xhr.responseJSON && (xhr.responseJSON.message || xhr.responseJSON.errors))
                    ? 'لطفا فیلدهای الزامی را کامل کنید' : 'خطا در ثبت دیدگاه';
                toast(msg, 'error');
            },
            cache: false,
            contentType: false,
            processData: false
        });
    });

    /* ============================================================
     * ۱۲) نمودار قیمت — Chart.js از priceChart
     * ============================================================ */
    var chartInstance = null;

    function renderPriceChart(action, title) {
        var titleEl = document.getElementById('selected-chart-price-title');
        if (titleEl) titleEl.textContent = title ? 'واریانت: ' + title : '';

        $.ajax({
            url: action,
            type: 'GET',
            beforeSend: ajaxHeaders,
            success: function (res) {
                // پاسخ priceChart با کلیدهای عددی ۱..۳۰ آبجکت است نه آرایه — به آرایه تبدیل می‌شود
                var data = Array.isArray(res.data) ? res.data : Object.values(res.data || {});
                var hasPrice = data.some(function (d) { return d.price !== null; });
                var emptyEl = document.getElementById('empty-chart');
                var chartEl = document.getElementById('chart');

                if (!hasPrice) {
                    if (emptyEl) emptyEl.style.display = 'block';
                    if (chartEl) chartEl.style.display = 'none';
                    return;
                }
                if (emptyEl) emptyEl.style.display = 'none';
                if (chartEl) chartEl.style.display = 'block';

                // Chart.js به canvas نیاز دارد — داخل #chart ساخته می‌شود
                var canvas = chartEl.querySelector('canvas');
                if (!canvas) {
                    canvas = document.createElement('canvas');
                    canvas.height = 300;
                    chartEl.appendChild(canvas);
                }

                var labels = data.map(function (d) { return d.date; }).reverse();
                var prices = data.map(function (d) { return d.discount_price !== null ? d.discount_price : d.price; }).reverse();

                if (chartInstance) chartInstance.destroy();
                chartInstance = new Chart(canvas, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'قیمت (تومان)',
                            data: prices,
                            borderColor: getComputedStyle(document.documentElement).getPropertyValue('--main-color-one') || '#ef394e',
                            backgroundColor: 'rgba(239,57,78,.08)',
                            fill: true,
                            tension: .3,
                            pointRadius: 2
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: {
                            y: {
                                ticks: {
                                    callback: function (value) {
                                        return Math.round(value / 1000000) + 'M';
                                    }
                                }
                            }
                        }
                    }
                });
            }
        });
    }

    $(document).on('click', '.chart-prices-label label', function () {
        document.querySelectorAll('.chart-prices-label label').forEach(function (l) { l.classList.remove('active', 'btn-dark', 'text-white'); });
        this.classList.add('active', 'btn-dark', 'text-white');
        renderPriceChart(this.dataset.action, this.dataset.title);
    });

    $(document).on('shown.bs.modal', '#chartModal', function () {
        var firstLabel = document.querySelector('.chart-prices-label label');
        if (firstLabel && !document.querySelector('.chart-prices-label label.active')) {
            firstLabel.click();
        }
    });

    /* ============================================================
     * ۱۳) ابزارهای صفحه — کپی لینک، اسکرول، توزیع‌ها، تب از لینک
     * ============================================================ */
    $(document).on('click', '.js-copy-link', function () {
        var input = document.getElementById('shareLink');
        if (input) {
            input.select();
            navigator.clipboard?.writeText(input.value);
            toast('لینک کپی شد');
        }
    });

    $(document).on('click', '.js-scroll-to-buy', function (e) {
        e.preventDefault();
        var box = document.getElementById('saniar-buy-box');
        if (box) box.scrollIntoView({ behavior: 'smooth', block: 'center' });
    });

    // لینک‌های «دیدگاه کاربران / پرسش و پاسخ» → فعال‌سازی تب
    $(document).on('click', '.js-activate-tab', function (e) {
        e.preventDefault();
        var tab = this.dataset.tab;
        var btn = null;
        if (tab === 'comments') btn = document.getElementById('productCommentTab');
        if (tab === 'questions') btn = document.getElementById('productQuestionTab');
        if (btn && window.bootstrap) {
            document.getElementById('prodesc').scrollIntoView({ behavior: 'smooth' });
            bootstrap.Tab.getOrCreateInstance(btn).show();
        }
    });

    /* ============================================================
     * راه‌اندازی اولیه
     * ============================================================ */
    document.addEventListener('DOMContentLoaded', function () {
        initQtyTouchspin();

        // تولتیپ‌های بوت‌استرپ
        document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (el) {
            if (window.bootstrap) new bootstrap.Tooltip(el);
        });
    });
})();
