var data = [];

$.each(provinces, function (index, province) {
    let cities = [];
    let checked = false;

    $.each(province.cities, function (index, city) {
        cities.push({
            id: city.id,
            title: city.name
        });

        if (selected_cities.includes(city.id)) {
            checked = true;
        }
    });

    let p = {
        id: 'province_' + province.id,
        title: province.name,
        subs: cities
    };

    if (checked) {
        selected_cities.push('province_' + province.id);
    }

    data.push(p);
});

var comboProvinces;

jQuery(document).ready(function ($) {
    comboProvinces = $('#included-cities').comboTree({
        source: data,
        isMultiple: true,
        cascadeSelect: true,
        collapse: true,
        selected: selected_cities
    });
});

$('#province, #city').select2({
    rtl: true,
    width: '100%'
});

$('#province').change(function () {
    var id = $(this).find(':selected').val();

    $('#city').empty();

    if (!id) {
        return;
    }

    $.ajax({
        type: 'GET',
        url: $('#province').data('action'),
        data: {id: id},
        success: function (data) {
            $(data).each(function (i, item) {
                $('#city').append(
                    '<option value="' + item.id + '">' + item.name + '</option>'
                );
            });
        },
        beforeSend: function () {
            block('#city');
        },
        complete: function () {
            unblock('#city');
        }
    });
});

$('select[name="covered_cities"]')
    .on('change', function () {
        if ($(this).val() == 'all') {
            $('#included-cities-container').hide();
        } else {
            $('#included-cities-container').show();
        }
    })
    .trigger('change');





// ========== تبدیل تاریخ میلادی به شمسی ==========
function convertToJalali(date, callback) {
    $.ajax({
        url: convertToJalaliUrl,
        type: 'POST',
        data: {
            date: date,
        },
        success: function(response) {
            callback(response);
        },
        beforeSend: function (xhr) {
            xhr.setRequestHeader(
                'X-CSRF-TOKEN',
                $('meta[name="csrf-token"]').attr('content')
            );
        },
        error: function() {
            callback(null);
        }
    });
}

// ========== دریافت نام روز هفته ==========
function getPersianDayName(dayIndex) {
    const days = ['یکشنبه', 'دوشنبه', 'سه‌شنبه', 'چهارشنبه', 'پنجشنبه', 'جمعه', 'شنبه'];
    return days[dayIndex];
}

// ========== محاسبه و نمایش روزها ==========
function calculateAndDisplayDates() {
    var selectedRange = parseInt($('input[name="user_select_ranges"]:checked').val());
    var startDaysAfter = parseInt($('#start_days_after_order').val()) || 1;
    var disableHolidays = $('#disable_holidays').is(':checked');
    var disableFridays = $('#disable_fridays').is(':checked');
    startDaysAfter-=1;

    if (!selectedRange || isNaN(selectedRange)) {
        $('#dates_preview_container').hide();
        return;
    }

    // ذخیره مقدار
    //$('#user_select_ranges').val(selectedRange);

    // دریافت تاریخ شروع از سرور
    $.ajax({
        url: calculateAndDisplayDatesUrl,
        type: 'POST',
        data: {
            start_days: startDaysAfter,
            range_days: selectedRange,
        },
        success: function(response) {
            if (response.success) {
                displayDatesPreview(response.dates, disableHolidays, disableFridays);
            } else {
                $('#dates_preview').html('<div class="text-center text-danger">خطا در بارگذاری</div>');
            }
        },
        beforeSend: function (xhr) {
            $('#dates_preview').html('<div class="text-center p-5"><i class="fa fa-spinner fa-spin fa-2x"></i><br>در حال بارگذاری...</div>');
            xhr.setRequestHeader(
                'X-CSRF-TOKEN',
                $('meta[name="csrf-token"]').attr('content')
            );
        },
        error: function() {
            $('#dates_preview').html('<div class="text-center text-danger">خطا در ارتباط با سرور</div>');
        }
    });
}

// نمایش روزها
function displayDatesPreview(dates, disableHolidays, disableFridays) {
    var container = $('#dates_preview');
    container.empty();

    if (!dates || dates.length === 0) {
        $('#dates_preview_container').hide();
        return;
    }

    for (var i = 0; i < dates.length; i++) {
        var date = dates[i];

        // تعیین کلاس‌ها و وضعیت
        var holidayClass = '';
        var badgeText = '';
        var badgeClass = '';
        var disabledAttr = '';
        var selectableClass = 'selectable';
        var isDisabled = false;
        console.log(disableHolidays);
        if (date.is_friday) {
            // جمعه
            holidayClass = 'friday';
            badgeText = 'جمعه';
            badgeClass = 'friday-badge';

            // اگر disableFridays فعال باشد، جمعه غیرقابل انتخاب
            if (disableFridays) {
                isDisabled = true;
                disabledAttr = 'disabled';
                selectableClass = '';
            }
        }else if (date.is_holiday) {
            // تعطیلات رسمی
            holidayClass = 'friday';
            badgeText = 'تعطیل';
            badgeClass = '';

            // اگر disableHolidays فعال باشد، تعطیلات رسمی غیرقابل انتخاب
            if (disableHolidays) {
                holidayClass = 'holiday';
                isDisabled = true;
                disabledAttr = 'disabled';
                selectableClass = '';
            }
        }

        // عنوان tooltip
        var holidayTitle = '';
        if (date.is_friday) {
            holidayTitle = 'title="جمعه"';
        } else if (date.is_holiday && date.holiday_description) {
            holidayTitle = `title="${date.holiday_description}"`;
        }

        var dateHtml = `
                <div class="col-md-3 col-6 mb-3">
                    <div class="date-card ${holidayClass} ${selectableClass}"
                         data-date="${date.gregorian}"
                         data-jalali="${date.jalali}"
                         data-is-holiday="${date.is_holiday}"
                         data-is-friday="${date.is_friday}"
                         ${disabledAttr}
                         ${holidayTitle}>
                        <div class="date-card-weekday">${date.day_name}</div>
                        <div class="date-card-date">${date.jalali_display}</div>
                        ${badgeText ? `<div class="date-card-holiday-badge ${badgeClass}">${badgeText}</div>` : ''}
                        ${isDisabled ? '<div class="date-card-disabled-overlay"></div>' : ''}
                    </div>
                </div>
            `;
        container.append(dateHtml);
    }

    $('#dates_preview_container').show();

 /*   // قابلیت انتخاب (فقط روزهای غیر غیرفعال)
    $('.date-card.selectable').off('click').on('click', function() {
        var isDisabled = $(this).is('[disabled]');
        var isFriday = $(this).data('is-friday');
        var isHoliday = $(this).data('is-holiday');
        var disableHolidaysVal = $('#disable_holidays').is(':checked');
        var disableFridaysVal = $('#disable_fridays').is(':checked');

        // بررسی اینکه آیا روز قابل انتخاب است
        var canSelect = true;

        if (isFriday && disableFridaysVal) {
            canSelect = false;
        } else if (isHoliday && disableHolidaysVal) {
            canSelect = false;
        }

        if (!isDisabled && canSelect) {
            $('.date-card').removeClass('selected');
            $(this).addClass('selected');
            $('#selected_delivery_date').val($(this).data('jalali'));

            console.log('Selected:', $(this).data('jalali'));
        }
    });
*/
}
// ========== مدیریت نمایش/مخفی کردن بخش‌ها ==========
function toggleDeliveryTimeFields() {
    var type = $('#delivery_time_type').val();

    if (type === 'default') {
        $('#default_range_container').show();
        $('#user_select_ranges_container').hide();
        $('#dates_preview_container').hide();
    } else {
        $('#default_range_container').hide();
        $('#user_select_ranges_container').show();
        calculateAndDisplayDates();
    }
}

// ========== رویدادها ==========
$('#delivery_time_type').on('change', function() {
    toggleDeliveryTimeFields();
});

$('.range-radio').on('change', function() {
    calculateAndDisplayDates();
});

$('#disable_fridays').on('change', function() {
    calculateAndDisplayDates();
});

$('#start_days_after_order').on('change', function() {
    calculateAndDisplayDates();
});

$('#disable_holidays').on('change', function() {
    calculateAndDisplayDates();
});

// ========== اعتبارسنجی قبل از ارسال ==========
$('#carrier-create-form').on('submit', function(e) {
    var type = $('#delivery_time_type').val();

    if (type === 'user_select') {
        var selectedRange = $('input[name="user_select_ranges"]:checked').val();

        if (!selectedRange) {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'خطا',
                text: 'لطفا یک بازه زمانی را انتخاب کنید'
            });
            return;
        }
    }

    return true;
});

// ========== اجرای اولیه ==========
toggleDeliveryTimeFields();
