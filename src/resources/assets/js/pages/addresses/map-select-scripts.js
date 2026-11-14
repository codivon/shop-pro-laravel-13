/* ============================================================
   map-select-scripts.js — فاز ۱۶۱ (SaniarTheme)
   مودال ثبت/ویرایش آدرس + نقشهٔ انتخاب موقعیت (Map.ir)

   رفع سه باگ گزارش کاربر (تک‌صفحهٔ پرداخت + پنل مشتری):
   ۱) «برای لود شدن نقشه باید دوبار کلیک شود» — شنوندهٔ show.bs.modal
      قبلاً داخل $('.openMap').click() وصل می‌شد؛ رویداد show بوت‌استرپ
      «قبل از» اجرای هندلر کلیکِ خودِ عنصر fire می‌شود، پس شنونده همیشه
      یک کلیک دیر می‌رسید. حالا هر دو شنونده (show/hidden) در لود صفحه
      وصل می‌شوند و بار اول هم نقشه ساخته می‌شود.
   ۲) «خطای ۴۰۵ هنگام افزودن آدرس» — هندلر hidden اکشن فرم را با ''
      خالی می‌کرد → POST به صفحهٔ جاری (که فقط GET است) → 405. حالا
      اکشن به مسیر «ذخیرهٔ آدرس» برگردانده می‌شود و _method حذف می‌شود.
   ۳) «استان و شهر باید از لوکیشن انتخابی پر شوند» — پاسخ reverse
      map.ir فیلد province/city دارد؛ به‌صورت خودکار در سلکت‌ها انتخاب
      می‌شود (شهرها با /province/get-cities بارگذاری می‌شوند). برای
      نتیجهٔ جستجوی آدرس هم data-province/data-city ست می‌شود.
   ============================================================ */

(function ($) {
    'use strict';

    /* ------------------------- ابزارها ------------------------- */

    /* یکسان‌سازی نویسه‌های عربی/فارسی برای تطبیق نام استان/شهر */
    function normalizeFa(text) {
        return String(text || '')
            .replace(/\u064A/g, '\u06CC') /* ي → ی */
            .replace(/\u0649/g, '\u06CC') /* ى → ی */
            .replace(/\u0643/g, '\u06A9') /* ك → ک */
            .replace(/\u200C/g, ' ')      /* ZWNJ → فاصله */
            .replace(/\s+/g, ' ')
            .trim();
    }

    function stripProvincePrefix(text) {
        return normalizeFa(text).replace(/^استان\s+/, '');
    }

    function getMapApiKey() {
        var $input = $('input[name=map_api]');
        return $input.length ? $input.val() : '';
    }

    /* بارگذاری شهرهای استان در index.js انجام می‌شود (شنوندهٔ change
       #province)؛ اینجا فقط «شهر در انتظار» ثبت می‌شود تا بعد از لود
       شهرها خودکار انتخاب شود — بدون دو مسیر موازی AJAX */

    /* انتخاب خودکار استان بر اساس نام (با پوشش نویسه‌های عربی/فارسی) */
    function selectProvinceByName(provinceName, cityName) {
        var $province = $('#province');
        var wanted = stripProvincePrefix(provinceName);
        if (!wanted) return false;

        var matched = null;
        $province.find('option').each(function () {
            if (!matched && normalizeFa($(this).text()) === wanted) {
                matched = $(this).val();
            }
        });

        if (matched) {
            /* شهرِ در انتظار — بعد از بارگذاری شهرها در index.js انتخاب می‌شود */
            $('#add-edit-address-modal').data('pending-city', normalizeFa(cityName || ''));
            if ($province.val() !== matched) {
                $province.val(matched);
                $province.trigger('change');
            } else {
                /* استان از قبل انتخاب بوده؛ شهرها در سلکت موجودند → مستقیم */
                selectPendingCity();
            }
            return true;
        }

        return false;
    }

    function selectPendingCity() {
        var $modal = $('#add-edit-address-modal');
        var wanted = $modal.data('pending-city');
        if (!wanted) return;
        $modal.removeData('pending-city');

        var $city = $('#city');
        var matched = null;
        $city.find('option').each(function () {
            if (!matched && normalizeFa($(this).text()) === wanted) {
                matched = $(this).val();
            }
        });
        if (matched) {
            $city.val(matched);
            $city.trigger('change');
            if ($.fn.niceSelect) { try { $city.niceSelect('update'); } catch (e) {} }
        }
    }

    /* اعمال موقعیت انتخاب‌شده روی فرم (آدرس/لات/لانگ/استان/شهر) */
    function applyLocationToForm(lat, lng, address, provinceName, cityName) {
        var $modal = $('#add-edit-address-modal');

        $modal.find('textarea#address').val(address || '');
        $modal.find('input#lat').val(lat);
        $modal.find('input#lng').val(lng);

        $('.add-address-unsuccess').remove();
        $('.add-address-success').remove();
        $modal.find('.modal-footer').prepend('<span class="add-address-success">آدرس اضافه شد!</span>');
        $('#next-add-address-btn').removeClass('disabled');
        $('#next-add-address-btn').removeAttr('disabled');

        /* فاز ۱۶۱ — استان/شهر خودکار از موقعیت انتخابی نقشه */
        selectProvinceByName(provinceName, cityName);
    }

    /* ------------------------- نقشه ------------------------- */

    var mapApp = null;   /* نمونهٔ Mapp */
    var mapMarker = null;

    function buildMap(apiKey) {
        $('#map-element').empty();

        mapApp = new Mapp({
            element: '#map-element',
            presets: {
                latlng: { lat: 35.73249, lng: 51.42268 },
                zoom: 10
            },
            apiKey: apiKey
        });

        $('#map-element .mapp-anchor.bottom.position-middle.reverse.item-set.vertical').html(' ');

        mapApp.addLayers();

        var drawnMarker = new L.LayerGroup();

        /* ------------------ جستجوی آدرس ------------------ */
        const search = {
            params: { text: null },
            search: function (options, calback) {
                if (options.text === null || options.text === '') {
                    return null;
                }
                const data = {};
                for (let key in options) {
                    if (options[key] !== null && options[key] !== '') {
                        data[key] = options[key];
                    }
                }
                calback(null); ///show results
                $.ajax({
                    url: 'https://map.ir/search/v2/',
                    data: JSON.stringify(data),
                    method: 'POST',
                    beforeSend: function (request) {
                        request.setRequestHeader('x-api-key', apiKey);
                        request.setRequestHeader('content-type', 'application/json');
                    },
                    success: function (data, status) {
                        calback(data); ///show results
                    },
                    error: function (error) {
                        calback({ 'odata.count': 0, value: [] }); /// show results
                    }
                });
            },
            setParams: function (key, value, onUpdate, calback) {
                this.params[key] = value;
                if (onUpdate) {
                    this.search(this.params, calback);
                }
            }
        };

        function showResults(results) {
            if (results === null) {
                $('.search-results').text('در حال جستجو...');
                $('.search-results').show();
            } else {
                let count = results['odata.count'];
                if (count > 0) {
                    $('.clear-seach').show();
                    let html = '';
                    results['value'].forEach(function (item) {
                        html += `<div data-title="${item.title}" data-address="${
                            item.address
                        }" data-lat="${item.geom.coordinates[1]}" data-lon="${
                            item.geom.coordinates[0]
                        }" data-province="${item.province || ''}" data-city="${item.city || item.county || ''}" class="search-result-item">`;
                        html += `<p class="search-result-item-title"><img src="https://map.ir/css/images/marker-default-white.svg"/>${
                            item.title
                        }</p>`;
                        html += `<p class="search-result-item-address">${item.address}</p>`;
                        html += `</div>`;
                    });
                    //show results
                    $('.search-results').html(html);
                    $('.search-result-item').on('click', function (e) {
                        let lat = e.currentTarget.getAttribute('data-lat');
                        let lng = e.currentTarget.getAttribute('data-lon');
                        let title = e.currentTarget.getAttribute('data-title');
                        let address = e.currentTarget.getAttribute('data-address');
                        let province = e.currentTarget.getAttribute('data-province');
                        let city = e.currentTarget.getAttribute('data-city');

                        mapApp.addMarker({
                            name: 'basic-marker',
                            latlng: { lat, lng },
                            popup: {
                                title: { html: title },
                                description: { html: address },
                                open: true
                            }
                        });
                        mapApp.map.flyTo({ lat, lng });

                        $('.search-results').hide();

                        /* فاز ۱۶۱ — انتخاب از نتایج جستجو هم استان/شهر را پر می‌کند */
                        applyLocationToForm(lat, lng, address, province, city);
                    });
                    $('.search-results').show();

                } else {
                    $('.clear-seach').show();
                    $('.search-results').html('<p>نتیجه ای یافت نشد</p>');
                }
            }
        }

        //clear search
        $('.clear-seach').click(function () {
            search.params = { text: null };

            $('.search-results').html('');
            $('.search-results').hide();
            $('.clear-seach').hide();
            $('input#search').val('');
            $('.leaflet-container').css('cursor', '');

            if (mapApp && mapApp.groups && mapApp.groups.features !== undefined) {
                mapApp.removeMarkers({ group: mapApp.groups.features.markers });
            }
            drawnMarker.clearLayers();
        });

        //text change event handling
        $('#search').on('keyup paste', function () {
            let text = $('input#search').val();
            if (text.length > 1) {
                search.setParams('text', text, true, showResults);
            }
        });

        mapMarker = mapApp.addMarker({
            name: 'marker',
            latlng: { lat: 35.73249, lng: 51.42268 },
            popup: false,
            pan: false,
            draggable: true,
            history: false,
        });
        mapApp.removeMarkers({
            group: mapApp.groups.features.markers,
        });

        mapApp.map.on('move', function () {
            $('#add-edit-address-modal textarea#address').val('');
            $('#next-add-address-btn').addClass('disabled');
            $('#next-add-address-btn').attr('disabled', true);
            $('.add-address-success').remove();
            $('.add-address-unsuccess').remove();
            $('#add-edit-address-modal .modal-footer').prepend('<span class="add-address-unsuccess">آدرس اضافه نشد!</span>');
        });

        //get address with dragend
        mapApp.map.on('dragend', function () {
            var e = mapApp.map.getCenter();

            $.ajax({
                url: `https://map.ir/reverse/?lat=${e.lat}&lon=${e.lng}`,
                method: 'GET',
                beforeSend: function (request) {
                    request.setRequestHeader('x-api-key', apiKey);
                    request.setRequestHeader('content-type', 'application/json');
                },
                success: function (data) {
                    var lat = e.lat;
                    var lng = e.lng;
                    var newLatLng = new L.LatLng(lat, lng);
                    if (mapMarker) { mapMarker.setLatLng(newLatLng); }

                    /* فاز ۱۶۱ — استان/شهر خودکار از پاسخ reverse
                       (city خالی بود → county، بعد rural_district) */
                    var provinceName = data.province || data.county || '';
                    var cityName = data.city || data.county || data.rural_district || '';

                    applyLocationToForm(lat, lng, data.address, provinceName, cityName);
                },
                error: function (error) {
                    console.log(error);
                }
            });
        });
    }

    /* ------------------- چرخهٔ حیات مودال ------------------- */

    $(function () {
        var $modal = $('#add-edit-address-modal');
        if (!$modal.length) return;

        /* مسیر «ذخیرهٔ» آدرس — از data-store-url روی مودال یا باکس افزودن */
        function storeUrl() {
            var url = $modal.data('store-url');
            if (!url) {
                var $box = $('.user-add-address--box[data-updateurl], #add-address-modal[data-updateurl]').first();
                url = $box.length ? $box.data('updateurl') : '';
            }
            return url;
        }

        /* فاز ۱۶۱ — هر دو شنونده در لود صفحه وصل می‌شوند (رفع کلیک دوم) */
        $modal.on('show.bs.modal', function () {
            var apiKey = getMapApiKey();

            block('#add-edit-address-modal .modal-body');
            $modal.find('#center-marker').addClass('d-none');
            /* کمی بعد از شروع نمایش (پایان fade این) تا Leaflet اندازهٔ
               درست بگیرد — قبلاً ۱۰۰۰ms بود که حس کندی می‌داد */
            setTimeout(function () {
                /* اگر مودال در فاصلهٔ تأخیر بسته شده بود، نساز */
                if (!$modal.hasClass('show')) {
                    try { unblock('#add-edit-address-modal .modal-body'); } catch (e) {}
                    return;
                }

                try {
                    buildMap(apiKey);
                } catch (error) {
                    console.error('map init failed', error);
                }

                try { unblock('#add-edit-address-modal .modal-body'); } catch (e) {}
                $('#add-edit-address-modal #center-marker').removeClass('d-none');
            }, 400);
        });

        /* فاز ۱۶۱ — رفع ۴۰۵: اکشن فرم دیگر خالی نمی‌شود؛
           به مسیر «ذخیره» برمی‌گردد و حالت ویرایش پاک می‌شود */
        $modal.on('hidden.bs.modal', function () {
            var $form = $('#add-update-address-form');
            var url = storeUrl();
            if (url) { $form.attr('action', url); }
            $form.find('input[name=_method]').remove();

            $('#map-element').html(' ');
            mapApp = null;
            mapMarker = null;

            $('#add-edit-address-modal textarea#address').val('');
            $('#next-add-address-btn').addClass('disabled');
            $('#next-add-address-btn').attr('disabled', true);
            $('.add-address-success').remove();
            $('.add-address-unsuccess').remove();
            $('#add-edit-address-modal #showMap').removeClass('d-none');
            $('#add-edit-address-modal #more-information').addClass('d-none');
        });
    });

})(jQuery);
