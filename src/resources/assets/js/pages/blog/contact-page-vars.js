/**
 * SaniarTheme — متغیرهای سراسری صفحه تماس با ما
 * (جایگزین اسکریپت اینلاین بلید — مقادیر از data-* خوانده می‌شوند)
 */
(function () {
    'use strict';

    var el = document.getElementById('contact-page-vars');
    if (!el) { return; }

    var dataset = el.dataset;

    window.info_map_type = dataset.mapType || 'google';
    window.info_latitude = dataset.latitude || '38.07709880960678';
    window.info_Longitude = dataset.longitude || '46.28582686185837';
    window.info_site_title = dataset.siteTitle || 'او پی شاپ';
    window.mapIrApiKey = dataset.mapirKey || '';
})();
