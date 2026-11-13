// ==================== متغیرهای سراسری ====================
let isUpdatingPrice = false;
let isFirstLoad = true; // مشخص می‌کند اولین بار است یا نه

// ==================== تابع به‌روزرسانی کلاس رادیوها ====================
function updateRadioActiveClass() {

    // کلاس‌های فعال همه رادیوها را حذف می‌کنیم
    $('.custom-radio').removeClass('custom-radio--active');
    $('.carriers-select-address i').removeClass('fa-check').addClass('fa-circle-dot');

    // برای هر مرسوله، رادیو چک‌شده آن گروه را پیدا می‌کنیم
    $('.consignment-container .consignments').each(function() {
        const $consignment = $(this);
        const groupId = $consignment.data('group-id');

        // پیدا کردن رادیو چک شده در این مرسوله
        let $checkedRadio = $consignment.find('input[name="carrier_id_' + groupId + '"]:checked');
        // اگر هیچ رادیویی چک نشده بود، اولین رادیو را چک کن
        /*if (!$checkedRadio.length) {
            const $firstRadio = $consignment.find('input[name="carrier_id_' + groupId + '"]').first();
            if ($firstRadio.length) {
                $firstRadio.prop('checked', true);
                $checkedRadio = $firstRadio;
            }
        }*/

        // حالا اگر رادیویی چک شده بود، کلاس فعال را اضافه کن
        if ($checkedRadio.length) {
            const $customRadio = $checkedRadio.closest('.custom-radio');
            $customRadio.addClass('custom-radio--active');
            $customRadio.find('.carriers-select-address i').removeClass('fa-circle-dot').addClass('fa-check');
        }
    });
}




// ==================== تابع دریافت قیمت نهایی ====================
function getFinalPrice(shouldUpdateClass = true,idGroupSelect=null) {
    // جلوگیری از اجرای همزمان
    if (isUpdatingPrice) {
        return;
    }
    block('.block-div');
    block('.checkout-summary');
    // ========== بررسی وجود محصولات فیزیکی ==========
    // اگر هیچ مرسوله‌ای (محصول فیزیکی) وجود نداشته باشد
    if ($('.consignment-container .consignments').length === 0) {
        // فقط سایدبار را به‌روزرسانی کن (برای نمایش قیمت دانلودی‌ها)
        if (shouldUpdateClass || isFirstLoad) {
            updateSidebarOnly();
        }
        return;
    }

    // جمع‌آوری اطلاعات carrier_id برای همه گروه‌ها
    let carriersData = {};
    let hasAnyCarrier = false;

    $('.consignment-container .consignments').each(function() {
        const $consignment = $(this);
        const groupId = $consignment.data('group-id');
        const $checkedRadio = $consignment.find('input[name="carrier_id_' + groupId + '"]:checked');

        if ($checkedRadio.length) {
            let carrierKey = 'carrier_id_' + groupId;
            carriersData[carrierKey] = $checkedRadio.val();
            hasAnyCarrier = true;
        }
    });
    // اگر هیچ حاملی در هیچ گروهی وجود نداشت
    if (!hasAnyCarrier) {
        unblock('.block-div');
        unblock('.checkout-summary');
        return;
    }

    var city_id = $('#address-section .user-address-item.active-address').data('city');
    var action = $('#address-section').data('action');

    // اگر شهر انتخاب نشده بود
    if (!city_id) {
        unblock('.block-div');
        unblock('.checkout-summary');
        return;
    }

    isUpdatingPrice = true;

    $('#send-period-container-'+idGroupSelect).html(null)
    $.ajax({
        url: action,
        type: 'GET',
        data: {
            city_id: city_id,
            carriers: carriersData,
            idGroupSelect:idGroupSelect,
            is_first_load: isFirstLoad
        },
        success: function (data) {
            // فقط در صورت نیاز به‌روزرسانی انجام بده

            if (shouldUpdateClass || isFirstLoad) {
                $('#checkout-carrier-container').replaceWith(data.carriers_container);
                $('#checkout-sidebar').replaceWith(data.checkout_sidebar);
                $('[data-toggle="tooltip"]').tooltip();

                if ($('.container .sticky-sidebar').length) {
                    $('.container .sticky-sidebar').theiaStickySidebar();
                }
            }

            if (data.deliveryDateForOne){
                $('#send-period-container-'+idGroupSelect).html(data.deliveryDateForOne)

                $('.delivery-Date').click(function() {
                    var $radio = $(this);
                    $radio.closest('.days-widget').find('label').removeClass('active');
                    $radio.closest('.days-widget').find('label i').removeClass('fa-check');
                    $radio.parent().find('label').addClass('active');
                    $radio.parent().find('label i').addClass('fa-check');
                });
            }

            check_wallet();

            // به‌روزرسانی کلاس‌های فعال (با تاخیر مطمئن)
            setTimeout(function() {
                $('.custom-radio').removeClass('custom-radio--active');
                $('.carriers-select-address i').removeClass('fa-check').addClass('fa-circle-dot');

                $('.consignment-container .consignments').each(function() {
                    const $consignment = $(this);
                    const groupId = $consignment.data('group-id');
                    const carrierKey = 'carrier_id_' + groupId;
                    const selectedValue = carriersData[carrierKey];

                    if (selectedValue) {
                        const $checkedRadio = $consignment.find('input[name="carrier_id_' + groupId + '"][value="' + selectedValue + '"]');
                        if ($checkedRadio.length) {
                            const $customRadio = $checkedRadio.closest('.custom-radio');
                            $customRadio.addClass('custom-radio--active');
                            $customRadio.find('.carriers-select-address i').removeClass('fa-circle-dot').addClass('fa-check');
                        }
                    }
                });
            }, 50);
            // بعد از اولین بار، flag را false کن
            isFirstLoad = false;
        },
        complete: function() {
            isUpdatingPrice = false;
            unblock('.block-div');
            unblock('.checkout-summary');
        },
        beforeSend: function(xhr) {
            xhr.setRequestHeader("X-CSRF-TOKEN", $('meta[name="csrf-token"]').attr('content'));
            if (!isFirstLoad) {
                block('.block-div');
                block('.checkout-summary');
            }
        },
        error: function(xhr, status, error) {
            console.error('Error in getFinalPrice:', error);
            isUpdatingPrice = false;
            unblock('.block-div');
            unblock('.checkout-summary');
            isFirstLoad = false;
        }
    });

}

// ==================== تابع به‌روزرسانی فقط سایدبار (برای محصولات دانلودی) ====================
function updateSidebarOnly() {
    // فقط سایدبار را به‌روزرسانی کن (بدون نیاز به city_id و carrier)
    var action = $('#address-section').data('action');

    $.ajax({
        url: action,
        type: 'GET',
        data: {
            only_sidebar: true, // پارامتر جدید برای تشخیص
            is_first_load: isFirstLoad
        },
        success: function (data) {
            if (data.checkout_sidebar) {
                $('#checkout-sidebar').replaceWith(data.checkout_sidebar);
            }
            check_wallet();
            isFirstLoad = false;
        },
        error: function(xhr, status, error) {
            console.error('Error in updateSidebarOnly:', error);
            isFirstLoad = false;
        }
    });
}


// فقط بعد از لود کامل صفحه، یک بار اجرا شود
$(document).ready(function () {
    // مرحله 1: تنظیم کلاس‌های فعال
    updateRadioActiveClass();

    // مرحله 2: محاسبه اولیه قیمت (فقط در صورت وجود محصول فیزیکی)
    setTimeout(function() {
        const hasPhysical = $('.consignment-container .consignments').length > 0;
        if (hasPhysical) {
            if ($('#address-section .user-address-item.active-address').data('city')) {
                getFinalPrice(true);
            }
        } else {
            // فقط سایدبار را به‌روزرسانی کن
            updateSidebarOnly();
        }
    }, 100);

    // مرحله 3: تنظیم سایر توابع
    check_wallet();
});

$(document).on('change', 'input[name^="carrier_id_"]', function() {
    const hasPhysical = $('.consignment-container .consignments').length > 0;
    if (!hasPhysical) {
        return; // اگر فقط دانلودی است، کاری نکن
    }

    const idGroupSelect = $(this).data('group-id');
    updateRadioActiveClass();
    setTimeout(function() {
        getFinalPrice(true, idGroupSelect);
    }, 50);
});
// رویداد تغییر شهر
$(document).on('change', '#city, #city_id', function () {
    const hasPhysical = $('.consignment-container .consignments').length > 0;
    if (hasPhysical) {
        getFinalPrice(true);
    }
});

// ==================== Validation ====================

// ==================== Validation ====================

jQuery('#checkout-form').validate({
    rules: {
        name: { required: true },
        mobile: {
            required: true,
            regex: '(09)[0-9]{9}'
        },
        postal_code: {
            required: true,
            digits: true,
            maxlength: 10,
            minlength: 10
        },
        province_id: { required: true },
        city_id: { required: true },
        address: { maxlength: 300 },
        description: { maxlength: 1000 }
    },
    submitHandler: function(form) {
        // ========== بررسی وجود محصولات فیزیکی ==========
        const hasPhysical = $('.consignment-container .consignments').length > 0;
        console.log(hasPhysical)
        // اگر فقط محصول دانلودی است، بدون بررسی ارسال کنید
        if (!hasPhysical) {
            form.submit();
            return;
        }

        let allGroupsHaveCarrier = true;

        $('.consignment-container .consignments').each(function() {
            const $consignment = $(this);
            const groupId = $consignment.data('group-id');
            const $checkedRadio = $consignment.find('input[name="carrier_id_' + groupId + '"]:checked');

            if (!$checkedRadio.length) {
                allGroupsHaveCarrier = false;
                showCustomToast('لطفاً روش ارسال را برای همه مرسوله‌ها انتخاب کنید!','error');
                return false;
            }
        });

        if (allGroupsHaveCarrier) {
            form.submit();
        }
    }
});

// بررسی آدرس در submit
jQuery('#checkout-form').on('submit', function(e) {
    // ========== بررسی وجود محصولات فیزیکی ==========
    const hasPhysical = $('.consignment-container .consignments').length > 0;

    // اگر فقط محصول دانلودی است، از بررسی آدرس صرف نظر کن
    if (!hasPhysical) {
        return true;
    }

    var city_id = $('#address-section .user-address-item.active-address').data('city');
    if (!city_id) {
        showCustomToast('آدرس خود را وارد کنید!','error');
        e.preventDefault();
        return false;
    }
});
// ==================== Discount Form ====================

$('#discount-create-form').validate({
    rules: {
        code: { required: true }
    }
});

$.validator.addMethod('regex', function(value, element, regexp) {
    var re = new RegExp(regexp);
    return this.optional(element) || re.test(value);
}, 'لطفا یک مقدار معتبر وارد کنید');

$('#discount-create-form').submit(function (e) {
    e.preventDefault();
    var formData = new FormData(this);

    if ($(this).valid()) {
        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: formData,
            success: function (data) {
                showCustomToast('کد تخفیف با موفقیت ثبت شد','success')
                setTimeout(function () {
                    location.reload();
                }, 1000);
            },
            beforeSend: function (xhr) {
                block('.block-div');
                block('.checkout-summary');
            },
            complete: function () {
                unblock('.block-div');
                unblock('.checkout-summary');
            },
            cache: false,
            contentType: false,
            processData: false
        });
    }
});

// ==================== Wallet ====================

function check_wallet() {
    var finalPrice = parseInt($('#final-price').data('value'));
    var walletBalance = parseInt($('#wallet-balance').data('value'));

    if (isNaN(finalPrice)) finalPrice = 0;
    if (isNaN(walletBalance)) walletBalance = 0;

    if (finalPrice > walletBalance) {
        $('.wallet-select .has-balance').hide();
        $('.wallet-select .increase-balance').show();
    } else {
        $('.wallet-select .has-balance').show();
        $('.wallet-select .increase-balance').hide();
    }
}

// ==================== Address ====================

$('.sett-address').click(function () {
    var item = this;
    $.ajax({
        url: $(item).data('action'),
        type: 'GET',
        success: function(data) {
            if (data.active == "success") {
                $('.user-address-item').removeClass('active-address');
                $('.user-address-item').attr('data-placeholder', 'انتخاب این آدرس');
                $('.user-address-item').find('.icon-address i').addClass('fa-circle-dot').removeClass('fa-check');
                $(item).parents('.user-address-item').addClass('active-address');
                $(item).parent('.custom-radio-box-label').attr('data-placeholder', 'انتخاب شده');
                $(item).parent('.custom-radio-box-label').find('.icon-address i').addClass('fa-check').removeClass('fa-circle-dot');

                const hasPhysical = $('.consignment-container .consignments').length > 0;
                if (hasPhysical) {
                    getFinalPrice(true);
                }
            }
        },
        beforeSend: function(xhr) {
            xhr.setRequestHeader("X-CSRF-TOKEN", $('meta[name="csrf-token"]').attr('content'));
        },
        cache: false,
        contentType: false,
        processData: false
    });
});

$(".mask-handler").click(function (e) {
    e.preventDefault();
    var sumaryBox = $(this).parents('#address-section');
    sumaryBox.find('.checkout-contact').toggleClass('active');
    sumaryBox.find('.shadow-box').fadeToggle(0);
    $(this).find('.show-more').fadeToggle(0);
    $(this).find('.show-less').fadeToggle(0);
});

// ==================== Reserve ====================

$(document).on('change', 'input[name="reserve"]', function () {
    var reserve = $('input[name="reserve"]:checked').val();

    $.ajax({
        url: $('#order-reserve-container').data('action'),
        type: 'GET',
        data: { reserve: reserve },
        success: function (data) {
            window.location.reload();
        },
        beforeSend: function (xhr) {
            block('.block-div');
            block('.checkout-summary');
            xhr.setRequestHeader('X-CSRF-TOKEN', $('meta[name="csrf-token"]').attr('content'));
        },
        complete: function () {
            unblock('.block-div');
            unblock('.checkout-summary');
        }
    });
});
