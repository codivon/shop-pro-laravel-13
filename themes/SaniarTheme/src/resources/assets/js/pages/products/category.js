/**
 * SaniarTheme — صفحه محصولات دسته‌بندی
 * فیلترها + مرتب‌سازی + نوار قیمت noUiSlider + بارگذاری ایجکس لیست محصولات
 */
(function ($) {
    'use strict';

    // ---------- ارسال فرم فیلتر ----------
    $(document).on('submit', '.products-filter-form', function (e) {
        e.preventDefault();

        var formData = new FormData(this);

        var sortType = $('.category-sort input[name="sort_type"]:checked').val();
        if (sortType) {
            formData.append('sort_type', sortType);
        }

        var queryString = new URLSearchParams(formData).toString();

        reloadProductsList(
            $('#category-products-div').data('action') + '?' + queryString
        );
    });

    // ---------- ارسال خودکار با تغییر ورودی‌های فیلتر ----------
    $(document).on('change', '.products-filter-form input', function () {
        $(this).closest('form').trigger('submit');
    });

    // ---------- مرتب‌سازی ----------
    $(document).on('change', '.category-sort input[name="sort_type"]', function () {
        var listWrapper = $('#category-products-div');
        if (!listWrapper.length) {
            return;
        }

        if ($('.products-filter-form').length) {
            // فرم فیلتر خودش sort_type فعلی را اضافه می‌کند
            $('.products-filter-form').trigger('submit');
        } else {
            reloadProductsList(listWrapper.data('action') + '?sort_type=' + $(this).val());
        }
    });

    // ---------- سازگاری با تب‌های مرتب‌سازی قدیمی (صفحه فروشنده) ----------
    $(document).on('click', '.products-list-sort-type .ah-tab-item[data-sort]', function () {
        if ($(this).attr('data-ah-tab-active') === 'true') {
            return false;
        }
        $('.products-list-sort-type .ah-tab-item').removeAttr('data-ah-tab-active');
        $(this).attr('data-ah-tab-active', 'true');

        var listWrapper = $('#category-products-div');
        if (!listWrapper.length) {
            return false;
        }

        if ($('.products-filter-form').length) {
            $('.products-filter-form').trigger('submit');
        } else {
            reloadProductsList(listWrapper.data('action') + '?sort_type=' + $(this).data('sort'));
        }
        return false;
    });

    function reloadProductsList(url) {
        $.ajax({
            type: 'GET',
            url: url,
            success: function (data) {
                var content = $(data).find('#category-products-div').html();

                $('#category-products-div').html(content);

                window.history.pushState({}, '', url);

                $('html, body').animate(
                    {
                        scrollTop: $('#category-products-div').offset().top - 20
                    },
                    1000
                );
            },
            beforeSend: function (xhr) {
                block('#category-products-div');
            },
            complete: function () {
                unblock('#category-products-div');
            }
        });
    }

    // ---------- صفحه‌بندی ایجکس ----------
    $(document).on('click', '#category-products-div .pagination a', function (e) {
        e.preventDefault();
        reloadProductsList($(this).attr('href'));
    });

    $(window).on('popstate', function () {
        location.reload();
    });

    // ---------- انتخاب ویژگی → فعال شدن «فقط کالاهای موجود» ----------
    $(document).on('change', '.attribute_group_input', function () {
        if ($(this).prop('checked')) {
            $('.in_stock').prop('checked', true);
        }
    });

    // ---------- نوار بازه قیمت (noUiSlider سانیار) ----------
    $(document).ready(function () {
        document.querySelectorAll('.js-price-slider').forEach(function (box) {
            if (typeof noUiSlider === 'undefined') {
                return;
            }

            var slider = box.querySelector('.price-slider');
            if (!slider || slider.noUiSlider) {
                return;
            }

            var minPrice = parseInt(box.getAttribute('data-min'), 10) || 0;
            var maxPrice = parseInt(box.getAttribute('data-max'), 10) || 0;
            var selectedMin = parseInt(box.getAttribute('data-min-selected'), 10) || minPrice;
            var selectedMax = parseInt(box.getAttribute('data-max-selected'), 10) || maxPrice;

            if (selectedMin < minPrice) { selectedMin = minPrice; }
            if (selectedMax > maxPrice || selectedMax < selectedMin) { selectedMax = maxPrice; }

            var faFormat = new Intl.NumberFormat('fa-IR');

            noUiSlider.create(slider, {
                start: [selectedMin, selectedMax],
                connect: true,
                direction: 'rtl',
                step: 1000,
                format: {
                    to: function (value) {
                        return Math.round(value);
                    },
                    from: function (value) {
                        return Number(value);
                    }
                },
                range: {
                    min: minPrice,
                    max: maxPrice
                }
            });

            var minHidden = box.parentElement.querySelector('input[name="min_price"]');
            var maxHidden = box.parentElement.querySelector('input[name="max_price"]');
            var fromInput = box.querySelector('.min-price-filter');
            var toInput = box.querySelector('.max-price-filter');

            slider.noUiSlider.on('update', function (values) {
                if (fromInput) { fromInput.value = faFormat.format(values[0]); }
                if (toInput) { toInput.value = faFormat.format(values[1]); }
                if (minHidden) { minHidden.value = Math.round(values[0]); }
                if (maxHidden) { maxHidden.value = Math.round(values[1]); }
            });

            slider.noUiSlider.on('change', function () {
                // هم‌رفتار با نسخه قبلی: تغییر قیمت، فیلترهای مرتبط را فعال می‌کند
                $('.in_stock').prop('checked', true);
                $('.price_filter').prop('checked', true);
                $(box).closest('form').trigger('submit');
            });
        });

        // ---------- سازگاری با اسلایدر قدیمی (صفحه فروشنده) ----------
        var legacySlider = document.getElementById('slider-non-linear-step');
        if (legacySlider && typeof noUiSlider !== 'undefined' && !legacySlider.noUiSlider) {
            var legacyMin = parseInt(window.products_min_price, 10) || 0;
            var legacyMax = parseInt(window.products_max_price, 10) || 0;
            var legacySelMin = parseInt(window.selected_min_price, 10) || legacyMin;
            var legacySelMax = parseInt(window.selected_max_price, 10) || legacyMax;

            noUiSlider.create(legacySlider, {
                start: [legacySelMin, legacySelMax],
                connect: true,
                direction: 'rtl',
                format: window.wNumb ? wNumb({decimals: 0, thousand: ','}) : undefined,
                range: {min: legacyMin, max: legacyMax}
            });

            var legacyValueEl = document.getElementById('slider-non-linear-step-value');
            var legacyMinInput = document.querySelector('input[name="min_price"]');
            var legacyMaxInput = document.querySelector('input[name="max_price"]');

            legacySlider.noUiSlider.on('update', function (values) {
                var raw = values.map(function (v) {
                    return String(v).replace(/,/g, '');
                });
                if (legacyMinInput) { legacyMinInput.value = raw[0]; }
                if (legacyMaxInput) { legacyMaxInput.value = raw[1]; }
                if (legacyValueEl) { legacyValueEl.innerHTML = values.join(' - '); }
            });

            legacySlider.noUiSlider.on('change', function () {
                $('.in_stock').prop('checked', true);
                $('.price_filter').prop('checked', true);
            });
        }
    });
})(jQuery);
