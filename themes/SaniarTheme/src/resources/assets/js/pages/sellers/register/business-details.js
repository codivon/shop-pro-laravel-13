// validate form with jquery validation plugin
jQuery('#seller-register-business-details').validate({
    rules: {
        private_business: {
            required: true,
        },
        first_name: {
            required: true,
            regex: "^[\u0600-\u06FF\uFB8A\u067E\u0686\u06AF]+$"
        },
        last_name: {
            required: true,
            regex: "^[\u0600-\u06FF\uFB8A\u067E\u0686\u06AF]+$"
        },
        birth_day: {
            required: true,
        },
        birth_month: {
            required: true,
        },
        birth_year: {
            required: true,
        },
        gender: {
            required: true,
        },
        identity_card_number: {
            required: true,
        },
        national_identity_number: {
            required: true,
            digits: true,
            minlength:10,
            maxlength:10
        },
        company_name: {
            required: true,
            regex: "^[\u0600-\u06ff0-9\\s]+$|[\u0750-\u077f0-9\\s]+$|[\ufb50-\ufc3f0-9\\s]+$|[\ufe70-\ufefc0-9\\s]+$|[\u06cc0-9\\s]+$|[\u067e0-9\\s]+$|[\u06af0-9\\s]$|[\u06910-9\\s]+$|^$"
        },
        company_type: {
            required: true,
        },
        company_registration_number: {
            required: true,
        },
        company_national_identity_number: {
            required: true,
            digits: true,
            minlength:11,
            maxlength:11
        },
        company_economic_number: {
            digits: true,
            minlength:11,
            maxlength:11
        },
        state_id: {
            required: true,
        },
        city_id: {
            required: true,
        },
        address: {
            required: true,
            regex: "^[\u0600-\u06ff0-9\\s]+$|[\u0750-\u077f0-9\\s]+$|[\ufb50-\ufc3f0-9\\s]+$|[\ufe70-\ufefc0-9\\s]+$|[\u06cc0-9\\s]+$|[\u067e0-9\\s]+$|[\u06af0-9\\s]$|[\u06910-9\\s]+$|^$"
        },
        post_code: {
            required: true,
            digits: true,
            minlength:10,
            maxlength:10
        },
        phone: {
            required: true,
            digits: true,
            minlength:11,
            maxlength:11
        },
        mobile_phone: {
            required: true,
            regex: "(09)[0-9]{9}"
        },
        business_name: {
            required: true,
            regex: "^[\u0600-\u06ff0-9\\s]+$|[\u0750-\u077f0-9\\s]+$|[\ufb50-\ufc3f0-9\\s]+$|[\ufe70-\ufefc0-9\\s]+$|[\u06cc0-9\\s]+$|[\u067e0-9\\s]+$|[\u06af0-9\\s]$|[\u06910-9\\s]+$|^$"
        },
        shaba_number: {
            required: true,
        },
        main_supply_category_id: {
            required: true,
        },
        number_of_products: {
            required: true,
        },
        econtract: {
            required: true,
        },
    },
    messages: {
        first_name: {
            required: "وارد نمودن نام اجباری است",
            regex: "تنها حروف فارسی مجاز است",
        },
        last_name: {
            required: "وارد نمودن نام خانوادگی اجباری است",
            regex: "تنها حروف فارسی مجاز است",
        },
        birth_year: "انتخاب تاریخ اجباری است",
        identity_card_number: "وارد نمودن شماره شناسنامه اجباری است",
        national_identity_number: {
            required: "وارد کردن کد ملی اجباری است",
            digits: "لطفا یک مقدار معتبر وارد کنید",
            minlength: "لطفا یک مقدار معتبر وارد کنید",
            maxlength: "لطفا یک مقدار معتبر وارد کنید",
        },
        company_name: {
            required: "وارد نمودن نام شرکت اجباری است",
            regex: "فقط کاراکترهای فارسی، عدد مجاز است",
        },
        company_registration_number: "وارد نمودن شماره ثبت اجباری است",
        company_national_identity_number: {
            required: "وارد نمودن شناسه ملی اجباری است",
            minlength: "کدپستی میبایست حداقل 11 رقم باشد",
            maxlength: "شناسه ملی حداکثر باید 11 رقم باشد",
            digits: "لطفا یک مقدار معتبر وارد کنید",
        },
        company_economic_number: {
            minlength: "کد اقتصادی میبایست حداقل 12 رقم باشد",
            maxlength: "کد اقتصادی حداکثر باید 12 رقم باشد",
            digits: "لطفا یک مقدار معتبر وارد کنید",
        },
        state_id: "انتخاب استان اجباری است",
        city_id: "انتخاب شهر اجباری است",
        address: {
            required:  "وارد نمودن آدرس اجباری است",
            regex: "فقط کاراکترهای فارسی، عدد مجاز است",
        },
        post_code: {
            required: "وارد نمودن کدپستی اجباری است",
            minlength: "کدپستی باید 10 رقمی‌باشد",
            maxlength: "کدپستی باید 10 رقمی‌باشد",
            digits: "لطفا یک مقدار معتبر وارد کنید",
        },
        phone: {
            required: "وارد نمون مقدار تلفن اجباری است",
            minlength: "شماره تلفن ثابت باید 11 رقمی‌باشد",
            maxlength: "شماره تلفن ثابت باید 11 رقمی‌باشد",
            digits: "لطفا یک مقدار معتبر وارد کنید",
        },
        mobile_phone: {
            required: "وارد نمودن مقدار شماره موبایل اجباری است",
            regex: "شماره همراه باید با ۰۹ شروع شود و ۱۱ رقم باشد",
        },
        business_name: {
            required: "وارد نمودن نام تجاری اجباری است",
            regex: "تنها حروف فارسی مجاز است",
        },
        shaba_number: {
            required: "وارد کردن کد شبا الزامی است",
        },
        main_supply_category_id: "لطفا نوع کالاهایی را که میخواهید در دیجیکالا به فروش برسانید را مشخص کنید",
        econtract: "تایید قرار داد الزامی است.",

    }
});
$.validator.addMethod(
    "regex",
    function(value, element, regexp) {
        var re = new RegExp(regexp);
        return this.optional(element) || re.test(value);
    },
    "لطفا یک مقدار معتبر وارد کنید"
);



$('#seller-register-business-details').submit(function (e) {
    e.preventDefault();
    if ($(this).valid() && !$(this).data('disabled')) {
        var formData = new FormData(this);

        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: formData,
            success: function (data) {
                if (data.status=="success"){
                    window.location.href = data.redirect;
                }else{
                    showCustomToast(data.message,'error');
                    setTimeout(function () {
                        window.location.href = data.redirect;
                    },1000)
                }

            },
            beforeSend: function (xhr) {
                block('.registration-business-details');
                xhr.setRequestHeader(
                    'X-CSRF-TOKEN',
                    $('meta[name="csrf-token"]').attr('content')
                );
            },
            complete: function () {
                unblock('.registration-business-details');
            },
            cache: false,
            contentType: false,
            processData: false
        });
    }
});

$('input[name=private_business]').click(function(){
    var item=this;
    if(item.checked){
        var private_business=$(item).val()
        if (private_business=='private'){
            $('#business-div').addClass('d-none');
            $('#private-div').removeClass('d-none');
        }else if (private_business=='business'){
            $('#private-div').addClass('d-none');
            $('#business-div').removeClass('d-none');
        }
    }

});








var mapIrApiKey = $('input[name=map_api]').val();
var icon_location = BASE_URL + '/themes/SaniarTheme/img/pin-location.svg'
$('#lat_and_long').click(function () {
    $('#business-details-map-modal').on('show.bs.modal', function () {
        block('#add-edit-address-modal .modal-body');
        $('#add-edit-address-modal #center-marker').addClass('d-none');
        setTimeout(function () {
            //create map and layers
            $('#map-element').empty();
            var app = [];


            app = new Mapp({
                element: '#map-element',
                presets: {
                    latlng: {
                        lat: 35.73249,
                        lng: 51.42268
                    },
                    zoom: 10
                },
                apiKey: mapIrApiKey,
            });


            app.addLayers();
            app.map.on('click', function(e) {
                // آدرس یابی و نمایش نتیجه در یک باکس مشخص

                app.addMarker({
                    name: 'advanced-marker',
                    latlng: {
                        lat: e.latlng.lat,
                        lng: e.latlng.lng
                    },
                    icon: {
                        iconUrl: icon_location,
                        iconSize:     [40, 40], // size of the icon
                    },
                    popup: false
                });
                $('input[name=location]').val(e.latlng.lat+';'+e.latlng.lng);
                var latlng=$('input[name=location]').val();
                if (latlng!=""){
                    $('.c-ui-form__col.c-ui-form__col--group-item.c-ui-form__col--wrap-xs button').addClass('c-ui-btn--active');
                    $('.c-ui-form__col.c-ui-form__col--group-item.c-ui-form__col--wrap-xs button').removeAttr('disabled','disabled');
                }else {
                    $('.c-ui-form__col.c-ui-form__col--group-item.c-ui-form__col--wrap-xs button').removeClass('c-ui-btn--active');
                    $('.c-ui-form__col.c-ui-form__col--group-item.c-ui-form__col--wrap-xs button').attr('disabled','disabled');
                }
             });

            $('#map-element .mapp-anchor.bottom.position-middle.reverse.item-set.vertical').html(' ');
            //app.addVectorLayers();

            //search object
            const search = {
                params: {
                    text: null
                },
                search: function (options, calback) {
                    if (options.text === null || options.text === '') {
                        return null;
                    }
                    //prepare data
                    const data = {};
                    for (let key in options) {
                        if (options[key] !== null && options[key] !== '') {
                            data[key] = options[key];
                        }
                    }
                    calback(null); ///show results
                    $.ajax({
                        url: `https://map.ir/search/v2/`,
                        data: JSON.stringify(data),
                        method: 'POST',
                        beforeSend: function (request) {
                            request.setRequestHeader('x-api-key', mapIrApiKey);
                            request.setRequestHeader('content-type', 'application/json');
                        },
                        success: function (data, status) {
                            calback(data); ///show results
                        },
                        error: function (error) {
                            calback({'odata.count': 0, value: []}); /// show results
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
                        results['value'].forEach(function (item, index) {
                            html += `<div data-title="${item.title}" data-address="${
                                item.address
                            }" data-lat="${item.geom.coordinates[1]}" data-lon="${
                                item.geom.coordinates[0]
                            }" class="search-result-item">`;
                            html += `<p class="search-result-item-title">${
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
                            app.addMarker({
                                name: 'advanced-marker',
                                latlng: {
                                    lat,
                                    lng
                                },
                                popup: false
                            });
                            app.map.flyTo({
                                lat,
                                lng
                            });
                            $('input[name=lat_and_long]').val('');
                            $('.leaflet-pane.leaflet-marker-pane').empty();
                            $('.search-results').hide();
                            var latlng=$('input[name=location]').val();
                            if (latlng!=""){
                                $('.c-ui-form__col.c-ui-form__col--group-item.c-ui-form__col--wrap-xs button').addClass('c-ui-btn--active');
                                $('.c-ui-form__col.c-ui-form__col--group-item.c-ui-form__col--wrap-xs button').removeAttr('disabled','disabled');
                            }else {
                                $('.c-ui-form__col.c-ui-form__col--group-item.c-ui-form__col--wrap-xs button').removeClass('c-ui-btn--active');
                                $('.c-ui-form__col.c-ui-form__col--group-item.c-ui-form__col--wrap-xs button').attr('disabled','disabled');
                            }
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
                search.params = {
                    text: null
                };

                $('.search-results').html('');
                $('.search-results').hide();
                $('.clear-seach').hide();
                $('input#search').val('');
                $('.leaflet-container').css('cursor', '');

                if (app.groups.features !== undefined) {
                    app.removeMarkers({
                        group: app.groups.features.markers
                    });
                }
                drawnMarker.clearLayers();
            });

            //text change event handling
            $('#search').on('keyup paste', function (e) {
                let text = $('input#search').val();
                if (text.length > 1) {
                    search.setParams('text', text, true, showResults);
                }
            });



        }, 1000);
    });
    $('#add-edit-address-modal').on('hidden.bs.modal', function () {

        $('#add-update-address-form').attr('action', '');
        $('#map-element').html(' ')
        $('#add-edit-address-modal textarea#address').val('');
        $('#next-add-address-btn').addClass('disabled');
        $('#next-add-address-btn').attr('disabled', true);
        $('.add-address-success').remove();
        $('.add-address-unsuccess').remove();
        $('#add-edit-address-modal #showMap').removeClass('d-none');
        $('#add-edit-address-modal #more-information').addClass('d-none');
    })
})


$('.js-coordinates-confirm').click(function () {
    var latlng=$('input[name=location]').val();
    $('input[name=lat_and_long]').val(latlng);
    $('#lat_and_long').val('ثبت شد');
    $('#business-details-map-modal').modal('hide');
})
