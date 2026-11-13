$(document).ready(function() {
    let isLoading = false;

    // تابع دریافت پارامترها از URL
    function getFiltersFromUrl() {
        let urlParams = new URLSearchParams(window.location.search);
        let filters = {
            sort: urlParams.get('sort') || 'latest',
            page: urlParams.get('page') || 1
        };

        if (urlParams.get('cat')) {
            filters.cat = urlParams.get('cat');
        }

        if (urlParams.get('tag')) {
            filters.tag = urlParams.get('tag');
        }

        if (urlParams.get('profile')) {
            filters.profile = urlParams.get('profile');
        }

        return filters;
    }

    // تابع بروزرسانی URL
    function updateUrl(filters) {
        let url = new URL(window.location.href);

        // پاک کردن پارامترهای قبلی
        url.searchParams.delete('sort');
        url.searchParams.delete('cat');
        url.searchParams.delete('tag');
        url.searchParams.delete('page');
        url.searchParams.delete('profile');

        // اضافه کردن پارامترهای جدید
        if (filters.sort) {
            url.searchParams.set('sort', filters.sort);
        }

        if (filters.cat) {
            url.searchParams.set('cat', filters.cat);
        }

        if (filters.tag) {
            url.searchParams.set('tag', filters.tag);
        }

        if (filters.profile) {
            url.searchParams.set('profile', filters.profile);
        }


        if (filters.page && filters.page != 1) {
            url.searchParams.set('page', filters.page);
        }

        window.history.pushState({}, '', url);
    }

    // تابع بارگذاری مقالات
    function loadArticles(filters, shouldUpdateUrl = true) {
        block('.articles-index-page')
        if (isLoading) return;
        isLoading = true;

        $('#articles-container').html('<div class="text-center py-5"><div class="spinner-border text-primary" role="status"></div></div>');
        $('#pagination-container').html('');

        $.ajax({
            url: articlesIndexUrl,
            type: 'GET',
            data: filters,
            success: function(response) {
                $('#articles-container').html(response.data);
                $('#pagination-container').html(response.pagination);

                if (shouldUpdateUrl) {
                    updateUrl(filters);
                }

                // بروزرسانی کلاس active فیلترها
                $('.filter-link').removeClass('active');
                let sortValue = filters.sort || 'latest';
                $(`.filter-link[data-sort="${sortValue}"]`).addClass('active');
            },
            error: function(xhr) {
                $('#articles-container').html('<div class="text-center py-5 text-danger">خطا در بارگذاری مقالات</div>');
            },
            complete: function() {
                isLoading = false;
                unblock('.articles-index-page')
            }
        });
    }

    // رویداد کلیک روی فیلترها (همه با data-sort)
    $('.filter-link[data-sort]').on('click', function() {
        let filters = getFiltersFromUrl();
        filters.sort = $(this).data('sort');
        filters.page = 1;
        loadArticles(filters, true);
    });

    // رویداد کلیک روی صفحه‌بندی
    $(document).on('click', '.pagination a', function(e) {
        e.preventDefault();
        let href = $(this).attr('href');
        let pageMatch = href.match(/[?&]page=(\d+)/);
        if (pageMatch) {
            let filters = getFiltersFromUrl();
            filters.page = pageMatch[1];
            loadArticles(filters, true);
        }
    });

    // بازگشت به صفحه قبل با دکمه back/forward
    window.addEventListener('popstate', function() {
        let filters = getFiltersFromUrl();
        loadArticles(filters, false);
    });
});
