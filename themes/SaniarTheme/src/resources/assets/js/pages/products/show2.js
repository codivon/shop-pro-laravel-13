$(document).ready(function () {

    function writeColorsName() {
        $('.ui-variant-shape[checked]').each(function (index, item) {
            $('#' + $(item).data('group-id')).text($(item).data('name'));
        });
    }

    writeColorsName();


    $('#CommentsTab .filter-items.nav.nav-tabs li.nav-item').click(function () {
        var GetSortComment= $(this).attr('data-id');
        $('input[name=sortComment]').val(GetSortComment);
        fetch_comments_products(1);
    })

    $('#Newscomments .paginationPager ul.pagination li.page-item.next-item .page-link').append('<i class="fa fa-angle-double-left"></i>')
    $('#Newscomments .paginationPager ul.pagination li.page-item.next-item:last').prepend('<div class="pager-items-partition"></div>')

    $('#Newscomments .paginationPager ul.pagination li.page-item.prev-item .page-link').append('<i class="fa fa-angle-double-right"></i>')
    $('#Newscomments .paginationPager ul.pagination li.page-item.prev-item:last').append('<div class="pager-items-partition"></div>')

    $('#CommentsTab .paginationPager a').on('click', function(e) {
        e.preventDefault();
        var pageNumber=$(this).attr('href').split('page=')[1];
        //$(this).off("click").attr('href', "javascript: void(0);");
        fetch_comments_products(pageNumber);
    });

    function fetch_comments_products(pageNumber)
    {
        block('#myTabContent');
        var product_id=$('.footer-product-id').attr('data-id');
        var sortComment=$('input[name=sortComment]').val();
        var url=$('input[name=UrlGetComment]').val()+'?page='+pageNumber+'&product_id='+product_id+'&sortComment='+sortComment;
        $.ajax({
            url: url,
            success: function (data) {
                $('#Newscomments').html(data);
            },
            complete: function () {
                unblock('#myTabContent');
                $('#Newscomments .paginationPager ul.pagination li.page-item.next-item .page-link').append('<i class="fa fa-angle-double-left"></i>')
                $('#Newscomments .paginationPager ul.pagination li.page-item.next-item:last').prepend('<div class="pager-items-partition"></div>')
                $('#Newscomments .paginationPager ul.pagination li.page-item.prev-item .page-link').append('<i class="fa fa-angle-double-right"></i>')
                $('#Newscomments .paginationPager ul.pagination li.page-item.prev-item:last').append('<div class="pager-items-partition"></div>')


                $('#CommentsTab  .paginationPager a').on('click', function(e) {
                    e.preventDefault();
                    var pageNumber=$(this).attr('href').split('page=')[1];
                    //$(this).off("click").attr('href', "javascript: void(0);");
                    fetch_comments_products(pageNumber);
                });

                $('.comments-likes button').on('click', function (e) {
                    let btn = $(this);

                    $.ajax({
                        url: $(this).data('action'),
                        type: 'POST',
                        success: function (data) {
                            btn.closest('.comments-likes')
                                .find('.likes-count')
                                .attr('data-counter',data.review.likes_count);

                            btn.closest('.comments-likes')
                                .find('.dislikes-count')
                                .attr('data-counter',data.review.dislikes_count);
                        },

                        beforeSend: function (xhr) {
                            block(btn);
                            xhr.setRequestHeader(
                                'X-CSRF-TOKEN',
                                $('meta[name="csrf-token"]').attr('content')
                            );
                        },
                        complete: function () {
                            unblock(btn);
                        }
                    });
                });

            },
            cache: false,
            contentType: false,
            processData: false
        });

    }


});

$(document).on('click', '.add-to-cart', function () {
    var btn = this;
    var groups = [];

    $('.product-info-block input:checked').each(function (index, el) {
        groups.push($(el).val());
    });

    $.ajax({
        type: 'POST',
        url: $(btn).data('action'),
        data: {
            quantity: $('#cart-quantity').val(),
            price_id: $(btn).data('price_id')
        },
        success: function (data) {
            if (data.status == 'success') {
              /*  Swal.fire({
                    type: 'success',
                    title: 'با موفقیت اضافه شد',
                    text: 'محصول مورد نظر با موفقیت به سبد خرید شما اضافه شد برای رزرو محصول سفارش خود را نهایی کنید.',
                    confirmButtonText: 'باشه',
                    footer: '<h5><a href="/cart">مشاهده سبد خرید</a></h5>'
                });*/
                showCustomToast('با موفقیت به سبد اضافه شد','success');
                $('#cart-list-item').replaceWith(data.cart);
            } else {
                Swal.fire({
                    type: 'error',
                    title: 'خطا',
                    text: data.message,
                    confirmButtonText: 'باشه',
                    footer: '<h5><a href="/cart">مشاهده سبد خرید</a></h5>'
                });
            }
        },
        beforeSend: function (xhr) {
            xhr.setRequestHeader(
                'X-CSRF-TOKEN',
                $('meta[name="csrf-token"]').attr('content')
            );
            block(btn);
        },
        complete: function () {
            unblock(btn);
        }
    });
});

$('#stock_notify_btn').click(function () {
    var btn = this;

    if ($(btn).data('user')) {
        sendStockNotify();
    } else {
        $('#modal-stock-notify').modal('show');
    }
});

function sendStockNotify() {
    var btn = $('#stock_notify_btn');

    if ($(btn).data('user')) {
        var data = {
            product_id: $(btn).data('product')
        };
    } else {
        var data = {
            product_id: $(btn).data('product'),
            name: $('#stock-name').val(),
            mobile: $('#stock-mobile').val()
        };
    }

    $.ajax({
        type: 'POST',
        url: BASE_URL + '/stock-notify',
        data: data,
        success: function (data) {
            showCustomToast('نام شما در لیست اطلاع از موجودی این محصول قرار گرفت.','success')
        },
        beforeSend: function (xhr) {
            xhr.setRequestHeader(
                'X-CSRF-TOKEN',
                $('meta[name="csrf-token"]').attr('content')
            );
            block(btn);
        },
        complete: function () {
            unblock(btn);
        }
    });
}

$('#sendStockNotifyBtn').click(sendStockNotify);

// product prices js codes

//-------------------------- Add to favorites
$(document).on('click', '#add-to-favorites', function () {
    var btn = this;

    $.ajax({
        type: 'POST',
        url: $(btn).data('action'),
        data: {
            product_id: $(btn).data('product')
        },
        success: function (data) {
            showCustomToast('با موفقیت انجام شد','success')
            if (data.action == 'create') {
                $(btn).addClass('favorites');
                $(btn).parent().find('span').text('حذف از علاقمندی ها');
            } else {
                $(btn).removeClass('favorites');
                $(btn).parent().find('span').text('افزودن به علاقمندی ها');
            }
        },
        beforeSend: function (xhr) {
            xhr.setRequestHeader(
                'X-CSRF-TOKEN',
                $('meta[name="csrf-token"]').attr('content')
            );
            block('#add-to-favorites');
        },
        complete: function () {
            unblock('#add-to-favorites');
        }
    });
});

//-------------------------- tabs
$(document).ready(function () {
    $('.tabs-product-info .ah-tab-item:first').trigger('click');
});

$('#price-changes-modal').on('show.bs.modal', function (e) {
    if (!$(this).find('.chart-prices-label label.active').length) {
        setTimeout(() => {
            $(this).find('.chart-prices-label label').first().trigger('click');
        }, 100);
    }
});

$('.chart-prices-label label').on('click', function () {
    if ($(this).hasClass('active')) {
        return;
    }

    $('#selected-chart-price-title').text($(this).data('title'));

    $('.chart-prices-label label').removeClass('active');
    $(this).addClass('active');

    var action = $(this).data('action');

    $.ajax({
        url: action,
        type: 'GET',
        success: function (data) {
            data = data.data;

            var categories = [];
            var discountPrices = [];
            var realPrices = [];
            var discounts = [];

            for (const [key, value] of Object.entries(data)) {
                categories.push(value.date);
                discountPrices.push(value.discount_price);
                discounts.push(value.discount);

                if (
                    value.discount_price == value.price &&
                    (data[key - 1] == undefined ||
                        data[key - 1].discount_price == data[key - 1].price) &&
                    (data[parseInt(key) + 1] == undefined ||
                        data[parseInt(key) + 1].discount_price ==
                        data[parseInt(key) + 1].price)
                ) {
                    realPrices.push(null);
                } else {
                    realPrices.push(value.price);
                }
            }

            renderPriceChart(
                discountPrices.reverse(),
                realPrices.reverse(),
                discounts.reverse(),
                categories.reverse()
            );
        },

        beforeSend: function (xhr) {
            block('#price-changes-modal .modal-dialog');
        },
        complete: function () {
            unblock('#price-changes-modal .modal-dialog');
        },
        contentType: false,
        processData: false
    });
});

var chart;

//---------------------- modal
function renderPriceChart(discountPrices, realPrices, discounts, categories) {
    if (discountPrices.every((element) => element === null)) {
        $('#chart').hide();
        $('#empty-chart').show();
        return;
    }

    $('#chart').show();
    $('#empty-chart').hide();

    var options = {
        series: [
            {
                name: 'با تخفیف',
                data: discountPrices
            },
            {
                name: 'بدون تخفیف',
                data: realPrices
            }
        ],
        chart: {
            height: 350,
            type: 'line',
            zoom: {
                enabled: false
            },
            toolbar: {
                show: false
            },
            fontFamily: 'iranyekan'
        },

        tooltip: {
            custom: function ({series, seriesIndex, dataPointIndex, w}) {
                if (!series[0][dataPointIndex]) {
                    return '';
                }

                if (discounts[dataPointIndex]) {
                    var discountTemplate = `<div><del>${number_format(
                        series[1][dataPointIndex]
                    )}</del> <span class="chart-tooltip-discount">${
                        discounts[dataPointIndex]
                    }%</span></div>`;
                } else {
                    var discountTemplate = ``;
                }

                return `<div class="chart-tooltip-container">
                    <div class="chart-tooltip-title ml-3">کمترین قیمت:</div>
                    <div class="chart-tooltip-prices">
                        ${discountTemplate}
                        <div class="mt-1"><strong>${number_format(
                    series[0][dataPointIndex]
                )}</strong> <small> تومان </small></div>
                    </div>
                </div>`;
            }
        },
        stroke: {
            width: [5, 4],
            curve: 'straight',
            dashArray: [0, 5]
        },
        grid: {
            row: {
                colors: ['#f3f3f3', 'transparent'],
                opacity: 0.5
            }
        },
        xaxis: {
            categories: categories,
            labels: {
                rotate: 0,
                rotateAlways: false,
                formatter: function (value, timestamp, opts) {
                    if (
                        categories[0] == value ||
                        categories[9] == value ||
                        categories[19] == value ||
                        categories[29] == value
                    ) {
                        return value;
                    }

                    return '';
                }
            },
            tooltip: {
                formatter: function (value, timestamp, opts) {
                    return categories[value - 1];
                }
            }
        },
        colors: ['#00bfd6', '#cdcdcd'],
        markers: {
            size: [4, 0]
        },
        yaxis: {
            labels: {
                formatter: (value) => {
                    if (value == null) {
                        return '';
                    }
                    return number_format(value);
                }
            }
        }
    };

    if (chart == undefined) {
        chart = new ApexCharts(document.querySelector('#chart'), options);
        chart.render();
    } else {
        chart.destroy();
        chart = new ApexCharts(document.querySelector('#chart'), options);
        chart.render();
    }
}

// product review js codes

$(document).ready(function () {
    var inputs = $('#advantage-input, #disadvantage-input');
    var inputChangeCallback = function () {
        var self = $(this);
        if (self.val().trim().length > 0) {
            self.siblings('.js-icon-form-add').show();
        } else {
            self.siblings('.js-icon-form-add').hide();
        }
    };

    inputs.each(function () {
        inputChangeCallback.bind(this)();
        $(this).on('change keyup', inputChangeCallback.bind(this));
    });

    $('#advantages')
        .delegate('.js-icon-form-add', 'click', function (e) {
            var parent = $('.js-advantages-list');
            if (parent.find('.js-advantage-item').length >= 5) {
                return;
            }

            var advantageInput = $('#advantage-input');

            if (advantageInput.val().trim().length > 0) {
                parent.append(
                    `<div class="ui-dynamic-label ui-dynamic-label--positive js-advantage-item">${advantageInput.val()}
                        <button type="button" class="ui-dynamic-label-remove js-icon-form-remove"></button>
                        <input type="hidden" name="review[advantages][]" value="${advantageInput.val()}">
                    </div>`
                );

                advantageInput.val('').change();
                advantageInput.focus();
            }
        })
        .delegate('.js-icon-form-remove', 'click', function (e) {
            $(this).parent('.js-advantage-item').remove();
        });

    $('#disadvantages')
        .delegate('.js-icon-form-add', 'click', function (e) {
            var parent = $('.js-disadvantages-list');
            if (parent.find('.js-disadvantage-item').length >= 5) {
                return;
            }

            var disadvantageInput = $('#disadvantage-input');

            if (disadvantageInput.val().trim().length > 0) {
                parent.append(
                    `<div class="ui-dynamic-label ui-dynamic-label--negative js-disadvantage-item">${disadvantageInput.val()}
                        <button type="button" class="ui-dynamic-label-remove js-icon-form-remove"></button>
                        <input type="hidden" name="review[disadvantages][]" value="${disadvantageInput.val()}">
                    </div>`
                );

                disadvantageInput.val('').change();
                disadvantageInput.focus();
            }
        })
        .delegate('.js-icon-form-remove', 'click', function (e) {
            $(this).parent('.js-disadvantage-item').remove();
        });

    $('#advantage-input').on('keydown', function (event) {
        if (event.which === 13) {
            $('#advantages .js-icon-form-add').trigger('click');
            event.preventDefault();
        }
    });
    $('#disadvantage-input').on('keydown', function (event) {
        if (event.which === 13) {
            $('#disadvantages .js-icon-form-add').trigger('click');
            event.preventDefault();
        }
    });

    $('.product-review-rate input').on('change', function () {
        $('#selected-rating-text').text($(this).data('title'));
    });

    $('#add-product-review-form').on('submit', function (e) {
        e.preventDefault();
        var formData = new FormData(this);

        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: formData,
            success: function (data) {
                Swal.fire({
                    text: 'نظر شما با موفقیت ثبت شد و پس از تایید مدیر نمایش داده خواهد شد.',
                    type: 'success',
                    showCancelButton: false,
                    confirmButtonText: 'باشه'
                }).then((result) => {
                    var pathname = window.location.pathname;
                    if(pathname=="/profile/comments"){
                        window.location.reload();
                    }
                });

                $('#add-product-review-form').trigger('reset');
                $('.js-icon-form-remove').trigger('click');
                $('#add-product-review-modal').modal('hide');
                $('#edit-product-review-modal').modal('hide');

            },

            beforeSend: function (xhr) {
                block('#add-product-review-form');

                xhr.setRequestHeader(
                    'X-CSRF-TOKEN',
                    $('meta[name="csrf-token"]').attr('content')
                );
            },
            complete: function () {
                unblock('#add-product-review-form');

            },

            cache: false,
            contentType: false,
            processData: false
        });
    });

    $('#add-product-review-modal').on('show.bs.modal', function () {

        $('.js-advantages-list').empty();
        $('.js-disadvantages-list').empty();
        $('#advantage-input').val('');
        $('#disadvantage-input').val('');
        $('.slider-tick-label-container .slider-tick-label').css('width','64.4px')
        $('.slider-tick-label-container .slider-tick-label:first-child').css('width','30px')



        $.ajax({
            url: $(this).data('action'),
            type: 'GET',
            success: function (data) {
                let review = data.review;

                if (review) {
                    $('#add-product-review-form').find('input[name="title"]').val(review.title);
                    $('#add-product-review-form').find(`input[name="rating"][value="${review.rating}"]`).prop('checked', true);
                    $('#add-product-review-form').find(`input[name="rating"]`).attr('data-value',review.rating);
                    $('#add-product-review-form').find(`input[name="rating"]`).attr('value',review.rating);
                    $('#add-product-review-form').find(`.slider-handle.min-slider-handle.round`).attr('aria-valuenow',review.rating);
                    var rate_Percentage=(parseInt(100)/parseInt(5))*parseInt(review.rating);
                    $('#add-product-review-form').find(`.slider-handle.min-slider-handle.round`).css('right',rate_Percentage+'%');
                    $('#add-product-review-form').find(`.slider-handle.min-slider-handle.round`).attr('aria-valuenow',rate_Percentage);
                    $('#add-product-review-form').find(`.tooltip.tooltip-main.top`).css('right',rate_Percentage+'%');
                    $('#add-product-review-form').find(`.slider-selection.tick-slider-selection`).css('width',rate_Percentage+'%');
                    $('#add-product-review-form').find('textarea[name="body"]').val(review.body);

                    $('#add-product-review-form #review-suggest-'+review.suggest).prop('checked', true);
                    $('#add-product-review-form #review-suggest-'+review.suggest).parent().find('.review-suggest-item').addClass('active')

                    review.points.forEach(function (item) {
                        if(item.type == 'positive') {
                            $('#advantage-input').val(item.text);
                            $('#advantages .js-icon-form-add').trigger('click');
                        } else {
                            $('#disadvantage-input').val(item.text);
                            $('#disadvantages .js-icon-form-add').trigger('click');
                        }
                    });
                }
            },
        });

        $('.product-offer-question .review-suggest-item').click(function () {
            var item=this;
            $('.product-offer-question .review-suggest-item').removeClass('active');
            $(item).addClass('active');
        })
    });

    $('.comments-likes button').on('click', function (e) {
        let btn = $(this);

        $.ajax({
            url: $(this).data('action'),
            type: 'POST',
            success: function (data) {
                btn.closest('.comments-likes')
                    .find('.likes-count')
                    .attr('data-counter',data.review.likes_count);

                btn.closest('.comments-likes')
                    .find('.dislikes-count')
                    .attr('data-counter',data.review.dislikes_count);
            },

            beforeSend: function (xhr) {
                block(btn);
                xhr.setRequestHeader(
                    'X-CSRF-TOKEN',
                    $('meta[name="csrf-token"]').attr('content')
                );
            },
            complete: function () {
                unblock(btn);
            }
        });
    });
});

$('.copy-text-btn').on('click', function () {
    var copyText = document.getElementById('shareLink');
    copyText.select();
    copyText.setSelectionRange(0, 99999);
    navigator.clipboard.writeText(copyText.value);

    $('.copy-text-btn')
        .tooltip('hide')
        .attr('data-original-title', 'کپی شد')
        .tooltip('show');

    setTimeout(function () {
        $('.copy-text-btn')
            .tooltip('hide')
            .attr('data-original-title', 'کپی لینک')
            .tooltip('show');
    }, 1000);
});

if ($('#product-special-end-date').length) {
    // Set the date we're counting down to
    var countDownDate = new Date(
        $('#product-special-end-date').data('date')
    ).getTime();

    // Update the count down every 1 second
    var x = setInterval(function () {
        // Get today's date and time
        var now = new Date().getTime();

        // Find the distance between now and the count down date
        var distance = countDownDate - now;

        // Time calculations for days, hours, minutes and seconds
        var days = Math.floor(distance / (1000 * 60 * 60 * 24));
        var hours = Math.floor(
            (distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60)
        );
        var minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
        var seconds = Math.floor((distance % (1000 * 60)) / 1000);

        $('#product-special-end-date').find('[data-days]').text(days);
        $('#product-special-end-date').find('[data-hours]').text(hours);
        $('#product-special-end-date').find('[data-minutes]').text(minutes);
        $('#product-special-end-date').find('[data-seconds]').text(seconds);

        // If the count down is over, write some text
        if (distance < 0) {
            clearInterval(x);
            $('#product-special-end-date');
        }
    }, 1000);
}




let cachedStores = {};
let ajaxInProgress = false;

// دریافت اطلاعات اولیه از المنت مخفی
var productStoresInfo = $('#product-stores-info');
var initialStoresCount = productStoresInfo.data('initial-stores') || 0;
var firstColorId = productStoresInfo.data('first-color-id');

// تنظیم اولیه count_stores
if (initialStoresCount > 0) {
    $('.count_stores').html(initialStoresCount);
    $('.box-suppliers-headline-container').removeClass('d-none');
} else if (firstColorId) {
    // اگر مقدار اولیه صفر بود ولی رنگ داشت، بعداً بروزرسانی میشه
    $('.count_stores').html('0');
}

$(document).on('click', '.product-attribute:not(.unavailable)', function () {
    var input = $(this).find('input');
    var product = input.data('product');
    var groups = [];

    $('.product-info-block input:checked').each(function (index, el) {
        groups.push($(el).val());
    });

    // جلوگیری از درخواست‌های همزمان
    if (ajaxInProgress) return;
    ajaxInProgress = true;

    // تغییر کلاس لودینگ
    $('.product-seller-info').addClass('loading-skeleton');

    $.ajax({
        type: 'GET',
        url: BASE_URL + '/product/' + product + '/prices',
        data: { groups: groups },
        success: function (data) {
            $('.product-info-block').replaceWith(data);

            // بعد از لود، فروشنده‌ها رو بروزرسانی کن
            setTimeout(function() {
                updateStoresByColor();
            }, 100);
        },
        error: function(xhr) {
            console.error('Error loading product data:', xhr);
        },
        complete: function () {
            ajaxInProgress = false;
            $('.product-seller-info').removeClass('loading-skeleton');
            unblock('.product-info');
        }
    });
});

// تابع جداگانه برای بروزرسانی فروشنده‌ها با بهینه‌سازی
// تابع بروزرسانی فروشندگان بر اساس رنگ انتخاب شده
function updateStoresByColor() {
    if (multi_vendor_system_status == 'false') return;

    var checkedInput = $('.product-variants input:checked').first();
    if (!checkedInput.length) return;

    var color_id = checkedInput.val();
    var product_id = $('.footer-product-id').attr('data-id');

    if (!product_id) {
        product_id = $('#product-stores-info').data('product-id');
    }

    if (!product_id || !color_id) return;

    var cacheKey = product_id + '_' + color_id;

    // چک کردن کش
    if (cachedStores[cacheKey]) {
        renderStores(cachedStores[cacheKey]);
        return;
    }

    // نمایش لودینگ
    $('.table-suppliers-store').html('<div class="text-center p-3"><i class="mdi mdi-loading mdi-spin"></i> در حال بارگذاری...</div>');

    $.ajax({
        type: 'POST',
        url: BASE_URL + '/product/get-stores',
        data: {
            _token: $('meta[name="csrf-token"]').attr('content'),
            product_id: product_id,
            color_id: color_id
        },
        dataType: 'json',
        success: function (response) {
            if (response.has_stores) {
                cachedStores[cacheKey] = response;
                renderStores(response);
            } else {
                $('.table-suppliers-store').html('<div class="text-center p-3 text-muted">هیچ فروشنده‌ای برای این رنگ یافت نشد</div>');
                updateSellerCountDisplay(0, false);
            }
        },
        error: function(xhr) {
            console.log('Error loading stores:', xhr);
            $('.table-suppliers-store').html('<div class="text-center p-3 text-danger">خطا در بارگذاری فروشندگان</div>');
            updateSellerCountDisplay(0, false);
        }
    });
}


// تابع بروزرسانی نمایش تعداد فروشندگان در سایدبار
function updateSellerCountDisplay(totalStores, hasSiteStore) {
    var $sellerRow = $('#seller-count-row');
    var $sellerCountSpan = $('.count_stores_display');
    var $sellerTextSpan = $('.js-seller-text');

    if (!$sellerRow.length) return;

    // محاسبه تعداد فروشندگان دیگر (بدون احتساب خود سایت)
    var otherSellersCount = totalStores;

    if (hasSiteStore === undefined) {
        hasSiteStore = $('.has_site_store').val() == '1';
    }

    if (hasSiteStore && totalStores > 0) {
        otherSellersCount = totalStores - 1;
    }

    if (totalStores === 0) {
        // هیچ فروشنده‌ای ندارد - مخفی کردن کل ردیف
        $sellerRow.hide();
        $('.box-suppliers-headline-container').addClass('d-none');
    } else if (totalStores === 1 && hasSiteStore) {
        // فقط خود سایت دارد - نمایش "فروشنده او پی شاپ"
        $sellerRow.show();
        $sellerCountSpan.html(option('info_site_title', 'او پی شاپ'));
        $sellerTextSpan.html('');
        $('.box-suppliers-headline-container').removeClass('d-none');
    } else if (totalStores === 1 && !hasSiteStore) {
        // فقط یک فروشنده دیگر دارد
        $sellerRow.show();
        $sellerCountSpan.html('');
        $sellerTextSpan.html('1 فروشنده');
        $('.box-suppliers-headline-container').removeClass('d-none');
    } else {
        // بیشتر از یک فروشنده
        $sellerRow.show();
        if (otherSellersCount > 0) {
            $sellerCountSpan.html(otherSellersCount);
            $sellerTextSpan.html('فروشنده دیگر');
        } else {
            $sellerCountSpan.html(totalStores);
            $sellerTextSpan.html('فروشنده');
        }
        $('.box-suppliers-headline-container').removeClass('d-none');
    }
}

// تابع رندر کردن فروشندگان
function renderStores(response) {
    var $storesContainer = $('.table-suppliers-store');

    if (!$storesContainer.length) return;

    if (response.html && response.has_stores) {
        $storesContainer.html(response.html);

        // دریافت اطلاعات از المنت‌های مخفی
        var totalStores = response.total_stores;
        var hasSiteStore = $storesContainer.find('.has_site_store').val() == '1';

        // بروزرسانی نمایش در سایدبار
        updateSellerCountDisplay(totalStores, hasSiteStore);

        // اگر فقط یک فروشنده وجود دارد و آن هم سایت اصلی است،
        // ستون عنوان را به درستی نمایش بده
        if (totalStores === 1 && hasSiteStore) {
            $('.table-suppliers-row .table-suppliers-cell-title .seller-wrapper p a').each(function() {
                var text = $(this).text();
                if (text === option('info_site_title', 'او پی شاپ')) {
                    // درسته، همین باشه
                }
            });
        }

        // نمایش/مخفی کردن دکمه "مشاهده بیشتر"
        if (totalStores > 2) {
            $('.table-suppliers-more').show();
        } else {
            $('.table-suppliers-more').hide();
        }

    } else {
        $storesContainer.html('<div class="text-center p-3 text-muted">هیچ فروشنده‌ای برای این ویژگی یافت نشد</div>');
        updateSellerCountDisplay(0, false);
        $('.box-suppliers-headline-container').addClass('d-none');
    }
}
function updateCountStoresDisplay(count) {
    var $sellerRow = $('#seller-count-row');
    var $countStores = $('.count_stores');
    var $sellerCountText = $('.seller-count-text');

    if (count == 0) {
        $sellerRow.hide();
        $('.box-suppliers-headline-container').addClass('d-none');
    } else {
        $sellerRow.show();
        $countStores.html(count);

        if (count == 1) {
            $sellerCountText.html('فروشنده');
        } else {
            $sellerCountText.html('فروشنده دیگر');
        }
        $('.box-suppliers-headline-container').removeClass('d-none');
    }
}
// بهینه‌سازی تابع get_info_attr_product
function get_info_attr_product() {
    $('.mini-warrantyes').empty();

    $('.product-seller-row-guarantee').each(function (index, el) {
        var text = $(el).find('.js-guarantee-text').text();
        if (text) {
            $('.mini-warrantyes').append('<div class="mini-buy-box-row mini-buy-box-warranty"><i class="mdi mdi-check"></i>' + text + '</div>');
        }
    });

    $('.colors').empty();
    $('.product-variants input:checked').each(function (index, el) {
        var $parent = $(el).parent();
        var colorname = $parent.find('.ui-variant-shape').attr('data-colorname');
        var color = $parent.find('.ui-variant-shape').attr('style');
        if (colorname) {
            $('.colors').append('<label data-color-code="#FFFFFF" class="js-variant-color" style="' + color + '"></label><span class="js-color-title">' + colorname + '</span>');
        }
    });

    var price = $('#price-real .product-seller-price-prev').text();
    var data_price_id = $('.product-seller-info .product-seller-row button').attr('data-price_id');

    $('.product-mini-seller-price-real .js-price-value').text(price);
    $('.mini-buy-box-btn-row button').attr('data-price_id', data_price_id);
}

// تابع showStores بهینه شده
function showStores() {
    if (multi_vendor_system_status == 'false') return;

    requestAnimationFrame(function() {
        updateStoresByColor();
    });
}

// بارگذاری اولیه - اگر رنگ پیش‌فرض وجود داشت، فروشندگان آن را بارگذاری کن
$(document).ready(function() {
    var checkedColor = $('.variant-selector:checked').first();
    if (checkedColor.length) {
        setTimeout(function() {
            updateStoresByColor();
        }, 500);
    } else {
        // اگر رنگی انتخاب نشده بود، خط فروشندگان رو مخفی کن
        $('#seller-count-row').hide();
    }
});

// اضافه کردن اسکلتون لودینگ به CSS
if (!document.querySelector('#stores-loading-style')) {
    var style = document.createElement('style');
    style.id = 'stores-loading-style';
    style.textContent = `
        .loading-skeleton {
            position: relative;
            min-height: 200px;
        }
        .loading-skeleton::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: loading 1.5s infinite;
            border-radius: 8px;
            z-index: 1;
        }
        @keyframes loading {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }
    `;
    document.head.appendChild(style);
}
