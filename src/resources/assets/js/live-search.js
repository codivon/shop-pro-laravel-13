/**
 * سانیار — جستجوی زنده هدر
 * روی همه صفحات لود می‌شود؛ دراپ‌داون نتایج زیر فیلد جستجو (دسکتاپ و موبایل).
 * رفتار: تایپ ≥ ۲ کاراکتر → درخواست debounce شده به front.products.ajax_search → رندر partial.
 *
 * سانیار فاز K — سازگار با SPA (wire:navigate): این فایل data-navigate-once ندارد
 * و در هر ناوبری مجدداً اجرا می‌شود؛ اما DOMContentLoaded قبلاً رخ داده، پس با
 * الگوی readyState هم در بارگذاری اول و هم در ناوبری‌های SPA initialize می‌شود.
 */
(function () {
    'use strict';

    var SEARCH_URL = document.querySelector('meta[name="saniar-ajax-search"]')
        ? document.querySelector('meta[name="saniar-ajax-search"]').content
        : null;

    if (!SEARCH_URL) return;

    var CSRF = document.querySelector('meta[name="csrf-token"]');
    var debounceTimer = null;
    var currentRequest = null;

    function fieldWrap(input) {
        return input.closest('.search-filed');
    }

    function getOrCreateDropdown(input) {
        var wrap = fieldWrap(input);
        if (!wrap) return null;
        var box = wrap.querySelector('.search-live-box');
        if (!box) {
            box = document.createElement('div');
            box.className = 'search-live-box position-relative w-100';
            wrap.appendChild(box);
        }
        return box;
    }

    function hideDropdown(input) {
        var box = getOrCreateDropdown(input);
        if (box) box.innerHTML = '';
    }

    function showLoading(input) {
        var box = getOrCreateDropdown(input);
        if (!box) return;
        box.innerHTML = '<div class="search-live-dropdown sld-loading" aria-busy="true"></div>';
    }

    function render(input, html) {
        var box = getOrCreateDropdown(input);
        if (!box) return;
        box.innerHTML = html;

        // بستن با کلیک بیرون
        document.addEventListener('click', function onDocClick(e) {
            if (!box.contains(e.target) && e.target !== input) {
                box.innerHTML = '';
                document.removeEventListener('click', onDocClick);
            }
        });

        // بستن با Escape
        input.addEventListener('keydown', function onEsc(e) {
            if (e.key === 'Escape') {
                box.innerHTML = '';
                input.removeEventListener('keydown', onEsc);
            }
        });
    }

    function doSearch(input) {
        var q = input.value.trim();

        if (q.length < 2) {
            hideDropdown(input);
            return;
        }

        if (currentRequest) currentRequest.abort();

        showLoading(input);

        currentRequest = $.ajax({
            url: SEARCH_URL,
            type: 'POST',
            data: { q: q },
            beforeSend: function (xhr) {
                if (CSRF) xhr.setRequestHeader('X-CSRF-TOKEN', CSRF.content);
                xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            },
            success: function (data) {
                if (data && data.html) {
                    render(input, data.html);
                }
            },
            error: function (xhr) {
                if (xhr.statusText !== 'abort') hideDropdown(input);
            },
            complete: function () {
                currentRequest = null;
            }
        });
    }

    function initLiveSearch() {
        document.querySelectorAll('.search-input[name="q"]').forEach(function (input) {
            if (input.dataset.saniarSearchInit === '1') return;
            input.dataset.saniarSearchInit = '1';
            input.setAttribute('autocomplete', 'off');
            input.addEventListener('input', function () {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(function () { doSearch(input); }, 350);
            });
            // جلوگیری از ارسال فرم وقتی کاربر فقط می‌خواهد از نتایج استفاده کند؟ نه — اینتر = جستجوی کامل صفحه
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initLiveSearch);
    } else {
        initLiveSearch();
    }
})();
