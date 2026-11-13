/* ============================================================
   theme-product.js — فاز ۱۵۵ (SaniarTheme)
   صفحهٔ تک‌محصول: به‌روزرسانی لحظه‌ای قیمت با انتخاب تنوع،
   تعویض تصویر گالری (بدون ez-plus)، متن ویجت ستاره‌ای، کپی شناسه.
   اسکریپت در انتهای صفحهٔ محصول (استک scripts) لود می‌شود؛
   همهٔ بایندها delegated هستند تا پس از replaceWith بلوک اطلاعات
   محصول (show.js) زنده بمانند.
   ============================================================ */
(function () {
    'use strict';

    /* ---------------------------------------------------------
       ۱) به‌روزرسانی لحظه‌ای قیمت/تخفیف با انتخاب تنوع
       منبع داده: جزیرهٔ JSON رندرشده در product-info.blade.php
       (#js-product-variants-data) — پس از هر AJAX هم همین پارشال
       جایگزین می‌شود و جزیرهٔ داده تازه می‌ماند.
       --------------------------------------------------------- */

    function readVariantsData() {
        var node = document.getElementById('js-product-variants-data');
        if (!node) return null;
        try {
            var parsed = JSON.parse(node.textContent || '[]');
            return Array.isArray(parsed) && parsed.length ? parsed : null;
        } catch (error) {
            return null;
        }
    }

    function findMatchingPrice(variants, checkedIds) {
        if (!variants || !checkedIds.length) return null;

        for (var i = 0; i < variants.length; i++) {
            var attrs = variants[i].attrs || [];
            var matched = checkedIds.every(function (id) {
                return attrs.indexOf(id) !== -1;
            });

            if (matched) return variants[i];
        }

        return null;
    }

    function applyVariantPriceInstantly() {
        var variants = readVariantsData();
        if (!variants) return;

        var checkedIds = [];
        document.querySelectorAll('.product-info-block input[type="radio"]:checked').forEach(function (input) {
            checkedIds.push(parseInt(input.value, 10));
        });

        var match = findMatchingPrice(variants, checkedIds);
        if (!match) return;

        /* ستون قیمت قبلی/درصد تخفیف (اولین ستون باکس اقدام) */
        var priceBox = document.querySelector('.product-info-block .product-meta-action');
        if (priceBox) {
            var oldPriceCol = priceBox.querySelector('.row > div:first-child');
            if (oldPriceCol) {
                if (match.discount > 0) {
                    oldPriceCol.innerHTML =
                        '<div class="product-seller-price-real text-danger">' +
                        '<del class="product-seller-price-prev font-size-20">' + match.price + '</del>' +
                        ' تومان' +
                        '<div class="discount show-discount"><span>' + match.discount + '٪</span></div>' +
                        '</div>';
                } else {
                    var existingLabel = oldPriceCol.querySelector('.product-seller-price-label');
                    var labelText = existingLabel ? existingLabel.textContent.trim() : 'قیمت';
                    oldPriceCol.innerHTML =
                        '<div class="product-seller-price-label text-muted font-13">' + labelText + '</div>';
                }
            }

            /* قیمت فروش لحظه‌ای + فلش بصری */
            var salePriceEl = priceBox.querySelector('#price-real .product-seller-price-prev');
            if (salePriceEl && salePriceEl.textContent !== match.sale) {
                salePriceEl.textContent = match.sale;
                salePriceEl.classList.remove('dt-price-updated');
                /* شروع مجدد انیمیشن */
                void salePriceEl.offsetWidth;
                salePriceEl.classList.add('dt-price-updated');
            }
        }

        /* دکمهٔ افزودن به سبد — قیمت تنوع انتخاب‌شده */
        document.querySelectorAll('.product-info-block .btn-add-to-cart[data-price_id]').forEach(function (btn) {
            btn.setAttribute('data-price_id', String(match.id));
        });

        /* مینی‌باکس (در صورت وجود در قالب‌های مشتق) */
        var miniPrice = document.querySelector('.product-mini-seller-price-real .js-price-value');
        if (miniPrice) miniPrice.textContent = match.sale;
    }

    document.addEventListener('change', function (event) {
        var target = event.target;
        if (!target || target.tagName !== 'INPUT' || target.type !== 'radio') return;
        if (!target.closest('.product-info-block')) return;

        applyVariantPriceInstantly();
    }, true);

    /* ---------------------------------------------------------
       ۲) گالری — تعویض تصویر با بندانگشتی‌ها (جایگزین ez-plus)
       --------------------------------------------------------- */
    document.addEventListener('click', function (event) {
        var thumb = event.target.closest('.js-gallery-thumb');
        if (!thumb) return;

        event.preventDefault();

        var mainImage = document.querySelector('.product-page .pro_gallery .product-main-image');
        if (!mainImage || !thumb.dataset.image) return;

        mainImage.setAttribute('src', thumb.dataset.image);

        document.querySelectorAll('#gallery_01f .js-gallery-thumb').forEach(function (item) {
            item.classList.remove('active');
        });
        thumb.classList.add('active');
    });

    /* ---------------------------------------------------------
       ۳) ویجت ستاره‌ای فرم نظر — متن توصیفی کنار ستاره‌ها
       --------------------------------------------------------- */
    document.addEventListener('change', function (event) {
        var target = event.target;
        if (!target || !target.matches('.review-star-rating input[type="radio"]')) return;

        var label = document.querySelector('.review-star-rating .review-star[data-value="' + target.value + '"]');
        var textNode = document.getElementById('review-rating-text');
        if (textNode) {
            textNode.textContent = label && label.dataset.text ? label.dataset.text : '';
        }
    });

    /* ---------------------------------------------------------
       ۴) کپی شناسه محصول از ردیف فروشنده (tooltip «کپی شناسه محصول»)
       --------------------------------------------------------- */
    document.addEventListener('click', function (event) {
        var skuLabel = event.target.closest('.sku-label');
        if (!skuLabel) return;

        var valueNode = skuLabel.querySelector('[dir="ltr"]');
        if (!valueNode || !navigator.clipboard) return;

        navigator.clipboard.writeText(valueNode.textContent.trim()).catch(function () {
            /* سکوت — کپی فقط افزونهٔ راحتی است */
        });
    });

    /* ---------------------------------------------------------
       ۵) جایگزین product-page-init.js — صفحه‌بند نظرات در حالت
          «بدون هیچ نظری» مخفی شود
       --------------------------------------------------------- */
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('#Newscomments').forEach(function (container) {
            var hasReviews = container.querySelectorAll('.comments-list .article').length > 0;
            var pager = container.querySelector('.paginationPager');
            if (pager && !hasReviews) {
                pager.classList.add('hide');
            }
        });
    });
})();
