
$(document).ready(function() {

    $('#print-order').click(function () {
        window.print();
    });
    // به‌روزرسانی وضعیت ارسال
    $('#update-status').click(function() {
        var newStatus = $('#shipping-status-change').val();

        // اگر وضعیت انتخاب شده "canceled" است، مودال را نشان بده

        if (newStatus === 'canceled') {
            $('#cancelOrderModal').modal('show');
            return;
        }

        // غیرفعال کردن دکمه تأیید برای جلوگیری از کلیک مجدد
        $(this).prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> در حال پردازش...');


        // برای سایر وضعیت‌ها، مستقیماً درخواست بزن
        $.ajax({
            url: $(this).data('action'),
            type: 'POST',
            data: {
                shipping_status: newStatus,
            },
            success: function(response) {
                showCustomToast('وضعیت با موفقیت تغییر کرد','success');
                setTimeout(function() {
                    location.reload();
                }, 1000);
            },
            beforeSend: function(xhr) {
                xhr.setRequestHeader("X-CSRF-TOKEN", $('meta[name="csrf-token"]').attr('content'));
            },
            error: function(xhr) {
                toastr.error('خطا در تغییر وضعیت: ' + (xhr.responseJSON?.message || 'خطای ناشناخته'));
            }
        });
    });

// تأیید لغو سفارش از مودال
    $('#confirm-cancel').click(function() {
        var canceled_refund_amount = $('#canceled_refund_amount').is(':checked');
        var cancelReason = $('#cancel_reason').val();
        var newStatus = $('#shipping-status-change').val();

        // غیرفعال کردن دکمه تأیید برای جلوگیری از کلیک مجدد
        $(this).prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> در حال پردازش...');

        $.ajax({
            url: $('#update-status').data('action'),
            type: 'POST',
            data: {
                shipping_status: newStatus,
                canceled_refund_amount: canceled_refund_amount ? 1 : 0,
                cancel_reason: cancelReason
            },
            success: function(response) {
                showCustomToast('سفارش با موفقیت لغو شد','success');
                setTimeout(function() {
                    location.reload();
                }, 1000);
            },
            error: function(xhr) {
                toastr.error('خطا در لغو سفارش: ' + (xhr.responseJSON?.message || 'خطای ناشناخته'));
                $('#confirm-cancel').prop('disabled', false).html('تأیید و لغو سفارش');
            },
            beforeSend: function(xhr) {
                xhr.setRequestHeader("X-CSRF-TOKEN", $('meta[name="csrf-token"]').attr('content'));
            }
        });
    });

// به‌روزرسانی متن مبلغ در مودال هنگام باز شدن
    $('#cancelOrderModal').on('show.bs.modal', function() {
        var orderAmount = $('#order-amount-value').data('amount');
    });

    // به‌روزرسانی کد رهگیری
    $('#update-tracking').click(function() {
        var trackingCode = $('#tracking-code').val();
        if (!trackingCode) {
            showCustomToast('لطفا کد رهگیری را وارد کنید','warning');
            return;
        }
        // غیرفعال کردن دکمه تأیید برای جلوگیری از کلیک مجدد
        $(this).prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> در حال پردازش...');

        $.ajax({
            url: $(this).data('action'),
            type: 'POST',
            data: {
                tracking_code: trackingCode
            },
            success: function(response) {
                showCustomToast('کد رهگیری با موفقیت ثبت شد','success');
                $('#update-tracking').prop('disabled', false).html('<i class="feather icon-save"></i>')
            },
            beforeSend: function(xhr) {
                xhr.setRequestHeader("X-CSRF-TOKEN", $('meta[name="csrf-token"]').attr('content'));
            },
            error: function(xhr) {
                toastr.error('خطا در ثبت کدرهگیری : ' + (xhr.responseJSON?.message || 'خطای ناشناخته'));
            }
        });
    });


    $('#locationMapModal').on('show.bs.modal', function () {
        block('#locationMapModal #map')
        setTimeout(function () {
            loadMap()
            unblock('#locationMapModal #map')
        }, 1000);
    });

    function loadMap() {
        "use strict";

        if (info_map_type == 'google') {
            if (typeof google !== 'undefined') {
                var text = info_site_title;
                var myLatlng = new google.maps.LatLng(info_latitude, info_Longitude);
            }

            function initialize() {
                var mapProp = {
                    center: myLatlng,
                    zoom: 16,
                    scrollwheel: false,
                    mapTypeId: google.maps.MapTypeId.ROADMAP,
                };

                var map = new google.maps.Map(document.getElementById("map"), mapProp);

                var marker = new google.maps.Marker({
                    position: myLatlng,
                });

                var infowindow = new google.maps.InfoWindow({
                    content: text
                });

                infowindow.open(map, marker);

                marker.setMap(map);
            }

            if ($('#map').length > 0 && typeof google !== 'undefined') {
                google.maps.event.addDomListener(window, 'load', initialize);
            }
        } else {
            var app = new Mapp({
                element: '#map',
                presets: {
                    latlng: {
                        lat: info_latitude,
                        lng: info_Longitude,
                    },
                    zoom: 16
                },
                apiKey: mapIrApiKey
            });
            app.addLayers();

            var marker = app.addMarker({
                name: 'marker',
                latlng: {
                    lat: info_latitude,
                    lng: info_Longitude,
                },
                icon: app.icons.red,
                popup: false,
                pan: true,
            });

            var popup = app.generatePopupHtml({
                title: {
                    i18n: info_site_title,
                },
                class: 'custom-css-class',
            });

            marker.bindPopup(popup);
        }
    }



});

$('#shipping-status').change(function () {
    var select = this;

    console.log($(select).data('action'))
    $.ajax({
        url: $(select).data('action'),
        type: 'POST',
        data: {
            status: $(select).val()
        },
        success: function (data) {
            Swal.fire({
                type: 'success',
                title: 'تغییرات با موفقیت ذخیره شد',
                confirmButtonClass: 'btn btn-primary',
                confirmButtonText: 'باشه',
                buttonsStyling: false,
            })
        },
        beforeSend: function (xhr) {
            block('#main-card');
            xhr.setRequestHeader("X-CSRF-TOKEN", $('meta[name="csrf-token"]').attr('content'));
        },
        complete: function () {
            unblock('#main-card');
        },

    });
});
