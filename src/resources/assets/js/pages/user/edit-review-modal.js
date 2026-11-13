/**
 * مودال ویرایش دیدگاه کاربر (user/comments + user/partials/edit-review)
 * انتقال‌یافته از بلید به فایل js (بدون <script> داخل ویو)
 */
(function () {
    'use strict';

    if (!window.jQuery) {
        return;
    }

    var $ = window.jQuery;

    $(function () {
        $('input[name=rating]').val(3);

        $('.add-product-review-modal-btn').on('click', function () {
            var item = this;
            var url = $(item).data('url');
            var productId = $(item).data('productid');
            $('input[name=product_id]').val(productId);

            $('#edit-product-review-modal').on('show.bs.modal', function () {

                $('#advantage-input').val('');
                $('#disadvantage-input').val('');

                $('.slider-tick-label-container .slider-tick-label').css('width', '64.4px');
                $('.slider-tick-label-container .slider-tick-label:first-child').css('width', '30px');

                block('#edit-product-review-modal');
                $.ajax({
                    url: url,
                    type: 'GET',
                    success: function (data) {
                        var review = data.review;
                        $('.js-advantages-list').empty();
                        $('.js-disadvantages-list').empty();
                        if (review) {
                            $('#product_title').text(review.product.title);
                            $('#add-product-review-form').find('input[name="title"]').val(review.title);
                            $('#add-product-review-form').find('input[name="rating"][value="' + review.rating + '"]').prop('checked', true);
                            $('#add-product-review-form').find('input[name="rating"]').attr('data-value', review.rating);
                            $('#add-product-review-form').find('input[name="rating"]').attr('value', review.rating);
                            $('#add-product-review-form').find('.slider-handle.min-slider-handle.round').attr('aria-valuenow', review.rating);
                            var rate_Percentage = (parseInt(100) / parseInt(5)) * parseInt(review.rating);
                            $('#add-product-review-form').find('.slider-handle.min-slider-handle.round').css('right', rate_Percentage + '%');
                            $('#add-product-review-form').find('.slider-handle.min-slider-handle.round').attr('aria-valuenow', rate_Percentage);
                            $('#add-product-review-form').find('.tooltip.tooltip-main.top').css('right', rate_Percentage + '%');
                            $('#add-product-review-form').find('.slider-selection.tick-slider-selection').css('width', rate_Percentage + '%');
                            $('#add-product-review-form').find('textarea[name="body"]').val(review.body);

                            $('#add-product-review-form #review-suggest-' + review.suggest).prop('checked', true);
                            $('#add-product-review-form #review-suggest-' + review.suggest).parent().find('.review-suggest-item').addClass('active');

                            review.points.forEach(function (item) {
                                if (item.type == 'positive') {
                                    $('#advantage-input').val(item.text);
                                    $('#advantages .js-icon-form-add').trigger('click');
                                } else {
                                    $('#disadvantage-input').val(item.text);
                                    $('#disadvantages .js-icon-form-add').trigger('click');
                                }
                            });
                        }

                    },
                    complete: function () {
                        unblock('#edit-product-review-modal');
                    }
                });

                $('.product-offer-question .review-suggest-item').click(function () {
                    var item2 = this;
                    $('.product-offer-question .review-suggest-item').removeClass('active');
                    $(item2).addClass('active');
                });
            });


        });
    });
})();
