/**
 * متغیرهای سراسری صفحه معرفی (referrals)
 * مقدارها از data-attributeهای #referral-data خوانده می‌شوند
 * (جایگزین بلوک <script> داخل بلید).
 * باید قبل از js/pages/profile/referrals.js لود شود.
 */
(function () {
    'use strict';

    var el = document.getElementById('referral-data');
    if (!el) {
        return;
    }

    window.referralLink = el.getAttribute('data-link') || '';
    window.referralCode = el.getAttribute('data-code') || '';
})();
