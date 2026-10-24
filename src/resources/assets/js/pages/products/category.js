/**
 * سانیار فاز C — جاوااسکریپت صفحه دسته‌بندی محصولات
 * فیلترها (فرم GET) + اسلایدر قیمت (noUiSlider) + مرتب‌سازی + صفحه‌بندی AJAX
 *
 * قرارداد بک‌اند (scopeFrontFilter):
 *   s, in_stock, price_filter, min_price, max_price, filters[id][value], sort_type
 */
(function () {
    'use strict';

    var productsDiv = document.getElementById('category-products-div');
    if (!productsDiv) return;

    var baseUrl = productsDiv.dataset.action || window.location.href.split('?')[0];
    var reloadPending = null;

    // ---------- سریالایز فرم فیلتر ----------
    function formQueryString(extra) {
        var form = document.getElementById('products-filter-form');
        var params = new URLSearchParams();

        if (form) {
            var formData = new FormData(form);
            var seenSort = false;

            formData.forEach(function (value, key) {
                if (key === 'sort_type') { seenSort = true; }
                params.append(key, value);
            });
        }

        // مرتب‌سازی فعال
        var activeSort = productsDiv.querySelector('.js-sort-radio:checked');
        var sortValue = activeSort ? activeSort.value : 'latest';

        if (extra && extra.sort_type !== undefined) {
            sortValue = extra.sort_type;
        }

        if (seenSort) {
            params.set('sort_type', sortValue);
        } else {
            params.append('sort_type', sortValue);
        }

        if (extra && extra.page) {
            params.set('page', extra.page);
        }

        return params.toString();
    }

    // ---------- بارگذاری AJAX لیست ----------
    function reloadProductsList(url, push) {
        if (reloadPending) reloadPending.abort();

        productsDiv.classList.add('saniar-loading');

        reloadPending = $.ajax({
            type: 'GET',
            url: url,
            success: function (data) {
                var parser = new DOMParser();
                var doc = parser.parseFromString(data, 'text/html');
                var fresh = doc.getElementById('category-products-div');

                if (fresh) {
                    productsDiv.innerHTML = fresh.innerHTML;
                }

                if (push !== false) {
                    window.history.pushState({}, '', url);
                }

                $('html, body').animate({
                    scrollTop: $(productsDiv).offset().top - 90
                }, 400);
            },
            error: function (xhr) {
                if (xhr.statusText !== 'abort') {
                    console.error('Saniar category: reload error', xhr);
                }
            },
            complete: function () {
                productsDiv.classList.remove('saniar-loading');
                reloadPending = null;
            }
        });
    }

    function submitFilters(extra) {
        var qs = formQueryString(extra);
        reloadProductsList(baseUrl + '?' + qs);
    }

    // ---------- فرم فیلتر ----------
    $(document).on('submit', '#products-filter-form', function (e) {
        e.preventDefault();
        // در موبایل بعد از اعمال، offcanvas بسته شود
        var offcanvasEl = document.getElementById('saniarFilters');
        if (offcanvasEl && window.bootstrap && offcanvasEl.classList.contains('show')) {
            var inst = bootstrap.Offcanvas.getInstance(offcanvasEl);
            if (inst) inst.hide();
        }
        submitFilters({});
    });

    // تغییر هر چک‌باکس/سوییچ → اعمال خودکار
    $(document).on('change', '#products-filter-form input[type="checkbox"], #products-filter-form input[name="s"]', function () {
        submitFilters({});
    });

    // اینتر در جستجوی فیلتر
    $(document).on('keydown', '#products-filter-form input[name="s"]', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            submitFilters({});
        }
    });

    // ---------- مرتب‌سازی (داخل ناحیه ریلود — event delegation) ----------
    $(document).on('change', '#category-products-div .js-sort-radio', function () {
        $('#category-products-div .form-check.form-check-inline').removeClass('active');
        $(this).closest('.form-check').addClass('active');
        submitFilters({ sort_type: this.value });
    });

    // ---------- صفحه‌بندی ----------
    $(document).on('click', '#category-products-div .pagination a', function (e) {
        e.preventDefault();
        reloadProductsList(this.href);
    });

    // ---------- دکمه back/forward مرورگر ----------
    $(window).on('popstate', function () {
        location.reload();
    });

    // ---------- اسلایدر قیمت (noUiSlider) ----------
    document.querySelectorAll('.price-slider').forEach(function (sliderEl) {
        var min = parseInt(sliderEl.dataset.min || 0, 10);
        var max = parseInt(sliderEl.dataset.max || 0, 10);

        if (!max || max <= min) return; // بدون دامنه معتبر، اسلایدر رندر نمی‌شود

        var fromInput = document.querySelector('.js-slider-range-from');
        var toInput = document.querySelector('.js-slider-range-to');
        var startMin = parseInt(fromInput ? (fromInput.dataset.value || fromInput.value) : min, 10);
        var startMax = parseInt(toInput ? (toInput.dataset.value || toInput.value) : max, 10);

        startMin = Math.max(min, Math.min(startMin, max));
        startMax = Math.max(min, Math.min(startMax, max));

        noUiSlider.create(sliderEl, {
            start: [startMin, startMax],
            connect: true,
            direction: 'rtl',
            range: { min: min, max: max },
            format: {
                to: function (value) { return Math.round(value); },
                from: function (value) { return Math.round(value); }
            },
            tooltips: false
        });

        var updateTimer = null;

        sliderEl.noUiSlider.on('update', function (values) {
            if (fromInput) {
                fromInput.value = values[0];
                fromInput.dataset.value = values[0];
            }
            if (toInput) {
                toInput.value = values[1];
                toInput.dataset.value = values[1];
            }
        });

        // اعمال فیلتر بعد از پایان درگ (با تاخیر کوتاه)
        sliderEl.noUiSlider.on('change', function () {
            clearTimeout(updateTimer);
            updateTimer = setTimeout(function () { submitFilters({}); }, 250);
        });
    });

    // ---------- سوایپر نوار برندها ----------
    if (document.querySelector('.category-brand .swiper')) {
        new Swiper('.category-brand .swiper', {
            slidesPerView: 'auto',
            freeMode: true,
            spaceBetween: 12
        });
    }
})();
