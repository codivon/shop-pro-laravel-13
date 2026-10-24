/**
 * سانیار فاز K — آرشیو مقالات: مرتب‌سازی/جستجو/فیلتر/صفحه‌بندی AJAX سبک
 * داده‌ها از ArticleController@index با هدر X-Requested-With (پاسخ JSON:
 * data/pagination/total). بدون هیچ JS خطی در ویو — همه از data-attr.
 */
(function () {
    'use strict';

    var bar = document.querySelector('[data-saniar-articles-index]');
    if (!bar || bar.dataset.saniarInit === '1') return;
    bar.dataset.saniarInit = '1';

    var CSRF = document.querySelector('meta[name="csrf-token"]');
    var baseUrl = bar.dataset.baseUrl || window.location.pathname;
    var currentParams = new URLSearchParams(window.location.search);
    var request = null;

    var listContainer = document.getElementById('articles-container');
    var paginationContainer = document.getElementById('pagination-container');
    var totalCounter = document.getElementById('articles-total-counter');

    function toast(message, type) {
        var el = document.createElement('div');
        el.className = 'saniar-toast alert alert-' + (type === 'error' ? 'danger' : 'success') +
            ' position-fixed shadow-box rounded-3 px-3 py-2 font-14';
        el.style.cssText = 'top:84px;left:18px;z-index:2000;max-width:340px;';
        el.textContent = message;
        document.body.appendChild(el);
        setTimeout(function () { el.remove(); }, 3200);
    }

    function setLoading(on) {
        if (listContainer) listContainer.style.opacity = on ? '0.45' : '1';
    }

    function applyDom(data) {
        if (listContainer && data.data) listContainer.innerHTML = data.data;
        if (paginationContainer) paginationContainer.innerHTML = data.pagination || '';
        if (totalCounter && typeof data.total !== 'undefined') {
            totalCounter.textContent = new Intl.NumberFormat('fa-IR').format(data.total) + ' مقاله منتشر شده';
        }
    }

    function fetchPage(overrides, pushUrl) {
        if (request) request.abort();

        var params = new URLSearchParams(currentParams.toString());
        Object.keys(overrides).forEach(function (key) {
            if (overrides[key] === null || overrides[key] === '') params.delete(key);
            else params.set(key, overrides[key]);
        });

        setLoading(true);
        request = $.ajax({
            url: baseUrl,
            type: 'GET',
            data: params.toString(),
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            success: function (data) {
                applyDom(data);
                currentParams = params;
                if (pushUrl) {
                    var qs = params.toString();
                    window.history.replaceState({}, '', window.location.pathname + (qs ? '?' + qs : ''));
                }
            },
            error: function (xhr) {
                if (xhr.statusText !== 'abort') toast('خطا در دریافت مقالات', 'error');
            },
            complete: function () {
                setLoading(false);
                request = null;
            }
        });
    }

    // مرتب‌سازی
    bar.querySelectorAll('.nav-link[data-sort]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            bar.querySelectorAll('.nav-link[data-sort]').forEach(function (b) { b.classList.remove('active'); });
            btn.classList.add('active');
            fetchPage({ sort: btn.dataset.sort === 'latest' ? null : btn.dataset.sort, page: null }, true);
        });
    });

    // جستجو (فرم + دبونس)
    var searchForm = document.querySelector('[data-saniar-articles-search]');
    if (searchForm) {
        var input = searchForm.querySelector('input[name="q"]');
        var timer = null;

        searchForm.addEventListener('submit', function (e) {
            e.preventDefault();
            fetchPage({ q: input.value.trim(), page: null }, true);
        });

        if (input) {
            input.addEventListener('input', function () {
                clearTimeout(timer);
                timer = setTimeout(function () {
                    fetchPage({ q: input.value.trim(), page: null }, true);
                }, 450);
            });
        }
    }

    // صفحه‌بندی (delegated روی کانتینر — HTML بعد از هر fetch عوض می‌شود)
    if (paginationContainer) {
        paginationContainer.addEventListener('click', function (e) {
            var link = e.target.closest('a');
            if (!link) return;
            e.preventDefault();
            var page = new URL(link.href, window.location.origin).searchParams.get('page') || '1';
            fetchPage({ page: page }, true);
            var top = bar.getBoundingClientRect().top + window.scrollY - 120;
            window.scrollTo({ top: top, behavior: 'smooth' });
        });
    }
})();
